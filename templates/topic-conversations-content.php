<?php
/**
 * Topic Conversations Content Template
 * Shows all conversations in a specific topic
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

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>


<div class="partyminder-topic-conversations">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <a href="<?php echo PartyMinder::get_conversations_url(); ?>">
            <?php _e('🏠 Community Conversations', 'partyminder'); ?>
        </a>
        <span> › </span>
        <span><?php echo esc_html($topic->icon . ' ' . $topic->name); ?></span>
    </div>

    <!-- Topic Header -->
    <div class="topic-header">
        <h1>
            <span><?php echo esc_html($topic->icon); ?></span>
            <span><?php echo esc_html($topic->name); ?></span>
        </h1>
        <p><?php echo esc_html($topic->description); ?></p>
        
        <div class="topic-actions">
            <a href="#" class="pm-button start-conversation-btn" 
               data-topic-id="<?php echo esc_attr($topic->id); ?>"
               data-topic-name="<?php echo esc_attr($topic->name); ?>">
                <span>💬</span>
                <?php _e('Start New Conversation', 'partyminder'); ?>
            </a>
            <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-button pm-button-secondary">
                <span>←</span>
                <?php _e('Back to All Topics', 'partyminder'); ?>
            </a>
        </div>
    </div>

    <!-- Conversations Header -->
    <div class="conversations-header">
        <h2><?php printf(__('%d Conversations', 'partyminder'), count($conversations)); ?></h2>
        <a href="#" class="pm-button start-conversation-btn" 
           data-topic-id="<?php echo esc_attr($topic->id); ?>"
           data-topic-name="<?php echo esc_attr($topic->name); ?>">
            <span>💬</span>
            <?php _e('Start New Conversation', 'partyminder'); ?>
        </a>
    </div>

    <!-- Conversations List -->
    <?php if (!empty($conversations)): ?>
        <div class="conversations-list">
            <?php foreach ($conversations as $conversation): ?>
                <div class="conversation-item">
                    <div class="conversation-main">
                        <div class="conversation-content">
                            <a href="<?php echo home_url('/conversations/' . $topic->slug . '/' . $conversation->slug); ?>" 
                               class="conversation-title">
                                <?php if ($conversation->is_pinned): ?>
                                    📌 
                                <?php endif; ?>
                                <?php echo esc_html($conversation->title); ?>
                            </a>
                            
                            <div class="conversation-excerpt">
                                <?php echo wp_trim_words(strip_tags($conversation->content), 20, '...'); ?>
                            </div>
                            
                            <div class="conversation-meta">
                                <span><?php printf(__('by %s', 'partyminder'), esc_html($conversation->author_name)); ?></span>
                                <span><?php echo human_time_diff(strtotime($conversation->created_at), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?></span>
                                <?php if ($conversation->last_reply_author && $conversation->last_reply_author !== $conversation->author_name): ?>
                                    <span><?php printf(__('Last reply by %s', 'partyminder'), esc_html($conversation->last_reply_author)); ?></span>
                                    <span><?php echo human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="conversation-stats">
                            <?php if ($conversation->reply_count > 0): ?>
                                <div class="reply-count">
                                    <?php echo $conversation->reply_count; ?> 
                                    <?php echo $conversation->reply_count === 1 ? __('reply', 'partyminder') : __('replies', 'partyminder'); ?>
                                </div>
                            <?php else: ?>
                                <div class="reply-count new">
                                    <?php _e('New', 'partyminder'); ?>
                                </div>
                            <?php endif; ?>
                            <div class="last-activity">
                                <?php echo human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="conversations-list">
            <div class="no-conversations">
                <div class="no-conversations-icon">💭</div>
                <h3><?php _e('No conversations yet', 'partyminder'); ?></h3>
                <p><?php _e('Be the first to start a conversation in this topic!', 'partyminder'); ?></p>
                <a href="#" class="pm-button start-conversation-btn" 
                   data-topic-id="<?php echo esc_attr($topic->id); ?>"
                   data-topic-name="<?php echo esc_attr($topic->name); ?>">
                    <span>✨</span>
                    <?php _e('Start the First Conversation', 'partyminder'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>