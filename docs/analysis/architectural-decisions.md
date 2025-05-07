# Architectural Decisions - PHPUnit Testing Framework

This document captures key architectural decisions made in the PHPUnit Testing Framework, including design patterns, refactoring history, and avoided anti-patterns.

## Core Design Decisions

### Settings (and Database Settings) Priority Order

**Decision**: Implement a strict priority order for loading settings:
1. wp-config.php (lowest priority)
2. Config file (.env.testing)
3. Environment variables
4. Lando configuration (highest priority, database and other application settings won't work except how defined in .lando.yml)

**Rationale**: Provides predictable configuration behavior while allowing for environment-specific overrides.

### WordPress Root Validation

**Decision**: Always validate WordPress root using the filesystem path (`$filesystem_wp_root`), never any container path.

**Rationale**: The script runs on the host filesystem, not inside containers, so validation should always use host paths.

### Configuration Loading

**Decision**: Use direct inclusion of wp-config.php rather than regex parsing.

**Rationale**: Direct inclusion is more robust and matches how WordPress itself operates.

### Error Handling

**Decision**: Fail fast with clear error messages if configuration is incomplete.

**Rationale**: Better to fail early with a clear message than to proceed with incomplete configuration.

## Refactoring History

### Database Settings Loading

**Centralized**: into a single, reusable `get_database_settings()` function with clear priority order.

**Why**: Improves maintainability, reduces redundancy, and ensures consistent behavior.

### Path Translation Logic

**Simplified logic**: that sets PHP command and paths based on environment.

**Why**: Reduces complexity and potential for errors.

## Avoided Anti-Patterns

### Regex Parsing of wp-config.php

**Anti-Pattern**: Using regular expressions to parse wp-config.php.

**Solution**: Direct inclusion of wp-config.php to access defined constants.

**Why**: More reliable, matches WordPress's own approach, and avoids regex complexity.

### Conditional Overwrites

**Anti-Pattern**: Complex conditional logic to determine when to overwrite settings.

**Solution**: Load settings in strict priority order, only overwriting with non-empty values.

**Why**: Simplifies logic and makes behavior more predictable.

### Default Values Fallback

**Anti-Pattern**: Falling back to default values for missing database settings.

**Solution**: Throw an error if any required settings are missing after checking all sources.

**Why**: Prevents running with potentially incorrect configuration.

### Mixed Logic and Configuration

**Anti-Pattern**: Mixing configuration loading with business logic.

**Solution**: Separate configuration loading into helper functions.

**Why**: Improves separation of concerns and makes code more maintainable.
