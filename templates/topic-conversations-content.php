<?php
/**
 * Topic Conversations Content Template
 * Shows all conversations in a specific topic
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';

$conversation_manager = new PartyMinder_Conversation_Manager();

// Get topic slug from URL
$topic_slug = get_query_var('conversation_topic');
if (!$topic_slug) {
    wp_redirect(PartyMinder::get_conversations_url());
    exit;
}

// Get topic
$topic = $conversation_manager->get_topic_by_slug($topic_slug);
if (!$topic) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
}

// Get conversations for this topic
$conversations = $conversation_manager->get_conversations_by_topic($topic->id, 20);

// Get current user info
$current_user = wp_get_current_user();
$user_email = is_user_logged_in() ? $current_user->user_email : '';

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>


<div class="pm-container-wide">
    <!-- Breadcrumbs -->
    <nav class="pm-breadcrumbs pm-mb-6">
        <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-breadcrumb-link">
            🏠 <?php _e('Community Conversations', 'partyminder'); ?>
        </a>
        <span class="pm-breadcrumb-separator">›</span>
        <span class="pm-breadcrumb-current"><?php echo esc_html($topic->icon . ' ' . $topic->name); ?></span>
    </nav>

    <!-- Topic Header -->
    <div class="pm-card pm-mb-6">
        <div class="pm-card-header">
            <div class="pm-flex pm-flex-between pm-flex-center-gap pm-mb-4">
                <div>
                    <h1 class="pm-heading pm-heading-lg pm-text-primary pm-mb-2">
                        <span class="pm-mr-2"><?php echo esc_html($topic->icon); ?></span>
                        <?php echo esc_html($topic->name); ?>
                    </h1>
                    <p class="pm-text-muted pm-m-0"><?php echo esc_html($topic->description); ?></p>
                </div>
                <div class="pm-flex pm-flex-center-gap pm-flex-column pm-flex-sm-row">
                    <a href="#" class="pm-button pm-button-primary start-conversation-btn" 
                       data-topic-id="<?php echo esc_attr($topic->id); ?>"
                       data-topic-name="<?php echo esc_attr($topic->name); ?>">
                        💬 <?php _e('Start New Conversation', 'partyminder'); ?>
                    </a>
                    <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-button pm-button-secondary">
                        ← <?php _e('Back to All Topics', 'partyminder'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversations Header -->
    <div class="pm-flex pm-flex-between pm-flex-center-gap pm-mb-4">
        <h2 class="pm-heading pm-heading-md pm-text-primary pm-m-0">
            <?php printf(__('%d Conversations', 'partyminder'), count($conversations)); ?>
        </h2>
        <a href="#" class="pm-button pm-button-primary pm-button-small start-conversation-btn" 
           data-topic-id="<?php echo esc_attr($topic->id); ?>"
           data-topic-name="<?php echo esc_attr($topic->name); ?>">
            💬 <?php _e('Start New Conversation', 'partyminder'); ?>
        </a>
    </div>

    <!-- Conversations List -->
    <?php if (!empty($conversations)): ?>
        <div class="pm-flex pm-flex-column pm-gap-md">
            <?php foreach ($conversations as $conversation): ?>
                <div class="pm-card pm-card-hover">
                    <div class="pm-card-body">
                        <div class="pm-flex pm-flex-between pm-flex-center-gap">
                            <div class="pm-flex-1 pm-min-w-0">
                                <div class="pm-flex pm-flex-center-gap pm-mb-2">
                                    <?php if ($conversation->is_pinned): ?>
                                        <span class="pm-badge pm-badge-warning pm-text-xs">📌 <?php _e('Pinned', 'partyminder'); ?></span>
                                    <?php endif; ?>
                                    <h3 class="pm-heading pm-heading-sm pm-m-0">
                                        <a href="<?php echo home_url('/conversations/' . $topic->slug . '/' . $conversation->slug); ?>" 
                                           class="pm-text-primary pm-no-underline pm-truncate">
                                            <?php echo esc_html($conversation->title); ?>
                                        </a>
                                    </h3>
                                </div>
                                
                                <p class="pm-text-muted pm-mb-3 pm-line-clamp-2">
                                    <?php echo wp_trim_words(strip_tags($conversation->content), 25, '...'); ?>
                                </p>
                                
                                <div class="pm-flex pm-flex-wrap pm-flex-center-gap pm-text-xs pm-text-muted">
                                    <span><?php printf(__('by %s', 'partyminder'), esc_html($conversation->author_name)); ?></span>
                                    <span>•</span>
                                    <span><?php echo human_time_diff(strtotime($conversation->created_at), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?></span>
                                    <?php if ($conversation->last_reply_author && $conversation->last_reply_author !== $conversation->author_name): ?>
                                        <span>•</span>
                                        <span><?php printf(__('Last reply by %s', 'partyminder'), esc_html($conversation->last_reply_author)); ?></span>
                                        <span><?php echo human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="pm-flex pm-flex-column pm-text-center pm-min-w-20">
                                <?php if ($conversation->reply_count > 0): ?>
                                    <div class="pm-stat-number pm-text-success pm-text-lg pm-font-bold">
                                        <?php echo $conversation->reply_count; ?>
                                    </div>
                                    <div class="pm-stat-label pm-text-xs pm-text-muted">
                                        <?php echo $conversation->reply_count === 1 ? __('reply', 'partyminder') : __('replies', 'partyminder'); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="pm-badge pm-badge-success pm-text-xs"><?php _e('New', 'partyminder'); ?></span>
                                <?php endif; ?>
                                <div class="pm-text-xs pm-text-muted pm-mt-1">
                                    <?php echo human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="pm-card pm-text-center pm-p-8">
            <div class="pm-text-6xl pm-mb-4">💭</div>
            <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-3">
                <?php _e('No conversations yet', 'partyminder'); ?>
            </h3>
            <p class="pm-text-muted pm-mb-6">
                <?php _e('Be the first to start a conversation in this topic!', 'partyminder'); ?>
            </p>
            <a href="#" class="pm-button pm-button-primary start-conversation-btn" 
               data-topic-id="<?php echo esc_attr($topic->id); ?>"
               data-topic-name="<?php echo esc_attr($topic->name); ?>">
                ✨ <?php _e('Start the First Conversation', 'partyminder'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>