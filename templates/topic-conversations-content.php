<?php
/**
 * Topic Conversations Content Template
 * Shows all conversations in a specific topic using unified two-column system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';

$conversation_manager = new PartyMinder_Conversation_Manager();

// Get topic slug from URL
$topic_slug = get_query_var('conversation_topic');
if (!$topic_slug) {
    wp_redirect(PartyMinder::get_conversations_url());
    exit;
}

// Get topic
$topic = $conversation_manager->get_topic_by_slug($topic_slug);
if (!$topic) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
}

// Get conversations for this topic
$conversations = $conversation_manager->get_conversations_by_topic($topic->id, 20);

// Get current user info
$current_user = wp_get_current_user();
$user_email = is_user_logged_in() ? $current_user->user_email : '';

// Set up template variables
$page_title = $topic->icon . ' ' . $topic->name;
$page_description = $topic->description;
$breadcrumbs = array(
    array('title' => __('Conversations', 'partyminder'), 'url' => PartyMinder::get_conversations_url()),
    array('title' => $topic->icon . ' ' . $topic->name)
);

// Main content
ob_start();
?>

<!-- Topic Header -->
<div class="pm-section pm-mb">
    <div class="pm-section-header">
        <div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
            <div>
                <h2 class="pm-heading pm-heading-lg pm-text-primary">
                    <?php echo esc_html($topic->icon . ' ' . $topic->name); ?>
                </h2>
                <p class="pm-text-muted"><?php echo esc_html($topic->description); ?></p>
            </div>
            <div class="pm-flex pm-gap pm-flex-wrap">
                <a href="<?php echo add_query_arg('topic_id', $topic->id, PartyMinder::get_create_conversation_url()); ?>" class="pm-btn">
                     <?php _e('Start New Conversation', 'partyminder'); ?>
                </a>
                <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn-secondary">
                    ← <?php _e('Back to All Topics', 'partyminder'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Conversations List -->
<div class="pm-section">
    <div class="pm-section-header">
        <div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
            <h3 class="pm-heading pm-heading-md pm-text-primary">
                <?php printf(__('%d Conversations', 'partyminder'), count($conversations)); ?>
            </h3>
            <a href="<?php echo add_query_arg('topic_id', $topic->id, PartyMinder::get_create_conversation_url()); ?>" class="pm-btn pm-btn-sm">
                 <?php _e('Start New Conversation', 'partyminder'); ?>
            </a>
        </div>
    </div>

    <?php if (!empty($conversations)): ?>
        <div class="pm-flex pm-gap pm-flex-column">
            <?php foreach ($conversations as $conversation): ?>
                <div class="pm-section">
                    <div class="pm-flex pm-flex-between pm-gap">
                        <div class="pm-flex-1">
                            <div class="pm-flex pm-gap pm-flex-wrap pm-mb">
                                <?php if ($conversation->is_pinned): ?>
                                    <span class="pm-badge pm-badge-secondary">📌 <?php _e('Pinned', 'partyminder'); ?></span>
                                <?php endif; ?>
                                <h4 class="pm-heading pm-heading-sm">
                                    <a href="<?php echo home_url('/conversations/' . $topic->slug . '/' . $conversation->slug); ?>" 
                                       class="pm-text-primary">
                                        <?php echo esc_html($conversation->title); ?>
                                    </a>
                                </h4>
                            </div>
                            
                            <p class="pm-text-muted pm-mb pm-line-clamp-2">
                                <?php echo wp_trim_words(strip_tags($conversation->content), 25, '...'); ?>
                            </p>
                            
                            <div class="pm-flex pm-flex-wrap pm-gap pm-text-muted">
                                <span><?php printf(__('by %s', 'partyminder'), esc_html($conversation->author_name)); ?></span>
                                <span>•</span>
                                <span><?php echo human_time_diff(strtotime($conversation->created_at), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?></span>
                                <?php if ($conversation->last_reply_author && $conversation->last_reply_author !== $conversation->author_name): ?>
                                    <span>•</span>
                                    <span><?php printf(__('Last reply by %s', 'partyminder'), esc_html($conversation->last_reply_author)); ?></span>
                                    <span><?php echo human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="pm-text-center pm-min-w-20">
                            <?php if ($conversation->reply_count > 0): ?>
                                <div class="pm-stat-number pm-text-primary pm-font-bold">
                                    <?php echo $conversation->reply_count; ?>
                                </div>
                                <div class="pm-stat-label pm-text-muted">
                                    <?php echo $conversation->reply_count === 1 ? __('reply', 'partyminder') : __('replies', 'partyminder'); ?>
                                </div>
                            <?php else: ?>
                                <span class="pm-badge pm-badge-success"><?php _e('New', 'partyminder'); ?></span>
                            <?php endif; ?>
                            <div class="pm-text-muted pm-mt">
                                <?php echo human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="pm-section pm-text-center pm-p-8">
            <div class="pm-text-6xl pm-mb"></div>
            <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb">
                <?php _e('No conversations yet', 'partyminder'); ?>
            </h3>
            <p class="pm-text-muted pm-mb">
                <?php _e('Be the first to start a conversation in this topic!', 'partyminder'); ?>
            </p>
            <a href="<?php echo add_query_arg('topic_id', $topic->id, PartyMinder::get_create_conversation_url()); ?>" class="pm-btn">
                 <?php _e('Start the First Conversation', 'partyminder'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<!-- Quick Actions (No Heading) -->
<div class="pm-card pm-mb-4">
    <div class="pm-card-body">
        <div class="pm-flex pm-flex-column pm-gap-4">
            <a href="<?php echo add_query_arg('topic_id', $topic->id, PartyMinder::get_create_conversation_url()); ?>" class="pm-btn">
                 <?php _e('Start New Conversation', 'partyminder'); ?>
            </a>
            <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn-secondary">
                <?php _e('← All Topics', 'partyminder'); ?>
            </a>
            <a href="<?php echo PartyMinder::get_dashboard_url(); ?>" class="pm-btn pm-btn-secondary">
                <?php _e('← Dashboard', 'partyminder'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Topic Info -->
<div class="pm-section pm-mb">
    <div class="pm-section-header">
        <h3 class="pm-heading pm-heading-sm"><?php _e('Topic Info', 'partyminder'); ?></h3>
    </div>
    <div class="pm-stat-list">
        <div class="pm-stat-item">
            <span class="pm-stat-label"><?php _e('Topic', 'partyminder'); ?></span>
            <span class="pm-stat-value"><?php echo esc_html($topic->icon . ' ' . $topic->name); ?></span>
        </div>
        <div class="pm-stat-item">
            <span class="pm-stat-label"><?php _e('Conversations', 'partyminder'); ?></span>
            <span class="pm-stat-value"><?php echo count($conversations); ?></span>
        </div>
        <div class="pm-stat-item">
            <span class="pm-stat-label"><?php _e('Total Replies', 'partyminder'); ?></span>
            <span class="pm-stat-value"><?php echo array_sum(array_column($conversations, 'reply_count')); ?></span>
        </div>
    </div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
?>