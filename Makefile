# Candidacy Management API — task runner
#
# Wraps `docker compose` so no target depends on ./vendor/bin/sail, which needs a
# POSIX shell and misbehaves on Windows. Every non-interactive target passes -T:
# without it `docker compose exec` aborts with "the input device is not a TTY"
# when make runs from PowerShell.

# This machine runs a second Dockerized Laravel stack that already owns host
# ports 80 and 5173, so the defaults here are the free ones. Override per call:
# `make up APP_PORT=80`.
APP_PORT ?= 8080
VITE_PORT ?= 5174
export APP_PORT
export VITE_PORT

DC := docker compose
APP := $(DC) exec -T laravel

.DEFAULT_GOAL := help

.PHONY: help
help:
	@echo "Stack:    up down restart status logs bash"
	@echo "Quality:  test test-f filter=X test-suite suite=X analyse quality"
	@echo "Data:     migrate seed migrate-seed fresh"
	@echo "Runtime:  queue schedule swagger storage-link clean"
	@echo "Info:     urls"

# --- Stack ------------------------------------------------------------------

.PHONY: up
up:
	$(DC) up -d
	@echo "API on http://localhost:$(APP_PORT)"

.PHONY: down
down:
	$(DC) down

.PHONY: restart
restart: down up

.PHONY: status
status:
	$(DC) ps

.PHONY: logs
logs:
	$(DC) logs -f laravel

.PHONY: bash
bash:
	$(DC) exec laravel bash

# --- Quality gates (the two CI jobs) ----------------------------------------

# Tests run against the MySQL container, never SQLite: the consolidated
# evaluators query is raw MySQL (GROUP_CONCAT ... SEPARATOR) and the assignment
# lock is a real SELECT ... FOR UPDATE. Both are silently wrong on SQLite.
.PHONY: test
test:
	$(APP) php artisan test

# make test-f filter=AssignCandidateTest
.PHONY: test-f
test-f:
	$(APP) php artisan test --filter=$(filter)

# make test-suite suite=Evaluators
.PHONY: test-suite
test-suite:
	$(APP) php artisan test --testsuite=$(suite)

.PHONY: analyse
analyse:
	$(APP) ./vendor/bin/phpstan analyse --memory-limit=2G

# Everything CI blocks on, in CI order.
.PHONY: quality
quality: analyse test

# --- Data -------------------------------------------------------------------

.PHONY: migrate
migrate:
	$(APP) php artisan migrate

.PHONY: seed
seed:
	$(APP) php artisan db:seed

.PHONY: migrate-seed
migrate-seed:
	$(APP) php artisan migrate --seed

# Destroys the local database.
.PHONY: fresh
fresh:
	$(APP) php artisan migrate:fresh --seed

# --- Runtime ----------------------------------------------------------------

# Required for POST /candidates/{id}/analyze and POST /evaluators/report:
# both answer 202 and do the work in a queued job.
.PHONY: queue
queue:
	$(DC) exec laravel php artisan queue:work

.PHONY: schedule
schedule:
	$(DC) exec laravel php artisan schedule:work

.PHONY: swagger
swagger:
	$(APP) php artisan l5-swagger:generate

.PHONY: storage-link
storage-link:
	$(APP) php artisan storage:link

.PHONY: clean
clean:
	$(APP) php artisan optimize:clear

# --- Info -------------------------------------------------------------------

.PHONY: urls
urls:
	@echo "API       http://localhost:$(APP_PORT)"
	@echo "Swagger   http://localhost:$(APP_PORT)/api/documentation"
	@echo "Telescope http://localhost:$(APP_PORT)/telescope"
	@echo "Mailpit   http://localhost:8025"
	@echo "Login     test@example.com / password (role admin)"
