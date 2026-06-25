<?php

namespace App\MessageHandler;

use App\Message\HealthCheckPingMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class HealthCheckPingMessageHandler
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(HealthCheckPingMessage $message): void
    {
        $this->logger->info('Health check ping message handled successfully.', [
            'payload' => $message->payload,
        ]);
    }
}