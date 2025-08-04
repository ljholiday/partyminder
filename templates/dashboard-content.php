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

// Set up template variables
$page_title = $user_logged_in 
    ? sprintf(__('Welcome back, %s!', 'partyminder'), esc_html($current_user->display_name))
    : __('Welcome to PartyMinder', 'partyminder');
$page_description = __('Your social event hub for connecting, planning, and celebrating together.', 'partyminder');

// Main content
ob_start();
?>
<?php if ($user_logged_in): ?>
<!-- Events Section -->
<div class="section mb-4">
    <div class="section-header">
        <h2 class="heading heading-md mb-4">🎪 <?php _e('Recent Events', 'partyminder'); ?></h2>
        <p class="text-muted"><?php _e('Events you\'ve created or RSVP\'d to', 'partyminder'); ?></p>
    </div>
                    <?php if (!empty($recent_events)): ?>
                        <div class="flex gap-4">
                            <?php foreach ($recent_events as $event): ?>
                                <?php 
                                $is_past = strtotime($event->event_date) < time();
                                $is_hosting = $event->relationship_type === 'created';
                                ?>
                                <div class="flex flex-between p-4 ">
                                    <div class="flex-1 ">
                                        <h4 class="heading heading-sm  ">
                                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="text-primary ">
                                                <?php echo esc_html($event->title); ?>
                                            </a>
                                        </h4>
                                        <div class="flex flex-wrap gap-4  text-muted ">
                                            <span>📅 <?php echo date('M j, Y', strtotime($event->event_date)); ?></span>
                                            <?php if ($event->venue_info): ?>
                                                <span>📍 <?php echo esc_html(wp_trim_words($event->venue_info, 3)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge badge-<?php echo $is_hosting ? 'primary' : 'secondary'; ?> ">
                                            <?php echo $is_hosting ? __('Hosting', 'partyminder') : __('Attending', 'partyminder'); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <div class=" mb-4">📅</div>
                            <h3 class="heading heading-sm mb-4"><?php _e('No Recent Events', 'partyminder'); ?></h3>
                            <p class="text-muted "><?php _e('Create an event or RSVP to events to see them here.', 'partyminder'); ?></p>
                        </div>
                    <?php endif; ?>
    <div class="text-center mt-4">
        <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="btn btn-secondary btn-small">
            <?php _e('Browse All Events', 'partyminder'); ?>
        </a>
    </div>
</div>

<!-- Conversations Section -->
<div class="section mb-4">
    <div class="section-header">
        <h2 class="heading heading-md mb-4">💬 <?php _e('Community Conversations', 'partyminder'); ?></h2>
        <p class="text-muted"><?php _e('Latest discussions about hosting and party planning', 'partyminder'); ?></p>
    </div>
                    <?php if (!empty($recent_conversations)): ?>
                        <div class="flex gap-4">
                            <?php foreach ($recent_conversations as $conversation): ?>
                                <div class="flex flex-between p-4 ">
                                    <div class="flex-1 ">
                                        <div class="flex gap-4 ">
                                            <?php if ($conversation->is_pinned): ?>
                                                <span class="badge badge-secondary ">📌</span>
                                            <?php endif; ?>
                                            <h4 class="heading heading-sm  ">
                                                <a href="<?php echo home_url('/conversations/' . $conversation->topic_slug . '/' . $conversation->slug); ?>" class="text-primary ">
                                                    <?php echo esc_html($conversation->title); ?>
                                                </a>
                                            </h4>
                                        </div>
                                        <div class="text-muted ">
                                            <?php printf(__('by %s in %s • %s ago', 'partyminder'), 
                                                esc_html($conversation->author_name),
                                                esc_html($conversation->topic_name),
                                                human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp'))
                                            ); ?>
                                        </div>
                                    </div>
                                    <div class="text-center ">
                                        <div class="stat-number text-primary "><?php echo $conversation->reply_count; ?></div>
                                        <div class="stat-label "><?php _e('replies', 'partyminder'); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <div class=" mb-4">💭</div>
                            <h3 class="heading heading-sm mb-4"><?php _e('No Conversations Yet', 'partyminder'); ?></h3>
                            <p class="text-muted "><?php _e('Be the first to start a discussion!', 'partyminder'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="btn btn-secondary btn-small">
                        <?php _e('View All Conversations', 'partyminder'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Event Conversations Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="heading heading-md mb-4">🎪 <?php _e('Event Planning Discussions', 'partyminder'); ?></h2>
                    <p class="text-muted "><?php _e('Active conversations about specific events', 'partyminder'); ?></p>
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
                                <div class="event-conversation-group mb-4">
                                    <!-- Event Header (Clickable to expand/collapse) -->
                                    <div class="event-group-header flex flex-between p-4  " 
                                         onclick="toggleEventConversations('event-<?php echo $event_id; ?>')">
                                        <div class="flex gap-4 flex-1">
                                            <span class=""><?php echo $is_upcoming ? '📅' : '🗓️'; ?></span>
                                            <div class="flex-1 ">
                                                <h4 class="heading heading-sm   text-primary">
                                                    <?php echo esc_html($event_data['event_title']); ?>
                                                </h4>
                                                <div class="text-muted ">
                                                    <?php echo $event_date->format('M j, Y'); ?> • 
                                                    <?php printf(_n('%d conversation', '%d conversations', $conversation_count, 'partyminder'), $conversation_count); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex gap-4">
                                            <div class="stat text-center">
                                                <div class="stat-number text-primary ">
                                                    <?php echo array_sum(array_map(function($conv) { return $conv->reply_count; }, $event_data['conversations'])); ?>
                                                </div>
                                                <div class="stat-label "><?php _e('Replies', 'partyminder'); ?></div>
                                            </div>
                                            <span class="expand-icon text-muted" id="icon-event-<?php echo $event_id; ?>">▼</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Conversations List (Initially collapsed) -->
                                    <div class="event-conversations-list  mt-4" id="event-<?php echo $event_id; ?>" style="display: none;">
                                        <?php foreach ($event_data['conversations'] as $conversation): ?>
                                            <div class="flex flex-between p-4 mb-4 mb-4">
                                                <div class="flex-1 ">
                                                    <div class="flex gap-4 ">
                                                        <span class="">💬</span>
                                                        <h5 class="heading heading-sm  ">
                                                            <a href="<?php echo home_url('/conversations/' . ($conversation->topic_slug ?? 'general') . '/' . $conversation->slug); ?>" 
                                                               class="text-primary ">
                                                                <?php echo esc_html($conversation->title); ?>
                                                            </a>
                                                        </h5>
                                                    </div>
                                                    <div class="text-muted ">
                                                        <?php printf(__('by %s • %s ago', 'partyminder'), 
                                                            esc_html($conversation->author_name),
                                                            human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp'))
                                                        ); ?>
                                                    </div>
                                                </div>
                                                <div class="stat text-center ">
                                                    <div class="stat-number text-primary "><?php echo $conversation->reply_count; ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <div class=" mb-4">🎪</div>
                            <h3 class="heading heading-sm mb-4"><?php _e('No Event Discussions Yet', 'partyminder'); ?></h3>
                            <p class="text-muted "><?php _e('Event conversations will appear here when people start planning together!', 'partyminder'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="btn btn-secondary btn-small">
                        <?php _e('View All Conversations', 'partyminder'); ?>
                    </a>
                </div>
            </div>
<?php else: ?>
<!-- Login Section for Non-Logged-In Users -->
<div class="section mb-4">
    <div class="section-header">
        <h2 class="heading heading-md mb-4">🔐 <?php _e('Sign In to Get Started', 'partyminder'); ?></h2>
        <p class="text-muted"><?php _e('Log in to create events, join conversations, and connect with the community', 'partyminder'); ?></p>
    </div>
    <div class="text-center p-4">
        <div class="text-xl mb-4">🎉</div>
        <h3 class="heading heading-md mb-4"><?php _e('Welcome to PartyMinder!', 'partyminder'); ?></h3>
        <p class="text-muted mb-4"><?php _e('Your social event hub for connecting, planning, and celebrating together.', 'partyminder'); ?></p>
        <div class="flex gap-4 justify-center">
            <a href="<?php echo esc_url(PartyMinder::get_login_url()); ?>" class="btn btn-lg">
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
<div class="section mb-4">
    <div class="section-header">
        <h2 class="heading heading-md mb-4">✨ <?php _e('What You Can Do', 'partyminder'); ?></h2>
        <p class="text-muted"><?php _e('Discover all the features waiting for you', 'partyminder'); ?></p>
    </div>
    <div class="grid gap-4">
        <div class="flex gap-4 p-4">
            <div class="text-xl">🎪</div>
            <div class="flex-1">
                <h4 class="heading heading-sm"><?php _e('Create & Host Events', 'partyminder'); ?></h4>
                <p class="text-muted"><?php _e('Plan dinner parties, game nights, and social gatherings', 'partyminder'); ?></p>
            </div>
        </div>
        <div class="flex gap-4 p-4">
            <div class="text-xl">💬</div>
            <div class="flex-1">
                <h4 class="heading heading-sm"><?php _e('Join Conversations', 'partyminder'); ?></h4>
                <p class="text-muted"><?php _e('Share tips and connect with fellow hosts and party-goers', 'partyminder'); ?></p>
            </div>
        </div>
        <div class="flex gap-4 p-4">
            <div class="text-xl">👥</div>
            <div class="flex-1">
                <h4 class="heading heading-sm"><?php _e('Build Communities', 'partyminder'); ?></h4>
                <p class="text-muted"><?php _e('Create groups around shared interests and plan together', 'partyminder'); ?></p>
            </div>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="btn btn-secondary btn-small">
            <?php _e('Browse Public Events', 'partyminder'); ?>
        </a>
    </div>
</div>

<?php endif; ?>
<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>
<!-- Quick Navigation -->
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
    </div>
    <div class="flex gap-4 flex-wrap">
        <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn">
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
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">🔐 <?php _e('Get Started', 'partyminder'); ?></h3>
    </div>
    <p class="text-muted mb-4"><?php _e('Log in to access all features and manage your events.', 'partyminder'); ?></p>
    <div class="flex gap-4">
        <a href="<?php echo esc_url(PartyMinder::get_login_url()); ?>" class="btn">
            <?php _e('Login', 'partyminder'); ?>
        </a>
        <?php if (get_option('users_can_register')): ?>
        <a href="<?php echo esc_url(add_query_arg('action', 'register', PartyMinder::get_login_url())); ?>" class="btn btn-secondary">
            <?php _e('Register', 'partyminder'); ?>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>

<!-- User Profile Summary -->
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">👤 <?php _e('Your Profile', 'partyminder'); ?></h3>
    </div>
    <div class="flex gap-4 mb-4">
        <div class="avatar">
            <?php echo get_avatar($current_user->ID, 48, '', '', array('class' => 'avatar-img')); ?>
        </div>
        <div class="flex-1">
            <div class="heading heading-sm"><?php echo esc_html($current_user->display_name); ?></div>
            <?php if ($profile_data && $profile_data['location']): ?>
            <div class="text-muted">📍 <?php echo esc_html($profile_data['location']); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="flex gap-4">
        <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="btn btn-secondary btn-small">
            <?php _e('View Profile', 'partyminder'); ?>
        </a>
        <a href="<?php echo esc_url(PartyMinder::get_logout_url()); ?>" class="btn btn-secondary btn-small">
            🚪 <?php _e('Logout', 'partyminder'); ?>
        </a>
    </div>
</div>

<?php endif; ?>
<!-- Community Activity -->
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">🌟 <?php _e('Community Activity', 'partyminder'); ?></h3>
    </div>
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
<?php
$sidebar_content = ob_get_clean();

// Include two-column template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
?>

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