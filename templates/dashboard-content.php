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

?>

<div class="pm-container-wide">
    
    <!-- Dashboard Header -->
    <div class="pm-card-header pm-mb-6">
        <?php if ($user_logged_in): ?>
            <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php printf(__('👋 Welcome back, %s!', 'partyminder'), esc_html($current_user->display_name)); ?></h1>
            <p class="pm-text-muted"><?php _e('Your social event hub for connecting, planning, and celebrating together.', 'partyminder'); ?></p>
        <?php else: ?>
            <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php _e('🎉 Welcome to PartyMinder', 'partyminder'); ?></h1>
            <p class="pm-text-muted"><?php _e('Your social event hub for connecting, planning, and celebrating together.', 'partyminder'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Mobile-First Responsive Layout -->
    <div class="pm-dashboard-grid">
        
        <!-- Main Content Column -->
        <div class="pm-dashboard-main">
            
            <!-- Events Section -->
            <div class="pm-card pm-mb-6">
                <div class="pm-card-header">
                    <h2 class="pm-heading pm-heading-md pm-mb-2">🎪 <?php _e('Recent Events', 'partyminder'); ?></h2>
                    <p class="pm-text-muted pm-m-0"><?php _e('Events you\'ve created or RSVP\'d to', 'partyminder'); ?></p>
                </div>
                <div class="pm-card-body">
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
                                        <div class="pm-flex pm-flex-wrap pm-flex-center-gap pm-text-xs pm-text-muted pm-mb-1">
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
                            <p class="pm-text-muted pm-text-sm"><?php _e('Create an event or RSVP to events to see them here.', 'partyminder'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="pm-card-footer pm-text-center">
                    <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="pm-button pm-button-secondary pm-button-small">
                        <?php _e('Browse All Events', 'partyminder'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Conversations Section -->
            <div class="pm-card pm-mb-6">
                <div class="pm-card-header">
                    <h2 class="pm-heading pm-heading-md pm-mb-2">💬 <?php _e('Community Conversations', 'partyminder'); ?></h2>
                    <p class="pm-text-muted pm-m-0"><?php _e('Latest discussions about hosting and party planning', 'partyminder'); ?></p>
                </div>
                <div class="pm-card-body">
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
                                        <div class="pm-text-muted pm-text-xs">
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
                            <p class="pm-text-muted pm-text-sm"><?php _e('Be the first to start a discussion!', 'partyminder'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="pm-card-footer pm-text-center">
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="pm-button pm-button-secondary pm-button-small">
                        <?php _e('View All Conversations', 'partyminder'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Column -->
        <div class="pm-dashboard-sidebar">
            
            <!-- Quick Navigation -->
            <div class="pm-card pm-mb-4">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body pm-flex pm-flex-column pm-gap-sm">
                    <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="pm-button pm-button-primary">
                        ✨ <?php _e('Create Event', 'partyminder'); ?>
                    </a>
                    <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="pm-button pm-button-secondary">
                        🎪 <?php _e('Browse Events', 'partyminder'); ?>
                    </a>
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="pm-button pm-button-secondary">
                        💬 <?php _e('Join Conversations', 'partyminder'); ?>
                    </a>
                </div>
            </div>
            
            <!-- User Status -->
            <?php if (!$user_logged_in): ?>
            <div class="pm-card pm-mb-4">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">🔐 <?php _e('Get Started', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body">
                    <p class="pm-text-muted pm-text-sm pm-mb-3"><?php _e('Log in to access all features and manage your events.', 'partyminder'); ?></p>
                    <div class="pm-flex pm-flex-column pm-gap-sm">
                        <a href="<?php echo esc_url(PartyMinder::get_login_url()); ?>" class="pm-button pm-button-primary">
                            <?php _e('Login', 'partyminder'); ?>
                        </a>
                        <?php if (get_option('users_can_register')): ?>
                        <a href="<?php echo esc_url(add_query_arg('action', 'register', PartyMinder::get_login_url())); ?>" class="pm-button pm-button-secondary">
                            <?php _e('Register', 'partyminder'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            
            <!-- User Profile Summary -->
            <div class="pm-card pm-mb-4">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">👤 <?php _e('Your Profile', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body">
                    <div class="pm-flex pm-flex-center-gap pm-mb-3">
                        <div class="pm-profile-avatar-small">
                            <?php echo get_avatar($current_user->ID, 48, '', '', array('class' => 'pm-profile-avatar-small-img')); ?>
                        </div>
                        <div class="pm-flex-1 pm-min-w-0">
                            <div class="pm-heading pm-heading-sm pm-m-0 pm-truncate"><?php echo esc_html($current_user->display_name); ?></div>
                            <?php if ($profile_data && $profile_data['location']): ?>
                            <div class="pm-text-muted pm-text-xs">📍 <?php echo esc_html($profile_data['location']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pm-flex pm-flex-column pm-gap-xs">
                        <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="pm-button pm-button-secondary pm-button-small">
                            <?php _e('View Profile', 'partyminder'); ?>
                        </a>
                        <a href="<?php echo esc_url(PartyMinder::get_logout_url()); ?>" class="pm-button pm-button-secondary pm-button-small">
                            🚪 <?php _e('Logout', 'partyminder'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
            
            <!-- Community Activity -->
            <div class="pm-card pm-mb-4">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">🌟 <?php _e('Community Activity', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body">
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