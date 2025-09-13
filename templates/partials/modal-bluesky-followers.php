<?php
/**
 * Bluesky Followers Invite Modal Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modal_id = 'pm-bluesky-followers-modal';
$modal_size = 'lg';
$modal_title = __( 'Invite Bluesky Followers', 'partyminder' );

ob_start();
?>
<div class="pm-bluesky-followers-content">
	<p class="pm-text-muted pm-mb-4">
		<?php _e( 'Select followers from your Bluesky account to invite to this event.', 'partyminder' ); ?>
	</p>
	
	<div id="pm-bluesky-followers-loading" class="pm-text-center pm-py-4">
		<div class="pm-spinner"></div>
		<p class="pm-text-muted pm-mt-2"><?php _e( 'Loading your Bluesky followers...', 'partyminder' ); ?></p>
	</div>
	
	<div id="pm-bluesky-followers-list" style="display: none;">
		<div class="pm-mb-4">
			<label class="pm-form-label">
				<input type="checkbox" id="pm-select-all-followers" class="pm-form-checkbox">
				<?php _e( 'Select All', 'partyminder' ); ?>
			</label>
		</div>
		
		<div id="pm-followers-container" class="pm-followers-list">
			<!-- Followers will be loaded here via JavaScript -->
		</div>
	</div>
	
	<div id="pm-bluesky-followers-error" style="display: none;" class="pm-card pm-card-error pm-mb-4">
		<div class="pm-card-body">
			<p class="pm-text-error" id="pm-followers-error-message"></p>
		</div>
	</div>
</div>
<?php
$modal_content = ob_get_clean();

ob_start();
?>
<button type="button" class="pm-btn pm-btn-secondary pm-modal-close"><?php _e( 'Cancel', 'partyminder' ); ?></button>
<button type="button" class="pm-btn pm-btn-primary" id="pm-send-followers-invites" disabled>
	<?php _e( 'Send Invitations', 'partyminder' ); ?>
</button>
<?php
$modal_footer = ob_get_clean();

include PARTYMINDER_PLUGIN_DIR . 'templates/partials/modal-base.php';
?>