# Uninstalling the PHPUnit Testing Framework Submodule

This guide explains how to completely remove the `gl-phpunit-test-framework` Git submodule from your project. These steps ensure all traces of the submodule are removed from your repository and working directory.

## Steps to Remove the Submodule

1. **Delete the submodule entry from `.gitmodules`:**

   Open `.gitmodules` and remove the section corresponding to the submodule (`tests/gl-phpunit-test-framework`).

2. **Remove the submodule from the Git index:**

   ```sh
   git rm --cached tests/gl-phpunit-test-framework
   ```

   This removes the submodule from your repository (staging the removal) but keeps the actual directory and files on disk.

3. **Delete the submodule directory from your working tree:**

   ```sh
   rm -rf tests/gl-phpunit-test-framework
   ```

4. **Remove the submodule configuration from `.git/config`:**

   Open `.git/config` and remove the section related to `submodule.tests/gl-phpunit-test-framework`.

5. **Commit the changes:**

   ```sh
   git add .gitmodules
   git commit -m "Remove gl-phpunit-test-framework submodule"
   ```

6. **(Optional) Remove leftover submodule data from `.git/modules`:**

   ```sh
   rm -rf .git/modules/tests/gl-phpunit-test-framework
   ```
