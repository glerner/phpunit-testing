# Naming Conventions and Autoloading

## Balancing WordPress Naming Conventions with PSR-4 Autoloading

This guide addresses the balance between WordPress naming conventions and PSR-4 autoloading compatibility. It provides clear guidelines for naming files and classes in a way that satisfies both WordPress coding standards and PSR-4 autoloading requirements.

## Current Naming Conventions

Our project uses the following naming conventions:

1. **Class Files**: WordPress-style with `class-` prefix and kebab-case (e.g., `class-journey-questions-model.php`)
2. **Test Files**: kebab-case (e.g., `test-journey-questions.php`)
3. **Base Test Classes**: Pascal_Case with underscores (e.g., `Unit_Test_Case.php`)
4. **Stub Classes**: Pascal_Case with underscores (e.g., `WP_Unit_Test_Case.php`)
5. **Directories**: kebab-case (e.g., `wp-content/`)
6. **Non-PHP Files**: kebab-case (e.g., `admin-styles.css`)

## Autoloading Configuration

Our project uses classmap autoloading as configured in `composer.json`

**Our Current Approach (Classmap):**

```json
"autoload": {
    "classmap": ["src/"],
    "exclude-from-classmap": [
        "src/Stubs/"
    ]
}
```

With classmap autoloading:

1. WordPress-style file naming conventions can be used (e.g., `class-my-class.php`)
2. The class name inside the file must still match what you use in your code
3. The namespace declaration must be correct and consistent

**Traditional PSR-4 Approach (For Reference):**

```json
"autoload": {
    "psr-4": {
        "WP_PHPUnit_Framework\\": "src/"
    }
}
```

With PSR-4 autoloading:

1. The namespace must match the directory structure
2. The class name must match the file name exactly (case-sensitive)
3. File naming would need to follow PSR-4 conventions (e.g., `My_Class.php` for a class named `My_Class`)

## WordPress vs. PSR-4 Naming Conventions

### WordPress Conventions

- **Files**: lowercase-with-hyphens (e.g., `class-my-class.php`)
- **Classes**: Pascal_Case with underscores (e.g., `My_Class`)
- **Functions/Methods**: snake_case (e.g., `get_post()`)
- **Hooks/Filters**: snake_case (e.g., `the_content`)

### PSR-4 Conventions

- **Files**: Match class name exactly (e.g., `MyClass.php` or `My_Class.php`)
- **Namespaces**: Match directory structure (e.g., `Vendor\Package\SubNamespace`)
- **Classes**: Typically PascalCase without underscores (e.g., `MyClass`)

## Our Hybrid Approach

Our project uses a hybrid approach that satisfies both WordPress conventions and PSR-4 requirements:

### For Autoloaded Classes (in `src/`)

- Use WordPress-style file naming with `class-` prefix and kebab-case for filenames
- Use Pascal_Case with underscores for class names
- Example: `class Journey_Questions_Model` in `class-journey-questions-model.php`

This approach:
- Works with classmap autoloading (which doesn't require filename to match class name)
- Follows WordPress file naming conventions (kebab-case with class- prefix)
- Follows WordPress class naming preferences (Pascal_Case with underscores)
- Maintains consistency with WordPress coding standards

### For Test Files (in `tests/`)

- Use WordPress kebab-case naming (`test-feature-name.php`)
- These aren't autoloaded by PSR-4, so they can follow WordPress conventions
- Test class names still use Pascal_Case with underscores (e.g., `Test_Feature_Name`)

### For Stubs and Special Classes

- Since we've excluded `src/Stubs/` from the classmap, we have flexibility here
- We maintain Pascal_Case with underscores for consistency

## Common Pitfalls to Avoid

1. **Inconsistent Case**: PSR-4 autoloading is case-sensitive. Ensure the filename matches the class name exactly.

   ```php
   // Correct:
   // File: My_Class.php
   class My_Class {}

   // Incorrect:
   // File: my-class.php or My-Class.php
   class My_Class {} // Autoloading will fail
   ```

2. **Missing Autoloader in Scripts**: Files in the `bin/` directory (or any directory outside of `src/`) need to explicitly include the Composer autoloader to use PSR-4 autoloaded classes.

   ```php
   // Files in bin/ need to include the Composer autoloader to enable PSR-4 class autoloading
   require_once __DIR__ . '/../vendor/autoload.php';

   // Now you can use autoloaded classes
   use WP_PHPUnit_Framework\Service\Database_Connection_Manager;
   ```

3. **Mismatch Between Namespace and Directory Structure**: Ensure your namespace hierarchy matches your directory structure.

   ```php
   // If your class is in src/Service/Database/
   // Correct:
   namespace WP_PHPUnit_Framework\Service\Database;

   // Incorrect:
   namespace WP_PHPUnit_Framework\Database; // Autoloading will fail
   ```

4. **Namespace and Class Name Mismatch**: Ensure your class name in the code matches the class name you use when instantiating or referencing it.

   ```php
   // File: class-my-service.php

   // Correct:
   namespace My_Plugin\Service;
   class My_Service {} // Class name matches usage

   // Incorrect:
   namespace My_Plugin\Service;
   class My_Custom_Service {} // Should use My_Service for class-my-service.php
   ```

## Why This Approach Works

This hybrid approach offers several benefits:

1. **Compatibility**: It works with WordPress naming standards while still providing autoloading through Composer's classmap functionality
2. **Consistency**: It maintains a consistent style across the codebase.
3. **Simplicity**: It's easy to remember and apply.
4. **Integration**: It allows seamless integration with WordPress plugins and themes.

## Quick Reference

| Type | Location | Naming Convention | Example |
|------|----------|-------------------|---------|
| **Class Files** | `src/` | WordPress-style with `class-` prefix and kebab-case | `class-journey-questions-model.php` for class `Journey_Questions_Model` |
| **Test Files** | `tests/` | kebab-case | `test-journey-questions.php` |
| **Test Classes** | Inside test files | Pascal_Case with `Test_` prefix | `Test_Journey_Questions` |
| **Directories** | Everywhere | kebab-case | `wp-content/` |
| **Non-PHP Files** | Everywhere | kebab-case | `admin-styles.css` |

## Workarounds for PSR-4-Style File Naming

If you prefer to use PSR-4 style file naming (where filenames match class names exactly), simply use PSR-4 autoloading in your composer.json:

```json
"autoload": {
    "psr-4": {
        "WP_PHPUnit_Framework\\": "src/"
    }
}
```

With this configuration:
1. Files must be named to match their class names exactly (e.g., `Journey_Questions_Model.php` for class `Journey_Questions_Model`)
2. Directories must match the namespace structure
3. No need for additional workarounds - PSR-4 autoloading handles everything

## Workarounds for WordPress-Style File Naming

If you have an existing codebase with WordPress-style file naming conventions (e.g., `class-my-service.php`), here are several approaches to make it work with PSR-4 autoloading without renaming all your files:

### Option 1: Classmap Autoloading (Recommended)

Use Composer's classmap autoloading instead of PSR-4. This approach maps class names directly to file paths regardless of naming convention:

```json
"autoload": {
    "classmap": ["src/"]
}
```

When you run `composer dump-autoload`, Composer scans all PHP files in the specified directories, parses them to find class, interface, and trait declarations, and creates a map between these class names and their file locations. This process:

1. Automatically detects all class names in each file
2. Creates a mapping from fully qualified class name to file path
3. Works regardless of file naming conventions

**Important notes about class names:**

- The class name inside the file must still match exactly what you use in your code
- The namespace declaration must be correct and consistent
- Each file should contain only one class (best practice)
- Class names should follow WordPress conventions (e.g., `My_Service` with underscores)

Example of a properly formatted class in a WordPress-style file:

```php
// File: src/Service/class-my-service.php

namespace My_Plugin\Service;

class My_Service {
    // Class implementation
}
```

To use this class elsewhere:

```php
use My_Plugin\Service\My_Service;

$service = new My_Service();
```

After updating your `composer.json`, run `composer dump-autoload` to regenerate the autoloader.

### Option 2: Custom Autoloader

Create a custom autoloader that handles the WordPress naming convention. This can be registered alongside the PSR-4 autoloader:

```php
spl_autoload_register(function ($class) {
    // Convert namespace separators to directory separators
    $class_path = str_replace('\\', '/', $class);

    // Extract the class name from the fully qualified class name
    $class_parts = explode('/', $class_path);
    $class_name = end($class_parts);

    // Convert class name to WordPress file naming convention
    $file_name = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';

    // Reconstruct the path with the WordPress file naming convention
    $class_parts[count($class_parts) - 1] = $file_name;
    $file_path = __DIR__ . '/src/' . implode('/', $class_parts);

    if (file_exists($file_path)) {
        require_once $file_path;
    }
});
```

Add this to your bootstrap file or register it in your `composer.json`.

### Option 3: File Aliases or Symlinks

Create file aliases or symlinks with PSR-4 compliant names that point to your WordPress-style files. For example:

```bash
# Create a symlink from PSR-4 name to WordPress name
ln -s src/Service/class-my-service.php src/Service/My_Service.php
```

This allows you to maintain your existing files while providing PSR-4 compatible entry points.

### Option 4: Gradual Migration

Adopt a gradual migration approach:

1. Use classmap autoloading temporarily
2. Rename files to PSR-4 convention one by one as you work on them
3. Once all files are renamed, switch to PSR-4 autoloading

This minimizes disruption while moving toward PSR-4 compliance over time.

## Conclusion

By following these guidelines, we maintain a codebase that is both PSR-4 compliant and consistent with WordPress coding standards. This approach ensures that our code is easily maintainable, properly autoloaded, and follows familiar WordPress conventions where possible.
