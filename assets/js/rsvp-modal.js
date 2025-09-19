/**
 * RSVP Modal JavaScript
 * Handles RSVP modal functionality using unified modal system
 */

window.PartyMinderRSVP = (function($) {
	'use strict';

	let isSubmitting = false;

	function init() {
		bindEvents();
		checkAutoOpen();
	}

	function checkAutoOpen() {
		const urlParams = new URLSearchParams(window.location.search);
		const rsvpParam = urlParams.get('rsvp');
		const tokenParam = urlParams.get('token');

		if (rsvpParam === '1' || tokenParam) {
			// Get event ID from the RSVP button data
			const $rsvpBtn = $('.pm-rsvp-btn');
			if ($rsvpBtn.length) {
				const eventId = $rsvpBtn.data('event-id');
				if (eventId) {
					openModal(eventId, tokenParam);
				}
			}
		}
	}

	function bindEvents() {
		// Open modal button handler
		$(document).on('click', '.pm-rsvp-btn', function(e) {
			e.preventDefault();
			const eventId = $(this).data('event-id');
			openModal(eventId);
		});

		// Modal close handlers
		$(document).on('click', '#pm-rsvp-modal .pm-modal-close', closeModal);
		$(document).on('click', '#pm-rsvp-modal .pm-modal-overlay', closeModal);

		// Footer submit button handler
		$(document).on('click', '#pm-submit-rsvp', function(e) {
			e.preventDefault();
			const $form = $('#pm-modal-rsvp-form');
			if ($form.length) {
				$form.trigger('submit');
			}
		});
	}

	function openModal(eventId, token = null) {
		const $modal = $('#pm-rsvp-modal');

		if ($modal.length === 0) {
			return;
		}

		// Show modal
		$modal.show();
		$('body').addClass('pm-modal-open');

		// Load RSVP form content
		loadRSVPForm(eventId, token);
	}

	function closeModal() {
		const $modal = $('#pm-rsvp-modal');
		$modal.hide();
		$('body').removeClass('pm-modal-open');
	}

	function loadRSVPForm(eventId, token = null) {
		const $container = $('#pm-rsvp-form-container');
		$container.html('<p>' + (partyminder_ajax.strings.loading || 'Loading...') + '</p>');

		const ajaxData = {
			action: 'partyminder_load_rsvp_form',
			event_id: eventId,
			nonce: partyminder_ajax.rsvp_form_nonce
		};

		if (token) {
			ajaxData.token = token;
		}

		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: ajaxData,
			success: function(response) {
				if (response.success) {
					$container.html(response.data.html);
					$('#pm-submit-rsvp').prop('disabled', false);
					bindFormEvents();
				} else {
					$container.html('<div class="pm-alert pm-alert-error">' +
						(response.data || partyminder_ajax.strings.error || 'An error occurred.') +
						'</div>');
				}
			},
			error: function() {
				$container.html('<div class="pm-alert pm-alert-error">' +
					(partyminder_ajax.strings.error || 'An error occurred. Please try again.') +
					'</div>');
			}
		});
	}

	function bindFormEvents() {
		// Handle RSVP form submission via AJAX
		$(document).off('submit', '#pm-modal-rsvp-form').on('submit', '#pm-modal-rsvp-form', function(e) {
			e.preventDefault();

			// Prevent double submissions
			if (isSubmitting) {
				return false;
			}
			isSubmitting = true;

			const $form = $(this);
			const $submitBtn = $('#pm-submit-rsvp');
			const originalText = $submitBtn.text();

			$submitBtn.prop('disabled', true).text(partyminder_ajax.strings.loading || 'Submitting...');

			// Get form data and map field names
			const formData = new FormData($form[0]);
			const ajaxData = {
				action: 'partyminder_rsvp',
				event_id: formData.get('event_id'),
				name: formData.get('guest_name'),
				email: formData.get('guest_email'),
				status: formData.get('rsvp_status'),
				dietary: formData.get('dietary_restrictions'),
				notes: formData.get('guest_notes'),
				invitation_source: formData.get('invitation_source'),
				existing_guest_id: formData.get('existing_guest_id'),
				nonce: partyminder_ajax.nonce
			};

			$.ajax({
				url: partyminder_ajax.ajax_url,
				type: 'POST',
				data: ajaxData,
				success: function(response) {
					isSubmitting = false;
					if (response.success) {
						$('#pm-rsvp-form-messages').html('<div class="pm-alert pm-alert-success">' +
							(response.data.message || partyminder_ajax.strings.success) + '</div>');
						setTimeout(closeModal, 2000);
					} else {
						$('#pm-rsvp-form-messages').html('<div class="pm-alert pm-alert-error">' +
							(response.data || partyminder_ajax.strings.error) + '</div>');
						$submitBtn.prop('disabled', false).text(originalText);
					}
				},
				error: function() {
					isSubmitting = false;
					$('#pm-rsvp-form-messages').html('<div class="pm-alert pm-alert-error">' +
						(partyminder_ajax.strings.error || 'An error occurred. Please try again.') + '</div>');
					$submitBtn.prop('disabled', false).text(originalText);
				}
			});
		});
	}

	// Public API
	return {
		init: init,
		openModal: openModal,
		closeModal: closeModal
	};

})(jQuery);

// Initialize when document is ready
jQuery(document).ready(function() {
	PartyMinderRSVP.init();
});