# Party Minder


Conversation topics. Configure and adjust the default conversation topics.

Provide an option for newest first and set as default.

In local, change Local Sites to local-sites.

Review css again. Ensure only necessary inline style. All other style goes
in the style system.

## Claude Code Instructions
Add instructions for handling style. Ensure only necessary inline style. All other style goes

PartyMinder WordPress Plugin: Migration from Custom Post Types to Pages

  Executive Summary

  We successfully migrated the PartyMinder WordPress plugin from using custom post types (party_event) to using standard
  WordPress pages with meta data. This architectural change improves SEO performance, theme compatibility, and user
  experience while maintaining all existing functionality.

  Background

  The PartyMinder plugin originally used WordPress custom post types to manage events. While functional, this approach had
  limitations:

  - Poor SEO performance compared to pages
  - Limited theme integration capabilities
  - Reduced compatibility with page builders
  - Complex URL structure management
  - Potential conflicts with theme functionality

  What We Accomplished

  1. Core Architecture Migration

  - Removed custom post type registration (party_event)
  - Converted event storage to use WordPress pages with meta data
  - Preserved all existing database tables and relationships
  - Maintained backward compatibility for existing events

  2. Database Schema Updates

  - Events table (wp_partyminder_events) still uses post_id but now references page IDs
  - Added meta data markers: _partyminder_event = 'true' and _partyminder_event_type = 'single_event'
  - All existing event data (RSVPs, guest lists, AI plans) remains intact

  3. Template System Overhaul

  Updated all database queries to use page + meta joins instead of custom post type queries:
  -- Old approach
  WHERE p.post_type = 'party_event'

  -- New approach  
  WHERE p.post_type = 'page'
  AND pm.meta_key = '_partyminder_event'
  AND pm.meta_value = 'true'

  4. URL Structure Simplification

  - Before: yoursite.com/party_event/event-name/
  - After: yoursite.com/event-name/ (standard page URLs)
  - Updated rewrite rules to use page-based routing
  - Cleaner, more SEO-friendly URL structure

  5. Admin Interface Updates

  - Event counting now uses meta queries
  - Admin links point to page management interface
  - Removed custom post type column management
  - Updated dashboard statistics

  Key Benefits

  SEO Improvements

  - Pages have better SEO support than custom post types
  - Cleaner URL structure
  - Better indexing by search engines
  - Enhanced social media sharing

  Theme Compatibility

  - Events now integrate seamlessly with any WordPress theme
  - Page builders can edit event pages directly
  - Standard WordPress page features available (comments, revisions, etc.)
  - Consistent styling with site theme

  User Experience

  - Simplified content management
  - Events appear in standard WordPress page management
  - Familiar editing interface for WordPress users
  - Better mobile responsiveness through theme integration

  Technical Benefits

  - Reduced plugin complexity
  - Better performance (fewer custom queries)
  - Improved caching compatibility
  - Standard WordPress workflows

  How to Use the New System

  For Site Administrators

  1. Migration Process
  // Visit this URL to migrate existing events (admin only)
  yoursite.com/?partyminder_migrate=1
  2. Post-Migration Steps
    - Deactivate and reactivate the plugin
    - Go to Settings > Permalinks > Save Changes
    - Test event functionality

  For Content Creators

  1. Creating Events
    - Use the same "Create Event" page/shortcode
    - Events are automatically created as pages with proper meta data
    - All existing functionality preserved (RSVP, guest management, AI assistance)
  2. Managing Events
    - Events appear in the WordPress Pages list
    - Edit like any standard WordPress page
    - Event-specific options available through meta boxes

  For Developers

  1. Template Integration
  // Check if a page is a PartyMinder event
  $is_event = get_post_meta($post_id, '_partyminder_event', true);

  // Get event data
  $event_manager = new PartyMinder_Event_Manager();
  $event = $event_manager->get_event($post_id);
  2. Shortcodes
    - [partyminder_events_list] - Display events list
    - [partyminder_my_events] - User's events dashboard
    - [partyminder_event_form] - Event creation form
    - [partyminder_rsvp_form event_id="123"] - RSVP form

  Technical Implementation Details

  Files Modified

  - partyminder.php - Main plugin file, removed post type registration
  - includes/class-activator.php - Updated to create pages, not post types
  - includes/class-event-manager.php - All queries converted to page-based
  - includes/class-admin.php - Dashboard and admin interface updates
  - includes/class-deactivator.php - Updated cleanup procedures
  - templates/*.php - All template files updated for page queries
  - uninstall.php - Updated to handle page-based events

  Database Changes

  - No breaking changes to existing tables
  - Events table continues to use post_id field
  - Added meta data for event identification
  - Migration function preserves all existing data

  Compatibility Notes

  - WordPress: Requires WordPress 5.0+ (no change)
  - PHP: Requires PHP 7.4+ (no change)
  - Themes: Now compatible with all standard WordPress themes
  - Plugins: Improved compatibility with SEO and page builder plugins

  Troubleshooting

  Common Issues

  1. 404 Errors: Flush permalinks (Settings > Permalinks > Save)
  2. Events Not Displaying: Check if pages were created properly
  3. Migration Issues: Run migration function manually
  4. Theme Conflicts: Events now use theme's page template

  Migration Verification

  // Check if migration was successful
  $migrated_events = get_posts([
      'post_type' => 'page',
      'meta_key' => '_partyminder_event',
      'meta_value' => 'true',
      'posts_per_page' => -1
  ]);
  echo "Found " . count($migrated_events) . " migrated events";

  Conclusion

  The migration from custom post types to pages represents a significant architectural improvement for the PartyMinder
  plugin. This change enhances SEO performance, improves theme compatibility, and provides a better user experience while
  maintaining all existing functionality. The migration process is designed to be seamless, with backward compatibility
  ensuring no data loss during the transition.

  The new page-based architecture positions PartyMinder for better long-term maintainability and integration with the
  broader WordPress ecosystem.




  **User Profile Features:**  


Yes. The profile page we discussed earlier. What questions do we need to answer before we create the page? What risks do we face creating the page without sufficient planning? 

I imagine our selection of fields will be fairly easy to manage and update/change as we go forward. 

I am not clear on how we should manage membership. You mentioned using the wordpress profile system or our own. I want to offer AT Proto integration either now or as an addon in the future.


For sure we want Name, Avatar, Profile image, Bio, preferences (food),
allergies (private), notification settings, social links, location, 

Do we want to use the built in wordpress page with column?

The profile page should only be for registered members. It should be
required to create an account to use our service.

Everyone that uses the site needs an account. Invitations include a link to account creation if the invitee does not have an account already.  
How do we use the at protocol?

I think we need our own profile system? Of course we will need to use wordpress authentication but I would like to use our own stylized login form. Or use a theme based login form. How do we integrate AT Proto?

I agree we should have a profile page, a main page, 

I do not want to use the word "discussion". Nor "discussion group". They
sound to much like work. I'm open to suggestions and ready to use
"conversation" for now.

## Language
Community

Member

Host

Guest

Conversation

Comment






   \- **Event categories/tags** (dinner parties, birthdays, etc.)  
Yes.   
Dinner party. A group of people for food. Private group.  
House party. A more open group of people for an evening or day at a home, boat, or beach. Friends can bring friends.  
Event. Could be multi-day. So arrangements for parking, housing, etc.  
  \- **RSVP management improvements** (dietary restrictions, plus-ones)  
  \- **Email notifications** system  
  \- **Event search/filtering** on the events page  
  What sounds most valuable for your users right now?

Maybe a main central section with the user/visitor social activity. We need to decide what to do with social activity. I do not want an algorithm driven "newsfeed". I want more of an ongoing set of discussions. Not real sure how to implement this yet.

You showed me there is a lot more to a user profile page than just creating a page. We need to discuss users. How we acquire them. How we manage them, etc.       

public and private? 

Preferences and allergies. Meat. Veggie, vegan, raw.

What would adding the social activities to the profile page look like?

For house parties, how do we share the invitation? Share to bsky? Facebook.


What is a meta box?

