# Installation Guide Update

**Date**: 2025-06-27  
**Status**: Completed  
**Files Modified**:  
- /home/george/sites/phpunit-testing/docs/guides/installation-guide.md

## Original Task
Update installation-guide.md for accuracy and consistency by referencing composer-update-sequence.md instead of duplicating instructions and removing obsolete composer test references.

## Completed Steps
- [x] Read and understand the current installation-guide.md
- [x] Search for and list all references to `composer test` and related scripts
- [x] Search for and list all Composer update instructions that should reference composer-update-sequence.md
- [x] Update all sections with references to composer-update-sequence.md (lines: 116, 130, 138, 229, 269, 331, 336, 853)
- [x] Remove all references to `composer test` commands (lines: 857-860)

## Summary of Changes
- Added references to composer-update-sequence.md in all sections mentioning Composer updates
- Removed obsolete references to composer test scripts
- Improved document flow and consistency

## Implementation Details
1. Added note references to the Composer Update Workflow guide in the Lando section
2. Updated the Local section to reference the composer-update-sequence.md guide
3. Updated the XAMPP/MAMP/WAMP section to reference the composer-update-sequence.md guide
4. Added reference to the composer-update-sequence.md guide in the framework update section
5. Removed all references to obsolete `composer test` commands and replaced with instructions to use the sync-and-test.php script
6. Improved document flow by removing unnecessary "or" text after removing composer test commands
