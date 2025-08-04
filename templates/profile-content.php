<?php
/**
 * Profile Content Template
 * User profile display and editing page
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
    echo '<div class="section text-center">';
    echo '<h3 class="heading heading-md">' . __('Profile Not Found', 'partyminder') . '</h3>';
    echo '<p class="text-muted">' . __('The requested user profile could not be found.', 'partyminder') . '</p>';
    echo '</div>';
    return;
}

// Get PartyMinder profile data
$profile_data = PartyMinder_Profile_Manager::get_user_profile($user_id);

// Handle profile form submission
$profile_updated = false;
if ($is_own_profile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['partyminder_profile_nonce'])) {
    if (wp_verify_nonce($_POST['partyminder_profile_nonce'], 'partyminder_profile_update')) {
        $result = PartyMinder_Profile_Manager::update_profile($user_id, $_POST);
        if ($result['success']) {
            $profile_updated = true;
            // Refresh profile data
            $profile_data = PartyMinder_Profile_Manager::get_user_profile($user_id);
        } else {
            $errors = $result['errors'];
        }
    }
}

// Set up template variables
$page_title = $is_editing 
    ? __('Edit Profile', 'partyminder') 
    : sprintf(__('%s\'s Profile', 'partyminder'), $user_data->display_name);
$page_description = $is_editing 
    ? __('Update your information, preferences, and privacy settings', 'partyminder')
    : __('View profile information and activity', 'partyminder');

$breadcrumbs = array(
    array('title' => __('Dashboard', 'partyminder'), 'url' => PartyMinder::get_dashboard_url()),
    array('title' => __('Profile', 'partyminder'))
);

// Profile Header (outside two-column layout)
if (!$is_editing) {
?>
<!-- Cover Photo Section - Full Width -->
<div class="header" style="position: relative; padding: 0; overflow: hidden; border: 2px solid var(--border); border-radius: 0.75rem;">
    <?php 
    $cover_photo = $profile_data['cover_image'] ?? '';
    $cover_style = $cover_photo 
        ? "background-image: url('" . esc_url($cover_photo) . "'); background-size: cover; background-position: center;" 
        : "background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);";
    ?>
    
    <!-- Cover Photo -->
    <div class="profile-cover" style="<?php echo $cover_style; ?>">
        <?php if ($is_own_profile): ?>
        <?php endif; ?>
    </div>
    
    <!-- Profile Info Overlay -->
    <div style="position: relative; padding: 1.5rem; margin-top: -4rem;">
        <div class="profile-header">
            <div class="flex gap-4 mb-4">
                <!-- Avatar -->
                <div style="position: relative;">
                    <div class="profile-avatar">
                        <?php echo get_avatar($user_id, 120, '', '', array('style' => 'width: 100%; height: 100%; object-fit: cover;')); ?>
                    </div>
                </div>
                
                <!-- User Info -->
                <div class="flex-1 profile-info" style="padding-top: 2rem;">
                    <h1 class="heading heading-xl mb-4" style="color: var(--text);"><?php echo esc_html($user_data->display_name); ?></h1>
                    
                    <div class="flex gap-4 flex-wrap mb-4">
                        <?php if (!empty($profile_data['location'])): ?>
                        <div class="flex gap-4">
                            <span>📍</span>
                            <span class="text-muted"><?php echo esc_html($profile_data['location']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex gap-4">
                            <span>📅</span>
                            <span class="text-muted"><?php printf(__('Member since %s', 'partyminder'), date('M Y', strtotime($user_data->user_registered))); ?></span>
                        </div>
                        <div class="flex gap-4">
                            <span>⭐</span>
                            <span class="text-muted"><?php _e('Active Host', 'partyminder'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="profile-actions">
                        <?php if ($is_own_profile): ?>
                        <div class="flex gap-4 flex-wrap">
                            <a href="<?php echo add_query_arg('edit', '1', PartyMinder::get_profile_url()); ?>" class="btn">
                                ✏️ <?php _e('Edit Profile', 'partyminder'); ?>
                            </a>
                            <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="btn btn-secondary">
                                📅 <?php _e('My Events', 'partyminder'); ?>
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="flex gap-4 flex-wrap">
                            <button class="btn">
                                💬 <?php _e('Send Message', 'partyminder'); ?>
                            </button>
                            <button class="btn btn-secondary">
                                👥 <?php _e('Follow', 'partyminder'); ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}

// Main content
ob_start();

// Show success message
if ($profile_updated || isset($_GET['updated'])) {
    echo '<div class="section mb-4" style="background: var(--success); color: white; border-color: var(--success);">';
    echo '<div class="flex flex-between">';
    echo '<div>';
    echo '<h4 class="heading heading-sm">' . __('Profile Updated!', 'partyminder') . '</h4>';
    echo '<p>' . __('Your profile has been successfully updated.', 'partyminder') . '</p>';
    echo '</div>';
    echo '<div>';
    echo '<a href="' . esc_url(PartyMinder::get_profile_url()) . '" class="btn btn-secondary">';
    echo '👤 ' . __('View Profile', 'partyminder');
    echo '</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Show errors if any
if (isset($errors) && !empty($errors)) {
    echo '<div class="section mb-4" style="background: var(--danger); color: white; border-color: var(--danger);">';
    echo '<h4 class="heading heading-sm">' . __('Please fix the following errors:', 'partyminder') . '</h4>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

if ($is_editing):
?>
<!-- Edit Profile Form -->
<div class="section">
    <form method="post" class="form" enctype="multipart/form-data">
        <?php wp_nonce_field('partyminder_profile_update', 'partyminder_profile_nonce'); ?>
        
        <!-- Basic Information -->
        <div class="form-group">
            <label class="form-label" for="display_name"><?php _e('Display Name', 'partyminder'); ?></label>
            <input type="text" 
                   id="display_name" 
                   name="display_name" 
                   class="form-input" 
                   value="<?php echo esc_attr($user_data->display_name); ?>" 
                   required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="bio"><?php _e('Bio', 'partyminder'); ?></label>
            <textarea id="bio" 
                      name="bio" 
                      class="form-textarea" 
                      placeholder="<?php _e('Tell people a bit about yourself...', 'partyminder'); ?>"><?php echo esc_textarea($profile_data['bio'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="location"><?php _e('Location', 'partyminder'); ?></label>
            <input type="text" 
                   id="location" 
                   name="location" 
                   class="form-input" 
                   value="<?php echo esc_attr($profile_data['location'] ?? ''); ?>" 
                   placeholder="<?php _e('City, State/Country', 'partyminder'); ?>">
        </div>
        
        <!-- Image Upload Section -->
        <div class="mb-4">
            <h3 class="heading heading-sm mb-4"><?php _e('Profile Images', 'partyminder'); ?></h3>
            
            <?php
            // Enqueue image upload assets
            PartyMinder_Image_Upload_Component::enqueue_assets();
            ?>
            
            <div class="grid grid-2 gap-4">
                <!-- Profile Photo Upload -->
                <div class="section text-center">
                    <div class="mb-4">
                        <div class="profile-avatar" style="width: 120px; height: 120px; margin: 0 auto;">
                            <?php echo get_avatar($user_id, 120, '', '', array('style' => 'width: 100%; height: 100%; object-fit: cover;')); ?>
                        </div>
                    </div>
                    <h4 class="heading heading-sm mb-4"><?php _e('Profile Photo', 'partyminder'); ?></h4>
                    <p class="text-muted mb-4"><?php _e('Your profile photo appears on your profile page and throughout the site', 'partyminder'); ?></p>
                    
                    <!-- Progress Bar for Profile Photo -->
                    <div class="upload-progress-bar" id="profile-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-text"><?php _e('Uploading profile photo...', 'partyminder'); ?></div>
                    </div>
                    
                    <?php
                    echo PartyMinder_Image_Upload_Component::render(array(
                        'entity_type' => 'user',
                        'entity_id' => $user_id,
                        'image_type' => 'profile',
                        'current_image' => $profile_data['profile_image'] ?? '',
                        'button_text' => __('Upload Profile Photo', 'partyminder'),
                        'button_icon' => '📷',
                        'button_class' => 'btn',
                        'modal_title' => __('Upload Profile Photo', 'partyminder'),
                        'show_preview' => false,
                        'dimensions' => __('Recommended: 400x400 pixels (square)', 'partyminder')
                    ));
                    ?>
                </div>
                
                <!-- Cover Photo Upload -->
                <div class="section text-center">
                    <div class="mb-4">
                        <div style="width: 200px; height: 80px; margin: 0 auto; border-radius: 0.5rem; overflow: hidden; border: 2px solid var(--border);">
                            <?php if (!empty($profile_data['cover_image'])): ?>
                            <img src="<?php echo esc_url($profile_data['cover_image']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;" 
                                 alt="<?php _e('Cover photo preview', 'partyminder'); ?>">
                            <?php else: ?>
                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem;">
                                <?php _e('No cover photo', 'partyminder'); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <h4 class="heading heading-sm mb-4"><?php _e('Cover Photo', 'partyminder'); ?></h4>
                    <p class="text-muted mb-4"><?php _e('Your cover photo appears at the top of your profile page', 'partyminder'); ?></p>
                    
                    <!-- Progress Bar for Cover Photo -->
                    <div class="upload-progress-bar" id="cover-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-text"><?php _e('Uploading cover photo...', 'partyminder'); ?></div>
                    </div>
                    
                    <?php
                    echo PartyMinder_Image_Upload_Component::render(array(
                        'entity_type' => 'user',
                        'entity_id' => $user_id,
                        'image_type' => 'cover',
                        'current_image' => $profile_data['cover_image'] ?? '',
                        'button_text' => __('Upload Cover Photo', 'partyminder'),
                        'button_icon' => '🖼️',
                        'button_class' => 'btn',
                        'modal_title' => __('Upload Cover Photo', 'partyminder'),
                        'show_preview' => false,
                        'dimensions' => __('Recommended: 1200x400 pixels (3:1 ratio)', 'partyminder')
                    ));
                    ?>
                </div>
            </div>
        </div>
        
        <div class="flex gap-4">
            <button type="submit" class="btn">
                💾 <?php _e('Save Changes', 'partyminder'); ?>
            </button>
            <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="btn btn-secondary">
                ↩️ <?php _e('Cancel', 'partyminder'); ?>
            </a>
        </div>
    </form>
</div>

<?php else: ?>
<!-- Profile Display - Main Content -->

<!-- About Section -->
<?php if (!empty($profile_data['bio'])): ?>
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm"><?php _e('About', 'partyminder'); ?></h3>
    </div>
    <p class="text-muted"><?php echo esc_html($profile_data['bio']); ?></p>
</div>
<?php endif; ?>

<!-- Activity Stats -->
<div class="section">
    <div class="section-header">
        <h3 class="heading heading-sm"><?php _e('Activity', 'partyminder'); ?></h3>
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
    
    <div class="grid grid-3 gap-4">
        <div class="text-center">
            <div class="stat-number text-primary"><?php echo intval($events_created); ?></div>
            <div class="stat-label"><?php _e('Events Created', 'partyminder'); ?></div>
        </div>
        <div class="text-center">
            <div class="stat-number text-primary"><?php echo intval($conversations_started); ?></div>
            <div class="stat-label"><?php _e('Conversations Started', 'partyminder'); ?></div>
        </div>
        <div class="text-center">
            <div class="stat-number text-primary"><?php echo rand(5, 25); ?></div>
            <div class="stat-label"><?php _e('Events Attended', 'partyminder'); ?></div>
        </div>
    </div>
</div>

<?php endif;

$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>
<?php if ($is_own_profile): ?>
<!-- Profile Management -->
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">⚙️ <?php _e('Profile Management', 'partyminder'); ?></h3>
    </div>
    <div class="flex gap-4 flex-wrap">
        <a href="<?php echo add_query_arg('edit', '1', PartyMinder::get_profile_url()); ?>" class="btn">
            ✏️ <?php _e('Edit Profile', 'partyminder'); ?>
        </a>
        <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="btn btn-secondary">
            📅 <?php _e('My Events', 'partyminder'); ?>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
    </div>
    <div class="flex gap-4 flex-wrap">
        <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn">
            ✨ <?php _e('Create Event', 'partyminder'); ?>
        </a>
        <a href="<?php echo esc_url(PartyMinder::get_events_page_url()); ?>" class="btn btn-secondary">
            🎪 <?php _e('Browse Events', 'partyminder'); ?>
        </a>
        <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="btn btn-secondary">
            💬 <?php _e('Join Conversations', 'partyminder'); ?>
        </a>
    </div>
</div>

<!-- Community Stats -->
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">🌟 <?php _e('Community', 'partyminder'); ?></h3>
    </div>
    <div class="text-muted">
        <div class="mb-4">
            <div class="flex flex-between">
                <span><?php _e('Member Level', 'partyminder'); ?></span>
                <strong><?php _e('Active Host', 'partyminder'); ?></strong>
            </div>
        </div>
        <div class="mb-4">
            <div class="flex flex-between">
                <span><?php _e('Reputation', 'partyminder'); ?></span>
                <strong><?php echo rand(85, 98); ?>%</strong>
            </div>
        </div>
    </div>
</div>
<?php
$sidebar_content = ob_get_clean();

// Include two-column template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
?>

<style>
.upload-progress-bar {
    margin-bottom: 1rem;
}

.upload-progress-bar .progress-bar {
    width: 100%;
    height: 0.5rem;
    background: var(--border);
    border-radius: 0.25rem;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.upload-progress-bar .progress-fill {
    height: 100%;
    background: var(--primary);
    width: 0%;
    transition: width 0.3s ease;
}

.upload-progress-bar .progress-text {
    text-align: center;
    color: var(--text-muted);
    font-size: 0.875rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let activeUploads = 0;
    const profileForm = document.querySelector('form.form');
    const saveButton = profileForm ? profileForm.querySelector('button[type="submit"]') : null;
    const originalSaveText = saveButton ? saveButton.textContent : '';
    
    // Listen for upload events
    document.addEventListener('partyminder:uploadStarted', function(e) {
        activeUploads++;
        updateFormState();
        
        // Show progress bar for the specific image type
        const progressBar = document.getElementById(e.detail.imageType + '-progress');
        if (progressBar) {
            progressBar.style.display = 'block';
            progressBar.querySelector('.progress-fill').style.width = '0%';
            progressBar.querySelector('.progress-text').textContent = 
                e.detail.imageType === 'profile' 
                ? '<?php _e('Uploading profile photo...', 'partyminder'); ?>'
                : '<?php _e('Uploading cover photo...', 'partyminder'); ?>';
        }
    });
    
    document.addEventListener('partyminder:uploadProgress', function(e) {
        const progressBar = document.getElementById(e.detail.imageType + '-progress');
        if (progressBar) {
            const percentage = Math.round(e.detail.progress);
            progressBar.querySelector('.progress-fill').style.width = percentage + '%';
            progressBar.querySelector('.progress-text').textContent = percentage + '%';
        }
    });
    
    document.addEventListener('partyminder:uploadCompleted', function(e) {
        activeUploads = Math.max(0, activeUploads - 1);
        updateFormState();
        
        // Hide progress bar after a short delay
        const progressBar = document.getElementById(e.detail.imageType + '-progress');
        if (progressBar) {
            setTimeout(() => {
                progressBar.style.display = 'none';
            }, 1000);
        }
    });
    
    document.addEventListener('partyminder:uploadError', function(e) {
        activeUploads = Math.max(0, activeUploads - 1);
        updateFormState();
        
        // Hide progress bar
        const progressBar = document.getElementById(e.detail.imageType + '-progress');
        if (progressBar) {
            progressBar.style.display = 'none';
        }
    });
    
    function updateFormState() {
        if (!saveButton) return;
        
        if (activeUploads > 0) {
            saveButton.disabled = true;
            saveButton.textContent = '<?php _e('Please wait for uploads to complete...', 'partyminder'); ?>';
            saveButton.style.opacity = '0.6';
        } else {
            saveButton.disabled = false;
            saveButton.textContent = originalSaveText;
            saveButton.style.opacity = '1';
        }
    }
    
    // Prevent form submission if uploads are active
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            if (activeUploads > 0) {
                e.preventDefault();
                alert('<?php _e('Please wait for image uploads to complete before saving.', 'partyminder'); ?>');
                return false;
            }
        });
    }
});
</script>