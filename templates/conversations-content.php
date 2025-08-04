<?php
/**
 * Conversations Content Template - Content Only
 * For theme integration via the_content filter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if we're on the dedicated page and include the full template
$on_dedicated_page = (is_page() && get_post_meta(get_the_ID(), '_partyminder_page_type', true) === 'conversations');

if ($on_dedicated_page) {
    // Include the full conversations template
    include PARTYMINDER_PLUGIN_DIR . 'templates/conversations.php';
} else {
    // Fallback embedded version
    echo '<div class="partyminder-shortcode-wrapper card text-center p-4 pm-m-5">';
    echo '<h3>' . __('Community Conversations', 'partyminder') . '</h3>';
    echo '<p>' . __('Connect with fellow hosts and guests, share tips, and plan amazing gatherings together.', 'partyminder') . '</p>';
    echo '<a href="' . esc_url(PartyMinder::get_conversations_url()) . '" class="btn">' . __('Join Conversations', 'partyminder') . '</a>';
    echo '</div>';
}