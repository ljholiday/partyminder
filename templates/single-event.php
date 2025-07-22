<?php
/**
 * Single Event Template
 * This template will be used by themes that don't provide their own event template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="partyminder-single-event">
    <?php
    // Get the event data
    $event = $GLOBALS['partyminder_current_event'] ?? null;
    
    if ($event) {
        // Include our event content template
        include PARTYMINDER_PLUGIN_DIR . 'templates/single-event-content.php';
    } else {
        echo '<p>' . __('Event not found.', 'partyminder') . '</p>';
    }
    ?>
</div>

<?php get_footer(); ?>