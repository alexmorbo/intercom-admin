<?php

namespace App\Command;

use App\Controller\Api\AccountsController;
use App\Kernel;
use App\Provider\ProviderRegistry;
use DateTime;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use SergiX44\Hydrator\Exception\InvalidValueException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Throwable;

#[AsCommand(
    name: 'server:run',
    description: 'Server runner',
)]
class ServerCommand extends Command
{
    private string $dbPath;

    public function __construct(private ProviderRegistry $providerRegistry, Kernel $kernel)
    {
        $this->dbPath = $kernel->getProjectDir() . '/data/data.db';
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('ip', null, InputOption::VALUE_OPTIONAL, 'Listen ip')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Listen port');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!($ip = $input->getOption('ip'))) {
            $ip = $io->ask('Enter listen ip');
        }
        if (!($port = $input->getOption('port'))) {
            $port = (int)$io->ask('Enter listen port');
        }

        Loop::addPeriodicTimer(5, function () {
            echo sprintf(
                '[%s] Current usage %f mb, Max: %f mb' . PHP_EOL,
                (new DateTime())->getTimestamp(),
                round(memory_get_usage() / 1024 / 1024, 2) . ' mb',
                round(memory_get_peak_usage() / 1024 / 1024, 2) . ' mb'
            );
        });

        $routes = $this->initRoutes();

        foreach ($this->providerRegistry->getProviders() as $provider) {
            /** @var Route $route */
            foreach ($provider->getRoutes() as $name => $route) {
                $routes->add($name, $route);
            }
        }

        $http = new HttpServer(function (ServerRequestInterface $request) use ($io, $routes) {
            $context = new RequestContext(
                method: $request->getMethod(),
                path: $request->getUri()->getPath(),
                queryString: $request->getUri()->getQuery(),
            );

            $matcher = new UrlMatcher($routes, $context);
            try {
                $parameters = $matcher->match($request->getUri()->getPath());
                $controllerName = $parameters['_controller'];
                $controller = new $controllerName(
                    $this->providerRegistry,
                    $request
                );

                /**
                 * Get all parameters from $parameters except starts with _
                 */
                $cleanParameters = array_filter(
                    $parameters,
                    fn($key) => strpos($key, '_') !== 0,
                    ARRAY_FILTER_USE_KEY
                );

                return call_user_func([$controller, $parameters['_method']], ...$cleanParameters)
                    ->then(
                        function (array $data) {
                            return new Response(
                                $data['code'] ?? 200,
                                $data['headers'] ?? ['Content-Type' => 'application/json'],
                                $data['json'] ?? json_encode($data['data'], JSON_UNESCAPED_UNICODE)
                            );
                        },
                        fn(Throwable $e) => new Response(
                            $e->getCode(),
                            ['Content-Type' => 'application/json'],
                            json_encode(['error' => $e->getMessage()])
                        )
                    );
            } catch (InvalidValueException|BadRequestHttpException|ResourceNotFoundException|MethodNotAllowedException $e) {
                $code = $e->getCode() ?: 404;
                $message = $e->getMessage();
                if ($e instanceof MethodNotAllowedException) {
                    $code = 405;
                    $message = 'Method not allowed, allowed: '.implode(', ', $e->getAllowedMethods());
                }
                return new Response(
                    $code,
                    ['Content-Type' => 'application/json'],
                    json_encode(['error' => $message])
                );
            } catch (Throwable $e) {
                dump($e);
                return new Response(
                    404,
                    ['Content-Type' => 'application/json'],
                    json_encode(['error' => $e->getMessage()])
                );
            }
        });

        $socket = new SocketServer(sprintf('%s:%d', $ip, $port));
        $http->listen($socket);
        $http->on('error', fn() => dd(func_get_args()));

        echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;

        $this->providerRegistry->loadAccounts();

        return Command::SUCCESS;
    }

    private function initRoutes(): RouteCollection
    {
        $routes = new RouteCollection();

        $routes->add(
            'accounts_index',
            new Route(
                '/api/accounts',
                [
                    '_controller' => AccountsController::class,
                    '_method' => 'index',
                ],
                methods: ['GET']
            )
        );

        $routes->add(
            'account_delete',
            new Route(
                '/api/account/{provider}/{accountId}',
                [
                    '_controller' => AccountsController::class,
                    '_method' => 'delete',
                ],
                methods: ['DELETE']
            )
        );

        return $routes;
    }
}
