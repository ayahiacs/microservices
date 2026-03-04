
## HubService-specific Notes

This repository hosts the HubService, a Laravel 12 application responsible for aggregating data from other microservices and broadcasting real-time updates to clients.

### Event Consumption

The service consumes events from RabbitMQ. A lightweight consumer (`app/Services/RabbitConsumer.php`) listens on the queue defined by `RABBITMQ_QUEUE` and dispatches messages to processors by type. Processors live in `app/Processors/` and handle individual event types such as `EmployeeUpdated` and `ChecklistUpdated`.

Run the consumer manually via Artisan:

```bash
php artisan rabbit:consume [queueName]
```

### Processors & Caching

Processors implement a clean separation of concerns:

1. Extract data from the incoming payload.
2. Update application cache (Redis is used by default).
3. Invalidate related cache keys.
4. Broadcast updates to WebSocket clients.
5. Log activity for monitoring.

Cache-aside pattern is used; helpers are available in `BaseProcessor` and `app/Helpers/CacheHelper.php`.

### Broadcasting / WebSockets

We're using **Soketi** with Laravel's built-in Pusher driver. Configuration is in `config/broadcasting.php` and channels are defined in `routes/channels.php`. A sample test page is available at `public/checklists.html`.

#### Channel Strategy

Channels are organized by entity type and scope:

| Channel pattern           | Description                            |
|---------------------------|----------------------------------------|
| `employee.{id}`           | Private channel for individual users   |
| `checklist.{country}`     | Public lobby per country (or "global")|

Private channels require authorization logic in `routes/channels.php`.

### Caching

Redis was chosen as the cache store for speed, simplicity, and existing presence in Laravel. The `.env.example` reflects this choice and the `CACHE_STORE` default. With Redis we get in-memory TTLs and built-in atomic operations.

### Testing

Pest is used for automated testing. Feature tests for processors ensure caching and broadcasting behaviors.
```
php artisan test --filter=EmployeeUpdatedProcessorTest
```

