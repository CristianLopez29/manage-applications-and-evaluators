# Load tests

A k6 scenario that reproduces the API's core write-then-read flow under
concurrency, so the performance claims in the main README have output standing
behind them.

## What it exercises

| Step | Endpoint | Why it is in the scenario |
|------|----------|---------------------------|
| Register candidate | `POST /api/v1/candidates` | The main write path, including validation and the domain event chain |
| Assign candidate | `POST /api/v1/evaluators/{id}/assign-candidate` | The path guarded by `SELECT ... FOR UPDATE` against double assignment |
| Read consolidated | `GET /api/v1/evaluators/consolidated` | The `GROUP_CONCAT` aggregate query, read while writes are in flight |

Two scenarios run in sequence:

- **`candidacy_flow`** — 10 VUs, 100 shared iterations. Writes and reads
  interleaved. This is what the "no 5xx" claim covers.
- **`consolidated_baseline`** — 5 VUs for 20s, reads only, once the writes are
  done. It separates the cost of the aggregate query itself from contention
  with the write path, which holds row locks and invalidates the consolidated
  cache on every assignment.

## Running it

Run against a **local** stack only. Never point it at the production VPS.

```bash
# 1. Stack up, database migrated and seeded
docker compose up -d
docker compose exec -T laravel php artisan migrate --seed

# 2. Raise the API rate limit for the duration of the run.
#    The default is 60 req/min, which would make the test measure the
#    limiter rather than the application.
echo "API_RATE_LIMIT_PER_MINUTE=1000000" >> .env
docker compose exec -T laravel php artisan config:clear

# 3. Run k6 on the compose network
docker run --rm --network manage-applications-and-evaluators_sail \
  -v "$(pwd)/load-tests:/scripts" -w /scripts \
  -e BASE_URL=http://laravel \
  grafana/k6 run /scripts/candidacy-flow.js

# 4. Put the rate limit back
#    (remove the API_RATE_LIMIT_PER_MINUTE line from .env)
docker compose exec -T laravel php artisan config:clear
```

On Windows with Git Bash, prefix the `docker run` with `MSYS_NO_PATHCONV=1` so
the volume path is not mangled.

**Do not run a queue worker during the test.** Registering a candidate queues an
AI screening job; with a worker running, 100 registrations mean 100 real calls
to the AI provider.

## Options

| Variable | Default | Meaning |
|----------|---------|---------|
| `BASE_URL` | `http://localhost:8080` | API base URL |
| `CANDIDATES` | `100` | Iterations of the write flow |
| `API_EMAIL` / `API_PASSWORD` | seeded admin | Credentials used for the run |

## Output

`k6 run` writes three artifacts into `results/`:

| File | Contents |
|------|----------|
| `summary.txt` | The block quoted in the main README |
| `summary.json` | Full k6 metrics, for diffing between runs |
| `report.html` | Self-contained HTML report |

## Thresholds

```
server_errors_5xx              count == 0     the headline claim
http_req_failed                rate  < 1%
candidate_register_duration    p95   < 1500ms
consolidated_read_duration     p95   < 6000ms  under concurrent writes
consolidated_baseline_duration p95   < 400ms   uncontended
```

`consolidated_read_duration` is deliberately loose: it documents the observed
ceiling on a development machine under write contention rather than asserting a
performance target. The uncontended threshold is the one that says what the
query actually costs.

A `409` on assignment is a **correct** answer (evaluator at capacity, or the
candidate is already assigned), so the scenario registers it as an expected
status rather than a failure.
