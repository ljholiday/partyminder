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
			add_action( 'wp_ajax_partyminder_send_bluesky_invitations', array( $this, 'ajax_send_bluesky_invitations' ) );
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
		wp_send_json( $result );
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
		wp_send_json( $result );
	}

	/**
	 * AJAX handler for checking Bluesky connection status
	 */
	public function ajax_check_bluesky_connection() {
		check_ajax_referer( 'partyminder_at_protocol', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Not authenticated' );
		}

		$user_id           = get_current_user_id();
		$connection_status = $this->validate_bluesky_connection( $user_id );

		wp_send_json_success( $connection_status );
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

	/**
	 * AJAX handler for sending Bluesky invitations
	 */
	public function ajax_send_bluesky_invitations() {
		error_log( "[PartyMinder Bluesky] AJAX handler called" );

		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'partyminder_at_protocol' ) ) {
			error_log( "[PartyMinder Bluesky] Nonce verification failed" );
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'partyminder' ) ) );
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in to send invitations.', 'partyminder' ) ) );
		}

		// Get request data
		$event_id = intval( $_POST['event_id'] ?? 0 );
		$community_id = intval( $_POST['community_id'] ?? 0 );
		$followers = $_POST['followers'] ?? array();
		$message = sanitize_textarea_field( $_POST['message'] ?? '' );

		error_log( "[PartyMinder Bluesky] Event ID: $event_id, Community ID: $community_id" );
		error_log( "[PartyMinder Bluesky] Followers count: " . count($followers) );

		// Require either event_id or community_id
		if ( ! $event_id && ! $community_id ) {
			error_log( "[PartyMinder Bluesky] No event or community ID provided" );
			wp_send_json_error( array( 'message' => __( 'Event or Community ID is required.', 'partyminder' ) ) );
		}

		if ( empty( $followers ) || ! is_array( $followers ) ) {
			error_log( "[PartyMinder Bluesky] No followers provided" );
			wp_send_json_error( array( 'message' => __( 'No followers selected.', 'partyminder' ) ) );
		}

		$current_user = wp_get_current_user();

		// Handle event invitations
		if ( $event_id ) {
			error_log( "[PartyMinder Bluesky] Processing event invitation" );
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
			$event_manager = new PartyMinder_Event_Manager();
			$event = $event_manager->get_event( $event_id );

			if ( ! $event ) {
				error_log( "[PartyMinder Bluesky] Event not found" );
				wp_send_json_error( array( 'message' => __( 'Event not found.', 'partyminder' ) ) );
			}

			if ( $event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
				error_log( "[PartyMinder Bluesky] Event permission denied" );
				wp_send_json_error( array( 'message' => __( 'You do not have permission to send invitations for this event.', 'partyminder' ) ) );
			}
		}

		// Handle community invitations
		if ( $community_id ) {
			error_log( "[PartyMinder Bluesky] Processing community invitation" );
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
			$community_manager = new PartyMinder_Community_Manager();
			$community = $community_manager->get_community( $community_id );

			if ( ! $community ) {
				error_log( "[PartyMinder Bluesky] Community not found" );
				wp_send_json_error( array( 'message' => __( 'Community not found.', 'partyminder' ) ) );
			}

			$user_role = $community_manager->get_member_role( $community_id, $current_user->ID );
			if ( $user_role !== 'admin' ) {
				error_log( "[PartyMinder Bluesky] Community permission denied - user role: " . $user_role );
				wp_send_json_error( array( 'message' => __( 'Only community admins can send invitations.', 'partyminder' ) ) );
			}
		}

		// Check if user is connected to Bluesky
		error_log( "[PartyMinder Bluesky] Checking Bluesky connection" );
		$identity_manager = new PartyMinder_Member_Identity_Manager();
		$identity = $identity_manager->get_member_identity( $current_user->ID );
		
		if ( ! $identity || ! isset( $identity->at_protocol_data['bluesky'] ) ) {
			error_log( "[PartyMinder Bluesky] Not connected to Bluesky" );
			wp_send_json_error( array( 'message' => __( 'You must be connected to Bluesky to send invitations.', 'partyminder' ) ) );
		}
		
		error_log( "[PartyMinder Bluesky] All checks passed, starting invitation loop" );

		$sent_count = 0;
		$failed_count = 0;
		$duplicate_count = 0;
		$failed_handles = array();
		$duplicate_handles = array();

		// Send invitation to each selected follower
		foreach ( $followers as $follower ) {
			$handle = sanitize_text_field( $follower['handle'] ?? '' );
			$display_name = sanitize_text_field( $follower['display_name'] ?? $handle );

			if ( empty( $handle ) ) {
				$failed_count++;
				continue;
			}

			// Check if invitation already exists before trying to create
			global $wpdb;
			$existing_invitation = null;

			if ( $event_id ) {
				$invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
				$existing_invitation = $wpdb->get_row( $wpdb->prepare(
					"SELECT id FROM {$invitations_table} WHERE event_id = %d AND invited_email = %s AND status = 'sent'",
					$event_id,
					$handle . '@bsky.social'
				) );
			} elseif ( $community_id ) {
				$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';
				$existing_invitation = $wpdb->get_row( $wpdb->prepare(
					"SELECT id FROM {$invitations_table} WHERE community_id = %d AND invited_email = %s AND status = 'pending'",
					$community_id,
					$handle . '@bsky.social'
				) );
			}

			if ( $existing_invitation ) {
				$duplicate_count++;
				$duplicate_handles[] = $handle;
				continue;
			}

			// Create invitation record in database
			$result = $this->create_bluesky_invitation( $event_id, $community_id, $handle, $display_name, $message );

			if ( $result ) {
				$sent_count++;
			} else {
				$failed_count++;
				$failed_handles[] = $handle;
			}
		}

		// Prepare response message
		$messages = array();
		
		if ( $sent_count > 0 ) {
			$messages[] = sprintf( 
				_n( 'Successfully sent %d invitation.', 'Successfully sent %d invitations.', $sent_count, 'partyminder' ),
				$sent_count
			);
		}
		
		if ( $duplicate_count > 0 ) {
			$messages[] = sprintf( 
				_n( '%d invitation was already sent.', '%d invitations were already sent.', $duplicate_count, 'partyminder' ),
				$duplicate_count
			);
		}
		
		if ( $failed_count > 0 ) {
			$messages[] = sprintf( 
				_n( '%d invitation failed.', '%d invitations failed.', $failed_count, 'partyminder' ),
				$failed_count
			);
		}

		if ( $sent_count > 0 || $duplicate_count > 0 ) {
			wp_send_json_success( array(
				'message' => implode( ' ', $messages ),
				'sent_count' => $sent_count,
				'duplicate_count' => $duplicate_count,
				'failed_count' => $failed_count
			) );
		} else {
			wp_send_json_error( array( 
				'message' => __( 'Failed to send invitations.', 'partyminder' ) 
			) );
		}
	}

	/**
	 * Create a Bluesky invitation record
	 */
	private function create_bluesky_invitation( $event_id, $community_id, $handle, $display_name, $message ) {
		global $wpdb;

		// Generate invitation token
		$invitation_token = wp_generate_password( 32, false );
		$current_user_id = get_current_user_id();

		// Handle event invitations
		if ( $event_id ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
			$event_manager = new PartyMinder_Event_Manager();
			$event = $event_manager->get_event( $event_id );

			if ( ! $event ) {
				return false;
			}

			$invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
			$result = $wpdb->insert(
				$invitations_table,
				array(
					'event_id' => $event_id,
					'invited_by_user_id' => $current_user_id,
					'invited_email' => $handle . '@bsky.social',
					'invitation_token' => $invitation_token,
					'status' => 'sent',
					'expires_at' => date( 'Y-m-d H:i:s', strtotime( '+30 days' ) ),
					'custom_message' => $message
				),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			// Send the actual Bluesky invitation post
			$event_manager = new PartyMinder_Event_Manager();
			$event = $event_manager->get_event( $event_id );
			
			if ( $event ) {
				// Get user's Bluesky connection
				$identity_manager = new PartyMinder_Member_Identity_Manager();
				$identity = $identity_manager->get_member_identity( get_current_user_id() );
				
				if ( ! $identity || ! isset( $identity->at_protocol_data['bluesky'] ) ) {
					return true; // Still return true since DB insert worked
				}
				
				$bluesky_data = $identity->at_protocol_data['bluesky'];
				
				// Initialize and authenticate Bluesky client
				if ( ! $this->bluesky_client ) {
					$this->bluesky_client = new PartyMinder_Bluesky_Client();
				}
				
				$this->bluesky_client->set_tokens(
					$this->decrypt_token( $bluesky_data['access_token'] ),
					$this->decrypt_token( $bluesky_data['refresh_token'] )
				);
				
				// Set the user's DID for post creation
				$this->bluesky_client->set_did( $bluesky_data['did'] );

				// Create RSVP invitation URL
				$invitation_url = add_query_arg(
					array( 'token' => $invitation_token ),
					home_url( '/events/' . $event->slug )
				);

				// Create invitation post
				$post_text = "Hey @{$handle}! You're invited to \"{$event->title}\" on " . date( 'M j, Y', strtotime( $event->event_date ) );
				if ( $message ) {
					$post_text .= "\n\n" . $message;
				}
				$post_text .= "\n\nRSVP here: " . $invitation_url;
				
				error_log( "[PartyMinder Bluesky] About to create post: " . $post_text );
				
				$post_result = $this->bluesky_client->create_post( $post_text, array( $handle ) );
				
				error_log( "[PartyMinder Bluesky] Post result: " . json_encode($post_result) );
				
				if ( $post_result['success'] ) {
					error_log( "[PartyMinder Bluesky] Invitation post sent successfully to {$handle}" );
					error_log( "[PartyMinder Bluesky] Post URI: " . $post_result['uri'] );
				} else {
					error_log( "[PartyMinder Bluesky] Failed to send invitation post to {$handle}: " . $post_result['error'] );
				}
			}
			
			return true;
		}
		}

		// Handle community invitations
		if ( $community_id ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
			$community_manager = new PartyMinder_Community_Manager();
			$community = $community_manager->get_community( $community_id );

			if ( ! $community ) {
				return false;
			}

			$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';
			$result = $wpdb->insert(
				$invitations_table,
				array(
					'community_id' => $community_id,
					'invited_by_member_id' => $current_user_id,
					'invited_email' => $handle . '@bsky.social',
					'invitation_token' => $invitation_token,
					'status' => 'pending',
					'expires_at' => date( 'Y-m-d H:i:s', strtotime( '+30 days' ) ),
					'message' => $message
				),
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( $result ) {
				// Send the actual Bluesky invitation post
				$identity_manager = new PartyMinder_Member_Identity_Manager();
				$identity = $identity_manager->get_member_identity( $current_user_id );

				if ( ! $identity || ! isset( $identity->at_protocol_data['bluesky'] ) ) {
					return true; // Still return true since DB insert worked
				}

				$bluesky_data = $identity->at_protocol_data['bluesky'];

				// Initialize and authenticate Bluesky client
				if ( ! $this->bluesky_client ) {
					$this->bluesky_client = new PartyMinder_Bluesky_Client();
				}

				$this->bluesky_client->set_tokens(
					$this->decrypt_token( $bluesky_data['access_token'] ),
					$this->decrypt_token( $bluesky_data['refresh_token'] )
				);

				$this->bluesky_client->set_did( $bluesky_data['did'] );

				// Create community invitation post
				$invitation_url = home_url( '/communities/join?token=' . $invitation_token );
				$post_text = "Hey @{$handle}! You're invited to join the \"{$community->name}\" community";
				if ( $message ) {
					$post_text .= "\n\n" . $message;
				}
				$post_text .= "\n\nJoin here: {$invitation_url}";

				$post_result = $this->bluesky_client->create_post( $post_text, array( $handle ) );

				if ( $post_result['success'] ) {
					error_log( "[PartyMinder Bluesky] Community invitation post sent successfully to {$handle}" );
				} else {
					error_log( "[PartyMinder Bluesky] Failed to send community invitation post to {$handle}: " . $post_result['error'] );
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Store pending invitations in user meta for processing after event creation
	 */
	private function store_pending_invitations( $user_id, $followers, $message = '' ) {
		$pending_data = array(
			'followers' => $followers,
			'message' => $message,
			'timestamp' => time()
		);
		
		update_user_meta( $user_id, 'partyminder_pending_invitations', $pending_data );
	}

	/**
	 * Process pending invitations after event creation
	 */
	public function process_pending_invitations( $user_id, $event_id ) {
		$pending_data = get_user_meta( $user_id, 'partyminder_pending_invitations', true );
		
		if ( ! $pending_data || empty( $pending_data['followers'] ) ) {
			return false;
		}

		// Clean up pending data first
		delete_user_meta( $user_id, 'partyminder_pending_invitations' );
		
		// Process the invitations
		$followers = $pending_data['followers'];
		$message = $pending_data['message'] ?? '';
		
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
		$event_manager = new PartyMinder_Event_Manager();
		$event = $event_manager->get_event( $event_id );
		
		if ( ! $event || $event->author_id != $user_id ) {
			return false;
		}

		$identity_manager = new PartyMinder_Member_Identity_Manager();
		$identity = $identity_manager->get_member_identity( $user_id );
		
		if ( ! $identity || ! isset( $identity->at_protocol_data['bluesky'] ) ) {
			return false;
		}

		$sent_count = 0;
		$failed_count = 0;

		// Send invitation to each follower
		foreach ( $followers as $follower ) {
			if ( ! isset( $follower['handle'] ) ) {
				$failed_count++;
				continue;
			}

			$handle = sanitize_text_field( $follower['handle'] );
			$display_name = sanitize_text_field( $follower['display_name'] ?? $handle );

			// Create invitation record
			global $wpdb;
			$invitations_table = $wpdb->prefix . 'partyminder_invitations';
			
			$invitation_data = array(
				'event_id' => $event_id,
				'inviter_user_id' => $user_id,
				'invitee_identifier' => $handle,
				'invitee_type' => 'bluesky',
				'invitation_method' => 'bluesky',
				'status' => 'sent',
				'custom_message' => $message,
				'invitation_token' => wp_generate_password( 32, false ),
				'created_at' => current_time( 'mysql' )
			);

			$result = $wpdb->insert( $invitations_table, $invitation_data );
			
			if ( $result ) {
				$sent_count++;
			} else {
				$failed_count++;
			}
		}

		return array(
			'sent' => $sent_count,
			'failed' => $failed_count
		);
	}
}
