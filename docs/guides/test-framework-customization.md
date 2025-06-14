# Test Framework Customization Guide

## Understanding the Test Framework Architecture

### Framework Code vs Framework Tests

- **Framework Code** (`src/`):
  - Contains production code that users extend/use
  - Autoloaded via `autoload` in composer.json
  - Example: `WP_PHPUnit_Framework\Unit\Unit_Test_Case`

- **Framework Tests** (`tests/Framework/`):
  - Tests for the framework itself
  - Autoloaded via `autoload-dev` in composer.json
  - Not included when users require the framework

### Plugin Tests

Your plugin's tests should be in:
- `yourplugin/tests/unit/` - For unit tests
- `yourplugin/tests/wp-mock/` - For WP_Mock tests
- `yourplugin/tests/integration/` - For integration tests

## For Plugin Developers (Most Common Case)

### Using the Test Framework
- The test framework is included as a read-only submodule
- Test files can be named freely, but should follow project conventions
- Test classes must use the documented namespaces and extend the appropriate base classes
- All test files should be placed in the appropriate test directory:
  - `yourplugin/tests/unit/` for isolated tests
  - `yourplugin/tests/wp-mock/` for WordPress function mocks
  - `yourplugin/tests/integration/` for WordPress integration tests

### When You Need Framework Changes
If you find yourself needing to modify the test framework itself, please:
1. Open an issue in the framework's repository to discuss the change
2. Consider if your needs could be met through existing extension points
3. If a framework change is truly needed, follow the contribution guidelines below

## For Framework Contributors

### Setting Up for Development
1. Clone the framework repository separately (not inside your plugin's folders):
   ```bash
   cd ~/your-sites-folder
   git clone https://github.com/your-org/gl-phpunit-test-framework.git
   cd gl-phpunit-test-framework
   ```

2. Make your changes and test them locally
3. Submit a pull request with your changes

### Testing Framework Changes
1. In your plugin, update the submodule to point to your framework branch:
   ```bash
   cd yourplugin/tests/gl-phpunit-test-framework
   git fetch origin your-branch-name
   git checkout your-branch-name
   cd ../..
   git add tests/gl-phpunit-test-framework
   git commit -m "Test with framework changes from your-branch-name"
   ```

## For Custom Framework Modifications (Advanced)

### Forking the Framework
If you need custom modifications that aren't suitable for upstream:

1. Fork the framework repository
2. Make your custom changes in your fork
3. Update your project to use your fork:
   ```bash
   # Remove the existing submodule
   git submodule deinit -f tests/gl-phpunit-test-framework
   rm -rf .git/modules/tests/gl-phpunit-test-framework
   git rm -f tests/gl-phpunit-test-framework

   # Add your fork as a new submodule
   git submodule add https://github.com/your-username/gl-phpunit-test-framework.git tests/gl-phpunit-test-framework
   ```

### Maintaining Custom Forks
- Keep your fork in sync with upstream:
  ```bash
  cd yourplugin/tests/gl-phpunit-test-framework
  git remote add upstream https://github.com/original-org/gl-phpunit-test-framework.git
  git fetch upstream
  git merge upstream/main  # or the appropriate branch
  ```
- Document your custom changes in a CHANGELOG.md in your fork
- Consider contributing generally useful changes back upstream

## Best Practices

1. **Avoid Framework Modifications** when possible
   - Most testing needs can be met by writing tests within your plugin
   - Use the framework's extension points before considering modifications

2. **Document Customizations**
   - Keep a record of why custom changes were necessary
   - Document any compatibility considerations

3. **Stay Updated**
   - Regularly sync with the upstream framework
   - Review and test updates before applying them to production projects

## Troubleshooting

### "Permission Denied" Errors
If you encounter permission issues after making the framework read-only:
```bash
# Make writable temporarily
chmod -R +w tests/gl-phpunit-test-framework

# After making necessary changes, make read-only again
chmod -R a-w tests/gl-phpunit-test-framework
```

### Recovering from Accidental Changes
If you've accidentally modified the framework:
```bash
cd yourplugin/tests/gl-phpunit-test-framework
git reset --hard HEAD
git clean -fd
```
