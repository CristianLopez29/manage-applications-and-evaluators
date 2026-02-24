# Architecture & Development Standards

This document defines the standard architecture, directory structure, and coding guidelines for the project. It is intended to be copied and adapted for new projects to ensure consistency across the organization.

## 1. Architecture Principles (Hexagonal / Ports & Adapters)
The project follows a strict **Modular Monolith** architecture using Hexagonal principles. All core logic resides in `src/`, separated by Bounded Contexts.

### A. Directory Structure
Each Context (e.g., `Candidates`, `Evaluators`) follows this structure:

```text
src/{Context}/
├── Domain/              # Pure business logic (Inner Hexagon)
│   ├── Events/          # Domain Events
│   ├── Exceptions/      # Domain-specific Exceptions
│   ├── Repositories/    # Interfaces (Ports)
│   ├── Services/        # Domain Services
│   ├── Validators/      # Domain Validation Rules
│   ├── ValueObjects/    # Value Objects
│   └── {Entity}.php     # Aggregate Root / Entities
│
├── Application/         # Application Logic (Coordinating Hexagon)
│   ├── DTOs/            # Data Transfer Objects
│   ├── Transformers/    # Output transformation
│   └── UseCases/        # Application Services / Command Handlers
│
├── Infrastructure/      # Framework & I/O (Adapters)
│   ├── Ai/              # AI Service Implementations
│   ├── Controllers/     # HTTP Controllers
│   ├── Jobs/            # Queue Jobs
│   ├── Listeners/       # Event Listeners
│   ├── Persistence/     # Eloquent Models & Repository Implementations
│   └── Notifications/   # Mail/Notification Implementations
│
└── Bindings.php         # ServiceProvider for dependency injection
```

### B. Architectural Rules (Strict)
1.  **Dependency Rule:**
    *   `Domain` depends on **NOTHING** (No framework, no DB, no external libs).
    *   `Application` depends ONLY on `Domain`.
    *   `Infrastructure` depends on `Application` and `Domain`.
2.  **Persistence Ignorance:**
    *   Domain Entities (`Candidate`, `Evaluator`) **DO NOT** extend Eloquent models.
    *   Mapping between Entities and Eloquent Models happens in the Repository implementation (`reconstruct` pattern).
3.  **Communication:**
    *   Contexts communicate via **Domain Events** or explicitly defined Public APIs (Shared Kernel), never by direct database queries to other contexts' tables.

## 2. Testing Strategy
We follow the Testing Pyramid, mirroring the directory structure:

*   **Unit (`tests/{Context}/Unit`)**:
    *   Fast execution.
    *   Tests Domain logic (Validators, Value Objects, Entities).
    *   **NO** Database, **NO** Framework mocking (unless absolutely necessary).
*   **Integration (`tests/{Context}/Integration`)**:
    *   Tests Infrastructure implementations.
    *   Verifies Repositories (DB interaction), Jobs, and External Services adapters.
*   **Acceptance (`tests/{Context}/Acceptance`)**:
    *   Outside-In testing.
    *   Simulates HTTP requests to Controllers.
    *   Verifies the full stack (Route -> Controller -> UseCase -> Domain -> DB).

## 3. Coding Standards & Clean Code Rules

### A. General Style
- **SOLID:** Apply strict SOLID principles.
- **Indentation:** Maximum **2 levels** of indentation per method.
- **Early Return:** Always use early returns to avoid `else` blocks.
- **Typed Properties:** Use strict typing for all properties and method arguments.

### B. Naming Conventions
- **Classes:** `PascalCase`.
- **Methods/Variables:** `camelCase`.
- **Routes:** `kebab-case` (e.g., `/api/candidates/register`).
- **Database Tables:** `snake_case` (plural).
- **Tests:** `snake_case` (e.g., `should_register_a_valid_candidacy`).

### C. Specific Patterns
- **Validation:** Use Domain Validators for business rules, Laravel FormRequests (or Controller validation) for input format validation.
- **DTOs:** Use DTOs to pass data from Controllers to UseCases.
- **Repositories:** Always define an interface in `Domain` and implement it in `Infrastructure`.
