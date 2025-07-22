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

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-topic-conversations {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.topic-header {
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    color: white;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 30px;
}

.topic-header h1 {
    font-size: 2.5em;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.topic-header p {
    font-size: 1.2em;
    margin: 0;
    opacity: 0.9;
}

.topic-actions {
    margin-top: 20px;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.conversations-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.conversations-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.conversation-item {
    padding: 20px 25px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s ease;
}

.conversation-item:hover {
    background: #f8f9fa;
}

.conversation-item:last-child {
    border-bottom: none;
}

.conversation-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
}

.conversation-content {
    flex: 1;
}

.conversation-title {
    font-size: 1.2em;
    font-weight: bold;
    color: #333;
    text-decoration: none;
    display: block;
    margin-bottom: 8px;
    line-height: 1.4;
}

.conversation-title:hover {
    color: var(--pm-primary);
}

.conversation-excerpt {
    color: #666;
    line-height: 1.5;
    margin-bottom: 10px;
}

.conversation-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 0.9em;
    color: #888;
}

.conversation-stats {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
}

.reply-count {
    background: var(--pm-primary);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}

.last-activity {
    font-size: 0.85em;
    color: #666;
}

.breadcrumbs {
    background: #f8f9fa;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.breadcrumbs a {
    color: var(--pm-primary);
    text-decoration: none;
}

.breadcrumbs a:hover {
    text-decoration: underline;
}

.no-conversations {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-conversations-icon {
    font-size: 4em;
    margin-bottom: 20px;
}

.pm-button {
    background: var(--pm-primary);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.pm-button:hover {
    opacity: 0.9;
    color: white;
}

.pm-button-secondary {
    background: #6c757d;
}

@media (max-width: 768px) {
    .topic-header h1 {
        font-size: 2em;
        flex-direction: column;
        gap: 10px;
    }
    
    .conversation-main {
        flex-direction: column;
        gap: 10px;
    }
    
    .conversation-stats {
        align-items: flex-start;
    }
    
    .topic-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .conversations-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
        text-align: center;
    }
}
</style>

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
                                <div class="reply-count" style="background: #28a745;">
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