<?php

namespace App\MessageHandler;

use App\Message\StatisticsDigestMessage;
use App\Repository\UserRepository;
use App\Service\StatisticsService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final class StatisticsDigestMessageHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private StatisticsService $statisticsService,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $mailerFrom = "no-reply@example.com",
    ) {}

    public function __invoke(StatisticsDigestMessage $message): void
    {
        $triggeredAt = new DateTimeImmutable()->format(DATE_ATOM);

        $this->logger->info("Start statistics digest handling.", [
            "triggered_at" => $triggeredAt,
        ]);

        $admins = $this->userRepository->findAdminUsers();

        if ($admins === []) {
            $this->logger->warning(
                "Statistics digest skipped because no administrators were found.",
            );

            return;
        }

        $statistics = $this->statisticsService->getStatistics();

        foreach ($admins as $admin) {
            $emailAddress = $admin->getEmail();

            if ($emailAddress === null || $emailAddress === "") {
                $this->logger->warning(
                    "Skip statistics digest for admin without email.",
                    [
                        "user_id" => $admin->getId(),
                    ],
                );

                continue;
            }

            try {
                $email = new TemplatedEmail()
                    ->from($this->mailerFrom)
                    ->to($emailAddress)
                    ->subject("Ежечасная статистика по платформе")
                    ->htmlTemplate("email/statistics_digest.html.twig")
                    ->context([
                        "statistics" => $statistics,
                        "triggeredAt" => $triggeredAt,
                    ]);

                $this->mailer->send($email);

                $this->logger->info(
                    "Statistics digest email sent successfully.",
                    [
                        "admin_email" => $emailAddress,
                    ],
                );
            } catch (Throwable $exception) {
                $this->logger->error(
                    "Failed to send statistics digest email.",
                    [
                        "admin_email" => $emailAddress,
                        "exception" => $exception,
                    ],
                );
            }
        }
    }
}