# PartyMinder WordPress Plugin

**AI-powered social event planning with federated networking. Transform dinner parties into decentralized social networks.**

## Description

PartyMinder is a comprehensive WordPress plugin that enables visitors to create events like dinner parties and house parties, invite their friends, and manage RSVPs. The plugin features AI-powered party planning assistance, guest management, and a modern, responsive interface.

### Key Features

- **Event Creation & Management** - Easy-to-use forms for creating and managing events
- **AI Party Planning Assistant** - Generate comprehensive party plans with AI (OpenAI GPT integration)
- **Guest Management & RSVP** - Streamlined RSVP system with email notifications
- **Responsive Design** - Mobile-friendly interface with customizable styling
- **WordPress Integration** - Custom post types, admin interface, and shortcodes
- **Demo Mode** - Works out-of-the-box with sample data when no API key is configured

## Installation

### Automatic Installation

1. Download the plugin zip file
2. Go to your WordPress admin dashboard
3. Navigate to **Plugins → Add New**
4. Click **Upload Plugin** and select the zip file
5. Click **Install Now** and then **Activate**

### Manual Installation

1. Upload the `partyminder` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **PartyMinder → Settings** to configure the plugin

### File Structure

```
partyminder/
├── partyminder.php              # Main plugin file
├── includes/                    # Core plugin classes
│   ├── class-activator.php     # Database setup & activation
│   ├── class-deactivator.php   # Cleanup & deactivation
│   ├── class-event-manager.php # Event management
│   ├── class-guest-manager.php # Guest & RSVP management
│   ├── class-ai-assistant.php  # AI planning functionality
│   └── class-admin.php         # Admin interface
├── templates/                   # Frontend templates
│   ├── event-form.php          # Event creation form
│   ├── rsvp-form.php           # RSVP form
│   └── events-list.php         # Events listing
├── assets/                      # CSS & JavaScript
│   ├── css/
│   │   ├── public.css          # Frontend styles
│   │   └── admin.css           # Admin styles
│   └── js/
│       ├── public.js           # Frontend JavaScript
│       └── admin.js            # Admin JavaScript
├── languages/                   # Translation files
└── README.md                   # This file
```

## Configuration

### 1. Basic Setup

After activation, go to **PartyMinder → Dashboard** to see the plugin overview.

### 2. AI Configuration (Optional)

To enable AI-powered party planning:

1. Go to **PartyMinder → Settings**
2. Under **AI Configuration**:
   - Select your AI provider (OpenAI)
   - Enter your OpenAI API key
   - Choose your preferred model (GPT-4 recommended)
   - Set a monthly cost limit

**Getting an OpenAI API Key:**
- Visit [OpenAI Platform](https://platform.openai.com/api-keys)
- Create an account and generate an API key
- Add billing information (pay-per-use pricing)

### 3. Email Settings

Configure email settings for invitations and notifications:

1. Set **From Name** (defaults to your site name)
2. Set **From Email** (defaults to admin email)
3. Test email functionality with the built-in test

### 4. Styling Customization

Customize the appearance in **Settings → Styling**:

- **Primary Color** - Main brand color for buttons and accents
- **Secondary Color** - Secondary brand color for gradients
- **Button Style** - Choose from rounded, square, or pill buttons
- **Form Layout** - Select card, minimal, or classic form styles

## Usage

### Creating Events

#### Method 1: Admin Interface
1. Go to **Party Events → Add New**
2. Fill in event details (title, date, venue, etc.)
3. Use the AI Assistant to generate party plans
4. Publish the event

#### Method 2: Frontend Form
Use the `[partyminder_event_form]` shortcode on any page or post to allow visitors to create events.

### Managing RSVPs

Events automatically include RSVP functionality. Guests can:
- Confirm, decline, or respond "maybe"
- Specify dietary restrictions
- Add additional notes
- Receive email confirmations

### AI Party Planning

Generate intelligent party plans with:
- Menu suggestions based on event type and dietary needs
- Shopping lists with estimated quantities
- Preparation timelines
- Cost estimates
- Atmosphere recommendations

## Shortcodes

### `[partyminder_event_form]`
Display an event creation form.

**Attributes:**
- `title` - Form title (default: "Create Your Event")

**Example:**
```php
[partyminder_event_form title="Plan Your Dinner Party"]
```

### `[partyminder_rsvp_form]`
Display an RSVP form for a specific event.

**Attributes:**
- `event_id` - Event ID (defaults to current post ID)

**Example:**
```php
[partyminder_rsvp_form event_id="123"]
```

### `[partyminder_events_list]`
Display a list of events.

**Attributes:**
- `limit` - Number of events to show (default: 10)
- `show_past` - Include past events (default: false)

**Example:**
```php
[partyminder_events_list limit="6" show_past="false"]
```

## Template Integration

### Single Event Display

PartyMinder now uses standard WordPress pages for events. Events integrate seamlessly with your theme's page templates. You can create custom page templates for enhanced styling if needed.

### Archive Page

Events are displayed using the built-in events list shortcode `[partyminder_events_list]` on dedicated pages rather than archive pages.

### Custom Styling

Override plugin styles by adding CSS to your theme:

```css
:root {
    --pm-primary: #your-color;
    --pm-secondary: #your-secondary-color;
}

.partyminder-event-form-container {
    /* Your custom styles */
}
```

## Database Schema

The plugin creates several custom tables:

### `wp_partyminder_events`
Extended event data beyond WordPress posts.

### `wp_partyminder_guests`
Guest information and RSVP responses.

### `wp_partyminder_ai_interactions`
AI usage tracking and cost management.

## Developer Documentation

### Hooks & Filters

**Actions:**
- `partyminder_event_created` - Fired when an event is created
- `partyminder_rsvp_updated` - Fired when an RSVP is updated
- `partyminder_ai_plan_generated` - Fired when AI generates a plan

**Filters:**
- `partyminder_event_form_fields` - Modify event form fields
- `partyminder_rsvp_form_fields` - Modify RSVP form fields
- `partyminder_ai_prompt` - Modify AI prompts before sending

### Page-Based Events

**Event Pages**
- Uses standard WordPress pages with meta data
- Full theme integration and compatibility
- SEO-optimized URLs
- Compatible with page builders

### API Usage

Access plugin functionality programmatically:

```php
// Create an event
$event_manager = new PartyMinder_Event_Manager();
$event_id = $event_manager->create_event($event_data);

// Process RSVP
$guest_manager = new PartyMinder_Guest_Manager();
$result = $guest_manager->process_rsvp($rsvp_data);

// Generate AI plan
$ai_assistant = new PartyMinder_AI_Assistant();
$plan = $ai_assistant->generate_plan($event_type, $guest_count, $dietary, $budget);
```

## Security

The plugin follows WordPress security best practices:

- Nonce verification for all forms
- Data sanitization and validation
- Capability checks for admin functions
- SQL injection prevention with prepared statements
- XSS protection with proper escaping

## Performance

- Efficient database queries with proper indexing
- AJAX for dynamic functionality
- Responsive images and optimized assets
- Minimal external dependencies

## Troubleshooting

### Common Issues

**AI features not working:**
- Check that you have a valid OpenAI API key
- Verify your monthly cost limit isn't exceeded
- Enable demo mode for testing without API

**Email not sending:**
- Check WordPress email configuration
- Verify SMTP settings if using custom email
- Test with the built-in email test function

**Styling issues:**
- Clear any caching plugins
- Check for theme conflicts
- Verify custom CSS isn't overriding plugin styles

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Changelog

### Version 1.0.0
- Initial release
- Event creation and management
- RSVP system with email notifications
- AI-powered party planning
- Responsive frontend interface
- Comprehensive admin dashboard
- Customizable styling options

## Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher

### Optional Requirements

- **OpenAI API Key** - For AI-powered planning features
- **SMTP Configuration** - For reliable email delivery

## Support

For support and documentation:

- Visit the plugin settings page for quick setup guidance
- Check the built-in help sections in the admin interface
- Review this README for comprehensive documentation

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Contributing

This plugin follows WordPress coding standards and best practices. When contributing:

1. Follow WordPress PHP coding standards
2. Include proper documentation
3. Write secure, efficient code
4. Test thoroughly across different environments
5. Maintain backward compatibility

---

**PartyMinder** - Transform your events into memorable experiences with intelligent planning and seamless guest management.