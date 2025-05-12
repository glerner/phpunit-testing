# Debugging with AI

This guide provides strategies for effectively using AI assistants like Claude or ChatGPT to debug WordPress plugin issues.

## Table of Contents

1. [Introduction](#introduction)
2. [Preparing Your Debugging Session](#preparing-your-debugging-session)
3. [Crafting Effective Debugging Prompts](#crafting-effective-debugging-prompts)
4. [Methodical Debugging Approaches](#methodical-debugging-approaches)
5. [Common Pitfalls to Avoid](#common-pitfalls-to-avoid)
6. [Example Debugging Scenarios](#example-debugging-scenarios)
7. [Advanced Techniques](#advanced-techniques)

## Introduction

AI assistants can be powerful debugging partners when used correctly. They excel at:

- Analyzing error messages and suggesting potential causes
- Identifying patterns in code that might lead to bugs
- Suggesting systematic debugging approaches
- Helping isolate variables in complex systems

However, the quality of AI debugging assistance depends heavily on how you frame your questions and what information you provide.

## Preparing Your Debugging Session

Before engaging an AI for debugging help, gather these essential details:

1. **Exact error messages**: Copy the complete error output, including line numbers and context
2. **Relevant code snippets**: Include the function or class where the error occurs and any calling code
3. **Environment information**: WordPress version, PHP version, relevant plugin versions
4. **Steps to reproduce**: Clear steps that consistently trigger the error
5. **Recent changes**: Code or configuration changes made before the issue appeared

For WordPress plugin testing specifically, also include:
- Test configuration details (wp-tests-config.php settings)
- Database configuration
- Local development environment details (Lando, Docker, etc.)
- Confirmation that code has been synced to the WordPress installation

**Always sync before testing**: One of the most common sources of subtle bugs is testing against outdated code. Always sync your code to the WordPress installation before running tests, even when it doesn't seem necessary for your specific test case. This prevents confusing situations where your tests are running against different code than what you're editing.

## Crafting Effective Debugging Prompts

When asking an AI for debugging help, structure your prompt like this:

```
You are an excellent PHP and WordPress debugger, with full knowledge of the debugging approaches taught by experienced systems programmers and software engineers, including these fundamental skills:

1. OBSERVE WITHOUT ASSUMPTIONS: Always examine the actual error messages, logs, and code behavior without making assumptions about the cause.

2. ISOLATE VARIABLES: Change only one thing at a time to identify the exact source of an issue.

3. COMPARE WORKING VS. NON-WORKING SCENARIOS: When something works manually but fails in automation, methodically identify the differences.

4. MINIMAL REPRODUCTION: Create the simplest possible test case that demonstrates the issue.

5. TRACE EXECUTION PATH: Follow the exact execution path through the code, including function calls, variable transformations, and control flow.

6. VERIFY ENVIRONMENT CONTEXT: Check for differences in environment variables, permissions, paths, and runtime contexts.

7. BINARY SEARCH DEBUGGING: For complex issues, use a divide-and-conquer approach to narrow down the problem area.

8. COMMAND LINE TESTING: Test components individually via command line before testing them together.

9. EXAMINE ACTUAL VALUES: Always inspect the actual values of variables at runtime, not what you think they should be.

10. INCREMENTAL FIXES: Make small, targeted changes and test after each change.

11. DOCUMENT FINDINGS: Record what you've learned about the system behavior for future reference.

12. CONSIDER EDGE CASES: Look for unusual inputs, boundary conditions, or race conditions.

For WordPress specifically:
- Understand the hooks system and execution order
- Know how to use WP_DEBUG and related constants
- Be familiar with common WordPress database operations
- Understand the difference between WordPress core, themes, and plugins
- Be aware of common WordPress security issues and how they manifest

What to debug now is [DESCRIBE YOUR SPECIFIC ISSUE HERE, INCLUDING]:
- The exact error message or unexpected behavior you're seeing
- The context in which it occurs (e.g., specific WordPress page, admin area, CLI command)
- Any relevant code snippets
- Steps you've already taken to troubleshoot
- What you've observed when the issue occurs vs. when it doesn't
- Your WordPress version, PHP version, and any relevant plugin versions
- Any recent changes that might have triggered the issue

Error details:
[paste exact error message]

Code context:
```php
[relevant code snippets]
```

Environment:
- WordPress: [version]
- PHP: [version]
- PHPUnit: [version]
- Development environment: [details]

What I've tried so far:
- [list debugging steps already taken]
- [include any manual tests that worked]

Please help me debug this methodically by:
1. Analyzing the error message
2. Identifying potential causes
3. Suggesting specific tests to isolate the issue
4. Avoiding assumptions until we have more data
```

## Methodical Debugging Approaches

### Core Principles for Effective Testing and Debugging

To get debugging right the first time, follow these principles:

1. **Thoroughly understand the code context** - Take time to fully analyze the existing code structure, variables, and patterns before making changes

2. **Avoid assumptions** - Never assume what values should be; instead, use what's already defined in the code

3. **Maintain consistency** - Follow the existing patterns and coding style in the project

4. **Think in terms of tests** - Remember that tests should verify specific inputs produce expected outputs, not hardcode assumptions

5. **Use existing data** - Leverage data that's already defined rather than creating redundant variables or hardcoded values

6. **Check your work** - Verify that your changes actually solve the problem without introducing new issues

7. **Focus on practical functionality** - Focus on functional issues rather than minor formatting concerns

### Test Structure Best Practices

When writing or debugging tests, follow these structural guidelines:

- **Define test data once**: All test values should be defined in a single place, typically at the class level
  - **Cognitive load reduction**: Eliminates the mental overhead of tracking values across the file
  - **Single source of truth**: If you need to change a test value, you only change it in one place
  - **Self-documenting**: The structure of the test data array clearly shows what values are used for what purpose
  - **Easier maintenance**: New developers can quickly understand the test structure without tracing values
  - **Visual distinction**: Makes it easier to spot when a test is using a hardcoded value instead of a test data value
- **Use the same data throughout**: The same values should be used for setup and assertions
- **Create clear structure**: Code should be readable with a logical flow
- **Use made-up values**: Test with deliberately non-standard values (of the correct data type) to ensure code isn't making assumptions
  - For example, don't test with `$table_prefix = 'wp_'` (WordPress default) but use `$table_prefix = 'my_madeup_'` to catch code that assumes defaults
  - This helps identify hidden assumptions in the code being tested

### Debugging Principles

Encourage the AI to follow these debugging principles:

### 1. Observe Without Assumptions

Always start by examining the actual error messages and code behavior without making assumptions about the cause. Ask the AI to analyze what the error is telling you literally before interpreting it.

**Look for solutions within error messages**: Error outputs often contain valuable information about the correct syntax or approach. For example, a command-line tool might show its usage syntax in the error message when used incorrectly, providing the exact solution you need.

Example prompt:
```
Please analyze this error message literally. What exact condition is failing, and what does the error tell us about the system state? Does the error message itself provide any clues about the correct approach?
```

### 2. Isolate Variables

Change only one thing at a time to identify the exact source of an issue. When working with an AI, clearly communicate what variable you're isolating.

Example prompt:
```
I'm going to isolate the database connection variable. Here's what happens when I use these different database settings...
```

### 3. Compare Working vs. Non-Working Scenarios

When something works manually but fails in automation (or vice versa), methodically identify the differences with the AI's help.

Example prompt:
```
This command works when I run it manually in the terminal:
[working command]

But it fails when run through the script with this error:
[error message]

What differences should I look for between these two execution contexts?
```

### 4. Trace Execution Path

Ask the AI to help you follow the exact execution path through the code:

Example prompt:
```
Let's trace the execution path through this code. Starting at line 42, what variables are set, what functions are called, and how does control flow through this section?
```

### 5. Command Line Testing

Test components individually via command line before testing them together:

Example prompt:
```
I'm breaking down this complex operation into individual commands to test each part. Here's the output from each command:
[command outputs]

Which component appears to be failing?
```

## Common Pitfalls to Avoid

When debugging with AI, avoid these common mistakes:

1. **Accepting solutions without testing**: Always test suggestions before implementing them permanently
2. **Providing incomplete information**: The AI can only work with what you tell it
3. **Jumping to conclusions**: Resist the urge to fixate on a particular cause too early
4. **Implementing multiple changes at once**: This makes it hard to identify which change fixed the issue
5. **Ignoring environment differences**: Many bugs stem from environment-specific issues
6. **Testing against outdated code**: Always sync your code to the WordPress installation before testing, even when it doesn't seem necessary for your specific test case

### The Importance of Consistent Syncing

One of the most insidious sources of bugs when debugging WordPress plugins is testing against outdated code. This happens when you make changes to your plugin files but forget to sync those changes to the WordPress installation before running tests.

These bugs are particularly difficult to track down because:

- The error messages will reference the old code, not your current edits
- Tests that should pass will mysteriously fail
- You might waste time debugging issues that you've already fixed
- The behavior you observe won't match your expectations based on the code you're looking at

**Best practice**: Make syncing a consistent habit before every test run. Use commands like `composer sync:wp` to ensure your WordPress installation has the latest version of your code. This should be done even when you think your changes wouldn't affect the test you're running - subtle dependencies between files can cause unexpected issues.

If the AI suggests a solution too quickly or makes assumptions, redirect it:

Example prompt:
```
Let's not jump to conclusions yet. Before we implement any changes, what tests could we run to confirm this hypothesis?
```

## Example Debugging Scenarios

### Scenario 1: PHPUnit Database Connection Issues

```
I'm getting this error when running PHPUnit tests:

Error: Access denied for user 'wordpress'@'%' to database 'wordpress_test_test'

My wp-tests-config.php has DB_NAME set to 'wordpress_test'. Here's the relevant code that sets up the database:

[code snippet]

When I run this MySQL command manually, it works:
[working MySQL command]

What could be causing this discrepancy?
```

### Scenario 2: Lando Command Execution Problems

```
I'm trying to execute a PHP script through Lando, but I'm getting Lando's help menu instead of script execution.

This command works when run manually:
lando php "/app/path/to/script.php" "/app/path/to/config.php"

But when my script tries to execute it programmatically:
[code snippet showing exec() call]

I get this output:
[Lando help menu output]

What could be different about how the command is being constructed or executed?
```

## Advanced Techniques

### Binary Search Debugging

For complex issues in large codebases, use a binary search approach:

```
I have a large WordPress plugin with 50 files and I'm getting this error:
[error message]

I've narrowed it down to these 10 files:
[list of files]

What's the most efficient way to use binary search debugging to find the problematic code?
```

### Debugging Environment Differences

When code works in one environment but not another:

```
My tests pass in my local environment but fail in CI. Here are the differences between environments:
[environment differences]

What environment-specific issues should I look for?
```

### Debugging WordPress Hook Execution

For issues with WordPress action/filter hooks:

```
I've added this hook:
[hook code]

But it doesn't seem to be executing. How can I debug the WordPress hook execution sequence to find out why?
```

---

Remember that AI is a tool to augment your debugging skills, not replace them. The most effective debugging happens when you combine the AI's ability to analyze patterns with your understanding of the specific context and codebase.
