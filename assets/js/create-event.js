/**
 * Create Event Page JavaScript
 * Handles form submission, date/time pickers, and Bluesky integration
 */

jQuery(document).ready(function($) {
	
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
});