<?php

/**
 * PartyMinder AT Protocol Manager
 * 
 * Handles AT Protocol synchronization, federation, and Bluesky integration
 */
class PartyMinder_AT_Protocol_Manager {
    
    private $bluesky_client;
    
    public function __construct() {
        // Hook into AT Protocol sync events
        if (PartyMinder_Feature_Flags::is_at_protocol_enabled()) {
            add_action('wp_ajax_partyminder_connect_bluesky', array($this, 'ajax_connect_bluesky'));
            add_action('wp_ajax_partyminder_get_bluesky_contacts', array($this, 'ajax_get_bluesky_contacts'));
            add_action('wp_ajax_partyminder_disconnect_bluesky', array($this, 'ajax_disconnect_bluesky'));
            add_action('wp_ajax_partyminder_check_bluesky_connection', array($this, 'ajax_check_bluesky_connection'));
        }
    }
    
    /**
     * Connect user to Bluesky account
     */
    public function connect_bluesky($user_id, $handle, $password) {
        if (!$this->bluesky_client) {
            $this->bluesky_client = new PartyMinder_Bluesky_Client();
        }
        
        $auth_result = $this->bluesky_client->authenticate($handle, $password);
        
        if ($auth_result['success']) {
            // Store Bluesky credentials securely
            $identity_manager = new PartyMinder_Member_Identity_Manager();
            $identity = $identity_manager->get_member_identity($user_id);
            
            if ($identity) {
                $at_protocol_data = $identity->at_protocol_data ?: array();
                $at_protocol_data['bluesky'] = array(
                    'handle' => $handle,
                    'did' => $auth_result['did'],
                    'access_token' => $this->encrypt_token($auth_result['access_token']),
                    'refresh_token' => $this->encrypt_token($auth_result['refresh_token']),
                    'connected_at' => current_time('mysql'),
                    'last_sync' => null
                );
                
                $identity_manager->update_at_protocol_data($user_id, $at_protocol_data);
                
                error_log('[PartyMinder] Connected Bluesky account for user ' . $user_id . ': ' . $handle);
                return array('success' => true, 'message' => 'Successfully connected to Bluesky');
            }
        }
        
        return array('success' => false, 'message' => $auth_result['error'] ?? 'Failed to connect to Bluesky');
    }
    
    /**
     * Get user's Bluesky contacts/follows
     */
    public function get_bluesky_contacts($user_id) {
        $identity_manager = new PartyMinder_Member_Identity_Manager();
        $identity = $identity_manager->get_member_identity($user_id);
        
        if (!$identity || !isset($identity->at_protocol_data['bluesky'])) {
            return array('success' => false, 'message' => 'Bluesky not connected');
        }
        
        $bluesky_data = $identity->at_protocol_data['bluesky'];
        
        if (!$this->bluesky_client) {
            $this->bluesky_client = new PartyMinder_Bluesky_Client();
        }
        
        // Set authentication tokens
        $this->bluesky_client->set_tokens(
            $this->decrypt_token($bluesky_data['access_token']),
            $this->decrypt_token($bluesky_data['refresh_token'])
        );
        
        $contacts = $this->bluesky_client->get_follows($bluesky_data['did']);
        
        if ($contacts['success']) {
            // Update last sync time
            $bluesky_data['last_sync'] = current_time('mysql');
            $identity->at_protocol_data['bluesky'] = $bluesky_data;
            $identity_manager->update_at_protocol_data($user_id, $identity->at_protocol_data);
            
            return array(
                'success' => true,
                'contacts' => $contacts['follows']
            );
        }
        
        return array('success' => false, 'message' => $contacts['error'] ?? 'Failed to fetch contacts');
    }
    
    /**
     * Disconnect Bluesky account
     */
    public function disconnect_bluesky($user_id) {
        $identity_manager = new PartyMinder_Member_Identity_Manager();
        $identity = $identity_manager->get_member_identity($user_id);
        
        if ($identity && isset($identity->at_protocol_data['bluesky'])) {
            unset($identity->at_protocol_data['bluesky']);
            $identity_manager->update_at_protocol_data($user_id, $identity->at_protocol_data);
            
            error_log('[PartyMinder] Disconnected Bluesky account for user ' . $user_id);
            return array('success' => true, 'message' => 'Bluesky account disconnected');
        }
        
        return array('success' => false, 'message' => 'No Bluesky account connected');
    }
    
    /**
     * Check if user has Bluesky connected
     */
    public function is_bluesky_connected($user_id) {
        $identity_manager = new PartyMinder_Member_Identity_Manager();
        $identity = $identity_manager->get_member_identity($user_id);
        
        return $identity && isset($identity->at_protocol_data['bluesky']);
    }
    
    /**
     * AJAX handler for connecting Bluesky
     */
    public function ajax_connect_bluesky() {
        check_ajax_referer('partyminder_at_protocol', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'Not authenticated')));
        }
        
        $handle = sanitize_text_field($_POST['handle'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($handle) || empty($password)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Handle and password required')));
        }
        
        $result = $this->connect_bluesky(get_current_user_id(), $handle, $password);
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX handler for getting Bluesky contacts
     */
    public function ajax_get_bluesky_contacts() {
        check_ajax_referer('partyminder_at_protocol', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'Not authenticated')));
        }
        
        $result = $this->get_bluesky_contacts(get_current_user_id());
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX handler for disconnecting Bluesky
     */
    public function ajax_disconnect_bluesky() {
        check_ajax_referer('partyminder_at_protocol', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'Not authenticated')));
        }
        
        $result = $this->disconnect_bluesky(get_current_user_id());
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX handler for checking Bluesky connection status
     */
    public function ajax_check_bluesky_connection() {
        check_ajax_referer('partyminder_at_protocol', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'Not authenticated')));
        }
        
        $user_id = get_current_user_id();
        $is_connected = $this->is_bluesky_connected($user_id);
        
        if ($is_connected) {
            $identity_manager = new PartyMinder_Member_Identity_Manager();
            $identity = $identity_manager->get_member_identity($user_id);
            $handle = $identity->at_protocol_data['bluesky']['handle'] ?? 'Unknown';
            
            wp_die(json_encode(array(
                'success' => true,
                'data' => array(
                    'connected' => true,
                    'handle' => $handle
                )
            )));
        } else {
            wp_die(json_encode(array(
                'success' => true,
                'data' => array('connected' => false)
            )));
        }
    }
    
    /**
     * Sync member identity to AT Protocol
     */
    public function sync_member_identity($user_id) {
        // TODO: Implement AT Protocol member sync
        error_log('[PartyMinder] AT Protocol sync for user ' . $user_id . ' - feature coming soon');
        return false;
    }
    
    /**
     * Sync community to AT Protocol
     */
    public function sync_community($community_id) {
        // TODO: Implement AT Protocol community sync
        error_log('[PartyMinder] AT Protocol sync for community ' . $community_id . ' - feature coming soon');
        return false;
    }
    
    /**
     * Get sync status
     */
    public function get_sync_status() {
        return array(
            'enabled' => PartyMinder_Feature_Flags::is_at_protocol_enabled(),
            'status' => 'active',
            'message' => 'AT Protocol integration with Bluesky contacts'
        );
    }
    
    /**
     * Encrypt token for storage
     */
    private function encrypt_token($token) {
        if (function_exists('openssl_encrypt')) {
            $key = wp_salt('secure_auth');
            $cipher = 'AES-256-CBC';
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
            $encrypted = openssl_encrypt($token, $cipher, $key, 0, $iv);
            return base64_encode($iv . $encrypted);
        }
        
        // Fallback to base64 encoding (less secure)
        return base64_encode($token);
    }
    
    /**
     * Decrypt token from storage
     */
    private function decrypt_token($encrypted_token) {
        if (function_exists('openssl_decrypt')) {
            $key = wp_salt('secure_auth');
            $cipher = 'AES-256-CBC';
            $data = base64_decode($encrypted_token);
            $iv_length = openssl_cipher_iv_length($cipher);
            $iv = substr($data, 0, $iv_length);
            $encrypted = substr($data, $iv_length);
            return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        }
        
        // Fallback from base64 encoding
        return base64_decode($encrypted_token);
    }
}