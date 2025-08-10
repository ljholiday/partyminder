# Test Fixtures

This directory contains fixture files for snapshot testing.

## Email Templates (`email-templates/`)

Contains expected HTML output for email templates:

- `invitation-email-expected.html` - Expected output for event invitation emails
- `rsvp-confirmation-expected.html` - Expected output for RSVP confirmation emails

## How Snapshot Tests Work

1. **First Run**: When a snapshot test runs for the first time, it generates the actual output and saves it as a fixture file
2. **Subsequent Runs**: The test compares the current output against the saved fixture
3. **Updates**: If output intentionally changes, delete the fixture file to regenerate it

## Updating Fixtures

To update fixtures after intentional changes:

```bash
# Remove specific fixture
rm tests/fixtures/email-templates/invitation-email-expected.html

# Remove all email fixtures
rm tests/fixtures/email-templates/*.html

# Run tests to regenerate
composer run test:snapshot
```

## Best Practices

- Commit fixture files to version control
- Review fixture changes in pull requests
- Keep fixtures minimal but representative
- Use normalized content (consistent whitespace, etc.)