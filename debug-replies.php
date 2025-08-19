<?php
// Debug script to check replies in database
// Access via: /wp-content/plugins/partyminder/debug-replies.php?conversation_id=123

require_once('../../../wp-load.php');

if (!is_user_logged_in()) {
    die('Please log in first');
}

$conversation_id = intval($_GET['conversation_id'] ?? 0);
if (!$conversation_id) {
    die('Please provide conversation_id parameter');
}

echo "<h2>Replies Debug for Conversation ID: $conversation_id</h2>\n";

global $wpdb;
$replies_table = $wpdb->prefix . 'partyminder_conversation_replies';
$conversations_table = $wpdb->prefix . 'partyminder_conversations';

// Get conversation info
$conversation = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM $conversations_table WHERE id = %d",
        $conversation_id
    )
);

if (!$conversation) {
    die("Conversation ID $conversation_id not found");
}

echo "<strong>Conversation:</strong><br>";
echo "ID: " . $conversation->id . "<br>";
echo "Title: " . $conversation->title . "<br>";
echo "Reply Count: " . $conversation->reply_count . "<br><br>";

// Get all replies for this conversation
$replies = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $replies_table WHERE conversation_id = %d ORDER BY created_at ASC",
        $conversation_id
    )
);

echo "<strong>Actual Replies in Database (" . count($replies) . "):</strong><br><br>";

if (empty($replies)) {
    echo "No replies found in database<br>";
} else {
    foreach ($replies as $reply) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
        echo "Reply ID: " . $reply->id . "<br>";
        echo "Author ID: " . $reply->author_id . " (" . $reply->author_name . ")<br>";
        echo "Created: " . $reply->created_at . "<br>";
        echo "Content: " . substr($reply->content, 0, 100) . "...<br>";
        echo "Parent Reply ID: " . ($reply->parent_reply_id ?? 'NULL') . "<br>";
        echo "Depth Level: " . $reply->depth_level . "<br>";
        echo "</div>";
    }
}

// Check for recent failed inserts in MySQL error log if accessible
echo "<br><strong>Database Status:</strong><br>";
echo "WordPress Database Error: " . ($wpdb->last_error ? $wpdb->last_error : 'None') . "<br>";
echo "Database Connection: " . ($wpdb->dbh ? 'OK' : 'FAILED') . "<br>";
?>