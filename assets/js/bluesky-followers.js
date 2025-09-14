/**
 * Shared BlueSky Followers JavaScript
 * Used by both event and community management pages
 */

window.PartyMinderBlueSky = (function($) {
	'use strict';

	let config = {};

	// Initialize with configuration
	function init(userConfig) {
		config = $.extend({
			ajax_url: '',
			at_protocol_nonce: '',
			context_id: 0,
			context_type: 'event', // 'event' or 'community'
			strings: {
				loading_followers: 'Loading your BlueSky followers...',
				no_followers: 'No followers found.',
				load_followers_failed: 'Failed to load followers.',
				network_error_followers: 'Network error loading followers.',
				select_followers: 'Please select at least one follower.',
				sending_invitations: 'Sending invitations...',
				invitations_sent: 'Invitations sent successfully!',
				invitations_failed: 'Failed to send invitations.',
				network_error: 'Network error occurred.',
				send_invitations: 'Send Invitations',
				connecting: 'Connecting...',
				connection_failed: 'Connection failed.',
				connect_account: 'Connect Account',
				confirm_disconnect: 'Are you sure you want to disconnect your BlueSky account?',
				disconnected_successfully: 'BlueSky account disconnected.',
				disconnect_failed: 'Failed to disconnect BlueSky account.',
				connection_failed_network: 'Network error during connection.'
			}
		}, userConfig);
	}

	// Check BlueSky connection status
	function checkConnection() {
		$.ajax({
			url: config.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_check_bluesky_connection',
				nonce: config.at_protocol_nonce
			},
			success: function(response) {
				if (response.success && response.data.connected) {
					showConnected(response.data.handle);
				} else {
					showNotConnected();
				}
			},
			error: function() {
				showNotConnected();
			}
		});
	}

	// Show connected state
	function showConnected(handle) {
		$('#manage-bluesky-not-connected').hide();
		$('#manage-bluesky-connected').show();
		$('#manage-bluesky-handle').text(handle);
	}

	// Show not connected state
	function showNotConnected() {
		$('#manage-bluesky-not-connected').show();
		$('#manage-bluesky-connected').hide();
	}

	// Show connection modal
	function showConnectModal() {
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

			$submitBtn.prop('disabled', true).text(config.strings.connecting);

			$.ajax({
				url: config.ajax_url,
				type: 'POST',
				data: {
					action: 'partyminder_connect_bluesky',
					handle: handle,
					password: password,
					nonce: config.at_protocol_nonce
				},
				success: function(response) {
					if (response.success) {
						showConnected(response.handle);
						$modal.hide();
						$('body').removeClass('pm-modal-open');
						$('#pm-bluesky-connect-form')[0].reset();
						$('.pm-form-error').hide();
					} else {
						$('.pm-form-error').show().text(response.message || config.strings.connection_failed);
					}
				},
				error: function() {
					$('.pm-form-error').show().text(config.strings.connection_failed_network);
				},
				complete: function() {
					$submitBtn.prop('disabled', false).text(config.strings.connect_account);
				}
			});
		});
	}

	// Disconnect BlueSky account
	function disconnect() {
		if (!confirm(config.strings.confirm_disconnect)) {
			return;
		}

		$.ajax({
			url: config.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_disconnect_bluesky',
				nonce: config.at_protocol_nonce
			},
			success: function(response) {
				if (response.success) {
					showNotConnected();
					alert(config.strings.disconnected_successfully);
				} else {
					alert(response.message || config.strings.disconnect_failed);
				}
			},
			error: function() {
				alert(config.strings.network_error);
			}
		});
	}

	// Show followers modal
	function showFollowersModal() {
		const $modal = $('#pm-bluesky-followers-modal');
		$modal.show();
		$('body').addClass('pm-modal-open');
		loadFollowers();

		// Set up modal close handlers
		$modal.find('.pm-modal-close').off('click').on('click', function() {
			$modal.hide();
			$('body').removeClass('pm-modal-open');
		});

		// Set up send invitations handler
		$('#pm-send-followers-invites').off('click').on('click', function() {
			sendFollowerInvitations();
		});
	}

	// Load followers from BlueSky
	function loadFollowers() {
		$('#pm-bluesky-followers-loading').show();
		$('#pm-bluesky-followers-list').hide();
		$('#pm-bluesky-followers-error').hide();

		$.ajax({
			url: config.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_bluesky_contacts',
				nonce: config.at_protocol_nonce
			},
			success: function(response) {
				$('#pm-bluesky-followers-loading').hide();

				if (response.success && response.contacts) {
					displayFollowers(response.contacts);
					$('#pm-bluesky-followers-list').show();
				} else {
					showFollowersError(response.message || config.strings.load_followers_failed);
				}
			},
			error: function() {
				$('#pm-bluesky-followers-loading').hide();
				showFollowersError(config.strings.network_error_followers);
			}
		});
	}

	// Display followers list
	function displayFollowers(contacts) {
		const $container = $('#pm-followers-container');
		$container.empty();

		if (contacts.length === 0) {
			$container.html('<p class="pm-text-muted">' + config.strings.no_followers + '</p>');
			return;
		}

		contacts.forEach(function(contact) {
			const followerHtml = `
				<div class="pm-follower-item pm-py-2 pm-border-b">
					<label class="pm-form-label pm-flex pm-items-center">
						<input type="checkbox" class="pm-form-checkbox pm-follower-checkbox" value="${contact.handle}" data-display-name="${contact.display_name || contact.handle}">
						<div class="pm-ml-3">
							<div class="pm-follower-name pm-font-medium">${contact.display_name || contact.handle}</div>
							<div class="pm-text-sm pm-text-muted">@${contact.handle}</div>
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

	// Show followers error
	function showFollowersError(message) {
		$('#pm-followers-error-message').text(message);
		$('#pm-bluesky-followers-error').show();
	}

	// Send invitations to selected followers
	function sendFollowerInvitations() {
		const selectedFollowers = [];
		$('.pm-follower-checkbox:checked').each(function() {
			selectedFollowers.push({
				handle: $(this).val(),
				display_name: $(this).data('display-name')
			});
		});

		if (selectedFollowers.length === 0) {
			alert(config.strings.select_followers);
			return;
		}

		const $sendBtn = $('#pm-send-followers-invites');
		const originalText = $sendBtn.text();
		$sendBtn.prop('disabled', true).text(config.strings.sending_invitations);

		// Prepare AJAX data based on context type
		const ajaxData = {
			action: 'partyminder_send_bluesky_invitations',
			followers: selectedFollowers,
			nonce: config.at_protocol_nonce
		};

		// Add context-specific parameters
		if (config.context_type === 'event') {
			ajaxData.event_id = config.context_id;
		} else if (config.context_type === 'community') {
			ajaxData.community_id = config.context_id;
		}

		$.ajax({
			url: config.ajax_url,
			type: 'POST',
			data: ajaxData,
			success: function(response) {
				if (response.success) {
					alert(response.data.message || config.strings.invitations_sent);
					$('#pm-bluesky-followers-modal').hide();
					$('body').removeClass('pm-modal-open');

					// Reload invitations list based on context
					if (config.context_type === 'event' && typeof loadEventInvitations === 'function') {
						loadEventInvitations();
					} else if (config.context_type === 'community' && typeof loadCommunityInvitations === 'function') {
						loadCommunityInvitations(config.context_id);
					}
				} else {
					alert(response.data.message || response.message || config.strings.invitations_failed);
				}
			},
			error: function() {
				alert(config.strings.network_error);
			},
			complete: function() {
				$sendBtn.prop('disabled', false).text(originalText);
			}
		});
	}

	// Public API
	return {
		init: init,
		checkConnection: checkConnection,
		showConnectModal: showConnectModal,
		disconnect: disconnect,
		showFollowersModal: showFollowersModal
	};

})(jQuery);