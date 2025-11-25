# rabbitmq-microservices (integration)

This folder contains a small producer / consumer microservice example and a PHP API that can publish events to the producer.

Quick overview
- `producer/` - Node.js producer that sends events to RabbitMQ, with HTTP fallback.
- `consumer/` - Node.js consumer that applies incoming events to a MySQL `accounts` table.
- `api/` - PHP API (existing) — updated to publish events to the producer.
- `php/` - Dockerfile to run the PHP API with `mysqli` enabled.
- `tests/` - integration test scripts: `run_integration.js` (microservice-only) and `php_integration.js` (PHP -> producer -> consumer).

Running everything locally (recommended)
1. Build and start the full stack (RabbitMQ, MySQL, producer, consumer, PHP):

```powershell
cd 'C:\Users\kubao\Desktop\Bank-main\rabbitmq-microservices'
docker-compose up -d --build
```

2. Optionally run the provided test runner which builds the stack, waits for MySQL, creates minimal tables, and runs both integration tests:

```powershell
.\
un-all-tests.ps1
```

Files produced by tests
- `tests/report.json` — microservice-only integration report.
- `tests/php_integration_report.json` — PHP end-to-end integration report.

Notes
- The PHP service in compose mounts the `api/` folder; you can modify PHP files and restart the php service to pick up changes.
- The PHP service is on the same Docker network as the producer and MySQL so environment defaults in `api/config.php` (`mysql`, `bank`, `bankpass`, `bank_db`) should work out of the box.

If you want me to also add CI integration or convert the retry queue to a DB-backed job queue, say so and I will implement it next.

**Dead-Letter (DLQ) and Monitoring**

- The system now records permanently failed events into a `dead_letter` table when the worker exhausts retry attempts (default 5). This helps with post-mortem and manual recovery.
- Inspect DLQ via SQL:

```powershell
docker exec -i bank_mysql mysql -u bank -pbankpass bank_db -e "SELECT id,event_id,attempts,last_error,failed_at FROM dead_letter ORDER BY failed_at DESC LIMIT 50;"
```

- There is a lightweight admin endpoint to view and requeue DLQ items: `api/dead_letter.php`.
	- Example (dev mode): `http://localhost:8000/dead_letter.php?admin=1&limit=50`
	- To requeue: POST JSON `{ "id": 123 }` to the same endpoint (use `admin_key` if you set `ADMIN_KEY` in env).

- Worker configuration (env vars):
	- `WORKER_MAX_ATTEMPTS` (default `5`) — after this the event is moved to `dead_letter`.
	- `WORKER_LEASE_SECONDS` (default `30`) — claim lease duration.

