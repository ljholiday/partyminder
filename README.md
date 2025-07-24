# PartyMinder

**AI-powered social event planning with federated networking.**  
Create dinner parties and social events with intelligent assistance, right from your WordPress site.

## Plugin Overview

PartyMinder is a modular, feature-rich WordPress plugin that enables users to:

- Create, manage, and invite guests to social events like dinner parties
- Track RSVPs, guest preferences, and conversations
- Build private or public community spaces (optional)
- Integrate with the AT Protocol for federated social interaction (optional)
- Use intelligent AI-assisted features for streamlined event planning

## Features

- 📅 **Event Management:** Create and manage events with full control over guests, date, time, venue, and capacity
- ✉️ **Invitation System:** Send, track, and manage event invitations and guest statuses
- ✅ **RSVP Tracking:** Monitor responses and participation stats in real-time
- 👥 **Guest Profiles:** View and manage guest preferences, allergies, and more
- 💬 **Conversation Threads:** Built-in community conversations and event chat
- 🌐 **Federated Networking:** Optional AT Protocol integration for public event sharing and interaction
- 🧠 **AI Assistance:** Automated suggestions and support to help plan better events faster
- 📦 **Shortcodes for Everything:** Easily embed dashboards, event forms, RSVP forms, and more

## Shortcodes

| Shortcode | Description |
|----------|-------------|
| `[partyminder_dashboard]` | User dashboard for managing events and conversations |
| `[partyminder_event_form]` | Event creation form |
| `[partyminder_event_edit_form]` | Event editing interface |
| `[partyminder_rsvp_form]` | RSVP form for guests |
| `[partyminder_events_list]` | Display a list of upcoming events |
| `[partyminder_my_events]` | User’s hosted and attending events dashboard |
| `[partyminder_conversations]` | Community conversation section |
| `[partyminder_profile]` | User profile view/edit |
| `[partyminder_login]` | Custom login page |
| `[partyminder_communities]` | Browse and join communities (if enabled) |

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Installation

1. Upload the `partyminder` plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin panel
3. Configure settings and permissions as needed

## Developer Notes

- **Modular Architecture**: Activation/deactivation handled via `class-activator.php` and `class-deactivator.php`
- **Feature Flags**: Enable or disable features like communities and AT Protocol via `class-feature-flags.php`
- **AJAX Endpoints**: All major actions (event create/update, RSVP, guest stats, etc.) are supported via AJAX
- **Frontend Templates**: Uses custom content injection for event and dashboard pages
- **Fake Post System**: Dynamic routing of individual events using post-like structures

## License

GPL v2 or later  
[https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Plugin Info

- **Plugin Name**: PartyMinder  
- **Version**: 1.0.0  
- **Author**: PartyMinder Team  
- **Plugin URI**: [https://partyminder.com](https://partyminder.com)  
- **Text Domain**: `partyminder`

---

