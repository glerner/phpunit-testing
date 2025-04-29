> composer run-script phpcs -- -s --generator=markdown --report-file=./docs/analysis/phpcbf-results.md
> phpcs --standard=phpcs.xml.dist '-s' '--generator=markdown' '--report-file=./docs/analysis/phpcbf-results.md'
# GL WordPress PHPUnit Testing Framework Coding Standard

## Function Calls to Dirname

PHP &gt;= 5.3: Usage of dirname(__FILE__) can be replaced with __DIR__.
  <table>
   <tr>
    <th>Valid: Using __DIR__.</th>
    <th>Invalid: Using dirname(__FILE__).</th>
   </tr>
   <tr>
<td>

    $path = __DIR__;

</td>
<td>

    $path = dirname(__FILE__);

</td>
   </tr>
  </table>
PHP &gt;= 7.0: Nested calls to dirname() can be replaced by using dirname() with the $levels parameter.
  <table>
   <tr>
    <th>Valid: Using dirname() with the $levels parameter.</th>
    <th>Invalid: Nested function calls to dirname().</th>
   </tr>
   <tr>
<td>

    $path = dirname($file, 3);

</td>
<td>

    $path = dirname(dirname(dirname($file)));

</td>
   </tr>
  </table>

## Array Brace Spacing

There should be no space between the &quot;array&quot; keyword and the array open brace.
  <table>
   <tr>
    <th>Valid: No space between the keyword and the open brace.</th>
    <th>Invalid: Space between the keyword and the open brace.</th>
   </tr>
   <tr>
<td>

    $args = array(1, 2);

</td>
<td>

    $args = array  (1, 2);

</td>
   </tr>
  </table>
There should be no space between the array open brace and the array close brace for an empty array.
  <table>
   <tr>
    <th>Valid: No space between the braces.</th>
    <th>Invalid: Space between the braces.</th>
   </tr>
   <tr>
<td>

    $args = array();

    $args = [];

</td>
<td>

    $args = array( );

    $args = [  ];

</td>
   </tr>
  </table>
There should be no space after the array open brace and before the array close brace in a single-line array.
  <table>
   <tr>
    <th>Valid: No space on the inside of the braces.</th>
    <th>Invalid: Space on the inside of the braces.</th>
   </tr>
   <tr>
<td>

    $args = array(1, 2);

    $args = [1, 2];

</td>
<td>

    $args = array( 1, 2 );

    $args = [  1, 2  ];

</td>
   </tr>
  </table>
There should be a new line after the array open brace and before the array close brace in a multi-line array.
  <table>
   <tr>
    <th>Valid: One new line after the open brace and before the close brace.</th>
    <th>Invalid: No new lines after the open brace and/or before the close brace.</th>
   </tr>
   <tr>
<td>

    $args = array(
        1,
        2
    );

    $args = [
        1,
        2
    ];

</td>
<td>

    $args = array(1,
        2);

    $args = [1,
        2];

</td>
   </tr>
  </table>

## Comma After Last Array Item

For single-line arrays, there should be *no* comma after the last array item.

However, for multi-line arrays, there *should* be a comma after the last array item.
  <table>
   <tr>
    <th>Valid: Single-line array without a comma after the last item.</th>
    <th>Invalid: Single-line array with a comma after the last item.</th>
   </tr>
   <tr>
<td>

    $args = array(1, 2, 3);

</td>
<td>

    $args = array(1, 2, 3, );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Multi-line array with a comma after the last item.</th>
    <th>Invalid: Multi-line array without a comma after the last item.</th>
   </tr>
   <tr>
<td>

    $args = [
        1 => 'foo',
        2 => 'bar',
    ];

</td>
<td>

    $args = [
        1 => 'foo',
        2 => 'bar'
    ];

</td>
   </tr>
  </table>

## Disallow Short Array Syntax

The array keyword must be used to define arrays.
  <table>
   <tr>
    <th>Valid: Using long form array syntax.</th>
    <th>Invalid: Using short array syntax.</th>
   </tr>
   <tr>
<td>

    $arr = array(
        'foo' => 'bar',
    );

</td>
<td>

    $arr = [
        'foo' => 'bar',
    ];

</td>
   </tr>
  </table>

## Duplicate Array Key

When a second array item with the same key is declared, it will overwrite the first.
This sniff detects when this is happening in array declarations.

**Explanation:**
- In the first invalid example, the key 'bar' is defined twice with different values (25 and 28). PHP will use the last value (28), and the earlier value (25) will be silently overwritten.
- In the second invalid example, PHP automatically assigns numeric keys starting from 0 for arrays without explicit keys. So the value 22 is at index 0, and 25 is at index 1. Then index 1 is explicitly redefined to 28, which overwrites the previous value (25).
  <table>
   <tr>
    <th>Valid: Arrays with unique keys.</th>
    <th>Invalid: Array with duplicate keys.</th>
   </tr>
   <tr>
<td>

    $args = array(
        'foo' => 22,
        'bar' => 25,
        'baz' => 28,
    );

    $args = array(
        22,
        25,
        2 => 28,
    );

</td>
<td>

    $args = array(
        'foo' => 22,
        'bar' => 25,
        'bar' => 28,
    );

    $args = array(
        22,
        25,
        1 => 28,
    );

</td>
   </tr>
  </table>

## Class Modifier Keyword Order

Requires that class modifier keywords consistently use the same keyword order.

By default the expected order is &quot;abstract/final readonly&quot;, but this can be changed via the sniff configuration.
  <table>
   <tr>
    <th>Valid: Modifier keywords ordered correctly.</th>
    <th>Invalid: Modifier keywords in reverse order.</th>
   </tr>
   <tr>
<td>

    final readonly class Foo {}
    abstract readonly class Bar {}

</td>
<td>

    readonly final class Foo {}
    readonly abstract class Bar {}

</td>
   </tr>
  </table>

## Require Anonymous Class Parentheses

Require the use of parentheses when declaring an anonymous class.
  <table>
   <tr>
    <th>Valid: Anonymous class with parentheses.</th>
    <th>Invalid: Anonymous class without parentheses.</th>
   </tr>
   <tr>
<td>

    $anon = new class() {};

</td>
<td>

    $anon = new class {};

</td>
   </tr>
  </table>

## Constructor Destructor Return

A class constructor/destructor can not have a return type declarations. This would result in a fatal error.
  <table>
   <tr>
    <th>Valid: No return type declaration.</th>
    <th>Invalid: Return type declaration.</th>
   </tr>
   <tr>
<td>

    class Foo {
        public function __construct() {}
    }

</td>
<td>

    class Foo {
        public function __construct(): int {}
    }

</td>
   </tr>
  </table>
A class constructor/destructor should not return anything.
  <table>
   <tr>
    <th>Valid: Class constructor/destructor doesn't return anything.</th>
    <th>Invalid: Class constructor/destructor returns a value.</th>
   </tr>
   <tr>
<td>

    class Foo {
        public function __construct() {
            // Do something.
        }

        public function __destruct() {
            // Do something.
            return;
        }
    }

</td>
<td>

    class Foo {
        public function __construct() {
            // Do something.
            return $this;
        }

        public function __destruct() {
            // Do something.
            return false;
        }
    }

</td>
   </tr>
  </table>

## Foreach Unique Assignment

When a foreach control structure uses the same variable for both the $key as well as the $value assignment, the key will be disregarded and be inaccessible and the variable will contain the value.
Mix in reference assignments and the behaviour becomes even more unpredictable.

This is a coding error. Either use unique variables for the key and the value or don&#039;t assign the key.
  <table>
   <tr>
    <th>Valid: Using unique variables.</th>
    <th>Invalid: Using the same variable for both the key as well as the value.</th>
   </tr>
   <tr>
<td>

    foreach ($array as $k => $v ) {}

</td>
<td>

    foreach ($array as $k => $k ) {}

</td>
   </tr>
  </table>

## No Double Negative

Detects double negation in code, which is effectively the same as a boolean cast, but with a much higher cognitive load.
  <table>
   <tr>
    <th>Valid: Using singular negation or a boolean cast.</th>
    <th>Invalid: Using double negation (or more).</th>
   </tr>
   <tr>
<td>

    $var = $a && ! $b;

    if((bool) callMe($a)) {}

</td>
<td>

    $var = $a && ! ! $b;

    if(! ! ! callMe($a)) {}

</td>
   </tr>
  </table>

## No Echo Sprintf

Detects use of `echo [v]sprintf(...);`. Use `[v]printf()` instead.
  <table>
   <tr>
    <th>Valid: Using [v]printf() or echo with anything but [v]sprintf().</th>
    <th>Invalid: Using echo [v]sprintf().</th>
   </tr>
   <tr>
<td>

    printf('text %s text', $var);
    echo callMe('text %s text', $var);

</td>
<td>

    echo sprintf('text %s text', $var);
    echo vsprintf('text %s text', [$var]);

</td>
   </tr>
  </table>

## Static in Final Class

When a class is declared as final, using the `static` keyword for late static binding is unnecessary and redundant.
This rule also covers using `static` in a comparison with `instanceof`, using `static` for class instantiations or as a return type.

`self` should be used instead.

This applies to final classes, anonymous classes (final by nature) and enums (final by design).
  <table>
   <tr>
    <th>Valid: Using 'self' in a final OO construct.</th>
    <th>Invalid: Using 'static' in a final OO construct.</th>
   </tr>
   <tr>
<td>

    final class Foo
    {
        public function myMethod($param) : self
        {
            $var = self::functionCall();
            $var = $obj instanceof self;
            $var = new self;
        }
    }

</td>
<td>

    $anon = new class {
        public function myMethod(
        ): int|static|false {
            $var = static::$prop;
            $var = $obj instanceof static;
            $var = new static();
        }
    };

</td>
   </tr>
  </table>

## Lowercase Class Resolution Keyword

The &quot;class&quot; keyword when used for class name resolution, i.e. `::class`, must be in lowercase.
  <table>
   <tr>
    <th>Valid: Using lowercase.</th>
    <th>Invalid: Using uppercase or mixed case.</th>
   </tr>
   <tr>
<td>

    echo MyClass::class;

</td>
<td>

    echo MyClass::CLASS;

</td>
   </tr>
  </table>

## Constant Modifier Keyword Order

Requires that constant modifier keywords consistently use the same keyword order.

By default the expected order is &quot;final visibility&quot;, but this can be changed via the sniff configuration.
  <table>
   <tr>
    <th>Valid: Modifier keywords ordered correctly.</th>
    <th>Invalid: Modifier keywords in reverse order.</th>
   </tr>
   <tr>
<td>

    class CorrectOrder {
        final public const FOO = 'foo';
    }

</td>
<td>

    class IncorrectOrder {
        #[SomeAttribute]
        protected final const BAR = 'foo';
    }

</td>
   </tr>
  </table>

## Uppercase Magic Constants

The PHP native `__...__` magic constant should be in uppercase.
  <table>
   <tr>
    <th>Valid: Using uppercase.</th>
    <th>Invalid: Using lowercase or mixed case.</th>
   </tr>
   <tr>
<td>

    echo __LINE__;
    include __DIR__ . '/file.php';

</td>
<td>

    echo __NameSpace__;
    include dirname(__file__) . '/file.php';

</td>
   </tr>
  </table>

## Disallow Lonely If

Disallows `if` statements as the only statement in an `else` block.

If an `if` statement is the only statement in the `else` block, use `elseif` instead.
  <table>
   <tr>
    <th>Valid: Use of elseif or if followed by another statement.</th>
    <th>Invalid: Lonely if in an else block.</th>
   </tr>
   <tr>
<td>

    if ($foo) {
        // ...
    } elseif ($bar) {
        // ...
    }

    if ($foo) {
        // ...
    } else {
        if ($bar) {
            // ...
        }

        doSomethingElse();

    }

</td>
<td>

    if ($foo) {
        // ...
    } else {
        if ($bar) {
            // ...
        } else {
            // ...
        }
    }

</td>
   </tr>
  </table>

## Separate Functions From OO

A file should either declare (global/namespaced) functions or declare OO structures, but not both.

Nested function declarations, i.e. functions declared within a function/method will be disregarded for the purposes of this sniff.
The same goes for anonymous classes, closures and arrow functions.
  <table>
   <tr>
    <th>Valid: Files containing only functions or only OO.</th>
    <th>Invalid: File containing both OO structure declarations as well as function declarations.</th>
   </tr>
   <tr>
<td>

    // Valid1.php
    <?php
    class Bar {
        public function foo() {}
    }

    // Valid2.php
    <?php
    function foo() {}
    function bar() {}
    function baz() {}

</td>
<td>

    // Invalid.php
    <?php
    function foo() {}

    class Bar {
        public function foo() {}
    }

    function bar() {}
    function baz() {}

</td>
   </tr>
  </table>

## Disallow Curly Brace Namespace Syntax

Namespace declarations using the curly brace syntax are not allowed.
  <table>
   <tr>
    <th>Valid: Namespace declaration without braces.</th>
    <th>Invalid: Namespace declaration with braces.</th>
   </tr>
   <tr>
<td>

    namespace Vendor\Project\Sub;

    // Code

</td>
<td>

    namespace Vendor\Project\Scoped {
        // Code.
    }

</td>
   </tr>
  </table>

## Disallow Namespace Declaration Without Name

Namespace declarations without a namespace name are not allowed.
  <table>
   <tr>
    <th>Valid: Named namespace declaration.</th>
    <th>Invalid: Namespace declaration without a name (=global namespace).</th>
   </tr>
   <tr>
<td>

    namespace Vendor\Name {
    }

</td>
<td>

    namespace {
    }

</td>
   </tr>
  </table>

## One Namespace Declaration Per File

There should be only one namespace declaration per file.
  <table>
   <tr>
    <th>Valid: One namespace declaration in a file.</th>
    <th>Invalid: Multiple namespace declarations in a file.</th>
   </tr>
   <tr>
<td>

    namespace Vendor\Project\Sub;

</td>
<td>

    namespace Vendor\Project\Sub\A {
    }

    namespace Vendor\Project\Sub\B {
    }

</td>
   </tr>
  </table>

## No Reserved Keyword Parameter Names

It is recommended not to use reserved keywords as parameter names as this can become confusing when people use them in function calls using named parameters.
  <table>
   <tr>
    <th>Valid: Parameter names do not use reserved keywords.</th>
    <th>Invalid: Parameter names use reserved keywords.</th>
   </tr>
   <tr>
<td>

    function foo( $input, $description ) {}

</td>
<td>

    function foo( $string, $echo = true ) {}

</td>
   </tr>
  </table>

## Disallow Short Ternary

Using short ternaries is not allowed.

While short ternaries are useful when used correctly, the principle of them is often misunderstood and they are more often than not used incorrectly, leading to hard to debug issues and/or PHP warnings/notices.
  <table>
   <tr>
    <th>Valid: Full ternary.</th>
    <th>Invalid: Short ternary.</th>
   </tr>
   <tr>
<td>

    echo !empty($a) ? $a : 'default';

</td>
<td>

    echo !empty($a) ?: 'default';
    echo $a ? : 'default';

</td>
   </tr>
  </table>

## Disallow Standalone Post-Increment/Decrement

In a stand-alone in/decrement statement, pre-in/decrement should always be favoured over post-in/decrement.

This reduces the chance of bugs when code gets moved around.
  <table>
   <tr>
    <th>Valid: Pre-in/decrement in a stand-alone statement.</th>
    <th>Invalid: Post-in/decrement in a stand-alone statement.</th>
   </tr>
   <tr>
<td>

    ++$i;
    --$j;

</td>
<td>

    $i++;
    $j--;

</td>
   </tr>
  </table>
Using multiple increment/decrement operators in a stand-alone statement is strongly discouraged.
  <table>
   <tr>
    <th>Valid: Single in/decrement operator in a stand-alone statement.</th>
    <th>Invalid: Multiple in/decrement operators in a stand-alone statement.</th>
   </tr>
   <tr>
<td>

    ++$i;

</td>
<td>

    --$i++++;

</td>
   </tr>
  </table>

## Strict Comparisons

Using loose comparisons is not allowed.

Loose comparisons will type juggle the values being compared, which often results in bugs.
  <table>
   <tr>
    <th>Valid: Using strict comparisons.</th>
    <th>Invalid: Using loose comparisons.</th>
   </tr>
   <tr>
<td>

    if ($var === 'text') {}

    if ($var !== true) {}

</td>
<td>

    if ($var == 'text') {}

    if ($var != true) {}

</td>
   </tr>
  </table>

## Type Separator Spacing

Enforce spacing rules around the union, intersection and DNF type operators.
- No space on either side of a union or intersection type operator.
- No space on the inside of DNF type parenthesis or before/after if the previous/next &quot;thing&quot; is part of the type.
- One space before a DNF open parenthesis when it is at the start of a type.
- One space after a DNF close parenthesis when it is at the end of a type.

This applies to all locations where type declarations can be used, i.e. property types, constant types, parameter types and return types.
  <table>
   <tr>
    <th>Valid: Correct spacing around the separators.</th>
    <th>Invalid: Incorrect spacing around the separators.</th>
   </tr>
   <tr>
<td>

    function foo(
        int|string $paramA,
        TypeA&TypeB $paramB,
        (TypeA&TypeB)|null $paramC
    ): int|false {}

</td>
<td>

    function foo(
        int | string $paramA,
        TypeA & TypeB $paramB,
        ( TypeA&TypeB ) |null $paramC
    ): int
       |
       false {}

</td>
   </tr>
  </table>

## Lowercase PHP Tag

Enforces that the PHP open tag is lowercase.
  <table>
   <tr>
    <th>Valid: Lowercase open tag.</th>
    <th>Invalid: Uppercase open tag.</th>
   </tr>
   <tr>
<td>

    <?php
    echo 'hello!';

</td>
<td>

    <?PHP
    echo 'hello!';

</td>
   </tr>
  </table>

## Disallow Mixed Group Use

Disallow group use statements which combine imports for namespace/OO, functions and/or constants in one statement.
  <table>
   <tr>
    <th>Valid: Single type group use statements.</th>
    <th>Invalid: Mixed group use statement.</th>
   </tr>
   <tr>
<td>

    use Some\NS\ {
        ClassName,
        AnotherClass,
    };
    use function Some\NS\ {
        SubLevel\functionName,
        SubLevel\AnotherFunction,
    };
    use const Some\NS\ {
        Constants\MY_CONSTANT as SOME_CONSTANT,
    };

</td>
<td>

    use Some\NS\ {
        ClassName,
        function SubLevel\functionName,
        const MY_CONSTANT as SOME_CONSTANT,
        function SubLevel\AnotherName,
        AnotherLevel,
    };

</td>
   </tr>
  </table>

## Import Use Keyword Spacing

Enforce a single space after the `use`, `function`, `const` keywords and both before and after the `as` keyword in import `use` statements.
  <table>
   <tr>
    <th>Valid: Single space used around keywords.</th>
    <th>Invalid: Incorrect spacing used around keywords.</th>
   </tr>
   <tr>
<td>

    use function strpos;
    use const PHP_EOL as MY_EOL;

</td>
<td>

    use    function   strpos;
    use
      const
      PHP_EOL
      as
      MY_EOL;

</td>
   </tr>
  </table>

## Lowercase Function Const

`function` and `const` keywords in import `use` statements should be in lowercase.
  <table>
   <tr>
    <th>Valid: Lowercase keywords.</th>
    <th>Invalid: Non-lowercase keywords.</th>
   </tr>
   <tr>
<td>

    use function strpos;
    use const PHP_EOL;

</td>
<td>

    use Function strpos;
    use CONST PHP_EOL;

</td>
   </tr>
  </table>

## No Leading Backslash

Import `use` statements must never begin with a leading backslash as they should always be fully qualified.
  <table>
   <tr>
    <th>Valid: Import use statement without leading backslash.</th>
    <th>Invalid: Import use statement with leading backslash.</th>
   </tr>
   <tr>
<td>

    use Package\ClassName;

</td>
<td>

    use \Package\ClassName;

</td>
   </tr>
  </table>

## No Useless Aliases

Detects useless aliases for import use statements.

Aliasing something to the same name as the original construct is considered useless.
Note: as OO and function names in PHP are case-insensitive, aliasing to the same name, using a different case is also considered useless.
  <table>
   <tr>
    <th>Valid: Import use statement with an alias to a different name.</th>
    <th>Invalid: Import use statement with an alias to the same name.</th>
   </tr>
   <tr>
<td>

    use Vendor\Package\ClassName as AnotherName;
    use function functionName as my_function;
    use const SOME_CONSTANT as MY_CONSTANT;

</td>
<td>

    use Vendor\Package\ClassName as ClassName;
    use function functionName as FunctionName;
    use const SOME_CONSTANT as SOME_CONSTANT;

</td>
   </tr>
  </table>

## Anonymous Class Keyword Spacing

Checks the spacing between the &quot;class&quot; keyword and the open parenthesis for anonymous classes with parentheses.

The desired amount of spacing is configurable and defaults to no space.
  <table>
   <tr>
    <th>Valid: No space between the class keyword and the open parenthesis.</th>
    <th>Invalid: Space between the class keyword and the open parenthesis.</th>
   </tr>
   <tr>
<td>

    $foo = new class($param)
    {
        public function __construct($p) {}
    };

</td>
<td>

    $foo = new class ($param)
    {
        public function __construct($p) {}
    };

</td>
   </tr>
  </table>

## Comma Spacing

There should be no space before a comma and exactly one space, or a new line, after a comma.

The sniff makes the following exceptions to this rule:
1. A comma preceded or followed by a parenthesis, curly or square bracket.
These will not be flagged to prevent conflicts with sniffs handling spacing around braces.
2. A comma preceded or followed by another comma, like for skipping items in a list assignment.
These will not be flagged.
3. A comma preceded by a non-indented heredoc/nowdoc closer.
In that case, unless the `php_version` config directive is set to a version higher than PHP 7.3.0,
a new line before will be enforced to prevent parse errors on PHP &lt; 7.3.
  <table>
   <tr>
    <th>Valid: Correct spacing.</th>
    <th>Invalid: Incorrect spacing.</th>
   </tr>
   <tr>
<td>

    isset($param1, $param2, $param3);

    function_call(
        $param1,
        $param2,
        $param3
    );

    $array = array($item1, $item2, $item3);
    $array = [
        $item1,
        $item2,
    ];

    list(, $a, $b,,) = $array;
    list(
        ,
        $a,
        $b,
    ) = $array;

</td>
<td>

    unset($param1  ,   $param2,$param3);

    function_call(
        $a
        ,$b
        ,$c
    );

    $array = array($item1,$item2  ,  $item3);
    $array = [
        $item1,
        $item2  ,
    ];

    list( ,$a, $b  ,,) = $array;
    list(
        ,
        $a,
        $b  ,
    ) = $array;

</td>
   </tr>
  </table>
A comma should follow the code and not be placed after a trailing comment.
  <table>
   <tr>
    <th>Valid: Comma after the code.</th>
    <th>Invalid: Comma after a trailing comment.</th>
   </tr>
   <tr>
<td>

    function_call(
        $param1, // Comment.
        $param2, /* Comment. */
    );

</td>
<td>

    function_call(
        $param1 // Comment.
        ,
        $param2 /* Comment. */,
    );

</td>
   </tr>
  </table>

## Disallow Inline Tabs

Spaces must be used for mid-line alignment.
  <table>
   <tr>
    <th>Valid: Spaces used for alignment.</th>
    <th>Invalid: Tabs used for alignment.</th>
   </tr>
   <tr>
<td>

    $title[space]= 'text';
    $text[space][space]= 'more text';

</td>
<td>

    $title[tab]= 'text';
    $text[tab]= 'more text';

</td>
   </tr>
  </table>

## Precision Alignment

Detects when the indentation is not a multiple of a tab-width, i.e. when precision alignment is used.
  <table>
   <tr>
    <th>Valid: Indentation equals (a multiple of) the tab width.</th>
    <th>Invalid: Precision alignment used, indentation does not equal (a multiple of) the tab width.</th>
   </tr>
   <tr>
<td>

    // Code samples presume tab width = 4.
    [space][space][space][space]$foo = 'bar';

    [tab]$foo = 'bar';

</td>
<td>

    // Code samples presume tab width = 4.
    [space][space]$foo = 'bar';

    [tab][space]$foo = 'bar';

</td>
   </tr>
  </table>

## Duplicate Class Names

Class and Interface names should be unique in a project.  They should never be duplicated.
  <table>
   <tr>
    <th>Valid: A unique class name.</th>
    <th>Invalid: A class duplicated (including across multiple files).</th>
   </tr>
   <tr>
<td>

    class Foo
    {
    }

</td>
<td>

    class Foo
    {
    }

    class Foo
    {
    }

</td>
   </tr>
  </table>

## Opening Brace on Same Line

The opening brace of a class must be on the same line after the definition and must be the last thing on that line.
  <table>
   <tr>
    <th>Valid: Opening brace on the same line.</th>
    <th>Invalid: Opening brace on the next line.</th>
   </tr>
   <tr>
<td>

    class Foo {
    }

</td>
<td>

    class Foo
    {
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Opening brace is the last thing on the line.</th>
    <th>Invalid: Opening brace not last thing on the line.</th>
   </tr>
   <tr>
<td>

    class Foo {
    }

</td>
<td>

    class Foo { // Start of class.
    }

</td>
   </tr>
  </table>

## Assignment In Condition

Variable assignments should not be made within conditions.
  <table>
   <tr>
    <th>Valid: A variable comparison being executed within a condition.</th>
    <th>Invalid: A variable assignment being made within a condition.</th>
   </tr>
   <tr>
<td>

    if ($test === 'abc') {
        // Code.
    }

</td>
<td>

    if ($test = 'abc') {
        // Code.
    }

</td>
   </tr>
  </table>

## Empty PHP Statement

Empty PHP tags are not allowed.
  <table>
   <tr>
    <th>Valid: There is at least one statement inside the PHP tag pair.</th>
    <th>Invalid: There is no statement inside the PHP tag pair.</th>
   </tr>
   <tr>
<td>

    <?php echo 'Hello World'; ?>
    <?= 'Hello World'; ?>

</td>
<td>

    <?php ; ?>
    <?=  ?>

</td>
   </tr>
  </table>
Superfluous semicolons are not allowed.
  <table>
   <tr>
    <th>Valid: There is no superfluous semicolon after a PHP statement.</th>
    <th>Invalid: There are one or more superfluous semicolons after a PHP statement.</th>
   </tr>
   <tr>
<td>

    function_call();
    if (true) {
        echo 'Hello World';
    }

</td>
<td>

    function_call();;;
    if (true) {
        echo 'Hello World';
    };

</td>
   </tr>
  </table>

## Empty Statements

Control Structures must have at least one statement inside of the body.
  <table>
   <tr>
    <th>Valid: There is a statement inside the control structure.</th>
    <th>Invalid: The control structure has no statements.</th>
   </tr>
   <tr>
<td>

    if ($test) {
        $var = 1;
    }

</td>
<td>

    if ($test) {
        // do nothing
    }

</td>
   </tr>
  </table>

## Condition-Only For Loops

For loops that have only a second expression (the condition) should be converted to while loops.
  <table>
   <tr>
    <th>Valid: A for loop is used with all three expressions.</th>
    <th>Invalid: A for loop is used without a first or third expression.</th>
   </tr>
   <tr>
<td>

    for ($i = 0; $i < 10; $i++) {
        echo "{$i}\n";
    }

</td>
<td>

    for (;$test;) {
        $test = doSomething();
    }

</td>
   </tr>
  </table>

## For Loops With Function Calls in the Test

For loops should not call functions inside the test for the loop when they can be computed beforehand.
  <table>
   <tr>
    <th>Valid: A for loop that determines its end condition before the loop starts.</th>
    <th>Invalid: A for loop that unnecessarily computes the same value on every iteration.</th>
   </tr>
   <tr>
<td>

    $end = count($foo);
    for ($i = 0; $i < $end; $i++) {
        echo $foo[$i]."\n";
    }

</td>
<td>

    for ($i = 0; $i < count($foo); $i++) {
        echo $foo[$i]."\n";
    }

</td>
   </tr>
  </table>

## Jumbled Incrementers

Incrementers in nested loops should use different variable names.
  <table>
   <tr>
    <th>Valid: Two different variables being used to increment.</th>
    <th>Invalid: Inner incrementer is the same variable name as the outer one.</th>
   </tr>
   <tr>
<td>

    for ($i = 0; $i < 10; $i++) {
        for ($j = 0; $j < 10; $j++) {
        }
    }

</td>
<td>

    for ($i = 0; $i < 10; $i++) {
        for ($j = 0; $j < 10; $i++) {
        }
    }

</td>
   </tr>
  </table>

## Require Explicit Boolean Operator Precedence

Forbids mixing different binary boolean operators (&amp;&amp;, ||, and, or, xor) within a single expression without making precedence clear using parentheses.
  <table>
   <tr>
    <th>Valid: Making precedence clear with parentheses.</th>
    <th>Invalid: Not using parentheses.</th>
   </tr>
   <tr>
<td>

    $one = false;
    $two = false;
    $three = true;

    $result = ($one && $two) || $three;
    $result2 = $one && ($two || $three);
    $result3 = ($one && !$two) xor $three;
    $result4 = $one && (!$two xor $three);

    if (
        ($result && !$result3)
        || (!$result && $result3)
    ) {}

</td>
<td>

    $one = false;
    $two = false;
    $three = true;

    $result = $one && $two || $three;

    $result3 = $one && !$two xor $three;


    if (
        $result && !$result3
        || !$result && $result3
    ) {}

</td>
   </tr>
  </table>

## Unconditional If Statements

If statements that are always evaluated should not be used.
  <table>
   <tr>
    <th>Valid: An if statement that only executes conditionally.</th>
    <th>Invalid: An if statement that is always performed.</th>
   </tr>
   <tr>
<td>

    if ($test) {
        $var = 1;
    }

</td>
<td>

    if (true) {
        $var = 1;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: An if statement that only executes conditionally.</th>
    <th>Invalid: An if statement that is never performed.</th>
   </tr>
   <tr>
<td>

    if ($test) {
        $var = 1;
    }

</td>
<td>

    if (false) {
        $var = 1;
    }

</td>
   </tr>
  </table>

## Unnecessary Final Modifiers

Methods should not be declared final inside of classes that are declared final.
  <table>
   <tr>
    <th>Valid: A method in a final class is not marked final.</th>
    <th>Invalid: A method in a final class is also marked final.</th>
   </tr>
   <tr>
<td>

    final class Foo
    {
        public function bar()
        {
        }
    }

</td>
<td>

    final class Foo
    {
        public final function bar()
        {
        }
    }

</td>
   </tr>
  </table>

## Unused function parameters

All parameters in a functions signature should be used within the function.
  <table>
   <tr>
    <th>Valid: All the parameters are used.</th>
    <th>Invalid: One of the parameters is not being used.</th>
   </tr>
   <tr>
<td>

    function addThree($a, $b, $c)
    {
        return $a + $b + $c;
    }

</td>
<td>

    function addThree($a, $b, $c)
    {
        return $a + $b;
    }

</td>
   </tr>
  </table>

## Useless Overriding Method

It is discouraged to override a method if the overriding method only calls the parent method.
  <table>
   <tr>
    <th>Valid: A method that extends functionality of a parent method.</th>
    <th>Invalid: An overriding method that only calls the parent method.</th>
   </tr>
   <tr>
<td>

    final class Foo extends Baz
    {
        public function bar()
        {
            parent::bar();
            $this->doSomethingElse();
        }
    }

</td>
<td>

    final class Foo extends Baz
    {
        public function bar()
        {
            parent::bar();
        }
    }

</td>
   </tr>
  </table>

## Doc Comment

Enforces rules related to the formatting of DocBlocks (&quot;Doc Comments&quot;) in PHP code.

DocBlocks are a special type of comment that can provide information about a structural element. In the context of DocBlocks, the following are considered structural elements:
class, interface, trait, enum, function, property, constant, variable declarations and require/include[_once] statements.

DocBlocks start with a `/**` marker and end on `*/`. This sniff will check the formatting of all DocBlocks, independently of whether or not they are attached to a structural element.
A DocBlock must not be empty.
  <table>
   <tr>
    <th>Valid: DocBlock with some content.</th>
    <th>Invalid: Empty DocBlock.</th>
   </tr>
   <tr>
<td>

    /**
     * Some content.
     */

</td>
<td>

    /**
     *
     */

</td>
   </tr>
  </table>
The opening and closing DocBlock tags must be the only content on the line.
  <table>
   <tr>
    <th>Valid: The opening and closing DocBlock tags have to be on a line by themselves.</th>
    <th>Invalid: The opening and closing DocBlock tags are not on a line by themselves.</th>
   </tr>
   <tr>
<td>

    /**
     * Short description.
     */

</td>
<td>

    /** Short description. */

</td>
   </tr>
  </table>
The DocBlock must have a short description, and it must be on the first line.
  <table>
   <tr>
    <th>Valid: DocBlock with a short description on the first line.</th>
    <th>Invalid: DocBlock without a short description or short description not on the first line.</th>
   </tr>
   <tr>
<td>

    /**
     * Short description.
     */

</td>
<td>

    /**
     * @return int
     */

    /**
     *
     * Short description.
     */

</td>
   </tr>
  </table>
Both the short description, as well as the long description, must start with a capital letter.
  <table>
   <tr>
    <th>Valid: Both the short and long description start with a capital letter.</th>
    <th>Invalid: Neither short nor long description starts with a capital letter.</th>
   </tr>
   <tr>
<td>

    /**
     * Short description.
     *
     * Long description.
     */

</td>
<td>

    /**
     * short description.
     *
     * long description.
     */

</td>
   </tr>
  </table>
There must be exactly one blank line separating the short description, the long description and tag groups.
  <table>
   <tr>
    <th>Valid: One blank line separating the short description, the long description and tag groups.</th>
    <th>Invalid: More than one or no blank line separating the short description, the long description and tag groups.</th>
   </tr>
   <tr>
<td>

    /**
     * Short description.
     *
     * Long description.
     *
     * @param int $foo
     */

</td>
<td>

    /**
     * Short description.
     *
     *

     * Long description.
     * @param int $foo
     */

</td>
   </tr>
  </table>
Parameter tags must be grouped together.
  <table>
   <tr>
    <th>Valid: Parameter tags grouped together.</th>
    <th>Invalid: Parameter tags not grouped together.</th>
   </tr>
   <tr>
<td>

    /**
     * Short description.
     *
     * @param int $foo
     * @param string $bar
     */

</td>
<td>

    /**
     * Short description.
     *
     * @param int $foo
     *
     * @param string $bar
     */

</td>
   </tr>
  </table>
Parameter tags must not be grouped together with other tags.
  <table>
   <tr>
    <th>Valid: Parameter tags are not grouped together with other tags.</th>
    <th>Invalid: Parameter tags grouped together with other tags.</th>
   </tr>
   <tr>
<td>

    /**
     * Short description.
     *
     * @param int $foo
     *
     * @since      3.4.8
     * @deprecated 6.0.0
     */

</td>
<td>

    /**
     * Short description.
     *
     * @param      int $foo
     * @since      3.4.8
     * @deprecated 6.0.0
     */

</td>
   </tr>
  </table>
Tag values for different tags in the same group must be aligned with each other.
  <table>
   <tr>
    <th>Valid: Tag values for different tags in the same tag group are aligned with each other.</th>
    <th>Invalid: Tag values for different tags in the same tag group are not aligned with each other.</th>
   </tr>
   <tr>
<td>

    /**
     * Short description.
     *
     * @since      0.5.0
     * @deprecated 1.0.0
     */

</td>
<td>

    /**
     * Short description.
     *
     * @since 0.5.0
     * @deprecated 1.0.0
     */

</td>
   </tr>
  </table>
Parameter tags must be defined before other tags in a DocBlock.
  <table>
   <tr>
    <th>Valid: Parameter tags are defined first.</th>
    <th>Invalid: Parameter tags are not defined first.</th>
   </tr>
   <tr>
<td>

    /**
     * Short description.
     *
     * @param string $foo
     *
     * @return void
     */

</td>
<td>

    /**
     * Short description.
     *
     * @return void
     *
     * @param string $bar
     */

</td>
   </tr>
  </table>
There must be no additional blank (comment) lines before the closing DocBlock tag.
  <table>
   <tr>
    <th>Valid: No additional blank lines before the closing DocBlock tag.</th>
    <th>Invalid: Additional blank lines before the closing DocBlock tag.</th>
   </tr>
   <tr>
<td>

    /**
     * Short description.
     */

</td>
<td>

    /**
     * Short description.
     *
     */

</td>
   </tr>
  </table>

## Inline Control Structures

Control Structures should use braces.
  <table>
   <tr>
    <th>Valid: Braces are used around the control structure.</th>
    <th>Invalid: No braces are used for the control structure..</th>
   </tr>
   <tr>
<td>

    if ($test) {
        $var = 1;
    }

</td>
<td>

    if ($test)
        $var = 1;

</td>
   </tr>
  </table>

## Byte Order Marks

Byte Order Marks that may corrupt your application should not be used.  These include 0xefbbbf (UTF-8), 0xfeff (UTF-16 BE) and 0xfffe (UTF-16 LE).

## Line Endings

Unix-style line endings are preferred (&quot;\n&quot; instead of &quot;\r\n&quot;).

## Line Length

It is recommended to keep lines at approximately 80 characters long for better code readability.

## One Object Structure Per File

There should only be one class or interface or trait defined in a file.
  <table>
   <tr>
    <th>Valid: Only one object structure in the file.</th>
    <th>Invalid: Multiple object structures defined in one file.</th>
   </tr>
   <tr>
<td>

    <?php
    trait Foo
    {
    }

</td>
<td>

    <?php
    trait Foo
    {
    }

    class Bar
    {
    }

</td>
   </tr>
  </table>

## Multiple Statements On a Single Line

Multiple statements are not allowed on a single line.
  <table>
   <tr>
    <th>Valid: Two statements are spread out on two separate lines.</th>
    <th>Invalid: Two statements are combined onto one line.</th>
   </tr>
   <tr>
<td>

    $foo = 1;
    $bar = 2;

</td>
<td>

    $foo = 1; $bar = 2;

</td>
   </tr>
  </table>

## Aligning Blocks of Assignments

There should be one space on either side of an equals sign used to assign a value to a variable. In the case of a block of related assignments, more space may be inserted to promote readability.
  <table>
   <tr>
    <th>Valid: Equals signs aligned.</th>
    <th>Invalid: Not aligned; harder to read.</th>
   </tr>
   <tr>
<td>

    $shortVar        = (1 + 2);
    $veryLongVarName = 'string';
    $var             = foo($bar, $baz);

</td>
<td>

    $shortVar = (1 + 2);
    $veryLongVarName = 'string';
    $var = foo($bar, $baz);

</td>
   </tr>
  </table>
When using plus-equals, minus-equals etc. still ensure the equals signs are aligned to one space after the longest variable name.
  <table>
   <tr>
    <th>Valid: Equals signs aligned; only one space after longest var name.</th>
    <th>Invalid: Two spaces after longest var name.</th>
   </tr>
   <tr>
<td>

    $shortVar       += 1;
    $veryLongVarName = 1;

</td>
<td>

    $shortVar        += 1;
    $veryLongVarName  = 1;

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Equals signs aligned.</th>
    <th>Invalid: Equals signs not aligned.</th>
   </tr>
   <tr>
<td>

    $shortVar         = 1;
    $veryLongVarName -= 1;

</td>
<td>

    $shortVar        = 1;
    $veryLongVarName -= 1;

</td>
   </tr>
  </table>

## Space After Cast

Exactly one space is allowed after a cast.
  <table>
   <tr>
    <th>Valid: A cast operator is followed by one space.</th>
    <th>Invalid: A cast operator is not followed by whitespace.</th>
   </tr>
   <tr>
<td>

    $foo = (string) 1;

</td>
<td>

    $foo = (string)1;

</td>
   </tr>
  </table>

## Call-Time Pass-By-Reference

Call-time pass-by-reference is not allowed. It should be declared in the function definition.
  <table>
   <tr>
    <th>Valid: Pass-by-reference is specified in the function definition.</th>
    <th>Invalid: Pass-by-reference is done in the call to a function.</th>
   </tr>
   <tr>
<td>

    function foo(&$bar)
    {
        $bar++;
    }

    $baz = 1;
    foo($baz);

</td>
<td>

    function foo($bar)
    {
        $bar++;
    }

    $baz = 1;
    foo(&$baz);

</td>
   </tr>
  </table>

## Function Call Argument Spacing

There should be no space before and exactly one space, or a new line, after a comma when passing arguments to a function or method.
  <table>
   <tr>
    <th>Valid: No space before and exactly one space after a comma.</th>
    <th>Invalid: A space before and no space after a comma.</th>
   </tr>
   <tr>
<td>

    foo($bar, $baz);

</td>
<td>

    foo($bar ,$baz);

</td>
   </tr>
  </table>

## Opening Function Brace Kerninghan Ritchie

The function opening brace must be on the same line as the end of the function declaration, with
exactly one space between the end of the declaration and the brace. The brace must be the last
content on the line.
  <table>
   <tr>
    <th>Valid: Opening brace on the same line.</th>
    <th>Invalid: Opening brace on the next line.</th>
   </tr>
   <tr>
<td>

    function fooFunction($arg1, $arg2 = '') {
        // Do something.
    }

</td>
<td>

    function fooFunction($arg1, $arg2 = '')
    {
        // Do something.
    }

</td>
   </tr>
  </table>

## Constant Names

Constants should always be all-uppercase, with underscores to separate words.
  <table>
   <tr>
    <th>Valid: All uppercase constant name.</th>
    <th>Invalid: Mixed case or lowercase constant name.</th>
   </tr>
   <tr>
<td>

    define('FOO_CONSTANT', 'foo');

    class FooClass
    {
        const FOO_CONSTANT = 'foo';
    }

</td>
<td>

    define('Foo_Constant', 'foo');

    class FooClass
    {
        const foo_constant = 'foo';
    }

</td>
   </tr>
  </table>

## Backtick Operator

Disallow the use of the backtick operator for execution of shell commands.

## Deprecated Functions

Deprecated functions should not be used.
  <table>
   <tr>
    <th>Valid: A non-deprecated function is used.</th>
    <th>Invalid: A deprecated function is used.</th>
   </tr>
   <tr>
<td>

    $foo = explode('a', $bar);

</td>
<td>

    $foo = split('a', $bar);

</td>
   </tr>
  </table>

## Alternative PHP Code Tags

Always use &lt;?php ?&gt; to delimit PHP code, do not use the ASP &lt;% %&gt; style tags nor the &lt;script language=&quot;php&quot;&gt;&lt;/script&gt; tags. This is the most portable way to include PHP code on differing operating systems and setups.

## PHP Code Tags

Always use &lt;?php ?&gt; to delimit PHP code, not the &lt;? ?&gt; shorthand. This is the most portable way to include PHP code on differing operating systems and setups.

## Goto

Discourage the use of the PHP `goto` language construct.

## Forbidden Functions

The forbidden functions sizeof() and delete() should not be used.
  <table>
   <tr>
    <th>Valid: count() is used in place of sizeof().</th>
    <th>Invalid: sizeof() is used.</th>
   </tr>
   <tr>
<td>

    $foo = count($bar);

</td>
<td>

    $foo = sizeof($bar);

</td>
   </tr>
  </table>

## Lowercase PHP Constants

The *true*, *false* and *null* constants must always be lowercase.
  <table>
   <tr>
    <th>Valid: Lowercase constants.</th>
    <th>Invalid: Uppercase constants.</th>
   </tr>
   <tr>
<td>

    if ($var === false || $var === null) {
        $var = true;
    }

</td>
<td>

    if ($var === FALSE || $var === NULL) {
        $var = TRUE;
    }

</td>
   </tr>
  </table>

## Lowercase Keywords

All PHP keywords should be lowercase.
  <table>
   <tr>
    <th>Valid: Lowercase array keyword used.</th>
    <th>Invalid: Non-lowercase array keyword used.</th>
   </tr>
   <tr>
<td>

    $foo = array();

</td>
<td>

    $foo = Array();

</td>
   </tr>
  </table>

## Lowercase PHP Types

All PHP types used for parameter type and return type declarations should be lowercase.
  <table>
   <tr>
    <th>Valid: Lowercase type declarations used.</th>
    <th>Invalid: Non-lowercase type declarations used.</th>
   </tr>
   <tr>
<td>

    function myFunction(int $foo) : string {
    }

</td>
<td>

    function myFunction(Int $foo) : STRING {
    }

</td>
   </tr>
  </table>
All PHP types used for type casting should be lowercase.
  <table>
   <tr>
    <th>Valid: Lowercase type used.</th>
    <th>Invalid: Non-lowercase type used.</th>
   </tr>
   <tr>
<td>

    $foo = (bool) $isValid;

</td>
<td>

    $foo = (BOOL) $isValid;

</td>
   </tr>
  </table>

## PHP Syntax

The code should use valid PHP syntax.
  <table>
   <tr>
    <th>Valid: No PHP syntax errors.</th>
    <th>Invalid: Code contains PHP syntax errors.</th>
   </tr>
   <tr>
<td>

    echo "Hello!";
    $array = [1, 2, 3];

</td>
<td>

    echo "Hello!" // Missing semicolon.
    $array = [1, 2, 3; // Missing closing bracket.

</td>
   </tr>
  </table>

## Unnecessary String Concatenation

Strings should not be concatenated together.
  <table>
   <tr>
    <th>Valid: A string can be concatenated with an expression.</th>
    <th>Invalid: Strings should not be concatenated together.</th>
   </tr>
   <tr>
<td>

    echo '5 + 2 = ' . (5 + 2);

</td>
<td>

    echo 'Hello' . ' ' . 'World';

</td>
   </tr>
  </table>

## Arbitrary Parentheses Spacing

Arbitrary sets of parentheses should have no spaces inside.
  <table>
   <tr>
    <th>Valid: No spaces on the inside of a set of arbitrary parentheses.</th>
    <th>Invalid: Spaces or new lines on the inside of a set of arbitrary parentheses.</th>
   </tr>
   <tr>
<td>

    $a = (null !== $extra);

</td>
<td>

    $a = ( null !== $extra );

    $a = (
        null !== $extra
    );

</td>
   </tr>
  </table>

## No Space Indentation

Tabs should be used for indentation instead of spaces.

## Increment Decrement Spacing

There should be no whitespace between variables and increment/decrement operators.
  <table>
   <tr>
    <th>Valid: No whitespace between variables and increment/decrement operators.</th>
    <th>Invalid: Whitespace between variables and increment/decrement operators.</th>
   </tr>
   <tr>
<td>

    ++$i;
    --$i['key']['id'];
    ClassName::$prop++;
    $obj->prop--;

</td>
<td>

    ++ $i;
    --   $i['key']['id'];
    ClassName::$prop    ++;
    $obj->prop
    --;

</td>
   </tr>
  </table>

## Language Construct Spacing

Language constructs that can be used without parentheses, must have a single space between the language construct keyword and its content.
  <table>
   <tr>
    <th>Valid: Single space after language construct.</th>
    <th>Invalid: No space, more than one space or newline after language construct.</th>
   </tr>
   <tr>
<td>

    echo 'Hello, World!';
    throw new Exception();
    return $newLine;

</td>
<td>

    echo'Hello, World!';
    throw   new   Exception();
    return
    $newLine;

</td>
   </tr>
  </table>
A single space must be used between the &quot;yield&quot; and &quot;from&quot; keywords for a &quot;yield from&quot; expression.
  <table>
   <tr>
    <th>Valid: Single space between yield and from.</th>
    <th>Invalid: More than one space or newline between yield and from.</th>
   </tr>
   <tr>
<td>

    function myGenerator() {
        yield from [1, 2, 3];
    }

</td>
<td>

    function myGenerator() {
        yield  from [1, 2, 3];
        yield
        from [1, 2, 3];
    }

</td>
   </tr>
  </table>

## Scope Indentation

Indentation for control structures, classes, and functions should be 4 spaces per level.
  <table>
   <tr>
    <th>Valid: 4 spaces are used to indent a control structure.</th>
    <th>Invalid: 8 spaces are used to indent a control structure.</th>
   </tr>
   <tr>
<td>

    if ($test) {
        $var = 1;
    }

</td>
<td>

    if ($test) {
            $var = 1;
    }

</td>
   </tr>
  </table>

## Spacing After Spread Operator

There should be no space between the spread operator and the variable/function call it applies to.
  <table>
   <tr>
    <th>Valid: No space between the spread operator and the variable/function call it applies to.</th>
    <th>Invalid: Space found between the spread operator and the variable/function call it applies to.</th>
   </tr>
   <tr>
<td>

    function foo(&...$spread) {
        bar(...$spread);

        bar(
            [...$foo],
            ...array_values($keyedArray)
        );
    }

</td>
<td>

    function bar(... $spread) {
        bar(...
            $spread
        );

        bar(
            [... $foo ],.../*@*/array_values($keyed)
        );
    }

</td>
   </tr>
  </table>

## Including Code

Anywhere you are unconditionally including a class file, use *require_once*. Anywhere you are conditionally including a class file (for example, factory methods), use *include_once*. Either of these will ensure that class files are included only once. They share the same file list, so you don&#039;t need to worry about mixing them - a file included with *require_once* will not be included again by *include_once*.
Note that *include_once* and *require_once* are statements, not functions. Parentheses should not surround the subject filename.
  <table>
   <tr>
    <th>Valid: Used as statement.</th>
    <th>Invalid: Used as function.</th>
   </tr>
   <tr>
<td>

    require_once 'PHP/CodeSniffer.php';

</td>
<td>

    require_once('PHP/CodeSniffer.php');

</td>
   </tr>
  </table>

## Function Calls

Functions should be called with no spaces between the function name, the opening parenthesis, and the first parameter; and no space between the last parameter, the closing parenthesis, and the semicolon.
  <table>
   <tr>
    <th>Valid: Spaces between parameters.</th>
    <th>Invalid: Additional spaces used.</th>
   </tr>
   <tr>
<td>

    $var = foo($bar, $baz, $quux);

</td>
<td>

    $var = foo ( $bar, $baz, $quux ) ;

</td>
   </tr>
  </table>

## Default Values in Function Declarations

Arguments with default values go at the end of the argument list.
  <table>
   <tr>
    <th>Valid: Argument with default value at end of declaration.</th>
    <th>Invalid: Argument with default value at start of declaration.</th>
   </tr>
   <tr>
<td>

    function connect($dsn, $persistent = false)
    {
        ...
    }

</td>
<td>

    function connect($persistent = false, $dsn)
    {
        ...
    }

</td>
   </tr>
  </table>

## Class Names

Classes should be given descriptive names. Avoid using abbreviations where possible. Class names should always begin with an uppercase letter. The PEAR class hierarchy is also reflected in the class name, each level of the hierarchy separated with a single underscore.
  <table>
   <tr>
    <th>Valid: Examples of valid class names.</th>
    <th>Invalid: Examples of invalid class names.</th>
   </tr>
   <tr>
<td>

    Log
    Net_Finger
    HTML_Upload_Error

</td>
<td>

    log
    NetFinger
    HTML-Upload-Error

</td>
   </tr>
  </table>

## Class Declaration

Each class must be in a file by itself and must be under a namespace (a top-level vendor name).
  <table>
   <tr>
    <th>Valid: One class in a file.</th>
    <th>Invalid: Multiple classes in a single file.</th>
   </tr>
   <tr>
<td>

    <?php
    namespace Foo;

    class Bar {
    }

</td>
<td>

    <?php
    namespace Foo;

    class Bar {
    }

    class Baz {
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: A vendor-level namespace is used.</th>
    <th>Invalid: No namespace used in file.</th>
   </tr>
   <tr>
<td>

    <?php
    namespace Foo;

    class Bar {
    }

</td>
<td>

    <?php
    class Bar {
    }

</td>
   </tr>
  </table>

## Side Effects

A PHP file should either contain declarations with no side effects, or should just have logic (including side effects) with no declarations.
  <table>
   <tr>
    <th>Valid: A class defined in a file by itself.</th>
    <th>Invalid: A class defined in a file with other code.</th>
   </tr>
   <tr>
<td>

    <?php
    class Foo
    {
    }

</td>
<td>

    <?php
    class Foo
    {
    }

    echo "Class Foo loaded."

</td>
   </tr>
  </table>

## Method Name

Method names MUST be declared in camelCase.
  <table>
   <tr>
    <th>Valid: Method name in camelCase.</th>
    <th>Invalid: Method name not in camelCase.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        private function doBar()
        {
        }
    }

</td>
<td>

    class Foo
    {
        private function do_bar()
        {
        }
    }

</td>
   </tr>
  </table>

## Class Declarations

There should be exactly 1 space between the abstract or final keyword and the class keyword and between the class keyword and the class name.  The extends and implements keywords, if present, must be on the same line as the class name.  When interfaces implemented are spread over multiple lines, there should be exactly 1 interface mentioned per line indented by 1 level.  The closing brace of the class must go on the first line after the body of the class and must be on a line by itself.
  <table>
   <tr>
    <th>Valid: Correct spacing around class keyword.</th>
    <th>Invalid: 2 spaces used around class keyword.</th>
   </tr>
   <tr>
<td>

    abstract class Foo
    {
    }

</td>
<td>

    abstract  class  Foo
    {
    }

</td>
   </tr>
  </table>

## Property Declarations

Property names should not be prefixed with an underscore to indicate visibility.  Visibility should be used to declare properties rather than the var keyword.  Only one property should be declared within a statement.  The static declaration must come after the visibility declaration.
  <table>
   <tr>
    <th>Valid: Correct property naming.</th>
    <th>Invalid: An underscore prefix used to indicate visibility.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        private $bar;
    }

</td>
<td>

    class Foo
    {
        private $_bar;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Visibility of property declared.</th>
    <th>Invalid: Var keyword used to declare property.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        private $bar;
    }

</td>
<td>

    class Foo
    {
        var $bar;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: One property declared per statement.</th>
    <th>Invalid: Multiple properties declared in one statement.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        private $bar;
        private $baz;
    }

</td>
<td>

    class Foo
    {
        private $bar, $baz;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: If declared as static, the static declaration must come after the visibility declaration.</th>
    <th>Invalid: Static declaration before the visibility declaration.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        public static $bar;
        private $baz;
    }

</td>
<td>

    class Foo
    {
        static protected $bar;
    }

</td>
   </tr>
  </table>

## Elseif Declarations

PHP&#039;s elseif keyword should be used instead of else if.
  <table>
   <tr>
    <th>Valid: Single word elseif keyword used.</th>
    <th>Invalid: Separate else and if keywords used.</th>
   </tr>
   <tr>
<td>

    if ($foo) {
        $var = 1;
    } elseif ($bar) {
        $var = 2;
    }

</td>
<td>

    if ($foo) {
        $var = 1;
    } else if ($bar) {
        $var = 2;
    }

</td>
   </tr>
  </table>

## Switch Declarations

Case statements should be indented 4 spaces from the switch keyword.  It should also be followed by a space.  Colons in switch declarations should not be preceded by whitespace.  Break statements should be indented 4 more spaces from the case statement.  There must be a comment when falling through from one case into the next.
  <table>
   <tr>
    <th>Valid: Case statement indented correctly.</th>
    <th>Invalid: Case statement not indented 4 spaces.</th>
   </tr>
   <tr>
<td>

    switch ($foo) {
        case 'bar':
            break;
    }

</td>
<td>

    switch ($foo) {
    case 'bar':
        break;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Case statement followed by 1 space.</th>
    <th>Invalid: Case statement not followed by 1 space.</th>
   </tr>
   <tr>
<td>

    switch ($foo) {
        case 'bar':
            break;
    }

</td>
<td>

    switch ($foo) {
        case'bar':
            break;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Colons not prefixed by whitespace.</th>
    <th>Invalid: Colons prefixed by whitespace.</th>
   </tr>
   <tr>
<td>

    switch ($foo) {
        case 'bar':
            break;
        default:
            break;
    }

</td>
<td>

    switch ($foo) {
        case 'bar' :
            break;
        default :
            break;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Break statement indented correctly.</th>
    <th>Invalid: Break statement not indented 4 spaces.</th>
   </tr>
   <tr>
<td>

    switch ($foo) {
        case 'bar':
            break;
    }

</td>
<td>

    switch ($foo) {
        case 'bar':
        break;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Comment marking intentional fall-through.</th>
    <th>Invalid: No comment marking intentional fall-through.</th>
   </tr>
   <tr>
<td>

    switch ($foo) {
        case 'bar':
        // no break
        default:
            break;
    }

</td>
<td>

    switch ($foo) {
        case 'bar':
        default:
            break;
    }

</td>
   </tr>
  </table>

## Closing Tag

Checks that the file does not end with a closing tag.
  <table>
   <tr>
    <th>Valid: Closing tag not used.</th>
    <th>Invalid: Closing tag used.</th>
   </tr>
   <tr>
<td>

    <?php
    echo 'Foo';


</td>
<td>

    <?php
    echo 'Foo';
    ?>

</td>
   </tr>
  </table>

## End File Newline

PHP Files should end with exactly one newline.

## Function Call Signature

Checks that the function call format is correct.
  <table>
   <tr>
    <th>Valid: Correct spacing is used around parentheses.</th>
    <th>Invalid: Incorrect spacing used, too much space around the parentheses.</th>
   </tr>
   <tr>
<td>

    foo($bar, $baz);

</td>
<td>

    foo ( $bar, $baz );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Correct number of spaces used for indent in a multi-line function call.</th>
    <th>Invalid: Incorrect number of spaces used for indent in a multi-line function call.</th>
   </tr>
   <tr>
<td>

    foo(
        $bar,
        $baz
    );

</td>
<td>

    foo(
      $bar,
          $baz
    );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Closing parenthesis for a multi-line function call is on a new line after the last parameter.</th>
    <th>Invalid: Closing parenthesis for a multi-line function call is not on a new line after the last parameter.</th>
   </tr>
   <tr>
<td>

    foo(
        $bar,
        $baz
    );

</td>
<td>

    foo(
        $bar,
        $baz);

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: The first argument of a multi-line function call is on a new line.</th>
    <th>Invalid: The first argument of a multi-line function call is not on a new line.</th>
   </tr>
   <tr>
<td>

    foo(
        $bar,
        $baz
    );

</td>
<td>

    foo($bar,
        $baz
    );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Only one argument per line in a multi-line function call.</th>
    <th>Invalid: Two or more arguments per line in a multi-line function call.</th>
   </tr>
   <tr>
<td>

    foo(
        $bar,
        $baz
    );

</td>
<td>

    foo(
        $bar, $baz
    );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: No blank lines in a multi-line function call.</th>
    <th>Invalid: Blank line in multi-line function call.</th>
   </tr>
   <tr>
<td>

    foo(
        $bar,
        $baz
    );

</td>
<td>

    foo(
        $bar,

        $baz
    );

</td>
   </tr>
  </table>

## Function Closing Brace

Checks that the closing brace of a function goes directly after the body.
  <table>
   <tr>
    <th>Valid: Closing brace directly follows the function body.</th>
    <th>Invalid: Blank line between the function body and the closing brace.</th>
   </tr>
   <tr>
<td>

    function foo()
    {
        echo 'foo';
    }

</td>
<td>

    function foo()
    {
        echo 'foo';

    }

</td>
   </tr>
  </table>

## Method Declarations

Method names should not be prefixed with an underscore to indicate visibility.  The static keyword, when present, should come after the visibility declaration, and the final and abstract keywords should come before.
  <table>
   <tr>
    <th>Valid: Correct method naming.</th>
    <th>Invalid: An underscore prefix used to indicate visibility.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        private function bar()
        {
        }
    }

</td>
<td>

    class Foo
    {
        private function _bar()
        {
        }
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Correct ordering of method prefixes.</th>
    <th>Invalid: `static` keyword used before visibility and final used after.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        final public static function bar()
        {
        }
    }

</td>
<td>

    class Foo
    {
        static public final function bar()
        {
        }
    }

</td>
   </tr>
  </table>

## Namespace Declarations

There must be one blank line after the namespace declaration.
  <table>
   <tr>
    <th>Valid: One blank line after the namespace declaration.</th>
    <th>Invalid: No blank line after the namespace declaration.</th>
   </tr>
   <tr>
<td>

    namespace \Foo\Bar;

    use \Baz;

</td>
<td>

    namespace \Foo\Bar;
    use \Baz;

</td>
   </tr>
  </table>

## Class Instantiation

When instantiating a new class, parenthesis MUST always be present even when there are no arguments passed to the constructor.
  <table>
   <tr>
    <th>Valid: Parenthesis used.</th>
    <th>Invalid: Parenthesis not used.</th>
   </tr>
   <tr>
<td>

    new Foo();

</td>
<td>

    new Foo;

</td>
   </tr>
  </table>

## Closing Brace

The closing brace of object-oriented constructs and functions must not be followed by any comment or statement on the same line.
  <table>
   <tr>
    <th>Valid: Closing brace is the last content on the line.</th>
    <th>Invalid: Comment or statement following the closing brace on the same line.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        // Class content.
    }

    function bar()
    {
        // Function content.
    }

</td>
<td>

    interface Foo2
    {
        // Interface content.
    } echo 'Hello!';

    function bar()
    {
        // Function content.
    } //end bar()

</td>
   </tr>
  </table>

## Opening Brace Space

The opening brace of an object-oriented construct must not be followed by a blank line.
  <table>
   <tr>
    <th>Valid: No blank lines after opening brace.</th>
    <th>Invalid: Blank line after opening brace.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        public function bar()
        {
            // Method content.
        }
    }

</td>
<td>

    class Foo
    {

        public function bar()
        {
            // Method content.
        }
    }

</td>
   </tr>
  </table>

## Boolean Operator Placement

Boolean operators between conditions in control structures must always be at the beginning or at the end of the line, not a mix of both.

This rule applies to if/else conditions, while loops and switch/match statements.
  <table>
   <tr>
    <th>Valid: Boolean operator between conditions consistently at the beginning of the line.</th>
    <th>Invalid: Mix of boolean operators at the beginning and the end of the line.</th>
   </tr>
   <tr>
<td>

    if (
        $expr1
        && $expr2
        && ($expr3
        || $expr4)
        && $expr5
    ) {
        // if body.
    }

</td>
<td>

    if (
        $expr1 &&
        ($expr2 || $expr3)
        && $expr4
    ) {
        // if body.
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Boolean operator between conditions consistently at the end of the line.</th>
    <th>Invalid: Mix of boolean operators at the beginning and the end of the line.</th>
   </tr>
   <tr>
<td>

    if (
        $expr1 ||
        ($expr2 || $expr3) &&
        $expr4
    ) {
        // if body.
    }

</td>
<td>

    match (
        $expr1
        && $expr2 ||
        $expr3
    ) {
        // structure body.
    };

</td>
   </tr>
  </table>

## Control Structure Spacing

Single line control structures must have no spaces after the condition opening parenthesis and before the condition closing parenthesis.
  <table>
   <tr>
    <th>Valid: No space after the opening parenthesis in a single-line condition.</th>
    <th>Invalid: Space after the opening parenthesis in a single-line condition.</th>
   </tr>
   <tr>
<td>

    if ($expr) {
    }

</td>
<td>

    if ( $expr) {
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: No space before the closing parenthesis in a single-line condition.</th>
    <th>Invalid: Space before the closing parenthesis in a single-line condition.</th>
   </tr>
   <tr>
<td>

    if ($expr) {
    }

</td>
<td>

    if ($expr ) {
    }

</td>
   </tr>
  </table>
The condition of the multi-line control structure must be indented once, placing the first expression on the next line after the opening parenthesis.
  <table>
   <tr>
    <th>Valid: First expression of a multi-line control structure condition block is on the line after the opening parenthesis.</th>
    <th>Invalid: First expression of a multi-line control structure condition block is on the same line as the opening parenthesis.</th>
   </tr>
   <tr>
<td>

    while (
        $expr1
        && $expr2
    ) {
    }

</td>
<td>

    while ($expr1
        && $expr2
    ) {
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Each line in a multi-line control structure condition block indented at least once. Default indentation is 4 spaces.</th>
    <th>Invalid: Some lines in a multi-line control structure condition block not indented correctly.</th>
   </tr>
   <tr>
<td>

    while (
        $expr1
        && $expr2
    ) {
    }

</td>
<td>

    while (
    $expr1
        && $expr2
      && $expr3
    ) {
    }

</td>
   </tr>
  </table>
The closing parenthesis of the multi-line control structure must be on the next line after the last condition, indented to the same level as the start of the control structure.
  <table>
   <tr>
    <th>Valid: The closing parenthesis of a multi-line control structure condition block is on the line after the last expression.</th>
    <th>Invalid: The closing parenthesis of a multi-line control structure condition block is on the same line as the last expression.</th>
   </tr>
   <tr>
<td>

    while (
        $expr1
        && $expr2
    ) {
    }

</td>
<td>

    while (
        $expr1
        && $expr2) {
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: The closing parenthesis of a multi-line control structure condition block is indented to the same level as start of the control structure.</th>
    <th>Invalid: The closing parenthesis of a multi-line control structure condition block is not indented to the same level as start of the control structure.</th>
   </tr>
   <tr>
<td>

    while (
        $expr1
        && $expr2
    ) {
    }

</td>
<td>

    while (
        $expr1
        && $expr2
      ) {
    }

</td>
   </tr>
  </table>

## Import Statement

Import use statements must not begin with a leading backslash.
  <table>
   <tr>
    <th>Valid: Import statement doesn't begin with a leading backslash.</th>
    <th>Invalid: Import statement begins with a leading backslash.</th>
   </tr>
   <tr>
<td>

    <?php

    use Vendor\Package\ClassA as A;

    class FooBar extends A
    {
        // Class content.
    }

</td>
<td>

    <?php

    use \Vendor\Package\ClassA as A;

    class FooBar extends A
    {
        // Class content.
    }

</td>
   </tr>
  </table>

## Open PHP Tag

When the opening &lt;?php tag is on the first line of the file, it must be on its own line with no other statements unless it is a file containing markup outside of PHP opening and closing tags.
  <table>
   <tr>
    <th>Valid: Opening PHP tag on a line by itself.</th>
    <th>Invalid: Opening PHP tag not on a line by itself.</th>
   </tr>
   <tr>
<td>

    <?php

    echo 'hi';

</td>
<td>

    <?php echo 'hi';

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Opening PHP tag not on a line by itself, but has markup outside the closing PHP tag.</th>
    <th>Invalid: Opening PHP tag not on a line by itself without any markup in the file.</th>
   </tr>
   <tr>
<td>

    <?php declare(strict_types=1); ?>
    <html>
    <body>
        <?php
            // ... additional PHP code ...
        ?>
    </body>
    </html>

</td>
<td>

    <?php declare(strict_types=1); ?>

</td>
   </tr>
  </table>

## Nullable Type Declarations Functions

In nullable type declarations there MUST NOT be a space between the question mark and the type.
  <table>
   <tr>
    <th>Valid: No whitespace used.</th>
    <th>Invalid: Superfluous whitespace used.</th>
   </tr>
   <tr>
<td>

    public function functionName(
        ?string $arg1,
        ?int $arg2
    ): ?string {
    }

</td>
<td>

    public function functionName(
        ? string $arg1,
        ? int $arg2
    ): ? string {
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: No unexpected characters.</th>
    <th>Invalid: Unexpected characters used.</th>
   </tr>
   <tr>
<td>

    public function foo(?int $arg): ?string
    {
    }

</td>
<td>

    public function bar(? /* comment */ int $arg): ?
        // nullable for a reason
        string
    {
    }

</td>
   </tr>
  </table>

## Return Type Declaration

For function and closure return type declarations, there must be one space after the colon followed by the type declaration, and no space before the colon.

The colon and the return type declaration have to be on the same line as the argument list closing parenthesis.
  <table>
   <tr>
    <th>Valid: A single space between the colon and type in a return type declaration.</th>
    <th>Invalid: No space between the colon and the type in a return type declaration.</th>
   </tr>
   <tr>
<td>

    $closure = function ( $arg ): string {
       // Closure body.
    };

</td>
<td>

    $closure = function ( $arg ):string {
       // Closure body.
    };

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: No space before the colon in a return type declaration.</th>
    <th>Invalid: One or more spaces before the colon in a return type declaration.</th>
   </tr>
   <tr>
<td>

    function someFunction( $arg ): string {
       // Function body.
    };

</td>
<td>

    function someFunction( $arg )   : string {
       // Function body.
    };

</td>
   </tr>
  </table>

## Short Form Type Keywords

Short form of type keywords MUST be used i.e. bool instead of boolean, int instead of integer etc.
  <table>
   <tr>
    <th>Valid: Short form type used.</th>
    <th>Invalid: Long form type type used.</th>
   </tr>
   <tr>
<td>

    $foo = (bool) $isValid;

</td>
<td>

    $foo = (boolean) $isValid;

</td>
   </tr>
  </table>

## Compound Namespace Depth

Compound namespaces with a depth of more than two MUST NOT be used.
  <table>
   <tr>
    <th>Valid: Max depth of 2.</th>
    <th>Invalid: Max depth of 3.</th>
   </tr>
   <tr>
<td>

    use Vendor\Package\SomeNamespace\{
        SubnamespaceOne\ClassA,
        SubnamespaceOne\ClassB,
        SubnamespaceTwo\ClassY,
        ClassZ,
    };

</td>
<td>

    use Vendor\Package\SomeNamespace\{
        SubnamespaceOne\AnotherNamespace\ClassA,
        SubnamespaceOne\ClassB,
        ClassZ,
    };

</td>
   </tr>
  </table>

## Operator Spacing

All binary and ternary (but not unary) operators MUST be preceded and followed by at least one space. This includes all arithmetic, comparison, assignment, bitwise, logical (excluding ! which is unary), string concatenation, type operators, trait operators (insteadof and as), and the single pipe operator (e.g. ExceptionType1 | ExceptionType2 $e).
  <table>
   <tr>
    <th>Valid: At least 1 space used.</th>
    <th>Invalid: No spacing used.</th>
   </tr>
   <tr>
<td>

    if ($a === $b) {
        $foo = $bar ?? $a ?? $b;
    } elseif ($a > $b) {
        $variable = $foo ? 'foo' : 'bar';
    }

</td>
<td>

    if ($a===$b) {
        $foo=$bar??$a??$b;
    } elseif ($a>$b) {
        $variable=$foo?'foo':'bar';
    }

</td>
   </tr>
  </table>

## Constant Visibility

Visibility must be declared on all class constants if your project PHP minimum version supports constant visibilities (PHP 7.1 or later).

The term &quot;class&quot; refers to all classes, interfaces, enums and traits.
  <table>
   <tr>
    <th>Valid: Constant visibility declared.</th>
    <th>Invalid: Constant visibility not declared.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        private const BAR = 'bar';
    }

</td>
<td>

    class Foo
    {
        const BAR = 'bar';
    }

</td>
   </tr>
  </table>

## Self Member Reference

The self keyword should be used instead of the current class name, should be lowercase, and should not have spaces before or after it.
  <table>
   <tr>
    <th>Valid: Lowercase self used.</th>
    <th>Invalid: Uppercase self used.</th>
   </tr>
   <tr>
<td>

    self::foo();

</td>
<td>

    SELF::foo();

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Correct spacing used.</th>
    <th>Invalid: Incorrect spacing used.</th>
   </tr>
   <tr>
<td>

    self::foo();

</td>
<td>

    self :: foo();

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Self used as reference.</th>
    <th>Invalid: Local class name used as reference.</th>
   </tr>
   <tr>
<td>

    class Foo
    {
        public static function bar()
        {
        }

        public static function baz()
        {
            self::bar();
        }
    }

</td>
<td>

    class Foo
    {
        public static function bar()
        {
        }

        public static function baz()
        {
            Foo::bar();
        }
    }

</td>
   </tr>
  </table>

## Valid Class Name

Class names must be written in Pascal case. This means that it starts with a capital letter, and the first letter of each word in the class name is capitalized. Only letters and numbers are allowed.
  <table>
   <tr>
    <th>Valid: Class name starts with a capital letter.</th>
    <th>Invalid: Class name does not start with a capital letter.</th>
   </tr>
   <tr>
<td>

    class PascalCaseStandard
    {
    }

</td>
<td>

    class notPascalCaseStandard
    {
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Class name contains only letters and numbers.</th>
    <th>Invalid: Class name contains underscores.</th>
   </tr>
   <tr>
<td>

    class PSR7Response
    {
    }

</td>
<td>

    class PSR7_Response
    {
    }

</td>
   </tr>
  </table>

## Block Comment

A block comment is a multi-line comment delimited by an opener &quot;/*&quot; and a closer &quot;*/&quot; which are each on their own line with the comment text in between.
  <table>
   <tr>
    <th>Valid: Uses a valid opener and closer.</th>
    <th>Invalid: Uses /** **/.</th>
   </tr>
   <tr>
<td>

    /*
        A block comment.
    */

</td>
<td>

    /**
        A block comment.
    **/

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Uses a valid opener and closer.</th>
    <th>Invalid: Uses multiple // or #.</th>
   </tr>
   <tr>
<td>

    /*
     * A block comment
     * with multiple lines.
     */

</td>
<td>

    // A block comment
    // with multiple lines.

    # A block comment
    # with multiple lines.

</td>
   </tr>
  </table>
Single line block comments are not allowed.
  <table>
   <tr>
    <th>Valid: Multi-line block comment.</th>
    <th>Invalid: Single line block comment.</th>
   </tr>
   <tr>
<td>

    /*
        A block comment.
    */

</td>
<td>

    /* A block comment. */

</td>
   </tr>
  </table>
A block comment should not be empty.
  <table>
   <tr>
    <th>Valid: A block comment with contents.</th>
    <th>Invalid: An empty block comment.</th>
   </tr>
   <tr>
<td>

    /*
        A block comment.
    */

</td>
<td>

    /*

    */

</td>
   </tr>
  </table>
Block comment text should start on a new line immediately after the opener.
  <table>
   <tr>
    <th>Valid: Text starts on a new line.</th>
    <th>Invalid: Text starts on the same line.</th>
   </tr>
   <tr>
<td>

    /*
        A block comment.
    */

</td>
<td>

    /* A block comment.
    */

</td>
   </tr>
  </table>
If there are no asterisks at the start of each line, the contents of the docblock should be indented by at least 4 spaces.
  <table>
   <tr>
    <th>Valid: Indented by at least 4 spaces.</th>
    <th>Invalid: Indented by less than 4 spaces.</th>
   </tr>
   <tr>
<td>

    /*
        A block comment
          with multiple lines.
        And a second paragraph.
    */

</td>
<td>

    /*
     A block comment
      with
       multiple lines.
    */

</td>
   </tr>
  </table>
If asterisks are used, they should be aligned.
  <table>
   <tr>
    <th>Valid: Asterisks are aligned.</th>
    <th>Invalid: Asterisks are not aligned.</th>
   </tr>
   <tr>
<td>

    /*
     * A block comment
     * with
     * multiple lines.
     */

</td>
<td>

    /*
     * A block comment
      * with
     * multiple lines.
    */

</td>
   </tr>
  </table>
A block comment should start with a capital letter.
  <table>
   <tr>
    <th>Valid: Starts with a capital letter.</th>
    <th>Invalid: Does not start with a capital letter.</th>
   </tr>
   <tr>
<td>

    /*
        A block comment.
    */

</td>
<td>

    /*
        a block comment.
    */

</td>
   </tr>
  </table>
The block comment closer should be on a new line.
  <table>
   <tr>
    <th>Valid: Closer is on a new line.</th>
    <th>Invalid: Closer is not on a new line.</th>
   </tr>
   <tr>
<td>

    /*
        A block comment.
    */

</td>
<td>

    /*
        A block comment. */

</td>
   </tr>
  </table>
If asterisks are used, the closer&#039;s asterisk should be aligned with these. Otherwise, the closer&#039;s asterisk should be aligned with the opener&#039;s slash.
  <table>
   <tr>
    <th>Valid: The closer's asterisk is aligned with other asterisks.</th>
    <th>Invalid: The closer's asterisk is not aligned with other asterisks.</th>
   </tr>
   <tr>
<td>

    /*
     * A block comment
     */

</td>
<td>

    /*
     * A block comment.
    */

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: The closer's asterisk is aligned with the opener's slash.</th>
    <th>Invalid: The closer's asterisk is not aligned with the opener's slash.</th>
   </tr>
   <tr>
<td>

    /*
        A block comment.
    */

</td>
<td>

    /*
        A block comment.
     */

</td>
   </tr>
  </table>
There should be an empty line after the block comment.
  <table>
   <tr>
    <th>Valid: An empty line after the comment.</th>
    <th>Invalid: No empty line after the comment.</th>
   </tr>
   <tr>
<td>

    /*
        A block comment.
    */

    echo 'Content';

</td>
<td>

    /*
        A block comment.
    */
    echo 'Content';

</td>
   </tr>
  </table>
A block comment immediately after a PHP open tag should not have a preceeding blank line.
  <table>
   <tr>
    <th>Valid: No blank line after an open tag.</th>
    <th>Invalid: A blank line after an open tag.</th>
   </tr>
   <tr>
<td>

    <?php
    /*
     * A block comment
     * with
     * multiple lines.
     */

</td>
<td>

    <?php

    /*
     * A block comment
     * with
     * multiple lines.
     */

</td>
   </tr>
  </table>

## Doc Comment Alignment

The asterisks in a doc comment should align, and there should be one space between the asterisk and tags.
  <table>
   <tr>
    <th>Valid: Asterisks are aligned.</th>
    <th>Invalid: Asterisks are not aligned.</th>
   </tr>
   <tr>
<td>

    /**
     * @see foo()
     */

</td>
<td>

    /**
      * @see foo()
    */

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: One space between asterisk and tag.</th>
    <th>Invalid: Incorrect spacing used.</th>
   </tr>
   <tr>
<td>

    /**
     * @see foo()
     */

</td>
<td>

    /**
     *  @see foo()
     */

</td>
   </tr>
  </table>

## Doc Comment Throws Tag

If a function throws any exceptions, they should be documented in a @throws tag.
  <table>
   <tr>
    <th>Valid: @throws tag used.</th>
    <th>Invalid: No @throws tag used for throwing function.</th>
   </tr>
   <tr>
<td>

    /**
     * @throws Exception all the time
     * @return void
     */
    function foo()
    {
        throw new Exception('Danger!');
    }

</td>
<td>

    /**
     * @return void
     */
    function foo()
    {
        throw new Exception('Danger!');
    }

</td>
   </tr>
  </table>

## Foreach Loop Declarations

There should be a space between each element of a foreach loop and the as keyword should be lowercase.
  <table>
   <tr>
    <th>Valid: Correct spacing used.</th>
    <th>Invalid: Invalid spacing used.</th>
   </tr>
   <tr>
<td>

    foreach ($foo as $bar => $baz) {
        echo $baz;
    }

</td>
<td>

    foreach ( $foo  as  $bar=>$baz ) {
        echo $baz;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Lowercase as keyword.</th>
    <th>Invalid: Uppercase as keyword.</th>
   </tr>
   <tr>
<td>

    foreach ($foo as $bar => $baz) {
        echo $baz;
    }

</td>
<td>

    foreach ($foo AS $bar => $baz) {
        echo $baz;
    }

</td>
   </tr>
  </table>

## For Loop Declarations

In a for loop declaration, there should be no space inside the brackets and there should be 0 spaces before and 1 space after semicolons.
  <table>
   <tr>
    <th>Valid: Correct spacing used.</th>
    <th>Invalid: Invalid spacing used inside brackets.</th>
   </tr>
   <tr>
<td>

    for ($i = 0; $i < 10; $i++) {
        echo $i;
    }

</td>
<td>

    for ( $i = 0; $i < 10; $i++ ) {
        echo $i;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Correct spacing used.</th>
    <th>Invalid: Invalid spacing used before semicolons.</th>
   </tr>
   <tr>
<td>

    for ($i = 0; $i < 10; $i++) {
        echo $i;
    }

</td>
<td>

    for ($i = 0 ; $i < 10 ; $i++) {
        echo $i;
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Correct spacing used.</th>
    <th>Invalid: Invalid spacing used after semicolons.</th>
   </tr>
   <tr>
<td>

    for ($i = 0; $i < 10; $i++) {
        echo $i;
    }

</td>
<td>

    for ($i = 0;$i < 10;$i++) {
        echo $i;
    }

</td>
   </tr>
  </table>

## Lowercase Control Structure Keywords

The PHP keywords if, else, elseif, foreach, for, do, switch, while, try, and catch should be lowercase.
  <table>
   <tr>
    <th>Valid: Lowercase if keyword.</th>
    <th>Invalid: Uppercase if keyword.</th>
   </tr>
   <tr>
<td>

    if ($foo) {
        $bar = true;
    }

</td>
<td>

    IF ($foo) {
        $bar = true;
    }

</td>
   </tr>
  </table>

## Lowercase Function Keywords

The PHP keywords function, public, private, protected, and static should be lowercase.
  <table>
   <tr>
    <th>Valid: Lowercase function keyword.</th>
    <th>Invalid: Uppercase function keyword.</th>
   </tr>
   <tr>
<td>

    function foo()
    {
        return true;
    }

</td>
<td>

    FUNCTION foo()
    {
        return true;
    }

</td>
   </tr>
  </table>

## Cast Whitespace

Casts should not have whitespace inside the parentheses.
  <table>
   <tr>
    <th>Valid: No spaces.</th>
    <th>Invalid: Whitespace used inside parentheses.</th>
   </tr>
   <tr>
<td>

    $foo = (int)'42';

</td>
<td>

    $foo = ( int )'42';

</td>
   </tr>
  </table>

## Scope Closing Brace

Indentation of a closing brace must match the indentation of the line containing the opening brace.
  <table>
   <tr>
    <th>Valid: Closing brace aligned with line containing opening brace.</th>
    <th>Invalid: Closing brace misaligned with line containing opening brace.</th>
   </tr>
   <tr>
<td>

    function foo()
    {
    }

    if (!class_exists('Foo')) {
        class Foo {
        }
    }

    <?php if ($something) { ?>
        <span>some output</span>
    <?php } ?>

</td>
<td>

    function foo()
    {
     }

    if (!class_exists('Foo')) {
        class Foo {
    }
        }

    <?php if ($something) { ?>
        <span>some output</span>
     <?php } ?>

</td>
   </tr>
  </table>
Closing brace must be on a line by itself.
  <table>
   <tr>
    <th>Valid: Close brace on its own line.</th>
    <th>Invalid: Close brace on a line containing other code.</th>
   </tr>
   <tr>
<td>

    enum Foo {
    }

</td>
<td>

    enum Foo {}

</td>
   </tr>
  </table>

## Scope Keyword Spacing

The PHP keywords static, public, private, and protected should have one space after them.
  <table>
   <tr>
    <th>Valid: A single space following the keywords.</th>
    <th>Invalid: Multiple spaces following the keywords.</th>
   </tr>
   <tr>
<td>

    public static function foo()
    {
    }

</td>
<td>

    public  static  function foo()
    {
    }

</td>
   </tr>
  </table>

## Semicolon Spacing

Semicolons should not have spaces before them.
  <table>
   <tr>
    <th>Valid: No space before the semicolon.</th>
    <th>Invalid: Space before the semicolon.</th>
   </tr>
   <tr>
<td>

    echo "hi";

</td>
<td>

    echo "hi" ;

</td>
   </tr>
  </table>

## Superfluous Whitespace

There should be no superfluous whitespace at the start of a file.
  <table>
   <tr>
    <th>Valid: No whitespace preceding first content in file.</th>
    <th>Invalid: Whitespace used before content in file.</th>
   </tr>
   <tr>
<td>

    <?php
    echo 'opening PHP tag at start of file';

</td>
<td>


    <?php
    echo 'whitespace before opening PHP tag';

</td>
   </tr>
  </table>
There should be no trailing whitespace at the end of lines.
  <table>
   <tr>
    <th>Valid: No whitespace found at end of line.</th>
    <th>Invalid: Whitespace found at end of line.</th>
   </tr>
   <tr>
<td>

    echo 'semicolon followed by new line char';

</td>
<td>

    echo 'trailing spaces after semicolon';

</td>
   </tr>
  </table>
There should be no consecutive blank lines in functions.
  <table>
   <tr>
    <th>Valid: Functions do not contain multiple empty lines in a row.</th>
    <th>Invalid: Functions contain multiple empty lines in a row.</th>
   </tr>
   <tr>
<td>

    function myFunction()
    {
        echo 'code here';

        echo 'code here';
    }

</td>
<td>

    function myFunction()
    {
        echo 'code here';


        echo 'code here';
    }

</td>
   </tr>
  </table>
There should be no superfluous whitespace after the final closing PHP tag in a file.
  <table>
   <tr>
    <th>Valid: A single new line appears after the last content in the file.</th>
    <th>Invalid: Multiple new lines appear after the last content in the file.</th>
   </tr>
   <tr>
<td>

    function myFunction()
    {
        echo 'Closing PHP tag, then';
        echo 'Single new line char, then EOF';
    }

    ?>


</td>
<td>

    function myFunction()
    {
        echo 'Closing PHP tag, then';
        echo 'Multiple new line chars, then EOF';
    }

    ?>



</td>
   </tr>
  </table>

## Array Indentation

The array closing bracket indentation should line up with the start of the content on the line containing the array opener.
  <table>
   <tr>
    <th>Valid: Closing bracket lined up correctly</th>
    <th>Invalid: Closing bracket lined up incorrectly</th>
   </tr>
   <tr>
<td>

    $args = array(
        'post_id' => 22,
    );

</td>
<td>

    $args = array(
        'post_id' => 22,
            );

</td>
   </tr>
  </table>
In multi-line arrays, array items should be indented by a 4-space tab for each level of nested array, so that the array visually matches its structure.
  <table>
   <tr>
    <th>Valid: Correctly indented array</th>
    <th>Invalid: Indented incorrectly; harder to read.</th>
   </tr>
   <tr>
<td>

    $args = array(
        'post_id'       => 22,
        'comment_count' => array(
            'value'   => 25,
            'compare' => '>=',
        ),
        'post_type' => array(
            'post',
            'page',
        ),
    );

</td>
<td>

    $args = array(
        'post_id'       => 22,
        'comment_count' => array(
        'value'   => 25,
        'compare' => '>=',
        ),
        'post_type' => array(
        'post',
        'page',
        ),
    );

</td>
   </tr>
  </table>
Subsequent lines in multiline array items should be indented at least as much as the first line of the array item.
For heredocs/nowdocs, this does not apply to the content of the heredoc/nowdoc or the closer, but it does apply to the comma separating the item from the next.
  <table>
   <tr>
    <th>Valid: Subsequent lines are indented correctly.</th>
    <th>Invalid: Subsequent items are indented before the first line item.</th>
   </tr>
   <tr>
<td>

    $args = array(
        'phrase' => 'start of phrase'
            . 'concatented additional phrase'
            . 'more text',
    );

</td>
<td>

    $args = array(
        'phrase' => 'start of phrase'
    . 'concatented additional phrase'
    . 'more text',
    );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Opener and comma after closer are indented correctly</th>
    <th>Invalid: Opener is aligned incorrectly to match the closer. The comma does not align correctly with the array indentation.</th>
   </tr>
   <tr>
<td>

    $text = array(
        <<<EOD
        start of phrase
        concatented additional phrase
        more text
    EOD
        ,
    );

</td>
<td>

    $text = array(
    <<<EOD
        start of phrase
        concatented additional phrase
        more text
    EOD
    ,
    );

</td>
   </tr>
  </table>

## Array Key Spacing Restrictions

When referring to array items, only include a space around the index if it is a variable or the key is concatenated.
  <table>
   <tr>
    <th>Valid: Correct spacing around the index keys</th>
    <th>Invalid: Incorrect spacing around the index keys</th>
   </tr>
   <tr>
<td>

    $post       = $posts[ $post_id ];
    $post_title = $post[ 'concatenated' . $title ];
    $post       = $posts[ HOME_PAGE ];
    $post       = $posts[123];
    $post_title = $post['post_title'];

</td>
<td>

    $post       = $posts[$post_id];
    $post_title = $post['concatenated' . $title ];
    $post       = $posts[HOME_PAGE];
    $post       = $posts[ 123 ];
    $post_title = $post[ 'post_title' ];

</td>
   </tr>
  </table>

## Array Multiple Statement Alignment

When declaring arrays, there should be one space on either side of a double arrow operator used to assign a value to a key.
  <table>
   <tr>
    <th>Valid: correct spacing between the key and value.</th>
    <th>Invalid: No or incorrect spacing between the key and value.</th>
   </tr>
   <tr>
<td>

    $foo = array( 'cat' => 22 );
    $bar = array( 'year' => $current_year );

</td>
<td>

    $foo = array( 'cat'=>22 );
    $bar = array( 'year'=>   $current_year );

</td>
   </tr>
  </table>
In the case of a block of related assignments, it is recommended to align the arrows to promote readability.
  <table>
   <tr>
    <th>Valid: Double arrow operators aligned</th>
    <th>Invalid: Not aligned; harder to read</th>
   </tr>
   <tr>
<td>

    $args = array(
        'cat'      => 22,
        'year'     => $current_year,
        'monthnum' => $current_month,
    );

</td>
<td>

    $args = array(
        'cat' => 22,
        'year' => $current_year,
        'monthnum' => $current_month,
    );

</td>
   </tr>
  </table>

## Escaped Not Translated

Text intended for translation needs to be wrapped in a localization function call.
This sniff will help you find instances where text is escaped for output, but no localization function is called, even though an (unexpected) text domain argument is passed to the escape function.
  <table>
   <tr>
    <th>Valid: esc_html__() used to translate and escape.</th>
    <th>Invalid: esc_html() used to only escape a string intended to be translated as well.</th>
   </tr>
   <tr>
<td>

    echo esc_html__( 'text', 'domain' );

</td>
<td>

    echo esc_html( 'text', 'domain' );

</td>
   </tr>
  </table>

## Current Time Timestamp

Don&#039;t use current_time() to get a timestamp as it doesn&#039;t produce a Unix (UTC) timestamp, but a &quot;WordPress timestamp&quot;, i.e. a Unix timestamp with current timezone offset.
  <table>
   <tr>
    <th>Valid: using time() to get a Unix (UTC) timestamp.</th>
    <th>Invalid: using current_time() to get a Unix (UTC) timestamp.</th>
   </tr>
   <tr>
<td>

    $timestamp = time();

</td>
<td>

    $timestamp = current_time( 'timestamp', true );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: using current_time() with a non-timestamp format.</th>
    <th>Invalid: using current_time() to get a timezone corrected timestamp.</th>
   </tr>
   <tr>
<td>

    $timestamp = current_time( 'Y-m-d' );

</td>
<td>

    $timestamp = current_time( 'U', false );

</td>
   </tr>
  </table>

## Restricted Date and Time Functions

The restricted functions date_default_timezone_set() and date() should not be used.
Using the PHP native date_default_timezone_set() function isn&#039;t allowed, because WordPress Core needs the default time zone to be set to UTC for timezone calculations using the WP Core API to work correctly.
  <table>
   <tr>
    <th>Valid: Using DateTime object.</th>
    <th>Invalid: Using date_default_timezone_set().</th>
   </tr>
   <tr>
<td>

    $date = new DateTime();
    $date->setTimezone(
        new DateTimeZone( 'Europe/Amsterdam' )
    );

</td>
<td>

    date_default_timezone_set( 'Europe/Amsterdam' );

</td>
   </tr>
  </table>
Using the PHP native date() function isn&#039;t allowed, as it is affected by runtime timezone changes which can cause the date/time to be incorrectly displayed. Use gmdate() instead.
  <table>
   <tr>
    <th>Valid: Using gmdate().</th>
    <th>Invalid: Using date().</th>
   </tr>
   <tr>
<td>

    $last_updated = gmdate(
        'Y-m-d\TH:i:s',
        strtotime( $plugin['last_updated'] )
    );

</td>
<td>

    $last_updated = date(
        'Y-m-d\TH:i:s',
        strtotime( $plugin['last_updated'] )
    );

</td>
   </tr>
  </table>

## Prefix All Globals

All globals terms must be prefixed with a theme/plugin specific term. Global terms include Namespace names, Class/Interface/Trait/Enum names (when not namespaced), Functions (when not namespaced or in an OO structure), Constants/Variable names declared in the global namespace, and Hook names.

A prefix must be distinct and unique to the plugin/theme, in order to prevent potential conflicts with other plugins/themes and with WordPress itself.

The prefix used for a plugin/theme may be chosen by the developers and should be defined in a custom PHPCS ruleset to allow for this sniff to verify that the prefix is consistently used.
Prefixes will be treated in a case-insensitive manner.
https://github.com/WordPress/WordPress-Coding-Standards/wiki/Customizable-sniff-properties#naming-conventions-prefix-everything-in-the-global-namespace
  <table>
   <tr>
    <th>Valid: Using the prefix ECPT_</th>
    <th>Invalid: non-prefixed code</th>
   </tr>
   <tr>
<td>

    define( 'ECPT_VERSION', '1.0' );

    $ecpt_admin = new ECPT_Admin_Page();

    class ECPT_Admin_Page {}

    apply_filter(
        'ecpt_modify_content',
        $ecpt_content
    );

</td>
<td>

    define( 'PLUGIN_VERSION', '1.0' );

    $admin = new Admin_Page();

    class Admin_Page {}

    apply_filter(
        'modify_content',
        $content
    );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: Using the prefix ECPT_ in namespaced code</th>
    <th>Invalid: using a non-prefixed namespace</th>
   </tr>
   <tr>
<td>

    namespace ECPT_Plugin\Admin;

    // Constants declared using `const` will
    // be namespaced and therefore prefixed.
    const VERSION = 1.0;

    // A class declared in a (prefixed) namespace
    // is automatically prefixed.
    class Admin_Page {}

    // Variables in a namespaced file are not
    // namespaced, so still need prefixing.
    $ecpt_admin = new Admin_Page();

    // Hook names are not subject to namespacing.
    apply_filter(
        'ecpt_modify_content',
        $ecpt_content
    );

</td>
<td>

    namespace Admin;

    // As the namespace is not prefixed, this
    // is still bad.
    const VERSION = 1.0;

    // As the namespace is not prefixed, this
    // is still bad.
    class Admin_Page {}

</td>
   </tr>
  </table>
Using prefixes reserved for WordPress is not permitted, even if WordPress is not currently using the prefix (yet).
  <table>
   <tr>
    <th>Valid: Using the prefix mycoolplugin_</th>
    <th>Invalid: Using a WordPress reserved prefix wp_</th>
   </tr>
   <tr>
<td>

    function mycoolplugin_save_post() {}

</td>
<td>

    function wp_save_post() {}

</td>
   </tr>
  </table>
Prefixes must have a minimum length of three character to be considered valid, as many plugins and themes share the same initials.
  <table>
   <tr>
    <th>Valid: Using the distinct prefix MyPlugin</th>
    <th>Invalid: Using a two-letter prefix My</th>
   </tr>
   <tr>
<td>

    interface MyPluginIsCool {}

</td>
<td>

    interface My {}

</td>
   </tr>
  </table>

## Valid Hook Name

Use lowercase letters in action and filter names. Separate words using underscores.
  <table>
   <tr>
    <th>Valid: lowercase hook name.</th>
    <th>Invalid: mixed case hook name.</th>
   </tr>
   <tr>
<td>

    do_action( 'prefix_hook_name', $var );

</td>
<td>

    do_action( 'Prefix_Hook_NAME', $var );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: words separated by underscores.</th>
    <th>Invalid: using non-underscore characters to separate words.</th>
   </tr>
   <tr>
<td>

    apply_filters( 'prefix_hook_name', $var );

</td>
<td>

    apply_filters( 'prefix\hook-name', $var );

</td>
   </tr>
  </table>

## Valid Post Type Slug

The post type slug used in register_post_type() must be between 1 and 20 characters.
  <table>
   <tr>
    <th>Valid: short post type slug.</th>
    <th>Invalid: too long post type slug.</th>
   </tr>
   <tr>
<td>

    register_post_type(
        'my_short_slug',
        array()
    );

</td>
<td>

    register_post_type(
        'my_own_post_type_too_long',
        array()
    );

</td>
   </tr>
  </table>
The post type slug used in register_post_type() can only contain lowercase alphanumeric characters, dashes and underscores.
  <table>
   <tr>
    <th>Valid: no special characters in post type slug.</th>
    <th>Invalid: invalid characters in post type slug.</th>
   </tr>
   <tr>
<td>

    register_post_type(
        'my_post_type_slug',
        array()
    );

</td>
<td>

    register_post_type(
        'my/post/type/slug',
        array()
    );

</td>
   </tr>
  </table>
One should be careful with passing dynamic slug names to &quot;register_post_type()&quot;, as the slug may become too long and could contain invalid characters.
  <table>
   <tr>
    <th>Valid: static post type slug.</th>
    <th>Invalid: dynamic post type slug.</th>
   </tr>
   <tr>
<td>

    register_post_type(
        'my_post_active',
        array()
    );

</td>
<td>

    register_post_type(
        "my_post_{$status}",
        array()
    );

</td>
   </tr>
  </table>
The post type slug used in register_post_type() can not use reserved keywords, such as the ones used by WordPress itself.
  <table>
   <tr>
    <th>Valid: prefixed post slug.</th>
    <th>Invalid: using a reserved keyword as slug.</th>
   </tr>
   <tr>
<td>

    register_post_type(
        'prefixed_author',
        array()
    );

</td>
<td>

    register_post_type(
        'author',
        array()
    );

</td>
   </tr>
  </table>
The post type slug used in register_post_type() can not use reserved prefixes, such as &#039;wp_&#039;, which is used by WordPress itself.
  <table>
   <tr>
    <th>Valid: custom prefix post slug.</th>
    <th>Invalid: using a reserved prefix.</th>
   </tr>
   <tr>
<td>

    register_post_type(
        'prefixed_author',
        array()
    );

</td>
<td>

    register_post_type(
        'wp_author',
        array()
    );

</td>
   </tr>
  </table>

## Detect Use Of `ini_set()

Using ini_set() and similar functions for altering PHP settings at runtime is discouraged. Changing runtime configuration might break other plugins and themes, and even WordPress itself.
  <table>
   <tr>
    <th>Valid: ini_set() for a possibly breaking setting.</th>
    <th>Invalid: ini_set() for a possibly breaking setting.</th>
   </tr>
   <tr>
<td>

    // ini_set() should not be used.

</td>
<td>

    ini_set( 'short_open_tag', 'off' );

</td>
   </tr>
  </table>
For some configuration values there are alternative ways available - either via WordPress native functionality of via standard PHP - to achieve the same without the risk of breaking interoperability. These alternatives are preferred.
  <table>
   <tr>
    <th>Valid: WordPress functional alternative.</th>
    <th>Invalid: ini_set() to alter memory limits.</th>
   </tr>
   <tr>
<td>

    wp_raise_memory_limit();

</td>
<td>

    ini_set( 'memory_limit', '256M' );

</td>
   </tr>
  </table>

## Strict In Array Syntax

When using functions which compare a value to a range of values in an array, make sure a strict comparison is executed.

Typically, this rule verifies function calls to the PHP native `in_array()`, `array_search()` and `array_keys()` functions pass the `$strict` parameter.
  <table>
   <tr>
    <th>Valid: calling in_array() with the $strict parameter set to `true`.</th>
    <th>Invalid: calling in_array() without passing the $strict parameter.</th>
   </tr>
   <tr>
<td>

    $array = array( '1', 1, true );
    if ( in_array( $value, $array, true ) ) {}

</td>
<td>

    $array = array( '1', 1, true );
    if ( in_array( $value, $array ) ) {}

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: calling array_search() with the $strict parameter set to `true`.</th>
    <th>Invalid: calling array_search() without passing the $strict parameter.</th>
   </tr>
   <tr>
<td>

    $key = array_search( 1, $array, true );

</td>
<td>

    $key = array_search( 1, $array );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: calling array_keys() with a $search_value and the $strict parameter set to `true`.</th>
    <th>Invalid: calling array_keys() with a $search_value without passing the $strict parameter.</th>
   </tr>
   <tr>
<td>

    $keys = array_keys( $array, $key, true );

</td>
<td>

    $keys = array_keys( $array, $key );

</td>
   </tr>
  </table>

## Yoda Conditions

When doing logical comparisons involving variables, the variable must be placed on the right side. All constants, literals, and function calls must be placed on the left side. If neither side is a variable, the order is unimportant.
  <table>
   <tr>
    <th>Valid: The variable is placed on the right</th>
    <th>Invalid: The variable has been placed on the left</th>
   </tr>
   <tr>
<td>

    if ( true === $the_force ) {
        $victorious = you_will( $be );
    }

</td>
<td>

    if ( $the_force === false ) {
        $victorious = you_will_not( $be );
    }

</td>
   </tr>
  </table>

## Safe Redirect

wp_safe_redirect() should be used whenever possible to prevent open redirect vulnerabilities. One of the main uses of an open redirect vulnerability is to make phishing attacks more credible. In this case the user sees your (trusted) domain and might get redirected to an attacker controlled website aimed at stealing private information.
  <table>
   <tr>
    <th>Valid: Redirect can only go to allowed domains.</th>
    <th>Invalid: Unsafe redirect, can be abused.</th>
   </tr>
   <tr>
<td>

    wp_safe_redirect( $location );

</td>
<td>

    wp_redirect( $location );

</td>
   </tr>
  </table>

## Cast Structure Spacing

A type cast should be preceded by whitespace.
There is only one exception to this rule: when the cast is preceded by the spread operator there should be no space between the spread operator and the cast.
  <table>
   <tr>
    <th>Valid: space before typecast.</th>
    <th>Invalid: no space before typecast.</th>
   </tr>
   <tr>
<td>

    $a = (int) '420';

    // No space between spread operator and cast.
    $a = function_call( ...(array) $mixed );

</td>
<td>

    $a =(int) '420';

</td>
   </tr>
  </table>

## Control Structure Spacing

Put one space on both sides of the opening and closing parentheses of control structures.
  <table>
   <tr>
    <th>Valid: One space on each side of the open and close parentheses.</th>
    <th>Invalid: Incorrect spacing around the open and close parentheses.</th>
   </tr>
   <tr>
<td>

    while ( have_posts() ) {}

    // For multi-line conditions,
    // a new line is also accepted.
    if ( true === $condition
        && $count > 10
    ) {}

</td>
<td>

    // No spaces.
    while(have_posts()){}

    // Too much space.
    while   (   have_posts()   )   {}

</td>
   </tr>
  </table>
The open brace for the control structure must be on the same line as the close parenthesis or the control structure keyword, with one space between them.
  <table>
   <tr>
    <th>Valid: Open brace on the same line as the keyword/close parenthesis.</th>
    <th>Invalid: Open brace on a different line than the keyword/close parenthesis.</th>
   </tr>
   <tr>
<td>

    try {
        // Do something.
    } catch (
        ExceptionA | ExceptionB $e
    ) {
    }

</td>
<td>

    try
    {
        // Do something.
    } catch ( Exception $e )
    (
    }

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: One space between the keyword/close parenthesis and the open brace.</th>
    <th>Invalid: Too much space between the keyword/close parenthesis and the open brace.</th>
   </tr>
   <tr>
<td>

    if ( $condition ) {
        // Do something.
    }

</td>
<td>

    if ( $condition )     {
        // Do something.
    }

</td>
   </tr>
  </table>
When using alternative control structure syntaxes, there should be one space between the close parenthesis and the colon opening the control structure body.
  <table>
   <tr>
    <th>Valid: One space before the colon.</th>
    <th>Invalid: No space before the colon.</th>
   </tr>
   <tr>
<td>

    foreach ( $types as $type ) :
        // Do something.
    endforeach;

</td>
<td>

    foreach ( $types as $type ):
        // Do something.
    endforeach;

</td>
   </tr>
  </table>
When a control structure is nested in another control structure and the closing braces follow each other, there should be no blank line between the closing braces.
  <table>
   <tr>
    <th>Valid: No blank line between the consecutive close braces.</th>
    <th>Invalid: Blank line(s) between the consecutive close braces.</th>
   </tr>
   <tr>
<td>

    if ( $a === $b ) {
        if ( $something ) {
            // Do something.
        }
    }

</td>
<td>

    if ( $a === $b ) {
        if ( $something ) {
            // Do something.
        }


    }

</td>
   </tr>
  </table>
[Optional, turned off by default]
There should be no blank line(s) at the start or end of the control structure body.
  <table>
   <tr>
    <th>Valid: No blank lines at the start or end of the control structure body.</th>
    <th>Invalid: Blank line(s) at the start and end of the control structure body.</th>
   </tr>
   <tr>
<td>

    if ( $a === $b ) {
        echo $a;
    }

</td>
<td>

    if ( $a === $b ) {


        echo $a;


    }

</td>
   </tr>
  </table>

## Object Operator Spacing

The object operators (-&gt;, ?-&gt;, ::) should not have any spaces around them, though new lines are allowed except for use with the `::class` constant.
  <table>
   <tr>
    <th>Valid: No spaces around the object operator.</th>
    <th>Invalid: Whitespace surrounding the object operator.</th>
   </tr>
   <tr>
<td>

    $foo->bar();

</td>
<td>

    $foo  ?->  bar();

</td>
   </tr>
  </table>

## Operator Spacing

Always put one space on both sides of logical, comparison and concatenation operators.
Always put one space after an assignment operator.
  <table>
   <tr>
    <th>Valid: one space before and after an operator.</th>
    <th>Invalid: too much/little space.</th>
   </tr>
   <tr>
<td>

    if ( $a === $b && $b === $c ) {}
    if ( ! $var ) {}

</td>
<td>

    // Too much space.
    if ( $a === $b   &&   $b ===      $c ) {}
    if (  ! $var ) {}

    // Too little space.
    if ( $a===$b &&$b ===$c ) {}
    if ( !$var ) {}

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: a new line instead of a space is okay too.</th>
    <th>Invalid: too much space after operator on new line.</th>
   </tr>
   <tr>
<td>

    if ( $a === $b
        && $b === $c
    ) {}

</td>
<td>

    if ( $a === $b
        &&     $b === $c
    ) {}

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: one space after assignment operator.</th>
    <th>Invalid: too much/little space after assignment operator.</th>
   </tr>
   <tr>
<td>

    $a   = 'foo';
    $all = 'foobar';

</td>
<td>

    $a   =     'foo';
    $all ='foobar';

</td>
   </tr>
  </table>

## Capabilities

Capabilities passed should be valid capabilities (custom capabilities can be added in the ruleset).
  <table>
   <tr>
    <th>Valid: a WP native or registered custom user capability is used.</th>
    <th>Invalid: unknown/unsupported user capability is used.</th>
   </tr>
   <tr>
<td>

    if ( author_can( $post, 'manage_sites' ) ) { }

</td>
<td>

    map_meta_cap( 'manage_site', $user->ID );

</td>
   </tr>
  </table>
Always use user capabilities instead of roles.
  <table>
   <tr>
    <th>Valid: user capability is used.</th>
    <th>Invalid: user role is used instead of a capability.</th>
   </tr>
   <tr>
<td>

    add_options_page(
        esc_html__( 'Options', 'textdomain' ),
        esc_html__( 'Options', 'textdomain' ),
        'manage_options',
        'options_page_slug',
        'project_options_page_cb'
    );

</td>
<td>

    add_options_page(
        esc_html__( 'Options', 'textdomain' ),
        esc_html__( 'Options', 'textdomain' ),
        'author',
        'options_page_slug',
        'project_options_page_cb'
    );

</td>
   </tr>
  </table>
Don&#039;t use deprecated capabilities.
  <table>
   <tr>
    <th>Valid: a WP native or registered custom user capability is used.</th>
    <th>Invalid: deprecated user capability is used.</th>
   </tr>
   <tr>
<td>

    if ( author_can( $post, 'read' ) ) { }

</td>
<td>

    if ( author_can( $post, 'level_6' ) ) { }

</td>
   </tr>
  </table>

## Capital P Dangit

The correct spelling of &quot;WordPress&quot; should be used in text strings, comments and object names.

In select cases, when part of an identifier or a URL, WordPress does not have to be capitalized.
  <table>
   <tr>
    <th>Valid: WordPress is correctly capitalized.</th>
    <th>Invalid: WordPress is not correctly capitalized.</th>
   </tr>
   <tr>
<td>

    class WordPress_Example {

        /**
         * This function is about WordPress.
         */
        public function explain() {
            echo 'This is an explanation
                about WordPress.';
        }
    }

</td>
<td>

    class Wordpress_Example {

        /**
         * This function is about Wordpress.
         */
        public function explain() {
            echo 'This is an explanation
                about wordpress.';
        }
    }

</td>
   </tr>
  </table>

## Class Name Case

It is strongly recommended to refer to WP native classes by their properly cased name.
  <table>
   <tr>
    <th>Valid: reference to a WordPress native class name using the correct case.</th>
    <th>Invalid: reference to a WordPress native class name not using the correct case.</th>
   </tr>
   <tr>
<td>

    $obj = new WP_Query;

</td>
<td>

    $obj = new wp_query;

</td>
   </tr>
  </table>

## Cron Interval

Cron schedules running more often than once every 15 minutes are discouraged. Crons running that frequently can negatively impact the performance of a site.
  <table>
   <tr>
    <th>Valid: Cron schedule is created to run once every hour.</th>
    <th>Invalid: Cron schedule is added to run more than once per 15 minutes.</th>
   </tr>
   <tr>
<td>

    function adjust_schedules( $schedules ) {
        $schedules['every_hour'] = array(
            'interval' => HOUR_IN_SECONDS,
            'display'  => __( 'Every hour' )
        );
        return $schedules;
    }

    add_filter(
        'cron_schedules',
        'adjust_schedules'
    );

</td>
<td>

    function adjust_schedules( $schedules ) {
        $schedules['every_9_mins'] = array(
            'interval' => 9 * 60,
            'display'  => __( 'Every 9 minutes' )
        );
        return $schedules;
    }

    add_filter(
        'cron_schedules',
        'adjust_schedules'
    );

</td>
   </tr>
  </table>

## Deprecated Classes

Please refrain from using deprecated WordPress classes.
  <table>
   <tr>
    <th>Valid: use of a current (non-deprecated) class.</th>
    <th>Invalid: use of a deprecated class.</th>
   </tr>
   <tr>
<td>

    $a = new WP_User_Query();

</td>
<td>

    $a = new WP_User_Search(); // Deprecated WP 3.1.

</td>
   </tr>
  </table>

## Deprecated Functions

Please refrain from using deprecated WordPress functions.
  <table>
   <tr>
    <th>Valid: use of a current (non-deprecated) function.</th>
    <th>Invalid: use of a deprecated function.</th>
   </tr>
   <tr>
<td>

    $sites = get_sites();

</td>
<td>

    $sites = wp_get_sites(); // Deprecated WP 4.6.

</td>
   </tr>
  </table>

## Deprecated Function Parameters

Please refrain from passing deprecated WordPress function parameters.
In case, you need to pass an optional parameter positioned *after* the deprecated parameter, only ever pass the default value.
  <table>
   <tr>
    <th>Valid: not passing a deprecated parameter.</th>
    <th>Invalid: passing a deprecated parameter.</th>
   </tr>
   <tr>
<td>

    // First - and only - parameter deprecated.
    get_the_author();

</td>
<td>

    // First - and only - parameter deprecated.
    get_the_author( $string );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: passing default value for a deprecated parameter.</th>
    <th>Invalid: not passing the default value for a deprecated parameter.</th>
   </tr>
   <tr>
<td>

    // Third parameter deprecated in WP 2.3.0.
    add_option( 'option_name', 123, '', 'yes' );

</td>
<td>

    // Third parameter deprecated in WP 2.3.0.
    add_option( 'my_name', 123, 'oops', 'yes' );

</td>
   </tr>
  </table>

## Deprecated Function Parameter Values

Please refrain from using deprecated WordPress function parameter values.
  <table>
   <tr>
    <th>Valid: passing a valid function parameter value.</th>
    <th>Invalid: passing a deprecated function parameter value.</th>
   </tr>
   <tr>
<td>

    bloginfo( 'url' );

</td>
<td>

    bloginfo ( 'home' ); // Deprecated WP 2.2.0.

</td>
   </tr>
  </table>

## Enqueued Resource Parameters

The resource version must be set, to prevent the browser from using an outdated, cached version after the resource has been updated.
  <table>
   <tr>
    <th>Valid: Resource has a version number.</th>
    <th>Invalid: Resource has no version number set.</th>
   </tr>
   <tr>
<td>

    wp_register_style(
        'someStyle-css',
        $path_to_local_file,
        array(),
        '1.0.0'
    );

</td>
<td>

    wp_register_style(
        'someStyle-css',
        $path_to_local_file,
        array()
    );

</td>
   </tr>
  </table>
The resource version must not be `false`. When this value is set to `false`, the WordPress Core version number will be used, which is incorrect for themes and plugins.
  <table>
   <tr>
    <th>Valid: Resource has a version number.</th>
    <th>Invalid: Resource has version set to false.</th>
   </tr>
   <tr>
<td>

    wp_enqueue_script(
        'someScript-js',
        $path_to_local_file,
        array( 'jquery' ),
        '1.0.0',
        true
    );

</td>
<td>

    wp_enqueue_script(
        'someScript-js',
        $path_to_local_file,
        array( 'jquery' ),
        false,
        true
    );

</td>
   </tr>
  </table>
You must explicitly set a JavaScript resource to load in either the header or the footer of your page. It is recommended to load these resources in the footer by setting the `$in_footer` parameter to `true`.

Loading scripts in the header blocks parsing of the page and has a negative impact on load times. However, loading in the footer may break compatibility when other scripts rely on the resource to be available at any time.
In that case, you can pass `false` to make it explicit that the script should be loaded in the header of the page.
  <table>
   <tr>
    <th>Valid: The resource is specified to load in the footer.</th>
    <th>Invalid: The location to load this resource is not explicitly set.</th>
   </tr>
   <tr>
<td>

    wp_register_script(
        'someScript-js',
        $path_to_local_file,
        array( 'jquery' ),
        '1.0.0',
        true
    );

</td>
<td>

    wp_register_script(
        'someScript-js',
        $path_to_local_file,
        array( 'jquery' ),
        '1.0.0'
    );

</td>
   </tr>
  </table>

## Enqueued Resources

Scripts must be registered/enqueued via wp_enqueue_script().
  <table>
   <tr>
    <th>Valid: Script registered and enqueued correctly.</th>
    <th>Invalid: Script is directly embedded in HTML.</th>
   </tr>
   <tr>
<td>

    wp_enqueue_script(
        'someScript-js',
        $path_to_file,
        array( 'jquery' ),
        '1.0.0',
        true
    );

</td>
<td>

    printf(
        '<script src="%s"></script>',
        esc_url( $path_to_file )
    );

</td>
   </tr>
  </table>
Stylesheets must be registered/enqueued via wp_enqueue_style().
  <table>
   <tr>
    <th>Valid: Stylesheet registered and enqueued correctly.</th>
    <th>Invalid: Stylesheet is directly embedded in HTML.</th>
   </tr>
   <tr>
<td>

    wp_enqueue_style(
        'style-name',
        $path_to_file,
        array(),
        '1.0.0'
    );

</td>
<td>

    printf(
        '<link rel="stylesheet" href="%s" />',
        esc_url( $path_to_file )
    );

</td>
   </tr>
  </table>

## High Posts Per Page Limit

Using &quot;posts_per_page&quot; or &quot;numberposts&quot; with the value set to an high number opens up the potential for making requests slow if the query ends up querying thousands of posts.

You should always fetch the lowest number possible that still gives you the number of results you find acceptable.
  <table>
   <tr>
    <th>Valid: posts_per_page is not over limit (default 100).</th>
    <th>Invalid: posts_per_page is over limit (default 100).</th>
   </tr>
   <tr>
<td>

    $args = array(
        'posts_per_page' => -1,
    );
    $args = array(
        'posts_per_page' => 100,
    );
    $args = array(
        'posts_per_page' => '10',
    );

    $query_args['posts_per_page'] = 100;

    _query_posts( 'nopaging=1&posts_per_page=50' );

</td>
<td>

    $args = array(
        'posts_per_page' => 101,
    );

    $query_args['posts_per_page'] = 200;

    _query_posts( 'nopaging=1&posts_per_page=999' );

</td>
   </tr>
  </table>
  <table>
   <tr>
    <th>Valid: numberposts is not over limit (default 100).</th>
    <th>Invalid: numberposts is over limit (default 100).</th>
   </tr>
   <tr>
<td>

    $args = array(
        'numberposts' => -1,
    );
    $args = array(
        'numberposts' => 100,
    );
    $args = array(
        'numberposts' => '10',
    );

    $query_args['numberposts'] = '-1';

    _query_posts( 'numberposts=50' );

</td>
<td>

    $args = array(
        'numberposts' => 101,
    );

    $query_args['numberposts'] = '200';

    _query_posts( 'numberposts=999' );

</td>
   </tr>
  </table>

Documentation generated on Mon, 28 Apr 2025 13:31:17 -0700 by [PHP_CodeSniffer 3.12.2](https://github.com/PHPCSStandards/PHP_CodeSniffer)
