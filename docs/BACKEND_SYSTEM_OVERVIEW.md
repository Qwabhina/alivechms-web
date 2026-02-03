# AliveChMS Backend System Overview

## 🏁 Introduction
The AliveChMS backend is a robust, PSR-4 compliant PHP application built with a **Domain-Driven Design (DDD)** approach and a strict **Repository/Service pattern**. It provides a scalable and secure API for church management, handling everything from membership and finances to operations and security.

---

## 🏗 Architectural Layers

### 1. Route Layer (`routes/`)
- **Entry Point**: All HTTP requests enter through individual route files.
- **Base Class**: Every route class extends `AliveChMS\Core\System\BaseRoute`.
- **Responsibilities**:
    - Parsing HTTP method and path.
    - Rate limiting using `Infrastructure\RateLimiter`.
    - Authentication and Permission checking via `Identity\Auth`.
    - Sanitizing basic input and dispatching to services.
- **Rule**: Routes strictly call Services. They contain no database logic.

### 2. Service Layer (`core/[Domain]/[Service].php`)
- **The Brain**: Contains core business logic.
- **Responsibilities**:
    - Complex validation using `System\Validator`.
    - Orchestrating multiple database calls.
    - Triggering events via `Events\EventDispatcher`.
    - Managing transactions using `System\ORM`.
- **Examples**: `People\Member`, `Financial\Contribution`, `Operations\Group`.

### 3. Repository Layer (`core/[Domain]/[Repository].php`)
- **The Librarian**: Encapsulates all SQL logic.
- **Responsibilities**:
    - Direct interactions with `System\ORM`.
    - Building complex queries (Joins, aggregations).
    - Returning hydrated arrays or structured data to Services.
- **Examples**: `People\MemberRepository`, `Identity\AuthRepository`, `System\LookupRepository`.

### 4. Infrastructure & System Layer (`core/System/` & `core/Infrastructure/`)
- **The Framework**: Provides the essential tools used by all other layers.
- **System**: ORM, Database Connection, Helpers, Response formatting, and DI Container.
- **Infrastructure**: Caching (File/Memory), SMS/Email Gateways, and Rate Limiting.

---

## 🔄 The Request Lifecycle

1. **Client Request**: A JSON payload is sent to an endpoint (e.g., `POST /api/member/create`).
2. **Route Handling**: `MemberRoutes` catches the request, validates the JWT token, and checks permissions.
3. **Service Execution**: `People\Member::create()` is called. It validates the input strictly and checks business rules (e.g., duplicate email check).
4. **Repository Query**: `People\MemberRepository::insert()` executes the safe SQL via the ORM.
5. **Response**: `System\ResponseHelper` formats the result into a standardized JSON response (success/error).

---

## 🔐 Identity & Security
- **Authentication**: JWT-based (JSON Web Tokens) with Secure HttpOnly cookies for refresh tokens.
- **Authorization**: Granular Role-Based Access Control (RBAC). Permissions are checked at the route level before execution.
- **Security Utilities**: Automatic input sanitization, CSRF protection (via tokens), and robust SQL injection prevention through the ORM's prepared statements.

---

## 🎨 Frontend Integration Guide

### 1. API Communication
- **Format**: All requests and responses use `application/json`.
- **Response Structure**:
  ```json
  {
    "status": "success",
    "message": "Operation completed",
    "data": { ... }
  }
  ```

### 2. Authentication Flow
- **Login**: `POST /auth/login` returns an `access_token` and sets a `refresh_token` cookie.
- **Headers**: Include `Authorization: Bearer <access_token>` in all authenticated requests.
- **Token Refresh**: If the access token expires, the backend automatically provides a flow to refresh using the HttpOnly cookie.

### 3. File Uploads
- Use `multipart/form-data`.
- The `Infrastructure\EmailGateway` and specialized services handle attachments and profile pictures securely.

---

## 💪 System Strengths
- **Decoupling**: Business logic is separated from HTTP logic and Database logic, making testing and maintenance easy.
- **Security First**: Every layer is designed with security in mind (Rate limiting, strict validation, ORM-only DB access).
- **Scalability**: The Repository/Service pattern allows you to swap database logic or add new domains without breaking existing ones.
- **Standardization**: Consistent response formats and helper utilities reduce frontend complexity.

---

## 📂 Domain Map
- **People**: Members, Families, Visitors, Volunteers.
- **Financial**: Contributions, Budgets, Expenses, Pledges.
- **Operations**: Groups, Events, Documents, Communications.
- **Identity**: Users, Roles, Permissions, Auth Sessions.
- **System**: Core Engine (ORM, DI Container, Helpers).
- **Infrastructure**: External Services (Cache, SMS, Email).
