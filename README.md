# AliveChMS - Church Management System (Backend V2.0)

A robust, enterprise-grade church management system refactored for scalability, security, and maintainability.

---

## 🏗 Architecture & Refactoring (Feb 2026)

This project has undergone a major architectural transformation (Phases 1-6) to adopt modern PHP standards and design patterns.

### 1. Repository Pattern
Data access logic has been decoupled from business logic.
- **Location**: `core/Repositories/`
- **Purpose**: Handles all database interactions (CRUD, complex queries).
- **Key Class**: `MemberRepository` - Manages member persistence, filtering, and retrieval.
- **Benefit**: Allows swapping storage backends (if needed) and simplifies unit testing by mocking repositories.

### 2. Service-Oriented Architecture
Core classes now act as the **Service Layer**, orchestrating business rules and delegating tasks.
- **Location**: `core/` (e.g., `Member.php`, `Auth.php`)
- **Role**: Validates input, checks permissions, and calls Repositories/Stats classes.
- **Benefit**: Thinner controllers (Routes) and cleaner entities.

### 3. Statistics & Reporting Module
Heavy aggregation queries have been moved to dedicated classes.
- **Location**: `core/Stats/`
- **Classes**: `MemberStats`, `ContributionStats`, `ExpenseStats`.
- **Benefit**: `Member.php` dropped ~500 lines of code, focusing solely on member management, while reporting logic lives separately.

### 4. Direct Injection & Helper Standardization
- **Dependency Injection**: `ORM` and other dependencies are injected or instantiated locally, avoiding global state where possible.
- **Helpers**: Consolidated into `AliveChMS\Core\Helpers` and `ResponseHelper` for standard execution.

### 5. PSR-4 Namespacing & Autoloading
- **Namespace**: `AliveChMS\Core\`
- **Autoloading**: Managed via Composer. `require_once` calls have been largely removed in favor of `use` statements.

---

## 📁 Updated Project Structure

```
alivechms/
├── core/
│   ├── Repositories/       # [NEW] Data access layer (MemberRepository)
│   ├── Stats/              # [NEW] Reporting logic (MemberStats, ContributionStats)
│   ├── Http/               # Request/Response/Middleware
│   ├── Security/           # CSRF, Token Management
│   ├── Auth.php            # Authentication Service (JWT, RBAC)
│   ├── Member.php          # Member Service Layer
│   ├── ORM.php             # Database Wrapper
│   └── Helpers.php         # Utilities
├── routes/                 # API Endpoints
├── tests/                  # Test Suite
│   ├── Unit/               # Isolated tests (AuthTest, SimpleValidatorTest)
│   └── Integration/        # Database-dependent tests
├── vendor/                 # Composer dependencies
└── phpunit.xml             # Test Configuration
```

---

## 🚀 Installation & Setup

### Requirements
- PHP 8.1+
- Composer
- MySQL 5.7+ / MariaDB

### 1. specific Dependencies
```bash
composer install
composer dump-autoload
```

### 2. Environment Configuration
Copy `.env.example` to `.env` and configure:
```ini
DB_HOST=localhost
DB_NAME=alivechms
DB_USER=root
DB_PASS=
JWT_SECRET=your_secret_key
JWT_REFRESH_SECRET=your_refresh_secret
```

### 3. Database
Import the schema (ensure `alive_chms.sql` is up to date):
```bash
mysql -u root -p alivechms < alive_chms.sql
```

---

## 🧪 Testing

The system uses **PHPUnit 10** for testing.

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/Unit/AuthTest.php
```

### Test Suites
- **Unit Tests**: Test individual classes (`Auth`, `Validators`) using mocking. working and passing.
- **Integration Tests**: Test database interactions (`ORMTest`). *Note: Requires local test database setup.*

---

## 🔒 Security Features

- **JWT Authentication**: Secure stateless authentication with Access (30m) and Refresh (24h) tokens.
- **RBAC**: Role-Based Access Control integrated into `Auth::checkPermission()`.
- **Input Sanitization**: Middleware and Helper-based sanitization for all inputs.
- **CSRF Protection**: Double-submit cookie pattern for state-changing requests.
- **Secure Headers**: CORS headers configured via environment variables.

---

## 🛠 API Overview

The backend exposes a JSON REST API.

- **Auth**: `/auth/login`, `/auth/refresh`, `/auth/logout`
- **Members**: `/members/list`, `/members/get/{id}`, `/members/create`, `/members/update/{id}`
- **Stats**: `/members/stats`, `/finance/stats` (Delegated to `Stats/` module)

Response Format (Standardized):
```json
{
  "status": "success",
  "message": "Operation successful",
  "data": { ... },
  "timestamp": "2026-02-01T12:00:00+00:00"
}
```

---

## 📜 Recent Changelog (Feb 2026)

- **Phase 1**: Critical Schema fixes (`church_budget`, `fiscal_year`).
- **Phase 2**: Foundation (Autoloading, FileService).
- **Phase 3**: Deduplication (Helpers cleanup).
- **Phase 4**: Namespacing (`AliveChMS\Core`).
- **Phase 5**: Architecture (Repositories, Stats Extraction).
- **Phase 6**: Cleanup & Test Stabilization.

---

**Status**: ✅ Stable | **Version**: 2.0.0 Refactor
