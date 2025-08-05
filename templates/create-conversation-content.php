<?php
/**
 * Create Conversation Content Template
 * Uses unified form template system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
$conversation_manager = new PartyMinder_Conversation_Manager();

// Get topics for dropdown
$topics = $conversation_manager->get_topics();

// Get pre-selected topic from URL parameter
$selected_topic_id = intval($_GET['topic_id'] ?? 0);
$selected_topic = null;
if ($selected_topic_id) {
    foreach ($topics as $topic) {
        if ($topic->id == $selected_topic_id) {
            $selected_topic = $topic;
            break;
        }
    }
}

// Get current user info
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// Check for form submission success
$conversation_created = false;
$form_errors = array();

// Check if conversation was just created
if (isset($_GET['partyminder_created']) && $_GET['partyminder_created'] == '1') {
    $create_data = get_transient('partyminder_conversation_created_' . ($is_logged_in ? get_current_user_id() : session_id()));
    if ($create_data) {
        $conversation_created = true;
        $created_conversation = $create_data;
        // Clear the transient
        delete_transient('partyminder_conversation_created_' . ($is_logged_in ? get_current_user_id() : session_id()));
    }
}

// Check for form errors
$stored_errors = get_transient('partyminder_create_conversation_errors_' . ($is_logged_in ? get_current_user_id() : session_id()));
if ($stored_errors) {
    $form_errors = $stored_errors;
    // Clear the transient
    delete_transient('partyminder_create_conversation_errors_' . ($is_logged_in ? get_current_user_id() : session_id()));
}

// Set up template variables
$page_title = __('Start New Conversation', 'partyminder');
$page_description = __('Share ideas, ask questions, and connect with the community.', 'partyminder');
$breadcrumbs = array(
    array('title' => __('Conversations', 'partyminder'), 'url' => PartyMinder::get_conversations_url()),
    array('title' => __('Start New Conversation', 'partyminder'))
);

// If we have a selected topic, add it to breadcrumbs
if ($selected_topic) {
    $breadcrumbs = array(
        array('title' => __('Conversations', 'partyminder'), 'url' => PartyMinder::get_conversations_url()),
        array('title' => $selected_topic->icon . ' ' . $selected_topic->name, 'url' => home_url('/conversations/' . $selected_topic->slug)),
        array('title' => __('Start New Conversation', 'partyminder'))
    );
    $page_description = sprintf(__('Start a new conversation in %s', 'partyminder'), $selected_topic->name);
}

// Main content
ob_start();
?>

<?php if ($conversation_created): ?>
    <!-- Success Message -->
    <div class="pm-alert pm-alert-success pm-mb-4">
        <h3><?php _e('✅ Conversation Started Successfully!', 'partyminder'); ?></h3>
        <p><?php _e('Your conversation has been created and is now live.', 'partyminder'); ?></p>
        <div class="pm-success-actions">
            <a href="<?php echo esc_url($created_conversation['url'] ?? PartyMinder::get_conversations_url()); ?>" class="pm-btn">
                <span>👀</span>
                <?php _e('View Conversation', 'partyminder'); ?>
            </a>
            <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn-secondary">
                <span>🏠</span>
                <?php _e('All Conversations', 'partyminder'); ?>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($form_errors)): ?>
    <div class="pm-alert pm-alert-error pm-mb-4">
        <h4><?php _e('Please fix the following issues:', 'partyminder'); ?></h4>
        <ul>
            <?php foreach ($form_errors as $error): ?>
                <li><?php echo esc_html($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="pm-form" id="partyminder-conversation-form">
    <?php wp_nonce_field('create_partyminder_conversation', 'partyminder_conversation_nonce'); ?>
    <input type="hidden" name="action" value="partyminder_create_conversation">
    
    <?php if (!$is_logged_in): ?>
        <div class="pm-mb-4">
            <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Your Information', 'partyminder'); ?></h3>
            
            <div class="pm-form-row">
                <div class="pm-form-group">
                    <label for="guest_name" class="pm-form-label"><?php _e('Your Name *', 'partyminder'); ?></label>
                    <input type="text" id="guest_name" name="guest_name" class="pm-form-input" 
                           value="<?php echo esc_attr($_POST['guest_name'] ?? ''); ?>" 
                           placeholder="<?php esc_attr_e('Enter your name', 'partyminder'); ?>" required>
                </div>
                
                <div class="pm-form-group">
                    <label for="guest_email" class="pm-form-label"><?php _e('Your Email *', 'partyminder'); ?></label>
                    <input type="email" id="guest_email" name="guest_email" class="pm-form-input" 
                           value="<?php echo esc_attr($_POST['guest_email'] ?? ''); ?>" 
                           placeholder="<?php esc_attr_e('Enter your email address', 'partyminder'); ?>" required>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="pm-mb-4">
        <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Conversation Details', 'partyminder'); ?></h3>
        
        <div class="pm-form-group">
            <label for="topic_id" class="pm-form-label"><?php _e('Topic *', 'partyminder'); ?></label>
            <select id="topic_id" name="topic_id" class="pm-form-input" required>
                <option value=""><?php _e('Choose a topic...', 'partyminder'); ?></option>
                <?php foreach ($topics as $topic): ?>
                    <option value="<?php echo esc_attr($topic->id); ?>" 
                            <?php selected($selected_topic_id, $topic->id); ?>>
                        <?php echo esc_html($topic->icon . ' ' . $topic->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="pm-form-help pm-text-muted"><?php _e('Select the topic that best fits your conversation', 'partyminder'); ?></p>
        </div>
        
        <div class="pm-form-group">
            <label for="conversation_title" class="pm-form-label"><?php _e('Conversation Title *', 'partyminder'); ?></label>
            <input type="text" id="conversation_title" name="title" class="pm-form-input" 
                   value="<?php echo esc_attr($_POST['title'] ?? ''); ?>" 
                   placeholder="<?php esc_attr_e('What would you like to discuss?', 'partyminder'); ?>" 
                   maxlength="255" required>
            <p class="pm-form-help pm-text-muted"><?php _e('A clear, descriptive title helps others find and join your conversation', 'partyminder'); ?></p>
        </div>
        
        <div class="pm-form-group">
            <label for="conversation_content" class="pm-form-label"><?php _e('Your Message *', 'partyminder'); ?></label>
            <textarea id="conversation_content" name="content" class="pm-form-textarea" 
                      rows="8" required
                      placeholder="<?php esc_attr_e('Share your thoughts, ask a question, or start a discussion...', 'partyminder'); ?>"><?php echo esc_textarea($_POST['content'] ?? ''); ?></textarea>
            <p class="pm-form-help pm-text-muted"><?php _e('Provide context and details to encourage meaningful discussions', 'partyminder'); ?></p>
        </div>
    </div>
    
    <div class="pm-form-actions">
        <button type="submit" name="partyminder_create_conversation" class="pm-btn">
            <span>💬</span>
            <?php _e('Start Conversation', 'partyminder'); ?>
        </button>
        <a href="<?php echo esc_url($selected_topic ? home_url('/conversations/' . $selected_topic->slug) : PartyMinder::get_conversations_url()); ?>" class="pm-btn pm-btn-secondary">
            <span>👈</span>
            <?php _e('Back to Conversations', 'partyminder'); ?>
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();

// Include form template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php');
?>

<script>
jQuery(document).ready(function($) {
    $('#partyminder-conversation-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).html('<span>⏳</span> <?php _e("Starting Conversation...", "partyminder"); ?>');
        
        // Prepare form data
        const formData = new FormData(this);
        formData.append('action', 'partyminder_create_conversation');
        
        // Convert FormData to regular object for jQuery
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Redirect to success page or conversation
                    const redirectUrl = response.data.redirect_url || '<?php echo PartyMinder::get_create_conversation_url(); ?>?partyminder_created=1';
                    window.location.href = redirectUrl;
                } else {
                    // Show error message
                    $form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4><?php _e("Please fix the following issues:", "partyminder"); ?></h4><ul><li>' + (response.data || 'Unknown error occurred') + '</li></ul></div>');
                    
                    // Scroll to top to show error message
                    $('html, body').animate({scrollTop: 0}, 500);
                }
            },
            error: function() {
                $form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4><?php _e("Error", "partyminder"); ?></h4><p><?php _e("Network error. Please try again.", "partyminder"); ?></p></div>');
                
                // Scroll to top to show error message
                $('html, body').animate({scrollTop: 0}, 500);
            },
            complete: function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>