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
	@echo "Quality:  test test-f filter=X test-suite suite=X analyse audit quality"
	@echo "Data:     migrate seed migrate-seed fresh"
	@echo "Runtime:  queue schedule swagger storage-link clean"
	@echo "Perf:     load-test"
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

# --abandoned=report lists abandoned packages without failing on them:
# doctrine/annotations is abandoned upstream and pulled in transitively, so the
# bare command would always exit non-zero. Real advisories still fail.
.PHONY: audit
audit:
	$(APP) composer audit --abandoned=report

# Everything CI blocks on, in CI order.
.PHONY: quality
quality: analyse audit test

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

# --- Performance ------------------------------------------------------------

# Local only — never point this at the VPS. Raises the API rate limit for the
# duration of the run, otherwise the scenario measures the limiter rather than
# the application, and puts it back afterwards. Do not run a queue worker at the
# same time: every registration queues a real AI screening call.
#
# MSYS_NO_PATHCONV stops Git Bash from mangling the volume path on Windows.
.PHONY: load-test
load-test:
	@echo "API_RATE_LIMIT_PER_MINUTE=1000000" >> .env
	@$(APP) php artisan config:clear >/dev/null
	-MSYS_NO_PATHCONV=1 docker run --rm 		--network $$(basename $$(pwd) | tr -d '.')_sail 		-v "$$(pwd)/load-tests:/scripts" -w /scripts 		-e BASE_URL=http://laravel 		grafana/k6 run /scripts/candidacy-flow.js
	@sed -i '/^API_RATE_LIMIT_PER_MINUTE=1000000$$/d' .env
	@$(APP) php artisan config:clear >/dev/null
	@echo "Rate limit restored. Artifacts in load-tests/results/"

# --- Info -------------------------------------------------------------------

.PHONY: urls
urls:
	@echo "API       http://localhost:$(APP_PORT)"
	@echo "Swagger   http://localhost:$(APP_PORT)/api/documentation"
	@echo "Telescope http://localhost:$(APP_PORT)/telescope"
	@echo "Mailpit   http://localhost:8025"
	@echo "Login     test@example.com / password (role admin)"
