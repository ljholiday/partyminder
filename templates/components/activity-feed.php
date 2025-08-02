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
    <div class="pm-activity-feed">
        <div class="pm-flex pm-flex-column pm-gap-sm">
            <?php foreach ($activities as $activity): ?>
                <div class="pm-activity-item pm-flex pm-flex-center-gap pm-p-3 pm-border pm-border-radius">
                    <!-- Activity Icon -->
                    <div class="pm-activity-icon pm-flex-shrink-0 pm-text-lg">
                        <?php echo $activity_manager->get_activity_icon($activity); ?>
                    </div>
                    
                    <!-- Activity Content -->
                    <div class="pm-activity-content pm-flex-1 pm-min-w-0">
                        <div class="pm-activity-description pm-heading pm-heading-xs pm-mb-1">
                            <?php echo $activity_manager->get_activity_description($activity); ?>
                            
                            <?php if ($show_user_names && isset($activity->author_name)): ?>
                                <span class="text-muted"><?php printf(__('by %s', 'partyminder'), esc_html($activity->author_name)); ?></span>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url($activity_manager->get_activity_link($activity)); ?>" 
                               class="pm-text-primary pm-no-underline pm-activity-link">
                                <?php echo esc_html($activity_manager->get_activity_title($activity)); ?>
                            </a>
                        </div>
                        
                        <?php $metadata = $activity_manager->get_activity_metadata($activity); ?>
                        <?php if ($metadata): ?>
                            <div class="pm-activity-metadata text-muted pm-text-xs pm-mb-1">
                                <?php echo $metadata; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="pm-activity-time text-muted pm-text-xs">
                            <?php echo human_time_diff(strtotime($activity->activity_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif ($show_empty_state): ?>
    <div class="pm-activity-feed-empty pm-text-center pm-p-6">
        <div class="pm-text-4xl pm-mb-3">📝</div>
        <h4 class="pm-heading pm-heading-sm pm-text-primary pm-mb-3">
            <?php if ($user_id): ?>
                <?php _e('No Activity Yet', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('No Recent Activity', 'partyminder'); ?>
            <?php endif; ?>
        </h4>
        <p class="text-muted pm-text-sm pm-mb-4">
            <?php if ($user_id): ?>
                <?php _e('Start by creating an event or joining a conversation!', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('Be the first to create an event or start a conversation!', 'partyminder'); ?>
            <?php endif; ?>
        </p>
        
        <?php if ($empty_state_actions): ?>
            <div class="pm-flex pm-flex-center-gap pm-flex-wrap">
                <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" 
                   class="btn btn-primary btn-small">
                    ✨ <?php _e('Create Event', 'partyminder'); ?>
                </a>
                <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" 
                   class="btn btn-secondary btn-small">
                    💬 <?php _e('Join Conversations', 'partyminder'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>