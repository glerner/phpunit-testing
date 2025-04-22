# Contributing to GL WordPress PHPUnit Testing Framework

Thank you for your interest in contributing to the GL WordPress PHPUnit Testing Framework! This document provides guidelines and instructions for contributing to this project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Pull Request Process](#pull-request-process)

## Code of Conduct

This project adheres to a Code of Conduct that all contributors are expected to follow. By participating, you are expected to uphold this code.

## Getting Started

1. **Fork the repository** on GitHub
2. **Clone your fork** locally
3. **Install dependencies** using Composer:
   ```bash
   composer install
   ```
4. **Create a branch** for your feature or bug fix:
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Development Workflow

1. Make your changes in your feature branch
2. Add or update tests as necessary
3. Ensure all tests pass
4. Update documentation if needed
5. Submit a pull request

## Coding Standards

This project follows PSR-12 coding standards and WordPress coding standards where appropriate.

### PHP Coding Standards

- Use strict typing with `declare(strict_types=1);`
- Follow PSR-4 autoloading standards
- Use proper namespacing
- Include comprehensive PHPDoc comments
- Use type hints and return type declarations

### Fixing Coding Standards Issues

We use PHP_CodeSniffer to enforce coding standards. You can check and fix coding standards issues with:

```bash
# Check coding standards
composer run-script phpcs

# Fix coding standards issues automatically where possible
composer run-script phpcbf
```

Common issues to watch for:
- Indentation (4 spaces, not tabs)
- Line length (generally 100 characters max)
- Proper spacing around operators
- Proper docblock formatting
- Naming conventions

## Testing

All new code should include appropriate tests:

```bash
# Run all tests
composer run-script test

# Run only unit tests
composer run-script test:unit

# Run only integration tests
composer run-script test:integration
```

## Documentation

Please update documentation when adding or modifying features:

1. Update relevant sections in `/docs/guides/`
2. Add examples where appropriate
3. Ensure PHPDoc comments are comprehensive and accurate

## Pull Request Process

1. Update the README.md or documentation with details of changes if appropriate
2. Update the CHANGELOG.md file with details of changes
3. The PR should work on PHP 7.4 and above
4. Ensure all tests pass and coding standards are met
5. Your PR will be reviewed by maintainers, who may request changes

## Questions?

If you have any questions about contributing, please open an issue or reach out to the maintainers.

Thank you for contributing to the GL WordPress PHPUnit Testing Framework!
