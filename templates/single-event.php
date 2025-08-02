<?php
/**
 * Single Event Template
 * This template will be used by themes that don't provide their own event template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

$content_template = 'single-event-content.php';
include PARTYMINDER_PLUGIN_DIR . 'templates/layouts/main-page.php';

get_footer();