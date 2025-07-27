<?php
/**
 * Dashboard Content Template - Two-Column Layout
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

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

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

?>


<div class="partyminder-dashboard-content">
    
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

    <!-- Two-Column Layout -->
    <div class="pm-grid pm-grid-2">
        
        <!-- Main Column: Events & Conversations -->
        <div class="pm-flex pm-flex-column pm-gap-lg">
            
            <!-- Events Section -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h2 class="pm-heading pm-heading-md">🎪 <?php _e('Recent Events', 'partyminder'); ?></h2>
                    <p class="pm-text-muted"><?php _e('Events you\'ve created or RSVP\'d to', 'partyminder'); ?></p>
                    
                    <!-- Event Filters -->
                    <div class="pm-flex pm-flex-center-gap pm-mt-3" id="event-filters">
                        <button class="pm-button pm-button-small pm-button-secondary filter-btn active" data-filter="all"><?php _e('All', 'partyminder'); ?></button>
                        <button class="pm-button pm-button-small pm-button-secondary filter-btn" data-filter="upcoming"><?php _e('Upcoming', 'partyminder'); ?></button>
                        <button class="pm-button pm-button-small pm-button-secondary filter-btn" data-filter="past"><?php _e('Past', 'partyminder'); ?></button>
                        <button class="pm-button pm-button-small pm-button-secondary filter-btn" data-filter="hosting"><?php _e('Hosting', 'partyminder'); ?></button>
                    </div>
                </div>
                <div class="pm-card-body">
                    <?php if (!empty($recent_events)): ?>
                        <div class="pm-space-y-3" id="events-list">
                            <?php foreach ($recent_events as $event): ?>
                                <?php 
                                $is_past = strtotime($event->event_date) < time();
                                $is_hosting = $event->relationship_type === 'created';
                                $event_classes = array();
                                if ($is_past) $event_classes[] = 'event-past';
                                if (!$is_past) $event_classes[] = 'event-upcoming';
                                if ($is_hosting) $event_classes[] = 'event-hosting';
                                ?>
                                <div class="pm-flex pm-p-3 pm-border pm-border-radius event-item <?php echo implode(' ', $event_classes); ?>" data-filter-tags="<?php echo implode(' ', $event_classes); ?>">
                                    <div class="pm-flex-1">
                                        <h4 class="pm-heading pm-heading-sm pm-mb-1">
                                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-text-primary">
                                                <?php echo esc_html($event->title); ?>
                                            </a>
                                        </h4>
                                        <div class="pm-flex pm-flex-center-gap pm-text-sm pm-text-muted pm-mb-2">
                                            <span>📅 <?php echo date('M j, Y', strtotime($event->event_date)); ?></span>
                                            <?php if ($event->venue_info): ?>
                                                <span>📍 <?php echo esc_html(wp_trim_words($event->venue_info, 3)); ?></span>
                                            <?php endif; ?>
                                            <span class="pm-badge pm-badge-<?php echo $is_hosting ? 'primary' : 'secondary'; ?> pm-text-xs">
                                                <?php echo $is_hosting ? __('Hosting', 'partyminder') : __('Attending', 'partyminder'); ?>
                                            </span>
                                        </div>
                                        <?php if ($event->description): ?>
                                            <p class="pm-text-muted pm-text-sm pm-m-0"><?php echo esc_html(wp_trim_words($event->description, 15)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="pm-text-center pm-p-6">
                            <div class="pm-mb-4 pm-text-4xl">📅</div>
                            <h3 class="pm-heading pm-heading-md pm-mb-2"><?php _e('No Recent Events', 'partyminder'); ?></h3>
                            <p class="pm-text-muted pm-mb-4"><?php _e('Create an event or RSVP to events to see them here.', 'partyminder'); ?></p>
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
            <div class="pm-card">
                <div class="pm-card-header">
                    <h2 class="pm-heading pm-heading-md">💬 <?php _e('Community Conversations', 'partyminder'); ?></h2>
                    <p class="pm-text-muted"><?php _e('Latest discussions about hosting and party planning', 'partyminder'); ?></p>
                    
                    <!-- Conversation Filters -->
                    <div class="pm-flex pm-flex-center-gap pm-mt-3" id="conversation-filters">
                        <button class="pm-button pm-button-small pm-button-secondary filter-btn active" data-filter="all"><?php _e('All', 'partyminder'); ?></button>
                        <button class="pm-button pm-button-small pm-button-secondary filter-btn" data-filter="popular"><?php _e('Popular', 'partyminder'); ?></button>
                        <button class="pm-button pm-button-small pm-button-secondary filter-btn" data-filter="recent"><?php _e('Recent', 'partyminder'); ?></button>
                    </div>
                </div>
                <div class="pm-card-body">
                    <!-- Conversations Content (simplified) -->
                    <div class="pm-text-center pm-p-4">
                        <div class="pm-mb-3 pm-text-xl">💭</div>
                        <p class="pm-text-muted pm-mb-3"><?php _e('Join conversations with fellow hosts and guests', 'partyminder'); ?></p>
                    </div>
                </div>
                <div class="pm-card-footer pm-text-center">
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="pm-button pm-button-secondary pm-button-small">
                        <?php _e('View All Conversations', 'partyminder'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Sidebar: Navigation & Quick Info -->
        <div class="pm-flex pm-flex-column pm-gap-lg">
            
            <!-- Quick Navigation -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm">⚡ <?php _e('Quick Navigation', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body pm-p-0">
                    <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" class="pm-nav-link">
                        <span class="pm-text-primary" class="pm-text-xl pm-mr-3">🎪</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('Browse Events', 'partyminder'); ?></div>
                            <div class="pm-text-muted" class="pm-text-sm"><?php _e('Discover upcoming events', 'partyminder'); ?></div>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" class="pm-nav-link">
                        <span class="pm-text-primary" class="pm-text-xl pm-mr-3">✨</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('Create Event', 'partyminder'); ?></div>
                            <div class="pm-text-muted" class="pm-text-sm"><?php _e('Host your own party', 'partyminder'); ?></div>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" class="pm-nav-link">
                        <span class="pm-text-primary" class="pm-text-xl pm-mr-3">📋</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('My Events', 'partyminder'); ?></div>
                            <div class="pm-text-muted" class="pm-text-sm"><?php _e('Manage your events & RSVPs', 'partyminder'); ?></div>
                        </div>
                    </a>
                    
                    <?php if ($user_logged_in): ?>
                    <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" class="pm-nav-link">
                        <span class="pm-text-primary" class="pm-text-xl pm-mr-3">👤</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('My Profile', 'partyminder'); ?></div>
                            <div class="pm-text-muted" class="pm-text-sm"><?php _e('Update your preferences', 'partyminder'); ?></div>
                        </div>
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" class="pm-nav-link">
                        <span class="pm-text-primary" class="pm-text-xl pm-mr-3">💬</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('Conversations', 'partyminder'); ?></div>
                            <div class="pm-text-muted" class="pm-text-sm"><?php _e('Connect with the community', 'partyminder'); ?></div>
                        </div>
                    </a>
                    <?php if ($user_logged_in): ?>
                    <a href="<?php echo esc_url(PartyMinder::get_logout_url()); ?>" class="pm-flex pm-p-4" class="pm-nav-link">
                        <span class="pm-text-danger" class="pm-text-xl pm-mr-3">🚪</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('Logout', 'partyminder'); ?></div>
                            <div class="pm-text-muted" class="pm-text-sm"><?php _e('Sign out of your account', 'partyminder'); ?></div>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- User Status / Login -->
            <?php if (!$user_logged_in): ?>
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm">🔐 <?php _e('Get Started', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body">
                    <p class="pm-text-muted pm-mb-4"><?php _e('Log in to access all features and manage your events.', 'partyminder'); ?></p>
                    <a href="<?php echo esc_url(PartyMinder::get_login_url()); ?>" class="pm-button pm-button-primary pm-w-full pm-mb-2">
                        <?php _e('Login', 'partyminder'); ?>
                    </a>
                    <?php if (get_option('users_can_register')): ?>
                    <a href="<?php echo esc_url(add_query_arg('action', 'register', PartyMinder::get_login_url())); ?>" class="pm-button pm-button-secondary pm-w-full">
                        <?php _e('Register', 'partyminder'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            
            <!-- User Profile Summary -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm">👤 <?php _e('Your Profile', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body">
                    <div class="pm-flex pm-mb-4 pm-items-center">
                        <div class="pm-mr-3">
                        <?php if ($profile_data && $profile_data['profile_image']): ?>
                            <img src="<?php echo esc_url($profile_data['profile_image']); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="pm-avatar-md">
                        <?php else: ?>
                            <div class="pm-flex pm-flex-center pm-rounded-full pm-text-primary pm-avatar-md pm-avatar-placeholder">
                                <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        </div>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php echo esc_html($current_user->display_name); ?></div>
                            <?php if ($profile_data && $profile_data['location']): ?>
                            <div class="pm-text-muted" class="pm-text-sm">📍 <?php echo esc_html($profile_data['location']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pm-flex pm-flex-between pm-p-4 pm-bg-transparent pm-border pm-rounded">
                        <div class="pm-text-center">
                            <div class="pm-heading pm-heading-sm pm-text-primary pm-m-0"><?php echo intval($profile_data['events_hosted'] ?? 0); ?></div>
                            <div class="pm-text-muted" class="pm-text-xs"><?php _e('Hosted', 'partyminder'); ?></div>
                        </div>
                        <div class="pm-text-center">
                            <div class="pm-heading pm-heading-sm pm-text-primary pm-m-0"><?php echo intval($profile_data['events_attended'] ?? 0); ?></div>
                            <div class="pm-text-muted" class="pm-text-xs"><?php _e('Attended', 'partyminder'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <?php if (!empty($recent_events)): ?>
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm">📅 <?php _e('Recent Activity', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body pm-p-0">
                    <?php foreach ($recent_events as $event): ?>
                    <?php
                    $event_date = new DateTime($event->event_date);
                    $is_future = $event_date > new DateTime();
                    ?>
                    <div class="pm-flex pm-p-4 pm-border-bottom pm-items-center">
                        <div class="pm-flex pm-flex-center pm-rounded-full pm-text-white pm-avatar-sm pm-avatar-primary pm-mr-3">
                            <?php echo $event->relationship_type === 'created' ? '🎨' : '💌'; ?>
                        </div>
                        <div class="pm-flex-1">
                            <div class="pm-heading pm-heading-sm pm-m-0">
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-text-primary" >
                                    <?php echo esc_html($event->title); ?>
                                </a>
                            </div>
                            <div class="pm-text-muted" class="pm-text-sm">
                                <?php if ($event->relationship_type === 'created'): ?>
                                    <?php _e('You created', 'partyminder'); ?>
                                <?php else: ?>
                                    <?php _e('You RSVP\'d', 'partyminder'); ?>
                                <?php endif; ?>
                                • <?php echo $event_date->format('M j'); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
            
        </div>
        
    </div>
    
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Event Filter functionality
    const eventFilters = document.querySelectorAll('#event-filters .filter-btn');
    const eventItems = document.querySelectorAll('#events-list .event-item');
    
    eventFilters.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active button
            eventFilters.forEach(btn => btn.classList.remove('active', 'pm-button-primary'));
            eventFilters.forEach(btn => btn.classList.add('pm-button-secondary'));
            this.classList.remove('pm-button-secondary');
            this.classList.add('active', 'pm-button-primary');
            
            // Filter events
            eventItems.forEach(item => {
                const tags = item.getAttribute('data-filter-tags') || '';
                if (filter === 'all' || tags.includes('event-' + filter)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Conversation Filter functionality
    const conversationFilters = document.querySelectorAll('#conversation-filters .filter-btn');
    
    conversationFilters.forEach(button => {
        button.addEventListener('click', function() {
            // Update active button
            conversationFilters.forEach(btn => btn.classList.remove('active', 'pm-button-primary'));
            conversationFilters.forEach(btn => btn.classList.add('pm-button-secondary'));
            this.classList.remove('pm-button-secondary');
            this.classList.add('active', 'pm-button-primary');
            
            // Note: Conversation filtering logic would go here when conversations are implemented
        });
    });
});
</script>