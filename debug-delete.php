<?php
// Debug script to test delete permissions
// Access via: /wp-content/plugins/partyminder/debug-delete.php?reply_id=123

require_once('../../../wp-load.php');

if (!is_user_logged_in()) {
    die('Please log in first');
}

$reply_id = intval($_GET['reply_id'] ?? 0);
if (!$reply_id) {
    die('Please provide reply_id parameter');
}

echo "<h2>Delete Permission Debug for Reply ID: $reply_id</h2>\n";

global $wpdb;
$replies_table = $wpdb->prefix . 'partyminder_conversation_replies';

// Get reply data
$reply = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM $replies_table WHERE id = %d",
        $reply_id
    )
);

if (!$reply) {
    die("Reply ID $reply_id not found");
}

echo "<strong>Reply Data:</strong><br>";
echo "ID: " . $reply->id . "<br>";
echo "Author ID: " . $reply->author_id . " (type: " . gettype($reply->author_id) . ")<br>";
echo "Author Name: " . $reply->author_name . "<br>";
echo "Content: " . substr($reply->content, 0, 100) . "...<br><br>";

$current_user = wp_get_current_user();
echo "<strong>Current User:</strong><br>";
echo "ID: " . $current_user->ID . " (type: " . gettype($current_user->ID) . ")<br>";
echo "Display Name: " . $current_user->display_name . "<br>";
echo "Email: " . $current_user->user_email . "<br><br>";

// Type conversion and comparison
$current_user_id = intval($current_user->ID);
$reply_author_id = intval($reply->author_id);
$is_author = ($current_user_id === $reply_author_id);
$is_admin = current_user_can('manage_options');

echo "<strong>Permission Check:</strong><br>";
echo "Current User ID (int): $current_user_id<br>";
echo "Reply Author ID (int): $reply_author_id<br>";
echo "Is Author: " . ($is_author ? 'YES' : 'NO') . "<br>";
echo "Is Admin: " . ($is_admin ? 'YES' : 'NO') . "<br>";
echo "Can Delete: " . (($is_author || $is_admin) ? 'YES' : 'NO') . "<br><br>";

// Additional debug info
echo "<strong>User Capabilities:</strong><br>";
foreach ($current_user->allcaps as $cap => $has) {
    if ($has && strpos($cap, 'admin') !== false) {
        echo "$cap: " . ($has ? 'YES' : 'NO') . "<br>";
    }
}

echo "<br><strong>User Roles:</strong><br>";
foreach ($current_user->roles as $role) {
    echo "- $role<br>";
}
?>