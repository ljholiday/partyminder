<?php
// Debug script to check all recent replies
// Access via: /wp-content/plugins/partyminder/debug-all-replies.php

require_once('../../../wp-load.php');

if (!is_user_logged_in()) {
    die('Please log in first');
}

echo "<h2>All Recent Replies Debug</h2>\n";

global $wpdb;
$replies_table = $wpdb->prefix . 'partyminder_conversation_replies';
$conversations_table = $wpdb->prefix . 'partyminder_conversations';

// Get all replies from the last 24 hours
$recent_replies = $wpdb->get_results(
    "SELECT r.*, c.title as conversation_title 
     FROM $replies_table r 
     LEFT JOIN $conversations_table c ON r.conversation_id = c.id 
     WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) 
     ORDER BY r.created_at DESC 
     LIMIT 50"
);

echo "<strong>Recent Replies (last 24 hours):</strong><br><br>";

if (empty($recent_replies)) {
    echo "No recent replies found<br>";
} else {
    foreach ($recent_replies as $reply) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: #f9f9f9;'>";
        echo "<strong>Reply ID: " . $reply->id . "</strong><br>";
        echo "Conversation ID: " . $reply->conversation_id . " (" . $reply->conversation_title . ")<br>";
        echo "Author: " . $reply->author_name . " (ID: " . $reply->author_id . ")<br>";
        echo "Created: " . $reply->created_at . "<br>";
        echo "Content: " . substr($reply->content, 0, 100) . "...<br>";
        echo "</div>";
    }
}

// Show max reply ID in database
$max_reply_id = $wpdb->get_var("SELECT MAX(id) FROM $replies_table");
echo "<br><strong>Highest Reply ID in Database:</strong> " . ($max_reply_id ?? 'None') . "<br>";

// Show all conversations with reply counts
echo "<br><strong>Active Conversations:</strong><br>";
$conversations = $wpdb->get_results(
    "SELECT c.id, c.title, c.reply_count, 
            (SELECT COUNT(*) FROM $replies_table WHERE conversation_id = c.id) as actual_count
     FROM $conversations_table c 
     WHERE c.last_reply_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY c.last_reply_date DESC 
     LIMIT 20"
);

foreach ($conversations as $conv) {
    $mismatch = ($conv->reply_count != $conv->actual_count) ? " ⚠️ MISMATCH" : "";
    echo "Conversation {$conv->id}: {$conv->title} - Recorded: {$conv->reply_count}, Actual: {$conv->actual_count}$mismatch<br>";
}
?>