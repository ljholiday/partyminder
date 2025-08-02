<?php
/**
 * Dashboard Content Template - Clean Mobile-First Rebuild
 * Your PartyMinder home with conversations and navigation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-profile-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();
$conversation_manager = new PartyMinder_Conversation_Manager();

// Get current user info
$current_user = wp_get_current_user();
$user_logged_in = is_user_logged_in();

// Get user profile data if logged in
$profile_data = null;
if ($user_logged_in) {
    $profile_data = PartyMinder_Profile_Manager::get_user_profile($current_user->ID);
}

// Get user's recent activity
$recent_events = array();
if ($user_logged_in) {
    global $wpdb;
    $events_table = $wpdb->prefix . 'partyminder_events';
    
    // Get user's 3 most recent events (created or RSVP'd)
    $recent_events = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT e.*, 'created' as relationship_type FROM $events_table e 
         WHERE e.author_id = %d AND e.event_status = 'active'
         UNION
         SELECT DISTINCT e.*, 'rsvpd' as relationship_type FROM $events_table e
         INNER JOIN {$wpdb->prefix}partyminder_guests g ON e.id = g.event_id
         WHERE g.email = %s AND e.event_status = 'active'
         ORDER BY event_date DESC
         LIMIT 3",
        $current_user->ID,
        $current_user->user_email
    ));
}

// Get recent conversations for dashboard
$recent_conversations = $conversation_manager->get_recent_conversations(3, true);

// Get recent event conversations for dashboard, grouped by event
$recent_event_conversations = $conversation_manager->get_event_conversations(null, 10);

// Group conversations by event
$conversations_by_event = array();
if (!empty($recent_event_conversations)) {
    foreach ($recent_event_conversations as $conversation) {
        $event_key = $conversation->event_id;
        if (!isset($conversations_by_event[$event_key])) {
            $conversations_by_event[$event_key] = array(
                'event_title' => $conversation->event_title,
                'event_slug' => $conversation->event_slug,
                'event_date' => $conversation->event_date,
                'conversations' => array()
            );
        }
        $conversations_by_event[$event_key]['conversations'][] = $conversation;
    }
    
    // Sort events by most recent conversation activity
    uasort($conversations_by_event, function($a, $b) {
        $a_latest = max(array_map(function($conv) { return strtotime($conv->last_reply_date); }, $a['conversations']));
        $b_latest = max(array_map(function($conv) { return strtotime($conv->last_reply_date); }, $b['conversations']));
        return $b_latest - $a_latest;
    });
    
    // Limit to 3 most active events
    $conversations_by_event = array_slice($conversations_by_event, 0, 3, true);
}

?>

<div class="pm-container-wide">
    
    <!-- Dashboard Header -->
    <div class="card-header pm-mb-6">
        <?php if ($user_logged_in): ?>
            <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php printf(__('👋 Welcome back, %s!', 'partyminder'), esc_html($current_user->display_name)); ?></h1>
            <p class="text-muted"><?php _e('Your social event hub for connecting, planning, and celebrating together.', 'partyminder'); ?></p>
        <?php else: ?>
            <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php _e('🎉 Welcome to PartyMinder', 'partyminder'); ?></h1>
            <p class="text-muted"><?php _e('Your social event hub for connecting, planning, and celebrating together.', 'partyminder'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Mobile-First Responsive Layout -->
    <div class="pm-dashboard-grid">
        
        <!-- Main Content Column -->
        <div class="pm-dashboard-main">
            
            <?php if ($user_logged_in): ?>
            <!-- Events Section -->
            <div class="card pm-mb-6">
                <div class="card-header">
                    <h2 class="pm-heading pm-heading-md pm-mb-2">🎪 <?php _e('Recent Events', 'partyminder'); ?></h2>
                    <p class="text-muted pm-m-0"><?php _e('Events you\'ve created or RSVP\'d to', 'partyminder'); ?></p>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_events)): ?>
                        <div class="pm-flex pm-flex-column pm-gap-sm">
                            <?php foreach ($recent_events as $event): ?>
                                <?php 
                                $is_past = strtotime($event->event_date) < time();
                                $is_hosting = $event->relationship_type === 'created';
                                ?>
                                <div class="pm-flex pm-flex-between pm-flex-center-gap pm-p-3 pm-border pm-border-radius">
                                    <div class="pm-flex-1 pm-min-w-0">
                                        <h4 class="pm-heading pm-heading-sm pm-mb-1 pm-truncate">
                                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-text-primary pm-no-underline">
                                                <?php echo esc_html($event->title); ?>
                                            </a>
                                        </h4>
                                        <div class="pm-flex pm-flex-wrap pm-flex-center-gap pm-text-xs text-muted pm-mb-1">
                                            <span>📅 <?php echo date('M j, Y', strtotime($event->event_date)); ?></span>
                                            <?php if ($event->venue_info): ?>
                                                <span>📍 <?php echo esc_html(wp_trim_words($event->venue_info, 3)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="pm-badge pm-badge-<?php echo $is_hosting ? 'primary' : 'secondary'; ?> pm-text-xs">
                                            <?php echo $is_hosting ? __('Hosting', 'partyminder') : __('Attending', 'partyminder'); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="pm-text-center pm-p-6">
                            <div class="pm-text-4xl pm-mb-3">📅</div>
                            <h3 class="pm-heading pm-heading-sm pm-mb-2"><?php _e('No Recent Events', 'partyminder'); ?></h3>
                            <p class="text-muted pm-text-sm"><?php _e('Create an event or RSVP to events to see them here.', 'partyminder'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer pm-text-center">
                    <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="btn btn-secondary btn-small">
                        <?php _e('Browse All Events', 'partyminder'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Conversations Section -->
            <div class="card pm-mb-6">
                <div class="card-header">
                    <h2 class="pm-heading pm-heading-md pm-mb-2">💬 <?php _e('Community Conversations', 'partyminder'); ?></h2>
                    <p class="text-muted pm-m-0"><?php _e('Latest discussions about hosting and party planning', 'partyminder'); ?></p>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_conversations)): ?>
                        <div class="pm-flex pm-flex-column pm-gap-sm">
                            <?php foreach ($recent_conversations as $conversation): ?>
                                <div class="pm-flex pm-flex-between pm-flex-center-gap pm-p-3 pm-border pm-border-radius">
                                    <div class="pm-flex-1 pm-min-w-0">
                                        <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                            <?php if ($conversation->is_pinned): ?>
                                                <span class="pm-badge pm-badge-warning pm-text-xs">📌</span>
                                            <?php endif; ?>
                                            <h4 class="pm-heading pm-heading-sm pm-m-0 pm-truncate">
                                                <a href="<?php echo home_url('/conversations/' . $conversation->topic_slug . '/' . $conversation->slug); ?>" class="pm-text-primary pm-no-underline">
                                                    <?php echo esc_html($conversation->title); ?>
                                                </a>
                                            </h4>
                                        </div>
                                        <div class="text-muted pm-text-xs">
                                            <?php printf(__('by %s in %s • %s ago', 'partyminder'), 
                                                esc_html($conversation->author_name),
                                                esc_html($conversation->topic_name),
                                                human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp'))
                                            ); ?>
                                        </div>
                                    </div>
                                    <div class="pm-text-center pm-min-w-12">
                                        <div class="pm-stat-number pm-text-success pm-text-sm"><?php echo $conversation->reply_count; ?></div>
                                        <div class="pm-stat-label pm-text-xs"><?php _e('replies', 'partyminder'); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="pm-text-center pm-p-6">
                            <div class="pm-text-4xl pm-mb-3">💭</div>
                            <h3 class="pm-heading pm-heading-sm pm-mb-2"><?php _e('No Conversations Yet', 'partyminder'); ?></h3>
                            <p class="text-muted pm-text-sm"><?php _e('Be the first to start a discussion!', 'partyminder'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer pm-text-center">
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="btn btn-secondary btn-small">
                        <?php _e('View All Conversations', 'partyminder'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Event Conversations Section -->
            <div class="card pm-mb-6">
                <div class="card-header">
                    <h2 class="pm-heading pm-heading-md pm-mb-2">🎪 <?php _e('Event Planning Discussions', 'partyminder'); ?></h2>
                    <p class="text-muted pm-m-0"><?php _e('Active conversations about specific events', 'partyminder'); ?></p>
                </div>
                <div class="card-body">
                    <?php if (!empty($conversations_by_event)): ?>
                        <div class="event-conversations-grouped">
                            <?php foreach ($conversations_by_event as $event_id => $event_data): ?>
                                <?php 
                                $conversation_count = count($event_data['conversations']);
                                $event_date = new DateTime($event_data['event_date']);
                                $is_upcoming = $event_date > new DateTime();
                                ?>
                                <div class="event-conversation-group pm-mb-4">
                                    <!-- Event Header (Clickable to expand/collapse) -->
                                    <div class="event-group-header pm-flex pm-flex-between pm-flex-center-gap pm-p-3 pm-border pm-border-radius pm-cursor-pointer" 
                                         onclick="toggleEventConversations('event-<?php echo $event_id; ?>')">
                                        <div class="pm-flex pm-flex-center-gap pm-flex-1">
                                            <span class="pm-text-lg"><?php echo $is_upcoming ? '📅' : '🗓️'; ?></span>
                                            <div class="pm-flex-1 pm-min-w-0">
                                                <h4 class="pm-heading pm-heading-sm pm-m-0 pm-truncate pm-text-primary">
                                                    <?php echo esc_html($event_data['event_title']); ?>
                                                </h4>
                                                <div class="text-muted pm-text-xs">
                                                    <?php echo $event_date->format('M j, Y'); ?> • 
                                                    <?php printf(_n('%d conversation', '%d conversations', $conversation_count, 'partyminder'), $conversation_count); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="pm-flex pm-flex-center-gap">
                                            <div class="pm-stat pm-text-center">
                                                <div class="pm-stat-number pm-text-success pm-text-sm">
                                                    <?php echo array_sum(array_map(function($conv) { return $conv->reply_count; }, $event_data['conversations'])); ?>
                                                </div>
                                                <div class="pm-stat-label pm-text-xs"><?php _e('Replies', 'partyminder'); ?></div>
                                            </div>
                                            <span class="expand-icon text-muted" id="icon-event-<?php echo $event_id; ?>">▼</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Conversations List (Initially collapsed) -->
                                    <div class="event-conversations-list pm-ml-4 pm-mt-2" id="event-<?php echo $event_id; ?>" style="display: none;">
                                        <?php foreach ($event_data['conversations'] as $conversation): ?>
                                            <div class="pm-flex pm-flex-between pm-flex-center-gap pm-p-3 pm-border-left pm-pl-4 pm-mb-2">
                                                <div class="pm-flex-1 pm-min-w-0">
                                                    <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                                        <span class="pm-text-sm">💬</span>
                                                        <h5 class="pm-heading pm-heading-xs pm-m-0 pm-truncate">
                                                            <a href="<?php echo home_url('/conversations/' . ($conversation->topic_slug ?? 'general') . '/' . $conversation->slug); ?>" 
                                                               class="pm-text-primary pm-no-underline">
                                                                <?php echo esc_html($conversation->title); ?>
                                                            </a>
                                                        </h5>
                                                    </div>
                                                    <div class="text-muted pm-text-xs">
                                                        <?php printf(__('by %s • %s ago', 'partyminder'), 
                                                            esc_html($conversation->author_name),
                                                            human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp'))
                                                        ); ?>
                                                    </div>
                                                </div>
                                                <div class="pm-stat pm-text-center pm-min-w-8">
                                                    <div class="pm-stat-number pm-text-success pm-text-xs"><?php echo $conversation->reply_count; ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="pm-text-center pm-p-6">
                            <div class="pm-text-4xl pm-mb-3">🎪</div>
                            <h3 class="pm-heading pm-heading-sm pm-mb-2"><?php _e('No Event Discussions Yet', 'partyminder'); ?></h3>
                            <p class="text-muted pm-text-sm"><?php _e('Event conversations will appear here when people start planning together!', 'partyminder'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer pm-text-center">
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="btn btn-secondary btn-small">
                        <?php _e('View All Conversations', 'partyminder'); ?>
                    </a>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Login Section for Non-Logged-In Users -->
            <div class="card pm-mb-6">
                <div class="card-header">
                    <h2 class="pm-heading pm-heading-md pm-mb-2">🔐 <?php _e('Sign In to Get Started', 'partyminder'); ?></h2>
                    <p class="text-muted pm-m-0"><?php _e('Log in to create events, join conversations, and connect with the community', 'partyminder'); ?></p>
                </div>
                <div class="card-body pm-text-center pm-p-6">
                    <div class="pm-text-4xl pm-mb-4">🎉</div>
                    <h3 class="pm-heading pm-heading-md pm-mb-3"><?php _e('Welcome to PartyMinder!', 'partyminder'); ?></h3>
                    <p class="text-muted pm-text-lg pm-mb-4"><?php _e('Your social event hub for connecting, planning, and celebrating together.', 'partyminder'); ?></p>
                    <div class="pm-flex pm-flex-column pm-flex-center pm-gap-md pm-max-w-sm pm-mx-auto">
                        <a href="<?php echo esc_url(PartyMinder::get_login_url()); ?>" class="btn btn-primary btn-lg">
                            🔐 <?php _e('Sign In', 'partyminder'); ?>
                        </a>
                        <?php if (get_option('users_can_register')): ?>
                        <a href="<?php echo esc_url(add_query_arg('action', 'register', PartyMinder::get_login_url())); ?>" class="btn btn-secondary btn-lg">
                            ✨ <?php _e('Create Account', 'partyminder'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Preview Section for Non-Logged-In Users -->
            <div class="card pm-mb-6">
                <div class="card-header">
                    <h2 class="pm-heading pm-heading-md pm-mb-2">✨ <?php _e('What You Can Do', 'partyminder'); ?></h2>
                    <p class="text-muted pm-m-0"><?php _e('Discover all the features waiting for you', 'partyminder'); ?></p>
                </div>
                <div class="card-body">
                    <div class="pm-grid pm-grid-1 pm-gap-md">
                        <div class="pm-flex pm-flex-center-gap pm-p-4 pm-border pm-border-radius">
                            <div class="pm-text-2xl">🎪</div>
                            <div class="pm-flex-1">
                                <h4 class="pm-heading pm-heading-sm pm-mb-1"><?php _e('Create & Host Events', 'partyminder'); ?></h4>
                                <p class="text-muted pm-text-sm pm-m-0"><?php _e('Plan dinner parties, game nights, and social gatherings', 'partyminder'); ?></p>
                            </div>
                        </div>
                        <div class="pm-flex pm-flex-center-gap pm-p-4 pm-border pm-border-radius">
                            <div class="pm-text-2xl">💬</div>
                            <div class="pm-flex-1">
                                <h4 class="pm-heading pm-heading-sm pm-mb-1"><?php _e('Join Conversations', 'partyminder'); ?></h4>
                                <p class="text-muted pm-text-sm pm-m-0"><?php _e('Share tips and connect with fellow hosts and party-goers', 'partyminder'); ?></p>
                            </div>
                        </div>
                        <div class="pm-flex pm-flex-center-gap pm-p-4 pm-border pm-border-radius">
                            <div class="pm-text-2xl">👥</div>
                            <div class="pm-flex-1">
                                <h4 class="pm-heading pm-heading-sm pm-mb-1"><?php _e('Build Communities', 'partyminder'); ?></h4>
                                <p class="text-muted pm-text-sm pm-m-0"><?php _e('Create groups around shared interests and plan together', 'partyminder'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer pm-text-center">
                    <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="btn btn-outline btn-small">
                        <?php _e('Browse Public Events', 'partyminder'); ?>
                    </a>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
        
        <!-- Sidebar Column -->
        <div class="pm-dashboard-sidebar">
            
            <!-- Quick Navigation -->
            <div class="card pm-mb-4">
                <div class="card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
                </div>
                <div class="card-body pm-flex pm-flex-column pm-gap-sm">
                    <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn btn-primary">
                        ✨ <?php _e('Create Event', 'partyminder'); ?>
                    </a>
                    <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="btn btn-secondary">
                        🎪 <?php _e('Browse Events', 'partyminder'); ?>
                    </a>
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="btn btn-secondary">
                        💬 <?php _e('Join Conversations', 'partyminder'); ?>
                    </a>
                </div>
            </div>
            
            <!-- User Status -->
            <?php if (!$user_logged_in): ?>
            <div class="card pm-mb-4">
                <div class="card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">🔐 <?php _e('Get Started', 'partyminder'); ?></h3>
                </div>
                <div class="card-body">
                    <p class="text-muted pm-text-sm pm-mb-3"><?php _e('Log in to access all features and manage your events.', 'partyminder'); ?></p>
                    <div class="pm-flex pm-flex-column pm-gap-sm">
                        <a href="<?php echo esc_url(PartyMinder::get_login_url()); ?>" class="btn btn-primary">
                            <?php _e('Login', 'partyminder'); ?>
                        </a>
                        <?php if (get_option('users_can_register')): ?>
                        <a href="<?php echo esc_url(add_query_arg('action', 'register', PartyMinder::get_login_url())); ?>" class="btn btn-secondary">
                            <?php _e('Register', 'partyminder'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            
            <!-- User Profile Summary -->
            <div class="card pm-mb-4">
                <div class="card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">👤 <?php _e('Your Profile', 'partyminder'); ?></h3>
                </div>
                <div class="card-body">
                    <div class="pm-flex pm-flex-center-gap pm-mb-3">
                        <div class="pm-profile-avatar-small">
                            <?php echo get_avatar($current_user->ID, 48, '', '', array('class' => 'pm-profile-avatar-small-img')); ?>
                        </div>
                        <div class="pm-flex-1 pm-min-w-0">
                            <div class="pm-heading pm-heading-sm pm-m-0 pm-truncate"><?php echo esc_html($current_user->display_name); ?></div>
                            <?php if ($profile_data && $profile_data['location']): ?>
                            <div class="text-muted pm-text-xs">📍 <?php echo esc_html($profile_data['location']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pm-flex pm-flex-column pm-gap-xs">
                        <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="btn btn-secondary btn-small">
                            <?php _e('View Profile', 'partyminder'); ?>
                        </a>
                        <a href="<?php echo esc_url(PartyMinder::get_logout_url()); ?>" class="btn btn-secondary btn-small">
                            🚪 <?php _e('Logout', 'partyminder'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
            
            <!-- Community Activity -->
            <div class="card pm-mb-4">
                <div class="card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">🌟 <?php _e('Community Activity', 'partyminder'); ?></h3>
                </div>
                <div class="card-body">
                    <?php
                    // Include community activity feed
                    $user_id = null; // No specific user = community feed
                    $limit = 5;
                    $show_user_names = true; // Show who did what
                    $activity_types = array('events', 'conversations'); // Only public activities
                    $show_empty_state = true;
                    $empty_state_actions = true;
                    
                    include PARTYMINDER_PLUGIN_DIR . 'templates/components/activity-feed.php';
                    ?>
                </div>
            </div>
            
        </div>
        
    </div>
    
</div>

<script>
function toggleEventConversations(elementId) {
    const conversationsList = document.getElementById(elementId);
    const icon = document.getElementById('icon-' + elementId);
    
    if (conversationsList.style.display === 'none' || conversationsList.style.display === '') {
        conversationsList.style.display = 'block';
        icon.textContent = '▲';
    } else {
        conversationsList.style.display = 'none';
        icon.textContent = '▼';
    }
}
</script>