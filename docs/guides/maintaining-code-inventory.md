# Maintaining Code Inventory Documentation

This guide explains the recommended workflow for keeping the code-inventory.md file up to date as the framework evolves.

## Why Code Inventory Matters

The `docs/analysis/code-inventory.md` file serves several critical purposes:

1. **Documentation for Developers**: Provides a comprehensive reference of functions, classes, and components
2. **Onboarding Resource**: Helps new contributors understand the codebase structure
3. **AI Assistant Reference**: Enables AI tools to provide more accurate assistance by understanding existing code patterns and naming conventions
4. **Architecture Overview**: Shows relationships between components and their dependencies

## Documentation Branch Workflow

To maintain clean separation between code changes and documentation updates, we use a dedicated documentation branch approach:

```bash
# 1. Create a documentation branch
# git status   will now show:
# On branch docs/update-code-inventory
git checkout -b docs/update-code-inventory

# 2. See what code changes need documenting
# Note: changes.diff is in .gitignore
git diff main -- includes/ bin/ tests/ src/ docs/ composer.json phpcs.xml.dist phpstan.neon > changes.diff

# 3. Review changes and update code-inventory.md
# Look for:
# - New functions/classes/methods
# - Changed function signatures
# - Renamed components
# - Modified relationships between components

# 4. Commit your documentation updates
# Include both the code-inventory.md file and any related documentation
git add docs/analysis/code-inventory.md docs/guides/maintaining-code-inventory.md
git commit -m "docs: update code-inventory with new components and add maintenance guide"

# 5. Switch back to main and merge the documentation
git checkout main
git merge docs/update-code-inventory

# 6. Optional: Delete the documentation branch if you're done with it
git branch -d docs/update-code-inventory
```

## When to Update Code Inventory

Update the code inventory after:

1. **Feature additions** - When you add new functions, classes, or components
   - Example: "feat: Add comprehensive example tests for all test types"

2. **Refactoring** - When you change how components interact or rename things
   - Example: "refactor: improve test isolation and environment handling"

3. **Architectural changes** - When you modify how systems work together
   - Example: "refactor: standardize on WP_PHPUnit_Framework namespace"

You don't need to update after every small fix or documentation change. Focus on commits that change the code structure or add new components.

## Using AI to Help Update Documentation

AI assistants can help analyze code changes and suggest documentation updates:

1. After generating changes.diff, open it in your AI Editor
2. Ask: "Review changes.diff and make updates to code-inventory.md. Please identify new functions, component relationships, parameter types, and return values that should be documented."

This approach combines the efficiency of AI with your knowledge of the codebase to maintain accurate documentation.

## Finding Specific Commits

If you need to find when a specific part of the documentation was last updated:

```bash
# Find when code-inventory.md was last modified
git log --oneline -- docs/analysis/code-inventory.md

# Compare changes between a specific commit and now
# For example, if 2050a69 was your last docs update:
git diff 2050a69 HEAD -- includes/ bin/ tests/ src/ docs/ composer.json phpcs.xml.dist phpstan.neon > changes.diff
```

## Future Improvements

In the future, we may implement:

1. **Automated Documentation Generation**: Scripts to extract function signatures and comments
2. **Documentation Testing**: Verify that all public APIs are documented
3. **Change Detection**: Automatically identify code changes that need documentation updates

Until then, this manual process with AI assistance provides a practical approach to maintaining comprehensive documentation.
