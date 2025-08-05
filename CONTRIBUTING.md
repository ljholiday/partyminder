# Contributing to PartyMinder

Thank you for considering a contribution to the PartyMinder plugin. This document outlines the preferred practices for contributing code, fixing bugs, updating templates, and maintaining project consistency.

---

## 1. Project Philosophy

PartyMinder prioritizes:
- Minimal, semantic CSS
- Shared layouts and templates
- Stability across any WordPress theme
- Clean, maintainable, modular code
- Strict class naming to avoid conflicts with WordPress core or other plugins

Avoid:
- Utility-first or atomic CSS (e.g., `.p-4`, `.mt-2`)
- Generic class names (e.g., `.card`, `.button`, `.header`)
- Duplicated layout structures or inline styles

---

## 2. How to Contribute

1. Clone the repo:
   ```bash
   git clone https://github.com/[your-org]/partyminder.git
   cd partyminder
   ```

2. Create a branch from `main` or `dev`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. Make your changes. Follow the coding and styling conventions.

4. Check for any unprefixed or invalid CSS classes:
   ```bash
   grep -o '^\.[a-zA-Z0-9_-]\+' assets/css/partyminder.css | grep -v '^\.pm-' | sort | uniq
   ```

5. Commit your changes with a clear message:
   ```bash
   git commit -am "Add: New pm-button variant for secondary actions"
   ```

6. Push and submit a pull request.

---

## 3. Coding and Styling Guidelines

- All CSS class names must begin with `.pm-`
- Use meaningful names: `.pm-card`, `.pm-form-row`, `.pm-section-header`
- Avoid inline styles and IDs for styling
- Prefer shared templates for layout
- Use `esc_html()`, `esc_attr()`, and `wp_nonce_field()` in all PHP output
- Keep `assets/css/partyminder.css` minimal and consistent

---

## 4. Template Usage

Use these shared page layouts:
- `main` — for all general content and list pages
- `two-column` — for settings, dashboard, and utilities
- `form` — for create/edit/update screens (e.g., RSVP, event creation)

Avoid duplicating layouts in each page. Inject content into the layout shell via template parts or shortcodes.

---

## 5. Database Changes

If your PR involves schema changes:
- Update `docs/database-tables.md` using:
  ```bash
  wp db tables > docs/database-tables.md
  ```
- Use `dbDelta()` for table updates
- Bump plugin version in the main plugin header

---

## 6. Before You Submit

- Double-check responsiveness (desktop/mobile)
- Remove any debug code, `var_dump()`, or test classes
- Confirm no unprefixed classes exist in the CSS
- Run the plugin locally and confirm no errors appear in the browser console or PHP logs

---

## 7. Issues and Questions

Please open a GitHub issue if:
- You found a bug
- You have a feature request
- You want feedback on a planned contribution

This plugin is maintained by the PartyMinder development team. Direct contact is not required—use GitHub to collaborate.


