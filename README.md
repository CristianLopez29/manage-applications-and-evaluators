# Candidacy Management API

[![CI](https://github.com/CristianLopez29/manage-applications-and-evaluators/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/CristianLopez29/manage-applications-and-evaluators/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](phpstan.neon)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

The **CI** badge is live: it reflects the last run of
[`.github/workflows/ci.yml`](.github/workflows/ci.yml) on `main`, which runs
three blocking jobs — PHPStan at level 9, `composer audit`, and the PHPUnit
suite against MySQL and Redis. The PHPStan badge states the level configured in
[`phpstan.neon`](phpstan.neon); the CI badge is what proves it passes.

> Modular and scalable system for managing candidacies and evaluators, implemented with **Hexagonal Architecture**, **advanced design patterns**, and **software best practices**.

---

## 🎯 Architectural Decisions

### Hexagonal Architecture (Clean Architecture)

**Hexagonal Architecture** (also known as Ports and Adapters) was chosen for the following reasons:

#### ✅ **Total Framework Decoupling**
- Business logic (`Domain`) has no Laravel dependencies
- Use cases (`Application`) are framework-agnostic
- Infrastructure (`Infrastructure`) is completely interchangeable
- **Benefit:** I can replace Laravel with Symfony without touching business logic

#### ✅ **Superior Testability**
- Unit, integration and acceptance layers (live count on the CI badge above)
- Unit tests do not require the framework
- Fakes and mocks are trivial to implement
- **Benefit:** Fast and reliable tests

#### ✅ **Long-Term Maintainability**
- Clear separation of responsibilities
- Each layer has a specific purpose
- Changes in UI/DB do not affect business logic
- **Benefit:** Code that ages well

#### ✅ **Team Scalability**
- Teams can work on independent layers
- Clear interfaces between layers
- Easier onboarding with predictable structure
- **Benefit:** Frictionless team growth

### Decision: Domain-Driven Design (DDD)

**DDD** principles were applied to model the domain:

- **Entities:** `Candidate`, `Evaluator`, `CandidateAssignment`
- **Value Objects:** `Email`, `CV`, `YearsOfExperience`, `Specialty`, `AssignmentStatus`
- **Domain Events:** `CandidateRegistered` for audit logging
- **Repositories:** Interfaces in Domain, implementations in Infrastructure
- **DTOs:** Data transfer between layers without exposing entities

**Why?** The HR domain is complex and business rules change frequently. DDD allows us to model the business expressively and maintainably.

---

## 📁 Project Structure

```
src/
├── Candidates/              # Candidacy Module
│   ├── Domain/              # Pure business logic (no Laravel)
│   │   ├── Candidate.php    # Domain Entity
│   │   ├── ValueObjects/    # Email, CV, YearsOfExperience
│   │   ├── Validators/      # Chain of Responsibility
│   │   ├── Repositories/    # Interfaces (contracts)
│   │   ├── Events/          # Domain Events
│   │   └── Exceptions/      # Domain Exceptions
│   ├── Application/         # Use Cases
│   │   ├── UseCases/        # RegisterCandidacy, GetCandidateSummary (no UseCase suffix)
│   │   ├── DTOs/            # Data Transfer Objects
│   │   └── Transformers/
│   └── Infrastructure/      # Technical implementations
│       ├── Persistence/     # Eloquent Models & Repositories
│       ├── Controllers/     # Single-action __invoke controllers
│       ├── Ai/              # OpenAI / Gemini screening adapters
│       ├── Jobs/            # AnalyzeCandidateCvJob
│       └── Listeners/       # Event Listeners
│
├── Evaluators/              # Evaluators Module
│   ├── Domain/              # Pure business logic
│   │   ├── Evaluator.php
│   │   ├── CandidateAssignment.php
│   │   ├── ValueObjects/    # Specialty, AssignmentStatus
│   │   ├── Repositories/    # Interfaces
│   │   └── Criteria/        # Query criteria objects
│   ├── Application/         # Use Cases
│   │   ├── UseCases/        # AssignCandidateToEvaluator, GetConsolidatedEvaluators
│   │   ├── DTOs/
│   │   ├── Ports/           # EvaluatorCachePort
│   │   └── Transformers/
│   └── Infrastructure/
│       ├── Persistence/
│       ├── Controllers/
│       ├── Jobs/            # GenerateEvaluatorsReportJob, ProcessOverdueAssignmentsJob
│       ├── Cache/           # LaravelEvaluatorCache
│       ├── Export/          # Excel exporters
│       └── Notifications/   # Email notifications
│
└── Shared/                  # Code shared between modules
    ├── Domain/
    └── Infrastructure/
```

Each module also has a `Bindings.php` at its root (a `ServiceProvider`): `register()` maps ports to
adapters, `boot()` declares the module's `/api/v1` routes. Both are registered in `bootstrap/providers.php`.

### Conventions

- **Domain:** No external dependencies. Pure PHP.
- **Application:** Orchestrates the domain. Must not contain business logic.
- **Infrastructure:** Everything related to Laravel, databases, external APIs.

---

## ⚡ Quick Start

```bash
# 1. Clone and install dependencies
git clone https://github.com/CristianLopez29/manage-applications-and-evaluators.git
cd manage-applications-and-evaluators

# 2. Install with Docker (first time)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs

# 3. Setup environment
cp .env.example .env

# 4. Start services (MySQL, Redis, Mailpit)
docker compose up -d

# 5. Initialize database and storage
docker compose exec -T laravel php artisan key:generate
docker compose exec -T laravel php artisan storage:link
docker compose exec -T laravel php artisan migrate:fresh --seed

# 6. Generate Swagger docs
docker compose exec -T laravel php artisan l5-swagger:generate

# 7. Run tests
docker compose exec -T laravel php artisan test
```

> **`docker compose` rather than `./vendor/bin/sail`.** Sail is a bash script that needs a POSIX shell
> and misbehaves on Windows hosts. `exec -T` is deliberate: without it, `exec` aborts with
> *"the input device is not a TTY"* when driven from PowerShell.

> **Ports.** `compose.yaml` maps the app to `APP_PORT` (default `80`). Set `APP_PORT=8080` in `.env`
> if something else already owns port 80 on your machine — the examples below assume `8080`.

**🌐 Services Available:**
- **API**: http://localhost:8080
- **Swagger**: http://localhost:8080/api/documentation
- **Mailpit** (emails): http://localhost:8025

**🐳 Database Connections (from host machine):**
- **MySQL**: `127.0.0.1:3306` (only if you need to connect with external tools like TablePlus/DBeaver)
  - User: `sail`
  - Password: `password`
  - Database: `laravel`
  - **Note**: From the Laravel application use `DB_HOST=mysql` (inside Docker)
- **Redis**: `127.0.0.1:6379` (inside Docker use `REDIS_HOST=redis`)

**⚡ Start Queue Worker** (required for Excel reports *and* AI screening):
```bash
# Ensure QUEUE_CONNECTION=redis in .env
docker compose exec -T laravel php artisan queue:work
```

---

## 📋 Table of Contents

- [🎯 Architectural Decisions](#-architectural-decisions)
- [📁 Project Structure](#-project-structure)
- [⚡ Quick Start](#-quick-start)
- [📐 Layer Diagram](#-layer-diagram)
- [🔧 Technical Justification](#-technical-justification)
- [🎨 Implemented Patterns](#-implemented-patterns)
- [🚀 Scalability](#-scalability)
- [💻 How to Run](#-how-to-run)
- [🔍 Trying the API](#-trying-the-api)
- [📈 Performance evidence](#-performance-evidence)
- [📡 API Endpoints](#-api-endpoints)
- [🧪 Testing](#-testing)
- [📦 Technologies](#-technologies)
- [🚢 Deployment](#-deployment)

---

## 📐 Layer Diagram

```mermaid
graph TD
    subgraph "Presentation Layer"
        A[Controllers HTTP]
        B[API Routes]
        C[Request Validation]
    end
    
    subgraph "Application Layer"
        D[Use Cases]
        E[DTOs]
        F[Application Services]
    end
    
    subgraph "Domain Layer - CORE"
        G[Entities]
        H[Value Objects]
        I[Repository Interfaces]
        J[Domain Events]
        K[Validators Chain]
    end
    
    subgraph "Infrastructure Layer"
        L[Eloquent Repositories]
        M[Event Listeners]
        N[Jobs/Queues]
        O[External Services]
        P[Database Models]
    end
    
    A --> D
    B --> A
    C --> A
    D --> I
    D --> G
    E --> G
    F --> D
    L --> I
    L --> P
    M --> J
    N --> F
    
    style G fill:#e1f5e1
    style H fill:#e1f5e1
    style I fill:#e1f5e1
    style J fill:#e1f5e1
    style K fill:#e1f5e1
```

### Data Flow

```
HTTP Request → Controller → Use Case → Domain Logic → Repository Interface
                                                              ↓
                                                    Repository Implementation → Database
```

**Golden Rule:** Dependencies always point inwards. The domain never depends on infrastructure.

---

## 🔧 Technical Justification

### Why Chain of Responsibility for Validations?

```php
$validator = new RequiredCVValidator();
$validator
    ->setNext(new ValidEmailValidator())
    ->setNext(new MinimumExperienceValidator());

$validator->validate($candidate);
```

**Reasons:**

1. **Extensibility:** Add new validation = create new class. Do not modify existing code (Open/Closed Principle)
2. **Testability:** Each validator is tested in isolation
3. **Reusability:** Validators can be composed in different ways
4. **Maintainability:** Clear and localized validation logic

### Why Repository Pattern?

```php
// In Domain - interface
interface CandidateRepository {
    public function save(Candidate $candidate): void;
    public function findById(int $id): ?Candidate;
}

// In Infrastructure - implementation with Eloquent
class EloquentCandidateRepository implements CandidateRepository {
    // Implementation with Eloquent/MySQL
}
```

**Benefits:**

- I can switch from Eloquent to Doctrine without touching use cases
- Easy to mock in tests
- Complex SQL encapsulated in the repository

### Why Value Objects?

```php
readonly class Email {
    public function __construct(private string $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException();
        }
    }
}
```

**Advantages:**

- **Type Safety:** The compiler guarantees it is always a valid email
- **Immutability:** `readonly` prevents accidental mutations
- **Expressiveness:** `$candidate->email()->value()` is clearer than `$candidate->email`
- **Centralized Validation:** Validation is in one place

---

## 🎨 Implemented Patterns

### 1. Chain of Responsibility
- **Location:** `src/Candidates/Domain/Validators/`
- **Usage:** Extensible candidacy validation
- **Test:** `tests/Candidates/Unit/`

### 2. Repository Pattern
- **Location:** Interfaces in `Domain/Repositories/`, implementations in `Infrastructure/Persistence/`
- **Usage:** Persistence abstraction
- **Test:** `tests/{Candidates,Evaluators}/Integration/` against the real database

### 3. Data Transfer Object (DTO)
- **Location:** `Application/DTOs/`
- **Example:** `EvaluatorWithCandidatesDTO`, `CandidateSummaryDTO`
- **Usage:** Transfer data between layers without exposing entities

### 4. Value Object
- **Location:** `Domain/ValueObjects/`
- **Examples:** `Email`, `CV`, `YearsOfExperience`, `Specialty`, `AssignmentStatus`
- **Usage:** Encapsulate validation and type safety

### 5. Domain Events
- **Location:** `src/Candidates/Domain/Events/`
- **Event:** `CandidateRegistered`
- **Listener:** `LogCandidateAction`
- **Usage:** Decoupled audit logging

### 6. Strategy Pattern (implicit)
- In validators: each validator is a validation strategy

---

## 🚀 Scalability

### ✅ Implemented

#### 1. Queues

**Status:** ✅ **Implemented and working**

```php
// src/Evaluators/Infrastructure/Jobs/GenerateEvaluatorsReportJob.php
class GenerateEvaluatorsReportJob implements ShouldQueue
{
    public function handle(GetConsolidatedEvaluatorsUseCase $useCase): void
    {
        // Generates Excel and notifies by email when finished
    }
}
```

**Benefits:**
- ✅ API responds immediately (202 Accepted)
- ✅ Report generated in background
- ✅ Email notification when finished
- ✅ Redis-backed queue connection

**How to run:**
```bash
docker compose exec -T laravel php artisan queue:work
```

#### 2. Idempotency

**Status:** ✅ **Implemented with `ShouldBeUnique`**

```php
class GenerateEvaluatorsReportJob implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600; // 1 hour
    
    public function uniqueId(): string
    {
        return "generate-evaluators-report:{$this->userEmail}";
    }
}
```

**Benefits:**
- ✅ Prevents report duplication
- ✅ Only one job per email in queue/processing
- ✅ Configurable 1 hour TTL

#### 3. Overdue Reminders & Escalation

**Status:** ✅ Implemented

- Automatic reminders sent when an assignment passes its deadline
- Escalation to admins when overdue exceeds a threshold (configurable via `OVERDUE_ESCALATION_DAYS`, default: 3)
- Separate email notifications for evaluator and candidate

```php
// Job
// src/Evaluators/Infrastructure/Jobs/ProcessOverdueAssignmentsJob.php
// Notifications
// - Src\Evaluators\Infrastructure\Notifications\OverdueAssignmentNotification
// - Src\Evaluators\Infrastructure\Notifications\OverdueAssignmentEscalationNotification
```

#### 4. Rate Limiting

**Status:** ✅ Implemented

Applied at the **application** layer, so the limits understand who the caller
is. A `limit_req` zone in Nginx (see [DEPLOY.md](DEPLOY.md)) sits in front of it
as a flood guard only.

- Login (`POST /api/login`): `throttle:login`
  - 5 requests per minute per (email+IP)
  - 30 requests per minute per IP
  - Defined in AppServiceProvider → `RateLimiter::for('login', ...)`
- Authenticated business API: `throttle:api`
  - `API_RATE_LIMIT_PER_MINUTE` requests per minute (default 60), keyed by
    **token owner** when authenticated and by IP when anonymous
  - Keyed by user rather than IP on purpose: behind the reverse proxy every
    request shares one address, so an IP-keyed bucket would let a single client
    spend everybody's quota
- When limits are exceeded:
  - HTTP 429 (Too Many Requests) with `Retry-After` header
  - Enforced by Laravel's throttle middleware

#### 5. Health Checks

**Status:** ✅ Implemented

- Liveness: `/up` provided by the framework health route
  - Defined during bootstrap
- Liveness: `/api/health` — returns `status` and `time`
- Readiness: `/api/readiness`
  - Checks the database and the cache, returning `status`, `checks` and `time`
  - **`200` when both are reachable, `503` when either is down**, so an external
    monitor (UptimeRobot and friends) can actually alert on it
- Outside `local` and `testing`, both probes require an `X-Health-Check-Token`
  header matching `HEALTHCHECK_TOKEN`, compared with `hash_equals`. They **fail
  closed**: with no token configured the probes stay locked rather than
  publishing the dependency report.
### ✅ Additional Implemented Infrastructure
#### 6. Monitoring (Sentry)

**Status:** ✅ Implemented

- Package: `sentry/sentry-laravel`
- Configuration: set `SENTRY_LARAVEL_DSN` to enable reporting; leave it empty to
  disable it entirely
- Wiring: `Integration::handles($exceptions)` in
  [`bootstrap/app.php`](bootstrap/app.php). The package removes its own error
  listeners by design and reports nothing until the application opts in there —
  covered by `tests/Shared/Integration/SentryReportingTest.php`
- Domain exceptions are excluded from reporting: they are mapped to 4xx business
  responses, and alerting on them would bury genuine 5xx incidents
- Optional: tracing via `SENTRY_TRACES_SAMPLE_RATE`

#### 7. Structured Logging (correlation id)

**Status:** ✅ **Implemented**

- An `AddRequestContext` middleware binds a `request_id` (correlation id) and the
  authenticated `user_id` to the logging context of every API request. It runs
  **before** `auth:sanctum`, so rejected requests (401, 429) are traced too.
- The id is returned in the `X-Request-Id` response header — and honoured if the
  caller already sent one — enabling end-to-end tracing across clients, proxies
  and logs.
- **Application and access logs are separate:**

| Channel | Writes to | Contents |
|---------|-----------|----------|
| `json_daily` | `storage/logs/app.json.log-<date>` | Application log, one JSON object per line, rotated (`LOG_DAILY_DAYS`, default 14) |
| `access` | `storage/logs/access.json.log-<date>` | One line per request: method, path, status, duration, `request_id`, IP, user (`LOG_ACCESS_DAYS`, default 7) |
| `production` | both of the above + `stderr` | What `LOG_CHANNEL` should be set to on the server |

- The access line carries the same `request_id` the response header returns, so a
  user's report is traceable from header to application log. The reverse proxy's
  own access log cannot do that: it never sees the id.

#### 8. API Versioning

**Status:** ✅ **Implemented**

- Business resources are served under the `/api/v1` prefix (`/api/v1/candidates`, `/api/v1/evaluators`, …).
- Cross-cutting infrastructure endpoints (`/api/login`, `/api/health`, `/api/readiness`) are intentionally kept unversioned, so the versioned contract covers only the business API and can evolve to `/api/v2` without disrupting auth or health probes.

#### 3. Cache

**Status:** ✅ **Implemented** (active, event-driven invalidation)

The consolidated listing is cached behind an Application port (`EvaluatorCachePort`)
so the use case stays framework-free. The adapter (`LaravelEvaluatorCache`) uses
tag-based caching for Redis and falls back to a tagless flush for other drivers.

```php
// src/Evaluators/Application/UseCases/GetConsolidatedEvaluators.php
$paginator = $this->cache->remember($key, $ttl, fn () => $this->repository->findAllWithCandidates($criteria));

// src/Evaluators/Infrastructure/Cache/LaravelEvaluatorCache.php
Cache::tags(['evaluators'])->remember($key, $ttl, $closure);
Cache::tags(['evaluators'])->flush(); // on CandidateAssigned / AssignmentStatusChanged
```

Invalidation is event-driven via the `InvalidateEvaluatorCache` listener — no
coupling in the use cases.

#### 4. Concurrency (Pessimistic Locking)

**Status:** ✅ **Implemented**

Assignment and reassignment lock the candidate's current assignment row
(`SELECT ... FOR UPDATE`) inside a transaction to prevent race conditions.

```php
// src/Evaluators/Infrastructure/Persistence/EloquentAssignmentRepository.php
public function findByCandidateIdForUpdate(int $candidateId): ?CandidateAssignment
{
    // ...->lockForUpdate()->first();
}

// Used by AssignCandidateToEvaluator and ReassignCandidate use cases
```

### SQL Optimized for High Performance

**Relationship Diagram:**

```mermaid
erDiagram
    EVALUATORS ||--o{ CANDIDATE_ASSIGNMENTS : "has"
    CANDIDATES ||--o{ CANDIDATE_ASSIGNMENTS : "assigned_to"
    
    EVALUATORS {
        int id PK
        string name
        string email UK
        string specialty
        timestamp created_at
    }
    
    CANDIDATES {
        int id PK
        string name
        string email UK
        int years_of_experience
        text cv_content
        timestamp created_at
    }
    
    CANDIDATE_ASSIGNMENTS {
        int id PK
        int evaluator_id FK
        int candidate_id FK
        string status
        timestamp assigned_at
    }
```

**Consolidated Query with GROUP_CONCAT:**

```sql
SELECT 
    evaluators.*,
    COUNT(DISTINCT candidate_assignments.id) as total_candidates,
    AVG(candidates.years_of_experience) as avg_experience,
    GROUP_CONCAT(DISTINCT candidates.email ORDER BY candidates.email SEPARATOR ", ") as candidate_emails
FROM evaluators
LEFT JOIN candidate_assignments ON evaluators.id = candidate_assignments.evaluator_id
LEFT JOIN candidates ON candidate_assignments.candidate_id = candidates.id
GROUP BY evaluators.id
ORDER BY avg_experience DESC
```

**Benefits:**
- ✅ Single query (avoids N+1)
- ✅ Aggregations in SQL (not in PHP)
- ✅ Scalable to millions of records with indexes
- ✅ Efficient pagination

---

## 💻 How to Run

### Prerequisites

- Docker Desktop installed
- Git

### Installation with Docker (Laravel Sail)

```bash
# 1. Clone repository
git clone https://github.com/CristianLopez29/manage-applications-and-evaluators.git
cd manage-applications-and-evaluators

# 2. Install dependencies (first time)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd -W):/var/www/html" \
    -w //var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs

# 3. Copy environment file
cp .env.example .env

# 4. Start services (MySQL, Redis, Mailpit)
docker compose up -d

# 5. Generate application key
docker compose exec -T laravel php artisan key:generate

# 6. Run migrations and seeders
docker compose exec -T laravel php artisan migrate:fresh --seed

# 7. Generate Swagger documentation
docker compose exec -T laravel php artisan l5-swagger:generate
```

### Services Available

| Service | URL | Description |
|----------|-----|-------------|
| **API** | http://localhost:8080 | Main REST API |
| **Swagger UI** | http://localhost:8080/api/documentation | Interactive documentation — see below |
| **Mailpit** | http://localhost:8025 | Email viewer (for notifications) |
| **MySQL** | localhost:3306 | Database (user: `sail`, pass: `password`) |
| **Redis** | localhost:6379 | Cache and queues |

---

## 🔍 Trying the API

### Interactive documentation

OpenAPI annotations live on the controllers and are compiled by `l5-swagger`.

```bash
docker compose exec -T laravel php artisan l5-swagger:generate
```

Then open **http://localhost:8080/api/documentation**.

**Access control.** The docs route is public only when `APP_ENV=local`. Anywhere
else it is wrapped in `auth:sanctum` + `role:admin`
(see [`config/l5-swagger.php`](config/l5-swagger.php)), so on a deployed instance
you need an admin bearer token to open it. Set `L5_SWAGGER_PROTECT=false` if you
deliberately want the docs public.

Since Swagger UI cannot log in for you, get a token first and paste it into the
**Authorize** dialog as `Bearer <token>`.

### From the command line

```bash
# 1. Log in (seeded admin)
TOKEN=$(curl -s -X POST http://localhost:8080/api/login   -H 'Content-Type: application/json' -H 'Accept: application/json'   -d '{"email":"test@example.com","password":"password"}'   | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

# 2. Call a business endpoint
curl -s http://localhost:8080/api/v1/candidates   -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# 3. The consolidated evaluators view
curl -s 'http://localhost:8080/api/v1/evaluators/consolidated?per_page=15'   -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# 4. Health probes (open in local, token-gated elsewhere)
curl -s http://localhost:8080/api/health
curl -s http://localhost:8080/api/readiness
```

Every response carries an `X-Request-Id` header; the same id appears in the
application and access logs.

> **Queue-backed endpoints.** `POST /api/v1/candidates/{id}/analyze` and
> `POST /api/v1/evaluators/report` answer `202` and finish the work in a job.
> Without a queue worker running they never complete.

### Run Queue Worker (Important)

Required for the AI screening job and the Excel report:

```bash
docker compose exec -T laravel php artisan queue:work
```

> **Note:** In production use a systemd unit (see [DEPLOY.md](DEPLOY.md)) or Supervisor to keep the
> worker running. `queue:work` holds code in memory, so restart it on every deploy.

### Run Scheduler

`ProcessOverdueAssignmentsJob` runs every 15 minutes:

```bash
docker compose exec -T laravel php artisan schedule:work
```

### Test Data (Seeders)

`migrate:fresh --seed` creates:

- 1 admin user — `test@example.com` / `password`
- 20 candidates with different experience levels
- 5 evaluators (`Backend`, `Frontend`, `Fullstack`, `DevOps`, `Mobile`)
- assignments with varied statuses

> **Never run the seeder in production**: it creates a known admin with a known password.
> [DEPLOY.md](DEPLOY.md) has the one-liner for creating a real admin instead.

Test and static-analysis commands are in the [Testing](#-testing) section.

---

## 📈 Performance evidence

> **Measured locally, not on the production VPS.** Every figure below comes from
> a run against the Docker Compose stack on a development machine. They are a
> regression baseline, not a statement about production capacity — a VPS with
> different CPU, disk and network will not reproduce them.

### What was measured

[`load-tests/candidacy-flow.js`](load-tests/candidacy-flow.js) drives the API's
core write-then-read flow under concurrency: register a candidate, assign it to
an evaluator (the path guarded by `SELECT ... FOR UPDATE` against double
assignment), then read the consolidated evaluators view (the `GROUP_CONCAT`
aggregate). 10 virtual users, 100 iterations, followed by a read-only pass with
no writes in flight.

### Conditions

| | |
|---|---|
| Tool | k6 `v2.0.0-rc1` (official `grafana/k6` Docker image) |
| Environment | **Local** — Docker Compose (`laravel` + `mysql` 8 + `redis`), Docker 29.7.2 |
| Host | Intel Core i5-11400H (6 cores / 12 threads), 32 GB RAM, Windows 10 |
| Dataset at run time | 620 candidates, 63 evaluators, 519 assignments |
| Rate limit | Raised for the run (`API_RATE_LIMIT_PER_MINUTE`), otherwise the default 60/min would measure the limiter instead of the app |

### Result

```text

  Candidacy Management API - k6 load test (LOCAL run, not the VPS)

  http requests...........................: 783
  responses with a 5xx....................: 0
  failed requests.........................: 0.00%
  checks passed...........................: 771 / 771

  latency                                      avg       median    p95       max
  Register candidate                           492.4 ms  508.2 ms  589.1 ms  624.7 ms
  Assign candidate                             2810.7 ms 2826.0 ms 4613.0 ms 5101.9 ms
  Consolidated read (under write load)         2316.8 ms 2325.3 ms 4177.1 ms 4623.2 ms
  Consolidated read (no writes in flight)      213.5 ms  197.2 ms  275.2 ms  712.8 ms
```

Artifacts from this exact run are committed next to the scenario:
[`summary.txt`](load-tests/results/summary.txt),
[`summary.json`](load-tests/results/summary.json) and an HTML report at
[`report.html`](load-tests/results/report.html).

### Reading the numbers honestly

- **No 5xx, no failed requests, all 771 checks passed.** That is the claim this
  run supports, and the `server_errors_5xx: count==0` threshold is what enforces it.
- **The consolidated read is ~275 ms p95 uncontended and ~4.2 s p95 while writes
  are in flight.** That gap is not the query: every assignment invalidates the
  consolidated cache and holds row locks, so under a write-heavy load each read
  is a cold miss queued behind writes. Both figures are reported rather than
  just the flattering one.
- **These latencies are dominated by the environment.** A bind-mounted Docker
  volume on Windows makes every PHP file read cross a filesystem boundary; the
  same endpoint answers in ~50 ms served sequentially.
- **A `409` on assignment is a correct answer** (evaluator at capacity, or the
  candidate is already assigned), so the scenario counts it as an expected
  status rather than a failure.

Reproduction steps, options and thresholds are in
[`load-tests/README.md`](load-tests/README.md).

---

## 📡 API Endpoints

### Candidates

#### `POST /api/v1/candidates`
Register new candidacy.

**Body:** (`cv` is the CV text; send `cv_file` as a PDF upload instead if you prefer)
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "years_of_experience": 5,
  "primary_specialty": "Backend",
  "cv": "Full Stack Developer with 5 years..."
}
```

`cv` is required unless a `cv_file` (PDF, max 5 MB) is uploaded as multipart.

**Response:** `201 Created`
```json
{
  "message": "Candidacy registered successfully",
  "data": { "id": 1, "email": "john@example.com", "analysis_status": "processing" }
}
```

---

#### `GET /api/v1/candidates/{id}/summary`
Get complete candidacy summary with validations.

**Response:** the CV is returned as a truncated `cv_preview`, never in full — the complete
document is only available through the authenticated download endpoint.

```json
{
  "data": {
    "candidate_info": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "experience_years": 5,
      "cv_preview": "Full Stack developer with 5 years of experience in Laravel, Vue.js and MySQL...",
      "cv_pdf": false,
      "cv_download_url": null
    },
    "assignment_info": {
      "evaluator_name": "Dr. Albert Martinez",
      "evaluator_email": "albert@example.com",
      "assigned_at": "2024-11-20 10:30:00",
      "status": "in_progress"
    },
    "compliance_report": {
      "CV Required": "Passed",
      "Valid Email": "Passed",
      "Minimum Experience": "Passed"
    }
  }
}
```

---

#### `GET /api/v1/candidates` · `GET /api/v1/candidates/search`
List / search candidates (admin only). Supports `search` filter and pagination.

---

#### `GET /api/v1/candidates/{id}/cv`
Download the candidate's uploaded CV file from the private disk.

---

#### `POST /api/v1/candidates/{id}/analyze`
Queue an **AI screening** of the candidate's CV. The `AnalyzeCandidateCvJob`
calls the configured AI provider (**OpenAI** or **Gemini**, selected via
`AI_PROVIDER`) and persists a structured evaluation.

**Response:** `202 Accepted`
```json
{ "status": "processing", "message": "Analysis queued" }
```

---

#### `GET /api/v1/candidates/{id}/evaluation`
Get the latest AI evaluation for a candidate.

**Response:**
```json
{
  "data": {
    "candidate_id": 1,
    "summary": "Full Stack professional with 5 years of experience...",
    "skills": ["Laravel", "Vue.js", "MySQL"],
    "years_experience": 5,
    "seniority_level": "Senior",
    "analyzed_at": "2026-06-30 17:38:10"
  }
}
```

---

### Evaluators

#### `POST /api/v1/evaluators`
Register new evaluator.

**Body:**
```json
{
  "name": "Mary Gonzalez",
  "email": "mary@example.com",
  "specialty": "Backend"
}
```

**Valid Specialties** (`Src\Evaluators\Domain\Enums\Specialty`, case-sensitive):
`Backend`, `Frontend`, `Fullstack`, `DevOps`, `Mobile`, `QA`, `Data`, `Security`

---

#### `GET /api/v1/evaluators/consolidated`
Consolidated list with complex SQL (GROUP_CONCAT, JOIN, AVG, COUNT).

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15)
- `search`: Filter by name or email
- `sort_by`: Sort by (`name`, `email`, `created_at`, `average_experience`, `specialty`, `total_assigned_candidates`, `concatenated_candidate_emails`)
- `sort_direction`: `asc` or `desc` (default: `desc`)

**Default Order:** The list is ordered by `average_experience` (average years of experience of candidates per evaluator) in descending order, computed as an aggregation at the SQL level so evaluators are ranked by the seniority of their candidate pool. If you need another sort criterion, you can specify it via `sort_by`.

**Optional Filters (any column in the list):**
- `specialty`: Filter by evaluator specialty (like).
- `min_average_experience` / `max_average_experience`: Range of average experience.
- `min_total_assigned` / `max_total_assigned`: Range of assigned candidates (COUNT in SQL).
- `candidate_email_contains`: Filter by concatenated candidate emails (GROUP_CONCAT in SQL).

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Dr. Albert Martinez",
      "email": "albert@example.com",
      "specialty": "backend",
      "average_candidate_experience": 5.3,
      "total_assigned_candidates": 4,
      "concatenated_candidate_emails": "ana@example.com, carlos@example.com, john@example.com, mary@example.com",
      "candidates": [
        {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com",
          "years_of_experience": 5
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 5
  }
}
```

---

#### `POST /api/v1/evaluators/{evaluatorId}/assign-candidate`
Assign candidate to evaluator.

**Body:**
```json
{
  "candidate_id": 1
}
```

**Response:** `200 OK`

- `404` if the evaluator does not exist
- `409` if the candidate is already assigned, or the evaluator is at capacity
  (`Evaluator::MAX_CONCURRENT_CANDIDATES`, 10)

---

#### `PUT /api/v1/evaluators/{evaluatorId}/assignments/{candidateId}/start-progress`
Move assignment status to `in_progress`. Triggers status-change email notifications.

**Response:** `200 OK`

---

#### `PUT /api/v1/evaluators/{evaluatorId}/assignments/{candidateId}/complete`
Mark assignment as `completed`. Triggers status-change email notifications.

**Response:** `200 OK`

---

#### `PUT /api/v1/evaluators/{evaluatorId}/assignments/{candidateId}/reject`
Mark assignment as `rejected`. Triggers status-change email notifications.

**Response:** `200 OK`

---

#### `DELETE /api/v1/evaluators/{evaluatorId}/assignments/{candidateId}`
Unassign a candidate from an evaluator.

**Response:** `200 OK`

---

#### `PUT /api/v1/evaluators/{newEvaluatorId}/reassign-candidate/{candidateId}`
Reassign a candidate to a new evaluator. Sends assignment notifications.

**Response:** `200 OK`

---

#### `GET /api/v1/evaluators/{evaluatorId}/candidates`
Get candidates assigned to an evaluator.

**Response:**
```json
{
  "evaluator": {
    "id": 1,
    "name": "Dr. Albert Martinez",
    "email": "albert@nalanda.com",
    "specialty": "backend"
  },
  "candidates": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "years_of_experience": 5,
      "assignment_status": "in_progress",
      "assigned_at": "2024-11-20 10:30:00"
    }
  ]
}
```

---

#### `GET /api/v1/candidates/{candidateId}/assignment-history`
Chronological status timeline of a candidate's assignments. Built from domain
events (`CandidateAssigned`, `AssignmentStatusChanged`) recorded into an
append-only `assignment_history` table, so the trail is preserved even if the
candidate is later unassigned. Admin only.

**Response:**
```json
{
  "data": [
    { "assignment_id": 7, "candidate_id": 1, "evaluator_id": 2, "from_status": null, "to_status": "pending", "occurred_at": "2026-06-30 10:30:00" },
    { "assignment_id": 7, "candidate_id": 1, "evaluator_id": 2, "from_status": "pending", "to_status": "in_progress", "occurred_at": "2026-06-30 11:05:00" },
    { "assignment_id": 7, "candidate_id": 1, "evaluator_id": 2, "from_status": "in_progress", "to_status": "completed", "occurred_at": "2026-06-30 14:20:00" }
  ]
}
```

---

#### `POST /api/v1/evaluators/report`
Generate Excel report (asynchronous with queue).

**Body:**
```json
{
  "email": "recipient@example.com"
}
```

**Response:** `202 Accepted`

The report is generated in the background and sent by email when ready.

---

## 🧪 Testing

The live count is on the **CI badge** at the top of this file rather than
written out here, so it cannot go stale.

### Layout

```
tests/
├── Auth/Acceptance/          # Authorization, permissions cascade
├── Candidates/
│   ├── Unit/                 # Value Objects, Chain-of-Responsibility validators
│   ├── Integration/          # Audit log, real-AI evaluation (opt-in)
│   └── Acceptance/           # HTTP: register, list, summary, CV download, AI analysis
├── Evaluators/
│   ├── Unit/                 # Evaluator, assignment, enums, overdue job
│   ├── Integration/          # Listeners, notifications, history, query counts
│   └── Acceptance/           # HTTP: register, assign, reassign, unassign, consolidated
├── Security/Acceptance/      # Unauthenticated access, role gates, throttling, SQLi,
│                             # query-parameter validation, security headers, health probes
├── Shared/
│   ├── Unit/                 # TestSuiteCoverageTest (phpunit.xml guard)
│   └── Integration/          # Audit log, request-context and access logging, Sentry wiring
└── TestCase.php              # Base case — no ambient auth, exposes actingAsAdmin()
```

**Authentication is explicit.** `TestCase` does not log anyone in: a test that
needs an admin calls `actingAsAdmin()`. Authenticating in `setUp()` would make a
401 impossible to assert and let an authorization test pass for the wrong
reason — which is exactly why
`tests/Security/Acceptance/UnauthenticatedAccessTest.php` can exist.

**`phpunit.xml` lists its suites one by one**, so a new top-level directory under
`tests/` would silently never run. `tests/Shared/Unit/TestSuiteCoverageTest.php`
fails until the matching `<testsuite>` is added.

### Run Tests

```bash
# All
docker compose exec -T laravel php artisan test

# One suite or one class
docker compose exec -T laravel php artisan test --testsuite=Evaluators
docker compose exec -T laravel php artisan test --filter=AssignCandidateTest

# Static analysis (PHPStan level 9, zero errors tolerated)
docker compose exec -T laravel php ./vendor/bin/phpstan analyse

# Dependency audit
docker compose exec -T laravel composer audit --abandoned=report
```

---

## 📦 Technologies

### Core
- **Laravel 12** - Base Framework
- **PHP 8.4** - Language (typed properties, readonly, enums)
- **MySQL 8.0** - Relational Database

### Architecture
- **Hexagonal Architecture** - Layer Decoupling
- **Domain-Driven Design** - Domain Modeling
- **SOLID Principles** - Maintainable Code

### Libraries
- `laravel/sanctum` - API token authentication
- `maatwebsite/excel` - Excel Report Export
- `darkaonline/l5-swagger` - OpenAPI Documentation
- `sentry/sentry-laravel` - Error monitoring and alerting
- `laravel/telescope` - Local debugging (**dev dependency**; not installed by `--no-dev`)
- `phpunit/phpunit` - Testing Framework

### AI Screening
- **OpenAI** / **Google Gemini** adapters for CV analysis, selected at runtime via `AI_PROVIDER`
- Asynchronous via `AnalyzeCandidateCvJob` (queue); persists a structured evaluation (summary, skills, years of experience, seniority level)

### DevOps
- **Docker** (Laravel Sail) - Local Development
- **Redis** - Cache and Queues
- **Mailpit** - Email Testing

---

## 🛠️ Troubleshooting

### Tests fail with database connection error

```bash
# Clear config and restart
docker compose exec -T laravel php artisan config:clear
docker compose exec -T laravel php artisan migrate:fresh --seed
docker compose exec -T laravel php artisan test
```

### Queue worker does not process jobs

```bash
# Restart the worker
docker compose exec -T laravel php artisan queue:restart

# In another terminal, start the worker
docker compose exec -T laravel php artisan queue:work

# Verify that the job was dispatched
docker compose exec -T laravel php artisan queue:failed
```

### "Class not found" error after creating new classes

```bash
# Regenerate autoload
docker compose exec -T laravel composer dump-autoload
```

### Emails are not sent (reports)

```bash
# Verify Mailpit is running
docker compose ps

# Access Mailpit UI
open http://localhost:8025

# Check job logs
docker compose exec -T laravel php artisan queue:work --verbose
```

### Swagger is not generated correctly

```bash
# Clear cache and regenerate
docker compose exec -T laravel php artisan config:clear
docker compose exec -T laravel php artisan route:clear
docker compose exec -T laravel php artisan l5-swagger:generate
```

### Permission error in storage/

```bash
# Give permissions (Linux/Mac)
docker compose exec -T laravel php artisan storage:link
sudo chmod -R 777 storage bootstrap/cache

# Windows: Run as Administrator or adjust permissions in properties
```

---

## 📝 Final Notes

### 🌟 Strong Points

✅ **Senior Architecture:** Hexagonal + DDD correctly implemented
✅ **Complex SQL:** GROUP_CONCAT, JOINs, multiple aggregations
✅ **Patterns:** Extensible Chain of Responsibility
✅ **Testing:** unit, integration and acceptance layers, with explicit authentication
✅ **Implemented Scalability:** Queues + Idempotency with `ShouldBeUnique`
✅ **Documentation:** Swagger + Complete README with diagrams

---

## 🚢 Deployment

Full VPS instructions — Nginx with TLS and `limit_req`, PHP-FPM settings, queue
worker and scheduler units, verification curls and the redeploy sequence — are in
**[DEPLOY.md](DEPLOY.md)**. Start from
[`.env.production.example`](.env.production.example) rather than `.env.example`.

### Environment variables

Beyond the standard Laravel set, this project reads:

| Variable | Default | Purpose |
|----------|---------|---------|
| `HEALTHCHECK_TOKEN` | — | **Required outside local/testing.** Shared secret for `/api/health` and `/api/readiness`; the probes fail closed without it. Generate with `openssl rand -hex 32` |
| `API_RATE_LIMIT_PER_MINUTE` | `60` | Requests per minute on the authenticated API, keyed by token owner. Raise only for load testing |
| `CORS_ALLOWED_ORIGINS` | *(empty)* | Comma-separated browser origins. Empty means no browser client; server-to-server callers are unaffected |
| `SENTRY_LARAVEL_DSN` | *(empty)* | Enables error reporting. Empty disables it entirely |
| `SENTRY_TRACES_SAMPLE_RATE` | — | Optional performance tracing |
| `L5_SWAGGER_PROTECT` | `true` outside local | Keeps Swagger UI behind `auth:sanctum` + `role:admin` |
| `LOG_DAILY_DAYS` | `14` | Retention for the structured application log |
| `LOG_ACCESS_DAYS` | `7` | Retention for the access log |
| `OVERDUE_ESCALATION_DAYS` | `3` | Days overdue before the scheduler escalates to admins |
| `AI_PROVIDER` | `openai` | `openai` or `gemini`; selects the screening adapter |

> All of these are read through `config()`, never `env()` at runtime. Once
> `php artisan config:cache` runs in production, `.env` is no longer read and an
> `env()` call outside `config/` silently returns its default.

Three standard Laravel variables also need the real domain, not just the deploy host:
`APP_URL` (it builds the `cv_download_url` returned by `/summary` and the links in notification
emails), `L5_SWAGGER_CONST_HOST` (otherwise Swagger's "Try it out" fires at localhost) and
`MAIL_FROM_ADDRESS`.

**On CORS.** It is a browser protection, not an API access control: `curl`, Postman and
server-to-server callers ignore it entirely, so leaving `CORS_ALLOWED_ORIGINS` empty costs nothing
and blocks nothing. Set it only when a browser front-end on a *different* origin needs to read
responses. A browser client using cookies instead of Bearer tokens is a different setup again — it
would additionally need `supports_credentials` and `SANCTUM_STATEFUL_DOMAINS`.

---

## 🗺️ Roadmap (Optional Improvements)

> ℹ️ **Note**: The following are potential future enhancements that are **not yet implemented**. They are intentionally outside the project's current scope, but are natural next steps for a larger production deployment.

### Performance
- [ ] **Query Optimization**: selective eager/lazy loading to reduce memory on very large reports

### Features
- [ ] **Excel Multi-Sheet Pagination**: split very large reports across sheets — *currently a single sheet with all records*
- [ ] **Webhooks**: outbound, signed (HMAC) event delivery with retries for external integrations

### DevOps
- [ ] **Continuous Deployment**: automated deploy step on top of the existing CI (tests + PHPStan with MySQL/Redis services)

---

## ❓ Questions and Support

For questions about implementation, architectural decisions, or technical details:

1. **Review source code**: The structure is self-documented
2. **Consult tests**: the suite documents expected behaviour
3. **Swagger**: Interactive API documentation

> The project architecture is designed to be **self-explanatory** through clean code, comprehensive tests, and integrated documentation.

---

<p align="center">
  <strong>Developed with Hexagonal Architecture and Design Patterns</strong><br>
  Laravel 12 | PHP 8.4 | MySQL | Redis | Docker
</p>
