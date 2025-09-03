<?php
/**
 * Search REST API
 * Handles search API endpoints
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PartyMinder_Search_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register search REST routes
	 */
	public function register_routes() {
		register_rest_route(
			'partyminder/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'search_content' ),
				'permission_callback' => '__return_true', // MVP: Allow all users
				'args'                => array(
					'q' => array(
						'required'    => true,
						'type'        => 'string',
						'description' => 'Search query',
					),
					'types' => array(
						'required'    => false,
						'type'        => 'string',
						'description' => 'Comma-separated list of entity types',
					),
					'limit' => array(
						'required'    => false,
						'type'        => 'integer',
						'default'     => 20,
						'description' => 'Number of results to return',
					),
				),
			)
		);
	}

	/**
	 * Handle search request
	 */
	public function search_content( WP_REST_Request $request ) {
		$query = trim( $request->get_param( 'q' ) );
		$types = $request->get_param( 'types' );
		$limit = min( 50, max( 1, intval( $request->get_param( 'limit' ) ) ) );

		if ( empty( $query ) ) {
			return rest_ensure_response( array( 'items' => array() ) );
		}

		// Parse entity types
		$allowed_types = array( 'event', 'community', 'conversation', 'member' );
		$entity_types = array();
		
		if ( $types ) {
			$entity_types = array_filter(
				array_map( 'trim', explode( ',', $types ) ),
				function( $type ) use ( $allowed_types ) {
					return in_array( $type, $allowed_types );
				}
			);
		}

		// Perform search
		$results = $this->perform_search( $query, $entity_types, $limit );

		// Format results
		$formatted_results = array();
		foreach ( $results as $result ) {
			$formatted_results[] = array(
				'entity_type' => $result->entity_type,
				'entity_id'   => intval( $result->entity_id ),
				'title'       => $result->title,
				'snippet'     => $this->create_snippet( $result->content, $query ),
				'url'         => $result->url,
				'score'       => floatval( $result->match_score ),
			);
		}

		return rest_ensure_response( array( 'items' => $formatted_results ) );
	}

	/**
	 * Perform the actual search query
	 */
	private function perform_search( $query, $entity_types = array(), $limit = 20 ) {
		global $wpdb;

		$search_table = $wpdb->prefix . 'partyminder_search';
		$current_user_id = get_current_user_id();

		// Create boolean search query
		$boolean_query = $this->create_boolean_query( $query );

		// Base query
		$sql = "SELECT entity_type, entity_id, title, content, url,
		               MATCH(title, content) AGAINST (%s IN BOOLEAN MODE) AS match_score,
		               last_activity_at
		        FROM $search_table
		        WHERE MATCH(title, content) AGAINST (%s IN BOOLEAN MODE)";

		$params = array( $boolean_query, $boolean_query );

		// Add entity type filter
		if ( ! empty( $entity_types ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $entity_types ), '%s' ) );
			$sql .= " AND entity_type IN ($placeholders)";
			$params = array_merge( $params, $entity_types );
		}

		// Add basic visibility filter (MVP: just check logged-in status)
		if ( ! $current_user_id ) {
			$sql .= " AND visibility_scope = 'public'";
		} else {
			$sql .= " AND (visibility_scope IN ('public', 'site') OR owner_user_id = %d)";
			$params[] = $current_user_id;
		}

		$sql .= " ORDER BY match_score DESC, last_activity_at DESC LIMIT %d";
		$params[] = $limit;

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

		// Fallback to LIKE search for very short queries
		if ( empty( $results ) && strlen( preg_replace( '/\s+/', '', $query ) ) <= 2 ) {
			$results = $this->fallback_like_search( $query, $entity_types, $limit, $current_user_id );
		}

		return $results;
	}

	/**
	 * Create boolean search query
	 */
	private function create_boolean_query( $query ) {
		$tokens = array();
		
		// Match quoted phrases and individual words
		preg_match_all( '/"[^"]+"|\S+/', $query, $matches );
		
		foreach ( $matches[0] as $token ) {
			if ( $token[0] === '"' && substr( $token, -1 ) === '"' ) {
				// Keep quoted phrases as-is
				$tokens[] = $token;
			} else {
				// Clean token and add prefix wildcard for longer terms
				$clean_token = preg_replace( '/[^\p{L}\p{N}_-]+/u', '', $token );
				if ( ! empty( $clean_token ) ) {
					if ( mb_strlen( $clean_token ) >= 3 ) {
						$tokens[] = '+' . $clean_token . '*';
					} else {
						$tokens[] = '+' . $clean_token;
					}
				}
			}
		}

		return implode( ' ', $tokens );
	}

	/**
	 * Fallback LIKE search for short queries
	 */
	private function fallback_like_search( $query, $entity_types, $limit, $current_user_id ) {
		global $wpdb;

		$search_table = $wpdb->prefix . 'partyminder_search';
		$like_query = '%' . $wpdb->esc_like( $query ) . '%';

		$sql = "SELECT entity_type, entity_id, title, content, url,
		               0.0 AS match_score, last_activity_at
		        FROM $search_table
		        WHERE (title LIKE %s OR content LIKE %s)";

		$params = array( $like_query, $like_query );

		// Add entity type filter
		if ( ! empty( $entity_types ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $entity_types ), '%s' ) );
			$sql .= " AND entity_type IN ($placeholders)";
			$params = array_merge( $params, $entity_types );
		}

		// Add visibility filter
		if ( ! $current_user_id ) {
			$sql .= " AND visibility_scope = 'public'";
		} else {
			$sql .= " AND (visibility_scope IN ('public', 'site') OR owner_user_id = %d)";
			$params[] = $current_user_id;
		}

		$sql .= " ORDER BY last_activity_at DESC LIMIT %d";
		$params[] = $limit;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Create search result snippet
	 */
	private function create_snippet( $content, $query, $max_length = 200 ) {
		$content = wp_strip_all_tags( $content );
		
		if ( strlen( $content ) <= $max_length ) {
			return $this->highlight_terms( $content, $query );
		}

		// Try to find query terms in content
		$query_terms = preg_split( '/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY );
		$best_position = 0;
		$best_score = 0;

		foreach ( $query_terms as $term ) {
			$position = stripos( $content, $term );
			if ( $position !== false ) {
				$score = 1 / ( $position + 1 ); // Prefer earlier matches
				if ( $score > $best_score ) {
					$best_score = $score;
					$best_position = max( 0, $position - 50 );
				}
			}
		}

		$snippet = substr( $content, $best_position, $max_length );
		
		// Clean up snippet boundaries
		if ( $best_position > 0 ) {
			$snippet = '...' . ltrim( $snippet );
		}
		
		if ( strlen( $content ) > $best_position + $max_length ) {
			$snippet = rtrim( $snippet ) . '...';
		}

		return $this->highlight_terms( $snippet, $query );
	}

	/**
	 * Highlight search terms in text
	 */
	private function highlight_terms( $text, $query ) {
		$query_terms = preg_split( '/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY );
		$terms_to_highlight = array();

		foreach ( $query_terms as $term ) {
			$clean_term = trim( $term, '"' );
			if ( strlen( $clean_term ) >= 2 ) {
				$terms_to_highlight[] = preg_quote( $clean_term, '/' );
			}
		}

		if ( ! empty( $terms_to_highlight ) ) {
			$pattern = '/(' . implode( '|', $terms_to_highlight ) . ')/i';
			$text = preg_replace( $pattern, '<mark>$1</mark>', esc_html( $text ) );
		} else {
			$text = esc_html( $text );
		}

		return $text;
	}
}