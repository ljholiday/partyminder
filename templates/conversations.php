<?php
/**
 * Community Conversations Template
 * Displays list of conversation topics with two column layout
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';

$conversation_manager = new PartyMinder_Conversation_Manager();
$event_manager = new PartyMinder_Event_Manager();

$conversation_topic = get_query_var('conversation_topic');
$conversation_slug = get_query_var('conversation_slug');

if ($conversation_topic && $conversation_slug) {
    include PARTYMINDER_PLUGIN_DIR . 'templates/single-conversation-content.php';
    return;
}

if ($conversation_topic) {
    include PARTYMINDER_PLUGIN_DIR . 'templates/topic-conversations-content.php';
    return;
}

$topics = $conversation_manager->get_topics();

ob_start();
?>
<div class="section">
    <div class="header">
        <h2><?php _e('Discussion Topics', 'partyminder'); ?></h2>
    </div>
    <?php if (!empty($topics)): ?>
        <?php foreach ($topics as $topic): ?>
            <div class="card">
                <h3><a href="<?php echo esc_url(home_url('/conversations/' . $topic->slug)); ?>"><?php echo esc_html($topic->name); ?></a></h3>
                <?php if (!empty($topic->description)): ?>
                    <p class="text-muted"><?php echo esc_html($topic->description); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted"><?php _e('No conversation topics found.', 'partyminder'); ?></p>
    <?php endif; ?>
    <?php if (is_user_logged_in()): ?>
        <div class="section">
            <a href="#" class="btn start-conversation-btn"><?php _e('Start Conversation', 'partyminder'); ?></a>
        </div>
    <?php endif; ?>
</div>
<?php
$main_content = ob_get_clean();
$sidebar_template = 'components/activity-feed.php';
include PARTYMINDER_PLUGIN_DIR . 'templates/layouts/two-column-page.php';
