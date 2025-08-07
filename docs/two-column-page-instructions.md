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

  3. Main Content Capture:
  // Main content
  ob_start();
  ?>
  <!-- Main content HTML here using pm- prefixed classes -->
  <div class="pm-section pm-mb">
      <!-- Content goes here -->
  </div>
  <?php
  $main_content = ob_get_clean();

  4. Sidebar Content Capture:
  // Sidebar content
  ob_start();
  ?>
  <!-- Sidebar content HTML here using pm- prefixed classes -->
  <div class="pm-section pm-mb">
      <!-- Sidebar content goes here -->
  </div>
  <?php
  $sidebar_content = ob_get_clean();

  5. Template Inclusion:
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

⏺ The key insight is that the partyminder-content wrapper is essential for overriding WordPress theme width constraints,
  but it must be added by the main plugin during content injection or page rendering, NOT within the template itself. The
  template should only contain the content structure with the ob_start() / ob_get_clean() pattern and the two-column base
  template include.


