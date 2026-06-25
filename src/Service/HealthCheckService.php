<?php

namespace App\Service;

use App\Message\HealthCheckPingMessage;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class HealthCheckService
{
    public const AVAILABLE_SERVICES = [
        "database_dev",
        "database_test",
        "api_weather",
        "api_universities",
        "cache",
        "mailer",
        "messenger",
        "server",
        "all",
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheItemPoolInterface $appCache,
        private MailerInterface $mailer,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private string $databaseUrl,
        private string $weatherApiUrl,
        private string $universitiesApiUrl,
        private string $healthCheckServerUrl,
        private string $healthCheckMailTo,
        private string $mailerFrom = "no-reply@example.com",
    ) {}

    /**
     * @return list<array{name: string, status: string, message: string, details: array<string, mixed>}>
     */
    public function runChecks(string $serviceName): array
    {
        $services =
            $serviceName === "all"
                ? array_values(
                    array_filter(
                        self::AVAILABLE_SERVICES,
                        static fn(string $name): bool => $name !== "all",
                    ),
                )
                : [$serviceName];

        $results = [];
        foreach ($services as $service) {
            $results[] = $this->runCheck($service);
        }

        return $results;
    }

    /**
     * @return array{name: string, status: string, message: string, details: array<string, mixed>}
     */
    public function runCheck(string $serviceName): array
    {
        $this->logger->info("Starting health check.", [
            "service" => $serviceName,
        ]);

        try {
            return match ($serviceName) {
                "database_dev" => $this->checkDatabase(
                    "database_dev",
                    $this->databaseUrl,
                ),
                "database_test" => $this->checkDatabase(
                    "database_test",
                    $this->resolveTestDatabaseUrl(),
                ),
                "api_weather" => $this->checkHttpEndpoint(
                    "api_weather",
                    $this->weatherApiUrl,
                ),
                "api_universities" => $this->checkHttpEndpoint(
                    "api_universities",
                    $this->universitiesApiUrl,
                ),
                "cache" => $this->checkCache(),
                "mailer" => $this->checkMailer(),
                "messenger" => $this->checkMessenger(),
                "server" => $this->checkHttpEndpoint(
                    "server",
                    $this->healthCheckServerUrl,
                    2,
                    200,
                ),
                default => $this->createResult(
                    $serviceName,
                    "FAIL",
                    "Unknown health-check service.",
                    [
                        "available_services" => self::AVAILABLE_SERVICES,
                    ],
                ),
            };
        } catch (Throwable $exception) {
            $this->logger->error("Health check crashed unexpectedly.", [
                "service" => $serviceName,
                "exception" => $exception,
            ]);

            return $this->createResult(
                $serviceName,
                "FAIL",
                $exception->getMessage(),
                [
                    "exception" => $exception::class,
                ],
            );
        }
    }

    /**
     * @return array{name: string, status: string, message: string, details: array<string, mixed>}
     */
    private function checkDatabase(string $name, string $databaseUrl): array
    {
        try {
            $parser = new DsnParser([
                "postgresql" => "pdo_pgsql",
                "pgsql" => "pdo_pgsql",
                "sqlite" => "pdo_sqlite",
            ]);

            $connection = DriverManager::getConnection(
                $parser->parse($databaseUrl),
            );
            $connection->executeQuery("SELECT 1")->fetchOne();
            $serverVersion = $connection->getServerVersion();
            $connection->close();

            return $this->createResult(
                $name,
                "OK",
                "Database connection established successfully.",
                [
                    "server_version" => $serverVersion,
                ],
            );
        } catch (Throwable $exception) {
            $this->logger->error("Database health check failed.", [
                "service" => $name,
                "exception" => $exception,
            ]);

            return $this->createResult(
                $name,
                "FAIL",
                $exception->getMessage(),
                [
                    "exception" => $exception::class,
                ],
            );
        }
    }

    /**
     * @return array{name: string, status: string, message: string, details: array<string, mixed>}
     */
    private function checkHttpEndpoint(
        string $name,
        string $url,
        int $expectedStatusPrefix = 2,
        ?int $expectedStatusCode = null,
    ): array {
        try {
            $response = $this->httpClient->request("GET", $url);
            $statusCode = $response->getStatusCode();

            if (
                $expectedStatusCode !== null &&
                $statusCode !== $expectedStatusCode
            ) {
                return $this->createResult(
                    $name,
                    "FAIL",
                    sprintf("Unexpected HTTP status code: %d.", $statusCode),
                    [
                        "url" => $url,
                        "status_code" => $statusCode,
                        "expected_status_code" => $expectedStatusCode,
                    ],
                );
            }

            if (
                $expectedStatusCode === null &&
                (int) floor($statusCode / 100) !== $expectedStatusPrefix
            ) {
                return $this->createResult(
                    $name,
                    "FAIL",
                    sprintf("Unexpected HTTP status code: %d.", $statusCode),
                    [
                        "url" => $url,
                        "status_code" => $statusCode,
                    ],
                );
            }

            return $this->createResult(
                $name,
                "OK",
                "HTTP endpoint responded successfully.",
                [
                    "url" => $url,
                    "status_code" => $statusCode,
                ],
            );
        } catch (Throwable $exception) {
            $this->logger->error("HTTP health check failed.", [
                "service" => $name,
                "url" => $url,
                "exception" => $exception,
            ]);

            return $this->createResult(
                $name,
                "FAIL",
                $exception->getMessage(),
                [
                    "url" => $url,
                    "exception" => $exception::class,
                ],
            );
        }
    }

    /**
     * @return array{name: string, status: string, message: string, details: array<string, mixed>}
     */
    private function checkCache(): array
    {
        $cacheKey = "health_check_ping";

        try {
            $item = $this->appCache->getItem($cacheKey);
            $item->set("pong");
            $this->appCache->save($item);

            $savedItem = $this->appCache->getItem($cacheKey);
            if (!$savedItem->isHit() || $savedItem->get() !== "pong") {
                return $this->createResult(
                    "cache",
                    "FAIL",
                    "Cache item was not saved correctly.",
                    [],
                );
            }

            $this->appCache->deleteItem($cacheKey);

            return $this->createResult(
                "cache",
                "OK",
                "Cache save/get/delete operations completed successfully.",
                [],
            );
        } catch (Throwable $exception) {
            $this->logger->error("Cache health check failed.", [
                "exception" => $exception,
            ]);

            return $this->createResult(
                "cache",
                "FAIL",
                $exception->getMessage(),
                [
                    "exception" => $exception::class,
                ],
            );
        }
    }

    /**
     * @return array{name: string, status: string, message: string, details: array<string, mixed>}
     */
    private function checkMailer(): array
    {
        try {
            $email = new Email()
                ->from($this->mailerFrom)
                ->to($this->healthCheckMailTo)
                ->subject("Health check ping email")
                ->text("Ping");

            $this->mailer->send($email);

            return $this->createResult(
                "mailer",
                "OK",
                "Ping email sent without critical errors.",
                [
                    "to" => $this->healthCheckMailTo,
                ],
            );
        } catch (Throwable $exception) {
            $this->logger->error("Mailer health check failed.", [
                "exception" => $exception,
            ]);

            return $this->createResult(
                "mailer",
                "FAIL",
                $exception->getMessage(),
                [
                    "exception" => $exception::class,
                ],
            );
        }
    }

    /**
     * @return array{name: string, status: string, message: string, details: array<string, mixed>}
     */
    private function checkMessenger(): array
    {
        try {
            $this->messageBus->dispatch(new HealthCheckPingMessage("ping"), [
                new TransportNamesStamp(["sync"]),
            ]);

            return $this->createResult(
                "messenger",
                "OK",
                "Ping message dispatched to sync transport successfully.",
                [],
            );
        } catch (Throwable $exception) {
            $this->logger->error("Messenger health check failed.", [
                "exception" => $exception,
            ]);

            return $this->createResult(
                "messenger",
                "FAIL",
                $exception->getMessage(),
                [
                    "exception" => $exception::class,
                ],
            );
        }
    }

    /**
     * @return array{name: string, status: string, message: string, details: array<string, mixed>}
     */
    private function createResult(
        string $name,
        string $status,
        string $message,
        array $details,
    ): array {
        $this->logger->info("Health check finished.", [
            "service" => $name,
            "status" => $status,
            "message" => $message,
            "details" => $details,
        ]);

        return [
            "name" => $name,
            "status" => $status,
            "message" => $message,
            "details" => $details,
        ];
    }

    private function resolveTestDatabaseUrl(): string
    {
        $testUrl =
            $_SERVER["DATABASE_TEST_URL"] ??
            ($_ENV["DATABASE_TEST_URL"] ?? null);
        if (is_string($testUrl) && $testUrl !== "") {
            return $testUrl;
        }

        $parts = parse_url($this->databaseUrl);
        if ($parts === false || !isset($parts["path"])) {
            return $this->databaseUrl;
        }

        $databaseName = ltrim($parts["path"], "/");
        if ($databaseName === "") {
            return $this->databaseUrl;
        }

        $parts["path"] = "/" . $databaseName . "_test";

        return $this->buildUrlFromParts($parts);
    }

    /**
     * @param array<string, mixed> $parts
     */
    private function buildUrlFromParts(array $parts): string
    {
        $scheme = isset($parts["scheme"]) ? $parts["scheme"] . "://" : "";
        $user = $parts["user"] ?? "";
        $pass = isset($parts["pass"]) ? ":" . $parts["pass"] : "";
        $auth = $user !== "" ? $user . $pass . "@" : "";
        $host = $parts["host"] ?? "";
        $port = isset($parts["port"]) ? ":" . $parts["port"] : "";
        $path = $parts["path"] ?? "";
        $query = isset($parts["query"]) ? "?" . $parts["query"] : "";
        $fragment = isset($parts["fragment"]) ? "#" . $parts["fragment"] : "";

        return $scheme . $auth . $host . $port . $path . $query . $fragment;
    }
}