<?php

namespace App\MessageHandler;

use App\Message\NewProviderAuthMessage;
use App\Message\RemoveAccountMessage;
use App\Provider\ProviderRegistry;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

final class ProviderMessagesHandler implements MessageSubscriberInterface
{
    public function __construct(private ProviderRegistry $providerRegistry)
    {
    }

    public static function getHandledMessages(): iterable
    {
        yield NewProviderAuthMessage::class => ['method' => 'onNewProviderAuthMessage'];
        yield RemoveAccountMessage::class => ['method' => 'onRemoveAccountMessage'];
    }

    public function onNewProviderAuthMessage(NewProviderAuthMessage $message): void
    {
        $this->providerRegistry->pushAccount($message->getAuthDto());
    }

    public function onRemoveAccountMessage(RemoveAccountMessage $message): void
    {
        $this->providerRegistry->removeAccount($message->getAuthDto());
    }
}
