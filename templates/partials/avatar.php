<?php
/**
 * Avatar Component
 * Reusable avatar fragment with profile link and image support
 * 
 * Usage:
 * include PARTYMINDER_PLUGIN_DIR . 'templates/partials/avatar.php';
 * 
 * Required variables:
 * - $user_id (int): WordPress user ID
 * - $user_name (string): Display name
 * 
 * Optional variables:
 * - $size (string): 'sm', 'md' (default), 'lg' - avatar size
 * - $link (bool): true (default) - whether to make it clickable
 * - $class (string): additional CSS classes
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set defaults
$size = isset($size) ? $size : 'md';
$link = isset($link) ? $link : true;
$class = isset($class) ? $class : '';

// Build CSS classes
$avatar_classes = array('pm-avatar');
if ($size === 'sm') {
	$avatar_classes[] = 'pm-avatar-sm';
} elseif ($size === 'lg') {
	$avatar_classes[] = 'pm-avatar-lg';
}
if ($class) {
	$avatar_classes[] = $class;
}

// Get avatar image
$avatar_size = $size === 'sm' ? 32 : ($size === 'lg' ? 56 : 40);
$has_avatar = get_avatar_url($user_id);
$initials = strtoupper(substr($user_name, 0, 2));

// Generate profile URL
$profile_url = PartyMinder::get_profile_url($user_id);

// Render avatar
if ($link && $user_id) : ?>
	<a href="<?php echo esc_url($profile_url); ?>" 
	   class="<?php echo esc_attr(implode(' ', $avatar_classes)); ?>" 
	   title="<?php echo esc_attr(sprintf(__('View %s\'s profile', 'partyminder'), $user_name)); ?>">
		<?php if ($has_avatar && $user_id) : ?>
			<?php echo get_avatar($user_id, $avatar_size, '', $user_name, array(
				'class' => 'pm-avatar-img'
			)); ?>
		<?php else : ?>
			<?php echo esc_html($initials); ?>
		<?php endif; ?>
	</a>
<?php else : ?>
	<div class="<?php echo esc_attr(implode(' ', $avatar_classes)); ?>" 
		 title="<?php echo esc_attr($user_name); ?>">
		<?php if ($has_avatar && $user_id) : ?>
			<?php echo get_avatar($user_id, $avatar_size, '', $user_name, array(
				'class' => 'pm-avatar-img'
			)); ?>
		<?php else : ?>
			<?php echo esc_html($initials); ?>
		<?php endif; ?>
	</div>
<?php endif; ?>