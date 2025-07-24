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
        
        <!-- Main Column: Conversations -->
        <div class="pm-card">
            <div class="pm-card-header">
                <h2 class="pm-heading pm-heading-md">💬 <?php _e('Community Conversations', 'partyminder'); ?></h2>
                <p class="pm-text-muted"><?php _e('Join the latest discussions about hosting, planning, and party tips.', 'partyminder'); ?></p>
            </div>
            <div class="pm-card-body">
                
                <!-- Conversations Content -->
                <?php
                // Include simplified conversations content for the dashboard
                $conversations_atts = array('limit' => 5, 'show_form' => false);
                
                // Check if conversations file exists
                $conversations_file = PARTYMINDER_PLUGIN_DIR . 'templates/conversations-content.php';
                if (file_exists($conversations_file)) {
                    include $conversations_file;
                } else {
                    // Fallback if conversations not available
                    echo '<div class="pm-text-center pm-p-6">';
                    echo '<div class="pm-mb-4" style="font-size: 3rem;">💭</div>';
                    echo '<h3 class="pm-heading pm-heading-md pm-mb-2">' . __('Conversations Coming Soon', 'partyminder') . '</h3>';
                    echo '<p class="pm-text-muted pm-mb-4">' . __('Community conversations will appear here when available.', 'partyminder') . '</p>';
                    echo '<a href="' . esc_url(PartyMinder::get_conversations_url()) . '" class="pm-button pm-button-primary">';
                    echo __('View Conversations', 'partyminder');
                    echo '</a>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="pm-card-footer pm-text-center">
                <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="pm-button pm-button-primary">
                    <?php _e('View All Conversations', 'partyminder'); ?>
                </a>
            </div>
        </div>
        
        <!-- Sidebar: Navigation & Quick Info -->
        <div class="pm-flex" style="flex-direction: column; gap: 20px;">
            
            <!-- Quick Navigation -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm">⚡ <?php _e('Quick Navigation', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body pm-p-0">
                    <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" style="align-items: center; text-decoration: none; color: inherit; transition: all 0.2s ease;" onmouseover="this.style.background='var(--pm-surface)'" onmouseout="this.style.background='transparent'">
                        <span class="pm-text-primary" style="font-size: 1.5rem; margin-right: 12px;">🎪</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('Browse Events', 'partyminder'); ?></div>
                            <div class="pm-text-muted" style="font-size: 0.875rem;"><?php _e('Discover upcoming events', 'partyminder'); ?></div>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" style="align-items: center; text-decoration: none; color: inherit; transition: all 0.2s ease;" onmouseover="this.style.background='var(--pm-surface)'" onmouseout="this.style.background='transparent'">
                        <span class="pm-text-primary" style="font-size: 1.5rem; margin-right: 12px;">✨</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('Create Event', 'partyminder'); ?></div>
                            <div class="pm-text-muted" style="font-size: 0.875rem;"><?php _e('Host your own party', 'partyminder'); ?></div>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" style="align-items: center; text-decoration: none; color: inherit; transition: all 0.2s ease;" onmouseover="this.style.background='var(--pm-surface)'" onmouseout="this.style.background='transparent'">
                        <span class="pm-text-primary" style="font-size: 1.5rem; margin-right: 12px;">📋</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('My Events', 'partyminder'); ?></div>
                            <div class="pm-text-muted" style="font-size: 0.875rem;"><?php _e('Manage your events & RSVPs', 'partyminder'); ?></div>
                        </div>
                    </a>
                    
                    <?php if ($user_logged_in): ?>
                    <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" style="align-items: center; text-decoration: none; color: inherit; transition: all 0.2s ease;" onmouseover="this.style.background='var(--pm-surface)'" onmouseout="this.style.background='transparent'">
                        <span class="pm-text-primary" style="font-size: 1.5rem; margin-right: 12px;">👤</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('My Profile', 'partyminder'); ?></div>
                            <div class="pm-text-muted" style="font-size: 0.875rem;"><?php _e('Update your preferences', 'partyminder'); ?></div>
                        </div>
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="pm-flex pm-p-4 pm-border-bottom" style="align-items: center; text-decoration: none; color: inherit; transition: all 0.2s ease;" onmouseover="this.style.background='var(--pm-surface)'" onmouseout="this.style.background='transparent'">
                        <span class="pm-text-primary" style="font-size: 1.5rem; margin-right: 12px;">💬</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('Conversations', 'partyminder'); ?></div>
                            <div class="pm-text-muted" style="font-size: 0.875rem;"><?php _e('Connect with the community', 'partyminder'); ?></div>
                        </div>
                    </a>
                    <?php if ($user_logged_in): ?>
                    <a href="<?php echo esc_url(PartyMinder::get_logout_url()); ?>" class="pm-flex pm-p-4" style="align-items: center; text-decoration: none; color: inherit; transition: all 0.2s ease;" onmouseover="this.style.background='var(--pm-surface)'" onmouseout="this.style.background='transparent'">
                        <span class="pm-text-danger" style="font-size: 1.5rem; margin-right: 12px;">🚪</span>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php _e('Logout', 'partyminder'); ?></div>
                            <div class="pm-text-muted" style="font-size: 0.875rem;"><?php _e('Sign out of your account', 'partyminder'); ?></div>
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
                    <div class="pm-flex pm-mb-4" style="align-items: center;">
                        <div style="margin-right: 12px;">
                        <?php if ($profile_data && $profile_data['profile_image']): ?>
                            <img src="<?php echo esc_url($profile_data['profile_image']); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <div class="pm-flex pm-flex-center pm-rounded-full pm-text-primary" style="width: 48px; height: 48px; background: var(--pm-surface); border: 2px solid var(--pm-primary); font-weight: 600;">
                                <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        </div>
                        <div>
                            <div class="pm-heading pm-heading-sm pm-m-0"><?php echo esc_html($current_user->display_name); ?></div>
                            <?php if ($profile_data && $profile_data['location']): ?>
                            <div class="pm-text-muted" style="font-size: 0.875rem;">📍 <?php echo esc_html($profile_data['location']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pm-flex pm-flex-between pm-p-4 pm-bg-transparent pm-border pm-rounded">
                        <div class="pm-text-center">
                            <div class="pm-heading pm-heading-sm pm-text-primary pm-m-0"><?php echo intval($profile_data['events_hosted'] ?? 0); ?></div>
                            <div class="pm-text-muted" style="font-size: 0.75rem;"><?php _e('Hosted', 'partyminder'); ?></div>
                        </div>
                        <div class="pm-text-center">
                            <div class="pm-heading pm-heading-sm pm-text-primary pm-m-0"><?php echo intval($profile_data['events_attended'] ?? 0); ?></div>
                            <div class="pm-text-muted" style="font-size: 0.75rem;"><?php _e('Attended', 'partyminder'); ?></div>
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
                    <div class="pm-flex pm-p-4 pm-border-bottom" style="align-items: center;">
                        <div class="pm-flex pm-flex-center pm-rounded-full pm-text-primary" style="width: 32px; height: 32px; background: var(--pm-primary); color: white; margin-right: 12px;">
                            <?php echo $event->relationship_type === 'created' ? '🎨' : '💌'; ?>
                        </div>
                        <div style="flex: 1;">
                            <div class="pm-heading pm-heading-sm pm-m-0">
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-text-primary" style="text-decoration: none;">
                                    <?php echo esc_html($event->title); ?>
                                </a>
                            </div>
                            <div class="pm-text-muted" style="font-size: 0.875rem;">
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
    // Dashboard-specific JavaScript can go here
    console.log('PartyMinder Dashboard loaded');
});
</script>