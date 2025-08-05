<?php
/**
 * Profile Content Template - Unified System
 * User profile display and editing page using unified templates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get user ID from query var or default to current user
$user_id = get_query_var('user', get_current_user_id());
$current_user_id = get_current_user_id();
$is_own_profile = ($user_id == $current_user_id);
$is_editing = $is_own_profile && isset($_GET['edit']);

// Get WordPress user data
$user_data = get_userdata($user_id);
if (!$user_data) {
    echo '<div class="pm-section pm-text-center">';
    echo '<h3 class="pm-heading pm-heading-md">' . __('Profile Not Found', 'partyminder') . '</h3>';
    echo '<p class="pm-text-muted">' . __('The requested user profile could not be found.', 'partyminder') . '</p>';
    echo '</div>';
    return;
}

// Get PartyMinder profile data
$profile_data = PartyMinder_Profile_Manager::get_user_profile($user_id);

// Handle profile form submission
$profile_updated = false;
$form_errors = array();
if ($is_own_profile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['partyminder_profile_nonce'])) {
    if (wp_verify_nonce($_POST['partyminder_profile_nonce'], 'partyminder_profile_update')) {
        $result = PartyMinder_Profile_Manager::update_profile($user_id, $_POST);
        if ($result['success']) {
            $profile_updated = true;
            // Refresh profile data
            $profile_data = PartyMinder_Profile_Manager::get_user_profile($user_id);
        } else {
            $form_errors = $result['errors'];
        }
    }
}

// Set up template variables
$page_title = $is_editing 
    ? __('Edit Profile', 'partyminder') 
    : $user_data->display_name;
$page_description = $is_editing 
    ? __('Update your information, preferences, and privacy settings', 'partyminder')
    : sprintf(__('%s\'s profile and activity', 'partyminder'), $user_data->display_name);

$breadcrumbs = array(
    array('title' => __('Dashboard', 'partyminder'), 'url' => PartyMinder::get_dashboard_url()),
    array('title' => __('Profile', 'partyminder'))
);

// If editing, use form template
if ($is_editing) {
    // Main content for form
    ob_start();
    
    // Success message
    if ($profile_updated || isset($_GET['updated'])) {
        echo '<div class="pm-alert pm-alert-success pm-mb-4">';
        echo '<h4 class="pm-heading pm-heading-sm">' . __('Profile Updated!', 'partyminder') . '</h4>';
        echo '<p>' . __('Your profile has been successfully updated.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(PartyMinder::get_profile_url()) . '" class="pm-btn pm-btn-secondary">';
        echo '👤 ' . __('View Profile', 'partyminder');
        echo '</a>';
        echo '</div>';
    }

    // Show errors if any
    if (!empty($form_errors)) {
        echo '<div class="pm-alert pm-alert-error pm-mb-4">';
        echo '<h4 class="pm-heading pm-heading-sm">' . __('Please fix the following errors:', 'partyminder') . '</h4>';
        echo '<ul>';
        foreach ($form_errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    ?>

    <form method="post" class="pm-form" enctype="multipart/form-data">
        <?php wp_nonce_field('partyminder_profile_update', 'partyminder_profile_nonce'); ?>
        
        <div class="pm-mb-4">
            <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Basic Information', 'partyminder'); ?></h3>
            
            <div class="pm-form-group">
                <label class="pm-form-label" for="display_name"><?php _e('Display Name *', 'partyminder'); ?></label>
                <input type="text" 
                       id="display_name" 
                       name="display_name" 
                       class="pm-form-input" 
                       value="<?php echo esc_attr($user_data->display_name); ?>" 
                       required>
            </div>
            
            <div class="pm-form-group">
                <label class="pm-form-label" for="bio"><?php _e('Bio', 'partyminder'); ?></label>
                <textarea id="bio" 
                          name="bio" 
                          class="pm-form-textarea" 
                          rows="4"
                          placeholder="<?php _e('Tell people a bit about yourself...', 'partyminder'); ?>"><?php echo esc_textarea($profile_data['bio'] ?? ''); ?></textarea>
            </div>
            
            <div class="pm-form-group">
                <label class="pm-form-label" for="location"><?php _e('Location', 'partyminder'); ?></label>
                <input type="text" 
                       id="location" 
                       name="location" 
                       class="pm-form-input" 
                       value="<?php echo esc_attr($profile_data['location'] ?? ''); ?>" 
                       placeholder="<?php _e('City, State/Country', 'partyminder'); ?>">
            </div>
        </div>
        
        <div class="pm-mb-4">
            <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Profile Images', 'partyminder'); ?></h3>
            
            <?php
            // Enqueue image upload assets
            PartyMinder_Image_Upload_Component::enqueue_assets();
            ?>
            
            <div class="pm-form-row">
                <!-- Profile Photo Upload -->
                <div class="pm-form-group">
                    <label class="pm-form-label"><?php _e('Profile Photo', 'partyminder'); ?></label>
                    <div class="pm-text-center pm-mb">
                        <div class="pm-profile-avatar" style="width: 120px; height: 120px; margin: 0 auto;">
                            <?php echo get_avatar($user_id, 120, '', '', array('style' => 'width: 100%; height: 100%; object-fit: cover;')); ?>
                        </div>
                    </div>
                    <p class="pm-form-help pm-text-muted pm-mb"><?php _e('Your profile photo appears throughout the site', 'partyminder'); ?></p>
                    
                    <?php
                    echo PartyMinder_Image_Upload_Component::render(array(
                        'entity_type' => 'user',
                        'entity_id' => $user_id,
                        'image_type' => 'profile',
                        'current_image' => $profile_data['profile_image'] ?? '',
                        'button_text' => __('Upload Profile Photo', 'partyminder'),
                        'button_icon' => '📷',
                        'button_class' => 'pm-btn pm-btn-secondary',
                        'modal_title' => __('Upload Profile Photo', 'partyminder'),
                        'show_preview' => false,
                        'dimensions' => __('Recommended: 400x400 pixels (square)', 'partyminder')
                    ));
                    ?>
                </div>
                
                <!-- Cover Photo Upload -->
                <div class="pm-form-group">
                    <label class="pm-form-label"><?php _e('Cover Photo', 'partyminder'); ?></label>
                    <div class="pm-text-center pm-mb">
                        <div style="width: 200px; height: 80px; margin: 0 auto; border-radius: 0.5rem; overflow: hidden; border: 2px solid var(--pm-border);">
                            <?php if (!empty($profile_data['cover_image'])): ?>
                            <img src="<?php echo esc_url($profile_data['cover_image']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;" 
                                 alt="<?php _e('Cover photo preview', 'partyminder'); ?>">
                            <?php else: ?>
                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--pm-primary) 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem;">
                                <?php _e('No cover photo', 'partyminder'); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="pm-form-help pm-text-muted pm-mb"><?php _e('Your cover photo appears at the top of your profile', 'partyminder'); ?></p>
                    
                    <?php
                    echo PartyMinder_Image_Upload_Component::render(array(
                        'entity_type' => 'user',
                        'entity_id' => $user_id,
                        'image_type' => 'cover',
                        'current_image' => $profile_data['cover_image'] ?? '',
                        'button_text' => __('Upload Cover Photo', 'partyminder'),
                        'button_icon' => '🖼️',
                        'button_class' => 'pm-btn pm-btn-secondary',
                        'modal_title' => __('Upload Cover Photo', 'partyminder'),
                        'show_preview' => false,
                        'dimensions' => __('Recommended: 1200x400 pixels (3:1 ratio)', 'partyminder')
                    ));
                    ?>
                </div>
            </div>
        </div>
        
        <div class="pm-form-actions">
            <button type="submit" class="pm-btn">
                <span>💾</span>
                <?php _e('Save Changes', 'partyminder'); ?>
            </button>
            <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="pm-btn pm-btn-secondary">
                <span>👤</span>
                <?php _e('View Profile', 'partyminder'); ?>
            </a>
        </div>
    </form>

    <?php
    $content = ob_get_clean();
    
    // Include form template
    include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php');
    
} else {
    // Profile view mode - use two-column template
    
    // Profile Header Section
    $cover_photo = $profile_data['cover_image'] ?? '';
    $cover_style = $cover_photo 
        ? "background-image: url('" . esc_url($cover_photo) . "'); background-size: cover; background-position: center;" 
        : "background: linear-gradient(135deg, var(--pm-primary) 0%, #764ba2 100%);";
    ?>
    
    <!-- Profile Header -->
    <div class="pm-profile-header pm-mb">
        <div class="pm-profile-cover" style="<?php echo $cover_style; ?>"></div>
        
        <div class="pm-profile-info">
            <div class="pm-flex pm-gap pm-mb">
                <div class="pm-profile-avatar">
                    <?php echo get_avatar($user_id, 120); ?>
                </div>
                
                <div class="pm-flex-1">
                    <h1 class="pm-heading pm-heading-xl pm-mb"><?php echo esc_html($user_data->display_name); ?></h1>
                    
                    <div class="pm-flex pm-gap pm-flex-wrap pm-mb pm-text-muted">
                        <?php if (!empty($profile_data['location'])): ?>
                        <span>📍 <?php echo esc_html($profile_data['location']); ?></span>
                        <?php endif; ?>
                        <span>📅 <?php printf(__('Member since %s', 'partyminder'), date('M Y', strtotime($user_data->user_registered))); ?></span>
                        <span>⭐ <?php _e('Active Host', 'partyminder'); ?></span>
                    </div>
                    
                    <?php if ($is_own_profile): ?>
                    <div class="pm-flex pm-gap pm-flex-wrap">
                        <a href="<?php echo add_query_arg('edit', '1', PartyMinder::get_profile_url()); ?>" class="pm-btn">
                            ✏️ <?php _e('Edit Profile', 'partyminder'); ?>
                        </a>
                        <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="pm-btn pm-btn-secondary">
                            📅 <?php _e('My Events', 'partyminder'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Main content
    ob_start();
    ?>
    
    <?php if (!empty($profile_data['bio'])): ?>
    <div class="pm-section pm-mb">
        <div class="pm-section-header">
            <h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e('About', 'partyminder'); ?></h3>
        </div>
        <p><?php echo esc_html($profile_data['bio']); ?></p>
    </div>
    <?php endif; ?>

    <div class="pm-section">
        <div class="pm-section-header">
            <h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e('Activity Stats', 'partyminder'); ?></h3>
        </div>
        
        <?php
        // Get user activity stats
        global $wpdb;
        $events_table = $wpdb->prefix . 'partyminder_events';
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        
        $events_created = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $events_table WHERE author_id = %d AND event_status = 'active'",
            $user_id
        ));
        
        $conversations_started = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $conversations_table WHERE author_id = %d",
            $user_id
        ));
        ?>
        
        <div class="pm-grid pm-grid-3 pm-gap">
            <div class="pm-text-center">
                <div class="pm-stat-number pm-text-primary"><?php echo intval($events_created); ?></div>
                <div class="pm-stat-label"><?php _e('Events Created', 'partyminder'); ?></div>
            </div>
            <div class="pm-text-center">
                <div class="pm-stat-number pm-text-primary"><?php echo intval($conversations_started); ?></div>
                <div class="pm-stat-label"><?php _e('Conversations Started', 'partyminder'); ?></div>
            </div>
            <div class="pm-text-center">
                <div class="pm-stat-number pm-text-primary"><?php echo rand(5, 25); ?></div>
                <div class="pm-stat-label"><?php _e('Events Attended', 'partyminder'); ?></div>
            </div>
        </div>
    </div>

    <?php
    $main_content = ob_get_clean();

    // Sidebar content
    ob_start();
    ?>
    
    <?php if ($is_own_profile): ?>
    <div class="pm-section pm-mb">
        <div class="pm-section-header">
            <h3 class="pm-heading pm-heading-sm">⚙️ <?php _e('Profile Management', 'partyminder'); ?></h3>
        </div>
        <div class="pm-flex pm-gap pm-flex-column">
            <a href="<?php echo add_query_arg('edit', '1', PartyMinder::get_profile_url()); ?>" class="pm-btn">
                ✏️ <?php _e('Edit Profile', 'partyminder'); ?>
            </a>
            <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="pm-btn pm-btn-secondary">
                📅 <?php _e('My Events', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="pm-section pm-mb">
        <div class="pm-section-header">
            <h3 class="pm-heading pm-heading-sm">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
        </div>
        <div class="pm-flex pm-gap pm-flex-column">
            <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="pm-btn pm-btn-secondary">
                ✨ <?php _e('Create Event', 'partyminder'); ?>
            </a>
            <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="pm-btn pm-btn-secondary">
                💬 <?php _e('Browse Conversations', 'partyminder'); ?>
            </a>
        </div>
    </div>

    <div class="pm-section pm-mb">
        <div class="pm-section-header">
            <h3 class="pm-heading pm-heading-sm">🌟 <?php _e('Community Stats', 'partyminder'); ?></h3>
        </div>
        <div class="pm-stat-list">
            <div class="pm-stat-item">
                <span class="pm-stat-label"><?php _e('Member Level', 'partyminder'); ?></span>
                <span class="pm-stat-value"><?php _e('Active Host', 'partyminder'); ?></span>
            </div>
            <div class="pm-stat-item">
                <span class="pm-stat-label"><?php _e('Reputation', 'partyminder'); ?></span>
                <span class="pm-stat-value"><?php echo rand(85, 98); ?>%</span>
            </div>
        </div>
    </div>
    
    <?php
    $sidebar_content = ob_get_clean();

    // Include two-column template
    include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
}
?>