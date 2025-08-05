<?php
/**
 * Activity Feed Component
 * Reusable activity feed that can be used in profiles, dashboard, etc.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Default parameters
$user_id = $user_id ?? null;
$limit = $limit ?? 8;
$show_user_names = $show_user_names ?? false; // For community feeds
$activity_types = $activity_types ?? array(); // Empty = all types
$show_empty_state = $show_empty_state ?? true;
$empty_state_actions = $empty_state_actions ?? true;

// Load activity manager
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-activity-manager.php';
$activity_manager = new PartyMinder_Activity_Manager();

// Get activities
if ($user_id) {
    $activities = $activity_manager->get_user_activity($user_id, $limit, $activity_types);
} else {
    $activities = $activity_manager->get_community_activity($limit, $activity_types);
}
?>

<?php if (!empty($activities)): ?>
    <div class="content-activity-feed">
        <?php foreach ($activities as $activity): ?>
            <div class="card content-activity-item">
                <div class="content-activity-icon">
                    <?php echo $activity_manager->get_activity_icon($activity); ?>
                </div>
                <div class="content-activity-content">
                    <div class="content-activity-description">
                        <?php echo $activity_manager->get_activity_description($activity); ?>
                        <?php if ($show_user_names && isset($activity->author_name)): ?>
                            <span class="text-muted"><?php printf(__('by %s', 'partyminder'), esc_html($activity->author_name)); ?></span>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($activity_manager->get_activity_link($activity)); ?>">
                            <?php echo esc_html($activity_manager->get_activity_title($activity)); ?>
                        </a>
                    </div>
                    <?php $metadata = $activity_manager->get_activity_metadata($activity); ?>
                    <?php if ($metadata): ?>
                        <div class="text-muted"><?php echo $metadata; ?></div>
                    <?php endif; ?>
                    <div class="content-activity-time text-muted">
                        <?php echo human_time_diff(strtotime($activity->activity_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($show_empty_state): ?>
    <div class="content-activity-empty">
        <h4><?php echo $user_id ? __('No Activity Yet', 'partyminder') : __('No Recent Activity', 'partyminder'); ?></h4>
        <p class="text-muted">
            <?php echo $user_id ? __('Start by creating an event or joining a conversation!', 'partyminder') : __('Be the first to create an event or start a conversation!', 'partyminder'); ?>
        </p>
        <?php if ($empty_state_actions): ?>
            <div>
                <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn">✨ <?php _e('Create Event', 'partyminder'); ?></a>
                <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="btn btn-secondary">💬 <?php _e('Join Conversations', 'partyminder'); ?></a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
