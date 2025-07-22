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
    echo '<div class="partyminder-shortcode-wrapper" style="padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px; margin: 20px 0;">';
    echo '<h3>' . __('Community Conversations', 'partyminder') . '</h3>';
    echo '<p>' . __('Connect with fellow hosts and guests, share tips, and plan amazing gatherings together.', 'partyminder') . '</p>';
    echo '<a href="' . esc_url(PartyMinder::get_conversations_url()) . '" class="pm-button" style="background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">' . __('Join Conversations', 'partyminder') . '</a>';
    echo '</div>';
}