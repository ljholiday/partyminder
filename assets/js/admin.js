/**
 * PartyMinder Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize Admin functionality
    $(document).ready(function() {
        PartyMinderAdmin.init();
    });

    window.PartyMinderAdmin = {
        
        /**
         * Initialize all admin functionality
         */
        init: function() {
            this.initDashboard();
            this.initAIAssistant();
            this.initSettings();
            this.initEventMetaBoxes();
            this.initListTables();
        },

        /**
         * Initialize dashboard functionality
         */
        initDashboard: function() {
            // Animate stats cards on load
            $('.stat-card').each(function(index) {
                $(this).delay(index * 100).animate({
                    opacity: 1,
                    transform: 'translateY(0)'
                }, 300);
            });

            // Refresh stats periodically
            setInterval(function() {
                PartyMinderAdmin.refreshStats();
            }, 300000); // 5 minutes
        },

        /**
         * Initialize AI Assistant functionality
         */
        initAIAssistant: function() {
            // AI plan generation form
            $('#ai-plan-form').on('submit', function(e) {
                e.preventDefault();
                PartyMinderAdmin.generateAIPlan($(this));
            });

            // Template buttons
            $('.ai-template-btn').on('click', function() {
                PartyMinderAdmin.applyAITemplate($(this).data('template'));
            });

            // Copy AI plan
            $(document).on('click', '.copy-ai-plan', function() {
                PartyMinderAdmin.copyToClipboard($(this).closest('.ai-plan').text());
            });

            // Save AI plan to event
            $(document).on('click', '.save-ai-plan', function() {
                PartyMinderAdmin.saveAIPlanToEvent($(this).data('event-id'));
            });
        },

        /**
         * Initialize settings functionality
         */
        initSettings: function() {
            // Test API connection
            $('.test-api-connection').on('click', function(e) {
                e.preventDefault();
                PartyMinderAdmin.testAPIConnection();
            });

            // Color picker initialization
            if ($.fn.wpColorPicker) {
                $('input[type="color"]').wpColorPicker({
                    change: function(event, ui) {
                        PartyMinderAdmin.updateColorPreview();
                    }
                });
            }

            // Settings form validation
            $('.partyminder-settings-form').on('submit', function(e) {
                if (!PartyMinderAdmin.validateSettings($(this))) {
                    e.preventDefault();
                    return false;
                }
            });

            // Live preview updates
            $('input[name="primary_color"], input[name="secondary_color"]').on('change', function() {
                PartyMinderAdmin.updateColorPreview();
            });

            $('select[name="button_style"], select[name="form_layout"]').on('change', function() {
                PartyMinderAdmin.updateStylePreview();
            });
        },

        /**
         * Initialize event meta boxes
         */
        initEventMetaBoxes: function() {
            // Date/time picker
            if ($('#event_date').length) {
                // Ensure minimum date is today
                const today = new Date().toISOString().slice(0, 16);
                $('#event_date').attr('min', today);
            }

            // Guest limit warnings
            $('#guest_limit').on('input', function() {
                const limit = parseInt($(this).val());
                if (limit > 100) {
                    PartyMinderAdmin.showWarning('Large events may require additional planning and resources.');
                }
            });

            // Auto-save meta box data
            $('.partyminder-metabox input, .partyminder-metabox textarea, .partyminder-metabox select').on('change', 
                PartyMinderAdmin.debounce(function() {
                    PartyMinderAdmin.autoSaveMetaBox();
                }, 2000)
            );
        },

        /**
         * Initialize list table enhancements
         */
        initListTables: function() {
            // Bulk actions
            $('.partyminder-bulk-action').on('click', function() {
                const action = $(this).data('action');
                const checked = $('.wp-list-table input[type="checkbox"]:checked');
                
                if (checked.length === 0) {
                    alert('Please select items to perform this action.');
                    return;
                }

                if (confirm('Are you sure you want to perform this action?')) {
                    PartyMinderAdmin.performBulkAction(action, checked);
                }
            });

            // Quick edit functionality
            $('.partyminder-quick-edit').on('click', function(e) {
                e.preventDefault();
                PartyMinderAdmin.showQuickEdit($(this).data('id'));
            });

            // AJAX refresh list tables
            $('.refresh-list-table').on('click', function(e) {
                e.preventDefault();
                PartyMinderAdmin.refreshListTable($(this).data('table'));
            });
        },

        /**
         * Generate AI plan
         */
        generateAIPlan: function($form) {
            const $button = $form.find('button[type="submit"]');
            const originalText = $button.html();
            
            // Show loading state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Generating...');

            // Hide previous results
            $('#ai-result').hide();

            $.ajax({
                url: partyminder_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'partyminder_generate_ai_plan',
                    nonce: partyminder_admin.nonce,
                    event_type: $form.find('#event_type').val(),
                    guest_count: $form.find('#guest_count').val(),
                    dietary: $form.find('#dietary').val(),
                    budget: $form.find('#budget').val()
                },
                success: function(response) {
                    if (response.success) {
                        PartyMinderAdmin.displayAIPlan(response.data);
                        $('#ai-result').slideDown();
                    } else {
                        PartyMinderAdmin.showError(response.data || 'Failed to generate AI plan');
                    }
                },
                error: function(xhr, status, error) {
                    PartyMinderAdmin.showError('Network error: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Display AI plan results
         */
        displayAIPlan: function(data) {
            let planHtml = '<div class="ai-plan">';
            
            // Show demo mode notice
            if (data.demo_mode) {
                planHtml += '<div class="demo-notice"><strong>Demo Mode:</strong> Configure your API key in settings for real AI generation.</div>';
            }

            try {
                const plan = JSON.parse(data.plan);
                
                // Menu section
                if (plan.menu) {
                    planHtml += '<h4>🍽️ Menu</h4><ul>';
                    for (const [course, description] of Object.entries(plan.menu)) {
                        const courseName = course.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        planHtml += `<li><strong>${courseName}:</strong> ${description}</li>`;
                    }
                    planHtml += '</ul>';
                }

                // Shopping list
                if (plan.shopping_list && Array.isArray(plan.shopping_list)) {
                    planHtml += '<h4>🛒 Shopping List</h4><ul>';
                    plan.shopping_list.forEach(item => {
                        planHtml += `<li>${item}</li>`;
                    });
                    planHtml += '</ul>';
                }

                // Timeline
                if (plan.timeline) {
                    planHtml += '<h4>⏰ Timeline</h4><ul>';
                    for (const [period, tasks] of Object.entries(plan.timeline)) {
                        const periodName = period.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        planHtml += `<li><strong>${periodName}:</strong> ${tasks}</li>`;
                    }
                    planHtml += '</ul>';
                }

                // Cost and additional info
                if (plan.estimated_cost) {
                    planHtml += `<p><strong>💰 Estimated Cost:</strong> $${plan.estimated_cost}</p>`;
                }
                if (plan.prep_time) {
                    planHtml += `<p><strong>⏱️ Prep Time:</strong> ${plan.prep_time}</p>`;
                }

            } catch (e) {
                // Fallback: show raw content
                planHtml += '<pre>' + data.plan + '</pre>';
            }

            // Action buttons
            planHtml += '<div class="ai-plan-actions">';
            planHtml += '<button type="button" class="button button-secondary copy-ai-plan">📋 Copy Plan</button> ';
            planHtml += '<button type="button" class="button button-primary save-ai-plan" data-event-id="0">💾 Save to Event</button>';
            planHtml += '</div>';

            planHtml += '</div>';

            $('#ai-plan-content').html(planHtml);
        },

        /**
         * Apply AI template
         */
        applyAITemplate: function(template) {
            const templates = {
                'dinner_party': {
                    event_type: 'dinner',
                    guest_count: 8,
                    dietary: 'vegetarian options',
                    budget: 'moderate'
                },
                'birthday_party': {
                    event_type: 'birthday',
                    guest_count: 12,
                    dietary: '',
                    budget: 'moderate'
                },
                'cocktail_party': {
                    event_type: 'cocktail',
                    guest_count: 15,
                    dietary: '',
                    budget: 'premium'
                }
            };

            const templateData = templates[template];
            if (templateData) {
                $('#event_type').val(templateData.event_type);
                $('#guest_count').val(templateData.guest_count);
                $('#dietary').val(templateData.dietary);
                $('#budget').val(templateData.budget);

                // Visual feedback
                PartyMinderAdmin.showSuccess('Template applied! Click "Generate AI Plan" to create your plan.');
            }
        },

        /**
         * Test API connection
         */
        testAPIConnection: function() {
            const $button = $('.test-api-connection');
            const originalText = $button.html();
            
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');

            $.ajax({
                url: partyminder_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'partyminder_test_api_connection',
                    nonce: partyminder_admin.nonce,
                    provider: $('#ai_provider').val(),
                    api_key: $('#ai_api_key').val(),
                    model: $('#ai_model').val()
                },
                success: function(response) {
                    if (response.success) {
                        PartyMinderAdmin.showSuccess('✅ API connection successful!');
                    } else {
                        PartyMinderAdmin.showError('❌ ' + (response.data || 'API connection failed'));
                    }
                },
                error: function() {
                    PartyMinderAdmin.showError('❌ Network error during API test');
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Validate settings form
         */
        validateSettings: function($form) {
            let isValid = true;

            // API key validation
            const apiKey = $('#ai_api_key').val();
            const demoMode = $('#demo_mode').is(':checked');
            
            if (!apiKey && !demoMode) {
                PartyMinderAdmin.showWarning('Please provide an API key or enable demo mode.');
                isValid = false;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const fromEmail = $('#email_from_address').val();
            
            if (fromEmail && !emailRegex.test(fromEmail)) {
                PartyMinderAdmin.showError('Please enter a valid email address.');
                isValid = false;
            }

            // Cost limit validation
            const costLimit = parseInt($('#ai_cost_limit_monthly').val());
            if (costLimit < 1 || costLimit > 1000) {
                PartyMinderAdmin.showError('Monthly cost limit must be between $1 and $1000.');
                isValid = false;
            }

            return isValid;
        },

        /**
         * Update color preview
         */
        updateColorPreview: function() {
            const primaryColor = $('input[name="primary_color"]').val();
            const secondaryColor = $('input[name="secondary_color"]').val();

            // Update CSS custom properties for preview
            if (primaryColor) {
                document.documentElement.style.setProperty('--pm-primary', primaryColor);
            }
            if (secondaryColor) {
                document.documentElement.style.setProperty('--pm-secondary', secondaryColor);
            }

            // Show preview notice
            PartyMinderAdmin.showInfo('Color preview updated. Save settings to apply changes.');
        },

        /**
         * Update style preview
         */
        updateStylePreview: function() {
            const buttonStyle = $('select[name="button_style"]').val();
            const formLayout = $('select[name="form_layout"]').val();

            // Update preview elements if they exist
            $('.button-preview').removeClass('style-rounded style-square style-pill').addClass('style-' + buttonStyle);
            $('.form-preview').removeClass('layout-card layout-minimal layout-classic').addClass('layout-' + formLayout);

            PartyMinderAdmin.showInfo('Style preview updated. Save settings to apply changes.');
        },

        /**
         * Auto-save meta box data
         */
        autoSaveMetaBox: function() {
            const postId = $('#post_ID').val();
            if (!postId) return;

            const data = {
                action: 'partyminder_autosave_metabox',
                nonce: partyminder_admin.nonce,
                post_id: postId,
                event_date: $('#event_date').val(),
                guest_limit: $('#guest_limit').val(),
                venue_info: $('#venue_info').val(),
                host_email: $('#host_email').val(),
                host_notes: $('#host_notes').val()
            };

            $.ajax({
                url: partyminder_admin.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        PartyMinderAdmin.showAutoSaveIndicator();
                    }
                }
            });
        },

        /**
         * Show auto-save indicator
         */
        showAutoSaveIndicator: function() {
            let $indicator = $('.autosave-indicator');
            if (!$indicator.length) {
                $indicator = $('<div class="autosave-indicator">✓ Auto-saved</div>')
                    .css({
                        position: 'fixed',
                        top: '32px',
                        right: '20px',
                        background: '#00a32a',
                        color: 'white',
                        padding: '8px 15px',
                        borderRadius: '4px',
                        fontSize: '13px',
                        zIndex: 9999
                    });
                $('body').append($indicator);
            }

            $indicator.fadeIn().delay(2000).fadeOut();
        },

        /**
         * Refresh dashboard stats
         */
        refreshStats: function() {
            $.ajax({
                url: partyminder_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'partyminder_refresh_stats',
                    nonce: partyminder_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        PartyMinderAdmin.updateStatsDisplay(response.data);
                    }
                }
            });
        },

        /**
         * Update stats display
         */
        updateStatsDisplay: function(stats) {
            $('.stat-card').each(function() {
                const statType = $(this).data('stat-type');
                if (stats[statType]) {
                    $(this).find('.stat-number').text(stats[statType]);
                }
            });
        },

        /**
         * Perform bulk action
         */
        performBulkAction: function(action, $checkedItems) {
            const ids = [];
            $checkedItems.each(function() {
                ids.push($(this).val());
            });

            $.ajax({
                url: partyminder_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'partyminder_bulk_action',
                    nonce: partyminder_admin.nonce,
                    bulk_action: action,
                    ids: ids
                },
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Reload to show changes
                    } else {
                        PartyMinderAdmin.showError(response.data || 'Bulk action failed');
                    }
                }
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    PartyMinderAdmin.showSuccess('Copied to clipboard!');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                PartyMinderAdmin.showSuccess('Copied to clipboard!');
            }
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            PartyMinderAdmin.showNotice(message, 'success');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            PartyMinderAdmin.showNotice(message, 'error');
        },

        /**
         * Show warning message
         */
        showWarning: function(message) {
            PartyMinderAdmin.showNotice(message, 'warning');
        },

        /**
         * Show info message
         */
        showInfo: function(message) {
            PartyMinderAdmin.showNotice(message, 'info');
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Find the best place to insert notice
            let $target = $('.wrap h1').first();
            if (!$target.length) {
                $target = $('.wrap').first();
            }
            
            $target.after($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);

            // Handle dismiss button
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function() {
                const context = this, args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };

})(jQuery);