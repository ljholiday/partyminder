<?php
/**
 * Test Bluesky AJAX Connection
 * Add this to your theme's functions.php temporarily or create as a standalone test
 */

// Test AJAX endpoint registration
add_action('wp_ajax_test_bluesky_debug', 'test_bluesky_debug');

function test_bluesky_debug() {
    error_log('[Bluesky Test] AJAX endpoint called');
    
    // Check if AT Protocol is enabled
    if (class_exists('PartyMinder_Feature_Flags')) {
        $at_enabled = PartyMinder_Feature_Flags::is_at_protocol_enabled();
        error_log('[Bluesky Test] AT Protocol enabled: ' . ($at_enabled ? 'YES' : 'NO'));
    } else {
        error_log('[Bluesky Test] Feature flags class not found');
    }
    
    // Check if classes exist
    $classes = [
        'PartyMinder_AT_Protocol_Manager',
        'PartyMinder_Bluesky_Client',
        'PartyMinder_Member_Identity_Manager'
    ];
    
    foreach ($classes as $class) {
        error_log('[Bluesky Test] Class ' . $class . ': ' . (class_exists($class) ? 'EXISTS' : 'MISSING'));
    }
    
    // Test database connection
    global $wpdb;
    $table = $wpdb->prefix . 'partyminder_member_identities';
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    error_log('[Bluesky Test] Member identities table: ' . ($exists ? 'EXISTS' : 'MISSING'));
    
    // Test if actions are registered
    $actions = [
        'wp_ajax_partyminder_connect_bluesky',
        'wp_ajax_partyminder_get_bluesky_contacts',
        'wp_ajax_partyminder_check_bluesky_connection'
    ];
    
    foreach ($actions as $action) {
        $registered = has_action($action);
        error_log('[Bluesky Test] Action ' . $action . ': ' . ($registered ? 'REGISTERED' : 'NOT REGISTERED'));
    }
    
    wp_die(json_encode(array(
        'success' => true,
        'message' => 'Debug complete - check error_log'
    )));
}

// Test Bluesky API connectivity
add_action('wp_ajax_test_bluesky_api', 'test_bluesky_api');

function test_bluesky_api() {
    error_log('[Bluesky API Test] Starting API test');
    
    $test_url = 'https://bsky.social/xrpc/com.atproto.server.describeServer';
    $response = wp_remote_get($test_url, array('timeout' => 10));
    
    if (is_wp_error($response)) {
        $error = $response->get_error_message();
        error_log('[Bluesky API Test] API Error: ' . $error);
        wp_die(json_encode(array(
            'success' => false,
            'message' => 'API Error: ' . $error
        )));
    }
    
    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log('[Bluesky API Test] Status: ' . $status);
    error_log('[Bluesky API Test] Response: ' . substr($body, 0, 200));
    
    wp_die(json_encode(array(
        'success' => true,
        'status' => $status,
        'message' => 'API test complete'
    )));
}

// Add test buttons to admin (temporary)
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info">
            <p><strong>Bluesky Debug Tests:</strong></p>
            <button onclick="testBlueskyDebug()" class="button">Test Debug Info</button>
            <button onclick="testBlueskyAPI()" class="button">Test API Connection</button>
            <script>
            function testBlueskyDebug() {
                fetch(ajaxurl, {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"},
                    body: "action=test_bluesky_debug"
                }).then(r => r.json()).then(d => alert(d.message));
            }
            function testBlueskyAPI() {
                fetch(ajaxurl, {
                    method: "POST", 
                    headers: {"Content-Type": "application/x-www-form-urlencoded"},
                    body: "action=test_bluesky_api"
                }).then(r => r.json()).then(d => alert(d.message + " (Status: " + d.status + ")"));
            }
            </script>
        </div>';
    }
});
?>