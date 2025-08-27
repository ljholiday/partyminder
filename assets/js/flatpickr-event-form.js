/**
 * Flatpickr Event Form JavaScript
 * Handles enhanced date/time picking with recurrence for event forms
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize Flatpickr instances
    function initFlatpickr() {
        // Start Date picker
        const startDatePicker = flatpickr('#start_date', {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            minDate: 'today',
            onChange: function(selectedDates, dateStr) {
                // Update end date picker minimum
                if (endDatePicker && selectedDates[0]) {
                    endDatePicker.set('minDate', selectedDates[0]);
                }
            }
        });

        // End Date picker
        const endDatePicker = flatpickr('#end_date', {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            minDate: 'today'
        });

        // Start Time picker
        const startTimePicker = flatpickr('#start_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: false,
            onChange: function(selectedDates, dateStr) {
                // Update end time picker minimum if same day
                if (endTimePicker && selectedDates[0]) {
                    const startDate = $('#start_date').val();
                    const endDate = $('#end_date').val();
                    if (startDate === endDate) {
                        endTimePicker.set('minTime', dateStr);
                    }
                }
            }
        });

        // End Time picker
        const endTimePicker = flatpickr('#end_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: false
        });

        return {
            startDate: startDatePicker,
            endDate: endDatePicker,
            startTime: startTimePicker,
            endTime: endTimePicker
        };
    }

    // Handle all-day toggle
    function handleAllDayToggle() {
        const $allDayCheckbox = $('#all_day');
        const $timeFields = $('.pm-time-field');

        $allDayCheckbox.on('change', function() {
            if (this.checked) {
                $timeFields.hide().find('input').prop('disabled', true);
            } else {
                $timeFields.show().find('input').prop('disabled', false);
            }
        });

        // Initialize state
        $allDayCheckbox.trigger('change');
    }

    // Handle recurrence type changes
    function handleRecurrenceType() {
        const $recurrenceType = $('#recurrence_type');
        const $recurrenceOptions = $('.pm-recurrence-options');

        $recurrenceType.on('change', function() {
            const selectedType = this.value;
            
            // Hide all options first
            $recurrenceOptions.hide();
            
            // Show relevant options
            if (selectedType !== 'none') {
                $('.pm-recurrence-interval').show();
                
                switch (selectedType) {
                    case 'weekly':
                        $('.pm-weekly-options').show();
                        break;
                    case 'monthly':
                        $('.pm-monthly-options').show();
                        break;
                    case 'custom':
                        $('.pm-custom-options').show();
                        break;
                }
            }
        });

        // Initialize state
        $recurrenceType.trigger('change');
    }

    // Handle monthly recurrence options
    function handleMonthlyRecurrence() {
        const $monthlyType = $('#monthly_type');
        const $weekdayOptions = $('.pm-monthly-weekday-options');

        $monthlyType.on('change', function() {
            if (this.value === 'weekday') {
                $weekdayOptions.show();
            } else {
                $weekdayOptions.hide();
            }
        });

        // Initialize state
        $monthlyType.trigger('change');
    }

    // Form validation
    function validateEventForm() {
        const $form = $('#pm-event-form');
        
        $form.on('submit', function(e) {
            let isValid = true;
            let errors = [];

            // Validate required fields
            const $title = $('#event_title');
            const $startDate = $('#start_date');
            const $hostEmail = $('#host_email');

            if (!$title.val().trim()) {
                errors.push('Event title is required.');
                $title.addClass('pm-field-error');
                isValid = false;
            } else {
                $title.removeClass('pm-field-error');
            }

            if (!$startDate.val()) {
                errors.push('Start date is required.');
                $startDate.addClass('pm-field-error');
                isValid = false;
            } else {
                $startDate.removeClass('pm-field-error');
            }

            if (!$hostEmail.val().trim()) {
                errors.push('Host email is required.');
                $hostEmail.addClass('pm-field-error');
                isValid = false;
            } else {
                $hostEmail.removeClass('pm-field-error');
            }

            // Validate time fields for non-all-day events
            const $allDay = $('#all_day');
            if (!$allDay.is(':checked')) {
                const $startTime = $('#start_time');
                if (!$startTime.val()) {
                    errors.push('Start time is required for timed events.');
                    $startTime.addClass('pm-field-error');
                    isValid = false;
                } else {
                    $startTime.removeClass('pm-field-error');
                }
            }

            // Validate end date/time is after start
            const $endDate = $('#end_date');
            const $endTime = $('#end_time');
            
            if ($endDate.val()) {
                const startDateTime = new Date($startDate.val() + 'T' + ($('#start_time').val() || '00:00'));
                const endDateTime = new Date($endDate.val() + 'T' + ($endTime.val() || '23:59'));
                
                if (endDateTime <= startDateTime) {
                    errors.push('End date/time must be after start date/time.');
                    $endDate.addClass('pm-field-error');
                    $endTime.addClass('pm-field-error');
                    isValid = false;
                } else {
                    $endDate.removeClass('pm-field-error');
                    $endTime.removeClass('pm-field-error');
                }
            }

            // Show errors if any
            if (!isValid) {
                e.preventDefault();
                let errorHtml = '<div class="pm-alert pm-alert-error"><ul>';
                errors.forEach(function(error) {
                    errorHtml += '<li>' + error + '</li>';
                });
                errorHtml += '</ul></div>';
                
                // Remove existing errors
                $('.pm-form-errors').remove();
                
                // Add new errors
                $form.prepend('<div class="pm-form-errors">' + errorHtml + '</div>');
                
                // Scroll to top of form
                $('html, body').animate({
                    scrollTop: $form.offset().top - 100
                }, 500);
            }
        });
    }

    // Initialize everything
    function init() {
        // Only run on event form pages
        if ($('#pm-event-form').length === 0) {
            return;
        }

        const pickers = initFlatpickr();
        handleAllDayToggle();
        handleRecurrenceType();
        handleMonthlyRecurrence();
        validateEventForm();

        // Store pickers globally for debugging
        window.partyminderPickers = pickers;
    }

    // Initialize when DOM is ready
    init();

    // Re-initialize if content is dynamically loaded
    $(document).on('partyminder:form:reload', init);
});