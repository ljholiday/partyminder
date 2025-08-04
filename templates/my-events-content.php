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

?>


<div class="page">
    
    <!-- Breadcrumb Navigation -->
    <div class="">
        <a href="<?php echo esc_url(PartyMinder::get_dashboard_url()); ?>" class="-link">
            🏠 <?php _e('Dashboard', 'partyminder'); ?>
        </a>
        <span class="-separator">→</span>
        <span class="-current"><?php _e('My Events', 'partyminder'); ?></span>
    </div>
    
    <!-- Header -->
    <div class="mb-4">
        <h2 class="heading heading-lg text-primary">
            <?php if (is_user_logged_in()): ?>
                <?php printf(__('👋 Hi %s, here are your events', 'partyminder'), esc_html($current_user->display_name)); ?>
            <?php else: ?>
                <?php _e('🎉 My Events', 'partyminder'); ?>
            <?php endif; ?>
        </h2>
        <p class="text-muted">
            <?php if ($show_past): ?>
                <?php _e('All your events and RSVPs', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('Your upcoming events and RSVPs', 'partyminder'); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Login/Email Prompt for non-logged-in users -->
    <?php if (!is_user_logged_in() && !$user_email): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="heading heading-md">🔐 <?php _e('Login to See Your Events', 'partyminder'); ?></h3>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4"><?php _e('Log in to see events you\'ve created and your RSVPs.', 'partyminder'); ?></p>
            <a href="<?php echo esc_url(add_query_arg('redirect_to', get_permalink(get_the_ID()), PartyMinder::get_login_url())); ?>" class="btn">
                <?php _e('Login', 'partyminder'); ?>
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="heading heading-md">📧 <?php _e('Or Find Your RSVPs by Email', 'partyminder'); ?></h3>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4"><?php _e('Enter your email to see events you\'ve RSVP\'d to.', 'partyminder'); ?></p>
            <form method="get" class="flex gap-4">
                <input type="email" name="email" class="form-input" style="flex: 1;" placeholder="<?php esc_attr_e('Enter your email address', 'partyminder'); ?>" required />
                <button type="submit" class="btn"><?php _e('Find My RSVPs', 'partyminder'); ?></button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Created Events Section -->
    <?php if (is_user_logged_in() && !empty($created_events)): ?>
    <div class="card mb-4">
        <div class="card-header flex flex-between">
            <h3 class="heading heading-md text-primary "><?php _e('🎨 Events You Created', 'partyminder'); ?></h3>
            <span class="badge"><?php echo count($created_events); ?></span>
        </div>
        <div class="card-body">
        <div class="grid grid-auto">
            <?php foreach ($created_events as $event): ?>
                <?php
                $event_date = new DateTime($event->event_date);
                $is_past = $event_date < new DateTime();
                ?>
                <article class="card">
                    <div class="card-header flex flex-between">
                        <h4 class="heading heading-sm ">
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="text-primary" ><?php echo esc_html($event->title); ?></a>
                        </h4>
                        <div class="badge badge-success">
                            <?php _e('Host', 'partyminder'); ?>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="flex">
                                <span>📅</span>
                                <span class="text-muted"><?php echo $event_date->format('M j, Y'); ?></span>
                            </div>
                            <div class="flex">
                                <span>🕐</span>
                                <span class="text-muted"><?php echo $event_date->format('g:i A'); ?></span>
                            </div>
                            <?php if ($event->venue_info): ?>
                            <div class="flex">
                                <span>📍</span>
                                <span class="text-muted"><?php echo esc_html($event->venue_info); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-between mb-4">
                            <div class="stat">
                                <div class="stat-number text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
                                <div class="stat-label"><?php _e('Confirmed', 'partyminder'); ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-number text-primary"><?php echo $event->guest_stats->maybe; ?></div>
                                <div class="stat-label"><?php _e('Maybe', 'partyminder'); ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-number text-primary"><?php echo $event->guest_stats->pending; ?></div>
                                <div class="stat-label"><?php _e('Pending', 'partyminder'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer flex gap-4 flex-wrap">
                        <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn btn-small btn-secondary">
                            <?php _e('View Event', 'partyminder'); ?>
                        </a>
                        <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn btn-small">
                            <span>⚙️</span>
                            <?php _e('Manage', 'partyminder'); ?>
                        </a>
                        <a href="<?php echo PartyMinder::get_edit_event_url($event->id); ?>" class="btn btn-secondary btn-small">
                            <span>✏️</span>
                            <?php _e('Edit', 'partyminder'); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- RSVP'd Events Section -->
    <?php if ($user_email && !empty($rsvp_events)): ?>
    <div class="card mb-4">
        <div class="card-header flex flex-between">
            <h3 class="heading heading-md text-primary "><?php _e('💌 Events You\'ve RSVP\'d To', 'partyminder'); ?></h3>
            <span class="badge"><?php echo count($rsvp_events); ?></span>
        </div>
        
        <div class="card-body">
            <div class="grid grid-auto">
                <?php foreach ($rsvp_events as $event): ?>
                    <?php
                    $event_date = new DateTime($event->event_date);
                    $is_past = $event_date < new DateTime();
                    $badge_text = array(
                        'confirmed' => __('Going', 'partyminder'),
                        'maybe' => __('Maybe', 'partyminder'),
                        'declined' => __('Can\'t Go', 'partyminder'),
                        'pending' => __('Pending', 'partyminder')
                    );
                    $badge_class = 'badge-' . ($event->rsvp_status === 'confirmed' ? 'success' : ($event->rsvp_status === 'declined' ? 'danger' : 'warning'));
                    ?>
                    <article class="card">
                        <div class="card-header flex flex-between">
                            <h4 class="heading heading-sm ">
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="text-primary" ><?php echo esc_html($event->title); ?></a>
                            </h4>
                            <div class="badge <?php echo esc_attr($badge_class); ?>">
                                <?php echo esc_html($badge_text[$event->rsvp_status] ?? __('RSVP\'d', 'partyminder')); ?>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="flex">
                                    <span>📅</span>
                                    <span class="text-muted"><?php echo $event_date->format('M j, Y'); ?></span>
                                </div>
                                <div class="flex">
                                    <span>🕐</span>
                                    <span class="text-muted"><?php echo $event_date->format('g:i A'); ?></span>
                                </div>
                                <?php if ($event->venue_info): ?>
                                <div class="flex">
                                    <span>📍</span>
                                    <span class="text-muted"><?php echo esc_html($event->venue_info); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="flex">
                                    <span>✉️</span>
                                    <span class="text-muted"><?php echo esc_html($event->host_email); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer flex gap-4">
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn btn-small btn-secondary">
                                <?php _e('View Event', 'partyminder'); ?>
                            </a>
                            <?php if (!$is_past): ?>
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>#rsvp" class="btn btn-small">
                                <?php _e('Update RSVP', 'partyminder'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- No Events Message -->
    <?php if ((is_user_logged_in() && empty($created_events) && empty($rsvp_events)) || (!is_user_logged_in() && $user_email && empty($rsvp_events))): ?>
    <div class="card mb-4">
        <div class="card-body text-center pm-placeholder">
            <div class="pm-icon-lg pm-placeholder-icon">🎭</div>
            <h3 class="heading heading-md mb-4"><?php _e('No Events Found', 'partyminder'); ?></h3>
            <?php if (is_user_logged_in()): ?>
                <p class="text-muted mb-4"><?php _e('You haven\'t created any events yet, and no RSVPs found.', 'partyminder'); ?></p>
                <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="btn">
                    <span>✨</span>
                    <?php _e('Create Your First Event', 'partyminder'); ?>
                </a>
            <?php else: ?>
                <p class="text-muted"><?php _e('No RSVPs found for this email address.', 'partyminder'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <?php if (is_user_logged_in()): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="heading heading-md text-primary "><?php _e('⚡ Quick Actions', 'partyminder'); ?></h3>
        </div>
        
        <div class="card-body">
            <div class="flex gap-4 flex-column">
                <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="btn">
                    <span>🎉</span>
                    <?php _e('Create Event', 'partyminder'); ?>
                </a>
                <a href="<?php echo PartyMinder::get_profile_url(); ?>" class="btn btn-secondary">
                    <span>👤</span>
                    <?php _e('My Profile', 'partyminder'); ?>
                </a>
                <a href="<?php echo get_permalink(get_the_ID()) . ($show_past ? '' : '?show_past=1'); ?>" class="btn btn-secondary">
                    <span>📅</span>
                    <?php echo $show_past ? __('Hide Past Events', 'partyminder') : __('Show Past Events', 'partyminder'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
// Event management functionality is now inline in single event pages
?>

<script>
// Event management is now handled inline on individual event pages
// No modal JavaScript needed
</script>