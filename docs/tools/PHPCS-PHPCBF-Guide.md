# PHP_CodeSniffer and PHP Code Beautifier Guide

This document provides detailed information about using PHP_CodeSniffer (PHPCS) and PHP Code Beautifier and Fixer (PHPCBF) with the GL WordPress PHPUnit Testing Framework.

## Basic Usage

### PHPCS (PHP_CodeSniffer)

PHPCS detects violations of coding standards in your PHP code:

```bash
# Basic usage, check PHP files in your project (excludes vendor/, .git/, and other paths in .gitignore)
composer run-script phpcs

# Check a specific file
composer run-script phpcs -- path/to/file.php

# Show sniff codes (helpful for identifying specific rules)
composer run-script phpcs -- -s

# Generate a summary report
composer run-script phpcs -- --report=summary

# Generate documentation of all rules
composer run-script phpcs -- -s --generator=markdown > docs/analysis/phpcs-rules-described.md
```

### PHPCBF (PHP Code Beautifier and Fixer)

PHPCBF automatically fixes many of the issues detected by PHPCS, so often will run it before PHPCS:

```bash
# Basic usage, beautify/fix PHP files in your project (excludes vendor/, .git/, and other paths in .gitignore)
composer run-script phpcbf

# Fix a specific file
composer run-script phpcbf -- path/to/file.php

# Verbose output
composer run-script phpcbf -- -v

# List installed standards
composer run-script phpcbf -- -i
```

## Configuration for Both PHPCS and PHPCBF

Both are actually part of one library, and use the same configuration file and (mostly) the same command line parameters.

This project uses a customized configuration file for them, in `phpcs.xml.dist` that:

1. Follows WordPress Coding Standards with some practical exclusions
2. Includes PSR-12 standards (except for indentation rules that conflict with WordPress standards)
3. Requires PHP 8.0+ and WordPress 6.1+
4. Enforces type hints through Slevomat Coding Standard

### Excluded Rules

This project excludes certain formatting rules that don't affect functionality:

- `Squiz.Commenting.InlineComment.InvalidEndChar`: Rule says Comments need to end with a period
- `PEAR.Functions.FunctionCallSignature.SpaceAfterOpenBracket`: Spacing inside function parentheses
- `Generic.Formatting.MultipleStatementAlignment`: Exact alignment of multiple assignments
- `WordPress.Arrays.ArrayIndentation`: Precise array indentation
- `WordPress.WhiteSpace.OperatorSpacing`: Spacing around operators

These exclusions focus the code quality tools on catching actual bugs. They exclude minor formatting issues, that WordPress plugin developers don't need to be concerned about, and would require manually editing a lot of code. Still, is a good coding practice to write new code following these formatting standards.

## Troubleshooting

### PHPCBF Limitations

- **50-Pass Limit**: PHPCBF has a built-in limit of 50 passes per file. If it can't fix all issues within 50 passes, it will show an ERROR and *won't save* any changes to that file.

- **"FAILED TO FIX" Message**: When PHPCBF shows this message, it may have fixed some issues but not all of them. The error code 2 is normal when PHPCBF fixes some issues but can't fix everything.

- **Report File Option**: The `--report-file` option doesn't work with PHPCBF (use it with PHPCS instead). For PHPCBF, redirect output to a file:
  ```bash
  composer run-script phpcbf -- -s -v > phpcbf-output.txt
  ```

### Viewing Loaded Rules and Exclusions

To see which rules are loaded and which are excluded, use the `-vv` (very verbose) flag with PHPCS:

```bash
# path/to/file.php is the file to check
composer run-script phpcs -- -vv path/to/file.php
```

This will show you all registered sniffs, excluded sniffs, and processing details. You can filter this output to see just what you need:

```bash
# Show only excluded sniffs
composer run-script phpcs -- -vv path/to/file.php | grep "Excluding"

# Show only registered sniffs
composer run-script phpcs -- -vv path/to/file.php | grep "Registered sniff"
```

### Strategies for Stubborn Files

1. **Convert Spaces to Tabs First**:
   ```bash
   composer run-script spaces_to_tabs
   ```
   This direct approach is significantly faster than using PHPCBF for indentation conversion. PHPCBF can fail to edit files with too many indentation issues, as it tries to make multiple passes and can get overwhelmed by the number of changes needed.

   "spaces_to_tabs" is much easier to read than the Bash command it runs:
   ```bash
   find src tests templates -name \"*.php\" -type f -exec sed -i 's/^    /\\t/g' {} \\;
   ```

2. **Identify and Exclude Problematic Rules** (see detailed section below):
   ```bash
   composer run-script phpcbf -- --exclude=Rule.Category.Sniff path/to/file.php
   ```

3. **Focus on One File at a Time**:
   ```bash
   composer run-script phpcbf -- path/to/specific/file.php
   ```

4. **Run PHPCBF Multiple Times**: Each run may fix different issues.

### Identifying Rules to Exclude

When PHPCBF gets stuck in a loop or can't fix certain issues, you may need to identify specific rules to exclude. Here's a systematic approach to identify problematic rules:

1. **Run PHPCS with sniff codes** (the -s option) to see which violations are being reported:
   ```bash
   composer run-script phpcs -- -s path/to/problem/file.php
   ```

2. **Run PHPCBF with verbose output** (the -vv option) to see which rules it's processing and where it's getting stuck:
   ```bash
   composer run-script phpcbf -- -vv path/to/problem/file.php > phpcbf-debug.txt
   ```

3. **Analyze the output** to identify patterns:
   - Look for rules that appear repeatedly in the output
   - Pay attention to rules being processed when PHPCBF hits the 50-pass limit
   - Note any rules that cause conflicts (e.g., spaces vs. tabs indentation)

   **Example output** showing PHPCBF stuck in a loop:
   ```
   => Fixing file: 32/33 violations remaining [made 50 passes]... ERROR in 3.33 secs
   Processing rule "WordPress.WhiteSpace.ControlStructureSpacing"
   Processing rule "WordPress.Arrays.ArrayDeclarationSpacingSniff.php"
   Processing rule "PEAR.Functions.FunctionCallSignature"
   ```

   This output shows PHPCBF hit the 50-pass limit while processing these specific rules. They are good candidates for exclusion.

   You might have to add exclusions and run PHPCBF again to find other rules to exclude.

4. **Understanding Rule Formats**:
   - **Sniff Code (3-part)**: `Standard.Category.Sniff` - Used with command line `--exclude`
   - **Message Code (4-part)**: `Standard.Category.Sniff.MessageCode` - Used in XML configuration

   When using `--exclude` on the command line, you can only exclude at the sniff level (3-part).

5. **Test excluding one rule at a time** using the command line:
   ```bash
   # First try excluding one rule
   composer run-script phpcbf -- --exclude=WordPress.WhiteSpace.ControlStructureSpacing bin/setup-plugin-tests.php

   # If still stuck, try excluding multiple rules based on the example output
   composer run-script phpcbf -- --exclude=WordPress.WhiteSpace.ControlStructureSpacing,PEAR.Functions.FunctionCallSignature,WordPress.Arrays.ArrayDeclaration bin/setup-plugin-tests.php
   ```

   Note: For `WordPress.Arrays.ArrayDeclarationSpacingSniff.php` seen in the output, we use `WordPress.Arrays.ArrayDeclaration` (the 3-part sniff code).

6. **Build up a list of problematic rules** by testing which exclusions allow PHPCBF to complete successfully

Once you've identified the problematic rules, you can add them to your phpcs.xml.dist file within the appropriate `<rule ref>` block:

```xml
<rule ref="WordPress">
    <!-- Exclude WordPress-specific rules that don't apply -->
    <exclude name="WordPress.Files.FileName"/>

    <!-- Exclude problematic formatting rules identified from our output -->
    <exclude name="WordPress.WhiteSpace.ControlStructureSpacing"/>
    <exclude name="WordPress.Arrays.ArrayDeclaration"/>
    <exclude name="PEAR.Functions.FunctionCallSignature"/>
</rule>
```
**Understanding Rule Formats**:
- **Sniff Code (3-part)**: `Standard.Category.Sniff` - Used with command line `--exclude` and in XML configuration
- **Message Code (4-part)**: `Standard.Category.Sniff.MessageCode` - Used only in XML configuration

When using `--exclude` on the command line, you can only exclude at the sniff level (3-part), which excludes all messages from that sniff. In the XML configuration file, you can use either format - the 3-part format excludes the entire sniff and all its messages, while the 4-part format lets you target specific message types within a sniff. Use the most specific exclusion that solves your problem.

Note: The `<exclude>` elements must be inside the specific `<rule ref>` block they apply to. Rules from different standards (WordPress, PSR12, etc.) need to be excluded within their respective rule blocks.

For this project, we identified the following rules as causing issues with PHPCBF and excluded them in our phpcs.xml.dist:

**Spacing and Indentation Rules:**
- `WordPress.WhiteSpace.OperatorSpacing`
- `WordPress.WhiteSpace.ControlStructureSpacing`
- `WordPress.Arrays.ArrayIndentation`
- `Generic.WhiteSpace.DisallowSpaceIndent` (handled by our spaces_to_tabs script instead)
- `PEAR.Functions.FunctionCallSignature.SpaceAfterOpenBracket`
- `PEAR.Functions.FunctionCallSignature.SpaceBeforeCloseBracket`

**Formatting and Alignment Rules:**
- `Generic.Formatting.MultipleStatementAlignment`
- `Squiz.Commenting.InlineComment.InvalidEndChar`
- `WordPress.PHP.YodaConditions`

These were systematically identified by running PHPCBF with different exclusions until we found the combination that allowed it to complete successfully without getting stuck in formatting loops.

4. **Exclude Problematic Rules on the Command Line**:
   ```bash
   composer run-script phpcbf -- --exclude=Generic.WhiteSpace.DisallowSpaceIndent,PEAR.Functions path/to/file.php
   ```
   This can help bypass rules that might be causing PHPCBF to get stuck in a loop. Note that multiple rules should be separated by commas with no spaces between them.

5. **Use PHPCS to Identify Specific Issues**:
   ```bash
   composer run-script phpcs -- -s path/to/file.php
   ```
   The `-s` parameter shows the sniff codes for each violation, making it easier to identify which rules to exclude. Focus on fixing the most common or problematic issues manually.

6. **Manually Fix Critical Issues**: 
   PHPCBF will indicate issues it can't fix in several ways:
   - When you see "FAILED TO FIX" messages for specific files - PHPCBF doesn't show which specific issues it couldn't fix, so run `composer run-script phpcs -- -s path/to/file.php` afterward to see the remaining issues with their sniff codes
   - When PHPCBF reports "ERROR" with "made 50 passes" (hit the maximum iteration limit) - try excluding problematic rules as described in the "Identifying Rules to Exclude" section above
   
   For these cases, you'll need to manually edit the files. The most common unfixable issues involve complex nested structures, multi-line function calls, or conflicting rule requirements.

### Indentation Issues (Whether to Use Tabs or Spaces)

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
Multiple rules should be separated by commas with no spaces between them. Remember that on the command line, you can only use the 3-part format (`Standard.Category.Sniff`).

### Creating a Baseline

For existing projects with many violations, consider creating a baseline that ignores current violations but enforces standards for new code:
```bash
composer run-script phpcs -- --report=json > phpcs-baseline.json
```

## Version Compatibility

This project uses PHP_CodeSniffer 3.7.x, which is no longer the latest version. We use this specific version because the WordPress Coding Standards package is not yet fully compatible with PHP_CodeSniffer 4.x.

## References

- [PHP_CodeSniffer Documentation](https://github.com/squizlabs/PHP_CodeSniffer/wiki)
- [PHP_CodeSniffer 3.x GitHub](https://github.com/PHPCSStandards/PHP_CodeSniffer) - The version we use for WordPress compatibility
- [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards) - Requires PHP_CodeSniffer 3.7.x
- [WordPress Coding Standards Documentation](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [PSR-12 Coding Standards](https://www.php-fig.org/psr/psr-12/)
