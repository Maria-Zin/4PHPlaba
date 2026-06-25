<?php

namespace App\Scheduler;

use App\Message\StatisticsDigestMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule("statistics_digest")]
final class StatisticsDigestSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {}

    public function getSchedule(): Schedule
    {
        $this->logger->info("Building statistics digest schedule.");

        return new Schedule()
            ->add(
                RecurringMessage::every(
                    "1 hour",
                    new StatisticsDigestMessage(),
                ),
            )
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);
    }
}