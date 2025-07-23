<?php
/**
 * My Events Content Template - Theme Integrated
 * Content only version for theme integration via the_content filter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

// Get shortcode attributes
$show_past = filter_var($atts['show_past'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Get current user info
$current_user = wp_get_current_user();
$user_email = '';

// Check if user provided email via URL parameter
if (isset($_GET['email']) && is_email($_GET['email'])) {
    $user_email = sanitize_email($_GET['email']);
} elseif (is_user_logged_in()) {
    $user_email = $current_user->user_email;
}

// Get user's created events (if logged in)
$created_events = array();
if (is_user_logged_in()) {
    global $wpdb;
    $events_table = $wpdb->prefix . 'partyminder_events';
    
    $query = "SELECT * FROM $events_table 
              WHERE author_id = %d 
              AND event_status = 'active'";
    
    if (!$show_past) {
        $query .= " AND event_date >= CURDATE()";
    }
    
    $query .= " ORDER BY event_date " . ($show_past ? "DESC" : "ASC");
    
    $created_events = $wpdb->get_results($wpdb->prepare($query, $current_user->ID));
    
    // Add guest stats to each event
    foreach ($created_events as $event) {
        $event->guest_stats = $event_manager->get_guest_stats($event->id);
    }
}

// Get user's RSVP'd events (by email)
$rsvp_events = array();
if ($user_email) {
    global $wpdb;
    $guests_table = $wpdb->prefix . 'partyminder_guests';
    $events_table = $wpdb->prefix . 'partyminder_events';
    
    $query = "SELECT DISTINCT e.*, g.status as rsvp_status FROM $events_table e 
              INNER JOIN $guests_table g ON e.id = g.event_id 
              WHERE g.email = %s 
              AND e.event_status = 'active'";
    
    if (!$show_past) {
        $query .= " AND e.event_date >= CURDATE()";
    }
    
    $query .= " ORDER BY e.event_date " . ($show_past ? "DESC" : "ASC");
    
    $rsvp_events = $wpdb->get_results($wpdb->prepare($query, $user_email));
    
    // Add guest stats to each event and preserve RSVP status
    foreach ($rsvp_events as $event) {
        $event->guest_stats = $event_manager->get_guest_stats($event->id);
        // RSVP status is already in $event->rsvp_status from the query
    }
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
$button_style = get_option('partyminder_button_style', 'rounded');
?>

<style>
/* Dynamic color styles for PartyMinder My Events */
.partyminder-my-events-content .my-events-header h2 {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-my-events-content .section-title {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-my-events-content .event-count {
    background: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-my-events-content .event-title a:hover {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-my-events-content .stat-number {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-my-events-content .pm-button {
    background: <?php echo esc_attr($primary_color); ?>;
}
</style>

<div class="partyminder-my-events-content">
    
    <!-- Dashboard Link -->
    <div class="partyminder-breadcrumb">
        <a href="<?php echo esc_url(PartyMinder::get_dashboard_url()); ?>" class="breadcrumb-link">
            🏠 <?php _e('Dashboard', 'partyminder'); ?>
        </a>
        <span class="breadcrumb-separator">→</span>
        <span class="breadcrumb-current"><?php _e('My Events', 'partyminder'); ?></span>
    </div>
    
    <!-- Header -->
    <div class="my-events-header">
        <h2>
            <?php if (is_user_logged_in()): ?>
                <?php printf(__('👋 Hi %s, here are your events', 'partyminder'), esc_html($current_user->display_name)); ?>
            <?php else: ?>
                <?php _e('🎉 My Events', 'partyminder'); ?>
            <?php endif; ?>
        </h2>
        <p>
            <?php if ($show_past): ?>
                <?php _e('All your events and RSVPs', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('Your upcoming events and RSVPs', 'partyminder'); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Login/Email Prompt for non-logged-in users -->
    <?php if (!is_user_logged_in() && !$user_email): ?>
    <div class="login-prompt">
        <h3><?php _e('🔐 Login to See Your Events', 'partyminder'); ?></h3>
        <p><?php _e('Log in to see events you\'ve created and your RSVPs.', 'partyminder'); ?></p>
        <a href="<?php echo esc_url(add_query_arg('redirect_to', get_permalink(get_the_ID()), PartyMinder::get_login_url())); ?>" class="pm-button">
            <?php _e('Login', 'partyminder'); ?>
        </a>
    </div>

    <div class="email-lookup">
        <h3><?php _e('📧 Or Find Your RSVPs by Email', 'partyminder'); ?></h3>
        <p><?php _e('Enter your email to see events you\'ve RSVP\'d to.', 'partyminder'); ?></p>
        <form method="get" class="email-form">
            <input type="email" name="email" placeholder="<?php esc_attr_e('Enter your email address', 'partyminder'); ?>" required />
            <button type="submit" class="pm-button"><?php _e('Find My RSVPs', 'partyminder'); ?></button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Created Events Section -->
    <?php if (is_user_logged_in() && !empty($created_events)): ?>
    <div class="events-section">
        <div class="section-header">
            <h3 class="section-title"><?php _e('🎨 Events You Created', 'partyminder'); ?></h3>
            <span class="event-count"><?php echo count($created_events); ?></span>
        </div>
        
        <div class="events-grid">
            <?php foreach ($created_events as $event): ?>
                <?php
                $event_date = new DateTime($event->event_date);
                $is_past = $event_date < new DateTime();
                ?>
                <article class="my-event-card">
                    <div class="event-badge badge-created">
                        <?php _e('Host', 'partyminder'); ?>
                    </div>
                    
                    <div class="event-content">
                        <h4 class="event-title">
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>"><?php echo esc_html($event->title); ?></a>
                        </h4>
                        
                        <div class="event-meta">
                            <div class="meta-item">
                                <span class="meta-icon">📅</span>
                                <span><?php echo $event_date->format('M j, Y'); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">🕐</span>
                                <span><?php echo $event_date->format('g:i A'); ?></span>
                            </div>
                            <?php if ($event->venue_info): ?>
                            <div class="meta-item">
                                <span class="meta-icon">📍</span>
                                <span><?php echo esc_html($event->venue_info); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="event-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $event->guest_stats->confirmed; ?></div>
                                <div class="stat-label"><?php _e('Confirmed', 'partyminder'); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $event->guest_stats->maybe; ?></div>
                                <div class="stat-label"><?php _e('Maybe', 'partyminder'); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $event->guest_stats->pending; ?></div>
                                <div class="stat-label"><?php _e('Pending', 'partyminder'); ?></div>
                            </div>
                        </div>

                        <div class="event-actions">
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-button pm-button-small">
                                <?php _e('View Event', 'partyminder'); ?>
                            </a>
                            <button class="pm-button pm-button-primary pm-button-small manage-event-btn" 
                                    data-event-id="<?php echo esc_attr($event->id); ?>"
                                    data-event-title="<?php echo esc_attr($event->title); ?>"
                                    data-event-slug="<?php echo esc_attr($event->slug); ?>">
                                <span>⚙️</span>
                                <?php _e('Manage', 'partyminder'); ?>
                            </button>
                            <a href="<?php echo PartyMinder::get_edit_event_url($event->id); ?>" class="pm-button pm-button-secondary pm-button-small">
                                <span>✏️</span>
                                <?php _e('Edit', 'partyminder'); ?>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- RSVP'd Events Section -->
    <?php if ($user_email && !empty($rsvp_events)): ?>
    <div class="events-section">
        <div class="section-header">
            <h3 class="section-title"><?php _e('💌 Events You\'ve RSVP\'d To', 'partyminder'); ?></h3>
            <span class="event-count"><?php echo count($rsvp_events); ?></span>
        </div>
        
        <div class="events-grid">
            <?php foreach ($rsvp_events as $event): ?>
                <?php
                $event_date = new DateTime($event->event_date);
                $is_past = $event_date < new DateTime();
                $badge_class = 'badge-' . $event->rsvp_status;
                $badge_text = array(
                    'confirmed' => __('Going', 'partyminder'),
                    'maybe' => __('Maybe', 'partyminder'),
                    'declined' => __('Can\'t Go', 'partyminder'),
                    'pending' => __('Pending', 'partyminder')
                );
                ?>
                <article class="my-event-card">
                    <div class="event-badge <?php echo esc_attr($badge_class); ?>">
                        <?php echo esc_html($badge_text[$event->rsvp_status] ?? __('RSVP\'d', 'partyminder')); ?>
                    </div>
                    
                    <div class="event-content">
                        <h4 class="event-title">
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>"><?php echo esc_html($event->title); ?></a>
                        </h4>
                        
                        <div class="event-meta">
                            <div class="meta-item">
                                <span class="meta-icon">📅</span>
                                <span><?php echo $event_date->format('M j, Y'); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">🕐</span>
                                <span><?php echo $event_date->format('g:i A'); ?></span>
                            </div>
                            <?php if ($event->venue_info): ?>
                            <div class="meta-item">
                                <span class="meta-icon">📍</span>
                                <span><?php echo esc_html($event->venue_info); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <span class="meta-icon">✉️</span>
                                <span><?php echo esc_html($event->host_email); ?></span>
                            </div>
                        </div>

                        <div class="event-actions">
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-button pm-button-small">
                                <?php _e('View Event', 'partyminder'); ?>
                            </a>
                            <?php if (!$is_past): ?>
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>#rsvp" class="pm-button pm-button-secondary pm-button-small">
                                <?php _e('Update RSVP', 'partyminder'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- No Events Message -->
    <?php if ((is_user_logged_in() && empty($created_events) && empty($rsvp_events)) || (!is_user_logged_in() && $user_email && empty($rsvp_events))): ?>
    <div class="no-events">
        <div class="no-events-icon">🎭</div>
        <h3><?php _e('No Events Found', 'partyminder'); ?></h3>
        <?php if (is_user_logged_in()): ?>
            <p><?php _e('You haven\'t created any events yet, and no RSVPs found.', 'partyminder'); ?></p>
            <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-button">
                <span>✨</span>
                <?php _e('Create Your First Event', 'partyminder'); ?>
            </a>
        <?php else: ?>
            <p><?php _e('No RSVPs found for this email address.', 'partyminder'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <?php if (is_user_logged_in()): ?>
    <div class="events-section">
        <div class="section-header">
            <h3 class="section-title"><?php _e('⚡ Quick Actions', 'partyminder'); ?></h3>
        </div>
        
        <div class="quick-actions-section">
            <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-button">
                <span class="action-icon">✨</span>
                <?php _e('Create New Event', 'partyminder'); ?>
            </a>
            <a href="<?php echo PartyMinder::get_profile_url(); ?>" class="pm-button pm-button-secondary">
                <span class="action-icon">👤</span>
                <?php _e('My Profile', 'partyminder'); ?>
            </a>
            <a href="<?php echo get_permalink(get_the_ID()) . '?show_past=1'; ?>" class="pm-button pm-button-secondary">
                <span class="action-icon">📅</span>
                <?php echo $show_past ? __('Hide Past Events', 'partyminder') : __('Show Past Events', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
// Include event management modal
include PARTYMINDER_PLUGIN_DIR . 'templates/event-management-modal.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle manage event button clicks
    document.querySelectorAll('.manage-event-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get event data from button attributes
            const eventData = {
                id: this.getAttribute('data-event-id'),
                title: this.getAttribute('data-event-title'),
                slug: this.getAttribute('data-event-slug')
            };
            
            // Show the management modal
            if (typeof window.showEventManagementModal === 'function') {
                window.showEventManagementModal(eventData);
            } else {
                console.error('Event management modal not loaded');
            }
        });
    });
});
</script>