<?php

namespace App\Controller\Api;

use App\Controller\AbstractApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractApiController
{
    #[Route('/', name: 'accounts')]
    public function accounts(Request $request): Response
    {
        dd(
            $this->homeAssistant->getSupervisorNetworkInfo()
        );
    }
}
