.PHONY: help dev restart-dev frankenphp-build frankenphp-start frankenphp-ps frankenphp-logs frankenphp-stop frankenphp-horizon frankenphp-scheduler
SAIL = ./vendor/bin/sail
DOCKER_COMPOSE = docker compose
CONTAINER_NAME=laravel-toko-online
VOLUME_DATABASE=toko-online_db-vol
FRANKENPHP_PROFILE = frankenphp
FRANKENPHP_SERVICE = laravel.frankenphp
FRANKENPHP_USER = www-data

help: ## Print help.
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)


ps: ## Docker containers.
	@docker compose ps

build: ## Build all containers.
	@${SAIL} build --no-cache

start: ## Start all containers.
	@${SAIL} up -d

dev: start ## Start Sail, Vite, Horizon, and scheduler.
	@npm run dev:services

restart-dev: ## Restart Vite, Horizon, and scheduler without restarting Sail.
	@npm run dev:services

stop: ## Stop all containers.
	@${SAIL} down

destroy: ##Destroy all containers
	@${SAIL} down -v

setup: start start migrate-fresh db-seed storage-link optimize ## Destroy containers, build images, start containers.

fresh: stop destroy build setup ## Destroy containers, build images, start containers.

wait: ## Wait for 2 seconds before executing.
	@echo "Waiting for 2 seconds..."
	@sleep 2

migrate: ## Run migrations file
	@${SAIL} artisan migrate

migrate-fresh: ## Clear Database and run migrations file
	@${SAIL} artisan migrate:fresh

db-seed: ## Seed database
	@${SAIL} artisan db:seed

optimize: ##cache project
	@${SAIL} artisan optimize

cache: ## clear Cache Project
	@${SAIL} artisan cache:clear

storage-link: ## Cache Project
	@${SAIL} artisan storage:link

frankenphp-build: ## Build the FrankenPHP Octane image.
	@${DOCKER_COMPOSE} --profile frankenphp build laravel.frankenphp

frankenphp-start: ## Start the optional FrankenPHP Octane runtime.
	@${DOCKER_COMPOSE} --profile frankenphp up -d --build laravel.frankenphp

frankenphp-ps: ## Show the FrankenPHP runtime status.
	@${DOCKER_COMPOSE} --profile frankenphp ps laravel.frankenphp

frankenphp-logs: ## Follow FrankenPHP runtime logs.
	@${DOCKER_COMPOSE} --profile frankenphp logs -f laravel.frankenphp

frankenphp-stop: ## Stop the optional FrankenPHP Octane runtime.
	@${DOCKER_COMPOSE} --profile frankenphp stop laravel.frankenphp

frankenphp-horizon: ## Run Horizon in the FrankenPHP container.
	@COMPOSE_PROFILES=${FRANKENPHP_PROFILE} APP_SERVICE=${FRANKENPHP_SERVICE} APP_USER=${FRANKENPHP_USER} ${SAIL} artisan horizon

frankenphp-scheduler: ## Run the scheduler in the FrankenPHP container.
	@COMPOSE_PROFILES=${FRANKENPHP_PROFILE} APP_SERVICE=${FRANKENPHP_SERVICE} APP_USER=${FRANKENPHP_USER} ${SAIL} artisan schedule:work
