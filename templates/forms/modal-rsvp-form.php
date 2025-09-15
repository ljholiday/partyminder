<?php
/**
 * Modal RSVP Form Template
 * Simplified RSVP form for modal display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Expect $event_id to be passed in
if ( ! isset( $event_id ) || ! $event_id ) {
	echo '<div class="pm-alert pm-alert-error">' . __( 'No event specified.', 'partyminder' ) . '</div>';
	return;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

// Get event details
$event_manager = new PartyMinder_Event_Manager();
$event = $event_manager->get_event( $event_id );

if ( ! $event ) {
	echo '<div class="pm-alert pm-alert-error">' . __( 'Event not found.', 'partyminder' ) . '</div>';
	return;
}

// Get guest manager
$guest_manager = new PartyMinder_Guest_Manager();

// Use existing guest data from AJAX handler or check for logged-in user
$existing_rsvp = $existing_guest ?? null;
$current_user_email = '';

if ( ! $existing_rsvp && is_user_logged_in() ) {
	$current_user = wp_get_current_user();
	$current_user_email = $current_user->user_email;

	$guests = $guest_manager->get_event_guests( $event_id );
	foreach ( $guests as $guest ) {
		if ( $guest->email === $current_user_email ) {
			$existing_rsvp = $guest;
			break;
		}
	}
}

// Get event statistics
$guest_stats = $guest_manager->get_guest_stats( $event_id );

// Check if event is full
$is_event_full = $event->guest_limit > 0 && $guest_stats->confirmed >= $event->guest_limit;

// Format event date
$event_date = new DateTime( $event->event_date );
$is_past_event = $event_date < new DateTime();
?>

<div class="pm-rsvp-form-content">
	<?php if ( $is_past_event ) : ?>
		<div class="pm-alert pm-alert-warning">
			<strong><?php _e( 'This Event Has Passed', 'partyminder' ); ?></strong>
			<p><?php _e( 'This event is no longer accepting RSVPs.', 'partyminder' ); ?></p>
		</div>
	<?php elseif ( $is_event_full && ( ! $existing_rsvp || $existing_rsvp->status !== 'confirmed' ) ) : ?>
		<div class="pm-alert pm-alert-warning">
			<strong><?php _e( 'Event is Full', 'partyminder' ); ?></strong>
			<p><?php _e( 'This event has reached capacity. You can still RSVP for the waitlist.', 'partyminder' ); ?></p>
		</div>
	<?php endif; ?>

	<form id="pm-modal-rsvp-form" class="pm-form">
		<?php wp_nonce_field( 'partyminder_modal_rsvp_' . $event_id, 'partyminder_modal_rsvp_nonce' ); ?>
		<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
		<input type="hidden" name="invitation_source" value="<?php echo esc_attr( $invitation_source ?? 'direct' ); ?>" />
		<input type="hidden" name="existing_guest_id" value="<?php echo esc_attr( $existing_guest->id ?? '' ); ?>" />

		<!-- Guest Information -->
		<div class="pm-form-section pm-mb-4">
			<h4 class="pm-heading pm-heading-sm pm-mb-3"><?php _e( 'Your Information', 'partyminder' ); ?></h4>

			<div class="pm-form-group pm-mb-3">
				<label for="pm-guest-name" class="pm-form-label"><?php _e( 'Your Name', 'partyminder' ); ?> <span class="pm-required">*</span></label>
				<input type="text" id="pm-guest-name" name="guest_name" class="pm-form-input"
					value="<?php echo esc_attr( $existing_rsvp ? $existing_rsvp->name : ( is_user_logged_in() ? wp_get_current_user()->display_name : '' ) ); ?>"
					required />
			</div>

			<div class="pm-form-group pm-mb-3">
				<label for="pm-guest-email" class="pm-form-label"><?php _e( 'Email Address', 'partyminder' ); ?> <span class="pm-required">*</span></label>
				<input type="email" id="pm-guest-email" name="guest_email" class="pm-form-input"
					value="<?php echo esc_attr( $existing_rsvp ? $existing_rsvp->email : $current_user_email ); ?>"
					required />
			</div>
		</div>

		<!-- RSVP Status -->
		<div class="pm-form-section pm-mb-4">
			<h4 class="pm-heading pm-heading-sm pm-mb-3"><?php _e( 'Will You Attend?', 'partyminder' ); ?></h4>

			<div class="pm-rsvp-options">
				<?php
				$current_status = $existing_rsvp ? $existing_rsvp->status : '';
				$statuses = array(
					'confirmed' => array(
						'title' => __( 'Yes, I\'ll be there!', 'partyminder' ),
						'desc'  => __( 'Count me in', 'partyminder' ),
						'class' => 'pm-rsvp-yes'
					),
					'maybe' => array(
						'title' => __( 'Maybe', 'partyminder' ),
						'desc'  => __( 'I\'ll try to make it', 'partyminder' ),
						'class' => 'pm-rsvp-maybe'
					),
					'declined' => array(
						'title' => __( 'Sorry, can\'t make it', 'partyminder' ),
						'desc'  => __( 'Have fun without me', 'partyminder' ),
						'class' => 'pm-rsvp-no'
					),
				);

				foreach ( $statuses as $status => $info ) :
				?>
				<label class="pm-rsvp-option <?php echo $current_status === $status ? 'pm-selected' : ''; ?> <?php echo esc_attr( $info['class'] ); ?>">
					<input type="radio" name="rsvp_status" value="<?php echo esc_attr( $status ); ?>"
						<?php checked( $current_status, $status ); ?> required />
					<div class="pm-option-content">
						<div class="pm-option-title"><?php echo esc_html( $info['title'] ); ?></div>
						<div class="pm-option-desc"><?php echo esc_html( $info['desc'] ); ?></div>
					</div>
				</label>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Additional Info (only show if not declined) -->
		<div class="pm-form-section pm-additional-info" id="pm-additional-info"
			style="<?php echo $current_status === 'declined' ? 'display: none;' : ''; ?>">

			<div class="pm-form-group pm-mb-3">
				<label for="pm-dietary-restrictions" class="pm-form-label"><?php _e( 'Dietary Restrictions', 'partyminder' ); ?></label>
				<textarea id="pm-dietary-restrictions" name="dietary_restrictions" rows="2" class="pm-form-textarea"
					placeholder="<?php esc_attr_e( 'e.g., Vegetarian, gluten-free, no nuts...', 'partyminder' ); ?>"><?php echo esc_textarea( $existing_rsvp ? $existing_rsvp->dietary_restrictions : '' ); ?></textarea>
			</div>

			<div class="pm-form-group pm-mb-3">
				<label for="pm-guest-notes" class="pm-form-label"><?php _e( 'Additional Notes', 'partyminder' ); ?></label>
				<textarea id="pm-guest-notes" name="guest_notes" rows="2" class="pm-form-textarea"
					placeholder="<?php esc_attr_e( 'Anything else the host should know...', 'partyminder' ); ?>"><?php echo esc_textarea( $existing_rsvp ? $existing_rsvp->notes : '' ); ?></textarea>
			</div>
		</div>

		<div class="pm-form-actions">
			<div id="pm-rsvp-form-messages" class="pm-mb-3"></div>
		</div>
	</form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Only run if we're in the modal context
	if (!document.getElementById('pm-modal-rsvp-form')) return;

	// RSVP status change handler
	const rsvpOptions = document.querySelectorAll('#pm-modal-rsvp-form input[name="rsvp_status"]');
	const additionalInfo = document.getElementById('pm-additional-info');

	rsvpOptions.forEach(function(option) {
		option.addEventListener('change', function() {
			// Update visual selection
			document.querySelectorAll('.pm-rsvp-option').forEach(function(opt) {
				opt.classList.remove('pm-selected');
			});
			this.closest('.pm-rsvp-option').classList.add('pm-selected');

			// Show/hide additional info
			if (this.value === 'declined') {
				additionalInfo.style.display = 'none';
			} else {
				additionalInfo.style.display = 'block';
			}
		});
	});
});
</script>