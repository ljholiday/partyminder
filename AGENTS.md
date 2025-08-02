# AGENTS.md

## Overview

This repository contains the WordPress plugin **Party Minder**, which provides a front-end user interface for creating, viewing, and participating in social events. The plugin uses custom CSS for styling event-related pages, blocks, and templates.

The design philosophy for Party Minder is **simple, consistent, and modular**. The UI is composed of repeatable layout structures—pages, headers, content sections, and reusable components like cover images and buttons. The goal is to maintain a **small and maintainable CSS footprint** with **semantic class naming** and **zero redundancy**.

---

## Agent Goals

### CSS Refactor

- Eliminate duplicate, unused, or overly specific CSS rules.
- Consolidate all styles into a single file: `css/partyminder.css`.
- Convert any repeated visual patterns (e.g., headers, cover images, cards) into **shared semantic classes**.
- Ensure **identical elements always use the same class** (e.g., all cover images use `.cover-image`).
- Respect WordPress editor/preview parity where applicable (e.g., block styles that affect both admin and front end).

### Naming Conventions

- Use clear, semantic class names:
  - Examples: `.page`, `.section`, `.header`, `.cover-image`, `.btn`, `.text-muted`, `.card`, `.column`, `.footer`
- **Avoid** utility or atomic class patterns:
  - Forbidden examples: `.p-4`, `.text-lg`, `.mt-2`, `.grid-cols-2`

---

## File Structure

The plugin repository follows a standard WordPress plugin layout. CSS files may be found in `/css/`, block-specific directories, or inline within templates.

