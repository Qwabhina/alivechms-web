# AliveChMS Backend Architecture Guide (v2.0)

> **Last Updated:** 2026-02-03
> **Architecture:** PSR-4 Compliant, Domain-Driven Design (DDD)
> **Pattern:** Repository-Service Pattern

---

## 🏗 Directory Structure

The `core/` directory is the heart of the application, organized by domain.

```
core/
├── Database/          # Database Layer (ORM, Migrations, Schema)
│   ├── Blueprint.php
│   ├── Migration.php
│   ├── MigrationManager.php
│   └── SchemaBuilder.php
├── Events/            # Event System
│   ├── AbstractEventListener.php
│   ├── EventDispatcher.php
│   ├── Listeners/     # Event Listeners (UserActivityLogger, etc.)
│   └── [EventName].php # Individual Event Classes
├── Financial/         # Financial Domain
│   ├── Contribution.php
│   ├── ContributionRepository.php
│   ├── Budget.php
│   └── ...
├── Http/              # HTTP Foundation
│   ├── Request.php
│   ├── Response.php
│   ├── Middleware.php
│   └── MiddlewarePipeline.php
├── Identity/          # Auth & User Management
│   ├── Auth.php
│   ├── RBAC.php
│   └── TokenManager.php
├── Infrastructure/    # External Services
│   ├── Mailer.php
│   ├── SmsProviderInterface.php  # Pluggable SMS System
│   └── HubtelProvider.php
├── Operations/        # Church Operations
│   ├── Group.php
│   ├── Event.php
│   └── Dashboard.php
├── People/            # Member Management
│   ├── Member.php
│   ├── MemberRepository.php
│   └── Family.php
├── Providers/         # Service Providers (DI configuration)
└── System/            # Core Kernel Utilities
    ├── Application.php # DI Container & Bootstrap
    ├── Helpers.php
    └── SettingsHelper.php
```

---

## 核心 Core Components

### 1. Application Kernel (`Core\System\Application`)
The entry point for the application logic. It:
- Initializes the **Dependency Injection (DI)** Container.
- Registers **Service Providers**.
- Boots the application environment.

### 2. HTTP Layer (`Core\Http`)
Fully decoupled HTTP handling:
- **`Request`**: Encapsulates `$_GET`, `$_POST`, `$_SERVER`, and JSON body.
- **`Response`**: standardized JSON responses (`success`, `error`, `paginated`).
- **`Middleware`**: Chainable middleware for Auth, Rate Limiting, and CORS.
- **`MiddlewarePipeline`**: Manages the execution flow of middleware.

### 3. Database Layer (`Core\Database`)
- **ORM (`Core\System\ORM`)**: Singleton wrapper around PDO for secure SQL execution.
- **Repositories**: All SQL validation and execution happens here (e.g., `MemberRepository`).
- **Schema Builder**: Fluent interface for handling migrations (`SchemaBuilder`, `Blueprint`).

### 4. Event System (`Core\Events`)
Decoupled observer pattern:
- **`EventDispatcher`**: Dispatches events to registered listeners.
- **`AbstractEventListener`**: Base class for all listeners.
- **Events**: Simple DTOs (e.g., `UserLoginEvent`, `DatabaseQueryEvent`).
- **Listeners**: Logic that runs when events occur (e.g., `UserActivityLogger`).

---

## 🔄 Request Lifecycle

1.  **Entry Point (`index.php` or `public/index.php`)**
    - Loads `vendor/autoload.php`.
    - Initializes `Application` kernel.
    - Sets global settings and error handling.

2.  **Routing (`routes/*.php`)**
    - The correct route file is loaded (e.g., `MemberRoutes.php`).
    - **`TokenManager`** validates JWT/CSRF tokens.
    - **`RBAC`** checks user permissions (e.g., 'member.create').

3.  **Controller / Service Execution**
    - The route calls a **Service Class** (e.g., `Member::create`).
    - Service validates business logic (using `Validator`).

4.  **Data Access (Repository)**
    - Service delegates data persistence to a **Repository** (e.g., `MemberRepository::save`).
    - Repository executes secure SQL via **ORM**.

5.  **Event Dispatching**
    - If successful, Service dispatches an event (e.g., `UserRegistrationEvent`).
    - Listeners run (logging, email, etc.) without blocking the response.

6.  **Response**
    - `Response::success()` formats the data into JSON.
    - Headers (content-type, cookies) are sent.

---

## 🛡 Security Architecture

-   **Authentication**: JWT (Access Token) + HttpOnly Cookie (Refresh Token).
-   **Authorization**: Granular Role-Based Access Control (RBAC).
-   **CSRF**: Token-based protection for state-changing requests.
-   **SQL Injection**: 100% Prepared Statements via ORM.
-   **XSS/Input**: Automatic sanitization middleware.

---

## 🧩 Namespace Map

| Directory | Namespace | Description |
| :--- | :--- | :--- |
| `core/Database` | `AliveChMS\Core\Database` | Schema & Migrations |
| `core/Events` | `AliveChMS\Core\Events` | Event Bus |
| `core/Financial` | `AliveChMS\Core\Financial` | Finance Logic |
| `core/Http` | `AliveChMS\Core\Http` | Request/Response Objects |
| `core/Identity` | `AliveChMS\Core\Identity` | Auth & User Logic |
| `core/Infrastructure` | `AliveChMS\Core\Infrastructure` | SMS, Email, Cache |
| `core/Operations` | `AliveChMS\Core\Operations` | Church Ops Logic |
| `core/People` | `AliveChMS\Core\People` | Member Logic |
| `core/System` | `AliveChMS\Core\System` | Core Utilities |

