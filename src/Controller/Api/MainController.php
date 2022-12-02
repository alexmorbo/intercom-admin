<?php

namespace App\Controller\Api;

use App\Controller\AbstractApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractApiController
{
    #[Route('/api/accounts', name: 'accounts')]
    public function accounts(): Response
    {
        dd($this->homeAssistant);
    }
}
