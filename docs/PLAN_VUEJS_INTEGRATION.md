# Implementation Plan - Phase 14: VueJS Frontend Integration

**Goal**: Replace the legacy PHP-rendered frontend with a modern, high-performance Single Page Application (SPA) using Vue 3 and Vite.

## Architectural Overview

The frontend will be a standalone application residing in the `frontend/` directory, communicating with the PHP backend via the JSON API.

### Tech Stack
- **Framework**: Vue 3 (Composition API)
- **Build Tool**: Vite
- **State Management**: Pinia (if needed) or simple reactive stores
- **Routing**: Vue Router
- **HTTP Client**: Axios
- **Styling**: Vanilla CSS with modern variables (HSL, Grids, Flexbox)

---

## Proposed Changes

### 1. Project Initialization
- [NEW] Initialize Vite project in `frontend/` folder.
- [NEW] Configure `vite.config.js` to proxy API requests to the XAMPP/PHP backend.
- [NEW] Set up standard directory structure: `/src/components`, `/src/views`, `/src/services`, `/src/store`.

### 2. API Abstraction Layer
- [NEW] Create `api.js` using Axios with interceptors for JWT injection.
- [NEW] Create domain-specific services (e.g., `memberService.js`, `authService.js`) to encapsulate API calls.

### 3. Core Design System
- [NEW] `index.css`: Implement a premium design system with HSL colors, smooth transitions, and typography.
- [NEW] Base Layout components: `Sidebar.vue`, `Navbar.vue`, `DashboardCard.vue`.

### 4. Authentication Flow
- [NEW] `Login.vue`: Modern login interface with JWT handling.
- [NEW] Secure route guards to prevent unauthorized access.

### 5. Domain Migration (Step-by-Step)
- [NEW] **Dashboard**: Real-time stats cards and charts using `Operations\Dashboard` data.
- [NEW] **Member List**: Data table with server-side pagination and filtering.
- [NEW] **Member Profile**: Detailed view with file upload integration (Profile pics).

---

## Verification Plan

### Automated Tests
- Run `npm run lint` for code quality.
- Verified connectivity between Vue frontend and PHP backend using browser developer tools.

### Manual Verification
- Visual audit of the "Premium Design" aesthetics.
- Testing the end-to-end login -> dashboard -> logout flow.
- Verifying file uploads (member images) through the Vue interface.
