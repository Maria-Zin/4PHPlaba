<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('email_notifications')]
final class AuthorNotificationMessage
{
    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $subject,
        public readonly string $text,
    ) {
    }
}