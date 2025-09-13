<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

ob_start();
?>
<form id="pm-bluesky-connect-form" class="pm-modal-form">
	<div class="pm-form-error" style="display: none;"></div>
	
	<div class="pm-form-row">
		<label for="pm-bluesky-handle" class="pm-form-label"><?php _e( 'Bluesky Handle', 'partyminder' ); ?></label>
		<input type="text" 
		       id="pm-bluesky-handle" 
		       class="pm-input pm-bluesky-handle" 
		       placeholder="<?php esc_attr_e( 'username.bsky.social', 'partyminder' ); ?>" 
		       required>
	</div>
	
	<div class="pm-form-row">
		<label for="pm-bluesky-password" class="pm-form-label"><?php _e( 'App Password', 'partyminder' ); ?></label>
		<input type="password" 
		       id="pm-bluesky-password" 
		       class="pm-input pm-bluesky-password" 
		       placeholder="<?php esc_attr_e( 'Your Bluesky app password', 'partyminder' ); ?>" 
		       required>
		<small class="pm-form-help">
			<?php _e( 'Create an app password in your Bluesky settings for secure access.', 'partyminder' ); ?>
		</small>
	</div>
</form>
<?php
$modal_content = ob_get_clean();

ob_start();
?>
<div class="pm-form-actions">
	<button type="button" class="pm-btn pm-btn-secondary pm-modal-close">
		<?php _e( 'Cancel', 'partyminder' ); ?>
	</button>
	<button type="submit" form="pm-bluesky-connect-form" class="pm-btn pm-btn-primary pm-bluesky-connect-submit">
		<?php _e( 'Connect Account', 'partyminder' ); ?>
	</button>
</div>
<?php
$modal_footer = ob_get_clean();

$modal_id = 'pm-bluesky-connect-modal';
$modal_size = 'sm';
$modal_title = __( 'Connect to Bluesky', 'partyminder' );
$modal_close_label = __( 'Close Bluesky connection dialog', 'partyminder' );

include PARTYMINDER_PLUGIN_DIR . 'templates/partials/modal-base.php';
?>