<?php
/**
 * Community Invitation Acceptance Page
 * Handles invitation token processing and community joining
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

$community_manager = new PartyMinder_Community_Manager();

// Get invitation token from URL
$token        = sanitize_text_field( $_GET['token'] ?? '' );
$message      = '';
$message_type = '';
$community    = null;
$invitation   = null;

if ( ! $token ) {
	$message      = __( 'No invitation token provided.', 'partyminder' );
	$message_type = 'error';
} else {
	// Get invitation by token
	global $wpdb;
	$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';
	$invitation        = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT i.*, c.name as community_name, c.slug as community_slug, m.display_name as inviter_name
         FROM $invitations_table i
         LEFT JOIN {$wpdb->prefix}partyminder_communities c ON i.community_id = c.id
         LEFT JOIN {$wpdb->prefix}partyminder_community_members m ON i.invited_by_member_id = m.id
         WHERE i.invitation_token = %s",
			$token
		)
	);

	if ( ! $invitation ) {
		$message      = __( 'Invalid invitation token.', 'partyminder' );
		$message_type = 'error';
	} elseif ( $invitation->status !== 'pending' ) {
		$message      = __( 'This invitation has already been processed.', 'partyminder' );
		$message_type = 'error';
	} elseif ( strtotime( $invitation->expires_at ) < time() ) {
		$message      = __( 'This invitation has expired.', 'partyminder' );
		$message_type = 'error';
	} else {
		// Valid invitation - get community details
		$community = $community_manager->get_community( $invitation->community_id );

		if ( ! $community ) {
			$message      = __( 'The community for this invitation no longer exists.', 'partyminder' );
			$message_type = 'error';
		}
	}
}

// Handle form submission (invitation acceptance)
if ( $_POST && $invitation && $community && wp_verify_nonce( $_POST['invitation_nonce'], 'accept_invitation_' . $token ) ) {
	$current_user = wp_get_current_user();

	if ( ! $current_user->ID ) {
		$message      = __( 'You must be logged in to accept this invitation.', 'partyminder' );
		$message_type = 'error';
	} else {
		// Check if user is already a member
		if ( $community_manager->is_member( $community->id, $current_user->ID ) ) {
			$message      = __( 'You are already a member of this community.', 'partyminder' );
			$message_type = 'error';
		} else {
			// Add user to community
			$result = $community_manager->add_member(
				$community->id,
				array(
					'user_id'      => $current_user->ID,
					'email'        => $current_user->user_email,
					'display_name' => $current_user->display_name,
					'role'         => 'member',
					'status'       => 'active',
				)
			);

			if ( is_wp_error( $result ) ) {
				$message      = $result->get_error_message();
				$message_type = 'error';
			} else {
				// Mark invitation as accepted
				$wpdb->update(
					$invitations_table,
					array(
						'status'       => 'accepted',
						'responded_at' => current_time( 'mysql' ),
					),
					array( 'id' => $invitation->id ),
					array( '%s', '%s' ),
					array( '%d' )
				);

				$message      = sprintf( __( 'Welcome to %s! You have successfully joined the community.', 'partyminder' ), $community->name );
				$message_type = 'success';

				// Redirect to community page after a delay
				echo '<script>setTimeout(function() { window.location.href = "' . home_url( '/communities/' . $community->slug ) . '"; }, 3000);</script>';
			}
		}
	}
}

// Get styling options
$primary_color   = get_option( 'partyminder_primary_color', '#667eea' );
$secondary_color = get_option( 'partyminder_secondary_color', '#764ba2' );

// Set up template variables
$page_title       = __( 'Community Invitation', 'partyminder' );
$page_description = __( 'You\'ve been invited to join a community', 'partyminder' );

// Main content
ob_start();
?>
<div class="partyminder-invitation-accept">
	<!-- Page Header -->
	<div class="invitation-card">
		<div class="invitation-header">
			<div class="invitation-icon">📨</div>
			<h1 class="invitation-title"><?php _e( 'Community Invitation', 'partyminder' ); ?></h1>
			<p class="invitation-subtitle"><?php _e( 'You\'ve been invited to join a community', 'partyminder' ); ?></p>
		</div>
		
		<div class="invitation-body">
			<?php if ( $message ) : ?>
				<div class="message-box message-<?php echo esc_attr( $message_type ); ?>">
					<?php echo esc_html( $message ); ?>
				</div>
			<?php endif; ?>
			
			<?php if ( $invitation && $community && $message_type !== 'error' ) : ?>
				<div class="pm-invitation-details">
					<h4><?php _e( 'Community Details', 'partyminder' ); ?></h4>
					<p><strong><?php _e( 'Community:', 'partyminder' ); ?></strong> <?php echo esc_html( $community->name ); ?></p>
					<p><strong><?php _e( 'Privacy:', 'partyminder' ); ?></strong> <?php echo esc_html( ucfirst( $community->privacy ) ); ?></p>
					<p><strong><?php _e( 'Invited by:', 'partyminder' ); ?></strong> <?php echo esc_html( $invitation->inviter_name ?: __( 'Unknown', 'partyminder' ) ); ?></p>
					<p><strong><?php _e( 'Expires:', 'partyminder' ); ?></strong> <?php echo date( 'F j, Y g:i A', strtotime( $invitation->expires_at ) ); ?></p>
				</div>
				
				<?php if ( $community->description ) : ?>
					<div class="pm-invitation-details">
						<h4><?php _e( 'About This Community', 'partyminder' ); ?></h4>
						<p><?php echo wpautop( esc_html( $community->description ) ); ?></p>
					</div>
				<?php endif; ?>
				
				<?php if ( $invitation->message ) : ?>
					<div class="invitation-message">
						<h4><?php _e( 'Personal Message', 'partyminder' ); ?></h4>
						<em><?php echo wpautop( esc_html( $invitation->message ) ); ?></em>
					</div>
				<?php endif; ?>
				
				<?php if ( ! is_user_logged_in() ) : ?>
					<div class="login-prompt">
						<h4><?php _e( 'Login Required', 'partyminder' ); ?></h4>
						<p><?php _e( 'You need to be logged in to accept this invitation.', 'partyminder' ); ?></p>
						<a href="<?php echo wp_login_url( home_url( '/communities/join?token=' . urlencode( $token ) ) ); ?>" class="pm-btn">
							<span></span> <?php _e( 'Login to Accept', 'partyminder' ); ?>
						</a>
					</div>
				<?php elseif ( $message_type === 'success' ) : ?>
					<div style="text-align: center;">
						<p><?php _e( 'Redirecting to your new community...', 'partyminder' ); ?></p>
						<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-btn">
							<span>🏘️</span> <?php _e( 'Go to Community', 'partyminder' ); ?>
						</a>
					</div>
				<?php else : ?>
					<?php
					$current_user      = wp_get_current_user();
					$is_already_member = $community_manager->is_member( $community->id, $current_user->ID );
					?>
					
					<?php if ( $is_already_member ) : ?>
						<div class="already-member">
							<h4><?php _e( 'Already a Member', 'partyminder' ); ?></h4>
							<p><?php _e( 'You are already a member of this community.', 'partyminder' ); ?></p>
							<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-btn">
								<span>🏘️</span> <?php _e( 'Go to Community', 'partyminder' ); ?>
							</a>
						</div>
					<?php else : ?>
						<form method="post" style="text-align: center;">
							<?php wp_nonce_field( 'accept_invitation_' . $token, 'invitation_nonce' ); ?>
							<h4><?php _e( 'Accept Invitation', 'partyminder' ); ?></h4>
							<p><?php _e( 'Click below to join this community.', 'partyminder' ); ?></p>
							<button type="submit" class="pm-btn">
								<span>✅</span> <?php _e( 'Accept & Join Community', 'partyminder' ); ?>
							</button>
						</form>
					<?php endif; ?>
				<?php endif; ?>
			<?php else : ?>
				<div style="text-align: center;">
					<p><?php _e( 'Return to communities to explore other options.', 'partyminder' ); ?></p>
					<a href="<?php echo home_url( '/communities' ); ?>" class="pm-btn pm-btn">
						<span>🏘️</span> <?php _e( 'Browse Communities', 'partyminder' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
$content = ob_get_clean();

// Include base template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-page.php';