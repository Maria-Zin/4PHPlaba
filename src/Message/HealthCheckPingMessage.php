<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('sync')]
final class HealthCheckPingMessage
{
    public function __construct(
        public readonly string $payload,
    ) {
    }
}