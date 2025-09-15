<?php
/**
 * RSVP Modal Template
 * Uses unified modal system for event RSVP functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modal_id = 'pm-rsvp-modal';
$modal_size = 'md';
$modal_title = __( 'RSVP for Event', 'partyminder' );

ob_start();
?>
<div class="pm-rsvp-content">
	<p class="pm-text-muted pm-mb-4">
		<?php _e( 'Please let us know if you will be attending this event.', 'partyminder' ); ?>
	</p>

	<div id="pm-rsvp-form-container">
		<p><?php _e( 'RSVP form will be loaded here.', 'partyminder' ); ?></p>
	</div>
</div>
<?php
$modal_content = ob_get_clean();

ob_start();
?>
<button type="button" class="pm-btn pm-btn-secondary pm-modal-close"><?php _e( 'Cancel', 'partyminder' ); ?></button>
<button type="button" class="pm-btn pm-btn-primary" id="pm-submit-rsvp" disabled>
	<?php _e( 'Submit RSVP', 'partyminder' ); ?>
</button>
<?php
$modal_footer = ob_get_clean();

include PARTYMINDER_PLUGIN_DIR . 'templates/partials/modal-base.php';
?>