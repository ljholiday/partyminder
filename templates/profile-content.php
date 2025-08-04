<?php
/**
 * Profile Page Template
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
    echo '<div class="partyminder-error">';
    echo '<h3>' . __('Profile Not Found', 'partyminder') . '</h3>';
    echo '<p>' . __('The requested user profile could not be found.', 'partyminder') . '</p>';
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

// Show success message
if ($profile_updated || isset($_GET['updated'])) {
    echo '<div class="alert alert-success">';
    echo '<div class="flex flex-between">';
    echo '<div>';
    echo '<h4 class="">' . __('Profile Updated!', 'partyminder') . '</h4>';
    echo '<p class=" mt-4">' . __('Your profile has been successfully updated.', 'partyminder') . '</p>';
    echo '</div>';
    echo '<div>';
    echo '<a href="' . esc_url(PartyMinder::get_profile_url()) . '" class="btn btn-secondary btn-small">';
    echo '<span class="dashicons dashicons-admin-users"></span>';
    echo __('Return to Profile', 'partyminder');
    echo '</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Show errors if any
if (isset($errors) && !empty($errors)) {
    echo '<div class="alert alert-error">';
    echo '<h4>' . __('Please fix the following errors:', 'partyminder') . '</h4>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}
?>

<div class="page">
    
    <!-- Breadcrumb Navigation -->
    <div class="">
        <a href="<?php echo esc_url(PartyMinder::get_dashboard_url()); ?>" class="-link">
            🏠 <?php _e('Dashboard', 'partyminder'); ?>
        </a>
        <span class="-separator">→</span>
        <span class="-current"><?php _e('Profile', 'partyminder'); ?></span>
    </div>
    
    <?php if ($is_editing): ?>
        <!-- Edit Profile Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="heading heading-lg "><?php _e('Edit My Profile', 'partyminder'); ?></h2>
                <p class="text-muted "><?php _e('Update your information, preferences, and privacy settings.', 'partyminder'); ?></p>
            </div>
        </div>
        
        <form method="post" class="form" enctype="multipart/form-data">
            <?php wp_nonce_field('partyminder_profile_update', 'partyminder_profile_nonce'); ?>
            
            <!-- Basic Information Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="heading heading-md ">
                        <span class="dashicons dashicons-admin-users"></span> <?php _e('Basic Information', 'partyminder'); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="display_name"><?php _e('Display Name', 'partyminder'); ?></label>
                            <input type="text" id="display_name" name="display_name" class="form-input"
                                   value="<?php echo esc_attr($profile_data['display_name'] ?: $user_data->display_name); ?>" 
                                   maxlength="255">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="location"><?php _e('Location', 'partyminder'); ?></label>
                            <input type="text" id="location" name="location" class="form-input"
                                   value="<?php echo esc_attr($profile_data['location']); ?>" 
                                   placeholder="City, State" maxlength="255">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="bio"><?php _e('Bio', 'partyminder'); ?></label>
                        <textarea id="bio" name="bio" rows="4" maxlength="500" class="form-input form-textarea"
                                  placeholder="Tell others about yourself and your hosting style..."><?php echo esc_textarea($profile_data['bio']); ?></textarea>
                        <small class="character-count text-muted">0/500 characters</small>
                    </div>
                    
                    <!-- Photo Upload Section -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="profile_image"><?php _e('Profile Photo', 'partyminder'); ?></label>
                            <div class=" mb-4">
                                <div class="avatar">
                                    <?php if (!empty($profile_data['profile_image'])): ?>
                                        <img src="<?php echo esc_url($profile_data['profile_image']); ?>" alt="Profile" class="avatar-img">
                                    <?php else: ?>
                                        <?php echo get_avatar($user_id, 80, '', '', array('class' => 'avatar-img')); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="">
                                    <p class=" mb-4"><?php _e('Current profile photo', 'partyminder'); ?></p>
                                    <input type="file" id="profile_image" name="profile_image" class="form-input" accept="image/*">
                                    <small class="text-muted"><?php _e('Upload a new photo (JPG, PNG, GIF, WebP, max 5MB)', 'partyminder'); ?></small>
                                    
                                    <!-- Profile Image Upload Progress -->
                                    <div id="profile-image-progress" class="  mt-4">
                                        <div class="">
                                            <div class="" id="profile-progress-fill"></div>
                                        </div>
                                        <div class="  text-muted mt-4">
                                            <span id="profile-progress-status"><?php _e('Preparing image...', 'partyminder'); ?></span>
                                            <span id="profile-progress-percent" class="">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="cover_image"><?php _e('Cover Photo', 'partyminder'); ?></label>
                            <div class=" mb-4">
                                <div class="">
                                    <?php if (!empty($profile_data['cover_image'])): ?>
                                        <img src="<?php echo esc_url($profile_data['cover_image']); ?>" alt="Cover" class="">
                                    <?php else: ?>
                                        <div class="">
                                            <span class="dashicons dashicons-format-image"></span>
                                            <p><?php _e('No cover photo set', 'partyminder'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="">
                                    <p class=" mb-4"><?php _e('Cover photo (1200x400 recommended)', 'partyminder'); ?></p>
                                    <input type="file" id="cover_image" name="cover_image" class="form-input" accept="image/*">
                                    <small class="text-muted"><?php _e('Upload a cover photo (JPG, PNG, GIF, WebP, max 5MB)', 'partyminder'); ?></small>
                                    
                                    <!-- Cover Image Upload Progress -->
                                    <div id="cover-image-progress" class="  mt-4">
                                        <div class="">
                                            <div class="" id="cover-progress-fill"></div>
                                        </div>
                                        <div class="  text-muted mt-4">
                                            <span id="cover-progress-status"><?php _e('Preparing image...', 'partyminder'); ?></span>
                                            <span id="cover-progress-percent" class="">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Website Field -->
                    <div class="form-group">
                        <label class="form-label" for="website_url"><?php _e('Website', 'partyminder'); ?></label>
                        <input type="url" id="website_url" name="website_url" class="form-input"
                               value="<?php echo esc_attr($profile_data['website_url']); ?>" 
                               placeholder="https://yoursite.com">
                    </div>
                </div>
            </div>
            
            <!-- Hosting Preferences Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="heading heading-md ">
                        <span class="dashicons dashicons-calendar-alt"></span> <?php _e('Hosting Preferences', 'partyminder'); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label"><?php _e('Favorite Event Types', 'partyminder'); ?></label>
                        <div class="grid grid-2">
                            <?php
                            $event_types = array(
                                'dinner_party' => __('Dinner Parties', 'partyminder'),
                                'cocktail_party' => __('Cocktail Parties', 'partyminder'),
                                'bbq' => __('BBQ & Grilling', 'partyminder'),
                                'game_night' => __('Game Nights', 'partyminder'),
                                'book_club' => __('Book Clubs', 'partyminder'),
                                'wine_tasting' => __('Wine Tastings', 'partyminder'),
                                'outdoor' => __('Outdoor Events', 'partyminder'),
                                'cultural' => __('Cultural Events', 'partyminder'),
                            );
                            $selected_types = json_decode($profile_data['favorite_event_types'] ?: '[]', true);
                            foreach ($event_types as $key => $label):
                            ?>
                            <label class="flex gap-4 mb-4">
                                <input type="checkbox" name="favorite_event_types[]" value="<?php echo $key; ?>" 
                                       <?php checked(in_array($key, $selected_types)); ?>>
                                <span><?php echo $label; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="dietary_restrictions"><?php _e('Dietary Restrictions/Preferences', 'partyminder'); ?></label>
                            <textarea id="dietary_restrictions" name="dietary_restrictions" rows="3" class="form-input form-textarea"
                                      placeholder="Vegetarian, vegan, gluten-free, allergies, etc."><?php echo esc_textarea($profile_data['dietary_restrictions']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="accessibility_needs"><?php _e('Accessibility Considerations', 'partyminder'); ?></label>
                            <textarea id="accessibility_needs" name="accessibility_needs" rows="3" class="form-input form-textarea"
                                      placeholder="Mobility access, sensory considerations, etc."><?php echo esc_textarea($profile_data['accessibility_needs']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Privacy Settings Section -->
            <div class="form-section">
                <h3><span class="dashicons dashicons-privacy"></span> <?php _e('Privacy Settings', 'partyminder'); ?></h3>
                
                <?php $privacy_settings = json_decode($profile_data['privacy_settings'] ?: '{}', true); ?>
                
                <div class="form-group">
                    <label><?php _e('Profile Visibility', 'partyminder'); ?></label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="privacy[profile_visibility]" value="public" 
                                   <?php checked($privacy_settings['profile_visibility'] ?? 'public', 'public'); ?>>
                            <div class="option-card">
                                <div class="option-icon">🌍</div>
                                <div class="option-content">
                                    <div class="option-title"><?php _e('Public', 'partyminder'); ?></div>
                                    <div class="option-desc"><?php _e('Anyone can view your profile', 'partyminder'); ?></div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-option">
                            <input type="radio" name="privacy[profile_visibility]" value="community" 
                                   <?php checked($privacy_settings['profile_visibility'] ?? 'public', 'community'); ?>>
                            <div class="option-card">
                                <div class="option-icon">👥</div>
                                <div class="option-content">
                                    <div class="option-title"><?php _e('Community Members', 'partyminder'); ?></div>
                                    <div class="option-desc"><?php _e('Only members of your communities can see your profile', 'partyminder'); ?></div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-option">
                            <input type="radio" name="privacy[profile_visibility]" value="private" 
                                   <?php checked($privacy_settings['profile_visibility'] ?? 'public', 'private'); ?>>
                            <div class="option-card">
                                <div class="option-icon">🔒</div>
                                <div class="option-content">
                                    <div class="option-title"><?php _e('Private', 'partyminder'); ?></div>
                                    <div class="option-desc"><?php _e('Only you can see your full profile', 'partyminder'); ?></div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Notification Preferences Section -->
            <div class="form-section">
                <h3><span class="dashicons dashicons-email-alt"></span> <?php _e('Notification Preferences', 'partyminder'); ?></h3>
                
                <?php $notification_prefs = json_decode($profile_data['notification_preferences'] ?: '{}', true); ?>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="notifications[new_events]" value="1" 
                               <?php checked($notification_prefs['new_events'] ?? true, 1); ?>>
                        <?php _e('Notify me about new events in my communities', 'partyminder'); ?>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="notifications[event_invitations]" value="1" 
                               <?php checked($notification_prefs['event_invitations'] ?? true, 1); ?>>
                        <?php _e('Notify me when I receive event invitations', 'partyminder'); ?>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="notifications[rsvp_updates]" value="1" 
                               <?php checked($notification_prefs['rsvp_updates'] ?? true, 1); ?>>
                        <?php _e('Notify me about RSVP changes to my events', 'partyminder'); ?>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="notifications[community_activity]" value="1" 
                               <?php checked($notification_prefs['community_activity'] ?? false, 1); ?>>
                        <?php _e('Notify me about activity in my communities', 'partyminder'); ?>
                    </label>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="card mb-4">
                <div class="card-body">
                    <!-- Overall Upload Progress -->
                    <div id="overall-upload-progress" class="  mb-4">
                        <div class="flex gap-4 mb-4">
                            <span class="dashicons dashicons-upload text-primary"></span>
                            <strong class="text-primary"><?php _e('Uploading Images...', 'partyminder'); ?></strong>
                        </div>
                        <div class=" -lg">
                            <div class="" id="overall-progress-fill"></div>
                        </div>
                        <div class="  text-muted mt-4 text-center">
                            <span id="overall-progress-status"><?php _e('Processing images, please wait...', 'partyminder'); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" id="profile-submit-btn" class="btn">
                            <span class=" dashicons dashicons-yes"></span>
                            <span class=""><?php _e('Save Profile', 'partyminder'); ?></span>
                            <span class=" ">
                                <span class="dashicons dashicons-update "></span>
                                <?php _e('Saving...', 'partyminder'); ?>
                            </span>
                        </button>
                        
                        <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="btn btn-secondary">
                            <span class="dashicons dashicons-no-alt"></span>
                            <?php _e('Cancel', 'partyminder'); ?>
                        </a>
                    </div>
                    
                    <!-- Upload Warning -->
                    <div id="upload-warning" class="  mt-4">
                        <div class="flex gap-4">
                            <span class="dashicons dashicons-warning text-primary"></span>
                            <span class="text-primary "><?php _e('Images are still uploading. Please wait before saving.', 'partyminder'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
    <?php else: ?>
        <!-- View Profile -->
        <!-- Cover Image -->
        <div class="">
            <?php if (!empty($profile_data['cover_image'])): ?>
                <img src="<?php echo esc_url($profile_data['cover_image']); ?>" alt="<?php echo esc_attr($profile_data['display_name'] ?: $user_data->display_name); ?> Cover" class="-img">
            <?php else: ?>
                <div class="-placeholder">
                    <div class="-content">
                        <span class="text-muted ">🖼️ <?php _e('No cover image set', 'partyminder'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
            
        <!-- Profile Header -->
        <div class="">
            <div class="">
                <div class="avatar">
                    <?php if (!empty($profile_data['profile_image'])): ?>
                        <img src="<?php echo esc_url($profile_data['profile_image']); ?>" alt="<?php echo esc_attr($profile_data['display_name'] ?: $user_data->display_name); ?>" class="avatar">
                    <?php else:
                        $avatar = get_avatar($user_id, 120, '', '', array('class' => 'avatar'));
                        if ($avatar) {
                            echo $avatar;
                        } else {
                            // Fallback to initials if no avatar
                            ?>
                            <div class="">
                                <?php echo strtoupper(substr($profile_data['display_name'] ?: $user_data->display_name, 0, 1)); ?>
                            </div>
                            <?php
                        }
                    endif;
                    ?>
                </div>
            </div>
            
            <div class="pm-profile-info">
                <div class="pm-profile-name-section">
                    <h1 class="pm-profile-name">
                        <?php echo esc_html($profile_data['display_name'] ?: $user_data->display_name); ?>
                        <?php if ($profile_data['is_verified']): ?>
                            <span class="badge badge-success" title="<?php _e('Verified Host', 'partyminder'); ?>">✓</span>
                        <?php endif; ?>
                    </h1>
                    
                    <?php if ($profile_data['location']): ?>
                        <div class="pm-profile-location">
                            📍 <?php echo esc_html($profile_data['location']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_own_profile): ?>
                    <div class="pm-profile-actions">
                        <a href="<?php echo esc_url(add_query_arg('edit', '1', PartyMinder::get_profile_url())); ?>" class="btn">
                            ✏️ <?php _e('Edit Profile', 'partyminder'); ?>
                        </a>
                        <a href="<?php echo esc_url(PartyMinder::get_logout_url()); ?>" class="btn btn-secondary">
                            🚪 <?php _e('Logout', 'partyminder'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Use Unified Two-Column Layout -->
        <div class="grid grid-2 gap-4">
            <!-- Main Content Column -->
            <div class="">
                <!-- Activity Feed -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="heading heading-sm ">📈 <?php _e('Recent Activity', 'partyminder'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Include the reusable activity feed component
                        $user_id = $user_id; // Pass the current profile user ID
                        $limit = 8;
                        $show_user_names = false; // This is the user's own profile
                        $activity_types = array(); // Show all activity types
                        $show_empty_state = true;
                        $empty_state_actions = true;
                        
                        include PARTYMINDER_PLUGIN_DIR . 'templates/components/activity-feed.php';
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Column -->
            <div class="">
                <!-- About Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="heading heading-sm ">👋 <?php _e('About', 'partyminder'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($profile_data['bio']): ?>
                            <p class=""><?php echo wp_kses_post(nl2br($profile_data['bio'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted "><?php _e('No bio added yet.', 'partyminder'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Community Stats -->
                <?php
                require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
                $conversation_manager = new PartyMinder_Conversation_Manager();
                $stats = $conversation_manager->get_stats();
                ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="heading heading-sm ">📊 <?php _e('Community Stats', 'partyminder'); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-2 pm-gap-sm">
                            <div class="stat text-center">
                                <div class="stat-number text-primary"><?php echo $stats->total_conversations; ?></div>
                                <div class="stat-label"><?php _e('Conversations', 'partyminder'); ?></div>
                            </div>
                            <div class="stat text-center">
                                <div class="stat-number text-primary"><?php echo $stats->total_replies; ?></div>
                                <div class="stat-label"><?php _e('Messages', 'partyminder'); ?></div>
                            </div>
                            <div class="stat text-center">
                                <div class="stat-number text-primary"><?php echo $stats->active_conversations; ?></div>
                                <div class="stat-label"><?php _e('Active This Week', 'partyminder'); ?></div>
                            </div>
                            <div class="stat text-center">
                                <div class="stat-number text-primary"><?php echo $stats->total_follows; ?></div>
                                <div class="stat-label"><?php _e('Following', 'partyminder'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Favorite Event Types -->
                <?php if ($profile_data['favorite_event_types']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="heading heading-sm ">🎉 <?php _e('Favorite Event Types', 'partyminder'); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="flex flex-wrap pm-gap-xs">
                            <?php
                            $favorite_types = json_decode($profile_data['favorite_event_types'], true);
                            $event_type_labels = array(
                                'dinner_party' => __('Dinner Parties', 'partyminder'),
                                'cocktail_party' => __('Cocktail Parties', 'partyminder'),
                                'bbq' => __('BBQ & Grilling', 'partyminder'),
                                'game_night' => __('Game Nights', 'partyminder'),
                                'book_club' => __('Book Clubs', 'partyminder'),
                                'wine_tasting' => __('Wine Tastings', 'partyminder'),
                                'outdoor' => __('Outdoor Events', 'partyminder'),
                                'cultural' => __('Cultural Events', 'partyminder'),
                            );
                            foreach ($favorite_types as $type):
                                if (isset($event_type_labels[$type])):
                            ?>
                            <span class="badge badge-secondary "><?php echo esc_html($event_type_labels[$type]); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Website -->
                <?php if ($profile_data['website_url']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="heading heading-sm ">🔗 <?php _e('Website', 'partyminder'); ?></h3>
                    </div>
                    <div class="card-body">
                        <a href="<?php echo esc_url($profile_data['website_url']); ?>" target="_blank" rel="noopener noreferrer" class="text-primary ">
                            🌐 <?php echo esc_html(parse_url($profile_data['website_url'], PHP_URL_HOST)); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
    <?php endif; ?>
    
</div>

<script>
// Profile Form Enhancement with Upload Progress
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for bio field
    const bioField = document.getElementById('bio');
    const charCount = document.querySelector('.character-count');
    
    if (bioField && charCount) {
        function updateCharCount() {
            const count = bioField.value.length;
            charCount.textContent = count + '/500 characters';
            if (count > 450) {
                charCount.style.color = '#ef4444';
            } else {
                charCount.style.color = '#666';
            }
        }
        
        bioField.addEventListener('input', updateCharCount);
        updateCharCount();
    }

    // Upload Progress Management
    const profileImageInput = document.getElementById('profile_image');
    const coverImageInput = document.getElementById('cover_image');
    const submitBtn = document.getElementById('profile-submit-btn');
    const profileForm = document.querySelector('.form');
    
    let uploadState = {
        profileImage: { selected: false, processed: false, progress: 0 },
        coverImage: { selected: false, processed: false, progress: 0 },
        isUploading: false
    };

    // File Selection Handlers
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function(e) {
            handleFileSelection('profile', e.target.files[0]);
        });
    }

    if (coverImageInput) {
        coverImageInput.addEventListener('change', function(e) {
            handleFileSelection('cover', e.target.files[0]);
        });
    }

    function handleFileSelection(type, file) {
        if (!file) {
            uploadState[type + 'Image'].selected = false;
            uploadState[type + 'Image'].processed = false;
            hideProgress(type);
            updateSubmitButton();
            return;
        }

        // Validate file
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (file.size > maxSize) {
            alert('<?php _e("File too large. Maximum size is 5MB.", "partyminder"); ?>');
            clearFileInput(type);
            return;
        }

        if (!allowedTypes.includes(file.type.toLowerCase())) {
            alert('<?php _e("Invalid file type. Please select JPG, PNG, GIF, or WebP.", "partyminder"); ?>');
            clearFileInput(type);
            return;
        }

        // File is valid - start processing simulation
        uploadState[type + 'Image'].selected = true;
        uploadState[type + 'Image'].processed = false;
        uploadState[type + 'Image'].progress = 0;

        // Update thumbnail preview immediately
        updateThumbnailPreview(type, file);
        
        showProgress(type);
        simulateImageProcessing(type, file);
        updateSubmitButton();
    }

    function simulateImageProcessing(type, file) {
        const progressElement = document.getElementById(type + '-progress-fill');
        const statusElement = document.getElementById(type + '-progress-status');
        const percentElement = document.getElementById(type + '-progress-percent');
        
        let progress = 0;
        const isLargeFile = file.size > 1024 * 1024; // 1MB
        const duration = isLargeFile ? 3000 : 1500; // Longer for larger files
        const steps = 20;
        const stepTime = duration / steps;

        statusElement.textContent = '<?php _e("Processing image...", "partyminder"); ?>';

        const interval = setInterval(() => {
            progress += 100 / steps;
            if (progress > 100) progress = 100;

            uploadState[type + 'Image'].progress = progress;
            progressElement.style.width = progress + '%';
            percentElement.textContent = Math.round(progress) + '%';

            // Update status messages
            if (progress < 30) {
                statusElement.textContent = '<?php _e("Validating image...", "partyminder"); ?>';
            } else if (progress < 70) {
                statusElement.textContent = '<?php _e("Optimizing image...", "partyminder"); ?>';
            } else if (progress < 100) {
                statusElement.textContent = '<?php _e("Preparing upload...", "partyminder"); ?>';
            } else {
                statusElement.textContent = '<?php _e("Ready to upload!", "partyminder"); ?>';
            }

            if (progress >= 100) {
                uploadState[type + 'Image'].processed = true;
            }

            updateOverallProgress();
            updateSubmitButton();

            if (progress >= 100) {
                clearInterval(interval);
                // Auto-hide individual progress after completion
                setTimeout(() => {
                    if (uploadState[type + 'Image'].processed) {
                        hideProgress(type);
                        updateOverallProgress(); // Update overall progress when individual completes
                    }
                }, 1500);
            }
        }, stepTime);
    }

    function showProgress(type) {
        const progressElement = document.getElementById(type + '-image-progress');
        if (progressElement) {
            progressElement.classList.remove('');
        }
    }

    function hideProgress(type) {
        const progressElement = document.getElementById(type + '-image-progress');
        if (progressElement) {
            progressElement.classList.add('');
        }
    }

    function updateOverallProgress() {
        const overallProgress = document.getElementById('overall-upload-progress');
        
        // Check if we have files that are selected but not yet processed
        const hasActiveUploads = 
            (uploadState.profileImage.selected && !uploadState.profileImage.processed) ||
            (uploadState.coverImage.selected && !uploadState.coverImage.processed);
        
        // Only show overall progress during active processing or form submission
        if (!hasActiveUploads && !uploadState.isUploading) {
            if (overallProgress) overallProgress.classList.add('');
            return;
        }

        // Show progress during active uploads
        if (overallProgress && hasActiveUploads) {
            overallProgress.classList.remove('');

            let totalProgress = 0;
            let fileCount = 0;

            if (uploadState.profileImage.selected) {
                totalProgress += uploadState.profileImage.progress;
                fileCount++;
            }

            if (uploadState.coverImage.selected) {
                totalProgress += uploadState.coverImage.progress;
                fileCount++;
            }

            const averageProgress = fileCount > 0 ? totalProgress / fileCount : 0;
            const overallFill = document.getElementById('overall-progress-fill');
            if (overallFill) {
                overallFill.style.width = averageProgress + '%';
            }
        }
    }

    function updateSubmitButton() {
        if (!submitBtn) return;

        const hasUnprocessedFiles = 
            (uploadState.profileImage.selected && !uploadState.profileImage.processed) ||
            (uploadState.coverImage.selected && !uploadState.coverImage.processed);

        const submitText = submitBtn.querySelector('.');
        const submitSpinner = submitBtn.querySelector('.');
        const uploadWarning = document.getElementById('upload-warning');

        if (hasUnprocessedFiles) {
            // Files are still processing
            submitBtn.disabled = true;
            submitBtn.classList.add('');
            if (uploadWarning) uploadWarning.classList.remove('');
        } else {
            // All files processed or no files selected
            submitBtn.disabled = false;
            submitBtn.classList.remove('');
            if (uploadWarning) uploadWarning.classList.add('');
        }

        // Handle submit spinner
        if (uploadState.isUploading) {
            if (submitText) submitText.classList.add('');
            if (submitSpinner) submitSpinner.classList.remove('');
        } else {
            if (submitText) submitText.classList.remove('');
            if (submitSpinner) submitSpinner.classList.add('');
        }
    }

    function updateThumbnailPreview(type, file) {
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            if (type === 'profile') {
                const preview = document.querySelector('.avatar-img');
                if (preview) {
                    preview.src = e.target.result;
                }
            } else if (type === 'cover') {
                const preview = document.querySelector('.');
                const placeholder = document.querySelector('.');
                const previewContainer = document.querySelector('.');
                
                if (preview) {
                    // Update existing image
                    preview.src = e.target.result;
                } else if (placeholder && previewContainer) {
                    // Replace placeholder with new image
                    placeholder.style.display = 'none';
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.alt = 'Cover Preview';
                    newImg.className = '';
                    previewContainer.appendChild(newImg);
                }
            }
        };
        reader.readAsDataURL(file);
    }

    function clearFileInput(type) {
        const input = type === 'profile' ? profileImageInput : coverImageInput;
        if (input) input.value = '';
        uploadState[type + 'Image'] = { selected: false, processed: false, progress: 0 };
        hideProgress(type);
        updateSubmitButton();
        updateOverallProgress();
        
        // Reset thumbnail preview
        resetThumbnailPreview(type);
    }

    function resetThumbnailPreview(type) {
        if (type === 'cover') {
            const preview = document.querySelector('.');
            const placeholder = document.querySelector('.');
            
            if (preview) {
                preview.remove();
            }
            if (placeholder) {
                placeholder.style.display = 'flex';
            }
        }
        // Profile image reset could be added here if needed
    }

    // Form submission handler
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const hasUnprocessedFiles = 
                (uploadState.profileImage.selected && !uploadState.profileImage.processed) ||
                (uploadState.coverImage.selected && !uploadState.coverImage.processed);

            if (hasUnprocessedFiles) {
                e.preventDefault();
                alert('<?php _e("Please wait for image processing to complete before saving.", "partyminder"); ?>');
                return false;
            }

            // Show upload progress
            uploadState.isUploading = true;
            updateSubmitButton();
            
            // Show overall progress during form submission
            const overallProgress = document.getElementById('overall-upload-progress');
            const statusElement = document.getElementById('overall-progress-status');
            if (overallProgress && statusElement) {
                overallProgress.classList.remove('');
                statusElement.textContent = '<?php _e("Saving profile changes...", "partyminder"); ?>';
                
                const progressFill = document.getElementById('overall-progress-fill');
                if (progressFill) {
                    progressFill.style.width = '100%';
                }
            }
        });
    }

    // Initialize UI state
    updateSubmitButton();
    updateOverallProgress(); // Hide overall progress on page load
});
</script>