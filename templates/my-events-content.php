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
    $posts_table = $wpdb->posts;
    
    $query = "SELECT p.ID FROM $posts_table p 
              INNER JOIN $events_table e ON p.ID = e.post_id 
              WHERE p.post_type = 'party_event' 
              AND p.post_status = 'publish' 
              AND p.post_author = %d 
              AND e.event_status = 'active'";
    
    if (!$show_past) {
        $query .= " AND e.event_date >= CURDATE()";
    }
    
    $query .= " ORDER BY e.event_date " . ($show_past ? "DESC" : "ASC");
    
    $results = $wpdb->get_results($wpdb->prepare($query, $current_user->ID));
    
    foreach ($results as $result) {
        $event = $event_manager->get_event($result->ID);
        if ($event) {
            $created_events[] = $event;
        }
    }
}

// Get user's RSVP'd events (by email)
$rsvp_events = array();
if ($user_email) {
    global $wpdb;
    $guests_table = $wpdb->prefix . 'partyminder_guests';
    $events_table = $wpdb->prefix . 'partyminder_events';
    $posts_table = $wpdb->posts;
    
    $query = "SELECT DISTINCT p.ID, g.status as rsvp_status FROM $posts_table p 
              INNER JOIN $events_table e ON p.ID = e.post_id 
              INNER JOIN $guests_table g ON e.id = g.event_id 
              WHERE p.post_type = 'party_event' 
              AND p.post_status = 'publish' 
              AND g.email = %s 
              AND e.event_status = 'active'";
    
    if (!$show_past) {
        $query .= " AND e.event_date >= CURDATE()";
    }
    
    $query .= " ORDER BY e.event_date " . ($show_past ? "DESC" : "ASC");
    
    $results = $wpdb->get_results($wpdb->prepare($query, $user_email));
    
    foreach ($results as $result) {
        $event = $event_manager->get_event($result->ID);
        if ($event) {
            $event->user_rsvp_status = $result->rsvp_status;
            $rsvp_events[] = $event;
        }
    }
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
$button_style = get_option('partyminder_button_style', 'rounded');
?>

<style>
.partyminder-my-events-content {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 20px;
}

.partyminder-my-events-content .my-events-header {
    text-align: center;
    margin-bottom: 30px;
}

.partyminder-my-events-content .my-events-header h2 {
    font-size: 2.2em;
    margin-bottom: 10px;
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-my-events-content .events-section {
    margin: 30px 0;
}

.partyminder-my-events-content .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.partyminder-my-events-content .section-title {
    font-size: 1.4em;
    color: <?php echo esc_attr($primary_color); ?>;
    margin: 0;
}

.partyminder-my-events-content .event-count {
    background: <?php echo esc_attr($primary_color); ?>;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9em;
}

.partyminder-my-events-content .events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.partyminder-my-events-content .my-event-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.partyminder-my-events-content .my-event-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.partyminder-my-events-content .event-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    z-index: 1;
}

.partyminder-my-events-content .badge-created {
    background: #28a745;
    color: white;
}

.partyminder-my-events-content .badge-confirmed {
    background: #17a2b8;
    color: white;
}

.partyminder-my-events-content .badge-maybe {
    background: #ffc107;
    color: #212529;
}

.partyminder-my-events-content .badge-declined {
    background: #dc3545;
    color: white;
}

.partyminder-my-events-content .event-content {
    padding: 20px;
}

.partyminder-my-events-content .event-title {
    font-size: 1.2em;
    font-weight: bold;
    margin: 0 0 10px 0;
    color: #333;
}

.partyminder-my-events-content .event-title a {
    color: inherit;
    text-decoration: none;
}

.partyminder-my-events-content .event-title a:hover {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-my-events-content .event-meta {
    margin: 15px 0;
    font-size: 0.9em;
}

.partyminder-my-events-content .meta-item {
    display: inline-flex;
    align-items: center;
    margin: 5px 15px 5px 0;
    color: #666;
}

.partyminder-my-events-content .meta-icon {
    margin-right: 6px;
}

.partyminder-my-events-content .event-stats {
    display: flex;
    gap: 15px;
    margin: 15px 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.partyminder-my-events-content .stat-item {
    text-align: center;
    flex: 1;
}

.partyminder-my-events-content .stat-number {
    font-size: 1.1em;
    font-weight: bold;
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-my-events-content .stat-label {
    font-size: 0.8em;
    color: #666;
}

.partyminder-my-events-content .event-actions {
    margin-top: 15px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.partyminder-my-events-content .pm-button {
    background: <?php echo esc_attr($primary_color); ?>;
    color: white !important;
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9em;
    transition: opacity 0.3s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.partyminder-my-events-content .pm-button:hover {
    opacity: 0.9;
    color: white !important;
}

.partyminder-my-events-content .pm-button-secondary {
    background: #6c757d;
}

.partyminder-my-events-content .pm-button-small {
    padding: 4px 10px;
    font-size: 0.8em;
}

.partyminder-my-events-content .no-events {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.partyminder-my-events-content .no-events-icon {
    font-size: 3em;
    margin-bottom: 15px;
}

.partyminder-my-events-content .login-prompt {
    background: #e7f3ff;
    border: 1px solid #b8daff;
    color: #004085;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    margin: 20px 0;
}

.partyminder-my-events-content .email-lookup {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.partyminder-my-events-content .email-form {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 15px;
    flex-wrap: wrap;
}

.partyminder-my-events-content .email-form input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 250px;
    max-width: 100%;
}

@media (max-width: 768px) {
    .partyminder-my-events-content .events-grid {
        grid-template-columns: 1fr;
    }
    
    .partyminder-my-events-content .section-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .partyminder-my-events-content .event-actions {
        flex-direction: column;
    }
    
    .partyminder-my-events-content .email-form {
        flex-direction: column;
        align-items: center;
    }
    
    .partyminder-my-events-content .email-form input {
        width: 100%;
    }
}
</style>

<div class="partyminder-my-events-content">
    
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
        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-button">
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
                            <a href="<?php echo get_permalink($event->ID); ?>"><?php echo esc_html($event->title); ?></a>
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
                            <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button pm-button-small">
                                <?php _e('View Event', 'partyminder'); ?>
                            </a>
                            <a href="<?php echo PartyMinder::get_edit_event_url($event->ID); ?>" class="pm-button pm-button-secondary pm-button-small">
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
                $badge_class = 'badge-' . $event->user_rsvp_status;
                $badge_text = array(
                    'confirmed' => __('Going', 'partyminder'),
                    'maybe' => __('Maybe', 'partyminder'),
                    'declined' => __('Can\'t Go', 'partyminder'),
                    'pending' => __('Pending', 'partyminder')
                );
                ?>
                <article class="my-event-card">
                    <div class="event-badge <?php echo esc_attr($badge_class); ?>">
                        <?php echo esc_html($badge_text[$event->user_rsvp_status] ?? __('RSVP\'d', 'partyminder')); ?>
                    </div>
                    
                    <div class="event-content">
                        <h4 class="event-title">
                            <a href="<?php echo get_permalink($event->ID); ?>"><?php echo esc_html($event->title); ?></a>
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
                            <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button pm-button-small">
                                <?php _e('View Event', 'partyminder'); ?>
                            </a>
                            <?php if (!$is_past): ?>
                            <a href="<?php echo get_permalink($event->ID); ?>#rsvp" class="pm-button pm-button-secondary pm-button-small">
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
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
            <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-button">
                <span style="margin-right: 8px;">✨</span>
                <?php _e('Create New Event', 'partyminder'); ?>
            </a>
            <a href="<?php echo get_permalink() . '?show_past=1'; ?>" class="pm-button pm-button-secondary">
                <span style="margin-right: 8px;">📅</span>
                <?php echo $show_past ? __('Hide Past Events', 'partyminder') : __('Show Past Events', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>