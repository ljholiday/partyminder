<?php
/**
 * Community Invitation Acceptance Page
 * REUSES event invitation system approach - shows communities page with auto-opening modal
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get invitation token from URL
$token = sanitize_text_field( $_GET['token'] ?? '' );

if ( ! $token ) {
	wp_safe_redirect( home_url( '/communities/' ) );
	exit;
}

// Validate token exists
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
$community_manager = new PartyMinder_Community_Manager();
$invitation = $community_manager->get_invitation_by_token( $token );

if ( ! $invitation ) {
	wp_safe_redirect( home_url( '/communities/' ) );
	exit;
}

// Display communities page with modal
echo '<div class="partyminder-content partyminder-communities-page">';
include PARTYMINDER_PLUGIN_DIR . 'templates/communities-unified-content.php';
echo '</div>';

// Include modal template
include PARTYMINDER_PLUGIN_DIR . 'templates/partials/modal-community-invitation.php';

// Auto-open modal with token (REUSE event RSVP auto-open pattern)
?>
<script>
jQuery(document).ready(function() {
	// Auto-open modal with token from URL
	const urlParams = new URLSearchParams(window.location.search);
	const token = urlParams.get('token');
	if (token && window.PartyMinderCommunityInvitation) {
		window.PartyMinderCommunityInvitation.openModal(token);
	}
});
</script>