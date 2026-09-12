# API Case

A JSON:API-flavored e-commerce backend built with Laravel. It exposes a public storefront (product catalog, categories, orders, Stripe checkout) and an admin back office (product/category CRUD, order management), each authenticated through its own Laravel Sanctum guard (`user` and `admin`). All responses use the `application/vnd.api+json` content type.

**Highlights**
- Dual auth guards: public self-registration with required email verification (`user`), and pre-provisioned, deactivatable accounts (`admin`).
- Product catalog with translatable fields, categories, and stock-aware order creation with row-level locking to prevent overselling.
- Stripe Checkout integration with webhook handling (payment success, expiry, refunds) and a scheduled command to expire stale pending orders.
- Full OpenAPI documentation served via Swagger UI.

## Tech stack

- PHP 8.5, Laravel 13, Laravel Sanctum
- PostgreSQL 17, Redis 7 (cache/queue)
- Docker Compose (nginx, app, queue worker, database, redis, swagger)

## Prerequisites

- Docker and Docker Compose
- A Stripe account (test mode is fine) if you want to exercise checkout/webhook flows

## Installation

1. Clone the repository and move into it:
   ```bash
   git clone <repository-url> api-case
   cd api-case
   ```

2. Copy the environment file and adjust it if needed (database, Redis, and Stripe credentials are already wired for the Docker services):
   ```bash
   cp .env.example .env
   ```

3. Build and start the containers:
   ```bash
   make build
   make up
   ```
   This starts `nginx` (port `8080`), `app` (PHP-FPM), `queue`, `database` (Postgres, port `7799`), `redis` (port `7780`), and `swagger` (port `7765`).

4. Install PHP dependencies and generate the application key:
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   ```

5. Run database migrations:
   ```bash
   docker compose exec app php artisan migrate
   ```
   Or use `make fresh` (migrate:fresh) / `make reset` (migrate:fresh --seed) to reset the database.

6. The API is now available at `http://localhost:8080/api`. Interactive API documentation (Swagger UI) is available at `http://localhost:7765`.

## Running tests

```bash
docker compose exec app php artisan test
```

## Useful commands

| Command | Description |
|---|---|
| `make up` | Start all containers in the background |
| `make down` | Stop all containers |
| `make build` | Build/rebuild the images |
| `make shell` | Open a shell in the `app` container |
| `make fresh` | Re-run migrations from scratch |
| `make reset` | Re-run migrations from scratch and seed the database |
| `make queue-logs` | Tail the queue worker logs |
