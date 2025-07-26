<?php
/**
 * Enable AT Protocol Feature - WordPress Plugin Context
 * Place this in your active theme's functions.php or run as a one-time script
 */

// If running standalone, include WordPress
if (!function_exists('update_option')) {
    // Adjust path as needed for your WordPress installation
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Enable AT Protocol
update_option('partyminder_enable_at_protocol', true);

echo "AT Protocol feature has been enabled.\n";
echo "Current setting: " . (get_option('partyminder_enable_at_protocol') ? 'Enabled' : 'Disabled') . "\n";

// Also check if the database table exists
global $wpdb;
$table_name = $wpdb->prefix . 'partyminder_member_identities';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

echo "Member identities table: " . ($table_exists ? 'EXISTS' : 'MISSING') . "\n";

if (!$table_exists) {
    echo "\nIMPORTANT: The partyminder_member_identities table is missing.\n";
    echo "You may need to deactivate and reactivate the plugin to create the table.\n";
}
?>