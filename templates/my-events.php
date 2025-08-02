<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

ob_start();
include PARTYMINDER_PLUGIN_DIR . 'templates/my-events-content.php';
$main_content = ob_get_clean();
$sidebar_template = 'components/activity-feed.php';
include PARTYMINDER_PLUGIN_DIR . 'templates/layouts/two-column-page.php';
