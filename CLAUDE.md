# Owlet - Point of Sales System Rewrite

## Project Overview

Owlet is a modern rewrite of an existing Point of Sales (POS) system with employee management. The legacy system is outdated and is being incrementally replaced.

## Reference Codebases

When working on this project, reference the legacy code for context:

- **Legacy API (Laravel):** `~/Documents/Development/goldlink-api`
- **Legacy Frontend (React):** `~/Documents/Development/goldlink-react`

Use these references to understand:
- Existing business logic and workflows
- Database schema and relationships
- Feature requirements and edge cases
- API contracts and data structures

## Architecture Decisions

- **New models and UI are permitted** - This is not a 1:1 port. Improve upon the original design where appropriate.
- **Read-only legacy data access** - The system must connect to and display data from the old database in a read-only webpage format.
- **Incremental development** - Features will be prompted and built one at a time.
- **API-first backend design** - The Laravel backend must support both the Vue/Inertia frontend and external API clients (mobile apps, integrations).

## API Design Guidelines

The backend should be structured to serve both Inertia.js requests and standard JSON API requests:

- **Separate business logic from controllers** - Use Service classes or Actions to encapsulate business logic. Controllers should be thin wrappers that call services.
- **Dual-purpose controllers** - Controllers should detect request type and respond appropriately:
  - Inertia requests → Return Inertia responses with page components
  - API requests → Return JSON responses
- **Use Laravel's `wantsJson()` or custom middleware** - Check `$request->wantsJson()` or use the `Accept: application/json` header to determine response format.
- **Consistent API responses** - Use Laravel API Resources for JSON responses to ensure consistent data structure across all endpoints.
- **API routes in `routes/api.php`** - Define API-specific routes with proper versioning (e.g., `/api/v1/`). Web routes remain in `routes/web.php`.
- **Shared validation** - Use Form Request classes for validation that work for both Inertia and API requests.
- **Authentication flexibility** - Support both session-based auth (web) and token-based auth (API) via Laravel Sanctum.
- **Error handling** - Return appropriate error formats: Inertia error bags for web, JSON error responses for API.

## Tech Stack

- **Backend:** Laravel (fresh install in this directory)
- **Frontend:** Vue 3, Inertia.js, PrimeVue, Tailwind CSS
- **Testing:** Pest

## UI/UX Guidelines

- **Use PrimeVue components first** - Always check if a PrimeVue component exists for the UI need before building custom components or using other libraries.
- **Prefer small (sm) sized UI elements** - Use `size="small"` for PrimeVue components (buttons, inputs, etc.) on both mobile and desktop views for a compact, consistent interface.
- **Responsive design is required** - All UI must be fully responsive and work across all screen sizes (mobile, tablet, desktop).
- **Mobile-first approach** - Design for small screens first, then enhance for larger screens.
- **Small screen UX patterns:**
  - Use collapsible menus and drawers instead of persistent sidebars
  - Stack form fields vertically on mobile
  - Use full-width buttons and touch-friendly tap targets (minimum 44px)
  - Replace data tables with cards or lists on narrow screens
  - Use bottom sheets or modals for actions instead of inline controls
  - Prioritize essential content; hide secondary information behind expandable sections
- **Use Tailwind responsive prefixes** (`sm:`, `md:`, `lg:`, `xl:`) consistently

## Development Approach

1. When asked about a feature, first check the legacy codebases for existing implementation
2. Understand the original intent before proposing new designs
3. New implementations should be cleaner and more maintainable than the original
4. Preserve data compatibility with the legacy database for read operations
