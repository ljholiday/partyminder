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


<div class="partyminder-conversations pm-container-wide">
    <!-- Header -->
    <div class="pm-card-header pm-mb-6">
        <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php _e('💬 Community Conversations', 'partyminder'); ?></h1>
        <p class="pm-text-muted"><?php _e('Connect, share tips, and plan amazing gatherings with fellow hosts and guests', 'partyminder'); ?></p>
    </div>

    <!-- Two-column layout -->
    <div class="conversations-layout">
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