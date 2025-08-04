<?php
/**
 * Community Conversations Template
 * Simple fallback version to prevent 500 errors
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Set up template variables
$page_title = __('Community Conversations', 'partyminder');
$page_description = __('Connect, share tips, and plan amazing gatherings with fellow hosts and guests', 'partyminder');

// Main content
ob_start();
?>
<div class="section text-center">
    <div class="text-xl mb-4">💬</div>
    <h2 class="heading heading-md mb-4"><?php _e('Community Conversations', 'partyminder'); ?></h2>
    <p class="text-muted mb-4"><?php _e('Connect with fellow hosts and guests, share tips, and plan amazing gatherings together.', 'partyminder'); ?></p>
    
    <div class="flex gap-4 flex-center flex-wrap">
        <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn">
            ✨ <?php _e('Create Event', 'partyminder'); ?>
        </a>
        <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="btn btn-secondary">
            📅 <?php _e('Browse Events', 'partyminder'); ?>
        </a>
    </div>
</div>

<div class="section">
    <div class="section-header">
        <h3 class="heading heading-sm">💡 <?php _e('Coming Soon', 'partyminder'); ?></h3>
    </div>
    <p class="text-muted text-center"><?php _e('Community conversations feature is being updated. Check back soon!', 'partyminder'); ?></p>
</div>
<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>
<!-- Quick Actions -->
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
    </div>
    <div class="flex gap-4 flex-wrap">
        <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn">
            ✨ <?php _e('Create Event', 'partyminder'); ?>
        </a>
        <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="btn btn-secondary">
            📅 <?php _e('Browse Events', 'partyminder'); ?>
        </a>
        <a href="<?php echo esc_url(PartyMinder::get_communities_url()); ?>" class="btn btn-secondary">
            👥 <?php _e('Join Communities', 'partyminder'); ?>
        </a>
    </div>
</div>

<!-- Help -->
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">❓ <?php _e('Need Help?', 'partyminder'); ?></h3>
    </div>
    <p class="text-muted"><?php _e('The conversations feature is currently being updated to use the new design system.', 'partyminder'); ?></p>
</div>
<?php
$sidebar_content = ob_get_clean();

// Include two-column template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
?>