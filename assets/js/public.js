/**
 * PartyMinder Public JavaScript
 */

(function($) {
    'use strict';

    // Initialize PartyMinder
    window.PartyMinder = window.PartyMinder || {};

    $(document).ready(function() {
        PartyMinder.init();
    });

    /**
     * Initialize PartyMinder functionality
     */
    PartyMinder.init = function() {
        this.initEventForms();
        this.initRSVPForms();
        this.initEventsList();
        this.initSharing();
        this.initValidation();
    };

    /**
     * Initialize event creation forms
     */
    PartyMinder.initEventForms = function() {
        // Event form submission
        $('.partyminder-event-form').on('submit', function(e) {
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            const originalText = $submitBtn.html();
            $submitBtn.html('<span class="spinner"></span> ' + partyminder_ajax.strings.loading);
            
            // Validate form
            if (!PartyMinder.validateEventForm($form)) {
                $submitBtn.prop('disabled', false).html(originalText);
                e.preventDefault();
                return false;
            }
            
            // Form will submit normally
        });

        // Real-time validation
        $('.partyminder-event-form input[required], .partyminder-event-form select[required]').on('blur', function() {
            PartyMinder.validateField($(this));
        });

        // Date validation - ensure future date
        $('input[name="event_date"]').on('change', function() {
            const selectedDate = new Date($(this).val());
            const now = new Date();
            
            if (selectedDate <= now) {
                PartyMinder.showFieldError($(this), 'Please select a future date and time.');
            } else {
                PartyMinder.clearFieldError($(this));
            }
        });
    };

    /**
     * Initialize RSVP forms
     */
    PartyMinder.initRSVPForms = function() {
        // RSVP form submission
        $('.partyminder-rsvp-form').on('submit', function(e) {
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            const originalText = $submitBtn.html();
            $submitBtn.html('<span class="spinner"></span> ' + partyminder_ajax.strings.loading);
            
            // Validate form
            if (!PartyMinder.validateRSVPForm($form)) {
                $submitBtn.prop('disabled', false).html(originalText);
                e.preventDefault();
                return false;
            }
            
            // Form will submit normally
        });

        // RSVP status change handler
        $('input[name="rsvp_status"]').on('change', function() {
            const status = $(this).val();
            const $additionalInfo = $('.additional-info');
            
            // Update visual selection
            $('.rsvp-option').removeClass('selected');
            $(this).closest('.rsvp-option').addClass('selected');
            
            // Show/hide additional info based on status
            if (status === 'declined') {
                $additionalInfo.slideUp(300);
                // Clear non-required fields when declining
                $additionalInfo.find('input, textarea').val('');
            } else {
                $additionalInfo.slideDown(300);
            }
        });

        // Email validation
        $('input[type="email"]').on('blur', function() {
            const email = $(this).val();
            if (email && !PartyMinder.isValidEmail(email)) {
                PartyMinder.showFieldError($(this), 'Please enter a valid email address.');
            } else {
                PartyMinder.clearFieldError($(this));
            }
        });
    };

    /**
     * Initialize events list functionality
     */
    PartyMinder.initEventsList = function() {
        // Share event buttons
        $('.share-event').on('click', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const title = $(this).data('title');
            PartyMinder.shareEvent(url, title);
        });

        // Load more events
        $('.load-more-events').on('click', function(e) {
            e.preventDefault();
            PartyMinder.loadMoreEvents($(this));
        });

        // Newsletter signup
        $('.newsletter-form').on('submit', function(e) {
            e.preventDefault();
            PartyMinder.subscribeToNewsletter($(this));
        });

        // Event filtering (if filters exist)
        $('.event-filter').on('change', function() {
            PartyMinder.filterEvents();
        });
    };

    /**
     * Initialize sharing functionality
     */
    PartyMinder.initSharing = function() {
        // Generic share buttons
        $('.pm-share-button').on('click', function(e) {
            e.preventDefault();
            const url = $(this).data('url') || window.location.href;
            const title = $(this).data('title') || document.title;
            PartyMinder.shareEvent(url, title);
        });
    };

    /**
     * Initialize form validation
     */
    PartyMinder.initValidation = function() {
        // Remove error styling on input
        $('input, select, textarea').on('input change', function() {
            if ($(this).hasClass('error')) {
                PartyMinder.clearFieldError($(this));
            }
        });
    };

    /**
     * Validate event creation form
     */
    PartyMinder.validateEventForm = function($form) {
        let isValid = true;

        // Required fields
        $form.find('input[required], select[required]').each(function() {
            if (!$(this).val().trim()) {
                PartyMinder.showFieldError($(this), 'This field is required.');
                isValid = false;
            }
        });

        // Email validation
        const $email = $form.find('input[type="email"]');
        if ($email.length && $email.val() && !PartyMinder.isValidEmail($email.val())) {
            PartyMinder.showFieldError($email, 'Please enter a valid email address.');
            isValid = false;
        }

        // Date validation
        const $date = $form.find('input[name="event_date"]');
        if ($date.length && $date.val()) {
            const selectedDate = new Date($date.val());
            const now = new Date();
            if (selectedDate <= now) {
                PartyMinder.showFieldError($date, 'Please select a future date and time.');
                isValid = false;
            }
        }

        return isValid;
    };

    /**
     * Validate RSVP form
     */
    PartyMinder.validateRSVPForm = function($form) {
        let isValid = true;

        // Required fields
        $form.find('input[required], select[required]').each(function() {
            if (!$(this).val().trim()) {
                PartyMinder.showFieldError($(this), 'This field is required.');
                isValid = false;
            }
        });

        // Email validation
        const $email = $form.find('input[name="guest_email"]');
        if ($email.val() && !PartyMinder.isValidEmail($email.val())) {
            PartyMinder.showFieldError($email, 'Please enter a valid email address.');
            isValid = false;
        }

        // RSVP status validation
        const $rsvpStatus = $form.find('input[name="rsvp_status"]:checked');
        if (!$rsvpStatus.length) {
            PartyMinder.showError('Please select your RSVP status.');
            isValid = false;
        }

        return isValid;
    };

    /**
     * Validate individual field
     */
    PartyMinder.validateField = function($field) {
        const fieldName = $field.attr('name');
        const value = $field.val().trim();

        PartyMinder.clearFieldError($field);

        if ($field.prop('required') && !value) {
            PartyMinder.showFieldError($field, 'This field is required.');
            return false;
        }

        if ($field.attr('type') === 'email' && value && !PartyMinder.isValidEmail(value)) {
            PartyMinder.showFieldError($field, 'Please enter a valid email address.');
            return false;
        }

        return true;
    };

    /**
     * Show field error
     */
    PartyMinder.showFieldError = function($field, message) {
        $field.addClass('error');
        
        // Remove existing error message
        $field.siblings('.field-error').remove();
        
        // Add new error message
        $field.after('<div class="field-error" style="color: #ef4444; font-size: 0.85em; margin-top: 4px;">' + message + '</div>');
    };

    /**
     * Clear field error
     */
    PartyMinder.clearFieldError = function($field) {
        $field.removeClass('error');
        $field.siblings('.field-error').remove();
    };

    /**
     * Show general error message
     */
    PartyMinder.showError = function(message) {
        // Create or update error container
        let $errorContainer = $('.partyminder-form-errors');
        if (!$errorContainer.length) {
            $errorContainer = $('<div class="partyminder-form-errors" style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 15px; margin-bottom: 20px; border-radius: 6px;"></div>');
            $('.partyminder-form').prepend($errorContainer);
        }
        
        $errorContainer.html(message).show();
        
        // Scroll to error
        $('html, body').animate({
            scrollTop: $errorContainer.offset().top - 100
        }, 300);
    };

    /**
     * Share event
     */
    PartyMinder.shareEvent = function(url, title) {
        title = title || 'Check out this amazing event!';
        
        if (navigator.share) {
            // Use Web Share API if available
            navigator.share({
                title: title,
                url: url
            }).catch(function(error) {
                console.log('Error sharing:', error);
                PartyMinder.fallbackShare(url, title);
            });
        } else {
            PartyMinder.fallbackShare(url, title);
        }
    };

    /**
     * Fallback sharing methods
     */
    PartyMinder.fallbackShare = function(url, title) {
        if (navigator.clipboard) {
            // Copy to clipboard
            navigator.clipboard.writeText(url).then(function() {
                PartyMinder.showNotification('Event link copied to clipboard!', 'success');
            }).catch(function() {
                PartyMinder.openShareDialog(url, title);
            });
        } else {
            PartyMinder.openShareDialog(url, title);
        }
    };

    /**
     * Open share dialog
     */
    PartyMinder.openShareDialog = function(url, title) {
        const shareUrl = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title);
        window.open(shareUrl, '_blank', 'width=600,height=400');
    };

    /**
     * Load more events
     */
    PartyMinder.loadMoreEvents = function($button) {
        const page = parseInt($button.data('page')) || 2;
        const limit = parseInt($button.data('limit')) || 10;

        $button.prop('disabled', true);
        const originalText = $button.html();
        $button.html('<span class="spinner"></span> ' + partyminder_ajax.strings.loading);

        $.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_load_more_events',
                nonce: partyminder_ajax.nonce,
                page: page,
                limit: limit
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $('.events-grid').append(response.data.html);
                    
                    if (response.data.has_more) {
                        $button.data('page', page + 1);
                        $button.prop('disabled', false).html(originalText);
                    } else {
                        $button.fadeOut();
                    }
                } else {
                    $button.fadeOut();
                }
            },
            error: function() {
                $button.prop('disabled', false).html(originalText);
                PartyMinder.showNotification('Error loading more events. Please try again.', 'error');
            }
        });
    };

    /**
     * Subscribe to newsletter
     */
    PartyMinder.subscribeToNewsletter = function($form) {
        const $email = $form.find('input[type="email"]');
        const email = $email.val();

        if (!email || !PartyMinder.isValidEmail(email)) {
            PartyMinder.showFieldError($email, 'Please enter a valid email address.');
            return;
        }

        const $button = $form.find('button[type="submit"]');
        $button.prop('disabled', true);
        const originalText = $button.html();
        $button.html('<span class="spinner"></span> Subscribing...');

        $.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_newsletter_signup',
                nonce: partyminder_ajax.nonce,
                email: email
            },
            success: function(response) {
                if (response.success) {
                    $form.html('<div class="newsletter-success" style="color: #10b981; font-weight: 600; text-align: center;">✅ Thank you for subscribing!</div>');
                } else {
                    PartyMinder.showNotification(response.data || 'Subscription failed. Please try again.', 'error');
                    $button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                PartyMinder.showNotification('Network error. Please try again.', 'error');
                $button.prop('disabled', false).html(originalText);
            }
        });
    };

    /**
     * Filter events
     */
    PartyMinder.filterEvents = function() {
        const filters = {};
        
        $('.event-filter').each(function() {
            const filterName = $(this).data('filter');
            const filterValue = $(this).val();
            if (filterValue) {
                filters[filterName] = filterValue;
            }
        });

        $('.event-card').each(function() {
            const $card = $(this);
            let showCard = true;

            // Apply filters
            Object.keys(filters).forEach(function(filterName) {
                const cardValue = $card.data(filterName);
                if (cardValue !== filters[filterName]) {
                    showCard = false;
                }
            });

            if (showCard) {
                $card.fadeIn(300);
            } else {
                $card.fadeOut(300);
            }
        });
    };

    /**
     * Show notification
     */
    PartyMinder.showNotification = function(message, type) {
        type = type || 'info';
        
        const $notification = $('<div class="partyminder-notification"></div>')
            .addClass('notification-' + type)
            .html(message)
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                background: type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#3b82f6'),
                color: 'white',
                padding: '15px 20px',
                borderRadius: '6px',
                boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
                zIndex: 9999,
                maxWidth: '300px'
            });

        $('body').append($notification);

        // Auto-remove after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);

        // Click to dismiss
        $notification.on('click', function() {
            $(this).fadeOut(300, function() {
                $(this).remove();
            });
        });
    };

    /**
     * Validate email address
     */
    PartyMinder.isValidEmail = function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    /**
     * Utility: Debounce function
     */
    PartyMinder.debounce = function(func, wait, immediate) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    /**
     * Utility: Scroll to element
     */
    PartyMinder.scrollTo = function(element, offset) {
        offset = offset || 0;
        const $element = $(element);
        
        if ($element.length) {
            $('html, body').animate({
                scrollTop: $element.offset().top + offset
            }, 500);
        }
    };

    /**
     * Handle responsive behavior
     */
    $(window).on('resize', PartyMinder.debounce(function() {
        // Handle responsive adjustments if needed
        PartyMinder.handleResponsive();
    }, 250));

    PartyMinder.handleResponsive = function() {
        const width = $(window).width();
        
        // Adjust forms for mobile
        if (width < 768) {
            $('.form-row').addClass('mobile-stack');
            $('.pm-button').addClass('full-width');
        } else {
            $('.form-row').removeClass('mobile-stack');
            $('.pm-button').removeClass('full-width');
        }
    };

    // Run responsive handler on load
    PartyMinder.handleResponsive();

})(jQuery);