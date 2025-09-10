<?php
/**
 * Member Display Utilities
 * Handles displaying member information with avatars, display names, and profile links
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PartyMinder_Member_Display {

	/**
	 * Display member with avatar and display name as profile link
	 * 
	 * @param string|int|WP_User $user User email, ID, or WP_User object
	 * @param array $args Optional display arguments
	 * @return string HTML output
	 */
	public static function get_member_display( $user_id, $args = array() ) {
		ob_start();
		include PARTYMINDER_PLUGIN_DIR . 'templates/partials/member-display.php';
		return ob_get_clean();
	}
	
	/**
	 * Echo member display
	 */
	public static function member_display( $user_id, $args = array() ) {
		echo self::get_member_display( $user_id, $args );
	}
	
	
	/**
	 * Display host information for events
	 */
	public static function get_event_host_display( $event, $args = array() ) {
		$defaults = array(
			'prefix' => __( 'Hosted by ', 'partyminder' ),
			'avatar_size' => 24,
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$output = esc_html( $args['prefix'] );
		
		// Try to get user by author_id first, then fall back to host_email
		if ( ! empty( $event->author_id ) ) {
			$output .= self::get_member_display( $event->author_id, $args );
		} elseif ( ! empty( $event->host_email ) ) {
			$user = get_user_by( 'email', $event->host_email );
			if ( $user ) {
				$output .= self::get_member_display( $user->ID, $args );
			} else {
				$output .= '<span class="pm-member-display">' . esc_html( $event->host_email ) . '</span>';
			}
		} else {
			$output .= '<span class="pm-member-display">Unknown Host</span>';
		}
		
		return $output;
	}
	
	/**
	 * Echo event host display
	 */
	public static function event_host_display( $event, $args = array() ) {
		echo self::get_event_host_display( $event, $args );
	}
}