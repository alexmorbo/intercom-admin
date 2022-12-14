<?php

namespace App\Controller;

use App\Provider\ProviderRegistry;
use App\Service\HomeAssistant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractApiController extends AbstractController
{
    public function __construct(
        protected HomeAssistant $homeAssistant,
        protected ProviderRegistry $providerRegistry,
    )
    {
    }
}
