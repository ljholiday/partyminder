/**
 * RSVP Modal JavaScript
 * Handles RSVP modal functionality using unified modal system
 */

window.PartyMinderRSVP = (function($) {
	'use strict';

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
	}

	function openModal(eventId, token = null) {
		const $modal = $('#pm-rsvp-modal');

		if ($modal.length === 0) {
			console.error('RSVP modal not found');
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

			const $form = $(this);
			const $submitBtn = $('#pm-submit-rsvp');
			const originalText = $submitBtn.text();

			$submitBtn.prop('disabled', true).text(partyminder_ajax.strings.loading || 'Submitting...');

			$.ajax({
				url: partyminder_ajax.ajax_url,
				type: 'POST',
				data: $form.serialize() + '&action=partyminder_rsvp&nonce=' + partyminder_ajax.nonce,
				success: function(response) {
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