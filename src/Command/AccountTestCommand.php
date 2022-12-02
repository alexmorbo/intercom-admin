<?php

namespace App\Command;

use App\Exceptions\ProviderNotExistsException;
use App\Provider\ProviderRegistry;
use App\Service\HomeAssistant;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'account:test'
)]
class AccountTestCommand extends Command
{
    public function __construct(
        protected ProviderRegistry $providerRegistry,
        protected HomeAssistant $homeAssistant
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('provider', 'p', InputOption::VALUE_OPTIONAL, 'Provider')
            ->addOption('account', 'a', InputOption::VALUE_OPTIONAL, 'Account');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!($providerKey = $input->getOption('provider'))) {
            $io->warning('Provider is missing');
            $providerKey = $io->ask('Enter provider key');

            if (!$providerKey) {
                $io->error('Provider is missing');

                return Command::INVALID;
            }
        }

        if (!($accountKey = $input->getOption('account'))) {
            $io->warning('Account is missing');
            $accountKey = $io->ask('Enter account key');

            if (!$accountKey) {
                $io->error('Account is missing');

                return Command::INVALID;
            }
        }

        $provider = $this->providerRegistry->getProvider($providerKey);
        if (!$provider) {
            $io->error('Provider not found');

            return Command::FAILURE;
        }

        $account = $this->homeAssistant->getAccount($provider, $accountKey);
        $client = $provider->getSyncClient($account)->withAuth($account);

        dd(
//            iterator_to_array($client->fetchSubscriberPlaces())
            $client->getUserInfo()->subscriberPlaces[0],
            $client->getUserBalance(),
        );

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
