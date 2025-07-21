<?php
/**
 * Template for My Events Page
 * Displays user's created events and RSVPs
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

// Get page attributes
$show_past = filter_var($_GET['show_past'] ?? false, FILTER_VALIDATE_BOOLEAN);

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
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-my-events {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-breadcrumb {
    margin-bottom: 20px;
}

.page-breadcrumb a {
    color: var(--pm-primary);
    text-decoration: none;
}

.page-breadcrumb a:hover {
    text-decoration: underline;
}

.my-events-header {
    text-align: center;
    margin-bottom: 40px;
}

.my-events-header h1 {
    font-size: 2.5em;
    margin-bottom: 10px;
    color: var(--pm-primary);
}

.events-section {
    margin: 40px 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.section-title {
    font-size: 1.5em;
    color: var(--pm-primary);
    margin: 0;
}

.event-count {
    background: var(--pm-primary);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9em;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.my-event-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.my-event-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.event-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    z-index: 1;
}

.badge-created {
    background: #28a745;
    color: white;
}

.badge-confirmed {
    background: #17a2b8;
    color: white;
}

.badge-maybe {
    background: #ffc107;
    color: #212529;
}

.badge-declined {
    background: #dc3545;
    color: white;
}

.event-content {
    padding: 20px;
}

.event-title {
    font-size: 1.3em;
    font-weight: bold;
    margin: 0 0 10px 0;
    color: #333;
}

.event-title a {
    color: inherit;
    text-decoration: none;
}

.event-title a:hover {
    color: var(--pm-primary);
}

.event-meta {
    margin: 15px 0;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    margin: 5px 15px 5px 0;
    font-size: 0.9em;
    color: #666;
}

.meta-icon {
    margin-right: 8px;
}

.event-stats {
    display: flex;
    gap: 15px;
    margin: 15px 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-number {
    font-size: 1.2em;
    font-weight: bold;
    color: var(--pm-primary);
}

.stat-label {
    font-size: 0.8em;
    color: #666;
}

.event-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.pm-button {
    background: var(--pm-primary);
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
    transition: background 0.3s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.pm-button:hover {
    opacity: 0.9;
    color: white;
}

.pm-button-secondary {
    background: #6c757d;
}

.pm-button-small {
    padding: 6px 12px;
    font-size: 0.8em;
}

.pm-button.style-rounded {
    border-radius: 6px;
}

.pm-button.style-pill {
    border-radius: 25px;
}

.pm-button.style-square {
    border-radius: 0;
}

.no-events {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-events-icon {
    font-size: 4em;
    margin-bottom: 20px;
}

.login-prompt {
    background: #e7f3ff;
    border: 1px solid #b8daff;
    color: #004085;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    margin: 20px 0;
}

.email-lookup {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.email-form {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 15px;
}

.email-form input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 250px;
}

.quick-actions {
    margin: 40px 0;
}

.quick-actions-grid {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: center;
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .email-form {
        flex-direction: column;
        align-items: center;
    }
    
    .email-form input {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<div class="partyminder-my-events">
    
    <!-- Breadcrumb -->
    <div class="page-breadcrumb">
        <a href="<?php echo home_url(); ?>"><?php _e('Home', 'partyminder'); ?></a> 
        &raquo; <a href="<?php echo PartyMinder::get_events_page_url(); ?>"><?php _e('Events', 'partyminder'); ?></a>
        &raquo; <?php _e('My Events', 'partyminder'); ?>
    </div>
    
    <!-- Header -->
    <div class="my-events-header">
        <h1>
            <?php if (is_user_logged_in()): ?>
                <?php printf(__('👋 Hi %s, here are your events', 'partyminder'), esc_html($current_user->display_name)); ?>
            <?php else: ?>
                <?php _e('🎉 My Events', 'partyminder'); ?>
            <?php endif; ?>
        </h1>
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
            <span>🔐</span>
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
                <div class="my-event-card">
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
                                <span>👀</span>
                                <?php _e('View Event', 'partyminder'); ?>
                            </a>
                            <a href="<?php echo PartyMinder::get_edit_event_url($event->ID); ?>" class="pm-button pm-button-secondary pm-button-small">
                                <span>✏️</span>
                                <?php _e('Edit', 'partyminder'); ?>
                            </a>
                        </div>
                    </div>
                </div>
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
                <div class="my-event-card">
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
                                <span>👀</span>
                                <?php _e('View Event', 'partyminder'); ?>
                            </a>
                            <?php if (!$is_past): ?>
                            <a href="<?php echo get_permalink($event->ID); ?>#rsvp" class="pm-button pm-button-secondary pm-button-small">
                                <span>📝</span>
                                <?php _e('Update RSVP', 'partyminder'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
        
        <div class="quick-actions-grid">
            <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-button">
                <span>✨</span>
                <?php _e('Create New Event', 'partyminder'); ?>
            </a>
            <a href="<?php echo add_query_arg('show_past', $show_past ? '' : '1'); ?>" class="pm-button pm-button-secondary">
                <span>📅</span>
                <?php echo $show_past ? __('Hide Past Events', 'partyminder') : __('Show Past Events', 'partyminder'); ?>
            </a>
            <a href="<?php echo PartyMinder::get_events_page_url(); ?>" class="pm-button pm-button-secondary">
                <span>🎉</span>
                <?php _e('Browse All Events', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php get_footer(); ?>