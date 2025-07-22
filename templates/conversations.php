<?php
/**
 * Community Conversations Template
 * Two-column forum-style layout
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';

$conversation_manager = new PartyMinder_Conversation_Manager();
$event_manager = new PartyMinder_Event_Manager();

// Get current user info
$current_user = wp_get_current_user();
$user_email = '';
if (isset($_GET['email']) && is_email($_GET['email'])) {
    $user_email = sanitize_email($_GET['email']);
} elseif (is_user_logged_in()) {
    $user_email = $current_user->user_email;
}

// Get data for the page
$topics = $conversation_manager->get_topics();
$recent_conversations = $conversation_manager->get_recent_conversations(5, true); // Exclude event conversations
$event_conversations = $conversation_manager->get_event_conversations(null, 5);
$stats = $conversation_manager->get_stats();

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-conversations {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.conversations-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    color: white;
    border-radius: 12px;
}

.conversations-header h1 {
    font-size: 2.5em;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.conversations-header p {
    font-size: 1.2em;
    margin: 0;
    opacity: 0.9;
}

.conversations-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

@media (max-width: 768px) {
    .conversations-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

/* LEFT COLUMN - Community Conversations */
.community-conversations-column {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.column-header {
    background: var(--pm-primary);
    color: white;
    padding: 20px;
    text-align: center;
}

.column-header h2 {
    margin: 0;
    font-size: 1.4em;
}

.topic-section {
    border-bottom: 1px solid #f0f0f0;
}

.topic-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.topic-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.topic-icon {
    font-size: 1.2em;
}

.topic-name {
    font-weight: bold;
    color: #333;
    margin: 0;
}

.topic-description {
    font-size: 0.85em;
    color: #666;
    margin: 0;
}

.conversation-count {
    background: var(--pm-primary);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}

.conversations-list {
    padding: 0;
    margin: 0;
    list-style: none;
}

.conversation-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: background 0.2s ease;
}

.conversation-item:hover {
    background: #f8f9fa;
}

.conversation-item:last-child {
    border-bottom: none;
}

.conversation-main {
    flex: 1;
}

.conversation-title {
    font-weight: bold;
    color: #333;
    text-decoration: none;
    display: block;
    margin-bottom: 5px;
    line-height: 1.3;
}

.conversation-title:hover {
    color: var(--pm-primary);
}

.conversation-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 0.85em;
    color: #666;
}

.conversation-stats {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85em;
    color: #666;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.pinned-badge {
    background: #ffc107;
    color: #333;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7em;
    font-weight: bold;
    text-transform: uppercase;
}

.no-conversations {
    padding: 40px 20px;
    text-align: center;
    color: #666;
}

.start-conversation-btn {
    background: var(--pm-primary);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
    display: inline-block;
    margin-top: 10px;
    cursor: pointer;
}

.start-conversation-btn:hover {
    opacity: 0.9;
    color: white;
}

/* RIGHT COLUMN - Event Activity */
.event-activity-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.activity-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.activity-card-header {
    background: var(--pm-secondary);
    color: white;
    padding: 15px 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 8px;
}

.activity-card-content {
    padding: 20px;
}

.event-conversation {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.event-conversation:last-child {
    border-bottom: none;
}

.event-title {
    font-weight: bold;
    color: #333;
    text-decoration: none;
    display: block;
    margin-bottom: 5px;
}

.event-title:hover {
    color: var(--pm-primary);
}

.event-conversation-meta {
    font-size: 0.85em;
    color: #666;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.community-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.stat-box {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-number {
    font-size: 1.5em;
    font-weight: bold;
    color: var(--pm-primary);
    display: block;
}

.stat-label {
    font-size: 0.85em;
    color: #666;
    margin-top: 5px;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-button {
    background: var(--pm-primary);
    color: white;
    border: none;
    padding: 12px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
    text-align: center;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.action-button:hover {
    opacity: 0.9;
    color: white;
}

.action-button.secondary {
    background: #6c757d;
}

@media (max-width: 768px) {
    .conversations-header h1 {
        font-size: 2em;
    }
    
    .conversations-header p {
        font-size: 1em;
    }
    
    .conversation-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .community-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="partyminder-conversations">
    <!-- Header -->
    <div class="conversations-header">
        <h1><?php _e('🎉 Community Conversations', 'partyminder'); ?></h1>
        <p><?php _e('Connect, share tips, and plan amazing gatherings with fellow hosts and guests', 'partyminder'); ?></p>
    </div>

    <!-- Two-column layout -->
    <div class="conversations-layout">
        <!-- LEFT COLUMN - Community Conversations -->
        <div class="community-conversations-column">
            <div class="column-header">
                <h2><?php _e('💬 Community Conversations', 'partyminder'); ?></h2>
            </div>
            
            <?php if (!empty($topics)): ?>
                <?php foreach ($topics as $topic): ?>
                    <?php
                    $topic_conversations = $conversation_manager->get_conversations_by_topic($topic->id, 3);
                    ?>
                    <div class="topic-section">
                        <div class="topic-header">
                            <div class="topic-info">
                                <span class="topic-icon"><?php echo esc_html($topic->icon); ?></span>
                                <div>
                                    <h3 class="topic-name"><?php echo esc_html($topic->name); ?></h3>
                                    <p class="topic-description"><?php echo esc_html($topic->description); ?></p>
                                </div>
                            </div>
                            <span class="conversation-count"><?php echo count($topic_conversations); ?></span>
                        </div>
                        
                        <?php if (!empty($topic_conversations)): ?>
                            <ul class="conversations-list">
                                <?php foreach ($topic_conversations as $conversation): ?>
                                    <li class="conversation-item">
                                        <div class="conversation-main">
                                            <a href="#" class="conversation-title">
                                                <?php if ($conversation->is_pinned): ?>
                                                    <span class="pinned-badge"><?php _e('Pinned', 'partyminder'); ?></span>
                                                <?php endif; ?>
                                                <?php echo esc_html($conversation->title); ?>
                                            </a>
                                            <div class="conversation-meta">
                                                <span><?php printf(__('by %s', 'partyminder'), esc_html($conversation->author_name)); ?></span>
                                                <span><?php echo human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?></span>
                                            </div>
                                        </div>
                                        <div class="conversation-stats">
                                            <div class="stat-item">
                                                <span>💬</span>
                                                <span><?php echo $conversation->reply_count; ?></span>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="no-conversations">
                                <p><?php _e('No conversations yet in this topic.', 'partyminder'); ?></p>
                                <a href="#" class="start-conversation-btn">
                                    <?php _e('Start the Conversation', 'partyminder'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-conversations">
                    <p><?php _e('No conversation topics available.', 'partyminder'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN - Event Activity -->
        <div class="event-activity-column">
            <!-- Event Conversations -->
            <div class="activity-card">
                <div class="activity-card-header">
                    <span>🎪</span>
                    <?php _e('Event Planning', 'partyminder'); ?>
                </div>
                <div class="activity-card-content">
                    <?php if (!empty($event_conversations)): ?>
                        <?php foreach ($event_conversations as $event_conv): ?>
                            <div class="event-conversation">
                                <a href="<?php echo home_url('/events/' . $event_conv->event_slug); ?>" class="event-title">
                                    <?php echo esc_html($event_conv->event_title); ?>
                                </a>
                                <div class="event-conversation-meta">
                                    <span><?php echo date('M j', strtotime($event_conv->event_date)); ?></span>
                                    <span><?php echo $event_conv->reply_count; ?> <?php _e('comments', 'partyminder'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; margin: 0;">
                            <?php _e('No event conversations yet.', 'partyminder'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Community Stats -->
            <div class="activity-card">
                <div class="activity-card-header">
                    <span>📊</span>
                    <?php _e('Community Stats', 'partyminder'); ?>
                </div>
                <div class="activity-card-content">
                    <div class="community-stats">
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $stats->total_conversations; ?></span>
                            <div class="stat-label"><?php _e('Conversations', 'partyminder'); ?></div>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $stats->total_replies; ?></span>
                            <div class="stat-label"><?php _e('Messages', 'partyminder'); ?></div>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $stats->active_conversations; ?></span>
                            <div class="stat-label"><?php _e('Active This Week', 'partyminder'); ?></div>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $stats->total_follows; ?></span>
                            <div class="stat-label"><?php _e('Following', 'partyminder'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="activity-card">
                <div class="activity-card-header">
                    <span>⚡</span>
                    <?php _e('Quick Actions', 'partyminder'); ?>
                </div>
                <div class="activity-card-content">
                    <div class="quick-actions">
                        <a href="#" class="action-button">
                            <span>💬</span>
                            <?php _e('Start New Conversation', 'partyminder'); ?>
                        </a>
                        <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="action-button secondary">
                            <span>✨</span>
                            <?php _e('Create Event', 'partyminder'); ?>
                        </a>
                        <a href="<?php echo PartyMinder::get_events_page_url(); ?>" class="action-button secondary">
                            <span>🎉</span>
                            <?php _e('Browse Events', 'partyminder'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>