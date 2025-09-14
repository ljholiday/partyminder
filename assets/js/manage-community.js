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
			checkManageBlueskyConnection();

			// Handle Bluesky buttons
			$('#manage-connect-bluesky-btn').on('click', showManageBlueskyConnectModal);
			$('#manage-disconnect-bluesky-btn').on('click', disconnectManageBluesky);
			$('#create-invite-bluesky-btn').on('click', showBlueskyFollowersModal);
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

	// BlueSky Integration Functions
	function checkManageBlueskyConnection() {
		$.ajax({
			url: PartyMinderManageCommunity.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_check_bluesky_connection',
				nonce: PartyMinderManageCommunity.at_protocol_nonce
			},
			success: function(response) {
				if (response.success && response.data.connected) {
					showManageBlueskyConnected(response.data.handle);
				} else {
					showManageBlueskyNotConnected();
				}
			}
		});
	}

	function showManageBlueskyConnected(handle) {
		$('#manage-bluesky-not-connected').hide();
		$('#manage-bluesky-connected').show();
		$('#manage-bluesky-handle').text(handle);
	}

	function showManageBlueskyNotConnected() {
		$('#manage-bluesky-not-connected').show();
		$('#manage-bluesky-connected').hide();
	}

	function showManageBlueskyConnectModal() {
		const $modal = $('#pm-bluesky-connect-modal');
		$modal.show();
		$('body').addClass('pm-modal-open');

		// Focus on handle input
		setTimeout(() => {
			$('.pm-bluesky-handle').focus();
		}, 100);

		// Set up close button handler
		$modal.find('.pm-modal-close').off('click').on('click', function() {
			$modal.hide();
			$('body').removeClass('pm-modal-open');
		});

		// Set up form submission handler
		$('#pm-bluesky-connect-form').off('submit').on('submit', function(e) {
			e.preventDefault();

			const handle = $('.pm-bluesky-handle').val();
			const password = $('.pm-bluesky-password').val();
			const $submitBtn = $('.pm-bluesky-connect-submit');

			$submitBtn.prop('disabled', true).text(PartyMinderManageCommunity.strings.connecting);

			$.ajax({
				url: PartyMinderManageCommunity.ajax_url,
				type: 'POST',
				data: {
					action: 'partyminder_connect_bluesky',
					handle: handle,
					password: password,
					nonce: PartyMinderManageCommunity.at_protocol_nonce
				},
				success: function(response) {
					if (response.success) {
						$modal.hide();
						$('body').removeClass('pm-modal-open');
						showManageBlueskyConnected(response.data.handle);
						$('#pm-bluesky-connect-form')[0].reset();
						$('.pm-form-error').hide();
					} else {
						$('.pm-form-error').show().text(response.data || PartyMinderManageCommunity.strings.connection_failed);
					}
					$submitBtn.prop('disabled', false).text(PartyMinderManageCommunity.strings.connect_account);
				},
				error: function() {
					$('.pm-form-error').show().text(PartyMinderManageCommunity.strings.connection_failed_network);
					$submitBtn.prop('disabled', false).text(PartyMinderManageCommunity.strings.connect_account);
				}
			});
		});
	}

	function disconnectManageBluesky() {
		if (!confirm(PartyMinderManageCommunity.strings.confirm_disconnect)) {
			return;
		}

		$.ajax({
			url: PartyMinderManageCommunity.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_disconnect_bluesky',
				nonce: PartyMinderManageCommunity.at_protocol_nonce
			},
			success: function(response) {
				if (response.success) {
					showManageBlueskyNotConnected();
					alert(PartyMinderManageCommunity.strings.disconnected_successfully);
				} else {
					alert(response.data || PartyMinderManageCommunity.strings.disconnect_failed);
				}
			},
			error: function() {
				alert(PartyMinderManageCommunity.strings.network_error);
			}
		});
	}

	function showBlueskyFollowersModal() {
		const $modal = $('#pm-bluesky-followers-modal');
		$modal.show();
		$('body').addClass('pm-modal-open');
		loadBlueskyFollowersForCommunity();

		// Set up modal close handlers
		$modal.find('.pm-modal-close').off('click').on('click', function() {
			$modal.hide();
			$('body').removeClass('pm-modal-open');
		});

		// Set up send invitations handler
		$('#pm-send-followers-invites').off('click').on('click', function() {
			const selectedFollowers = [];
			$('.pm-follower-checkbox:checked').each(function() {
				const $followerItem = $(this).closest('.pm-follower-item');
				selectedFollowers.push({
					handle: $(this).val(),
					display_name: $followerItem.find('.pm-follower-name').text(),
					avatar: $followerItem.find('.pm-follower-avatar').attr('src') || ''
				});
			});

			if (selectedFollowers.length === 0) {
				alert(PartyMinderManageCommunity.strings.select_followers);
				return;
			}

			const $sendBtn = $(this);
			$sendBtn.prop('disabled', true).text(PartyMinderManageCommunity.strings.sending_invitations);

			$.ajax({
				url: PartyMinderManageCommunity.ajax_url,
				type: 'POST',
				data: {
					action: 'partyminder_send_community_bluesky_invitations',
					community_id: communityId,
					followers: selectedFollowers,
					nonce: PartyMinderManageCommunity.community_nonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message || PartyMinderManageCommunity.strings.invitations_sent);
						$modal.hide();
						$('body').removeClass('pm-modal-open');
						// Reload invitations list
						loadCommunityInvitations(communityId);
					} else {
						alert(response.data || PartyMinderManageCommunity.strings.invitations_failed);
					}
					$sendBtn.prop('disabled', false).text(PartyMinderManageCommunity.strings.send_invitations);
				},
				error: function() {
					alert(PartyMinderManageCommunity.strings.network_error);
					$sendBtn.prop('disabled', false).text(PartyMinderManageCommunity.strings.send_invitations);
				}
			});
		});
	}

	function loadBlueskyFollowersForCommunity() {
		$('#pm-bluesky-followers-loading').show();
		$('#pm-bluesky-followers-list').hide();
		$('#pm-bluesky-followers-error').hide();

		$.ajax({
			url: PartyMinderManageCommunity.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_bluesky_contacts',
				nonce: PartyMinderManageCommunity.at_protocol_nonce
			},
			success: function(response) {
				$('#pm-bluesky-followers-loading').hide();

				if (response.success && response.data.contacts) {
					displayBlueskyFollowersForCommunity(response.data.contacts);
					$('#pm-bluesky-followers-list').show();
				} else {
					$('#pm-bluesky-followers-error').show();
					$('#pm-followers-error-message').text(response.data || PartyMinderManageCommunity.strings.load_followers_failed);
				}
			},
			error: function() {
				$('#pm-bluesky-followers-loading').hide();
				$('#pm-bluesky-followers-error').show();
				$('#pm-followers-error-message').text(PartyMinderManageCommunity.strings.network_error_followers);
			}
		});
	}

	function displayBlueskyFollowersForCommunity(contacts) {
		const $container = $('#pm-followers-container');
		$container.empty();

		if (contacts.length === 0) {
			$container.html('<p class="pm-text-muted">' + PartyMinderManageCommunity.strings.no_followers + '</p>');
			return;
		}

		contacts.forEach(function(contact) {
			const followerHtml = `
				<div class="pm-follower-item pm-flex pm-gap-3 pm-p-3 pm-border-b">
					<label class="pm-flex pm-gap-3 pm-flex-1 pm-cursor-pointer">
						<input type="checkbox" class="pm-follower-checkbox pm-form-checkbox" value="${contact.handle}">
						<div class="pm-flex pm-gap-3 pm-flex-1">
							${contact.avatar ? `<img src="${contact.avatar}" alt="${contact.display_name}" class="pm-follower-avatar pm-w-10 pm-h-10 pm-rounded-full">` : `<div class="pm-follower-avatar pm-w-10 pm-h-10 pm-rounded-full pm-bg-primary pm-flex pm-items-center pm-justify-center pm-text-white pm-font-bold">${contact.display_name.charAt(0).toUpperCase()}</div>`}
							<div class="pm-flex-1">
								<div class="pm-follower-name pm-font-medium">${contact.display_name}</div>
								<div class="pm-text-muted pm-text-sm">@${contact.handle}</div>
							</div>
						</div>
					</label>
				</div>
			`;
			$container.append(followerHtml);
		});

		// Set up select all functionality
		const $selectAllCheckbox = $('#pm-select-all-followers');
		const $followerCheckboxes = $container.find('.pm-follower-checkbox');
		const $sendBtn = $('#pm-send-followers-invites');

		$selectAllCheckbox.off('change').on('change', function() {
			$followerCheckboxes.prop('checked', this.checked);
			updateSendButton();
		});

		$followerCheckboxes.off('change').on('change', updateSendButton);

		function updateSendButton() {
			const checkedCount = $container.find('.pm-follower-checkbox:checked').length;
			$sendBtn.prop('disabled', checkedCount === 0);
		}

		updateSendButton();
	}

	// Community deletion confirmation
	window.confirmCommunityDeletion = function(event) {
		return confirm(PartyMinderManageCommunity.strings.confirm_delete.replace('%s', communityName));
	};

	// Initialize member action listeners
	attachMemberActionListeners();
});