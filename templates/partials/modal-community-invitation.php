<?php
/**
 * Community Invitation Modal Template
 * REUSES event modal base structure and patterns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Reuse modal base configuration (same as event modal)
$modal_id = 'pm-community-invitation-modal';
$modal_size = 'md'; // Same size as RSVP modal
$modal_title = __( 'Community Invitation', 'partyminder' );

ob_start();
?>
<div class="pm-community-invitation-content">
	<!-- REUSE event invitation description pattern -->
	<p class="pm-text-muted pm-mb-4">
		<?php _e( 'You\'ve been invited to join a community.', 'partyminder' ); ?>
	</p>

	<!-- REUSE event form container pattern -->
	<div id="pm-community-invitation-form-container">
		<p><?php _e( 'Loading invitation details...', 'partyminder' ); ?></p>
	</div>
</div>
<?php
$modal_content = ob_get_clean();

ob_start();
?>
<!-- REUSE event modal footer button pattern -->
<button type="button" class="pm-btn pm-btn-secondary pm-modal-close"><?php _e( 'Cancel', 'partyminder' ); ?></button>
<button type="button" class="pm-btn pm-btn-primary" id="pm-accept-community-invitation" disabled>
	<?php _e( 'Join Community', 'partyminder' ); ?>
</button>
<?php
$modal_footer = ob_get_clean();

// REUSE modal base template (same as events)
include PARTYMINDER_PLUGIN_DIR . 'templates/partials/modal-base.php';
?>