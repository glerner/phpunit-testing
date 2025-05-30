# Memory ID: 611d425f-ebd5-480e-86a1-6222c696b1b6
# Title: Code Inventory, and Code Quality Standards

1. Always refer to `docs/guides/code-inventory.md` for classes, functions, variables, constants, files, and directories. No writing duplicate code.
2. Always follow WordPress Coding Standards for core functionality and structure (exception use external libraries names)
3. Write code so it passes PHPCS and PHPStan Level 5 code quality checks. Can ignore purely cosmetic rules. Are specific PHPCS overrides in the phpcs.xml file for the project.
4. When reminded, `read docs/guides/programming-with-ai.md` and `docs/guides/debugging-with-ai.md`

These standards ensure maintainable, testable code that follows WordPress conventions while leveraging modern PHP features

PHPStan strictness levels 0-8:
- 0: Basic checks
- 3: Good starting point for new projects
- 5: Recommended for mature projects
- 8: Maximum
