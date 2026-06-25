## Добавила

| Файл | Назначение |
|------|------------|
| `src/Command/HealthCheckCommand.php` | Консольная команда `app:health-check` для проверки работоспособности БД, API, кеша, почты, Messenger, сервера |
| `src/Command/PromoteUserToAdminCommand.php` | Консольная команда `app:user:promote-admin` для назначения пользователя администратором |
| `src/Service/HealthCheckService.php` | Сервис для проверки БД (dev/test), внешних API, кеша, почты, Messenger, сервера |
| `src/Service/StatisticsService.php` | Сервис для сбора статистики (кол-во комментариев, топ постов, топ профилей и т.д.) |
| `src/Message/AuthorNotificationMessage.php` | Сообщение для отправки уведомления автору поста/комментария |
| `src/Message/StatisticsDigestMessage.php` | Сообщение для рассылки статистики администраторам |
| `src/Message/HealthCheckPingMessage.php` | Сообщение для проверки работоспособности Messenger |
| `src/MessageHandler/AuthorNotificationMessageHandler.php` | Обработчик для отправки email-уведомления автору |
| `src/MessageHandler/StatisticsDigestMessageHandler.php` | Обработчик для сбора статистики и отправки письма администраторам |
| `src/MessageHandler/HealthCheckPingMessageHandler.php` | Обработчик для ping-сообщения (логирует получение) |
| `src/Scheduler/StatisticsDigestSchedule.php` | Расписание для автоматической рассылки статистики каждый час |
| `src/Controller/HealthController.php` | Контроллер для проверки работоспособности сервера по маршруту `/health` |
| `src/Controller/StatisticsController.php` | Контроллер для страницы статистики `/admin/statistics` (доступ только админам) |
| `templates/statistics/index.html.twig` | Шаблон страницы статистики |
| `templates/statistics/_content.html.twig` | Шаблон содержимого статистики (используется в письме и на странице) |
| `templates/email/statistics_digest.html.twig` | Шаблон письма со статистикой для администраторов |
| `docker/php-consumer/Dockerfile` | Dockerfile для сборки контейнеров воркеров |

---

## Изменила

| Файл | Что изменено |
|------|--------------|
| `src/Controller/CommentController.php` | Добавлена отправка уведомления автору поста при создании комментария |
| `src/Controller/CommentReactionController.php` | Добавлена отправка уведомления автору комментария при лайке/дизлайке |
| `src/Repository/UserRepository.php` | Добавлен метод `findAdminUsers()` для поиска пользователей с ролью ROLE_ADMIN |
| `config/services.yaml` | Добавлены параметры для HealthCheck (URLы API, email) и сервисы для Mailer |
| `config/packages/messenger.yaml` | Добавлены транспорты `email_notifications` (для уведомлений) и `sync` (для HealthCheck) |
| `config/packages/scheduler.yaml` | Новый файл, включена поддержка Scheduler |
| `compose.yaml` | Добавлены 2 консьюмера: `messenger_email_notifications_consumer` и `messenger_scheduler_statistics_digest_consumer` |
| `compose.override.yaml` | Настроены порты для PostgreSQL (5433) и Mailpit (1025, 8025) |

---

## Реализованный функционал

1. **Уведомления автору** — при комментариях и реакциях автор получает письмо (Messenger + Mailer)
2. **Статистика администраторам** — каждый час админам приходит письмо со статистикой (Scheduler)
3. **Health Check** — консольная команда `app:health-check` проверяет БД, API, кеш, почту, Messenger, сервер
4. **Docker-воркеры** — консьюмеры запущены в контейнерах, не требуют ручного запуска
