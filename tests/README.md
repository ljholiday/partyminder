# PartyMinder Testing Suite

Comprehensive automated testing setup for the PartyMinder WordPress plugin.

## Quick Start

1. **Install dependencies**:
   ```bash
   composer install
   ```

2. **Run all tests**:
   ```bash
   composer run test:all
   ```

3. **Run specific test suites**:
   ```bash
   composer run test:unit        # Unit tests only
   composer run test:integration # Integration tests only
   composer run test:snapshot    # Snapshot tests only
   ```

## Test Types

### Unit Tests (`/tests/Unit/`)

Fast, isolated tests that mock WordPress functions using Brain\Monkey:

- Test pure PHP logic without WordPress dependencies
- Mock WordPress functions and classes
- Focus on individual methods and functions
- Run in milliseconds

**Example**: `DateFormatTest.php` tests date formatting helpers

### Integration Tests (`/tests/Integration/`)

Tests that interact with WordPress core using the official test suite:

- Test WordPress-specific functionality (hooks, database, AJAX)
- Use real WordPress environment
- Test interactions between components
- Slower but more comprehensive

**Example**: `RoutesTest.php` tests AJAX handlers and database operations

### Snapshot Tests (`/tests/Snapshot/`)

Tests that compare output against saved fixtures:

- Test HTML email templates
- Catch unintended output changes
- Generate fixtures automatically on first run
- Perfect for template regression testing

**Example**: `EmailTemplateTest.php` tests invitation email HTML

## Local Development

### Prerequisites

- PHP 8.1+
- Composer
- MySQL (for integration tests)
- WordPress test suite

### Setup Integration Tests

1. **Install WordPress test suite**:
   ```bash
   # Option 1: Use WP-CLI (recommended)
   wp scaffold plugin-tests partyminder
   
   # Option 2: Manual install
   bash bin/install-wp-tests.sh wordpress_test root password localhost latest
   ```

2. **Set environment variables** (optional):
   ```bash
   export WP_TESTS_DIR=/tmp/wordpress-tests-lib
   export WP_CORE_DIR=/tmp/wordpress/
   ```

### Running Tests Locally

```bash
# Install dependencies
composer install

# Run linting
composer run lint

# Fix linting issues automatically
composer run lint:fix

# Run all tests
composer run test

# Run with coverage report
composer run test:coverage
```

### Writing Tests

#### Unit Test Example

```php
namespace PartyMinder\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Monkey\Functions\when('wp_create_nonce')->justReturn('test_nonce');
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    
    public function test_something() {
        // Test pure PHP logic here
        $this->assertTrue(true);
    }
}
```

#### Integration Test Example

```php
namespace PartyMinder\Tests\Integration;

use WP_UnitTestCase;

class MyTest extends WP_UnitTestCase {
    public function test_wordpress_functionality() {
        // Test with real WordPress
        $user_id = $this->factory->user->create();
        $this->assertGreaterThan(0, $user_id);
    }
}
```

## Continuous Integration

Tests run automatically on:
- Push to `main` or `develop` branches
- Pull requests to `main`

The CI pipeline:
1. **Lint Check**: PHP Code Sniffer with WordPress standards
2. **Unit Tests**: Fast isolated tests (PHP 8.1, 8.2)
3. **Integration Tests**: WordPress compatibility (PHP 8.1/8.2 × WP 6.5/6.6)
4. **Snapshot Tests**: Template output verification
5. **Coverage**: Code coverage reporting

## Pre-commit Hooks

Automatic checks before each commit:

```bash
# Enable pre-commit hook (already done)
chmod +x .git/hooks/pre-commit

# Hook runs automatically on: git commit
# - Linting (PHPCS)
# - Unit tests
# - Syntax checking
# - Debugging statement detection
```

To bypass (not recommended):
```bash
git commit --no-verify
```

## Configuration Files

| File | Purpose |
|------|---------|
| `composer.json` | Dependencies and scripts |
| `phpunit.xml` | PHPUnit configuration |
| `phpcs.xml` | Code sniffer rules |
| `tests/bootstrap.php` | Test initialization |
| `.github/workflows/tests.yml` | CI pipeline |

## Troubleshooting

### Common Issues

**"WordPress test suite not found"**
```bash
# Install WordPress test suite
wp scaffold plugin-tests partyminder
# or
bash bin/install-wp-tests.sh wordpress_test root password localhost latest
```

**"Composer dependencies missing"**
```bash
composer install
```

**"MySQL connection failed"**
```bash
# Start MySQL service
sudo service mysql start
# or
brew services start mysql
```

**"Permission denied on hooks"**
```bash
chmod +x .git/hooks/pre-commit
```

### Debugging Tests

```bash
# Run specific test file
vendor/bin/phpunit tests/Unit/DateFormatTest.php

# Run with debugging output
vendor/bin/phpunit --debug tests/Unit/DateFormatTest.php

# Show coverage for specific file
vendor/bin/phpunit --coverage-text --filter DateFormatTest
```

## Best Practices

### General
- Write tests before fixing bugs (TDD)
- Keep tests fast and focused
- Use descriptive test names
- Test edge cases and error conditions

### Unit Tests
- Mock all WordPress functions
- Test one thing at a time
- Avoid database operations
- Use data providers for multiple inputs

### Integration Tests
- Test WordPress-specific functionality
- Use WordPress factories for test data
- Clean up test data in tearDown()
- Test user permissions and capabilities

### Snapshot Tests
- Review fixture changes carefully
- Keep fixtures minimal but representative
- Regenerate fixtures after intentional changes
- Commit fixture files to version control

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Test Suite](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [Brain\Monkey](https://brain-wp.github.io/BrainMonkey/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)