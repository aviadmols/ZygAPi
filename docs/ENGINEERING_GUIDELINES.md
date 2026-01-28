# Engineering Guidelines

## English-Only Rule

All code, comments, UI labels, error messages, log messages, and documentation must be in English only. No Hebrew or other non-English languages are allowed in the codebase.

## Function Length

Functions should be 20-30 lines maximum. If a function exceeds this limit, break it down into smaller, focused functions.

## Naming Conventions

- Use descriptive names that clearly indicate the function's single responsibility
- Classes: PascalCase (e.g., `RedactionService`)
- Methods: camelCase (e.g., `redactHeaders`)
- Constants: UPPER_SNAKE_CASE (e.g., `STATUS_SUCCESS`)

## Logging and Redaction Rules

- All sensitive data (tokens, API keys, passwords) must be redacted before logging
- Use `RedactionService` for all request/response logging
- Never log raw tokens or credentials
- Structured logging: Use JSON format for logs when possible

## How to Add New Actions

1. Create a new action class in `app/Integrations/{Provider}/Actions/`
2. Implement `ActionInterface` with:
   - `name()`: Returns action identifier (e.g., `shopify.order.get`)
   - `execute()`: Performs the actual action
   - `simulate()`: Returns simulation/dry-run result
   - `requiredScopes()`: Returns array of required API scopes
3. Register the action in `StepRunner::getActionInstance()`
4. Add tests for the new action

## Architecture Principles

- Clean architecture boundaries: Domain logic separated from infrastructure
- Avoid "god classes": Keep classes focused on single responsibility
- Use dependency injection for services
- Encrypt all tokens at rest using Laravel Crypt
