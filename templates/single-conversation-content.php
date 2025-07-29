<?php
/**
 * Single Conversation Content Template
 * Shows individual conversation with replies
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';

$conversation_manager = new PartyMinder_Conversation_Manager();

// Get slugs from URL
$topic_slug = get_query_var('conversation_topic');
$conversation_slug = get_query_var('conversation_slug');

if (!$topic_slug || !$conversation_slug) {
    wp_redirect(PartyMinder::get_conversations_url());
    exit;
}

// Get topic and conversation
$topic = $conversation_manager->get_topic_by_slug($topic_slug);
$conversation = $conversation_manager->get_conversation($conversation_slug, true);

// Get event data if this is an event conversation
$event_data = null;
if ($conversation && $conversation->event_id) {
    require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
    $event_manager = new PartyMinder_Event_Manager();
    $event_data = $event_manager->get_event($conversation->event_id);
}

if (!$topic || !$conversation) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
}

// Get replies
$replies = $conversation_manager->get_conversation_replies($conversation->id);

// Get current user info
$current_user = wp_get_current_user();
$user_email = is_user_logged_in() ? $current_user->user_email : '';
$is_following = false;

if ($user_email) {
    $is_following = $conversation_manager->is_following($conversation->id, $current_user->ID, $user_email);
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-single-conversation {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
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

.conversation-header {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.conversation-title-section {
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    color: white;
    padding: 30px;
}

.conversation-title {
    font-size: 2em;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.conversation-meta {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 0.9em;
    opacity: 0.9;
    flex-wrap: wrap;
}

.conversation-actions {
    padding: 20px 30px;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.conversation-stats {
    display: flex;
    gap: 20px;
    align-items: center;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 0.9em;
}

.original-post {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 30px;
}

.post-author {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.author-avatar {
    width: 50px;
    height: 50px;
    background: var(--pm-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2em;
}

.author-info h4 {
    margin: 0;
    color: #333;
}

.author-info .post-date {
    color: #666;
    font-size: 0.9em;
}

.post-content {
    line-height: 1.6;
    color: #333;
}

.replies-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.replies-header {
    background: #f8f9fa;
    padding: 20px 30px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.reply-item {
    padding: 20px 30px;
    border-bottom: 1px solid #f0f0f0;
}

.reply-item:last-child {
    border-bottom: none;
}

.reply-item.depth-1 { padding-left: 60px; }
.reply-item.depth-2 { padding-left: 90px; }
.reply-item.depth-3 { padding-left: 120px; }
.reply-item.depth-4 { padding-left: 150px; }
.reply-item.depth-5 { padding-left: 180px; }

.reply-form-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 30px;
    margin-top: 30px;
}

.reply-form .form-row {
    margin-bottom: 20px;
}

.reply-form label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

.reply-form textarea,
.reply-form input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
}

.reply-form textarea:focus,
.reply-form input:focus {
    outline: none;
    border-color: var(--pm-primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

.pm-button-small {
    padding: 6px 12px;
    font-size: 0.9em;
}

.reply-actions {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

.no-replies {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

@media (max-width: 768px) {
    .conversation-title {
        font-size: 1.5em;
    }
    
    .conversation-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .conversation-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .post-author {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .author-avatar {
        width: 40px;
        height: 40px;
        font-size: 1em;
    }
    
    .reply-item.depth-1,
    .reply-item.depth-2,
    .reply-item.depth-3,
    .reply-item.depth-4,
    .reply-item.depth-5 {
        padding-left: 30px;
    }
    
    .reply-actions {
        flex-wrap: wrap;
    }
}
</style>

<div class="partyminder-single-conversation">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <a href="<?php echo PartyMinder::get_conversations_url(); ?>">
            <?php _e('🏠 Community Conversations', 'partyminder'); ?>
        </a>
        <span> › </span>
        <a href="<?php echo home_url('/conversations/' . $topic->slug); ?>">
            <?php echo esc_html($topic->icon . ' ' . $topic->name); ?>
        </a>
        <span> › </span>
        <span><?php echo esc_html($conversation->title); ?></span>
    </div>

    <!-- Conversation Header -->
    <div class="conversation-header">
        <div class="conversation-title-section">
            <h1 class="conversation-title"><?php echo esc_html($conversation->title); ?></h1>
            <div class="conversation-meta">
                <span><?php printf(__('Started by %s', 'partyminder'), esc_html($conversation->author_name)); ?></span>
                <span><?php echo human_time_diff(strtotime($conversation->created_at), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?></span>
                <?php if ($event_data): ?>
                    <span><?php printf(__('for event: %s', 'partyminder'), esc_html($event_data->title)); ?></span>
                <?php endif; ?>
                <span><?php printf(__('in %s %s', 'partyminder'), esc_html($topic->icon), esc_html($topic->name)); ?></span>
            </div>
        </div>
        
        <div class="conversation-actions">
            <div class="conversation-stats">
                <div class="stat-item">
                    <span>💬</span>
                    <span><?php echo $conversation->reply_count; ?> <?php echo $conversation->reply_count === 1 ? __('reply', 'partyminder') : __('replies', 'partyminder'); ?></span>
                </div>
                <?php if ($conversation->reply_count > 0): ?>
                    <div class="stat-item">
                        <span>🕐</span>
                        <span><?php printf(__('Last activity %s ago', 'partyminder'), human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp'))); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php if ($event_data): ?>
                    <a href="<?php echo home_url('/events/' . $event_data->slug); ?>" class="pm-button pm-button-primary pm-button-small">
                        <span>📅</span> <?php _e('Go To Event', 'partyminder'); ?>
                    </a>
                <?php endif; ?>
                <?php if ($user_email): ?>
                    <button class="pm-button pm-button-small follow-btn" 
                            data-conversation-id="<?php echo esc_attr($conversation->id); ?>">
                        <?php if ($is_following): ?>
                            <span>🔕</span> <?php _e('Unfollow', 'partyminder'); ?>
                        <?php else: ?>
                            <span>🔔</span> <?php _e('Follow', 'partyminder'); ?>
                        <?php endif; ?>
                    </button>
                <?php endif; ?>
                <a href="#reply-form" class="pm-button pm-button-small">
                    <span>💬</span>
                    <?php _e('Reply', 'partyminder'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Original Post -->
    <div class="original-post">
        <div class="post-author">
            <div class="author-avatar">
                <?php echo strtoupper(substr($conversation->author_name, 0, 2)); ?>
            </div>
            <div class="author-info">
                <h4><?php echo esc_html($conversation->author_name); ?></h4>
                <div class="post-date"><?php echo date('F j, Y \a\t g:i A', strtotime($conversation->created_at)); ?></div>
            </div>
        </div>
        <div class="post-content">
            <?php echo wpautop($conversation->content); ?>
        </div>
    </div>

    <!-- Replies Section -->
    <div class="replies-section">
        <div class="replies-header">
            <h3><?php printf(__('%d %s', 'partyminder'), $conversation->reply_count, $conversation->reply_count === 1 ? __('Reply', 'partyminder') : __('Replies', 'partyminder')); ?></h3>
        </div>
        
        <?php if (!empty($replies)): ?>
            <?php foreach ($replies as $reply): ?>
                <div class="reply-item depth-<?php echo min($reply->depth_level, 5); ?>" id="reply-<?php echo $reply->id; ?>">
                    <div class="post-author">
                        <div class="author-avatar">
                            <?php echo strtoupper(substr($reply->author_name, 0, 2)); ?>
                        </div>
                        <div class="author-info">
                            <h4><?php echo esc_html($reply->author_name); ?></h4>
                            <div class="post-date"><?php echo date('F j, Y \a\t g:i A', strtotime($reply->created_at)); ?></div>
                        </div>
                    </div>
                    <div class="post-content">
                        <?php echo wpautop($reply->content); ?>
                    </div>
                    <div class="reply-actions">
                        <a href="#reply-form" class="pm-button pm-button-small reply-btn"
                           data-conversation-id="<?php echo esc_attr($conversation->id); ?>"
                           data-parent-reply-id="<?php echo esc_attr($reply->id); ?>">
                            <span>↩️</span>
                            <?php _e('Reply', 'partyminder'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-replies">
                <p><?php _e('No replies yet. Be the first to respond!', 'partyminder'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Reply Form -->
    <div class="reply-form-section" id="reply-form">
        <h3><?php _e('Add Your Reply', 'partyminder'); ?></h3>
        
        <form class="reply-form" method="post">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('partyminder_nonce'); ?>">
            <input type="hidden" name="action" value="partyminder_add_reply">
            <input type="hidden" name="conversation_id" value="<?php echo esc_attr($conversation->id); ?>">
            <input type="hidden" name="parent_reply_id" value="">
            
            <?php if (!is_user_logged_in()): ?>
                <div class="form-row">
                    <label for="reply_guest_name"><?php _e('Your Name *', 'partyminder'); ?></label>
                    <input type="text" id="reply_guest_name" name="guest_name" required>
                </div>
                <div class="form-row">
                    <label for="reply_guest_email"><?php _e('Your Email *', 'partyminder'); ?></label>
                    <input type="email" id="reply_guest_email" name="guest_email" required>
                </div>
            <?php endif; ?>
            
            <div class="form-row">
                <label for="reply_content"><?php _e('Your Reply *', 'partyminder'); ?></label>
                <textarea id="reply_content" name="content" required rows="6" 
                          placeholder="<?php esc_attr_e('Share your thoughts on this conversation...', 'partyminder'); ?>"></textarea>
            </div>
            
            <div class="form-row">
                <button type="submit" class="pm-button">
                    <span class="button-text"><?php _e('Post Reply', 'partyminder'); ?></span>
                    <span class="button-spinner" style="display: none;"><?php _e('Posting...', 'partyminder'); ?></span>
                </button>
                <a href="<?php echo home_url('/conversations/' . $topic->slug); ?>" class="pm-button pm-button-secondary">
                    <span>←</span>
                    <?php _e('Back to Topic', 'partyminder'); ?>
                </a>
            </div>
        </form>
    </div>
</div>