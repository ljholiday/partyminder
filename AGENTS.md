# AGENTS.md

## Overview

This repository contains the WordPress plugin **PartyMinder**, a modular event and social interaction system designed to operate within a highly variable front-end environment. WordPress themes, core styles, admin interfaces, and third-party plugins are all known to inject or depend on **generic class names** such as `.button`, `.card`, `.header`, `.wrapper`, etc.

To maintain visual consistency and avoid unintended side effects, PartyMinder must use **uniquely prefixed CSS class names** throughout all frontend and editor-facing code.

## Goals for This Agent

### CSS Cleanup and Refactor

- Remove all unused, redundant, or malformed class definitions from `assets/css/partyminder.css`.
- Deduplicate rules that appear with minor variations (e.g., multiple classes that only differ by margin).
- Normalize styles into reusable, semantic building blocks for PartyMinder screens (e.g., `.pm-btn`, `.pm-card`, `.pm-section`).

### Strict Namespace Convention

All class names **must begin with `.pm-`** and describe their purpose semantically.

Examples of valid class names:
- `.pm-card`
- `.pm-btn-primary`
- `.pm-section-header`
- `.pm-avatar-img`

Avoid class names like:
- `.card`
- `.button`
- `.wrapper`
- `.header`

This prevents:
- Being silently overridden by WordPress core styles or themes
- Breaking other plugins that use the same class names
- Cross-theme compatibility issues

## Target File

All CSS refactoring should be performed in:

assets/css/partyminder.css


If classes are removed, ensure all PHP templates, block renderers, and shortcodes are updated to match the new class names.

## Refactor Guidelines

- Keep only class selectors with a `.pm-` prefix (or rename others accordingly).
- Delete any selectors named as:
  - Numeric values (e.g., `.1`, `.75rem`, `.2s`)
  - CSS units (`.9em`)
  - File extensions (`.php`, `.js`, etc.)
- Normalize spacing, layout, and typography utilities into a consistent design system.
- Reuse the same class for all elements that should share appearance (e.g., all event cards must use the same `.pm-card`).
- Avoid utility-style classes (e.g., `.p-4`, `.mt-2`, `.text-lg`) unless part of a scoped `.pm-` naming system and reused consistently.

## Output Expectations

- A cleaned and updated `assets/css/partyminder.css` file
- Only `.pm-*` class names should remain
- Maximum of 50–75 well-named semantic selectors
- No utility-class bloat
- Visual style must match the current working design (e.g., from Codex branch or live site)
- Clear, minimal, reusable structure that supports all screens and modules

## Verification Without Stylelint

If you cannot run `stylelint` locally, use the following command to verify CSS class prefix compliance:

```bash
grep -o '^\.[a-zA-Z0-9_-]\+' assets/css/partyminder.css | grep -v '^\.pm-' | sort | uniq

