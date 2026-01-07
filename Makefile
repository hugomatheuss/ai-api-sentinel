# Makefile com comandos úteis de desenvolvimento

COMPOSE=docker compose

.PHONY: up down logs shell migrate artisan test build

up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec app bash

migrate:
	$(COMPOSE) exec app php artisan migrate --force

artisan:
	$(COMPOSE) exec app php artisan $(cmd)

test:
	$(COMPOSE) exec app vendor/bin/pest --parallel

build:
	$(COMPOSE) build --pull --no-cache

