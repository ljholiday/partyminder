<?php
/**
 * Member Display Component
 * Reusable member display with avatar, display name, and profile link
 * 
 * Usage:
 * include PARTYMINDER_PLUGIN_DIR . 'templates/partials/member-display.php';
 * 
 * Required variables:
 * - $user_id (int): WordPress user ID or user object/email
 * 
 * Optional variables:
 * - $args (array): Display arguments
 *   - 'avatar_size' => 32 (int): Avatar size in pixels
 *   - 'show_avatar' => true (bool): Whether to show avatar
 *   - 'show_name' => true (bool): Whether to show name
 *   - 'link_profile' => true (bool): Whether to make it clickable
 *   - 'class' => 'pm-member-display' (string): CSS classes
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set defaults
$defaults = array(
	'avatar_size'    => 32,
	'show_avatar'    => true,
	'show_name'      => true,
	'link_profile'   => true,
	'fallback_email' => true,
	'class'          => 'pm-member-display',
);

$args = isset($args) ? wp_parse_args($args, $defaults) : $defaults;

// Get user object - handle various input types
$user_obj = null;
if ( $user_id instanceof WP_User ) {
	$user_obj = $user_id;
} elseif ( is_numeric( $user_id ) ) {
	$user_obj = get_user_by( 'id', $user_id );
} elseif ( is_email( $user_id ) ) {
	$user_obj = get_user_by( 'email', $user_id );
} elseif ( is_string( $user_id ) ) {
	$user_obj = get_user_by( 'login', $user_id );
}

if ( ! $user_obj ) {
	// Fallback for email-only cases
	if ( $args['fallback_email'] && is_email( $user_id ) ) {
		echo '<span class="' . esc_attr( $args['class'] ) . '">' . esc_html( $user_id ) . '</span>';
		return;
	}
	echo '<span class="' . esc_attr( $args['class'] ) . '">Unknown User</span>';
	return;
}

// Get display name using Profile Manager
$display_name = '';
if ( class_exists( 'PartyMinder_Profile_Manager' ) ) {
	$display_name = PartyMinder_Profile_Manager::get_display_name( $user_obj->ID );
}

// Fallback to WordPress native display name, then username
if ( empty( $display_name ) ) {
	$display_name = $user_obj->display_name ?: $user_obj->user_login;
}

// Get profile URL
$profile_url = '';
if ( method_exists( 'PartyMinder', 'get_profile_url' ) ) {
	$profile_url = PartyMinder::get_profile_url( $user_obj->ID );
} else {
	$profile_url = get_author_posts_url( $user_obj->ID );
}
?>

<div class="<?php echo esc_attr( $args['class'] ); ?> pm-flex pm-items-center pm-gap-2">
	<?php if ( $args['show_avatar'] ) : ?>
		<?php 
		$avatar = get_avatar( $user_obj->ID, $args['avatar_size'], '', $display_name, array(
			'class' => 'pm-avatar pm-avatar-sm pm-rounded-full'
		) );
		
		if ( $args['link_profile'] && $profile_url ) : ?>
			<a href="<?php echo esc_url( $profile_url ); ?>" class="pm-avatar-link"><?php echo $avatar; ?></a>
		<?php else : ?>
			<?php echo $avatar; ?>
		<?php endif; ?>
	<?php endif; ?>
	
	<?php if ( $args['show_name'] ) : ?>
		<?php if ( $args['link_profile'] && $profile_url ) : ?>
			<a href="<?php echo esc_url( $profile_url ); ?>" class="pm-member-name pm-link"><?php echo esc_html( $display_name ); ?></a>
		<?php else : ?>
			<span class="pm-member-name"><?php echo esc_html( $display_name ); ?></span>
		<?php endif; ?>
	<?php endif; ?>
</div>