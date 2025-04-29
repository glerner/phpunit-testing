# PHP_CodeSniffer and PHP Code Beautifier Guide

This document provides detailed information about using PHP_CodeSniffer (PHPCS) and PHP Code Beautifier and Fixer (PHPCBF) with the GL WordPress PHPUnit Testing Framework.

## Basic Usage

### PHPCS (PHP_CodeSniffer)

PHPCS detects violations of coding standards in your PHP code:

```bash
# Basic usage
composer run-script phpcs

# Check a specific file
composer run-script phpcs -- path/to/file.php

# Show sniff codes (helpful for identifying specific rules)
composer run-script phpcs -- -s

# Generate a summary report
composer run-script phpcs -- --report=summary

# Generate documentation of all rules
composer run-script phpcs -- -s --generator=markdown --report-file=docs/analysis/phpcs-rules-described.md
```

### PHPCBF (PHP Code Beautifier and Fixer)

PHPCBF automatically fixes many of the issues detected by PHPCS:

```bash
# Basic usage
composer run-script phpcbf

# Fix a specific file
composer run-script phpcbf -- path/to/file.php

# Verbose output
composer run-script phpcbf -- -v

# List installed standards
composer run-script phpcbf -- -i
```

## Configuration

The project uses a customized configuration in `phpcs.xml.dist` that:

1. Follows WordPress Coding Standards with some practical exclusions
2. Includes PSR-12 standards (except for indentation rules that conflict with WordPress)
3. Requires PHP 8.0+ and WordPress 6.1+
4. Enforces type hints through Slevomat Coding Standard

### Excluded Rules

The project excludes certain formatting rules that don't affect functionality:

- `Squiz.Commenting.InlineComment.InvalidEndChar`: Comments don't need to end with a period
- `PEAR.Functions.FunctionCallSignature.SpaceAfterOpenBracket`: Spacing inside function parentheses
- `Generic.Formatting.MultipleStatementAlignment`: Exact alignment of multiple assignments
- `WordPress.Arrays.ArrayIndentation`: Precise array indentation
- `WordPress.WhiteSpace.OperatorSpacing`: Spacing around operators

These exclusions focus the code quality tools on catching actual bugs rather than minor formatting issues.

## Troubleshooting

### PHPCBF Limitations

- **50-Pass Limit**: PHPCBF has a built-in limit of 50 passes per file. If it can't fix all issues within 50 passes, it will show an ERROR and won't save any changes to that file.

- **"FAILED TO FIX" Message**: When PHPCBF shows this message, it may have fixed some issues but not all of them. The error code 2 is normal when PHPCBF fixes some issues but can't fix everything.

- **Report File Option**: The `--report-file` option doesn't work with PHPCBF (use it with PHPCS instead). For PHPCBF, redirect output to a file:
  ```bash
  composer run-script phpcbf -- -s -v > phpcbf-output.txt
  ```

### Viewing Loaded Rules and Exclusions

To see which rules are loaded and which are excluded, use the `-vv` (very verbose) flag with PHPCS:

```bash
composer run-script phpcs -- -vv path/to/file.php
```

This will show you all registered sniffs, excluded sniffs, and processing details.

### Strategies for Stubborn Files

1. **Run PHPCBF Multiple Times**: Each run may fix different issues.

2. **Focus on One File at a Time**:
   ```bash
   composer run-script phpcbf -- path/to/specific/file.php
   ```

3. **Convert Spaces to Tabs First**:
   ```bash
   composer run-script spaces_to_tabs
   ```
   Then run PHPCBF after spaces are converted to tabs.

4. **Exclude Problematic Rules on the Command Line**:
   ```bash
   composer run-script phpcbf -- --exclude=Generic.WhiteSpace,PEAR.Functions path/to/file.php
   ```
   This can help bypass rules that might be causing PHPCBF to get stuck in a loop.

5. **Identify Specific Issues with PHPCS First**:
   ```bash
   composer run-script phpcs -- -s path/to/file.php
   ```
   Then focus on fixing the most common or problematic issues manually.

6. **Manually Fix Critical Issues**: Sometimes manual intervention is needed for complex issues.

### Indentation Issues (Tabs vs. Spaces)

WordPress Coding Standards require tabs for indentation, while PSR-12 requires spaces. This creates a conflict that can cause PHPCBF to get stuck in a loop. Our configuration resolves this by:

1. Excluding the PSR-12 rule against tabs: `<exclude name="Generic.WhiteSpace.DisallowTabIndent"/>`
2. Excluding the WordPress rule against spaces: `<exclude name="Generic.WhiteSpace.DisallowSpaceIndent"/>`
3. Providing a `spaces_to_tabs` Composer script for manual conversion when needed

**Critical Note**: Without excluding both rules, PHPCBF may get stuck in a loop trying to convert spaces to tabs and hit the 50-pass limit, resulting in no changes being saved.

### PHP 8.0+ Compatibility

The project requires PHP 8.0+ to enable modern type hints, including the `mixed` type. If you encounter type hint errors, ensure your code uses appropriate type declarations.

## Advanced Usage

### Using Different Standards

List all available standards:
```bash
composer run-script phpcs -- -i
```

The installed standards include: MySource, PEAR, PSR1, PSR2, PSR12, Squiz, Zend, PHPCompatibility, WordPress, WordPress-Core, WordPress-Docs, and WordPress-Extra.

### Excluding Specific Sniffs

You can temporarily exclude specific sniffs on the command line:
```bash
composer run-script phpcs -- --exclude=Squiz.Commenting.InlineComment
```

### Creating a Baseline

For existing projects with many violations, consider creating a baseline that ignores current violations but enforces standards for new code:
```bash
composer run-script phpcs -- --report=json > phpcs-baseline.json
```

## References

- [PHP_CodeSniffer Documentation](https://github.com/squizlabs/PHP_CodeSniffer/wiki)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [PSR-12 Coding Standards](https://www.php-fig.org/psr/psr-12/)
