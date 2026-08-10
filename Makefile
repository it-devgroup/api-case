up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

reset:
	docker compose exec app php artisan migrate:fresh --seed

fresh:
	docker compose exec app php artisan migrate:fresh

shell:
	docker compose exec app sh

queue-logs:
	docker compose logs -f queue
