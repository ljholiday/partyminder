# PartyMinder

**Human-centered social event planning for WordPress.**  
Create and manage real-life gatherings with privacy, personality, and purpose—right from your own website.

## Overview

PartyMinder is a modern, page-based WordPress plugin designed for people who want to plan and attend real-world events—dinners, meetups, community parties, and more. It provides a seamless, app-like experience for both hosts and guests, with dynamic templates, personalized dashboards, and optional support for decentralized social sharing via the AT Protocol.

Built for privacy, usability, and real connection, PartyMinder helps reclaim the web for what it was meant to do: bring people together.

## Features

- 🗓️ **Create Events** – Hosts can set a title, date, time, location, banner image, and guest list
- ✅ **RSVP Tracking** – Guests confirm attendance and share preferences (allergies, dietary needs, etc.)
- 👤 **Profile Management** – Users manage their own info and see personalized dashboards
- 🧑‍🤝‍🧑 **Event Hosting View** – Hosts can edit event details and monitor responses
- 🪑 **Event Attending View** – Guests can revisit event details and update their RSVP
- 💬 **Discussion-Ready** – Events support post-RSVP conversations and shared memories
- 🌐 **Federated Sharing** (optional) – Share public events via the AT Protocol (e.g., Bluesky)
- ⚙️ **No Blocks, No Shortcodes** – Clean page-based routing with theme-respecting templates

## Architecture

PartyMinder uses a lightweight, page-injection model:

- On activation, essential pages (Create Event, RSVP, Profile, etc.) are created automatically
- Dynamic routing is handled via the `the_content` filter
- Template files render context-specific UIs based on the active page slug
- All user interactions happen on the front end—no admin dashboard configuration required
- AJAX and REST endpoints power smooth, real-time updates

No reliance on Gutenberg, no fragile shortcodes—just clean integration into any modern WordPress theme.

## Installation

1. Upload the `partyminder` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin interface
3. The plugin will auto-create key pages and display them under **Pages → All Pages**
4. Start planning your first event!

## Requirements

- WordPress 5.8+
- PHP 7.4+
- A theme that supports full-width or content-area rendering (most do)

## Roadmap

The MVP focuses on:

- Event creation and RSVP workflows
- Customizable guest profiles
- Host and attendee views
- Optional AT Protocol federation

Planned expansions include:

- Vendor integration (e.g., caterers, food trucks, local shops)
- Premium add-ons for group planning, discussion threads, and collaborative budgeting
- Community tools for recurring events, open invites, and public browsing
- Full support for multisite installations

## Business Model

PartyMinder will be offered as a dual-license product:

- **Free Tier**: Unlimited private events, RSVP collection, and profile management
- **Premium Tier**: Advanced host features, federated sharing, vendor integrations, and community tools

We're seeking early adopters, beta users, and investor partners to help bring PartyMinder to more communities and independent site owners.

## License

GPL v2 or later  
[https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Learn More

- Website: [https://partyminder.com](https://partyminder.com)
- Docs & Support: Coming soon
- Contact: team@partyminder.com

---

PartyMinder is built for people, not algorithms.  
Your site. Your guests. Your party.

