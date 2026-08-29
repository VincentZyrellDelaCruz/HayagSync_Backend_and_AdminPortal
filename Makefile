# Shortcut command using "make" (e.g. "make up" or "make art migrate")
up:
	docker compose up -d

down:
	docker compose down

art:
	docker compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

composer:
	docker compose exec app composer $(filter-out $@,$(MAKECMDGOALS))

npm:
	docker compose exec node npm $(filter-out $@,$(MAKECMDGOALS))

shell:
	docker compose exec app bash

logs:
	docker compose logs -f app

fresh:
	docker compose exec app php artisan migrate:fresh --seed

%:
	@: