<?php

namespace App\MessageHandler;

use App\Message\AuthorNotificationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Throwable;

#[AsMessageHandler]
final class AuthorNotificationMessageHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $mailerFrom = "no-reply@example.com",
    ) {}

    public function __invoke(AuthorNotificationMessage $message): void
    {
        $this->logger->info("Start author notification email handling.", [
            "recipient_email" => $message->recipientEmail,
            "subject" => $message->subject,
        ]);

        try {
            $email = new Email()
                ->from($this->mailerFrom)
                ->to($message->recipientEmail)
                ->subject($message->subject)
                ->text($message->text);

            $this->mailer->send($email);

            $this->logger->info(
                "Author notification email sent successfully.",
                [
                    "recipient_email" => $message->recipientEmail,
                    "subject" => $message->subject,
                ],
            );
        } catch (Throwable $exception) {
            $this->logger->error("Failed to send author notification email.", [
                "recipient_email" => $message->recipientEmail,
                "subject" => $message->subject,
                "exception" => $exception,
            ]);
        }
    }
}