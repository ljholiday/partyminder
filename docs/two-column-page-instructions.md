⏺ Two-Column Page Construction Pattern for PartyMinder Plugin

  Required Template Structure

  All two-column pages must follow this exact pattern within the content template file:

  1. PHP Setup and Data Loading:
  <?php
  // Prevent direct access
  if (!defined('ABSPATH')) {
      exit;
  }

  // Load required classes and get data
  require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
  // ... other required classes

  // Get and prepare data
  $data = get_required_data();

  2. Template Variables Setup:
  // Set up template variables
  $page_title = 'Page Title';
  $page_description = 'Optional description'; // Can be empty string

  3. Secondary Navigation (Optional):
  <!-- Secondary Menu Bar -->
  <div class="pm-section pm-mb-4">
      <div class="pm-flex pm-gap-4 pm-flex-wrap">
          <a href="<?php echo PartyMinder::get_create_conversation_url(); ?>" class="pm-btn">
              <?php _e( 'Start Conversation', 'partyminder' ); ?>
          </a>
          <a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn pm-btn-secondary">
              <?php _e( 'Browse Events', 'partyminder' ); ?>
          </a>
          <a href="<?php echo esc_url( PartyMinder::get_dashboard_url() ); ?>" class="pm-btn pm-btn-secondary">
              <?php _e( 'Dashboard', 'partyminder' ); ?>
          </a>
          
          <!-- Circle Filter Buttons (for conversation pages) -->
          <button class="pm-btn pm-btn-secondary is-active" data-circle="close" role="tab" aria-selected="true" aria-controls="pm-convo-list">
              <?php _e( 'Close Circle', 'partyminder' ); ?>
          </button>
          <button class="pm-btn pm-btn-secondary" data-circle="trusted" role="tab" aria-selected="false" aria-controls="pm-convo-list">
              <?php _e( 'Trusted Circle', 'partyminder' ); ?>
          </button>
          <button class="pm-btn pm-btn-secondary" data-circle="extended" role="tab" aria-selected="false" aria-controls="pm-convo-list">
              <?php _e( 'Extended Circle', 'partyminder' ); ?>
          </button>
      </div>
  </div>

  4. Main Content Capture:
  // Main content
  ob_start();
  ?>
  <!-- Main content HTML here using pm- prefixed classes -->
  <div class="pm-section pm-mb">
      <!-- Content goes here -->
  </div>
  <?php
  $main_content = ob_get_clean();

  5. Sidebar Content Capture:
  // Sidebar content
  ob_start();
  ?>
  <!-- Sidebar content HTML here using pm- prefixed classes -->
  <div class="pm-section pm-mb">
      <!-- Sidebar content goes here -->
  </div>
  <?php
  $sidebar_content = ob_get_clean();

  6. Template Inclusion:
  // Include two-column template
  include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');

  Wrapper Integration for Full-Width Display

  For Content Injection Pages (like single events):
  The partyminder-content wrapper must be added in the main plugin file where content is injected:

  // In partyminder.php content injection method
  ob_start();
  echo '<div class="partyminder-content partyminder-[page-type]-page">';
  include PARTYMINDER_PLUGIN_DIR . 'templates/[template-name].php';
  echo '</div>';
  return ob_get_clean();

  For Direct Page Rendering (like events list):
  The wrapper is added in the main plugin where the page is rendered:

  // In partyminder.php page rendering method
  ob_start();
  echo '<div class="partyminder-content partyminder-[page-type]-page">';
  include PARTYMINDER_PLUGIN_DIR . 'templates/[template-name].php';
  echo '</div>';
  return ob_get_clean();

  CRITICAL: The content template itself should NOT contain the partyminder-content wrapper - it's added by the main plugin.

  Secondary Navigation Guidelines

  Include secondary navigation on user-facing pages for consistent experience:
  
  Standard Navigation Buttons:
  - Start Conversation (if user is logged in)
  - Browse Events  
  - Dashboard
  
  Additional Buttons for Specific Pages:
  - Conversation pages: Add Circle Filter buttons (Close Circle, Trusted Circle, Extended Circle)
  - Profile pages: Add Create Event, Edit Profile actions
  - Community pages: Add community-specific actions
  
  Navigation Structure:
  - Place after template variables setup, before main content capture
  - Use pm-flex pm-gap-4 pm-flex-wrap for responsive layout
  - Include ARIA attributes for filter buttons (role="tab", aria-selected, aria-controls)
  
  Essential Requirements

  CSS Classes: All content must use pm- prefixed classes:
  - Layout: pm-section, pm-card, pm-card-header, pm-card-body, pm-grid, pm-flex
  - Typography: pm-heading, pm-text-primary, pm-text-muted
  - Components: pm-btn, pm-badge, pm-stat
  - Spacing: pm-mb, pm-gap, pm-p-4

  No Emojis: Remove all emojis and clean up any empty HTML containers left behind.

  Template Variables: Must define $page_title and $page_description before the two-column template include.

  Converting Existing Pages to Two-Column

  1. Identify Content Sections: Determine what belongs in main content vs sidebar
  2. Extract Current HTML: Copy existing content structure
  3. Apply New Pattern:
    - Set up template variables
    - Wrap main content in ob_start() / ob_get_clean()
    - Wrap sidebar content in ob_start() / ob_get_clean()
    - Include two-column base template
  4. Update CSS Classes: Replace all classes with pm- prefixed versions
  5. Remove Emojis: Clean out all emoji characters and empty containers
  6. Add Wrapper: Ensure the main plugin adds partyminder-content wrapper around the template
  7. Verify Method Calls: Check that all Manager class methods exist before calling them
  8. Test URL Patterns: Use correct home_url() patterns for links, not non-existent static methods

  Reference Files

  - Working Example: templates/events-list-content.php
  - Base Template: templates/base/template-two-column.php
  - CSS Override: partyminder-content class in assets/css/partyminder.css (lines 22-30)

  Common Mistakes to Avoid

  - ❌ Adding partyminder-content wrapper inside the template itself
  - ❌ Not using ob_start() / ob_get_clean() pattern
  - ❌ Forgetting to define $page_title and $page_description
  - ❌ Using non-prefixed CSS classes
  - ❌ Including emojis anywhere in the content
  - ❌ Not adding the wrapper in the main plugin's content injection/rendering
  - ❌ Calling non-existent methods (verify all method calls exist before using)
  - ❌ Using incorrect URL patterns (use `home_url('/events/' . $slug)` not `PartyMinder::get_event_url()`)
  - ❌ Not checking Event Manager and Community Manager for available methods first

⏺ The key insight is that the partyminder-content wrapper is essential for overriding WordPress theme width constraints,
  but it must be added by the main plugin during content injection or page rendering, NOT within the template itself. The
  template should only contain the content structure with the ob_start() / ob_get_clean() pattern and the two-column base
  template include.


