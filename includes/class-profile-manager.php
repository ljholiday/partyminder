<?php

/**
 * PartyMinder Profile Manager
 * Handles user profile data and operations
 */
class PartyMinder_Profile_Manager {
    
    /**
     * Get user profile data
     */
    public static function get_user_profile($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'partyminder_user_profiles';
        
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $user_id
        ), ARRAY_A);
        
        if (!$profile) {
            // Create default profile if it doesn't exist
            return self::create_default_profile($user_id);
        }
        
        return $profile;
    }
    
    /**
     * Create default profile for new user
     */
    public static function create_default_profile($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'partyminder_user_profiles';
        $user_data = get_userdata($user_id);
        
        $default_data = array(
            'user_id' => $user_id,
            'display_name' => $user_data ? $user_data->display_name : '',
            'bio' => '',
            'location' => '',
            'profile_image' => '',
            'website_url' => '',
            'social_links' => json_encode(array()),
            'hosting_preferences' => json_encode(array()),
            'notification_preferences' => json_encode(array(
                'new_events' => true,
                'event_invitations' => true,
                'rsvp_updates' => true,
                'community_activity' => false
            )),
            'privacy_settings' => json_encode(array(
                'profile_visibility' => 'public'
            )),
            'events_hosted' => 0,
            'events_attended' => 0,
            'host_rating' => 0.00,
            'host_reviews_count' => 0,
            'favorite_event_types' => json_encode(array()),
            'available_times' => json_encode(array()),
            'dietary_restrictions' => '',
            'accessibility_needs' => '',
            'is_verified' => 0,
            'is_active' => 1,
            'last_active' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $default_data);
        
        if ($result) {
            $default_data['id'] = $wpdb->insert_id;
            return $default_data;
        }
        
        return array();
    }
    
    /**
     * Update user profile
     */
    public static function update_profile($user_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'partyminder_user_profiles';
        $errors = array();
        
        // Validate input data
        $update_data = array();
        
        // Display name
        if (isset($data['display_name'])) {
            $display_name = sanitize_text_field($data['display_name']);
            if (strlen($display_name) > 255) {
                $errors[] = __('Display name must be 255 characters or less.', 'partyminder');
            } else {
                $update_data['display_name'] = $display_name;
            }
        }
        
        // Bio
        if (isset($data['bio'])) {
            $bio = sanitize_textarea_field($data['bio']);
            if (strlen($bio) > 500) {
                $errors[] = __('Bio must be 500 characters or less.', 'partyminder');
            } else {
                $update_data['bio'] = $bio;
            }
        }
        
        // Location
        if (isset($data['location'])) {
            $location = sanitize_text_field($data['location']);
            if (strlen($location) > 255) {
                $errors[] = __('Location must be 255 characters or less.', 'partyminder');
            } else {
                $update_data['location'] = $location;
            }
        }
        
        // Website URL
        if (isset($data['website_url'])) {
            $website = esc_url_raw($data['website_url']);
            if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
                $errors[] = __('Please enter a valid website URL.', 'partyminder');
            } else {
                $update_data['website_url'] = $website;
            }
        }
        
        // Dietary restrictions
        if (isset($data['dietary_restrictions'])) {
            $update_data['dietary_restrictions'] = sanitize_textarea_field($data['dietary_restrictions']);
        }
        
        // Accessibility needs
        if (isset($data['accessibility_needs'])) {
            $update_data['accessibility_needs'] = sanitize_textarea_field($data['accessibility_needs']);
        }
        
        // Favorite event types
        if (isset($data['favorite_event_types']) && is_array($data['favorite_event_types'])) {
            $valid_types = array('dinner_party', 'cocktail_party', 'bbq', 'game_night', 'book_club', 'wine_tasting', 'outdoor', 'cultural');
            $selected_types = array_intersect($data['favorite_event_types'], $valid_types);
            $update_data['favorite_event_types'] = json_encode($selected_types);
        }
        
        // Notification preferences
        if (isset($data['notifications']) && is_array($data['notifications'])) {
            $notifications = array();
            $notifications['new_events'] = isset($data['notifications']['new_events']);
            $notifications['event_invitations'] = isset($data['notifications']['event_invitations']);
            $notifications['rsvp_updates'] = isset($data['notifications']['rsvp_updates']);
            $notifications['community_activity'] = isset($data['notifications']['community_activity']);
            $update_data['notification_preferences'] = json_encode($notifications);
        }
        
        // Privacy settings
        if (isset($data['privacy']) && is_array($data['privacy'])) {
            $privacy = array();
            if (isset($data['privacy']['profile_visibility'])) {
                $visibility = sanitize_text_field($data['privacy']['profile_visibility']);
                if (in_array($visibility, array('public', 'community', 'private'))) {
                    $privacy['profile_visibility'] = $visibility;
                } else {
                    $privacy['profile_visibility'] = 'public';
                }
            }
            $update_data['privacy_settings'] = json_encode($privacy);
        }
        
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = self::handle_profile_image_upload($_FILES['profile_image'], $user_id);
            if ($upload_result['success']) {
                $update_data['profile_image'] = $upload_result['url'];
            } else {
                $errors[] = $upload_result['error'];
            }
        }
        
        // Return early if there are validation errors
        if (!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }
        
        // Add updated timestamp
        $update_data['updated_at'] = current_time('mysql');
        
        // Check if profile exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing) {
            // Update existing profile
            $result = $wpdb->update(
                $table_name,
                $update_data,
                array('user_id' => $user_id)
            );
        } else {
            // Create new profile
            $update_data['user_id'] = $user_id;
            $update_data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table_name, $update_data);
        }
        
        if ($result !== false) {
            // Update last active time
            self::update_last_active($user_id);
            
            return array('success' => true);
        } else {
            return array('success' => false, 'errors' => array(__('Failed to update profile. Please try again.', 'partyminder')));
        }
    }
    
    /**
     * Handle profile image upload
     */
    private static function handle_profile_image_upload($file, $user_id) {
        // Check file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            return array('success' => false, 'error' => __('Profile image must be smaller than 2MB.', 'partyminder'));
        }
        
        // Check file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        if (!in_array($file['type'], $allowed_types)) {
            return array('success' => false, 'error' => __('Profile image must be JPG, PNG, or GIF format.', 'partyminder'));
        }
        
        // Set up upload directory
        $upload_dir = wp_upload_dir();
        $partyminder_dir = $upload_dir['basedir'] . '/partyminder/profiles/';
        $partyminder_url = $upload_dir['baseurl'] . '/partyminder/profiles/';
        
        // Create directory if it doesn't exist
        if (!file_exists($partyminder_dir)) {
            wp_mkdir_p($partyminder_dir);
        }
        
        // Generate unique filename
        $file_info = pathinfo($file['name']);
        $filename = 'user-' . $user_id . '-' . time() . '.' . $file_info['extension'];
        $file_path = $partyminder_dir . $filename;
        $file_url = $partyminder_url . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return array('success' => true, 'url' => $file_url, 'path' => $file_path);
        } else {
            return array('success' => false, 'error' => __('Failed to upload profile image. Please try again.', 'partyminder'));
        }
    }
    
    /**
     * Update last active time
     */
    public static function update_last_active($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'partyminder_user_profiles';
        
        $wpdb->update(
            $table_name,
            array('last_active' => current_time('mysql')),
            array('user_id' => $user_id)
        );
    }
    
    /**
     * Increment events hosted count
     */
    public static function increment_events_hosted($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'partyminder_user_profiles';
        
        // Ensure profile exists
        self::get_user_profile($user_id);
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET events_hosted = events_hosted + 1, updated_at = %s WHERE user_id = %d",
            current_time('mysql'),
            $user_id
        ));
    }
    
    /**
     * Increment events attended count
     */
    public static function increment_events_attended($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'partyminder_user_profiles';
        
        // Ensure profile exists
        self::get_user_profile($user_id);
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET events_attended = events_attended + 1, updated_at = %s WHERE user_id = %d",
            current_time('mysql'),
            $user_id
        ));
    }
    
    /**
     * Update host rating
     */
    public static function update_host_rating($user_id, $rating, $review_count = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'partyminder_user_profiles';
        
        // Ensure profile exists
        self::get_user_profile($user_id);
        
        $update_data = array(
            'host_rating' => floatval($rating),
            'updated_at' => current_time('mysql')
        );
        
        if ($review_count !== null) {
            $update_data['host_reviews_count'] = intval($review_count);
        }
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('user_id' => $user_id)
        );
    }
    
    /**
     * Get profiles by visibility setting
     */
    public static function get_public_profiles($limit = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'partyminder_user_profiles';
        
        $profiles = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.user_login, u.user_email 
             FROM $table_name p 
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
             WHERE p.is_active = 1 
             AND JSON_EXTRACT(p.privacy_settings, '$.profile_visibility') = 'public'
             ORDER BY p.last_active DESC 
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A);
        
        return $profiles;
    }
    
    /**
     * Search profiles
     */
    public static function search_profiles($search_term, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'partyminder_user_profiles';
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        $profiles = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.user_login, u.user_email 
             FROM $table_name p 
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
             WHERE p.is_active = 1 
             AND JSON_EXTRACT(p.privacy_settings, '$.profile_visibility') = 'public'
             AND (p.display_name LIKE %s OR p.bio LIKE %s OR p.location LIKE %s)
             ORDER BY p.display_name ASC 
             LIMIT %d",
            $search_term,
            $search_term,
            $search_term,
            $limit
        ), ARRAY_A);
        
        return $profiles;
    }
    
    /**
     * Check if user can view profile
     */
    public static function can_view_profile($profile_user_id, $viewing_user_id = null) {
        if (!$viewing_user_id) {
            $viewing_user_id = get_current_user_id();
        }
        
        // Users can always view their own profile
        if ($profile_user_id == $viewing_user_id) {
            return true;
        }
        
        $profile = self::get_user_profile($profile_user_id);
        $privacy_settings = json_decode($profile['privacy_settings'] ?: '{}', true);
        $visibility = $privacy_settings['profile_visibility'] ?? 'public';
        
        switch ($visibility) {
            case 'public':
                return true;
            
            case 'community':
                // Check if users share any communities (if communities feature is enabled)
                if (class_exists('PartyMinder_Community_Manager')) {
                    return PartyMinder_Community_Manager::users_share_community($profile_user_id, $viewing_user_id);
                }
                return false;
            
            case 'private':
                return false;
            
            default:
                return true;
        }
    }
    
    /**
     * Get profile URL for user
     */
    public static function get_profile_url($user_id) {
        if ($user_id == get_current_user_id()) {
            return PartyMinder::get_profile_url();
        } else {
            return PartyMinder::get_profile_url($user_id);
        }
    }
    
    /**
     * Get user display name with fallback
     */
    public static function get_display_name($user_id) {
        $profile = self::get_user_profile($user_id);
        if ($profile['display_name']) {
            return $profile['display_name'];
        }
        
        $user_data = get_userdata($user_id);
        return $user_data ? $user_data->display_name : __('Unknown User', 'partyminder');
    }
}