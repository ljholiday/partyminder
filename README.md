# PartyMinder

**An Actually Social Network—Built on WordPress**  
Plan real events. Connect with real people. Share real life.

## What Is PartyMinder?

PartyMinder transforms your WordPress site into a private, human-centered social network built around gatherings. It helps people plan events, invite friends, and build community—not followers.

Instead of endless feeds and viral noise, PartyMinder offers a quiet, meaningful layer for real-world interaction.

## Features

- 🎉 **Event Creation** – Hosts can create events with title, date/time, location, and image
- 📧 **Guest Invitations** – Send personalized RSVP links via email
- ✅ **RSVP System** – Guests respond with preferences like dietary needs or allergies
- 🧑‍🤝‍🧑 **Dashboard** – See events you're hosting or attending, all in one place
- 👤 **User Profiles** – Guests manage their own preferences and contact info
- 🌐 **Federated Sharing (Optional)** – Public events can be posted via AT Protocol
- 🧩 **Page-Based Architecture** – Uses dynamic content injection; no blocks, no shortcodes
- 🧼 **Zero Admin UI** – Everything happens cleanly on the front end

## Architecture

- Automatically registers custom pages: `/event-create`, `/rsvp`, `/dashboard`, etc.
- Uses the `the_content` filter to inject custom templates based on page slug
- Forms use AJAX and WordPress APIs for smooth interaction
- Fully theme-compatible—uses your existing site styles and layout
- No admin configuration or editor work needed

## Why It Matters

Social media has become performative and extractive.  
PartyMinder is an “actually social” network—where people:
- Know each other
- Share food, stories, and time
- Build trust through repeated in-person interaction

It’s a digital tool for analog joy.

## MVP Goals

- Create and manage events
- Invite guests and collect RSVPs with preferences
- View upcoming events on a personalized dashboard
- Optional integration with AT Protocol (Bluesky, etc.)
- Clean front-end experience with no admin overhead

## Installation

1. Upload the `partyminder` folder to `/wp-content/plugins/`
2. Activate via the WordPress Admin → Plugins
3. Visit **Pages → All Pages** to customize your auto-created routes (optional)
4. Start creating and hosting events!

## Requirements

- WordPress 5.8+
- PHP 7.4+
- A modern theme that respects content templates and spacing

## License

GPL v2 or later  
[https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Learn More

- Website: [https://partyminder.com](https://partyminder.com)
- Contact: team@partyminder.com

---

PartyMinder is built for people, not algorithms.  

