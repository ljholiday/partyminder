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

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
/* Dashboard-specific dynamic styles */
.partyminder-dashboard-content .dashboard-header h1 {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-dashboard-content .quick-nav .nav-item:hover {
    background: <?php echo esc_attr($primary_color); ?>15;
    border-left-color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-dashboard-content .quick-nav .nav-item .nav-icon {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-dashboard-content .recent-activity .activity-item .activity-icon {
    background: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-dashboard-content .pm-button {
    background: <?php echo esc_attr($primary_color); ?>;
}
</style>

<div class="partyminder-dashboard-content">
    
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <?php if ($user_logged_in): ?>
            <h1><?php printf(__('👋 Welcome back, %s!', 'partyminder'), esc_html($current_user->display_name)); ?></h1>
            <p><?php _e('Your social event hub for connecting, planning, and celebrating together.', 'partyminder'); ?></p>
        <?php else: ?>
            <h1><?php _e('🎉 Welcome to PartyMinder', 'partyminder'); ?></h1>
            <p><?php _e('Your social event hub for connecting, planning, and celebrating together.', 'partyminder'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Two-Column Layout -->
    <div class="dashboard-layout">
        
        <!-- Main Column: Conversations -->
        <div class="dashboard-main">
            <div class="main-section">
                <div class="section-header">
                    <h2>💬 <?php _e('Community Conversations', 'partyminder'); ?></h2>
                    <p><?php _e('Join the latest discussions about hosting, planning, and party tips.', 'partyminder'); ?></p>
                </div>
                
                <!-- Conversations Content -->
                <div class="conversations-embed">
                    <?php
                    // Include simplified conversations content for the dashboard
                    $conversations_atts = array('limit' => 5, 'show_form' => false);
                    
                    // Check if conversations file exists
                    $conversations_file = PARTYMINDER_PLUGIN_DIR . 'templates/conversations-content.php';
                    if (file_exists($conversations_file)) {
                        include $conversations_file;
                    } else {
                        // Fallback if conversations not available
                        echo '<div class="conversations-placeholder">';
                        echo '<div class="placeholder-icon">💭</div>';
                        echo '<h3>' . __('Conversations Coming Soon', 'partyminder') . '</h3>';
                        echo '<p>' . __('Community conversations will appear here when available.', 'partyminder') . '</p>';
                        echo '<a href="' . esc_url(PartyMinder::get_conversations_url()) . '" class="pm-button pm-button-secondary">';
                        echo __('View Conversations', 'partyminder');
                        echo '</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <div class="section-footer">
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="pm-button pm-button-secondary">
                        <?php _e('View All Conversations', 'partyminder'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Sidebar: Navigation & Quick Info -->
        <div class="dashboard-sidebar">
            
            <!-- Quick Navigation -->
            <div class="sidebar-section">
                <h3><?php _e('⚡ Quick Navigation', 'partyminder'); ?></h3>
                <div class="quick-nav">
                    <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="nav-item">
                        <span class="nav-icon">🎪</span>
                        <div class="nav-content">
                            <div class="nav-title"><?php _e('Browse Events', 'partyminder'); ?></div>
                            <div class="nav-desc"><?php _e('Discover upcoming events', 'partyminder'); ?></div>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="nav-item">
                        <span class="nav-icon">✨</span>
                        <div class="nav-content">
                            <div class="nav-title"><?php _e('Create Event', 'partyminder'); ?></div>
                            <div class="nav-desc"><?php _e('Host your own party', 'partyminder'); ?></div>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="nav-item">
                        <span class="nav-icon">📋</span>
                        <div class="nav-content">
                            <div class="nav-title"><?php _e('My Events', 'partyminder'); ?></div>
                            <div class="nav-desc"><?php _e('Manage your events & RSVPs', 'partyminder'); ?></div>
                        </div>
                    </a>
                    
                    <?php if ($user_logged_in): ?>
                    <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="nav-item">
                        <span class="nav-icon">👤</span>
                        <div class="nav-content">
                            <div class="nav-title"><?php _e('My Profile', 'partyminder'); ?></div>
                            <div class="nav-desc"><?php _e('Update your preferences', 'partyminder'); ?></div>
                        </div>
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="nav-item">
                        <span class="nav-icon">💬</span>
                        <div class="nav-content">
                            <div class="nav-title"><?php _e('Conversations', 'partyminder'); ?></div>
                            <div class="nav-desc"><?php _e('Connect with the community', 'partyminder'); ?></div>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- User Status / Login -->
            <?php if (!$user_logged_in): ?>
            <div class="sidebar-section">
                <h3><?php _e('🔐 Get Started', 'partyminder'); ?></h3>
                <div class="login-prompt">
                    <p><?php _e('Log in to access all features and manage your events.', 'partyminder'); ?></p>
                    <a href="<?php echo esc_url(PartyMinder::get_login_url()); ?>" class="pm-button">
                        <?php _e('Login', 'partyminder'); ?>
                    </a>
                    <?php if (get_option('users_can_register')): ?>
                    <a href="<?php echo esc_url(add_query_arg('action', 'register', PartyMinder::get_login_url())); ?>" class="pm-button pm-button-secondary">
                        <?php _e('Register', 'partyminder'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            
            <!-- User Profile Summary -->
            <div class="sidebar-section">
                <h3><?php _e('👤 Your Profile', 'partyminder'); ?></h3>
                <div class="profile-summary">
                    <div class="profile-avatar">
                        <?php if ($profile_data && $profile_data['profile_image']): ?>
                            <img src="<?php echo esc_url($profile_data['profile_image']); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-name"><?php echo esc_html($current_user->display_name); ?></div>
                        <?php if ($profile_data && $profile_data['location']): ?>
                        <div class="profile-location">📍 <?php echo esc_html($profile_data['location']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-number"><?php echo intval($profile_data['events_hosted'] ?? 0); ?></span>
                            <span class="stat-label"><?php _e('Hosted', 'partyminder'); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?php echo intval($profile_data['events_attended'] ?? 0); ?></span>
                            <span class="stat-label"><?php _e('Attended', 'partyminder'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <?php if (!empty($recent_events)): ?>
            <div class="sidebar-section">
                <h3><?php _e('📅 Recent Activity', 'partyminder'); ?></h3>
                <div class="recent-activity">
                    <?php foreach ($recent_events as $event): ?>
                    <?php
                    $event_date = new DateTime($event->event_date);
                    $is_future = $event_date > new DateTime();
                    ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php echo $event->relationship_type === 'created' ? '🎨' : '💌'; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>">
                                    <?php echo esc_html($event->title); ?>
                                </a>
                            </div>
                            <div class="activity-meta">
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