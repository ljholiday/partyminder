/**
 * ======================================================
 *  PartyMinder – An Actually Social Network
 *  Plan real events. Connect with real people. Share real life.
 * ======================================================
 *
 *  File: enhanced-date-picker.js
 *  Description: Advanced date/time picker with ranges, repeats, and all-day options
 *  Author: PartyMinder Team
 *
 *  Branding Notes:
 *  - Voice: Human, optimistic, simple
 *  - Vocabulary: Events, Conversations, Communities
 *  - Never use: posts, tweets, status
 *
 *  Features:
 *  - Date and date range selection
 *  - Start and end time pickers
 *  - All-day event toggle
 *  - Repeat intervals with end dates
 *  - Smart defaults and validation
 *
 * ======================================================
 */

jQuery(document).ready(function($) {
    'use strict';

    // Only initialize if we have the date picker section
    if ($('.pm-date-picker-section').length === 0) {
        return;
    }

    // Initialize enhanced date picker
    function initEnhancedDatePicker() {
        let startDatePicker, endDatePicker, repeatEndPicker;
        let startTimePicker, endTimePicker;

        // Initialize start date picker
        startDatePicker = flatpickr('#start_date', {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            minDate: 'today',
            onChange: function(selectedDates, dateStr) {
                // Update end date minimum
                if (endDatePicker && selectedDates[0]) {
                    endDatePicker.set('minDate', selectedDates[0]);
                    // Auto-set end date if multi-day is enabled and no end date set
                    if ($('#date_range').is(':checked') && !$('#end_date').val()) {
                        endDatePicker.setDate(selectedDates[0]);
                    }
                }
                // Update repeat end minimum
                if (repeatEndPicker && selectedDates[0]) {
                    repeatEndPicker.set('minDate', selectedDates[0]);
                }
                updateHiddenEventDate();
            }
        });

        // Initialize end date picker
        endDatePicker = flatpickr('#end_date', {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            minDate: 'today',
            onChange: function() {
                updateHiddenEventDate();
            }
        });

        // Initialize start time picker
        startTimePicker = flatpickr('#start_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            altInput: true,
            altFormat: 'h:i K',
            time_24hr: false,
            minuteIncrement: 15,
            onChange: function(selectedDates, dateStr) {
                // Auto-set end time if not set (3 hours later)
                if (!$('#end_time').val() && selectedDates[0]) {
                    const endTime = new Date(selectedDates[0]);
                    endTime.setHours(endTime.getHours() + 3);
                    endTimePicker.setDate(endTime);
                }
                updateHiddenEventDate();
            }
        });

        // Initialize end time picker
        endTimePicker = flatpickr('#end_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            altInput: true,
            altFormat: 'h:i K',
            time_24hr: false,
            minuteIncrement: 15,
            onChange: function() {
                updateHiddenEventDate();
            }
        });

        // Initialize repeat end date picker
        repeatEndPicker = flatpickr('#repeat_end', {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            minDate: 'today'
        });

        return {
            startDate: startDatePicker,
            endDate: endDatePicker,
            startTime: startTimePicker,
            endTime: endTimePicker,
            repeatEnd: repeatEndPicker
        };
    }

    // Handle all-day toggle
    function handleAllDayToggle() {
        $('#all_day').on('change', function() {
            const isAllDay = $(this).is(':checked');
            $('.pm-time-group').toggle(!isAllDay);
            $('.pm-time-input').prop('required', !isAllDay);
            updateHiddenEventDate();
        });
    }

    // Handle date range toggle
    function handleDateRangeToggle() {
        $('#date_range').on('change', function() {
            const isRange = $(this).is(':checked');
            $('.pm-end-date-group, .pm-end-time-group').toggle(isRange);
            if (!isRange) {
                $('#end_date, #end_time').val('');
            }
            updateHiddenEventDate();
        });
    }

    // Handle repeat options
    function handleRepeatOptions() {
        $('#repeat_type').on('change', function() {
            const repeatType = $(this).val();
            $('.pm-repeat-end-group').toggle(repeatType !== 'none');
            if (repeatType === 'none') {
                $('#repeat_end').val('');
            }
        });
    }

    // Update hidden event_date field for backward compatibility
    function updateHiddenEventDate() {
        const startDate = $('#start_date').val();
        const startTime = $('#start_time').val();
        const isAllDay = $('#all_day').is(':checked');

        if (startDate) {
            let eventDateTime = startDate;
            if (!isAllDay && startTime) {
                eventDateTime += ' ' + startTime;
            } else if (isAllDay) {
                eventDateTime += ' 00:00';
            }
            $('#event_date').val(eventDateTime);
        }
    }

    // Form validation
    function validateDatePicker() {
        // Find the form containing the date picker
        const $form = $('.pm-date-picker-section').closest('form');
        
        $form.on('submit', function(e) {
            let isValid = true;
            let errors = [];

            // Validate start date
            const startDate = $('#start_date').val();
            if (!startDate) {
                errors.push('Start date is required.');
                $('#start_date').addClass('pm-field-error');
                isValid = false;
            } else {
                $('#start_date').removeClass('pm-field-error');
            }

            // Validate start time for non-all-day events
            const isAllDay = $('#all_day').is(':checked');
            const startTime = $('#start_time').val();
            if (!isAllDay && !startTime) {
                errors.push('Start time is required for timed events.');
                $('#start_time').addClass('pm-field-error');
                isValid = false;
            } else {
                $('#start_time').removeClass('pm-field-error');
            }

            // Validate end date/time consistency
            const isRange = $('#date_range').is(':checked');
            const endDate = $('#end_date').val();
            const endTime = $('#end_time').val();
            
            if (isRange && endDate) {
                const startDateTime = new Date(startDate + 'T' + (startTime || '00:00'));
                const endDateTime = new Date(endDate + 'T' + (endTime || '23:59'));
                
                if (endDateTime <= startDateTime) {
                    errors.push('End date/time must be after start date/time.');
                    $('#end_date, #end_time').addClass('pm-field-error');
                    isValid = false;
                } else {
                    $('#end_date, #end_time').removeClass('pm-field-error');
                }
            }

            // Show errors if any
            if (!isValid) {
                e.preventDefault();
                showValidationErrors(errors);
            }
        });
    }

    // Show validation errors
    function showValidationErrors(errors) {
        // Remove existing errors
        $('.pm-date-picker-errors').remove();
        
        // Create error display
        let errorHtml = '<div class="pm-date-picker-errors pm-alert pm-alert-error pm-mb-4"><ul>';
        errors.forEach(function(error) {
            errorHtml += '<li>' + error + '</li>';
        });
        errorHtml += '</ul></div>';
        
        // Add to date picker section
        $('.pm-date-picker-section').prepend(errorHtml);
        
        // Scroll to date picker section
        $('html, body').animate({
            scrollTop: $('.pm-date-picker-section').offset().top - 100
        }, 500);
    }

    // Initialize everything
    function init() {
        const pickers = initEnhancedDatePicker();
        handleAllDayToggle();
        handleDateRangeToggle();
        handleRepeatOptions();
        validateDatePicker();

        // Set initial state
        $('#all_day').trigger('change');
        $('#date_range').trigger('change');
        $('#repeat_type').trigger('change');
        
        // Initial update of hidden field
        updateHiddenEventDate();

        // Store pickers globally for debugging
        window.partyminderDatePickers = pickers;
    }

    // Initialize when DOM is ready
    init();

    // Re-initialize if content is dynamically loaded
    $(document).on('partyminder:datepicker:reload', init);
});