<?php
/**
 * PartyMinder Bluesky Debug Script
 * Use this script to diagnose Bluesky connection issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', '/Users/lonnholiday/social.partyminder.com/');
    require_once(ABSPATH . 'wp-config.php');
    require_once(ABSPATH . 'wp-includes/wp-db.php');
    require_once(ABSPATH . 'wp-includes/pluggable.php');
    require_once(__DIR__ . '/includes/class-feature-flags.php');
}

echo "=== PartyMinder Bluesky Debug ===\n\n";

// Check feature flags
echo "1. Feature Flags:\n";
echo "   - AT Protocol Enabled: " . (PartyMinder_Feature_Flags::is_at_protocol_enabled() ? 'YES' : 'NO') . "\n";
echo "   - Communities Enabled: " . (PartyMinder_Feature_Flags::is_communities_enabled() ? 'YES' : 'NO') . "\n\n";

// Check database tables
global $wpdb;
echo "2. Database Tables:\n";

$tables_to_check = [
    'partyminder_member_identities',
    'partyminder_events',
    'partyminder_guests',
    'partyminder_communities'
];

foreach ($tables_to_check as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    echo "   - $full_table_name: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}

// Check if classes can be loaded
echo "\n3. Class Loading:\n";
$classes_to_check = [
    'PartyMinder_Feature_Flags' => __DIR__ . '/includes/class-feature-flags.php',
    'PartyMinder_Bluesky_Client' => __DIR__ . '/includes/class-bluesky-client.php',
    'PartyMinder_AT_Protocol_Manager' => __DIR__ . '/includes/class-at-protocol-manager.php',
    'PartyMinder_Member_Identity_Manager' => __DIR__ . '/includes/class-member-identity-manager.php'
];

foreach ($classes_to_check as $class => $file) {
    if (file_exists($file)) {
        require_once($file);
        echo "   - $class: " . (class_exists($class) ? 'LOADED' : 'FAILED') . "\n";
    } else {
        echo "   - $class: FILE MISSING ($file)\n";
    }
}

// Check WordPress AJAX actions
echo "\n4. AJAX Actions:\n";
if (PartyMinder_Feature_Flags::is_at_protocol_enabled()) {
    $at_protocol = new PartyMinder_AT_Protocol_Manager();
    echo "   - AT Protocol Manager initialized: YES\n";
    
    // Check if actions are registered
    $actions = [
        'wp_ajax_partyminder_connect_bluesky',
        'wp_ajax_partyminder_get_bluesky_contacts',
        'wp_ajax_partyminder_disconnect_bluesky',
        'wp_ajax_partyminder_check_bluesky_connection'
    ];
    
    foreach ($actions as $action) {
        $has_action = has_action($action);
        echo "   - $action: " . ($has_action ? 'REGISTERED' : 'NOT REGISTERED') . "\n";
    }
} else {
    echo "   - AT Protocol disabled, actions not registered\n";
}

// Test Bluesky API endpoint
echo "\n5. Bluesky API Test:\n";
$test_url = 'https://bsky.social/xrpc/com.atproto.server.describeServer';
$response = wp_remote_get($test_url, array('timeout' => 10));

if (is_wp_error($response)) {
    echo "   - API Connection: FAILED (" . $response->get_error_message() . ")\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "   - API Connection: " . ($status_code === 200 ? 'SUCCESS' : "FAILED (Status: $status_code)") . "\n";
}

echo "\n=== Debug Complete ===\n";

// Instructions
echo "\nNext Steps:\n";
if (!PartyMinder_Feature_Flags::is_at_protocol_enabled()) {
    echo "1. Enable AT Protocol in WordPress Admin -> PartyMinder -> Communities Settings\n";
}
echo "2. Check WordPress debug.log for detailed error messages\n";
echo "3. Try connecting to Bluesky again and check the logs\n";
?>