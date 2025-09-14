/**
 * Manage Community Page JavaScript
 * Handles tab content loading, member management, invitations, and BlueSky integration
 */

jQuery(document).ready(function($) {
	const communityId = PartyMinderManageCommunity.community_id;
	const currentTab = PartyMinderManageCommunity.current_tab;
	const communityName = PartyMinderManageCommunity.community_name;
	const communitySlug = PartyMinderManageCommunity.community_slug;

	// Load appropriate tab content based on current tab
	if (currentTab === 'members') {
		loadCommunityMembers(communityId);
	} else if (currentTab === 'invitations') {
		loadCommunityInvitations(communityId);

		// Initialize BlueSky integration if enabled
		if (PartyMinderManageCommunity.at_protocol_enabled) {
			// Initialize shared BlueSky module
			PartyMinderBlueSky.init({
				ajax_url: PartyMinderManageCommunity.ajax_url,
				at_protocol_nonce: PartyMinderManageCommunity.at_protocol_nonce,
				context_id: communityId,
				context_type: 'community',
				strings: {
					loading_followers: 'Loading your BlueSky followers...',
					no_followers: PartyMinderManageCommunity.strings.no_followers || 'No followers found.',
					load_followers_failed: PartyMinderManageCommunity.strings.load_followers_failed || 'Failed to load followers.',
					network_error_followers: PartyMinderManageCommunity.strings.network_error_followers || 'Network error loading followers.',
					select_followers: PartyMinderManageCommunity.strings.select_followers || 'Please select at least one follower.',
					sending_invitations: PartyMinderManageCommunity.strings.sending_invitations || 'Sending invitations...',
					invitations_sent: PartyMinderManageCommunity.strings.invitations_sent || 'Invitations sent successfully!',
					invitations_failed: PartyMinderManageCommunity.strings.invitations_failed || 'Failed to send invitations.',
					network_error: PartyMinderManageCommunity.strings.network_error || 'Network error occurred.',
					send_invitations: PartyMinderManageCommunity.strings.send_invitations || 'Send Invitations',
					connecting: PartyMinderManageCommunity.strings.connecting || 'Connecting...',
					connection_failed: PartyMinderManageCommunity.strings.connection_failed || 'Connection failed.',
					connect_account: PartyMinderManageCommunity.strings.connect_account || 'Connect Account',
					confirm_disconnect: PartyMinderManageCommunity.strings.confirm_disconnect || 'Are you sure you want to disconnect your BlueSky account?',
					disconnected_successfully: PartyMinderManageCommunity.strings.disconnected_successfully || 'BlueSky account disconnected.',
					disconnect_failed: PartyMinderManageCommunity.strings.disconnect_failed || 'Failed to disconnect BlueSky account.',
					connection_failed_network: PartyMinderManageCommunity.strings.connection_failed_network || 'Network error during connection.'
				}
			});

			PartyMinderBlueSky.checkConnection();

			// Handle Bluesky buttons
			$('#manage-connect-bluesky-btn').on('click', PartyMinderBlueSky.showConnectModal);
			$('#manage-disconnect-bluesky-btn').on('click', PartyMinderBlueSky.disconnect);
			$('#create-invite-bluesky-btn').on('click', PartyMinderBlueSky.showFollowersModal);
		}
	}

	// Handle delete community form
	const deleteConfirmInput = $('#delete-confirm-name');
	const deleteBtn = $('#delete-community-btn');

	if (deleteConfirmInput.length && deleteBtn.length) {
		deleteConfirmInput.on('input', function() {
			deleteBtn.prop('disabled', this.value !== communityName);
		});
	}

	// Handle invitation form submission
	$('#send-invitation-form').on('submit', function(e) {
		e.preventDefault();

		const email = $('#invitation-email').val();
		const message = $('#invitation-message').val();

		if (!email) {
			alert(PartyMinderManageCommunity.strings.enter_email);
			return;
		}

		const $submitBtn = $(this).find('button[type="submit"]');
		const originalText = $submitBtn.text();
		$submitBtn.text(PartyMinderManageCommunity.strings.sending).prop('disabled', true);

		$.ajax({
			url: PartyMinderManageCommunity.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_send_invitation',
				community_id: communityId,
				email: email,
				message: message,
				nonce: PartyMinderManageCommunity.community_nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					// Clear form
					$('#invitation-email').val('');
					$('#invitation-message').val('');
					// Reload invitations list if we're on that tab
					if (currentTab === 'invitations') {
						loadCommunityInvitations(communityId);
					}
				} else {
					alert(response.data || PartyMinderManageCommunity.strings.invitation_failed);
				}
				$submitBtn.text(originalText).prop('disabled', false);
			},
			error: function() {
				alert(PartyMinderManageCommunity.strings.network_error);
				$submitBtn.text(originalText).prop('disabled', false);
			}
		});
	});

	// Load community members
	function loadCommunityMembers(communityId) {
		const $membersList = $('#members-list');
		if (!$membersList.length) return;

		$membersList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageCommunity.strings.loading_members + '</p></div>');

		$.ajax({
			url: PartyMinderManageCommunity.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_community_members',
				community_id: communityId,
				nonce: PartyMinderManageCommunity.community_nonce
			},
			success: function(response) {
				if (response.success && response.data.members_html) {
					$membersList.html(response.data.members_html);
				} else {
					$membersList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageCommunity.strings.no_members + '</p></div>');
				}
			},
			error: function() {
				$membersList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageCommunity.strings.error_loading_members + '</p></div>');
			}
		});
	}

	// Load community invitations
	function loadCommunityInvitations(communityId) {
		const $invitationsList = $('#invitations-list');
		if (!$invitationsList.length) return;

		$invitationsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageCommunity.strings.loading_invitations + '</p></div>');

		$.ajax({
			url: PartyMinderManageCommunity.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_community_invitations',
				community_id: communityId,
				nonce: PartyMinderManageCommunity.community_nonce
			},
			success: function(response) {
				if (response.success && response.data.invitations) {
					renderInvitationsList(response.data.invitations);
				} else {
					$invitationsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageCommunity.strings.no_invitations + '</p></div>');
				}
			},
			error: function() {
				$invitationsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageCommunity.strings.error_loading_invitations + '</p></div>');
			}
		});
	}

	// Render invitations list
	function renderInvitationsList(invitations) {
		const $invitationsList = $('#invitations-list');

		if (!invitations || invitations.length === 0) {
			$invitationsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageCommunity.strings.no_invitations + '</p></div>');
			return;
		}

		let html = '<div class="pm-invitation-list">';
		invitations.forEach(invitation => {
			const createdDate = new Date(invitation.created_at).toLocaleDateString();
			const expiresDate = new Date(invitation.expires_at).toLocaleDateString();

			html += `
				<div class="pm-invitation-item" data-invitation-id="${invitation.id}">
					<div class="pm-invitation-info">
						<div class="pm-invitation-details">
							<h4>${invitation.invited_email}</h4>
							<small>${PartyMinderManageCommunity.strings.invited_on} ${createdDate}</small>
							<br><small>${PartyMinderManageCommunity.strings.expires} ${expiresDate}</small>
							${invitation.message ? '<br><small><em>"' + invitation.message + '"</em></small>' : ''}
						</div>
					</div>
					<div class="pm-invitation-actions">
						<span class="pm-member-role pending">${PartyMinderManageCommunity.strings.pending}</span>
						<button class="pm-btn copy-invitation-btn" data-invitation-token="${invitation.invitation_token}" data-community-id="${invitation.community_id}">
							${PartyMinderManageCommunity.strings.copy_invite}
						</button>
						<button class="pm-btn pm-btn-danger cancel-invitation-btn" data-invitation-id="${invitation.id}" data-email="${invitation.invited_email}">
							${PartyMinderManageCommunity.strings.cancel}
						</button>
					</div>
				</div>
			`;
		});
		html += '</div>';

		$invitationsList.html(html);

		// Add event listeners for invitation actions
		attachInvitationActionListeners();
	}

	// Attach event listeners for member actions
	function attachMemberActionListeners() {
		// Promote buttons
		$(document).on('click', '.promote-btn', function() {
			const memberId = $(this).data('member-id');
			updateMemberRole(memberId, 'admin');
		});

		// Demote buttons
		$(document).on('click', '.demote-btn', function() {
			const memberId = $(this).data('member-id');
			updateMemberRole(memberId, 'member');
		});

		// Remove buttons
		$(document).on('click', '.remove-btn', function() {
			const memberId = $(this).data('member-id');
			const memberName = $(this).data('member-name');

			if (confirm(PartyMinderManageCommunity.strings.confirm_remove.replace('%s', memberName))) {
				removeMember(memberId);
			}
		});
	}

	// Attach event listeners for invitation actions
	function attachInvitationActionListeners() {
		// Copy invitation buttons
		$(document).on('click', '.copy-invitation-btn', function() {
			const token = $(this).data('invitation-token');
			const communityId = $(this).data('community-id');
			const invitationUrl = PartyMinderManageCommunity.home_url + '/communities/' + communitySlug + '?invitation=' + token + '&community=' + communityId;

			// Copy to clipboard
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(invitationUrl).then(() => {
					// Change button text temporarily
					const $btn = $(this);
					const originalText = $btn.text();
					$btn.text(PartyMinderManageCommunity.strings.copied);
					setTimeout(() => {
						$btn.text(originalText);
					}, 2000);
				}).catch(err => {
					console.error('Failed to copy: ', err);
					alert(PartyMinderManageCommunity.strings.copy_failed);
				});
			} else {
				// Fallback for older browsers
				const textArea = document.createElement('textarea');
				textArea.value = invitationUrl;
				document.body.appendChild(textArea);
				textArea.focus();
				textArea.select();
				try {
					document.execCommand('copy');
					const $btn = $(this);
					const originalText = $btn.text();
					$btn.text(PartyMinderManageCommunity.strings.copied);
					setTimeout(() => {
						$btn.text(originalText);
					}, 2000);
				} catch (err) {
					console.error('Fallback copy failed: ', err);
					alert(PartyMinderManageCommunity.strings.copy_failed);
				}
				document.body.removeChild(textArea);
			}
		});

		// Cancel invitation buttons
		$(document).on('click', '.cancel-invitation-btn', function() {
			const invitationId = $(this).data('invitation-id');
			const email = $(this).data('email');

			if (confirm(PartyMinderManageCommunity.strings.confirm_cancel_invitation.replace('%s', email))) {
				cancelInvitation(invitationId);
			}
		});
	}

	// Update member role
	function updateMemberRole(memberId, newRole) {
		$.ajax({
			url: PartyMinderManageCommunity.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_update_member_role',
				community_id: communityId,
				member_id: memberId,
				new_role: newRole,
				nonce: PartyMinderManageCommunity.community_nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					// Reload members list
					loadCommunityMembers(communityId);
				} else {
					alert(response.data || PartyMinderManageCommunity.strings.update_role_failed);
				}
			},
			error: function() {
				alert(PartyMinderManageCommunity.strings.network_error);
			}
		});
	}

	// Remove member
	function removeMember(memberId) {
		$.ajax({
			url: PartyMinderManageCommunity.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_remove_member',
				community_id: communityId,
				member_id: memberId,
				nonce: PartyMinderManageCommunity.community_nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					// Reload members list
					loadCommunityMembers(communityId);
				} else {
					alert(response.data || PartyMinderManageCommunity.strings.remove_member_failed);
				}
			},
			error: function() {
				alert(PartyMinderManageCommunity.strings.network_error);
			}
		});
	}

	// Cancel invitation
	function cancelInvitation(invitationId) {
		$.ajax({
			url: PartyMinderManageCommunity.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_cancel_invitation',
				community_id: communityId,
				invitation_id: invitationId,
				nonce: PartyMinderManageCommunity.community_nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					// Reload invitations list
					loadCommunityInvitations(communityId);
				} else {
					alert(response.data || PartyMinderManageCommunity.strings.cancel_invitation_failed);
				}
			},
			error: function() {
				alert(PartyMinderManageCommunity.strings.network_error);
			}
		});
	}

	// Community deletion confirmation
	window.confirmCommunityDeletion = function(event) {
		return confirm(PartyMinderManageCommunity.strings.confirm_delete.replace('%s', communityName));
	};

	// Initialize member action listeners
	attachMemberActionListeners();
});
