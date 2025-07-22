<?php

/**
 * PartyMinder Community Manager
 * 
 * Handles community creation, membership, and management
 * Follows the same patterns as Event Manager and Conversation Manager
 */
class PartyMinder_Community_Manager {
    
    public function __construct() {
        // No WordPress hooks needed - pure custom table system
    }
    
    /**
     * Create a new community
     */
    public function create_community($community_data) {
        global $wpdb;
        
        // Check if communities are enabled
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            return new WP_Error('feature_disabled', __('Communities feature is not enabled', 'partyminder'));
        }
        
        // Check user permissions
        if (!PartyMinder_Feature_Flags::can_user_create_community()) {
            return new WP_Error('permission_denied', __('You cannot create communities', 'partyminder'));
        }
        
        // Validate required fields
        if (empty($community_data['name'])) {
            return new WP_Error('missing_data', __('Community name is required', 'partyminder'));
        }
        
        $current_user = wp_get_current_user();
        if (!$current_user->ID) {
            return new WP_Error('user_required', __('You must be logged in to create a community', 'partyminder'));
        }
        
        // Generate unique slug
        $slug = $this->generate_unique_slug($community_data['name']);
        
        // Generate AT Protocol DID if AT Protocol is enabled
        $at_protocol_did = '';
        if (PartyMinder_Feature_Flags::is_at_protocol_enabled()) {
            $at_protocol_did = $this->generate_community_did($slug);
        }
        
        // Insert community data
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        $result = $wpdb->insert(
            $communities_table,
            array(
                'name' => sanitize_text_field($community_data['name']),
                'slug' => $slug,
                'description' => wp_kses_post($community_data['description'] ?? ''),
                'type' => sanitize_text_field($community_data['type'] ?? 'standard'),
                'privacy' => sanitize_text_field($community_data['privacy'] ?? 'public'),
                'creator_id' => $current_user->ID,
                'creator_email' => $current_user->user_email,
                'settings' => wp_json_encode($community_data['settings'] ?? array()),
                'at_protocol_did' => $at_protocol_did,
                'requires_approval' => (bool) ($community_data['requires_approval'] ?? PartyMinder_Feature_Flags::communities_require_approval()),
                'is_active' => 1,
                'member_count' => 1, // Creator is the first member
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            $error_msg = $wpdb->last_error ? $wpdb->last_error : __('Failed to create community', 'partyminder');
            error_log('PartyMinder Community Creation Error: ' . $error_msg);
            error_log('PartyMinder Community Data: ' . print_r($community_data, true));
            return new WP_Error('creation_failed', $error_msg);
        }
        
        $community_id = $wpdb->insert_id;
        
        // Add creator as admin member
        $member_result = $this->add_member($community_id, array(
            'user_id' => $current_user->ID,
            'email' => $current_user->user_email,
            'display_name' => $current_user->display_name,
            'role' => 'admin',
            'status' => 'active'
        ), true); // Skip permission checks for creator
        
        if (is_wp_error($member_result)) {
            error_log('PartyMinder: Failed to add creator as member: ' . $member_result->get_error_message());
            // Don't fail community creation if member addition fails, just log it
        }
        
        // Generate member DID if AT Protocol is enabled
        if (PartyMinder_Feature_Flags::is_at_protocol_enabled()) {
            try {
                $this->ensure_member_has_did($current_user->ID, $current_user->user_email);
            } catch (Exception $e) {
                error_log('PartyMinder: Failed to generate member DID: ' . $e->getMessage());
                // Don't fail community creation if DID generation fails
            }
        }
        
        return $community_id;
    }
    
    /**
     * Get community by ID
     */
    public function get_community($community_id, $include_stats = false) {
        global $wpdb;
        
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        $community = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $communities_table WHERE id = %d AND is_active = 1",
            $community_id
        ));
        
        if (!$community) {
            return null;
        }
        
        // Parse JSON settings
        $community->settings = json_decode($community->settings ?: '{}', true);
        
        if ($include_stats) {
            $community->stats = $this->get_community_stats($community_id);
        }
        
        return $community;
    }
    
    /**
     * Get community by slug
     */
    public function get_community_by_slug($slug) {
        global $wpdb;
        
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        $community = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $communities_table WHERE slug = %s AND is_active = 1",
            $slug
        ));
        
        if (!$community) {
            return null;
        }
        
        // Parse JSON settings
        $community->settings = json_decode($community->settings ?: '{}', true);
        
        return $community;
    }
    
    /**
     * Get communities for a user
     */
    public function get_user_communities($user_id, $limit = 20) {
        global $wpdb;
        
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        $members_table = $wpdb->prefix . 'partyminder_community_members';
        
        $communities = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.role, m.joined_at, m.status as member_status
             FROM $communities_table c
             INNER JOIN $members_table m ON c.id = m.community_id
             WHERE m.user_id = %d AND m.status = 'active' AND c.is_active = 1
             ORDER BY m.joined_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $communities ?: array();
    }
    
    /**
     * Get public communities
     */
    public function get_public_communities($limit = 20, $offset = 0) {
        global $wpdb;
        
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        
        $communities = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $communities_table 
             WHERE privacy = 'public' AND is_active = 1
             ORDER BY member_count DESC, created_at DESC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
        
        return $communities ?: array();
    }
    
    /**
     * Add member to community
     */
    public function add_member($community_id, $member_data, $skip_permission_check = false) {
        global $wpdb;
        
        if (!$skip_permission_check) {
            // Check if communities are enabled
            if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
                return new WP_Error('feature_disabled', __('Communities feature is not enabled', 'partyminder'));
            }
            
            // Check if user can join communities
            if (!PartyMinder_Feature_Flags::can_user_join_community($member_data['user_id'])) {
                return new WP_Error('permission_denied', __('You cannot join communities', 'partyminder'));
            }
        }
        
        // Get community
        $community = $this->get_community($community_id);
        if (!$community) {
            return new WP_Error('community_not_found', __('Community not found', 'partyminder'));
        }
        
        // Check if already a member
        $members_table = $wpdb->prefix . 'partyminder_community_members';
        $existing_member = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $members_table WHERE community_id = %d AND user_id = %d",
            $community_id,
            $member_data['user_id']
        ));
        
        if ($existing_member) {
            if ($existing_member->status === 'active') {
                return new WP_Error('already_member', __('User is already a member', 'partyminder'));
            } else {
                // Reactivate existing membership
                $wpdb->update(
                    $members_table,
                    array('status' => 'active', 'joined_at' => current_time('mysql')),
                    array('id' => $existing_member->id),
                    array('%s', '%s'),
                    array('%d')
                );
                return $existing_member->id;
            }
        }
        
        // Generate member DID if AT Protocol is enabled
        $member_did = '';
        if (PartyMinder_Feature_Flags::is_at_protocol_enabled()) {
            $member_did = $this->ensure_member_has_did($member_data['user_id'], $member_data['email']);
        }
        
        // Insert member
        $result = $wpdb->insert(
            $members_table,
            array(
                'community_id' => $community_id,
                'user_id' => $member_data['user_id'],
                'email' => sanitize_email($member_data['email']),
                'display_name' => sanitize_text_field($member_data['display_name']),
                'role' => sanitize_text_field($member_data['role'] ?? 'member'),
                'permissions' => wp_json_encode($member_data['permissions'] ?? array()),
                'status' => sanitize_text_field($member_data['status'] ?? 'active'),
                'at_protocol_did' => $member_did,
                'joined_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            $error_msg = $wpdb->last_error ? $wpdb->last_error : __('Failed to add member', 'partyminder');
            return new WP_Error('add_member_failed', $error_msg);
        }
        
        // Update member count
        $this->update_member_count($community_id);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get community stats
     */
    public function get_community_stats($community_id) {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'partyminder_community_members';
        $events_table = $wpdb->prefix . 'partyminder_community_events';
        
        // Get member count
        $member_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE community_id = %d AND status = 'active'",
            $community_id
        ));
        
        // Get event count
        $event_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $events_table WHERE community_id = %d",
            $community_id
        ));
        
        // Get recent activity count (last 30 days)
        $recent_activity = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
             WHERE community_id = %d AND last_seen_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $community_id
        ));
        
        return (object) array(
            'member_count' => (int) $member_count,
            'event_count' => (int) $event_count,
            'recent_activity' => (int) $recent_activity
        );
    }
    
    /**
     * Generate unique slug for community
     */
    private function generate_unique_slug($name) {
        global $wpdb;
        
        $base_slug = sanitize_title($name);
        $slug = $base_slug;
        $counter = 1;
        
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $communities_table WHERE slug = %s", $slug))) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Generate AT Protocol DID for community
     */
    private function generate_community_did($slug) {
        // Generate a deterministic DID based on the community slug
        // Format: did:partyminder:community:{hash}
        $hash = substr(md5('community:' . $slug . ':' . time()), 0, 16);
        return 'did:partyminder:community:' . $hash;
    }
    
    /**
     * Ensure member has AT Protocol DID
     */
    private function ensure_member_has_did($user_id, $email) {
        global $wpdb;
        
        $identities_table = $wpdb->prefix . 'partyminder_member_identities';
        
        // Check if user already has a DID
        $existing_identity = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $identities_table WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing_identity) {
            return $existing_identity->at_protocol_did;
        }
        
        // Generate new DID for user
        $user_hash = substr(md5('user:' . $user_id . ':' . $email . ':' . time()), 0, 16);
        $did = 'did:partyminder:user:' . $user_hash;
        
        // Create identity record
        $user = get_user_by('id', $user_id);
        $display_name = $user ? $user->display_name : 'User';
        
        $wpdb->insert(
            $identities_table,
            array(
                'user_id' => $user_id,
                'email' => $email,
                'display_name' => $display_name,
                'at_protocol_did' => $did,
                'is_verified' => 0,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s')
        );
        
        return $did;
    }
    
    /**
     * Update community member count
     */
    private function update_member_count($community_id) {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'partyminder_community_members';
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        
        $member_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE community_id = %d AND status = 'active'",
            $community_id
        ));
        
        $wpdb->update(
            $communities_table,
            array('member_count' => $member_count),
            array('id' => $community_id),
            array('%d'),
            array('%d')
        );
    }
    
    /**
     * Check if user is member of community
     */
    public function is_member($community_id, $user_id) {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'partyminder_community_members';
        
        $member = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $members_table WHERE community_id = %d AND user_id = %d AND status = 'active'",
            $community_id,
            $user_id
        ));
        
        return $member !== null;
    }
    
    /**
     * Get member role in community
     */
    public function get_member_role($community_id, $user_id) {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'partyminder_community_members';
        
        $role = $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM $members_table WHERE community_id = %d AND user_id = %d AND status = 'active'",
            $community_id,
            $user_id
        ));
        
        return $role ?: null;
    }
}