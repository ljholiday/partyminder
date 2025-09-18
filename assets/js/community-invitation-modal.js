/**
 * Community Invitation Modal JavaScript
 * REUSES PartyMinderRSVP patterns and structure
 */

window.PartyMinderCommunityInvitation = (function($) {
	'use strict';

	let isSubmitting = false;

	function init() {
		bindEvents();
		checkAutoOpen(); // REUSE event auto-open pattern
	}

	// REUSE event checkAutoOpen pattern exactly
	function checkAutoOpen() {
		const urlParams = new URLSearchParams(window.location.search);
		const tokenParam = urlParams.get('token');
		const invitationParam = urlParams.get('invitation');
		const joinParam = urlParams.get('join');

		if (tokenParam || invitationParam) {
			const token = tokenParam || invitationParam;
			openModal(token);
		} else if (joinParam === '1') {
			// Get community ID from the join button data (same pattern as event RSVP)
			const $joinBtn = $('.pm-community-join-btn, .pm-btn[data-action="join"]');
			if ($joinBtn.length) {
				const communityId = $joinBtn.data('community-id');
				if (communityId) {
					openModal(null, communityId);
				}
			}
		}
	}

	function bindEvents() {
		// REUSE event modal patterns
		$(document).on('click', '.pm-community-invitation-btn', function(e) {
			e.preventDefault();
			const token = $(this).data('token');
			openModal(token);
		});

		// REUSE event modal close handlers
		$(document).on('click', '#pm-community-invitation-modal .pm-modal-close', closeModal);
		$(document).on('click', '#pm-community-invitation-modal .pm-modal-overlay', closeModal);

		// REUSE event footer submit pattern
		$(document).on('click', '#pm-accept-community-invitation', function(e) {
			e.preventDefault();
			const $form = $('#pm-community-invitation-form');
			if ($form.length) {
				$form.trigger('submit');
			}
		});
	}

	// REUSE event openModal pattern
	function openModal(token, communityId = null) {
		const $modal = $('#pm-community-invitation-modal');

		if ($modal.length === 0) {
			console.error('Community invitation modal not found');
			return;
		}

		// REUSE event modal show pattern
		$modal.show();
		$('body').addClass('pm-modal-open');

		if (token) {
			// Token-based invitation
			loadInvitationForm(token);
		} else if (communityId) {
			// Generic community join
			loadCommunityJoinForm(communityId);
		}
	}

	// REUSE event closeModal pattern exactly
	function closeModal() {
		const $modal = $('#pm-community-invitation-modal');
		$modal.hide();
		$('body').removeClass('pm-modal-open');
	}

	// REUSE event loadRSVPForm pattern
	function loadInvitationForm(token) {
		const $container = $('#pm-community-invitation-form-container');
		$container.html('<p>' + (partyminder_ajax.strings.loading || 'Loading...') + '</p>');

		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_load_community_invitation_form',
				token: token,
				nonce: partyminder_ajax.community_invitation_nonce
			},
			success: function(response) {
				if (response.success) {
					$container.html(response.data.html);

					// Hide join button if user needs to login or is already a member
					const hasForm = $container.find('#pm-community-invitation-form').length > 0;

					if (hasForm) {
						$('#pm-accept-community-invitation').prop('disabled', false).show();
						bindFormEvents();
					} else {
						$('#pm-accept-community-invitation').hide();
					}
				} else {
					$container.html('<div class="pm-alert pm-alert-error">' +
						(response.data || 'An error occurred.') + '</div>');
				}
			},
			error: function() {
				$container.html('<div class="pm-alert pm-alert-error">An error occurred. Please try again.</div>');
			}
		});
	}

	// Load community join form for generic join links (same pattern as invitation form)
	function loadCommunityJoinForm(communityId) {
		const $container = $('#pm-community-invitation-form-container');
		$container.html('<p>' + (partyminder_ajax.strings.loading || 'Loading...') + '</p>');

		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_load_community_join_form',
				community_id: communityId,
				nonce: partyminder_ajax.community_invitation_nonce
			},
			success: function(response) {
				if (response.success) {
					$container.html(response.data.html);

					// Hide join button if user needs to login or is already a member
					const hasForm = $container.find('#pm-community-invitation-form').length > 0;

					if (hasForm) {
						$('#pm-accept-community-invitation').prop('disabled', false).show();
						bindFormEvents();
					} else {
						$('#pm-accept-community-invitation').hide();
					}
				} else {
					$container.html('<div class="pm-alert pm-alert-error">' +
						(response.data || 'An error occurred.') + '</div>');
				}
			},
			error: function() {
				$container.html('<div class="pm-alert pm-alert-error">An error occurred. Please try again.</div>');
			}
		});
	}

	// REUSE event form submission pattern
	function bindFormEvents() {
		$(document).off('submit', '#pm-community-invitation-form').on('submit', '#pm-community-invitation-form', function(e) {
			e.preventDefault();

			// REUSE event double-submission prevention
			if (isSubmitting) {
				return false;
			}
			isSubmitting = true;

			const $form = $(this);
			const $submitBtn = $('#pm-accept-community-invitation');
			const originalText = $submitBtn.text();

			$submitBtn.prop('disabled', true).text(partyminder_ajax.strings.loading || 'Joining...');

			// REUSE event form data mapping pattern
			const formData = new FormData($form[0]);
			const ajaxData = {
				action: 'partyminder_accept_community_invitation',
				invitation_token: formData.get('invitation_token'),
				community_id: formData.get('community_id'),
				member_name: formData.get('member_name'),
				member_email: formData.get('member_email'),
				member_bio: formData.get('member_bio'),
				nonce: partyminder_ajax.nonce
			};

			$.ajax({
				url: partyminder_ajax.ajax_url,
				type: 'POST',
				data: ajaxData,
				success: function(response) {
					isSubmitting = false;
					if (response.success) {
						// REUSE event success pattern
						$('#pm-community-invitation-messages').html('<div class="pm-alert pm-alert-success">' +
							(response.data.message || 'Successfully joined community!') + '</div>');
						setTimeout(function() {
							if (response.data.redirect_url) {
								window.location.href = response.data.redirect_url;
							} else {
								closeModal();
							}
						}, 2000);
					} else {
						// REUSE event error handling
						$('#pm-community-invitation-messages').html('<div class="pm-alert pm-alert-error">' +
							(response.data || 'An error occurred.') + '</div>');
						$submitBtn.prop('disabled', false).text(originalText);
					}
				},
				error: function() {
					// REUSE event error handling
					isSubmitting = false;
					$('#pm-community-invitation-messages').html('<div class="pm-alert pm-alert-error">An error occurred. Please try again.</div>');
					$submitBtn.prop('disabled', false).text(originalText);
				}
			});
		});
	}

	// REUSE event public API pattern
	return {
		init: init,
		openModal: openModal,
		closeModal: closeModal
	};

})(jQuery);

// REUSE event initialization pattern
jQuery(document).ready(function() {
	PartyMinderCommunityInvitation.init();
});