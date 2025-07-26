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
    transition: background 0.2s ease;
}

.topic-header:hover {
    background: #e9ecef;
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

/* Modal Styles */
.partyminder-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.partyminder-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.partyminder-modal-content {
    background: white;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.partyminder-modal-overlay.active .partyminder-modal-content {
    transform: scale(1);
}

.modal-header {
    padding: 20px 30px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: var(--pm-primary);
    color: white;
    border-radius: 12px 12px 0 0;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.3em;
}

.modal-header p {
    margin: 5px 0 0 0;
    opacity: 0.9;
    font-size: 0.9em;
}

.close-modal {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s ease;
}

.close-modal:hover {
    background: rgba(255, 255, 255, 0.2);
}

.modal-body {
    padding: 30px;
}

.conversation-form .form-row {
    margin-bottom: 20px;
}

.conversation-form label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

.conversation-form input,
.conversation-form textarea,
.conversation-form select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
}

.conversation-form input:focus,
.conversation-form textarea:focus,
.conversation-form select:focus {
    outline: none;
    border-color: var(--pm-primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.conversation-form input.error,
.conversation-form textarea.error {
    border-color: #ef4444;
}

.field-error {
    color: #ef4444;
    font-size: 0.85em;
    margin-top: 4px;
    display: block;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.button-spinner {
    display: none;
}

@media (max-width: 768px) {
    .partyminder-modal-content {
        width: 95%;
        margin: 20px;
    }
    
    .modal-header,
    .modal-body {
        padding: 20px;
    }
    
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
    
    .form-actions {
        flex-direction: column;
    }
    
    .pm-button {
        justify-content: center;
    }
}
</style>

<div class="partyminder-conversations pm-container">
    <!-- Header -->
    <div class="pm-card-header pm-mb-6">
        <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php _e('💬 Community Conversations', 'partyminder'); ?></h1>
        <p class="pm-text-muted"><?php _e('Connect, share tips, and plan amazing gatherings with fellow hosts and guests', 'partyminder'); ?></p>
    </div>

    <!-- Two-column layout -->
    <div class="pm-grid pm-grid-2">
        <!-- LEFT COLUMN - Community Conversations -->
        <div class="pm-card">
            <div class="pm-card-header">
                <h2 class="pm-heading pm-heading-md pm-text-primary pm-m-0"><?php _e('Discussion Topics', 'partyminder'); ?></h2>
                <p class="pm-text-muted pm-mt-2"><?php _e('Join conversations about hosting and party planning', 'partyminder'); ?></p>
            </div>
            
            <div class="pm-card-body">
            
            <?php if (!empty($topics)): ?>
                <?php foreach ($topics as $topic): ?>
                    <?php
                    $topic_conversations = $conversation_manager->get_conversations_by_topic($topic->id, 3);
                    ?>
                    <div class="pm-mb-6">
                        <div class="pm-flex pm-flex-between pm-flex-center-gap pm-mb-3">
                            <div class="pm-flex pm-flex-center-gap">
                                <span class="pm-text-xl"><?php echo esc_html($topic->icon); ?></span>
                                <div>
                                    <h3 class="pm-heading pm-heading-sm pm-m-0">
                                        <a href="<?php echo home_url('/conversations/' . $topic->slug); ?>" class="pm-text-primary pm-no-underline">
                                            <?php echo esc_html($topic->name); ?>
                                        </a>
                                    </h3>
                                    <p class="pm-text-muted pm-m-0 pm-text-sm"><?php echo esc_html($topic->description); ?></p>
                                </div>
                            </div>
                            <div class="pm-stat">
                                <div class="pm-stat-number pm-text-primary"><?php echo count($topic_conversations); ?></div>
                                <div class="pm-stat-label"><?php _e('Posts', 'partyminder'); ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($topic_conversations)): ?>
                            <div class="pm-pl-4 pm-border-left">
                                <?php foreach ($topic_conversations as $conversation): ?>
                                    <div class="pm-flex pm-flex-between pm-flex-center-gap pm-mb-3">
                                        <div class="pm-flex-1 pm-min-w-0">
                                            <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                                <?php if ($conversation->is_pinned): ?>
                                                    <span class="pm-badge pm-badge-warning pm-text-xs">📌 <?php _e('Pinned', 'partyminder'); ?></span>
                                                <?php endif; ?>
                                                <h4 class="pm-heading pm-heading-xs pm-m-0 pm-truncate">
                                                    <a href="<?php echo home_url('/conversations/' . $topic->slug . '/' . $conversation->slug); ?>" class="pm-text-primary pm-no-underline">
                                                        <?php echo esc_html($conversation->title); ?>
                                                    </a>
                                                </h4>
                                            </div>
                                            <div class="pm-text-muted pm-text-xs">
                                                <?php printf(__('by %s • %s ago', 'partyminder'), 
                                                    esc_html($conversation->author_name),
                                                    human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp'))
                                                ); ?>
                                            </div>
                                        </div>
                                        <div class="pm-stat pm-text-center pm-min-w-10">
                                            <div class="pm-stat-number pm-text-success pm-text-sm"><?php echo $conversation->reply_count; ?></div>
                                            <div class="pm-stat-label pm-text-xs"><?php _e('Replies', 'partyminder'); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="pm-text-center pm-p-4 pm-card-dashed">
                                <p class="pm-text-muted pm-mb-3"><?php _e('No conversations yet in this topic.', 'partyminder'); ?></p>
                                <?php if (is_user_logged_in()): ?>
                                <a href="#" class="pm-button pm-button-primary pm-button-small start-conversation-btn" 
                                   data-topic-id="<?php echo esc_attr($topic->id); ?>"
                                   data-topic-name="<?php echo esc_attr($topic->name); ?>">
                                    <span>💬</span>
                                    <?php _e('Start the Conversation', 'partyminder'); ?>
                                </a>
                                <?php else: ?>
                                <a href="<?php echo add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), PartyMinder::get_login_url()); ?>" class="pm-button pm-button-primary pm-button-small">
                                    <span>🔑</span>
                                    <?php _e('Login to Start Conversation', 'partyminder'); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="pm-text-center pm-p-6">
                    <p class="pm-text-muted"><?php _e('No conversation topics available.', 'partyminder'); ?></p>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN - Event Activity -->
        <div class="pm-flex pm-flex-column pm-gap-lg">
            <!-- Event Conversations -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">🎪 <?php _e('Event Planning', 'partyminder'); ?></h3>
                    <p class="pm-text-muted pm-mt-2"><?php _e('Discussions about specific events', 'partyminder'); ?></p>
                </div>
                <div class="pm-card-body">
                    <?php if (!empty($event_conversations)): ?>
                        <?php foreach ($event_conversations as $event_conv): ?>
                            <div class="pm-mb-4 pm-pb-3 pm-border-bottom">
                                <h4 class="pm-heading pm-heading-xs pm-mb-2">
                                    <a href="<?php echo home_url('/events/' . $event_conv->event_slug); ?>" class="pm-text-primary pm-no-underline">
                                        <?php echo esc_html($event_conv->event_title); ?>
                                    </a>
                                </h4>
                                <div class="pm-flex pm-flex-between pm-flex-center-gap">
                                    <span class="pm-text-muted pm-text-xs">
                                        📅 <?php echo date('M j', strtotime($event_conv->event_date)); ?>
                                    </span>
                                    <div class="pm-stat">
                                        <div class="pm-stat-number pm-text-success pm-text-sm"><?php echo $event_conv->reply_count; ?></div>
                                        <div class="pm-stat-label pm-text-xs"><?php _e('Comments', 'partyminder'); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="pm-text-center pm-text-muted pm-m-0">
                            <?php _e('No event conversations yet.', 'partyminder'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Community Stats -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <span>📊</span>
                    <?php _e('Community Stats', 'partyminder'); ?>
                </div>
                <div class="pm-card-body">
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
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body">
                    <div class="pm-flex pm-flex-center-gap pm-flex-column">
                        <?php if (is_user_logged_in()): ?>
                        <a href="#" class="pm-button pm-button-primary start-conversation-btn" 
                           data-topic-id="" data-topic-name="">
                            <span>💬</span>
                            <?php _e('Start New Conversation', 'partyminder'); ?>
                        </a>
                        <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-button pm-button-secondary">
                            <span>🎉</span>
                            <?php _e('Create Event', 'partyminder'); ?>
                        </a>
                        <?php else: ?>
                        <a href="<?php echo add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), PartyMinder::get_login_url()); ?>" class="pm-button pm-button-primary">
                            <span>🔑</span>
                            <?php _e('Login to Participate', 'partyminder'); ?>
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo PartyMinder::get_events_page_url(); ?>" class="pm-button pm-button-secondary">
                            <span>📅</span>
                            <?php _e('Browse Events', 'partyminder'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>