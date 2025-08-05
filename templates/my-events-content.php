<?php
/**
 * My Events Content Template
 * Shows events the user is hosting or attending
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

$show_past = filter_var($atts['show_past'] ?? false, FILTER_VALIDATE_BOOLEAN);

$current_user = wp_get_current_user();
$user_email = '';

if (isset($_GET['email']) && is_email($_GET['email'])) {
    $user_email = sanitize_email($_GET['email']);
} elseif (is_user_logged_in()) {
    $user_email = $current_user->user_email;
}

$created_events = array();
if (is_user_logged_in()) {
    global $wpdb;
    $events_table = $wpdb->prefix . 'partyminder_events';
    $query = "SELECT * FROM $events_table WHERE author_id = %d AND event_status = 'active'";
    if (!$show_past) {
        $query .= " AND event_date >= CURDATE()";
    }
    $query .= " ORDER BY event_date " . ($show_past ? "DESC" : "ASC");
    $created_events = $wpdb->get_results($wpdb->prepare($query, $current_user->ID));
}

$rsvp_events = array();
if ($user_email) {
    global $wpdb;
    $guests_table = $wpdb->prefix . 'partyminder_guests';
    $events_table = $wpdb->prefix . 'partyminder_events';
    $query = "SELECT DISTINCT e.*, g.status as rsvp_status FROM $events_table e INNER JOIN $guests_table g ON e.id = g.event_id WHERE g.email = %s AND e.event_status = 'active'";
    if (!$show_past) {
        $query .= " AND e.event_date >= CURDATE()";
    }
    $query .= " ORDER BY e.event_date " . ($show_past ? "DESC" : "ASC");
    $rsvp_events = $wpdb->get_results($wpdb->prepare($query, $user_email));
}
?>

<div class="pm-section">
    <div class="pm-header">
        <h2><?php _e('My Events', 'partyminder'); ?></h2>
    </div>

    <?php if (!empty($created_events)): ?>
        <h3><?php _e("Events I'm Hosting", 'partyminder'); ?></h3>
        <?php foreach ($created_events as $event): ?>
            <div class="card">
                <h4><a href="<?php echo esc_url(home_url('/events/' . $event->slug)); ?>"><?php echo esc_html($event->title); ?></a></h4>
                <p class="text-muted"><?php echo date_i18n(get_option('date_format'), strtotime($event->event_date)); ?></p>
                <div>
                    <a href="<?php echo esc_url(home_url('/events/' . $event->slug)); ?>" class="btn"><?php _e('View', 'partyminder'); ?></a>
                    <?php if (strtotime($event->event_date) >= current_time('timestamp')): ?>
                        <a href="<?php echo esc_url(PartyMinder::get_edit_event_url($event->id)); ?>" class="btn btn-secondary"><?php _e('Edit', 'partyminder'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($rsvp_events)): ?>
        <h3><?php _e("Events I'm Attending", 'partyminder'); ?></h3>
        <?php foreach ($rsvp_events as $event): ?>
            <div class="card">
                <h4><a href="<?php echo esc_url(home_url('/events/' . $event->slug)); ?>"><?php echo esc_html($event->title); ?></a></h4>
                <p class="text-muted"><?php echo date_i18n(get_option('date_format'), strtotime($event->event_date)); ?></p>
                <div>
                    <a href="<?php echo esc_url(home_url('/events/' . $event->slug)); ?>#rsvp" class="btn btn-secondary"><?php _e('Update RSVP', 'partyminder'); ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($created_events) && empty($rsvp_events)): ?>
        <p class="text-muted"><?php _e('No events found.', 'partyminder'); ?></p>
    <?php endif; ?>

    <?php if (is_user_logged_in()): ?>
        <div class="pm-section">
            <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn"><?php _e('Create Event', 'partyminder'); ?></a>
            <a href="<?php echo esc_url(add_query_arg('show_past', $show_past ? '0' : '1')); ?>" class="btn btn-secondary">
                <?php echo $show_past ? __('Hide Past Events', 'partyminder') : __('Show Past Events', 'partyminder'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
