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
    echo '<div class="pm-message pm-message-success">';
    echo '<h4>' . __('Profile Updated!', 'partyminder') . '</h4>';
    echo '<p>' . __('Your profile has been successfully updated.', 'partyminder') . '</p>';
    echo '</div>';
}

// Show errors if any
if (isset($errors) && !empty($errors)) {
    echo '<div class="pm-message pm-message-error">';
    echo '<h4>' . __('Please fix the following errors:', 'partyminder') . '</h4>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}
?>

<div class="partyminder-content pm-container">
    
    <!-- Breadcrumb Navigation -->
    <div class="pm-breadcrumb">
        <a href="<?php echo esc_url(PartyMinder::get_dashboard_url()); ?>" class="pm-breadcrumb-link">
            🏠 <?php _e('Dashboard', 'partyminder'); ?>
        </a>
        <span class="pm-breadcrumb-separator">→</span>
        <span class="pm-breadcrumb-current"><?php _e('Profile', 'partyminder'); ?></span>
    </div>
    
    <?php if ($is_editing): ?>
        <!-- Edit Profile Form -->
        <div class="pm-card pm-mb-6">
            <div class="pm-card-header">
                <h2 class="pm-heading pm-heading-lg pm-m-0"><?php _e('Edit My Profile', 'partyminder'); ?></h2>
                <p class="pm-text-muted pm-m-0"><?php _e('Update your information, preferences, and privacy settings.', 'partyminder'); ?></p>
            </div>
        </div>
        
        <form method="post" class="pm-form" enctype="multipart/form-data">
            <?php wp_nonce_field('partyminder_profile_update', 'partyminder_profile_nonce'); ?>
            
            <!-- Basic Information Section -->
            <div class="pm-card pm-mb-6">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-md pm-m-0">
                        <span class="dashicons dashicons-admin-users"></span> <?php _e('Basic Information', 'partyminder'); ?>
                    </h3>
                </div>
                <div class="pm-card-body">
                    <div class="pm-form-row">
                        <div class="pm-form-group">
                            <label class="pm-label" for="display_name"><?php _e('Display Name', 'partyminder'); ?></label>
                            <input type="text" id="display_name" name="display_name" class="pm-input"
                                   value="<?php echo esc_attr($profile_data['display_name'] ?: $user_data->display_name); ?>" 
                                   maxlength="255">
                        </div>
                        
                        <div class="pm-form-group">
                            <label class="pm-label" for="location"><?php _e('Location', 'partyminder'); ?></label>
                            <input type="text" id="location" name="location" class="pm-input"
                                   value="<?php echo esc_attr($profile_data['location']); ?>" 
                                   placeholder="City, State" maxlength="255">
                        </div>
                    </div>
                    
                    <div class="pm-form-group">
                        <label class="pm-label" for="bio"><?php _e('Bio', 'partyminder'); ?></label>
                        <textarea id="bio" name="bio" rows="4" maxlength="500" class="pm-input pm-textarea"
                                  placeholder="Tell others about yourself and your hosting style..."><?php echo esc_textarea($profile_data['bio']); ?></textarea>
                        <small class="character-count pm-text-muted">0/500 characters</small>
                    </div>
                    
                    <div class="pm-form-row">
                        <div class="pm-form-group">
                            <label class="pm-label" for="website_url"><?php _e('Website', 'partyminder'); ?></label>
                            <input type="url" id="website_url" name="website_url" class="pm-input"
                                   value="<?php echo esc_attr($profile_data['website_url']); ?>" 
                                   placeholder="https://yoursite.com">
                        </div>
                        
                        <div class="pm-form-group">
                            <label class="pm-label" for="profile_image"><?php _e('Profile Photo', 'partyminder'); ?></label>
                            <input type="file" id="profile_image" name="profile_image" class="pm-input" accept="image/*">
                            <small class="pm-text-muted"><?php _e('Upload a new profile photo (JPG, PNG, max 2MB)', 'partyminder'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hosting Preferences Section -->
            <div class="pm-card pm-mb-6">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-md pm-m-0">
                        <span class="dashicons dashicons-calendar-alt"></span> <?php _e('Hosting Preferences', 'partyminder'); ?>
                    </h3>
                </div>
                <div class="pm-card-body">
                    <div class="pm-form-group">
                        <label class="pm-label"><?php _e('Favorite Event Types', 'partyminder'); ?></label>
                        <div class="pm-grid pm-grid-2">
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
                            <label class="pm-flex pm-flex-center-gap pm-mb-2">
                                <input type="checkbox" name="favorite_event_types[]" value="<?php echo $key; ?>" 
                                       <?php checked(in_array($key, $selected_types)); ?>>
                                <span><?php echo $label; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="pm-form-row">
                        <div class="pm-form-group">
                            <label class="pm-label" for="dietary_restrictions"><?php _e('Dietary Restrictions/Preferences', 'partyminder'); ?></label>
                            <textarea id="dietary_restrictions" name="dietary_restrictions" rows="3" class="pm-input pm-textarea"
                                      placeholder="Vegetarian, vegan, gluten-free, allergies, etc."><?php echo esc_textarea($profile_data['dietary_restrictions']); ?></textarea>
                        </div>
                        
                        <div class="pm-form-group">
                            <label class="pm-label" for="accessibility_needs"><?php _e('Accessibility Considerations', 'partyminder'); ?></label>
                            <textarea id="accessibility_needs" name="accessibility_needs" rows="3" class="pm-input pm-textarea"
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
            <div class="pm-card pm-mb-6">
                <div class="pm-card-body pm-flex pm-flex-center-gap">
                    <button type="submit" class="pm-button pm-button-primary">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Save Profile', 'partyminder'); ?>
                    </button>
                    
                    <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="pm-button pm-button-secondary">
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php _e('Cancel', 'partyminder'); ?>
                    </a>
                </div>
            </div>
        </form>
        
    <?php else: ?>
        <!-- View Profile -->
        <div class="pm-card pm-mb-6">
            <div class="pm-card-header pm-flex pm-flex-center-gap pm-gap-lg">
                <div class="profile-avatar pm-avatar-lg">
                    <?php if ($profile_data['profile_image']): ?>
                        <img src="<?php echo esc_url($profile_data['profile_image']); ?>" alt="<?php echo esc_attr($profile_data['display_name'] ?: $user_data->display_name); ?>" class="pm-avatar-img">
                    <?php else: ?>
                        <div class="pm-heading pm-heading-lg pm-text-primary pm-m-0">
                            <?php echo strtoupper(substr($profile_data['display_name'] ?: $user_data->display_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h1 class="pm-title-primary pm-m-0"><?php echo esc_html($profile_data['display_name'] ?: $user_data->display_name); ?>
                        <?php if ($profile_data['is_verified']): ?>
                            <span class="pm-badge pm-badge-success" title="<?php _e('Verified Host', 'partyminder'); ?>">✓</span>
                        <?php endif; ?>
                    </h1>
                    
                    <?php if ($profile_data['location']): ?>
                        <div class="pm-meta-item">
                            <span class="dashicons dashicons-location"></span>
                            <span class="pm-text-muted"><?php echo esc_html($profile_data['location']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($profile_data['bio']): ?>
                        <div class="pm-text-muted">
                            <?php echo wp_kses_post(nl2br($profile_data['bio'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($is_own_profile): ?>
                        <div class="pm-mt-4 pm-flex pm-flex-center-gap pm-flex-wrap">
                            <a href="<?php echo esc_url(add_query_arg('edit', '1', PartyMinder::get_profile_url())); ?>" class="pm-button pm-button-primary">
                                <span class="dashicons dashicons-edit"></span>
                                <?php _e('Edit Profile', 'partyminder'); ?>
                            </a>
                            <a href="<?php echo esc_url(PartyMinder::get_logout_url()); ?>" class="pm-button pm-button-secondary">
                                <span>🚪</span>
                                <?php _e('Logout', 'partyminder'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Profile Stats -->
        <div class="pm-card pm-mb-6">
            <div class="pm-card-header">
                <h3 class="pm-title-secondary pm-m-0">Profile Stats</h3>
            </div>
            <div class="pm-card-body">
                <div class="pm-grid pm-grid-3">
                    <div class="pm-stat">
                        <div class="pm-stat-number pm-text-primary"><?php echo intval($profile_data['events_hosted']); ?></div>
                        <div class="pm-stat-label"><?php _e('Events Hosted', 'partyminder'); ?></div>
                    </div>
                    
                    <div class="pm-stat">
                        <div class="pm-stat-number pm-text-success"><?php echo intval($profile_data['events_attended']); ?></div>
                        <div class="pm-stat-label"><?php _e('Events Attended', 'partyminder'); ?></div>
                    </div>
                    
                    <?php if ($profile_data['host_rating'] > 0): ?>
                    <div class="pm-stat">
                        <div class="pm-stat-number pm-text-warning"><?php echo number_format($profile_data['host_rating'], 1); ?> ⭐</div>
                        <div class="pm-stat-label"><?php printf(__('Host Rating (%d reviews)', 'partyminder'), $profile_data['host_reviews_count']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Additional Profile Information -->
        <?php if ($profile_data['favorite_event_types'] || $profile_data['website_url']): ?>
        <div class="pm-card pm-mb-6">
            <div class="pm-card-header">
                <h3 class="pm-title-secondary pm-m-0">Additional Information</h3>
            </div>
            <div class="pm-card-body">
                <?php if ($profile_data['favorite_event_types']): ?>
                <div class="pm-mb-4">
                    <h4 class="pm-heading pm-heading-sm pm-text-primary"><?php _e('Favorite Event Types', 'partyminder'); ?></h4>
                    <div class="pm-flex pm-flex-center-gap pm-flex-wrap">
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
                        <span class="pm-badge pm-badge-secondary"><?php echo esc_html($event_type_labels[$type]); ?></span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($profile_data['website_url']): ?>
                <div>
                    <h4 class="pm-heading pm-heading-sm pm-text-primary pm-mb-2"><?php _e('Website', 'partyminder'); ?></h4>
                    <a href="<?php echo esc_url($profile_data['website_url']); ?>" target="_blank" rel="noopener noreferrer" class="pm-button pm-button-secondary">
                        <?php echo esc_html($profile_data['website_url']); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
    <?php endif; ?>
    
</div>

<script>
// Character counter for bio field
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>