/**
 * Create Event Page JavaScript
 * Handles form submission, date/time pickers, and Bluesky integration
 */

jQuery(document).ready(function($) {
	// Initialize Bluesky connection check on page load
	if (PartyMinderCreateEvent.at_protocol_enabled) {
		checkCreateBlueskyConnection();
		
		// Handle Bluesky buttons
		$('#create-connect-bluesky-btn').on('click', showCreateBlueskyConnectModal);
		$('#create-disconnect-bluesky-btn').on('click', disconnectCreateBluesky);
		$('#create-invite-bluesky-btn').on('click', showBlueskyFollowersModal);
	}
	
	// Handle event form submission
	$('#partyminder-event-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.html();
		
		// Disable submit button and show loading
		$submitBtn.prop('disabled', true).html('<span>⏳</span> ' + PartyMinderCreateEvent.creating_text);
		
		// Check if we have file uploads
		const hasFiles = $form.find('input[type="file"]').get().some(input => input.files.length > 0);
		const $progress = $('#create-event-upload-progress');
		const $progressFill = $progress.find('.pm-progress-fill');
		const $message = $('#create-event-upload-message');
		
		if (hasFiles) {
			$progress.show();
			$message.empty();
		}
		
		// Prepare form data properly for file uploads
		const formData = new FormData(this);
		formData.append('action', 'partyminder_create_event');
		
		$.ajax({
			url: PartyMinderCreateEvent.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			xhr: function() {
				const xhr = new window.XMLHttpRequest();
				if (hasFiles) {
					xhr.upload.addEventListener('progress', function(evt) {
						if (evt.lengthComputable) {
							const percentComplete = (evt.loaded / evt.total) * 100;
							$progressFill.css('width', percentComplete + '%');
						}
					}, false);
				}
				return xhr;
			},
			success: function(response) {
				if (response.success) {
					// Redirect to success page
					window.location.href = PartyMinderCreateEvent.success_url + '?partyminder_created=1';
				} else {
					// Show error message
					$form.before('<div class="partyminder-errors" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 4px;"><h4>' + PartyMinderCreateEvent.fix_issues_text + '</h4><ul><li>' + (response.data || PartyMinderCreateEvent.unknown_error_text) + '</li></ul></div>');
					
					// Scroll to top to show error message
					$('html, body').animate({scrollTop: 0}, 500);
				}
			},
			error: function() {
				$form.before('<div class="partyminder-errors" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 4px;"><h4>' + PartyMinderCreateEvent.error_text + '</h4><p>' + PartyMinderCreateEvent.network_error_text + '</p></div>');
				
				// Scroll to top to show error message
				$('html, body').animate({scrollTop: 0}, 500);
			},
			complete: function() {
				// Re-enable submit button
				$submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});
	
	// Initialize Flatpickr date and time pickers
	if (typeof flatpickr !== 'undefined') {
		// Initialize start date picker
		const startDatePicker = flatpickr('#start_date', {
			dateFormat: 'Y-m-d',
			minDate: 'today',
			defaultDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // Next week
			onChange: function(selectedDates, dateStr) {
				// Update end date minimum to start date
				if (selectedDates.length > 0 && endDatePicker) {
					endDatePicker.set('minDate', selectedDates[0]);
					if (!$('#end_date').val()) {
						endDatePicker.setDate(selectedDates[0]);
					}
				}
			}
		});

		// Initialize start time picker
		const startTimePicker = flatpickr('#start_time', {
			enableTime: true,
			noCalendar: true,
			dateFormat: 'H:i',
			defaultDate: '18:00',
			time_24hr: false
		});

		// Initialize end date picker
		const endDatePicker = flatpickr('#end_date', {
			dateFormat: 'Y-m-d',
			minDate: 'today'
		});

		// Initialize end time picker
		const endTimePicker = flatpickr('#end_time', {
			enableTime: true,
			noCalendar: true,
			dateFormat: 'H:i',
			defaultDate: '20:00',
			time_24hr: false
		});

		// Initialize recurrence end date picker
		const recurrenceEndPicker = flatpickr('#recurrence_end_date', {
			dateFormat: 'Y-m-d',
			minDate: 'today'
		});

		// All day event toggle
		$('#all_day').on('change', function() {
			const isAllDay = $(this).is(':checked');
			$('#start_time_group, #end_time_group').toggle(!isAllDay);
			$('#start_time, #end_time').prop('required', !isAllDay);
		});

		// Recurrence type handling
		$('#recurrence_type').on('change', function() {
			const recurrenceType = $(this).val();
			
			// Show/hide recurrence options
			$('#recurrence_end').toggle(recurrenceType !== '');
			$('#custom_recurrence').toggle(recurrenceType === 'custom');
			
			// Show monthly options when months selected in custom
			if (recurrenceType === 'monthly') {
				$('#monthly_options').show();
			} else {
				$('#monthly_options').hide();
			}
		});

		// Custom recurrence unit handling
		$('#recurrence_unit').on('change', function() {
			const unit = $(this).val();
			$('#monthly_options').toggle(unit === 'months');
		});
	}
	
	// Bluesky Integration Functions for Create Event Page
	function checkCreateBlueskyConnection() {
		$.ajax({
			url: PartyMinderCreateEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_check_bluesky_connection',
				nonce: PartyMinderCreateEvent.at_protocol_nonce
			},
			success: function(response) {
				if (response.success && response.data.connected) {
					showCreateBlueskyConnected(response.data.handle);
				} else {
					showCreateBlueskyNotConnected();
				}
			},
			error: function() {
				showCreateBlueskyNotConnected();
			}
		});
	}
	
	function showCreateBlueskyConnected(handle) {
		$('#create-bluesky-not-connected').hide();
		$('#create-bluesky-connected').show();
		$('#create-bluesky-handle').text(handle);
	}
	
	function showCreateBlueskyNotConnected() {
		$('#create-bluesky-not-connected').show();
		$('#create-bluesky-connected').hide();
	}
	
	function showCreateBlueskyConnectModal() {
		const modal = $('#pm-bluesky-connect-modal');
		modal.show();
		$('body').addClass('pm-modal-open');
		
		// Focus on handle input
		setTimeout(() => {
			$('.pm-bluesky-handle').focus();
		}, 100);
		
		// Set up close button handler
		modal.find('.pm-modal-close').off('click').on('click', function() {
			modal.attr('aria-hidden', 'true').hide();
			$('body').removeClass('pm-modal-open');
		});
		
		// Set up form submission handler
		$('#pm-bluesky-connect-form').off('submit').on('submit', function(e) {
			e.preventDefault();
			
			const handle = $('.pm-bluesky-handle').val();
			const password = $('.pm-bluesky-password').val();
			const $submitBtn = $('.pm-bluesky-connect-submit');
			
			$submitBtn.prop('disabled', true).text(PartyMinderCreateEvent.connecting_text);
			
			$.ajax({
				url: PartyMinderCreateEvent.ajax_url,
				type: 'POST',
				data: {
					action: 'partyminder_connect_bluesky',
					handle: handle,
					password: password,
					nonce: PartyMinderCreateEvent.at_protocol_nonce
				},
				success: function(response) {
					if (response.success) {
						showCreateBlueskyConnected(response.handle);
						modal.hide();
						$('body').removeClass('pm-modal-open');
					} else {
						alert(response.message || PartyMinderCreateEvent.connection_failed_text);
					}
				},
				error: function() {
					alert(PartyMinderCreateEvent.connection_failed_text);
				},
				complete: function() {
					$submitBtn.prop('disabled', false).text(PartyMinderCreateEvent.connect_account_text);
				}
			});
		});
	}
	
	function disconnectCreateBluesky() {
		if (!confirm(PartyMinderCreateEvent.disconnect_confirm_text)) {
			return;
		}
		
		$.ajax({
			url: PartyMinderCreateEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_disconnect_bluesky',
				nonce: PartyMinderCreateEvent.at_protocol_nonce
			},
			success: function(response) {
				showCreateBlueskyNotConnected();
			}
		});
	}
	
	// Bluesky Followers Modal Functions
	function showBlueskyFollowersModal() {
		const modal = $('#pm-bluesky-followers-modal');
		modal.show();
		$('body').addClass('pm-modal-open');
		
		// Load followers
		loadBlueskyFollowers();
	}
	
	function loadBlueskyFollowers() {
		$('#pm-bluesky-followers-loading').show();
		$('#pm-bluesky-followers-list').hide();
		$('#pm-bluesky-followers-error').hide();
		
		$.ajax({
			url: PartyMinderCreateEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_bluesky_contacts',
				nonce: PartyMinderCreateEvent.at_protocol_nonce
			},
			success: function(response) {
				$('#pm-bluesky-followers-loading').hide();
				
				if (response.success && response.contacts) {
					displayBlueskyFollowers(response.contacts);
					$('#pm-bluesky-followers-list').show();
				} else {
					showFollowersError(response.message || PartyMinderCreateEvent.failed_load_followers_text);
				}
			},
			error: function() {
				$('#pm-bluesky-followers-loading').hide();
				showFollowersError(PartyMinderCreateEvent.network_error_followers_text);
			}
		});
	}
	
	function displayBlueskyFollowers(contacts) {
		const container = $('#pm-followers-container');
		container.empty();
		
		if (contacts.length === 0) {
			container.html('<p class="pm-text-muted">' + PartyMinderCreateEvent.no_followers_text + '</p>');
			return;
		}
		
		contacts.forEach(function(contact) {
			const followerHtml = `
				<div class="pm-follower-item pm-py-2 pm-border-b">
					<label class="pm-form-label pm-flex pm-items-center">
						<input type="checkbox" class="pm-form-checkbox pm-follower-checkbox" value="${contact.handle}" data-display-name="${contact.display_name || contact.handle}">
						<div class="pm-ml-3">
							<div class="pm-font-medium">${contact.display_name || contact.handle}</div>
							<div class="pm-text-sm pm-text-muted">@${contact.handle}</div>
						</div>
					</label>
				</div>
			`;
			container.append(followerHtml);
		});
		
		// Update send button state when checkboxes change
		$('.pm-follower-checkbox').on('change', updateSendButtonState);
		$('#pm-select-all-followers').on('change', function() {
			const isChecked = $(this).is(':checked');
			$('.pm-follower-checkbox').prop('checked', isChecked);
			updateSendButtonState();
		});
		
		updateSendButtonState();
	}
	
	function showFollowersError(message) {
		$('#pm-followers-error-message').text(message);
		$('#pm-bluesky-followers-error').show();
	}
	
	function updateSendButtonState() {
		const checkedCount = $('.pm-follower-checkbox:checked').length;
		$('#pm-send-followers-invites').prop('disabled', checkedCount === 0);
	}
	
	// Handle followers modal events
	$(document).ready(function() {
		// Close followers modal
		$('#pm-bluesky-followers-modal .pm-modal-close').on('click', function() {
			$('#pm-bluesky-followers-modal').hide();
			$('body').removeClass('pm-modal-open');
		});
		
		// Send invitations
		$('#pm-send-followers-invites').on('click', function() {
			const selectedFollowers = [];
			$('.pm-follower-checkbox:checked').each(function() {
				selectedFollowers.push({
					handle: $(this).val(),
					display_name: $(this).data('display-name')
				});
			});
			
			if (selectedFollowers.length > 0) {
				// TODO: Implement actual invitation sending
				alert(PartyMinderCreateEvent.invitation_todo_text);
				// For now, just close the modal
				$('#pm-bluesky-followers-modal').hide();
				$('body').removeClass('pm-modal-open');
			}
		});
	});
});