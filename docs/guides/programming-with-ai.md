# Programming with AI Assistance

## Development Workflow and Standards

### Development Process
1. **Sync**: Use `bin/sync-and-test.php` to synchronize code to the WordPress environment and run tests
2. **Test**: Ensure all tests pass and PHPStan level 5 compliance is maintained
3. **Commit**: Make atomic commits with clear, descriptive messages
4. **Document**: Update documentation in a separate commit if needed

### Code Quality Requirements
- PHPStan level 5 compliance is required for all code
- WordPress coding standards must be followed
- Tests should be run frequently during development
- Use PHPCS for code style validation
- Maintain clear separation between development and WordPress environments
- Before pushing to the public repository (Github):
  - All tests must pass
  - PHPStan level 5 compliance must be met
  - Documentation should be up to date

## WordPress + PSR Development Standards

### Directory Structure
```
plugin-name/
├── src/                    # All PHP classes (PSR-4 autoloaded)
│   ├── Model/            # Domain models
│   ├── Service/          # Business logic
│   └── Controller/       # Request handlers
├── tests/                # Test files (WordPress style)
│   ├── Unit/            # Unit tests
│   ├── Integration/      # Integration tests
│   └── WP-Mock/         # WP-Mock tests
├── assets/              # CSS, JS, images (kebab-case)
├── templates/          # Template files (kebab-case)
├── composer.json       # PSR-4 autoloading config
└── plugin-name.php      # Main plugin file (kebab-case)
```

### Naming Conventions

| Type | Location | Naming Convention | Example |
|------|----------|-------------------|---------|
| **Class Files** | `src/` | Match class name (PascalCase) | `Journey_Questions_Model.php` |
| **Test Files** | `tests/` | `test-{feature}.php` (kebab-case) | `test-journey-questions.php` |
| **Main Plugin File** | Root | `plugin-name.php` (kebab-case) | `reinvent-coaching-process.php` |
| **Assets** | `assets/` | kebab-case | `main.js`, `admin-styles.css` |
| **Templates** | `templates/` | kebab-case | `single-journey.php` |

### AI Prompting Guidelines
When requesting code generation, use this exact format:

```
Follow these naming conventions:
- Class files: PSR-4 PascalCase matching class name (e.g., `Journey_Questions_Model.php`)
- Test files: WordPress kebab-case (e.g., `test-journey-questions.php`)
- Non-PHP files: kebab-case (e.g., `admin-styles.css`)
- Directories: kebab-case (e.g., `wp-content/`)
```

### Autoloading Configuration
```json
{
    "autoload": {
        "psr-4": {
            "GL_Reinvent\\": "src/"
        }
    }
}
```

### Key Points for AI Code Generation
1. **Class Files**:
   - Must match class name exactly (case-sensitive)
   - Example: `class Journey_Questions_Model` → `src/Model/Journey_Questions_Model.php`

2. **Test Files**:
   - Use `test-` prefix
   - Describe feature being tested
   - Example: `tests/Unit/test-journey-questions.php`

3. **Non-PHP Files**:
   - Always use kebab-case
   - Examples: `admin-styles.css`, `main.js`, `single-journey.php`

### Key Benefits
1. **Better Organization**: Clear separation of concerns
2. **Improved Tooling**: Better IDE support and static analysis
3. **Easier Testing**: Simplified dependency injection and mocking
4. **Modern Standards**: Follows current PHP ecosystem practices
5. **Scalability**: Easier to maintain as the codebase grows

### Migration Guidelines
1. Keep existing code working during transition
2. Move files to new structure gradually
3. Update namespaces and imports
4. Update autoloader configuration
5. Update test paths and namespaces

## Best Practices for AI-Assisted Development

### What Prompt Should You Use
A prompt that would help me stay on track could be:

IMPORTANT: Before making any changes:
1. Check `docs/guides/code-inventory.md` for existing functions, patterns, and architecture
2. Search for similar functionality in the codebase
3. Maintain consistency with existing code patterns
4. Never modify function signatures without discussion
5. Never use direct environment access when framework functions exist
6. Understand the framework's configuration system

This prompt should be used at the beginning of any significant code change task, especially when adding new features or modifying existing architecture.

## Avoiding Redundancy and Duplicates
- Always check for and remove duplicate function/method signatures, docblocks, or code blocks before submitting or accepting changes.
- Use code search and file-wide review before every commit.

## Session Awareness & File State Consistency
- Always re-read the entire file before making or proposing changes, especially after collaborative or multi-step editing.
- Never assume the file state; verify it before every change.

## Atomic Commits & Change Descriptions
- Make atomic (minimal, isolated) changes—only touch what is necessary.
- Always describe exactly what is being changed and why in commit messages or PR descriptions.

## Pre-Commit/Pre-Review Checklist
- [ ] No duplicate code, docblocks, or function signatures
- [ ] Naming conventions and parameter consistency
- [ ] Docblocks match signatures
- [ ] No unintentional removals/additions
- [ ] All changes are minimal and well-described

### Understanding the Framework's Configuration System

The PHPUnit testing framework uses a specific approach to configuration management that's important to understand:

1. **Configuration Sources**: Settings can come from multiple sources with a specific priority order:
   - Environment variables (highest priority)
   - .env.testing file (medium priority)
   - Global $loaded_settings array (set during bootstrap)
   - Default values (lowest priority)

2. **Accessing Configuration**: Always use the framework's `get_setting()` function:
   ```php
   // CORRECT: Using the framework function
   $plugin_slug = \WP_PHPUnit_Framework\get_setting('YOUR_PLUGIN_SLUG', 'default-value');

   // INCORRECT: Direct environment access
   $plugin_slug = getenv('YOUR_PLUGIN_SLUG');
   ```

3. **Common Pitfalls**:
   - Not including framework-functions.php before using framework functions
   - Using direct environment access instead of get_setting()
   - Forgetting to set the global $loaded_settings variable in bootstrap files
   - Not understanding the difference between WP_ROOT (container path) and FILESYSTEM_WP_ROOT (host path)

4. **Database Configuration**: The framework maintains separate test databases with specific naming conventions. See the get_phpunit_database_settings() function for details.

### Project-Specific Conventions

1. **WordPress Coding Standards**: This project follows WordPress coding standards with some exceptions:
   - Tabs for indentation (not spaces)
   - Snake_case for function and method names
   - Class names with underscores (Your_Plugin_Admin not YourPluginAdmin)
   - Namespaces with backslashes and PascalCase (Your_Plugin\Admin)

2. **Error Handling Approach**:
   - Use descriptive error messages that explain both what happened and what to do
   - Return null/false for utility functions rather than throwing exceptions
   - Echo error messages with color coding for CLI tools

3. **Testing Philosophy**:
   - Every function should have corresponding unit tests
   - Tests should include both standard and non-standard inputs
   - Avoid environment variable manipulation in tests

### Before Requesting Code Changes

1. **Provide Context**: Link to relevant documentation or code files that explain the project architecture
2. **Set Expectations**: Clearly state coding standards and patterns to follow
- Use the classes, functions, variable names, file locations, and patterns defined in `docs/guides/code-inventory.md`
- Use WordPress naming conventions and coding standards, as defined in the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Exception to the above: use Names for included libraries, such as PHPUnit
- Use the same error handling patterns as the rest of the project

### When Reviewing AI Suggestions

1. **Verify Pattern Consistency**: Ensure new code follows existing patterns
2. **Check Function Usage**: Confirm the AI is using existing utility functions rather than reinventing
3. **Review Variable Names**: Ensure they match the project's naming conventions
4. **Validate Error Handling**: Check that error cases are handled consistently

### Examples: Good vs. Problematic AI-Generated Code

#### ❌ Problematic: Direct Environment Access
```php
$wp_root = getenv('WP_ROOT') ? getenv('WP_ROOT') : '/var/www/html';
```

#### ✅ Better: Using Framework Functions
```php
$wp_root = \WP_PHPUnit_Framework\get_setting('WP_ROOT', '/var/www/html');
```

#### ❌ Problematic: Reinventing Existing Functionality
```php
function format_command($command) {
    return 'lando ' . $command;
}
```

#### ✅ Better: Using Existing Functions
```php
$formatted_command = \WP_PHPUnit_Framework\format_ssh_command('lando ssh', $command);
```

### Common AI Pitfalls to Watch For

1. **Direct Environment Access**: The AI may use `getenv()` directly instead of project functions
2. **Inconsistent Error Handling**: May not follow your established error handling patterns
3. **Reinventing Existing Functionality**: May create new functions when suitable ones already exist
4. **Incomplete Understanding of Context**: May miss important architectural constraints
5. **Hardcoded Values**: May include hardcoded values instead of using configuration

### Iterative Development with AI

1. **Start Small**: Begin with small, focused tasks before tackling larger features
2. **Verify Incrementally**: Test each component before moving to the next
3. **Provide Feedback**: Tell the AI specifically what was correct or incorrect about its suggestions
4. **Build Knowledge**: Each interaction should build the AI's understanding of your codebase

#### Effective Iteration Pattern:
1. Request a small implementation
2. Review and provide specific feedback
3. Ask for refinements based on feedback
4. Verify the implementation works
5. Move to the next component

### Effective AI Prompting Techniques

1. **Start with Architecture Review**: "Before suggesting changes, review the code architecture in `docs/guides/code-inventory.md`"
2. **Request Pattern Matching**:
- "Use the classes, functions, variable names, file locations, and patterns defined in `docs/guides/code-inventory.md`"
- "Follow the pattern used in [similar file] for this implementation"
3. **Specify Function Usage**: "Use the project's utility functions for accessing configuration"
4. **Request Verification**: "Explain why this approach maintains consistency with the existing codebase"

### Working with Larger Codebases

1. **Provide Architecture Diagrams**: For complex projects, share diagrams showing component relationships
2. **Define Component Boundaries**: Clearly state which components the AI should modify
3. **Highlight Key Files**: Identify the most important files that define project patterns
4. **Use Consistent Terminology**: Establish clear terms for your architecture components
5. **Create Focused Tasks**: Break large changes into smaller, focused modifications

For very large projects, consider creating a simplified "map" of the codebase that highlights:
- Core components and their responsibilities
- Data flow between components
- Extension points and plugin architecture
- Configuration management approach

### Documentation Expectations

When requesting new features or changes, specify documentation requirements:
1. **Function Documentation**: PHPDoc blocks for all functions and methods
2. **Usage Examples**: Code examples for complex functionality
3. **Architecture Impacts**: Notes on how changes affect the overall architecture
4. **Test Coverage**: Documentation of test cases and coverage

Example prompt: "Please implement this feature and include complete PHPDoc documentation and a usage example."

### When to Redirect the AI

If you notice the AI is:
1. Using direct PHP functions instead of project utilities
2. Changing function signatures without discussion
3. Introducing inconsistent patterns
4. Not following established error handling

Immediately redirect with: "Please review the existing patterns in [file/function] and revise your approach to maintain consistency."
