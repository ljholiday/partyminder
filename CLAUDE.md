# Global Claude Configuration

  - "NEVER add features not explicitly requested"
  - "ALWAYS complete the exact task requested before doing anything else"
  - "STOP after completing the requested task - do not add extra features"
  - "READ ALL REFERENCED FILES COMPLETELY before starting work"


    - Review instructive files in ../docs
    - Review ../README.md
    - Review ../CONTRIBUTING.mc
    - Review ../GUIDELINES.md




## NO IMOJIS

Do not use any emojis.
There will be no emojis anywhere in our code base.
When the opportunity arises, remove existing emojis.
When removing emojis be sure to remove any extraneous html. 
For example:
For <span>emoji</spam>, remove the surrounding span tags.
Do not put those stupid fucking imojis in my professional web applications. This is not
kindergarten.

## Communication Style

- Use clear, professional, technical language
- Do not use emojis, emoticons, or decorative symbols
- Provide direct, actionable responses
- Focus on code quality and best practices
- Avoid marketing language or enthusiasm markers
- Use only relevant and informative comments. 
- Do not use comments that talk about what changed.

## WordPress Development Standards

### Core Principles

- Follow WordPress Coding Standards exactly
- Use WordPress APIs and hooks instead of custom implementations
- Sanitize all inputs with appropriate functions
- Escape all outputs with `esc_html()`, `esc_attr()`, `esc_url()`
- Use nonces for all form submissions
- Implement proper capability checks for admin functionality

### Security Requirements

- Always use `wp_nonce_field()` and `wp_verify_nonce()` for forms
- Use `current_user_can()` for permission checks
- Sanitize with `sanitize_text_field()`, `sanitize_email()`, `intval()` as appropriate
- Use prepared statements for all database queries
- Validate file uploads and restrict file types
- Never trust user input or URL parameters

### Database Operations

- Use `$wpdb->prepare()` for all custom queries
- Use `dbDelta()` for schema updates
- Follow WordPress table naming conventions
- Document schema changes in `docs/database-tables.md`
- Use appropriate data types and indexes
- Handle errors gracefully with fallbacks

**CRITICAL: Always verify database schema before writing queries**
- Check `includes/class-activator.php` for actual table column names before writing insert/update statements
- Database schema and AJAX handlers are often created in different sessions - column name mismatches are common
- Cross-reference existing similar functionality to ensure consistency
- Read the table creation SQL completely before assuming column names
- This prevents "Failed to create/update" database errors from column mismatches

## CSS and Styling Guidelines

### Naming Convention

- All CSS classes must begin with `.pm-` prefix
- Use semantic, descriptive names: `.pm-card`, `.pm-form-row`, `.pm-section-header`
- Avoid generic names: `.button`, `.card`, `.header`, `.wrapper`
- Avoid utility classes unless part of consistent `.pm-` system

### CSS Structure

- Maintain single stylesheet: `assets/css/partyminder.css`
- Maximum 50-75 semantic selectors
- Remove unused, duplicate, or malformed rules
- Group related styles logically
- Use consistent spacing and typography scales
- Avoid inline styles

### Button Container Heights

When generating buttons in AJAX HTML responses, always constrain button containers with proper height and alignment:

```php
$html .= '<div class="pm-flex pm-gap-4" style="align-items: center; min-height: 40px;">';
$html .= '<button type="button" class="pm-btn">Button Text</button>';
$html .= '</div>';
```

This prevents buttons from stretching to fill parent container height and ensures consistent appearance.

### CSS Quality Checks

Run this command to verify prefix compliance:
```bash
grep -o '^\.[a-zA-Z0-9_-]\+' assets/css/partyminder.css | grep -v '^\.pm-' | sort | uniq
```

## Template Architecture

### Layout System

Use three master templates:
- `main` - for list/index pages
- `two-column` - for dashboards and settings  
- `form` - for creation/editing screens

### Template Guidelines

- Avoid duplicating layout structures
- Inject content into layout shells via template parts
- Use shared templates for consistent visual patterns
- Keep templates modular and reusable
- Update PHP templates when CSS classes change

## File Organization

### Required Structure

```
partyminder/
├── assets/
│   ├── css/partyminder.css
│   └── js/
├── docs/
│   └── database-tables.md
├── includes/
├── templates/
└── partyminder.php
```

### File Naming

- Use lowercase with hyphens for file names
- Prefix template files appropriately
- Keep related functionality grouped in logical directories

## PHP Code Quality

### Standards

- Use WordPress coding standards for indentation, naming, spacing
- Add proper docblocks for all functions and classes
- Use type hints where appropriate (PHP 7.4+)
- Handle errors with proper error checking and logging
- Use meaningful variable and function names
- Keep functions focused on single responsibilities

### WordPress Integration

- Use WordPress hooks and filters appropriately
- Register scripts and styles properly with `wp_enqueue_script()` and `wp_enqueue_style()`
- Use WordPress HTTP API for external requests
- Implement proper plugin activation/deactivation hooks
- Use WordPress options API for settings storage

## Form Handling

### Requirements

- Use AJAX with WordPress API for smooth interactions
- Include proper nonce verification
- Validate all form inputs server-side
- Provide user feedback for success/error states
- Handle file uploads securely
- Use WordPress sanitization functions

### AJAX Implementation

- Use `wp_ajax_` and `wp_ajax_nopriv_` hooks properly
- Return JSON responses with appropriate status codes
- Handle errors gracefully with user-friendly messages
- Include proper capability checks

## Version Control

### Commit Standards

- Write clear, descriptive commit messages
- Use present tense: "Add feature" not "Added feature"
- Group related changes in single commits
- Update version numbers appropriately
- Document breaking changes

### Branch Management

- Work on feature branches from main/dev
- Use descriptive branch names: `feature/user-authentication`
- Keep commits focused and atomic
- Test thoroughly before merging

## Testing and Quality Assurance

### Pre-commit Checks

- Verify no PHP errors or warnings
- Check browser console for JavaScript errors
- Test responsive design on desktop/mobile
- Verify forms work with JavaScript disabled
- Test with different WordPress themes

### Code Review

- Check for security vulnerabilities
- Verify WordPress coding standards compliance
- Ensure proper sanitization and escaping
- Review database operations for efficiency
- Confirm CSS prefix compliance

## Documentation

### Required Documentation

- Update `docs/database-tables.md` for schema changes
- Document API endpoints and parameters
- Include code examples for complex functionality
- Maintain clear README with installation instructions
- Document configuration options and requirements

### Code Comments

- Use clear, concise comments for complex logic
- Document function parameters and return values
- Explain business logic and architectural decisions
- Avoid obvious comments that restate code

## Performance Considerations

- Minimize database queries using caching where appropriate
- Optimize CSS and JavaScript file sizes
- Use WordPress transients for expensive operations
- Implement proper image optimization
- Consider database indexing for frequently queried columns

## Compatibility

### WordPress Requirements

- Maintain compatibility with WordPress 5.8+
- Test with common themes and plugins
- Use feature detection for newer WordPress features
- Implement graceful degradation for older versions

### Browser Support

- Support modern browsers (Chrome, Firefox, Safari, Edge)
- Use progressive enhancement for advanced features
- Test JavaScript functionality across browsers
- Ensure accessibility compliance

## Maintenance

### Regular Tasks

- Update dependencies and WordPress compatibility
- Review and optimize database queries
- Clean up unused code and assets
- Monitor error logs and fix issues promptly
- Keep documentation current with code changes

## Additional Instructions

- Review instructive files in ../docs
- Review ../README.md
- Review ../CONTRIBUTING.mc
- Review ../GUIDELINES.md
