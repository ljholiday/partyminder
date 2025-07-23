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
    echo '<div class="partyminder-success">';
    echo '<h3>' . __('Profile Updated!', 'partyminder') . '</h3>';
    echo '<p>' . __('Your profile has been successfully updated.', 'partyminder') . '</p>';
    echo '</div>';
}

// Show errors if any
if (isset($errors) && !empty($errors)) {
    echo '<div class="partyminder-errors">';
    echo '<h4>' . __('Please fix the following errors:', 'partyminder') . '</h4>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}
?>

<div class="partyminder-profile-container">
    
    <!-- Dashboard Link -->
    <div class="partyminder-breadcrumb">
        <a href="<?php echo esc_url(PartyMinder::get_dashboard_url()); ?>" class="breadcrumb-link">
            🏠 <?php _e('Dashboard', 'partyminder'); ?>
        </a>
        <span class="breadcrumb-separator">→</span>
        <span class="breadcrumb-current"><?php _e('Profile', 'partyminder'); ?></span>
    </div>
    
    <?php if ($is_editing): ?>
        <!-- Edit Profile Form -->
        <div class="profile-header">
            <h2><?php _e('Edit My Profile', 'partyminder'); ?></h2>
            <p><?php _e('Update your information, preferences, and privacy settings.', 'partyminder'); ?></p>
        </div>
        
        <form method="post" class="partyminder-form partyminder-profile-form" enctype="multipart/form-data">
            <?php wp_nonce_field('partyminder_profile_update', 'partyminder_profile_nonce'); ?>
            
            <!-- Basic Information Section -->
            <div class="form-section">
                <h3><span class="dashicons dashicons-admin-users"></span> <?php _e('Basic Information', 'partyminder'); ?></h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="display_name"><?php _e('Display Name', 'partyminder'); ?></label>
                        <input type="text" id="display_name" name="display_name" 
                               value="<?php echo esc_attr($profile_data['display_name'] ?: $user_data->display_name); ?>" 
                               maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label for="location"><?php _e('Location', 'partyminder'); ?></label>
                        <input type="text" id="location" name="location" 
                               value="<?php echo esc_attr($profile_data['location']); ?>" 
                               placeholder="City, State" maxlength="255">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="bio"><?php _e('Bio', 'partyminder'); ?></label>
                    <textarea id="bio" name="bio" rows="4" maxlength="500" 
                              placeholder="Tell others about yourself and your hosting style..."><?php echo esc_textarea($profile_data['bio']); ?></textarea>
                    <small class="character-count">0/500 characters</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="website_url"><?php _e('Website', 'partyminder'); ?></label>
                        <input type="url" id="website_url" name="website_url" 
                               value="<?php echo esc_attr($profile_data['website_url']); ?>" 
                               placeholder="https://yoursite.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_image"><?php _e('Profile Photo', 'partyminder'); ?></label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <small><?php _e('Upload a new profile photo (JPG, PNG, max 2MB)', 'partyminder'); ?></small>
                    </div>
                </div>
            </div>
            
            <!-- Hosting Preferences Section -->
            <div class="form-section">
                <h3><span class="dashicons dashicons-calendar-alt"></span> <?php _e('Hosting Preferences', 'partyminder'); ?></h3>
                
                <div class="form-group">
                    <label><?php _e('Favorite Event Types', 'partyminder'); ?></label>
                    <div class="checkbox-group">
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
                        <label class="checkbox-label">
                            <input type="checkbox" name="favorite_event_types[]" value="<?php echo $key; ?>" 
                                   <?php checked(in_array($key, $selected_types)); ?>>
                            <?php echo $label; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="dietary_restrictions"><?php _e('Dietary Restrictions/Preferences', 'partyminder'); ?></label>
                        <textarea id="dietary_restrictions" name="dietary_restrictions" rows="3" 
                                  placeholder="Vegetarian, vegan, gluten-free, allergies, etc."><?php echo esc_textarea($profile_data['dietary_restrictions']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="accessibility_needs"><?php _e('Accessibility Considerations', 'partyminder'); ?></label>
                        <textarea id="accessibility_needs" name="accessibility_needs" rows="3" 
                                  placeholder="Mobility access, sensory considerations, etc."><?php echo esc_textarea($profile_data['accessibility_needs']); ?></textarea>
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
            <div class="form-actions">
                <button type="submit" class="pm-button pm-button-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e('Save Profile', 'partyminder'); ?>
                </button>
                
                <a href="<?php echo esc_url(PartyMinder::get_profile_url()); ?>" class="pm-button pm-button-secondary">
                    <span class="dashicons dashicons-no-alt"></span>
                    <?php _e('Cancel', 'partyminder'); ?>
                </a>
            </div>
        </form>
        
    <?php else: ?>
        <!-- View Profile -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if ($profile_data['profile_image']): ?>
                    <img src="<?php echo esc_url($profile_data['profile_image']); ?>" alt="<?php echo esc_attr($profile_data['display_name'] ?: $user_data->display_name); ?>">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <?php echo strtoupper(substr($profile_data['display_name'] ?: $user_data->display_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <h1><?php echo esc_html($profile_data['display_name'] ?: $user_data->display_name); ?>
                    <?php if ($profile_data['is_verified']): ?>
                        <span class="verified-badge" title="<?php _e('Verified Host', 'partyminder'); ?>">✓</span>
                    <?php endif; ?>
                </h1>
                
                <?php if ($profile_data['location']): ?>
                    <div class="profile-location">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($profile_data['location']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($profile_data['bio']): ?>
                    <div class="profile-bio">
                        <?php echo wp_kses_post(nl2br($profile_data['bio'])); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_own_profile): ?>
                    <div class="profile-actions">
                        <a href="<?php echo esc_url(add_query_arg('edit', '1', PartyMinder::get_profile_url())); ?>" class="pm-button pm-button-primary">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Edit Profile', 'partyminder'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Profile Stats -->
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo intval($profile_data['events_hosted']); ?></div>
                <div class="stat-label"><?php _e('Events Hosted', 'partyminder'); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo intval($profile_data['events_attended']); ?></div>
                <div class="stat-label"><?php _e('Events Attended', 'partyminder'); ?></div>
            </div>
            
            <?php if ($profile_data['host_rating'] > 0): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($profile_data['host_rating'], 1); ?> ⭐</div>
                <div class="stat-label"><?php printf(__('Host Rating (%d reviews)', 'partyminder'), $profile_data['host_reviews_count']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Additional Profile Information -->
        <?php if ($profile_data['favorite_event_types'] || $profile_data['website_url']): ?>
        <div class="profile-details">
            
            <?php if ($profile_data['favorite_event_types']): ?>
            <div class="detail-section">
                <h3><?php _e('Favorite Event Types', 'partyminder'); ?></h3>
                <div class="event-types">
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
                    <span class="event-type-tag"><?php echo esc_html($event_type_labels[$type]); ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($profile_data['website_url']): ?>
            <div class="detail-section">
                <h3><?php _e('Website', 'partyminder'); ?></h3>
                <a href="<?php echo esc_url($profile_data['website_url']); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html($profile_data['website_url']); ?>
                </a>
            </div>
            <?php endif; ?>
            
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