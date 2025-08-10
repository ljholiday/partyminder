<?php

/**
 * PartyMinder Bluesky Client
 *
 * Handles AT Protocol/Bluesky API interactions
 */
class PartyMinder_Bluesky_Client {

	private $pds_url = 'https://bsky.social';
	private $access_token;
	private $refresh_token;
	private $did;

	/**
	 * Authenticate with Bluesky
	 */
	public function authenticate( $handle, $password ) {
		$endpoint = $this->pds_url . '/xrpc/com.atproto.server.createSession';

		// Normalize handle - remove @ if present, ensure .bsky.social if no domain
		$normalized_handle = $this->normalize_handle( $handle );

		// Log authentication attempt
		error_log( '[PartyMinder Bluesky] Original handle: ' . $handle );
		error_log( '[PartyMinder Bluesky] Normalized handle: ' . $normalized_handle );
		error_log( '[PartyMinder Bluesky] Password length: ' . strlen( $password ) );

		$body = wp_json_encode(
			array(
				'identifier' => $normalized_handle,
				'password'   => $password,
			)
		);

		error_log( '[PartyMinder Bluesky] Request body: ' . $body );

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => $body,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_msg = 'Network error: ' . $response->get_error_message();
			error_log( '[PartyMinder Bluesky] ' . $error_msg );
			return array(
				'success' => false,
				'error'   => $error_msg,
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		// Log response details
		error_log( '[PartyMinder Bluesky] Response status: ' . $status_code );
		error_log( '[PartyMinder Bluesky] Response body: ' . substr( $body, 0, 500 ) );

		if ( $status_code === 200 && isset( $data['accessJwt'] ) ) {
			$this->access_token  = $data['accessJwt'];
			$this->refresh_token = $data['refreshJwt'];
			$this->did           = $data['did'];

			error_log( '[PartyMinder Bluesky] Authentication successful for handle: ' . $handle );

			return array(
				'success'       => true,
				'access_token'  => $data['accessJwt'],
				'refresh_token' => $data['refreshJwt'],
				'did'           => $data['did'],
				'handle'        => $data['handle'],
			);
		}

		$error_message = 'Authentication failed';
		if ( isset( $data['error'] ) ) {
			$error_message = $data['message'] ?? $data['error'];
		}

		error_log( '[PartyMinder Bluesky] Authentication failed: ' . $error_message );

		return array(
			'success' => false,
			'error'   => $error_message,
		);
	}

	/**
	 * Set authentication tokens
	 */
	public function set_tokens( $access_token, $refresh_token ) {
		$this->access_token  = $access_token;
		$this->refresh_token = $refresh_token;
	}

	/**
	 * Get user's follows (contacts)
	 */
	public function get_follows( $did, $limit = 100 ) {
		error_log( '[PartyMinder Bluesky] Getting follows for DID: ' . $did );

		if ( ! $this->access_token ) {
			error_log( '[PartyMinder Bluesky] No access token available' );
			return array(
				'success' => false,
				'error'   => 'Not authenticated',
			);
		}

		$endpoint = $this->pds_url . '/xrpc/app.bsky.graph.getFollows';
		$url      = add_query_arg(
			array(
				'actor' => $did,
				'limit' => $limit,
			),
			$endpoint
		);

		error_log( '[PartyMinder Bluesky] Fetching follows from: ' . $url );

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_msg = 'Network error: ' . $response->get_error_message();
			error_log( '[PartyMinder Bluesky] ' . $error_msg );
			return array(
				'success' => false,
				'error'   => $error_msg,
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		error_log( '[PartyMinder Bluesky] Follows response status: ' . $status_code );
		error_log( '[PartyMinder Bluesky] Follows response body: ' . substr( $body, 0, 500 ) );

		if ( $status_code === 200 && isset( $data['follows'] ) ) {
			error_log( '[PartyMinder Bluesky] Found ' . count( $data['follows'] ) . ' follows' );

			// Process follows into a usable format
			$contacts = array();
			foreach ( $data['follows'] as $follow ) {
				$contacts[] = array(
					'did'             => $follow['did'],
					'handle'          => $follow['handle'],
					'display_name'    => $follow['displayName'] ?? $follow['handle'],
					'avatar'          => $follow['avatar'] ?? null,
					'description'     => $follow['description'] ?? '',
					'follower_count'  => $follow['followersCount'] ?? 0,
					'following_count' => $follow['followsCount'] ?? 0,
					'posts_count'     => $follow['postsCount'] ?? 0,
				);
			}

			error_log( '[PartyMinder Bluesky] Processed ' . count( $contacts ) . ' contacts' );

			return array(
				'success' => true,
				'follows' => $contacts,
				'cursor'  => $data['cursor'] ?? null,
			);
		}

		if ( $status_code === 401 ) {
			// Token might be expired, try to refresh
			$refresh_result = $this->refresh_session();
			if ( $refresh_result['success'] ) {
				// Retry the request with new token
				return $this->get_follows( $did, $limit );
			}
		}

		$error_message = 'Failed to fetch follows';
		if ( isset( $data['error'] ) ) {
			$error_message = $data['message'] ?? $data['error'];
		}

		error_log( '[PartyMinder Bluesky] Follows fetch failed: ' . $error_message );

		return array(
			'success' => false,
			'error'   => $error_message,
		);
	}

	/**
	 * Get user profile
	 */
	public function get_profile( $actor ) {
		if ( ! $this->access_token ) {
			return array(
				'success' => false,
				'error'   => 'Not authenticated',
			);
		}

		$endpoint = $this->pds_url . '/xrpc/app.bsky.actor.getProfile';
		$url      = add_query_arg( array( 'actor' => $actor ), $endpoint );

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => 'Network error: ' . $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code === 200 ) {
			return array(
				'success' => true,
				'profile' => array(
					'did'             => $data['did'],
					'handle'          => $data['handle'],
					'display_name'    => $data['displayName'] ?? $data['handle'],
					'avatar'          => $data['avatar'] ?? null,
					'banner'          => $data['banner'] ?? null,
					'description'     => $data['description'] ?? '',
					'follower_count'  => $data['followersCount'] ?? 0,
					'following_count' => $data['followsCount'] ?? 0,
					'posts_count'     => $data['postsCount'] ?? 0,
				),
			);
		}

		$error_message = 'Failed to fetch profile';
		if ( isset( $data['error'] ) ) {
			$error_message = $data['message'] ?? $data['error'];
		}

		return array(
			'success' => false,
			'error'   => $error_message,
		);
	}

	/**
	 * Refresh authentication session
	 */
	public function refresh_session() {
		if ( ! $this->refresh_token ) {
			return array(
				'success' => false,
				'error'   => 'No refresh token available',
			);
		}

		$endpoint = $this->pds_url . '/xrpc/com.atproto.server.refreshSession';

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->refresh_token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => 'Network error: ' . $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code === 200 && isset( $data['accessJwt'] ) ) {
			$this->access_token  = $data['accessJwt'];
			$this->refresh_token = $data['refreshJwt'];

			return array(
				'success'       => true,
				'access_token'  => $data['accessJwt'],
				'refresh_token' => $data['refreshJwt'],
			);
		}

		$error_message = 'Failed to refresh session';
		if ( isset( $data['error'] ) ) {
			$error_message = $data['message'] ?? $data['error'];
		}

		return array(
			'success' => false,
			'error'   => $error_message,
		);
	}

	/**
	 * Search for actors (users)
	 */
	public function search_actors( $query, $limit = 25 ) {
		if ( ! $this->access_token ) {
			return array(
				'success' => false,
				'error'   => 'Not authenticated',
			);
		}

		$endpoint = $this->pds_url . '/xrpc/app.bsky.actor.searchActors';
		$url      = add_query_arg(
			array(
				'term'  => $query,
				'limit' => $limit,
			),
			$endpoint
		);

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => 'Network error: ' . $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code === 200 && isset( $data['actors'] ) ) {
			$actors = array();
			foreach ( $data['actors'] as $actor ) {
				$actors[] = array(
					'did'          => $actor['did'],
					'handle'       => $actor['handle'],
					'display_name' => $actor['displayName'] ?? $actor['handle'],
					'avatar'       => $actor['avatar'] ?? null,
					'description'  => $actor['description'] ?? '',
				);
			}

			return array(
				'success' => true,
				'actors'  => $actors,
			);
		}

		$error_message = 'Failed to search actors';
		if ( isset( $data['error'] ) ) {
			$error_message = $data['message'] ?? $data['error'];
		}

		return array(
			'success' => false,
			'error'   => $error_message,
		);
	}

	/**
	 * Normalize Bluesky handle
	 */
	private function normalize_handle( $handle ) {
		// Remove @ if present
		$handle = ltrim( $handle, '@' );

		// If no domain is present, add .bsky.social
		if ( strpos( $handle, '.' ) === false ) {
			$handle = $handle . '.bsky.social';
		}

		return $handle;
	}
}
