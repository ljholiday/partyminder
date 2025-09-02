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
	public static function get_member_display( $user, $args = array() ) {
		$defaults = array(
			'avatar_size'    => 32,
			'show_avatar'    => true,
			'show_name'      => true,
			'link_profile'   => true,
			'fallback_email' => true,
			'class'          => 'pm-member-display',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		// Get user object
		$user_obj = self::get_user_object( $user );
		
		if ( ! $user_obj ) {
			// Fallback for email-only cases
			if ( $args['fallback_email'] && is_email( $user ) ) {
				return '<span class="' . esc_attr( $args['class'] ) . '">' . esc_html( $user ) . '</span>';
			}
			return '<span class="' . esc_attr( $args['class'] ) . '">Unknown User</span>';
		}
		
		// Try to get display name from PartyMinder profile first, then WordPress user
		$display_name = '';
		if ( class_exists( 'PartyMinder_Profile_Manager' ) ) {
			$display_name = PartyMinder_Profile_Manager::get_display_name( $user_obj->ID );
		}
		
		// Fallback to WordPress native display name, then username
		if ( empty( $display_name ) ) {
			$display_name = $user_obj->display_name ?: $user_obj->user_login;
		}
		$profile_url = self::get_profile_url( $user_obj->ID );
		
		$output = '<div class="' . esc_attr( $args['class'] ) . ' pm-flex pm-items-center pm-gap-2">';
		
		if ( $args['show_avatar'] ) {
			$avatar = get_avatar( $user_obj->ID, $args['avatar_size'], '', $display_name, array(
				'class' => 'pm-avatar pm-avatar-sm pm-rounded-full'
			) );
			
			if ( $args['link_profile'] && $profile_url ) {
				$output .= '<a href="' . esc_url( $profile_url ) . '" class="pm-avatar-link">' . $avatar . '</a>';
			} else {
				$output .= $avatar;
			}
		}
		
		if ( $args['show_name'] ) {
			if ( $args['link_profile'] && $profile_url ) {
				$output .= '<a href="' . esc_url( $profile_url ) . '" class="pm-member-name pm-link">' . esc_html( $display_name ) . '</a>';
			} else {
				$output .= '<span class="pm-member-name">' . esc_html( $display_name ) . '</span>';
			}
		}
		
		$output .= '</div>';
		
		return $output;
	}
	
	/**
	 * Echo member display
	 */
	public static function member_display( $user, $args = array() ) {
		echo self::get_member_display( $user, $args );
	}
	
	/**
	 * Get user object from various input types
	 */
	private static function get_user_object( $user ) {
		if ( $user instanceof WP_User ) {
			return $user;
		}
		
		if ( is_numeric( $user ) ) {
			return get_user_by( 'id', $user );
		}
		
		if ( is_email( $user ) ) {
			return get_user_by( 'email', $user );
		}
		
		if ( is_string( $user ) ) {
			// Try as username
			$user_obj = get_user_by( 'login', $user );
			if ( $user_obj ) {
				return $user_obj;
			}
		}
		
		return null;
	}
	
	/**
	 * Get profile URL for user
	 */
	private static function get_profile_url( $user_id ) {
		// Check if PartyMinder has a profile page
		if ( method_exists( 'PartyMinder', 'get_profile_url' ) ) {
			return PartyMinder::get_profile_url( $user_id );
		}
		
		// Fallback to WordPress profile or author page
		return get_author_posts_url( $user_id );
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
		
		$output = $args['prefix'];
		
		// Try to get user by author_id first, then fall back to host_email
		$user = null;
		if ( ! empty( $event->author_id ) ) {
			$user = get_user_by( 'id', $event->author_id );
		} elseif ( ! empty( $event->host_email ) ) {
			$user = get_user_by( 'email', $event->host_email );
		}
		
		if ( $user ) {
			$output .= self::get_member_display( $user, $args );
		} else {
			// Fallback to email if no user found
			$output .= '<span class="pm-member-display">' . esc_html( $event->host_email ?? 'Unknown Host' ) . '</span>';
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