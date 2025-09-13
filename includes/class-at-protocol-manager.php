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
		if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) {
			add_action( 'wp_ajax_partyminder_connect_bluesky', array( $this, 'ajax_connect_bluesky' ) );
			add_action( 'wp_ajax_partyminder_get_bluesky_contacts', array( $this, 'ajax_get_bluesky_contacts' ) );
			add_action( 'wp_ajax_partyminder_disconnect_bluesky', array( $this, 'ajax_disconnect_bluesky' ) );
			add_action( 'wp_ajax_partyminder_check_bluesky_connection', array( $this, 'ajax_check_bluesky_connection' ) );
		}
	}

	/**
	 * Connect user to Bluesky account
	 */
	public function connect_bluesky( $user_id, $handle, $password ) {
		error_log( '[PartyMinder AT Protocol] connect_bluesky called for user: ' . $user_id );

		if ( ! $this->bluesky_client ) {
			$this->bluesky_client = new PartyMinder_Bluesky_Client();
		}

		$auth_result = $this->bluesky_client->authenticate( $handle, $password );
		error_log( '[PartyMinder AT Protocol] Auth result: ' . json_encode( $auth_result ) );

		if ( $auth_result['success'] ) {
			// Store Bluesky credentials securely
			$identity_manager = new PartyMinder_Member_Identity_Manager();
			$identity         = $identity_manager->get_member_identity( $user_id );

			error_log( '[PartyMinder AT Protocol] Member identity exists: ' . ( $identity ? 'yes' : 'no' ) );

			if ( ! $identity ) {
				// Try to create identity if it doesn't exist
				error_log( '[PartyMinder AT Protocol] Creating member identity for user: ' . $user_id );
				$user = get_user_by( 'id', $user_id );
				if ( $user ) {
					$identity_manager->ensure_identity_exists( $user_id, $user->user_email, $user->display_name );
					$identity = $identity_manager->get_member_identity( $user_id );
				}
			}

			if ( $identity ) {
				$at_protocol_data            = $identity->at_protocol_data ?: array();
				$at_protocol_data['bluesky'] = array(
					'handle'        => $handle,
					'did'           => $auth_result['did'],
					'access_token'  => $this->encrypt_token( $auth_result['access_token'] ),
					'refresh_token' => $this->encrypt_token( $auth_result['refresh_token'] ),
					'connected_at'  => current_time( 'mysql' ),
					'last_sync'     => null,
				);

				$result = $identity_manager->update_at_protocol_data( $user_id, $at_protocol_data );
				error_log( '[PartyMinder AT Protocol] Update result: ' . ( $result ? 'success' : 'failed' ) );

				error_log( '[PartyMinder] Connected Bluesky account for user ' . $user_id . ': ' . $handle );
				return array(
					'success' => true,
					'message' => 'Successfully connected to Bluesky',
					'handle'  => $handle,
				);
			} else {
				error_log( '[PartyMinder AT Protocol] Could not create or find member identity' );
				return array(
					'success' => false,
					'message' => 'Could not create member identity',
				);
			}
		}

		return array(
			'success' => false,
			'message' => $auth_result['error'] ?? 'Failed to connect to Bluesky',
		);
	}

	/**
	 * Get user's Bluesky contacts/follows
	 */
	public function get_bluesky_contacts( $user_id ) {
		// First validate the connection and refresh tokens if needed
		$connection_status = $this->validate_bluesky_connection( $user_id );

		if ( ! $connection_status['connected'] ) {
			return array(
				'success' => false,
				'message' => 'Bluesky not connected or tokens invalid',
			);
		}

		$identity_manager = new PartyMinder_Member_Identity_Manager();
		$identity         = $identity_manager->get_member_identity( $user_id );
		$bluesky_data     = $identity->at_protocol_data['bluesky'];

		if ( ! $this->bluesky_client ) {
			$this->bluesky_client = new PartyMinder_Bluesky_Client();
		}

		// Set authentication tokens (they are now guaranteed to be valid)
		$this->bluesky_client->set_tokens(
			$this->decrypt_token( $bluesky_data['access_token'] ),
			$this->decrypt_token( $bluesky_data['refresh_token'] )
		);

		$contacts = $this->bluesky_client->get_follows( $bluesky_data['did'] );

		if ( $contacts['success'] ) {
			// Update last sync time
			$bluesky_data['last_sync']             = current_time( 'mysql' );
			$identity->at_protocol_data['bluesky'] = $bluesky_data;
			$identity_manager->update_at_protocol_data( $user_id, $identity->at_protocol_data );

			return array(
				'success'  => true,
				'contacts' => $contacts['follows'],
			);
		}

		return array(
			'success' => false,
			'message' => $contacts['error'] ?? 'Failed to fetch contacts',
		);
	}

	/**
	 * Disconnect Bluesky account
	 */
	public function disconnect_bluesky( $user_id ) {
		$identity_manager = new PartyMinder_Member_Identity_Manager();
		$identity         = $identity_manager->get_member_identity( $user_id );

		if ( $identity && isset( $identity->at_protocol_data['bluesky'] ) ) {
			unset( $identity->at_protocol_data['bluesky'] );
			$identity_manager->update_at_protocol_data( $user_id, $identity->at_protocol_data );

			error_log( '[PartyMinder] Disconnected Bluesky account for user ' . $user_id );
			return array(
				'success' => true,
				'message' => 'Bluesky account disconnected',
			);
		}

		return array(
			'success' => false,
			'message' => 'No Bluesky account connected',
		);
	}

	/**
	 * Check if user has Bluesky connected
	 */
	public function is_bluesky_connected( $user_id ) {
		$identity_manager = new PartyMinder_Member_Identity_Manager();
		$identity         = $identity_manager->get_member_identity( $user_id );

		return $identity && isset( $identity->at_protocol_data['bluesky'] );
	}

	/**
	 * AJAX handler for connecting Bluesky
	 */
	public function ajax_connect_bluesky() {
		error_log( '[PartyMinder AT Protocol] AJAX connect_bluesky called' );

		try {
			check_ajax_referer( 'partyminder_at_protocol', 'nonce' );
		} catch ( Exception $e ) {
			error_log( '[PartyMinder AT Protocol] Nonce check failed: ' . $e->getMessage() );
			wp_die(
				json_encode(
					array(
						'success' => false,
						'message' => 'Invalid nonce',
					)
				)
			);
		}

		if ( ! is_user_logged_in() ) {
			error_log( '[PartyMinder AT Protocol] User not logged in' );
			wp_die(
				json_encode(
					array(
						'success' => false,
						'message' => 'Not authenticated',
					)
				)
			);
		}

		$handle   = sanitize_text_field( $_POST['handle'] ?? '' );
		$password = $_POST['password'] ?? '';

		error_log( '[PartyMinder AT Protocol] Connecting handle: ' . $handle );

		if ( empty( $handle ) || empty( $password ) ) {
			error_log( '[PartyMinder AT Protocol] Missing handle or password' );
			wp_die(
				json_encode(
					array(
						'success' => false,
						'message' => 'Handle and password required',
					)
				)
			);
		}

		$result = $this->connect_bluesky( get_current_user_id(), $handle, $password );
		error_log( '[PartyMinder AT Protocol] Connect result: ' . json_encode( $result ) );
		
		wp_send_json( $result );
	}

	/**
	 * AJAX handler for getting Bluesky contacts
	 */
	public function ajax_get_bluesky_contacts() {
		check_ajax_referer( 'partyminder_at_protocol', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_die(
				json_encode(
					array(
						'success' => false,
						'message' => 'Not authenticated',
					)
				)
			);
		}

		$result = $this->get_bluesky_contacts( get_current_user_id() );
		wp_die( json_encode( $result ) );
	}

	/**
	 * AJAX handler for disconnecting Bluesky
	 */
	public function ajax_disconnect_bluesky() {
		check_ajax_referer( 'partyminder_at_protocol', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_die(
				json_encode(
					array(
						'success' => false,
						'message' => 'Not authenticated',
					)
				)
			);
		}

		$result = $this->disconnect_bluesky( get_current_user_id() );
		wp_die( json_encode( $result ) );
	}

	/**
	 * AJAX handler for checking Bluesky connection status
	 */
	public function ajax_check_bluesky_connection() {
		check_ajax_referer( 'partyminder_at_protocol', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_die(
				json_encode(
					array(
						'success' => false,
						'message' => 'Not authenticated',
					)
				)
			);
		}

		$user_id           = get_current_user_id();
		$connection_status = $this->validate_bluesky_connection( $user_id );

		wp_die(
			json_encode(
				array(
					'success' => true,
					'data'    => $connection_status,
				)
			)
		);
	}

	/**
	 * Validate Bluesky connection and refresh tokens if needed
	 */
	public function validate_bluesky_connection( $user_id ) {
		error_log( '[PartyMinder AT Protocol] Validating Bluesky connection for user: ' . $user_id );

		$identity_manager = new PartyMinder_Member_Identity_Manager();
		$identity         = $identity_manager->get_member_identity( $user_id );

		// Check if user has Bluesky data stored
		if ( ! $identity || ! isset( $identity->at_protocol_data['bluesky'] ) ) {
			error_log( '[PartyMinder AT Protocol] No Bluesky data found for user: ' . $user_id );
			return array( 'connected' => false );
		}

		$bluesky_data = $identity->at_protocol_data['bluesky'];
		$handle       = $bluesky_data['handle'] ?? 'Unknown';

		// Check if we have access and refresh tokens
		if ( ! isset( $bluesky_data['access_token'] ) || ! isset( $bluesky_data['refresh_token'] ) ) {
			error_log( '[PartyMinder AT Protocol] Missing tokens for user: ' . $user_id );
			return array( 'connected' => false );
		}

		// Initialize Bluesky client and set tokens
		if ( ! $this->bluesky_client ) {
			$this->bluesky_client = new PartyMinder_Bluesky_Client();
		}

		$access_token  = $this->decrypt_token( $bluesky_data['access_token'] );
		$refresh_token = $this->decrypt_token( $bluesky_data['refresh_token'] );

		$this->bluesky_client->set_tokens( $access_token, $refresh_token );

		// Try to make a simple API call to validate the token
		$profile_result = $this->bluesky_client->get_profile( $bluesky_data['did'] );

		if ( $profile_result['success'] ) {
			error_log( '[PartyMinder AT Protocol] Connection valid for user: ' . $user_id );
			return array(
				'connected' => true,
				'handle'    => $handle,
			);
		}

		// If the API call failed, try to refresh the token
		error_log( '[PartyMinder AT Protocol] Access token invalid, attempting refresh for user: ' . $user_id );
		$refresh_result = $this->bluesky_client->refresh_session();

		if ( $refresh_result['success'] ) {
			// Save the new tokens
			$bluesky_data['access_token']  = $this->encrypt_token( $refresh_result['access_token'] );
			$bluesky_data['refresh_token'] = $this->encrypt_token( $refresh_result['refresh_token'] );

			$identity->at_protocol_data['bluesky'] = $bluesky_data;
			$identity_manager->update_at_protocol_data( $user_id, $identity->at_protocol_data );

			error_log( '[PartyMinder AT Protocol] Tokens refreshed successfully for user: ' . $user_id );
			return array(
				'connected' => true,
				'handle'    => $handle,
			);
		}

		// If refresh also failed, the connection is invalid
		error_log( '[PartyMinder AT Protocol] Token refresh failed for user: ' . $user_id . ', error: ' . ( $refresh_result['error'] ?? 'Unknown' ) );
		return array( 'connected' => false );
	}

	/**
	 * Sync member identity to AT Protocol
	 */
	public function sync_member_identity( $user_id ) {
		// TODO: Implement AT Protocol member sync
		error_log( '[PartyMinder] AT Protocol sync for user ' . $user_id . ' - feature coming soon' );
		return false;
	}

	/**
	 * Sync community to AT Protocol
	 */
	public function sync_community( $community_id ) {
		// TODO: Implement AT Protocol community sync
		error_log( '[PartyMinder] AT Protocol sync for community ' . $community_id . ' - feature coming soon' );
		return false;
	}

	/**
	 * Get sync status
	 */
	public function get_sync_status() {
		return array(
			'enabled' => PartyMinder_Feature_Flags::is_at_protocol_enabled(),
			'status'  => 'active',
			'message' => 'AT Protocol integration with Bluesky contacts',
		);
	}

	/**
	 * Encrypt token for storage
	 */
	private function encrypt_token( $token ) {
		if ( function_exists( 'openssl_encrypt' ) ) {
			$key       = wp_salt( 'secure_auth' );
			$cipher    = 'AES-256-CBC';
			$iv        = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $cipher ) );
			$encrypted = openssl_encrypt( $token, $cipher, $key, 0, $iv );
			return base64_encode( $iv . $encrypted );
		}

		// Fallback to base64 encoding (less secure)
		return base64_encode( $token );
	}

	/**
	 * Decrypt token from storage
	 */
	private function decrypt_token( $encrypted_token ) {
		if ( function_exists( 'openssl_decrypt' ) ) {
			$key       = wp_salt( 'secure_auth' );
			$cipher    = 'AES-256-CBC';
			$data      = base64_decode( $encrypted_token );
			$iv_length = openssl_cipher_iv_length( $cipher );
			$iv        = substr( $data, 0, $iv_length );
			$encrypted = substr( $data, $iv_length );
			return openssl_decrypt( $encrypted, $cipher, $key, 0, $iv );
		}

		// Fallback from base64 encoding
		return base64_decode( $encrypted_token );
	}
}
