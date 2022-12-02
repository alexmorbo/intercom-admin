<?php

namespace App\Command;

use App\Enum\Provider\AuthScheme;
use App\Exceptions\ProviderClientException;
use App\Provider\DomRu\Dto\Auth\RequestConfirmSms;
use App\Provider\DomRu\Dto\Auth\RequestSms;
use App\Provider\ProviderRegistry;
use App\Service\HomeAssistant;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'account:add'
)]
class AccountAddCommand extends Command
{
    public function __construct(protected ProviderRegistry $providerRegistry, protected HomeAssistant $homeAssistant)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('provider', 'p', InputOption::VALUE_OPTIONAL, 'Provider');
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

        $provider = $this->providerRegistry->getProvider($providerKey);
        if (!$provider) {
            $io->error('Provider not found');

            return Command::FAILURE;
        }

        try {
            if ($provider->getAuthScheme() === AuthScheme::AddressFirst) {
                $io->section('Provider request <b>AddressFirst</b> auth type');
                $phone = $provider->formatPhone(
                    $io->ask('Enter phone number')
                );
                $io->text('Send sms code to phone number: ' . $phone);

                $addressRequest = $provider->getSyncClient()->requestAddressForSms($phone);
                $io->table(
                    ['Id', 'Address', 'Operator', 'Account'],
                    array_map(
                        fn($address) => [$address->id, $address->address, $address->operatorId, $address->accountId],
                        $addressRequest
                    )
                );

                $addressId = (int)$io->ask('Select address id');
                $selectedAddress = null;
                foreach ($addressRequest as $address) {
                    if ($address->id === $addressId) {
                        $io->text('Send sms code for account: ' . $address->accountId);
                        $selectedAddress = $address;
                        $provider->getSyncClient()->requestSmsAuth(
                            (new RequestSms())->populate([
                                'phone' => $phone,
                                'deviceId' => $address->deviceId,
                                'operatorId' => $address->operatorId,
                                'placeId' => $address->placeId,
                                'address' => $address->address,
                                'subscriberId' => $address->subscriberId,
                                'accountId' => $address->accountId,
                            ])
                        );

                        break;
                    }
                }

                if (!$selectedAddress) {
                    $io->error('Address not selected');

                    return Command::FAILURE;
                }

                $io->text('Waiting sms code...');
                $confirmationCode = $io->ask('Enter sms code');

                $authDto = $provider->getSyncClient()->confirmSmsAuth(
                    (new RequestConfirmSms())->populate([
                        'phone' => $phone,
                        'deviceId' => $selectedAddress->deviceId,
                        'login' => $phone,
                        'operatorId' => $selectedAddress->operatorId,
                        'subscriberId' => $selectedAddress->subscriberId,
                        'accountId' => $selectedAddress->accountId,
                        'confirm1' => $confirmationCode,
                    ])
                );

                dump($authDto);

                if ($this->homeAssistant->saveProviderAuthData($provider, $authDto)) {
                    $io->success('Account added');

                    return Command::SUCCESS;
                } else {
                    $io->error('Account not added');

                    return Command::FAILURE;
                }
            } else {
                $io->error('Provider is not SmsFirst');
            }
        } catch (ProviderClientException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}


