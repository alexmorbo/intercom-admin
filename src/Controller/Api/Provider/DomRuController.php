<?php

namespace App\Controller\Api\Provider;

use App\Controller\AbstractApiController;
use App\Model\Provider\Account;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DomRuController extends AbstractApiController
{
    public const PROVIDER_KEY = 'domru';

    #[Route('/api/domru/{accountId}/camera/{cameraId}/snapshot', name: 'api_domru_camera_snapshot')]
    public function cameraSnapshot(string $accountId, int $cameraId): Response
    {
        $provider = $this->providerRegistry->getProvider(self::PROVIDER_KEY);
        $account = new Account($this->homeAssistant->getAccount($provider, $accountId), $provider);

        return new Response($account->getCameraSnapshot($cameraId), 200, [
            'Content-Type' => 'image/jpeg',
        ]);
    }

    #[Route('/api/domru/{accountId}/camera/{cameraId}/video', name: 'api_domru_camera_video_stream')]
    public function cameraVideoStream(string $accountId, int $cameraId): Response
    {
        $provider = $this->providerRegistry->getProvider(self::PROVIDER_KEY);
        $account = new Account($this->homeAssistant->getAccount($provider, $accountId), $provider);
        try {
            $videoStreamUrl = $account->getCameraVideoStream($cameraId);
            return new RedirectResponse($videoStreamUrl);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getCode()], 500);
        }
    }

    #[Route('/api/domru/{accountId}/place/{placeId}/relay/{relayId}/open', name: 'api_domru_relay_open')]
    public function relayOpen(string $accountId, int $placeId, int $relayId): Response
    {
        $provider = $this->providerRegistry->getProvider(self::PROVIDER_KEY);
        $account = new Account($this->homeAssistant->getAccount($provider, $accountId), $provider);
        try {
            $account->openRelay($placeId, $relayId);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getCode()], 500);
        }
    }
}