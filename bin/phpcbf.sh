#!/bin/bash

# Run PHPCBF with practical exclusions that focus on functional issues
# rather than minor formatting concerns
# Alternative to `"phpcbf": "composer run-script spaces_to_tabs && phpcbf --standard=phpcs.xml.dist", ` in composer.json

# Usage:
#   ./bin/phpcbf.sh [options] [<file>...]
#
# Examples:
#   ./bin/phpcbf.sh                          # Run on all files
#   ./bin/phpcbf.sh src/Integration/         # Run on specific directory
#   ./bin/phpcbf.sh --report-file=report.txt # Save report to file
#   ./bin/phpcbf.sh -v                       # Verbose output

# Excluded rules:
# - Squiz.Commenting.InlineComment: Comment formatting rules
# - PEAR.Functions.FunctionCallSignature: Spacing in function calls
# - Generic.Formatting.MultipleStatementAlignment: Alignment of assignments
# - WordPress.Arrays.ArrayIndentation: Array indentation rules
# - WordPress.WhiteSpace.OperatorSpacing: Spacing around operators
# - WordPress.WhiteSpace.ControlStructureSpacing: Spacing in control structures
# - WordPress.PHP.YodaConditions: Requiring Yoda conditions

# Get directory of this script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Change to project root
cd "$PROJECT_ROOT" || exit 1

# Default options
OPTIONS="-s --no-colors"

# First convert spaces to tabs
composer run-script spaces_to_tabs

# Run PHPCBF with exclusions
composer run-script phpcbf -- $OPTIONS \
  --exclude=Squiz.Commenting.InlineComment,\
PEAR.Functions.FunctionCallSignature,\
Generic.Formatting.MultipleStatementAlignment,\
WordPress.Arrays.ArrayIndentation,\
WordPress.WhiteSpace.OperatorSpacing,\
WordPress.WhiteSpace.ControlStructureSpacing,\
WordPress.PHP.YodaConditions \
"$@"

# Return the exit code from PHPCBF
exit $?
