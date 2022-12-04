<?php

namespace App\Controller\Api;

use App\Controller\AbstractApiController;
use App\Model\Provider\Account;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class MainController extends AbstractApiController
{
    #[Route('/api/accounts', name: 'api_accounts')]
    public function accounts(SerializerInterface $serializer): Response
    {
        $haAccounts = $this->homeAssistant->getAccounts();
        $accounts = [];
        foreach ($haAccounts as $providerKey => $providerAccounts) {
            $provider = $this->providerRegistry->getProvider($providerKey);
            foreach ($providerAccounts as $authDto) {
                $accounts[$providerKey][] = (new Account($authDto, $provider))->getMainData();
            }
        }

        return new Response($serializer->serialize($accounts, 'json', ['groups' => ['publicApi']]));
    }

    #[Route('/api/account/{providerKey}/{accountId}', name: 'api_account')]
    public function account(string $providerKey, string $accountId, SerializerInterface $serializer): Response
    {
        $provider = $this->providerRegistry->getProvider($providerKey);
        $authDto = $this->homeAssistant->getAccount($provider, $accountId);

        return new Response($serializer->serialize((new Account($authDto, $provider))->getMainData(), 'json', ['groups' => ['publicApi']]));
    }
}
