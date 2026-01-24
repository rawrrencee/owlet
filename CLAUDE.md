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
- **Inertia-first frontend design** - The primary interface is the Vue/Inertia frontend. API support for external clients (mobile apps, integrations) is a secondary requirement for future extensibility.

## Frontend Request Guidelines

All frontend interactions should use Inertia's built-in request handling:

- **Use Inertia router and forms** - Always use `router.visit()`, `router.get()`, `router.post()`, `useForm()`, and other Inertia methods for requests. Avoid axios or fetch unless required by third-party dependencies.
- **Leverage Inertia's features** - Use Inertia's automatic CSRF handling, validation error bags, preserveState, preserveScroll, and partial reloads.
- **Form submissions** - Use `useForm()` for all form handling to get automatic validation error handling and form state management.
- **Navigation** - Use `<Link>` component or `router.visit()` for navigation instead of traditional anchor tags or window.location.

## Backend Design Guidelines

The backend primarily serves the Inertia frontend, with API support as a secondary concern:

- **Separate business logic from controllers** - Use Service classes or Actions to encapsulate business logic. Controllers should be thin wrappers that call services.
- **Primary web routes** - Define all routes in `routes/web.php` for Inertia. Controllers return Inertia responses by default.
- **Secondary API routes** - When API support is needed, define separate routes in `routes/api.php` with versioning (e.g., `/api/v1/`). Use Laravel API Resources for consistent JSON responses.
- **Shared validation** - Use Form Request classes for validation that work for both Inertia and API requests.
- **Authentication** - Primary: session-based auth for web. Secondary: token-based auth (API) via Laravel Sanctum when needed.
- **Error handling** - Return Inertia error bags for web requests. JSON error responses for API requests when implemented.

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
