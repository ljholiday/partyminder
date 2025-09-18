<?php
/**
 * Community Invitation Form Template
 * REUSES event RSVP form structure and styling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
$community_manager = new PartyMinder_Community_Manager();

// Handle both token-based invitations and generic joins
$invitation = null;
$community = null;

if ( isset( $token ) && $token ) {
	// Token-based invitation (existing flow)
	$invitation = $community_manager->get_invitation_by_token( $token );
	if ( ! $invitation ) {
		echo '<div class="pm-alert pm-alert-error">' . __( 'Invalid invitation.', 'partyminder' ) . '</div>';
		return;
	}
	$community = $community_manager->get_community( $invitation->community_id );
} else {
	// Generic join (no token) - get community from global or passed variable
	if ( isset( $GLOBALS['partyminder_current_community'] ) ) {
		$community = $GLOBALS['partyminder_current_community'];
	}
}

if ( ! $community ) {
	echo '<div class="pm-alert pm-alert-error">' . __( 'Community not found.', 'partyminder' ) . '</div>';
	return;
}

// Check invitation status (only if we have an invitation)
$is_expired = $invitation ? ( strtotime( $invitation->expires_at ) < time() ) : false;
$current_user_email = is_user_logged_in() ? wp_get_current_user()->user_email : '';
$is_already_member = is_user_logged_in() ? $community_manager->is_member( $community->id, get_current_user_id() ) : false;
?>

<div class="pm-community-invitation-form-content">
	<?php if ( $is_expired ) : ?>
		<!-- REUSE event expiration alert styling -->
		<div class="pm-alert pm-alert-warning">
			<strong><?php _e( 'This Invitation Has Expired', 'partyminder' ); ?></strong>
			<p><?php _e( 'This invitation is no longer valid.', 'partyminder' ); ?></p>
		</div>
	<?php elseif ( $is_already_member ) : ?>
		<!-- REUSE event "already RSVP'd" pattern -->
		<div class="pm-alert pm-alert-info">
			<strong><?php _e( 'Already a Member', 'partyminder' ); ?></strong>
			<p><?php _e( 'You are already a member of this community.', 'partyminder' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- REUSE event form structure -->
	<form id="pm-community-invitation-form" class="pm-form">
		<?php wp_nonce_field( 'partyminder_community_invitation_' . $community->id, 'partyminder_community_invitation_nonce' ); ?>
		<?php if ( $token ) : ?>
		<input type="hidden" name="invitation_token" value="<?php echo esc_attr( $token ); ?>" />
		<?php endif; ?>
		<input type="hidden" name="community_id" value="<?php echo esc_attr( $community->id ); ?>" />

		<!-- Community Information (REUSE event details section styling) -->
		<div class="pm-form-section pm-mb-4">
			<h4 class="pm-heading pm-heading-sm pm-mb-3"><?php _e( 'Community Details', 'partyminder' ); ?></h4>

			<!-- REUSE event info grid pattern -->
			<div class="pm-community-details">
				<div class="pm-community-info-item pm-mb-2">
					<span><strong><?php _e( 'Community:', 'partyminder' ); ?></strong> <?php echo esc_html( $community->name ); ?></span>
				</div>
				<div class="pm-community-info-item pm-mb-2">
					<span><strong><?php _e( 'Privacy:', 'partyminder' ); ?></strong> <?php echo esc_html( ucfirst( $community->visibility ) ); ?></span>
				</div>
				<div class="pm-community-info-item pm-mb-2">
					<span><strong><?php _e( 'Members:', 'partyminder' ); ?></strong> <?php echo intval( $community->member_count ); ?></span>
				</div>
				<?php if ( $invitation ) : ?>
				<div class="pm-community-info-item pm-mb-2">
					<span><strong><?php _e( 'Invited by:', 'partyminder' ); ?></strong> <?php echo esc_html( $invitation->inviter_name ?: __( 'Community Admin', 'partyminder' ) ); ?></span>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( $community->description ) : ?>
				<!-- REUSE event description styling -->
				<div class="pm-community-description pm-mt-3">
					<?php echo wpautop( esc_html( $community->description ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $invitation && $invitation->message ) : ?>
			<!-- REUSE event personal message pattern -->
			<div class="pm-invitation-message pm-mb-4">
				<h4><?php _e( 'Personal Message', 'partyminder' ); ?></h4>
				<em><?php echo wpautop( esc_html( $invitation->message ) ); ?></em>
			</div>
		<?php endif; ?>

		<?php if ( ! is_user_logged_in() ) : ?>
			<!-- Login required for community membership (unlike events) -->
			<div class="pm-alert pm-alert-info">
				<h4><?php _e( 'Account Required', 'partyminder' ); ?></h4>
				<p><?php _e( 'Community membership requires an account. Please login or create a free account to join this community.', 'partyminder' ); ?></p>
				<div class="pm-mt-3" style="text-align: center;">
					<a href="<?php echo wp_login_url( add_query_arg( 'join', '1', home_url( '/communities/' . $community->slug ) ) ); ?>" class="pm-btn pm-btn-primary">
						<?php _e( 'Login to Join', 'partyminder' ); ?>
					</a>
					<?php if ( get_option( 'users_can_register' ) ) : ?>
						<a href="<?php echo add_query_arg( 'redirect_to', urlencode( add_query_arg( 'join', '1', home_url( '/communities/' . $community->slug ) ) ), wp_registration_url() ); ?>" class="pm-btn pm-btn-secondary pm-ml-2">
							<?php _e( 'Create Account', 'partyminder' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php elseif ( $is_already_member ) : ?>
			<!-- Already a member -->
			<div class="pm-alert pm-alert-success">
				<h4><?php _e( 'Welcome Back!', 'partyminder' ); ?></h4>
				<p><?php _e( 'You are already a member of this community.', 'partyminder' ); ?></p>
				<div class="pm-text-center pm-mt-3">
					<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-btn pm-btn-primary">
						<?php _e( 'Go to Community', 'partyminder' ); ?>
					</a>
				</div>
			</div>
		<?php elseif ( ! $is_expired ) : ?>
			<!-- Join form for logged-in users only -->
			<div class="pm-form-section pm-mb-4">
				<h4 class="pm-heading pm-heading-sm pm-mb-3"><?php _e( 'Join This Community', 'partyminder' ); ?></h4>

				<!-- REUSE event form field styling -->
				<div class="pm-form-group pm-mb-3">
					<label for="pm-member-name" class="pm-form-label"><?php _e( 'Your Name', 'partyminder' ); ?> <span class="pm-required">*</span></label>
					<input type="text" id="pm-member-name" name="member_name" class="pm-form-input"
						value="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>"
						required />
				</div>

				<div class="pm-form-group pm-mb-3">
					<label for="pm-member-email" class="pm-form-label"><?php _e( 'Email Address', 'partyminder' ); ?> <span class="pm-required">*</span></label>
					<input type="email" id="pm-member-email" name="member_email" class="pm-form-input"
						value="<?php echo esc_attr( $current_user_email ); ?>"
						required />
				</div>

				<!-- Community-specific fields (optional) -->
				<div class="pm-form-group pm-mb-3">
					<label for="pm-member-bio" class="pm-form-label"><?php _e( 'Tell us about yourself (Optional)', 'partyminder' ); ?></label>
					<textarea id="pm-member-bio" name="member_bio" rows="2" class="pm-form-textarea"
						placeholder="<?php esc_attr_e( 'Share your interests, background, or what drew you to this community...', 'partyminder' ); ?>"></textarea>
				</div>
			</div>
		<?php endif; ?>

		<div class="pm-form-actions">
			<div id="pm-community-invitation-messages" class="pm-mb-3"></div>
		</div>
	</form>
</div>