<?php

namespace App\Controller;

use App\Model\Provider\Account;
use App\Provider\ProviderRegistry;
use App\Service\HomeAssistant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    #[Route('/', name: 'app_front')]
    public function index(
        HomeAssistant $homeAssistant,
        ProviderRegistry $providerRegistry,
    ): Response
    {
        $haAccounts = $homeAssistant->getAccounts();
        $accounts = [];
        foreach ($haAccounts as $providerKey => $providerAccounts) {
            $provider = $providerRegistry->getProvider($providerKey);
            foreach ($providerAccounts as $authDto) {
                $accounts[] = new Account($authDto, $provider);
            }
        }

        return $this->render('front/index.html.twig', [
            'accounts' => $accounts,
        ]);
    }
}
