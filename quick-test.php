<?php
/**
 * Quick test to verify Bluesky integration status
 * Run this directly via browser: /wp-content/plugins/partyminder/quick-test.php
 */

// Basic WordPress bootstrap
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "<h2>PartyMinder Bluesky Quick Test</h2>";

// Test 1: Feature Flags
echo "<h3>1. Feature Flags</h3>";
if (class_exists('PartyMinder_Feature_Flags')) {
    $at_enabled = PartyMinder_Feature_Flags::is_at_protocol_enabled();
    echo "AT Protocol Enabled: " . ($at_enabled ? '<span style="color:green">YES</span>' : '<span style="color:red">NO</span>') . "<br>";
} else {
    echo '<span style="color:red">PartyMinder_Feature_Flags class not found</span><br>';
}

// Test 2: Classes
echo "<h3>2. Class Loading</h3>";
$classes = [
    'PartyMinder_AT_Protocol_Manager',
    'PartyMinder_Bluesky_Client', 
    'PartyMinder_Member_Identity_Manager'
];

foreach ($classes as $class) {
    echo "$class: " . (class_exists($class) ? '<span style="color:green">LOADED</span>' : '<span style="color:red">MISSING</span>') . "<br>";
}

// Test 3: Database Table
echo "<h3>3. Database Table</h3>";
global $wpdb;
$table = $wpdb->prefix . 'partyminder_member_identities';
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
echo "Member Identities Table: " . ($exists ? '<span style="color:green">EXISTS</span>' : '<span style="color:red">MISSING</span>') . "<br>";

// Test 4: AJAX Actions  
echo "<h3>4. AJAX Actions</h3>";
$actions = [
    'wp_ajax_partyminder_connect_bluesky',
    'wp_ajax_partyminder_get_bluesky_contacts',
    'wp_ajax_partyminder_check_bluesky_connection'
];

foreach ($actions as $action) {
    $registered = has_action($action);
    echo "$action: " . ($registered ? '<span style="color:green">REGISTERED</span>' : '<span style="color:red">NOT REGISTERED</span>') . "<br>";
}

// Test 5: Bluesky API Test
echo "<h3>5. Bluesky API Test</h3>";
$test_url = 'https://bsky.social/xrpc/com.atproto.server.describeServer';
$response = wp_remote_get($test_url, array('timeout' => 10));

if (is_wp_error($response)) {
    echo "API Connection: <span style='color:red'>FAILED (" . $response->get_error_message() . ")</span><br>";
} else {
    $status = wp_remote_retrieve_response_code($response);
    echo "API Connection: " . ($status === 200 ? '<span style="color:green">SUCCESS</span>' : "<span style='color:red'>FAILED (Status: $status)</span>") . "<br>";
}

// Test 6: Manual AJAX Test
echo "<h3>6. Manual AJAX Test</h3>";
echo '<button onclick="testBlueskyConnection()">Test Bluesky Connection</button>';
echo '<div id="test-result"></div>';

echo '<script>
async function testBlueskyConnection() {
    const result = document.getElementById("test-result");
    result.innerHTML = "Testing...";
    
    try {
        const response = await fetch("' . admin_url('admin-ajax.php') . '", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "action=partyminder_connect_bluesky&handle=test.bsky.social&password=test&nonce=" + "' . wp_create_nonce('partyminder_at_protocol') . '"
        });
        
        const data = await response.json();
        result.innerHTML = "<pre>" + JSON.stringify(data, null, 2) + "</pre>";
    } catch (error) {
        result.innerHTML = "<span style=\'color:red\'>Error: " + error.message + "</span>";
    }
}
</script>';

echo "<h3>Debug Instructions</h3>";
echo "<p>1. Check all items above are working</p>";
echo "<p>2. Try the manual AJAX test with fake credentials</p>";  
echo "<p>3. Check debug.log: <code>/wp-content/debug.log</code></p>";
echo "<p>4. If AJAX actions aren't registered, the AT Protocol Manager isn't loading</p>";
?>