/**
 * Manage Event Page JavaScript
 * Handles form submission, date/time pickers, guest management, and invitations
 */

jQuery(document).ready(function($) {
	// Initialize Bluesky connection check on invites tab
	if (PartyMinderManageEvent.at_protocol_enabled && PartyMinderManageEvent.current_tab === 'invites') {
		checkManageBlueskyConnection();
		
		// Handle Bluesky buttons
		$('#manage-connect-bluesky-btn').on('click', showManageBlueskyConnectModal);
		$('#manage-disconnect-bluesky-btn').on('click', disconnectManageBluesky);
		$('#create-invite-bluesky-btn').on('click', showBlueskyFollowersModal);
	}

	// Initialize Flatpickr date and time pickers (if on settings tab)
	if (typeof flatpickr !== 'undefined' && PartyMinderManageEvent.current_tab === 'settings') {
		initializeDatePickers();
	}
	
	// Handle event settings form submission
	$('#manage-event-settings-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.html();
		
		// Disable submit button and show loading
		$submitBtn.prop('disabled', true).html('<span>⏳</span> ' + PartyMinderManageEvent.updating_text);
		
		// Check if we have file uploads
		const hasFiles = $form.find('input[type="file"]').get().some(input => input.files.length > 0);
		const $progress = $('#event-upload-progress');
		const $progressFill = $('.pm-progress-fill');
		const $message = $('#event-upload-message');
		
		if (hasFiles) {
			$progress.show();
			$message.empty();
		}
		
		// Prepare form data properly for file uploads
		const formData = new FormData(this);
		formData.append('action', 'partyminder_update_event');
		formData.append('event_id', PartyMinderManageEvent.event_id);
		formData.append('partyminder_edit_event_nonce', PartyMinderManageEvent.update_nonce);
		
		$.ajax({
			url: PartyMinderManageEvent.ajax_url,
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
					// Show success message
					const $successMsg = $('<div class="pm-alert pm-alert-success pm-mb-4">' + 
						PartyMinderManageEvent.update_success_text + 
						'</div>');
					$form.before($successMsg);
					setTimeout(() => $successMsg.fadeOut(() => $successMsg.remove()), 5000);
					
					// Hide progress bar
					if (hasFiles) {
						$progress.hide();
					}
				} else {
					// Show error message
					$form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4>' + PartyMinderManageEvent.fix_issues_text + '</h4><ul><li>' + (response.data || PartyMinderManageEvent.unknown_error_text) + '</li></ul></div>');
					
					// Scroll to top to show error message
					$('html, body').animate({scrollTop: 0}, 500);
				}
			},
			error: function() {
				$form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4>' + PartyMinderManageEvent.error_text + '</h4><p>' + PartyMinderManageEvent.network_error_text + '</p></div>');
				
				// Scroll to top to show error message
				$('html, body').animate({scrollTop: 0}, 500);
			},
			complete: function() {
				// Re-enable submit button
				$submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// Handle invitation form submission (invites tab)
	$('#send-invitation-form').on('submit', function(e) {
		e.preventDefault();
		
		const email = $('#invitation-email').val().trim();
		const message = $('#invitation-message').val().trim();
		
		if (!email) {
			alert(PartyMinderManageEvent.enter_email_text);
			return;
		}
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.text();
		
		$submitBtn.prop('disabled', true).text(PartyMinderManageEvent.sending_text);
		
		$.ajax({
			url: PartyMinderManageEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_send_event_invitation',
				event_id: PartyMinderManageEvent.event_id,
				email: email,
				message: message,
				nonce: PartyMinderManageEvent.nonce
			},
			success: function(response) {
				if (response.success) {
					// Clear form
					$form[0].reset();
					
					// Show success message
					const $successMsg = $('<div class="pm-alert pm-alert-success pm-mb-4">' + 
						PartyMinderManageEvent.invitation_sent_text + 
						'</div>');
					$form.before($successMsg);
					setTimeout(() => $successMsg.fadeOut(() => $successMsg.remove()), 3000);
					
					// Reload invitations list
					loadEventInvitations();
				} else {
					alert(response.data || PartyMinderManageEvent.invitation_failed_text);
				}
			},
			error: function() {
				alert(PartyMinderManageEvent.network_error_text);
			},
			complete: function() {
				$submitBtn.prop('disabled', false).text(originalText);
			}
		});
	});
	
	// Handle invitation cancellation
	$(document).on('click', '.cancel-event-invitation', function() {
		if (!confirm(PartyMinderManageEvent.cancel_invitation_confirm_text)) {
			return;
		}
		
		const invitationId = $(this).data('invitation-id');
		const $button = $(this);
		const originalText = $button.text();
		
		$button.prop('disabled', true).text(PartyMinderManageEvent.cancelling_text);
		
		$.ajax({
			url: PartyMinderManageEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_cancel_event_invitation',
				event_id: PartyMinderManageEvent.event_id,
				invitation_id: invitationId,
				nonce: PartyMinderManageEvent.nonce
			},
			success: function(response) {
				if (response.success) {
					// Reload invitations list
					loadEventInvitations();
				} else {
					alert(response.data || PartyMinderManageEvent.cancel_failed_text);
					$button.prop('disabled', false).text(originalText);
				}
			},
			error: function() {
				alert(PartyMinderManageEvent.network_error_text);
				$button.prop('disabled', false).text(originalText);
			}
		});
	});

	// Handle delete event form
	const deleteConfirmInput = $('#delete-confirm-name');
	const deleteBtn = $('#delete-event-btn');
	
	if (deleteConfirmInput && deleteBtn) {
		deleteConfirmInput.on('input', function() {
			deleteBtn.prop('disabled', this.value !== PartyMinderManageEvent.event_title);
		});
	}

	// Delete Event functionality
	window.deleteEvent = function() {
		if (!confirm(PartyMinderManageEvent.delete_confirm_text)) {
			return;
		}
		
		const $deleteBtn = $('#delete-event-btn');
		const originalText = $deleteBtn.html();
		
		// Disable button and show loading
		$deleteBtn.prop('disabled', true).html(PartyMinderManageEvent.deleting_text);
		
		$.ajax({
			url: PartyMinderManageEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_delete_event',
				event_id: PartyMinderManageEvent.event_id,
				nonce: PartyMinderManageEvent.nonce
			},
			success: function(response) {
				if (response.success) {
					// Show success message briefly then redirect
					$deleteBtn.html(PartyMinderManageEvent.deleted_text);
					setTimeout(function() {
						window.location.href = response.data.redirect_url || PartyMinderManageEvent.my_events_url;
					}, 1000);
				} else {
					alert(response.data || PartyMinderManageEvent.delete_failed_text);
					$deleteBtn.prop('disabled', false).html(originalText);
				}
			},
			error: function() {
				alert(PartyMinderManageEvent.network_error_text);
				$deleteBtn.prop('disabled', false).html(originalText);
			}
		});
	};

	// Load appropriate tab content on page load
	if (PartyMinderManageEvent.current_tab === 'guests') {
		loadEventGuests();
	} else if (PartyMinderManageEvent.current_tab === 'invites') {
		loadEventInvitations();
	}

	// Initialize date pickers for settings tab
	function initializeDatePickers() {
		// Initialize start date picker
		const startDatePicker = flatpickr('#start_date', {
			dateFormat: 'Y-m-d',
			minDate: 'today',
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

	// Load event guests (for guests tab)
	function loadEventGuests() {
		const $guestsList = $('#guests-list');
		if (!$guestsList.length) return;
		
		$guestsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageEvent.loading_guests_text + '</p></div>');
		
		$.ajax({
			url: PartyMinderManageEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_event_guests',
				event_id: PartyMinderManageEvent.event_id,
				nonce: PartyMinderManageEvent.nonce
			},
			success: function(response) {
				if (response.success && response.data.guests_html) {
					$guestsList.html(response.data.guests_html);
				} else {
					$guestsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageEvent.no_guests_text + '</p></div>');
				}
			},
			error: function() {
				$guestsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageEvent.error_loading_guests_text + '</p></div>');
			}
		});
	}

	// Load event invitations (for invites tab)
	function loadEventInvitations() {
		const $invitationsList = $('#invitations-list');
		if (!$invitationsList.length) return;
		
		$invitationsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageEvent.loading_invitations_text + '</p></div>');
		
		$.ajax({
			url: PartyMinderManageEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_event_invitations',
				event_id: PartyMinderManageEvent.event_id,
				nonce: PartyMinderManageEvent.nonce
			},
			success: function(response) {
				if (response.success && response.data.html) {
					$invitationsList.html(response.data.html);
				} else {
					$invitationsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageEvent.no_invitations_text + '</p></div>');
				}
			},
			error: function() {
				$invitationsList.html('<div class="pm-loading-placeholder"><p>' + PartyMinderManageEvent.error_loading_invitations_text + '</p></div>');
			}
		});
	}

	// Copy invitation URL to clipboard
	window.copyInvitationUrl = function(url) {
		if (navigator.clipboard) {
			navigator.clipboard.writeText(url).then(function() {
				alert(PartyMinderManageEvent.invitation_copied_text);
			});
		} else {
			// Fallback for older browsers
			const textArea = document.createElement('textarea');
			textArea.value = url;
			document.body.appendChild(textArea);
			textArea.select();
			document.execCommand('copy');
			document.body.removeChild(textArea);
			alert(PartyMinderManageEvent.invitation_copied_text);
		}
	};

	// Bluesky Integration Functions for Manage Event Page
	function checkManageBlueskyConnection() {
		$.ajax({
			url: PartyMinderManageEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_check_bluesky_connection',
				nonce: PartyMinderManageEvent.at_protocol_nonce
			},
			success: function(response) {
				if (response.success && response.data.connected) {
					showManageBlueskyConnected(response.data.handle);
				} else {
					showManageBlueskyNotConnected();
				}
			},
			error: function() {
				showManageBlueskyNotConnected();
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
		const modal = $('#pm-bluesky-connect-modal');
		modal.show();
		$('body').addClass('pm-modal-open');
		
		// Focus on handle input
		setTimeout(() => {
			$('.pm-bluesky-handle').focus();
		}, 100);
		
		// Set up close button handler
		modal.find('.pm-modal-close').off('click').on('click', function() {
			modal.hide();
			$('body').removeClass('pm-modal-open');
		});
		
		// Set up form submission handler
		$('#pm-bluesky-connect-form').off('submit').on('submit', function(e) {
			e.preventDefault();
			
			const handle = $('.pm-bluesky-handle').val();
			const password = $('.pm-bluesky-password').val();
			const $submitBtn = $('.pm-bluesky-connect-submit');
			
			$submitBtn.prop('disabled', true).text(PartyMinderManageEvent.connecting_text);
			
			$.ajax({
				url: PartyMinderManageEvent.ajax_url,
				type: 'POST',
				data: {
					action: 'partyminder_connect_bluesky',
					handle: handle,
					password: password,
					nonce: PartyMinderManageEvent.at_protocol_nonce
				},
				success: function(response) {
					if (response.success) {
						showManageBlueskyConnected(response.handle);
						modal.hide();
						$('body').removeClass('pm-modal-open');
					} else {
						alert(response.message || PartyMinderManageEvent.connection_failed_text);
					}
				},
				error: function() {
					alert(PartyMinderManageEvent.connection_failed_text);
				},
				complete: function() {
					$submitBtn.prop('disabled', false).text(PartyMinderManageEvent.connect_account_text);
				}
			});
		});
	}
	
	function disconnectManageBluesky() {
		if (!confirm(PartyMinderManageEvent.disconnect_confirm_text)) {
			return;
		}
		
		$.ajax({
			url: PartyMinderManageEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_disconnect_bluesky',
				nonce: PartyMinderManageEvent.at_protocol_nonce
			},
			success: function(response) {
				showManageBlueskyNotConnected();
			}
		});
	}
	
	// Bluesky Followers Modal Functions
	function showBlueskyFollowersModal() {
		const modal = $('#pm-bluesky-followers-modal');
		modal.show();
		$('body').addClass('pm-modal-open');
		loadBlueskyFollowers();
	}
	
	function loadBlueskyFollowers() {
		$('#pm-bluesky-followers-loading').show();
		$('#pm-bluesky-followers-list').hide();
		$('#pm-bluesky-followers-error').hide();
		
		$.ajax({
			url: PartyMinderManageEvent.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_bluesky_contacts',
				nonce: PartyMinderManageEvent.at_protocol_nonce
			},
			success: function(response) {
				$('#pm-bluesky-followers-loading').hide();
				
				if (response.success && response.contacts) {
					displayBlueskyFollowers(response.contacts);
					$('#pm-bluesky-followers-list').show();
				} else {
					showFollowersError(response.message || PartyMinderManageEvent.failed_load_followers_text);
				}
			},
			error: function() {
				$('#pm-bluesky-followers-loading').hide();
				showFollowersError(PartyMinderManageEvent.network_error_followers_text);
			}
		});
	}
	
	function displayBlueskyFollowers(contacts) {
		const container = $('#pm-followers-container');
		container.empty();
		
		if (contacts.length === 0) {
			container.html('<p class="pm-text-muted">' + PartyMinderManageEvent.no_followers_text + '</p>');
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
				const $button = $(this);
				const originalText = $button.text();
				
				$button.prop('disabled', true).text(PartyMinderManageEvent.sending_text);
				
				$.ajax({
					url: PartyMinderManageEvent.ajax_url,
					type: 'POST',
					data: {
						action: 'partyminder_send_bluesky_invitations',
						event_id: PartyMinderManageEvent.event_id,
						followers: selectedFollowers,
						nonce: PartyMinderManageEvent.at_protocol_nonce
					},
					success: function(response) {
						if (response.success) {
							alert(PartyMinderManageEvent.invitations_sent_text);
							$('#pm-bluesky-followers-modal').hide();
							$('body').removeClass('pm-modal-open');
							// Refresh invitations list
							loadEventInvitations();
						} else {
							alert(response.message || PartyMinderManageEvent.invitation_failed_text);
						}
					},
					error: function(xhr, status, error) {
						alert(PartyMinderManageEvent.network_error_text);
					},
					complete: function() {
						$button.prop('disabled', false).text(originalText);
					}
				});
			}
		});
	});
});