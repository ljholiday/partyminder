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


<div class="page">
    <!-- Breadcrumbs -->
    <nav class="s mb-4">
        <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="-link">
            🏠 <?php _e('Community Conversations', 'partyminder'); ?>
        </a>
        <span class="-separator">›</span>
        <span class="-current"><?php echo esc_html($topic->icon . ' ' . $topic->name); ?></span>
    </nav>

    <!-- Topic Header -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="flex flex-between mb-4">
                <div>
                    <h1 class="heading heading-lg text-primary mb-4">
                        <span class=""><?php echo esc_html($topic->icon); ?></span>
                        <?php echo esc_html($topic->name); ?>
                    </h1>
                    <p class="text-muted "><?php echo esc_html($topic->description); ?></p>
                </div>
                <div class="flex gap-4 flex-column flex-sm-row">
                    <a href="#" class="btn start-conversation-btn" 
                       data-topic-id="<?php echo esc_attr($topic->id); ?>"
                       data-topic-name="<?php echo esc_attr($topic->name); ?>">
                        💬 <?php _e('Start New Conversation', 'partyminder'); ?>
                    </a>
                    <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="btn btn-secondary">
                        ← <?php _e('Back to All Topics', 'partyminder'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversations Header -->
    <div class="flex flex-between mb-4">
        <h2 class="heading heading-md text-primary ">
            <?php printf(__('%d Conversations', 'partyminder'), count($conversations)); ?>
        </h2>
        <a href="#" class="btn btn-small start-conversation-btn" 
           data-topic-id="<?php echo esc_attr($topic->id); ?>"
           data-topic-name="<?php echo esc_attr($topic->name); ?>">
            💬 <?php _e('Start New Conversation', 'partyminder'); ?>
        </a>
    </div>

    <!-- Conversations List -->
    <?php if (!empty($conversations)): ?>
        <div class="flex pm-gap-md">
            <?php foreach ($conversations as $conversation): ?>
                <div class="card card-hover">
                    <div class="card-body">
                        <div class="flex flex-between">
                            <div class="flex-1 ">
                                <div class="flex gap-4 mb-4">
                                    <?php if ($conversation->is_pinned): ?>
                                        <span class="badge badge-secondary ">📌 <?php _e('Pinned', 'partyminder'); ?></span>
                                    <?php endif; ?>
                                    <h3 class="heading heading-sm ">
                                        <a href="<?php echo home_url('/conversations/' . $topic->slug . '/' . $conversation->slug); ?>" 
                                           class="text-primary  ">
                                            <?php echo esc_html($conversation->title); ?>
                                        </a>
                                    </h3>
                                </div>
                                
                                <p class="text-muted mb-4 pm-line-clamp-2">
                                    <?php echo wp_trim_words(strip_tags($conversation->content), 25, '...'); ?>
                                </p>
                                
                                <div class="flex flex-wrap flex-center-gap  text-muted">
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
                            
                            <div class="flex text-center pm-min-w-20">
                                <?php if ($conversation->reply_count > 0): ?>
                                    <div class="stat-number text-primary  pm-font-bold">
                                        <?php echo $conversation->reply_count; ?>
                                    </div>
                                    <div class="stat-label  text-muted">
                                        <?php echo $conversation->reply_count === 1 ? __('reply', 'partyminder') : __('replies', 'partyminder'); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge badge-success "><?php _e('New', 'partyminder'); ?></span>
                                <?php endif; ?>
                                <div class=" text-muted mt-4">
                                    <?php echo human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp')) . ' ' . __('ago', 'partyminder'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card text-center pm-p-8">
            <div class="pm-text-6xl mb-4">💭</div>
            <h3 class="heading heading-md text-primary mb-4">
                <?php _e('No conversations yet', 'partyminder'); ?>
            </h3>
            <p class="text-muted mb-4">
                <?php _e('Be the first to start a conversation in this topic!', 'partyminder'); ?>
            </p>
            <a href="#" class="btn start-conversation-btn" 
               data-topic-id="<?php echo esc_attr($topic->id); ?>"
               data-topic-name="<?php echo esc_attr($topic->name); ?>">
                ✨ <?php _e('Start the First Conversation', 'partyminder'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>