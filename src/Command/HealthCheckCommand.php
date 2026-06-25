<?php

namespace App\Command;

use App\Service\HealthCheckService;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
        name: "app:health-check",
        description: "Checks health of configured databases, external APIs, cache, mailer, messenger and server.",
    ),
]
final class HealthCheckCommand extends Command
{
    public function __construct(
        private HealthCheckService $healthCheckService,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            "service",
            InputArgument::REQUIRED,
            "Service to check: database_dev, database_test, api_weather, api_universities, cache, mailer, messenger, server, all",
        )->addOption(
            "format",
            null,
            InputOption::VALUE_REQUIRED,
            "Output format: json or table",
            "table",
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $service = (string) $input->getArgument("service");
        $format = (string) $input->getOption("format");

        if (!in_array($service, HealthCheckService::AVAILABLE_SERVICES, true)) {
            $io->error(sprintf('Unknown service "%s".', $service));

            return Command::INVALID;
        }

        if (!in_array($format, ["json", "table"], true)) {
            $io->error(sprintf('Unknown output format "%s".', $format));

            return Command::INVALID;
        }

        $this->logger->info("Health check command started.", [
            "service" => $service,
            "format" => $format,
        ]);

        $results = $this->healthCheckService->runChecks($service);
        $hasFailures = false;
        foreach ($results as $result) {
            if ($result["status"] === "FAIL") {
                $hasFailures = true;
                break;
            }
        }

        if ($format === "json") {
            try {
                $io->writeln(
                    json_encode(
                        $results,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_UNICODE |
                            JSON_THROW_ON_ERROR,
                    ),
                );
            } catch (JsonException $exception) {
                $this->logger->error(
                    "Failed to encode health check results to JSON.",
                    [
                        "exception" => $exception,
                    ],
                );
                $io->error("Failed to encode health check results to JSON.");

                return Command::FAILURE;
            }
        } else {
            $rows = array_map(
                static fn(array $result): array => [
                    $result["name"],
                    $result["status"],
                    $result["message"],
                    $result["details"] === []
                        ? "—"
                        : json_encode(
                            $result["details"],
                            JSON_UNESCAPED_UNICODE,
                        ),
                ],
                $results,
            );

            $io->table(["Service", "Status", "Message", "Details"], $rows);
        }

        if ($hasFailures) {
            $io->warning("One or more health checks failed.");
            $this->logger->warning(
                "Health check command finished with failures.",
                [
                    "service" => $service,
                ],
            );

            return Command::FAILURE;
        }

        $io->success("All requested health checks passed.");
        $this->logger->info("Health check command finished successfully.", [
            "service" => $service,
        ]);

        return Command::SUCCESS;
    }
}