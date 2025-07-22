<?php

/**
 * PartyMinder Feature Flags
 * 
 * Safe deployment system for new features
 */
class PartyMinder_Feature_Flags {
    
    /**
     * Check if communities feature is enabled
     */
    public static function is_communities_enabled() {
        return (bool) get_option('partyminder_enable_communities', false);
    }
    
    /**
     * Check if AT Protocol feature is enabled
     */
    public static function is_at_protocol_enabled() {
        return (bool) get_option('partyminder_enable_at_protocol', false);
    }
    
    /**
     * Check if communities require approval
     */
    public static function communities_require_approval() {
        return (bool) get_option('partyminder_communities_require_approval', true);
    }
    
    /**
     * Get max communities per user
     */
    public static function get_max_communities_per_user() {
        return (int) get_option('partyminder_max_communities_per_user', 10);
    }
    
    /**
     * Check if user can create communities
     */
    public static function can_user_create_community($user_id = null) {
        if (!self::is_communities_enabled()) {
            return false;
        }
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false; // Guest users cannot create communities
        }
        
        // Check if user has reached their limit
        global $wpdb;
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        $user_community_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $communities_table WHERE creator_id = %d AND is_active = 1",
            $user_id
        ));
        
        return $user_community_count < self::get_max_communities_per_user();
    }
    
    /**
     * Check if user can join communities
     */
    public static function can_user_join_community($user_id = null) {
        if (!self::is_communities_enabled()) {
            return false;
        }
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return $user_id > 0; // Must be registered user
    }
    
    /**
     * Check if communities feature should show in admin
     */
    public static function show_communities_in_admin() {
        return current_user_can('manage_options');
    }
    
    /**
     * Check if AT Protocol features should show in admin
     */
    public static function show_at_protocol_in_admin() {
        return current_user_can('manage_options') && self::is_at_protocol_enabled();
    }
    
    /**
     * Get feature status for JavaScript
     */
    public static function get_feature_status_for_js() {
        return array(
            'communities_enabled' => self::is_communities_enabled(),
            'at_protocol_enabled' => self::is_at_protocol_enabled(),
            'can_create_community' => self::can_user_create_community(),
            'can_join_community' => self::can_user_join_community(),
            'max_communities_per_user' => self::get_max_communities_per_user()
        );
    }
    
    /**
     * Enable communities feature (admin only)
     */
    public static function enable_communities() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        update_option('partyminder_enable_communities', true);
        
        // Log the feature activation
        error_log('[PartyMinder] Communities feature enabled by user ID: ' . get_current_user_id());
        
        return true;
    }
    
    /**
     * Disable communities feature (admin only)
     */
    public static function disable_communities() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        update_option('partyminder_enable_communities', false);
        
        // Log the feature deactivation
        error_log('[PartyMinder] Communities feature disabled by user ID: ' . get_current_user_id());
        
        return true;
    }
    
    /**
     * Enable AT Protocol feature (admin only)
     */
    public static function enable_at_protocol() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        update_option('partyminder_enable_at_protocol', true);
        
        // Log the feature activation
        error_log('[PartyMinder] AT Protocol feature enabled by user ID: ' . get_current_user_id());
        
        return true;
    }
    
    /**
     * Disable AT Protocol feature (admin only)
     */
    public static function disable_at_protocol() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        update_option('partyminder_enable_at_protocol', false);
        
        // Log the feature deactivation
        error_log('[PartyMinder] AT Protocol feature disabled by user ID: ' . get_current_user_id());
        
        return true;
    }
    
    /**
     * Get all feature flags for debugging
     */
    public static function get_all_flags() {
        if (!current_user_can('manage_options')) {
            return array();
        }
        
        return array(
            'communities_enabled' => self::is_communities_enabled(),
            'at_protocol_enabled' => self::is_at_protocol_enabled(),
            'communities_require_approval' => self::communities_require_approval(),
            'max_communities_per_user' => self::get_max_communities_per_user()
        );
    }
}