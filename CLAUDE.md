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

## Tech Stack

- **Backend:** Laravel (fresh install in this directory)
- **Testing:** Pest

## Development Approach

1. When asked about a feature, first check the legacy codebases for existing implementation
2. Understand the original intent before proposing new designs
3. New implementations should be cleaner and more maintainable than the original
4. Preserve data compatibility with the legacy database for read operations
