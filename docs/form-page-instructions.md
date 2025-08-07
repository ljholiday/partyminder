Instructions for Creating a template-form.php Based Form Page for Create Community

  Key Requirements from CLAUDE.md

  - NO EMOJIS - Remove all emojis from any existing code
  - Use pm- CSS prefix for all classes
  - Follow WordPress coding standards
  - Use proper nonces and sanitization

  IMPORTANT: Check for Existing Implementation First

  Before adding any functionality, search the codebase to ensure methods, cases, and handlers don't already exist:
  - Search for existing switch cases (e.g., `case 'create-community'`)
  - Check for existing methods (e.g., `add_create_community_body_class`)
  - Verify AJAX handlers aren't already implemented
  - Look for existing URL methods (e.g., `get_create_community_url`)
  
  Adding duplicate functionality will cause fatal PHP errors!

  Template Structure (Based on working create-conversation-content.php)

  1. File Structure

  <?php
  /**
   * Create Community Content Template
   * Uses unified form template system
   */

  // Prevent direct access
  if (!defined('ABSPATH')) {
      exit;
  }

  // Permission checks
  // Load required classes  
  // Get current user info
  // Check for success/error transients
  // Set template variables
  // Main content with ob_start()
  // Form HTML
  // ob_get_clean() and include template-form.php
  // JavaScript for AJAX submission
  ?>

  2. Template Variables Required by template-form.php

  $page_title = __('Create New Community', 'partyminder');
  $page_description = __('Build a community around shared interests and host amazing events together.', 'partyminder');
  $breadcrumbs = array(
      array('title' => __('Communities', 'partyminder'), 'url' => PartyMinder::get_communities_url()),
      array('title' => __('Create New Community', 'partyminder'))
  );

  3. Content Structure

  // Main content
  ob_start();
  ?>

  <!-- Success message handling -->
  <!-- Error message handling -->

  <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" class="pm-form" id="partyminder-community-form">
      <?php wp_nonce_field('create_partyminder_community', 'partyminder_community_nonce'); ?>
      <input type="hidden" name="action" value="partyminder_create_community">

      <!-- Form fields matching Community Manager expectations -->
      <!-- name, description, privacy fields -->

      <div class="pm-form-actions">
          <button type="submit" name="partyminder_create_community" class="pm-btn">
              <?php _e('Create Community', 'partyminder'); ?>
          </button>
          <a href="<?php echo esc_url(PartyMinder::get_communities_url()); ?>" class="pm-btn pm-btn-secondary">
              <?php _e('Back to Communities', 'partyminder'); ?>
          </a>
      </div>
  </form>

  <?php
  $content = ob_get_clean();

  // Include form template
  include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php');
  ?>

  4. Database Fields Required by Community Manager

  - name (required)
  - description (optional)
  - privacy ('public' or 'private')

  5. Main Plugin File Requirements (partyminder.php)

  Required Additions (if not already present):

  A. Add to switch statement in handle_custom_pages():
  case 'create-community':
      if (PartyMinder_Feature_Flags::is_communities_enabled()) {
          add_filter('the_content', array($this, 'inject_create_community_content'));
          add_filter('body_class', array($this, 'add_create_community_body_class'));
      }
      break;

  B. Add URL method (near other get_*_url methods):
  public static function get_create_community_url() {
      return self::get_page_url('create-community');
  }

  C. Add to $page_keys array in filter_document_title():
  $page_keys = array('events', 'create-event', 'my-events', 'edit-event', 'create-conversation', 'create-community');

  D. Add title case in switch statement:
  case 'create-community':
      $title_parts['title'] = __('Create New Community - Build Your Community', 'partyminder');
      break;

  E. Add body class method (if not already present):
  public function add_create_community_body_class($classes) {
      $classes[] = 'partyminder-communities';
      $classes[] = 'partyminder-create-community';
      return $classes;
  }

  // Content injection method:
  public function inject_create_community_content($content) {
      global $post;

      if (!is_page() || !in_the_loop() || !is_main_query()) {
          return $content;
      }

      $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
      if ($page_type !== 'create-community') {
          return $content;
      }

      ob_start();

      echo '<div class="partyminder-content partyminder-create-community-page">';
      include PARTYMINDER_PLUGIN_DIR . 'templates/create-community-content.php';
      echo '</div>';

      return ob_get_clean();
  }

  6. AJAX Handler Requirements

  The AJAX handler `ajax_create_community()` should already exist in partyminder.php.
  If not present, it must:
  - Use same nonce: create_partyminder_community, partyminder_community_nonce
  - Process fields: name, description, privacy
  - Set success transient: partyminder_community_created_ . get_current_user_id()
  - Redirect to: ?partyminder_created=1
  - Be registered with: add_action('wp_ajax_partyminder_create_community', array($this, 'ajax_create_community'))

  7. Critical: No Inline Styles

  - Use only pm- prefixed CSS classes
  - No style="" attributes
  - Let partyminder.css handle all styling

  8. Integration Notes

  - Feature flags: Community functionality should be wrapped in PartyMinder_Feature_Flags::is_communities_enabled() checks
  - The Community Manager class should already exist in includes/class-community-manager.php
  - Success/error handling uses WordPress transients with user-specific keys
  - The content injection method must properly use ob_start() and return ob_get_clean() to integrate with WordPress content system

  9. Common Pitfalls Avoided

  - Always check for existing implementations first to prevent fatal "Cannot redeclare" errors
  - Don't forget to add the page type to title handling arrays and switch statements  
  - Ensure AJAX handlers are already registered before building forms that depend on them
  - The create-community functionality may already be partially implemented - verify what exists before adding


