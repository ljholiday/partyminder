<?php

class PartyMinder_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    public function admin_menu() {
        add_menu_page(
            __('PartyMinder', 'partyminder'),
            __('PartyMinder', 'partyminder'),
            'manage_options',
            'partyminder',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        add_submenu_page(
            'partyminder',
            __('Dashboard', 'partyminder'),
            __('Dashboard', 'partyminder'),
            'manage_options',
            'partyminder',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'partyminder',
            __('Create Event', 'partyminder'),
            __('Create Event', 'partyminder'),
            'manage_options',
            'partyminder-create',
            array($this, 'create_event_page')
        );
        
        add_submenu_page(
            'partyminder',
            __('AI Assistant', 'partyminder'),
            __('AI Assistant', 'partyminder'),
            'manage_options',
            'partyminder-ai',
            array($this, 'ai_page')
        );
        
        add_submenu_page(
            'partyminder',
            __('Settings', 'partyminder'),
            __('Settings', 'partyminder'),
            'manage_options',
            'partyminder-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        // AI Settings
        register_setting('partyminder_ai_settings', 'partyminder_ai_provider');
        register_setting('partyminder_ai_settings', 'partyminder_ai_api_key');
        register_setting('partyminder_ai_settings', 'partyminder_ai_model');
        register_setting('partyminder_ai_settings', 'partyminder_ai_cost_limit_monthly');
        
        // Email Settings
        register_setting('partyminder_email_settings', 'partyminder_email_from_name');
        register_setting('partyminder_email_settings', 'partyminder_email_from_address');
        
        // Feature Settings
        register_setting('partyminder_feature_settings', 'partyminder_enable_public_events');
        register_setting('partyminder_feature_settings', 'partyminder_demo_mode');
        register_setting('partyminder_feature_settings', 'partyminder_track_analytics');
        
        // Style Settings
        register_setting('partyminder_style_settings', 'partyminder_primary_color');
        register_setting('partyminder_style_settings', 'partyminder_secondary_color');
        register_setting('partyminder_style_settings', 'partyminder_button_style');
        register_setting('partyminder_style_settings', 'partyminder_form_layout');
    }
    
    public function dashboard_page() {
        $event_manager = new PartyMinder_Event_Manager();
        $guest_manager = new PartyMinder_Guest_Manager();
        $ai_assistant = new PartyMinder_AI_Assistant();
        
        // Get stats
        $total_events = wp_count_posts('party_event')->publish ?? 0;
        $upcoming_events = $event_manager->get_upcoming_events(5);
        $ai_usage = $ai_assistant->get_monthly_usage();
        
        ?>
        <div class="wrap">
            <h1><?php _e('PartyMinder Dashboard', 'partyminder'); ?></h1>
            
            <?php if (get_option('partyminder_demo_mode', true)): ?>
            <div class="notice notice-info">
                <p><strong><?php _e('Demo Mode Active', 'partyminder'); ?></strong> - 
                <a href="<?php echo admin_url('admin.php?page=partyminder-settings'); ?>"><?php _e('Configure AI settings', 'partyminder'); ?></a> <?php _e('to unlock full functionality.', 'partyminder'); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="partyminder-dashboard">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🎉</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($total_events); ?></div>
                            <div class="stat-label"><?php _e('Total Events', 'partyminder'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🤖</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $ai_usage['interactions']; ?></div>
                            <div class="stat-label"><?php _e('AI Plans Generated', 'partyminder'); ?></div>
                            <div class="stat-sublabel">$<?php echo number_format($ai_usage['total'], 2); ?> <?php _e('this month', 'partyminder'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo count($upcoming_events); ?></div>
                            <div class="stat-label"><?php _e('Upcoming Events', 'partyminder'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2><?php _e('Quick Actions', 'partyminder'); ?></h2>
                    <div class="action-buttons">
                        <a href="<?php echo admin_url('admin.php?page=partyminder-create'); ?>" class="button button-primary button-large">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Create New Event', 'partyminder'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=partyminder-ai'); ?>" class="button button-secondary">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php _e('AI Assistant', 'partyminder'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('edit.php?post_type=party_event'); ?>" class="button button-secondary">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php _e('View All Events', 'partyminder'); ?>
                        </a>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <?php if (!empty($upcoming_events)): ?>
                <div class="upcoming-events">
                    <h2><?php _e('Upcoming Events', 'partyminder'); ?></h2>
                    <div class="events-list">
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="event-item">
                                <div class="event-date">
                                    <div class="date-day"><?php echo date('j', strtotime($event->event_date)); ?></div>
                                    <div class="date-month"><?php echo date('M', strtotime($event->event_date)); ?></div>
                                </div>
                                <div class="event-details">
                                    <h3><a href="<?php echo PartyMinder::get_edit_event_url($event->ID); ?>"><?php echo esc_html($event->title); ?></a></h3>
                                    <div class="event-meta">
                                        <span><?php echo date('g:i A', strtotime($event->event_date)); ?></span>
                                        <span><?php echo $event->guest_stats->confirmed; ?> confirmed</span>
                                        <?php if ($event->venue_info): ?>
                                            <span><?php echo esc_html($event->venue_info); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Getting Started -->
                <div class="getting-started">
                    <h2><?php _e('Getting Started', 'partyminder'); ?></h2>
                    <div class="steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3><?php _e('Configure AI Settings', 'partyminder'); ?></h3>
                                <p><?php _e('Add your OpenAI API key to enable intelligent party planning.', 'partyminder'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=partyminder-settings'); ?>"><?php _e('Go to Settings', 'partyminder'); ?></a>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3><?php _e('Create Your First Event', 'partyminder'); ?></h3>
                                <p><?php _e('Set up a party event and start inviting guests.', 'partyminder'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=partyminder-create'); ?>"><?php _e('Create Event', 'partyminder'); ?></a>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3><?php _e('Use AI Planning', 'partyminder'); ?></h3>
                                <p><?php _e('Generate intelligent party plans with menu suggestions and timelines.', 'partyminder'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=partyminder-ai'); ?>"><?php _e('Try AI Assistant', 'partyminder'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function create_event_page() {
        $event_created = false;
        $form_errors = array();
        
        // Handle form submission
        if (isset($_POST['partyminder_create_admin_event']) && wp_verify_nonce($_POST['partyminder_admin_nonce'], 'create_admin_event')) {
            
            // Validate required fields
            if (empty($_POST['event_title'])) {
                $form_errors[] = __('Event title is required.', 'partyminder');
            }
            if (empty($_POST['event_date'])) {
                $form_errors[] = __('Event date is required.', 'partyminder');
            }
            if (empty($_POST['host_email'])) {
                $form_errors[] = __('Host email is required.', 'partyminder');
            }
            
            // If no errors, create the event
            if (empty($form_errors)) {
                $event_data = array(
                    'title' => sanitize_text_field($_POST['event_title']),
                    'description' => wp_kses_post($_POST['event_description']),
                    'event_date' => sanitize_text_field($_POST['event_date']),
                    'venue' => sanitize_text_field($_POST['venue_info']),
                    'guest_limit' => intval($_POST['guest_limit']),
                    'host_email' => sanitize_email($_POST['host_email']),
                    'host_notes' => wp_kses_post($_POST['host_notes'])
                );
                
                $event_manager = new PartyMinder_Event_Manager();
                $event_id = $event_manager->create_event($event_data);
                
                if (!is_wp_error($event_id)) {
                    $event_created = true;
                    $event_url = get_edit_post_link($event_id);
                } else {
                    $form_errors[] = $event_id->get_error_message();
                }
            }
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Create New Event', 'partyminder'); ?></h1>
            
            <?php if ($event_created): ?>
                <div class="notice notice-success">
                    <p><strong><?php _e('Event created successfully!', 'partyminder'); ?></strong></p>
                    <p>
                        <a href="<?php echo PartyMinder::get_edit_event_url($event_id); ?>" class="button button-primary"><?php _e('Edit Event', 'partyminder'); ?></a>
                        <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="button"><?php _e('View My Events', 'partyminder'); ?></a>
                    </p>
                </div>
            <?php else: ?>
                
                <?php if (!empty($form_errors)): ?>
                    <div class="notice notice-error">
                        <p><strong><?php _e('Please fix the following issues:', 'partyminder'); ?></strong></p>
                        <ul>
                            <?php foreach ($form_errors as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="partyminder-admin-form">
                    <?php wp_nonce_field('create_admin_event', 'partyminder_admin_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="event_title"><?php _e('Event Title *', 'partyminder'); ?></label></th>
                            <td>
                                <input type="text" id="event_title" name="event_title" 
                                       value="<?php echo esc_attr($_POST['event_title'] ?? ''); ?>" 
                                       class="regular-text" required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="event_date"><?php _e('Event Date & Time *', 'partyminder'); ?></label></th>
                            <td>
                                <input type="datetime-local" id="event_date" name="event_date" 
                                       value="<?php echo esc_attr($_POST['event_date'] ?? ''); ?>" 
                                       min="<?php echo date('Y-m-d\TH:i'); ?>" class="regular-text" required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="venue_info"><?php _e('Venue/Location', 'partyminder'); ?></label></th>
                            <td>
                                <input type="text" id="venue_info" name="venue_info" 
                                       value="<?php echo esc_attr($_POST['venue_info'] ?? ''); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="guest_limit"><?php _e('Guest Limit', 'partyminder'); ?></label></th>
                            <td>
                                <input type="number" id="guest_limit" name="guest_limit" 
                                       value="<?php echo esc_attr($_POST['guest_limit'] ?? '10'); ?>" 
                                       min="1" max="100" class="small-text" />
                                <p class="description"><?php _e('Maximum number of guests allowed.', 'partyminder'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="host_email"><?php _e('Host Email *', 'partyminder'); ?></label></th>
                            <td>
                                <input type="email" id="host_email" name="host_email" 
                                       value="<?php echo esc_attr($_POST['host_email'] ?? wp_get_current_user()->user_email); ?>" 
                                       class="regular-text" required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="event_description"><?php _e('Event Description', 'partyminder'); ?></label></th>
                            <td>
                                <textarea id="event_description" name="event_description" rows="5" class="large-text"><?php echo esc_textarea($_POST['event_description'] ?? ''); ?></textarea>
                                <p class="description"><?php _e('Describe your event for guests.', 'partyminder'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="host_notes"><?php _e('Host Notes', 'partyminder'); ?></label></th>
                            <td>
                                <textarea id="host_notes" name="host_notes" rows="3" class="large-text"><?php echo esc_textarea($_POST['host_notes'] ?? ''); ?></textarea>
                                <p class="description"><?php _e('Special instructions, parking info, etc.', 'partyminder'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="partyminder_create_admin_event" class="button button-primary">
                            <?php _e('Create Event', 'partyminder'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=partyminder'); ?>" class="button"><?php _e('Cancel', 'partyminder'); ?></a>
                    </p>
                </form>
                
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function ai_page() {
        $ai_assistant = new PartyMinder_AI_Assistant();
        $usage = $ai_assistant->get_monthly_usage();
        ?>
        <div class="wrap">
            <h1><?php _e('AI Party Planning Assistant', 'partyminder'); ?></h1>
            
            <div class="ai-usage-summary">
                <h2><?php _e('Monthly Usage', 'partyminder'); ?></h2>
                <p><?php printf(__('Used: $%s of $%s limit (%d interactions)', 'partyminder'), 
                    number_format($usage['total'], 2), 
                    number_format($usage['limit'], 2), 
                    $usage['interactions']); ?></p>
            </div>
            
            <div class="ai-generator">
                <h2><?php _e('Generate Party Plan', 'partyminder'); ?></h2>
                
                <form id="ai-plan-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="event_type"><?php _e('Event Type', 'partyminder'); ?></label></th>
                            <td>
                                <select id="event_type" name="event_type" required>
                                    <option value=""><?php _e('Select event type...', 'partyminder'); ?></option>
                                    <option value="dinner"><?php _e('Dinner Party', 'partyminder'); ?></option>
                                    <option value="birthday"><?php _e('Birthday Party', 'partyminder'); ?></option>
                                    <option value="cocktail"><?php _e('Cocktail Party', 'partyminder'); ?></option>
                                    <option value="bbq"><?php _e('BBQ Party', 'partyminder'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="guest_count"><?php _e('Number of Guests', 'partyminder'); ?></label></th>
                            <td><input type="number" id="guest_count" name="guest_count" value="8" min="2" max="50" required></td>
                        </tr>
                        <tr>
                            <th><label for="dietary"><?php _e('Dietary Restrictions', 'partyminder'); ?></label></th>
                            <td><input type="text" id="dietary" name="dietary" placeholder="<?php _e('e.g., vegetarian, gluten-free', 'partyminder'); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="budget"><?php _e('Budget', 'partyminder'); ?></label></th>
                            <td>
                                <select id="budget" name="budget" required>
                                    <option value="budget"><?php _e('Budget ($15-25/person)', 'partyminder'); ?></option>
                                    <option value="moderate" selected><?php _e('Moderate ($25-40/person)', 'partyminder'); ?></option>
                                    <option value="premium"><?php _e('Premium ($40+/person)', 'partyminder'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php _e('Generate AI Plan', 'partyminder'); ?>
                        </button>
                    </p>
                </form>
                
                <div id="ai-result" style="display: none;">
                    <h3><?php _e('Generated Plan', 'partyminder'); ?></h3>
                    <div id="ai-plan-content"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ai-plan-form').on('submit', function(e) {
                e.preventDefault();
                
                const $button = $(this).find('button[type="submit"]');
                const originalText = $button.html();
                $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e("Generating...", "partyminder"); ?>');
                
                $.ajax({
                    url: partyminder_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'partyminder_generate_ai_plan',
                        nonce: partyminder_admin.nonce,
                        event_type: $('#event_type').val(),
                        guest_count: $('#guest_count').val(),
                        dietary: $('#dietary').val(),
                        budget: $('#budget').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            let planHtml = '<div class="ai-plan">';
                            
                            if (response.data.demo_mode) {
                                planHtml += '<p class="demo-notice"><strong>Demo Mode:</strong> Configure your API key in settings for real AI generation.</p>';
                            }
                            
                            try {
                                const plan = JSON.parse(response.data.plan);
                                planHtml += '<h4>Menu</h4><ul>';
                                for (const [course, description] of Object.entries(plan.menu || {})) {
                                    planHtml += `<li><strong>${course.replace('_', ' ')}:</strong> ${description}</li>`;
                                }
                                planHtml += '</ul>';
                                
                                if (plan.shopping_list) {
                                    planHtml += '<h4>Shopping List</h4><ul>';
                                    plan.shopping_list.forEach(item => {
                                        planHtml += `<li>${item}</li>`;
                                    });
                                    planHtml += '</ul>';
                                }
                                
                                if (plan.estimated_cost) {
                                    planHtml += `<p><strong>Estimated Cost:</strong> $${plan.estimated_cost}</p>`;
                                }
                            } catch (e) {
                                planHtml += '<pre>' + response.data.plan + '</pre>';
                            }
                            
                            planHtml += '</div>';
                            $('#ai-plan-content').html(planHtml);
                            $('#ai-result').show();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to generate plan'));
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            if (!wp_verify_nonce($_POST['partyminder_settings_nonce'] ?? '', 'partyminder_settings') || !current_user_can('manage_options')) {
                wp_die(__('Security check failed', 'partyminder'));
            }
            // Save settings
            update_option('partyminder_ai_provider', sanitize_text_field($_POST['ai_provider']));
            update_option('partyminder_ai_api_key', sanitize_text_field($_POST['ai_api_key']));
            update_option('partyminder_ai_model', sanitize_text_field($_POST['ai_model']));
            update_option('partyminder_ai_cost_limit_monthly', intval($_POST['ai_cost_limit_monthly']));
            
            update_option('partyminder_email_from_name', sanitize_text_field($_POST['email_from_name']));
            update_option('partyminder_email_from_address', sanitize_email($_POST['email_from_address']));
            
            update_option('partyminder_enable_public_events', isset($_POST['enable_public_events']));
            update_option('partyminder_demo_mode', isset($_POST['demo_mode']));
            update_option('partyminder_track_analytics', isset($_POST['track_analytics']));
            
            update_option('partyminder_primary_color', sanitize_hex_color($_POST['primary_color']));
            update_option('partyminder_secondary_color', sanitize_hex_color($_POST['secondary_color']));
            update_option('partyminder_button_style', sanitize_text_field($_POST['button_style']));
            update_option('partyminder_form_layout', sanitize_text_field($_POST['form_layout']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'partyminder') . '</p></div>';
        }
        
        // Get current values
        $ai_provider = get_option('partyminder_ai_provider', 'openai');
        $ai_api_key = get_option('partyminder_ai_api_key', '');
        $ai_model = get_option('partyminder_ai_model', 'gpt-4');
        $ai_cost_limit = get_option('partyminder_ai_cost_limit_monthly', 50);
        
        $email_from_name = get_option('partyminder_email_from_name', get_bloginfo('name'));
        $email_from_address = get_option('partyminder_email_from_address', get_option('admin_email'));
        
        $enable_public_events = get_option('partyminder_enable_public_events', true);
        $demo_mode = get_option('partyminder_demo_mode', true);
        $track_analytics = get_option('partyminder_track_analytics', true);
        
        $primary_color = get_option('partyminder_primary_color', '#667eea');
        $secondary_color = get_option('partyminder_secondary_color', '#764ba2');
        $button_style = get_option('partyminder_button_style', 'rounded');
        $form_layout = get_option('partyminder_form_layout', 'card');
        ?>
        <div class="wrap">
            <h1><?php _e('PartyMinder Settings', 'partyminder'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('partyminder_settings', 'partyminder_settings_nonce'); ?>
                
                <h2><?php _e('AI Configuration', 'partyminder'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="ai_provider"><?php _e('AI Provider', 'partyminder'); ?></label></th>
                        <td>
                            <select id="ai_provider" name="ai_provider">
                                <option value="openai" <?php selected($ai_provider, 'openai'); ?>>OpenAI (GPT-4)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ai_api_key"><?php _e('API Key', 'partyminder'); ?></label></th>
                        <td>
                            <input type="password" id="ai_api_key" name="ai_api_key" value="<?php echo esc_attr($ai_api_key); ?>" class="regular-text" />
                            <p class="description"><?php _e('Get your API key from OpenAI. Leave blank for demo mode.', 'partyminder'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ai_model"><?php _e('Model', 'partyminder'); ?></label></th>
                        <td>
                            <select id="ai_model" name="ai_model">
                                <option value="gpt-4" <?php selected($ai_model, 'gpt-4'); ?>>GPT-4</option>
                                <option value="gpt-3.5-turbo" <?php selected($ai_model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ai_cost_limit_monthly"><?php _e('Monthly Cost Limit ($)', 'partyminder'); ?></label></th>
                        <td><input type="number" id="ai_cost_limit_monthly" name="ai_cost_limit_monthly" value="<?php echo esc_attr($ai_cost_limit); ?>" min="1" max="1000" /></td>
                    </tr>
                </table>
                
                <h2><?php _e('Email Settings', 'partyminder'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="email_from_name"><?php _e('From Name', 'partyminder'); ?></label></th>
                        <td><input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr($email_from_name); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="email_from_address"><?php _e('From Email', 'partyminder'); ?></label></th>
                        <td><input type="email" id="email_from_address" name="email_from_address" value="<?php echo esc_attr($email_from_address); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                
                <h2><?php _e('Features', 'partyminder'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Public Events', 'partyminder'); ?></th>
                        <td><label><input type="checkbox" name="enable_public_events" value="1" <?php checked($enable_public_events); ?> /> <?php _e('Allow public event listings', 'partyminder'); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php _e('Demo Mode', 'partyminder'); ?></th>
                        <td><label><input type="checkbox" name="demo_mode" value="1" <?php checked($demo_mode); ?> /> <?php _e('Use demo AI responses when no API key configured', 'partyminder'); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php _e('Analytics', 'partyminder'); ?></th>
                        <td><label><input type="checkbox" name="track_analytics" value="1" <?php checked($track_analytics); ?> /> <?php _e('Track usage analytics', 'partyminder'); ?></label></td>
                    </tr>
                </table>
                
                <h2><?php _e('Styling', 'partyminder'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="primary_color"><?php _e('Primary Color', 'partyminder'); ?></label></th>
                        <td><input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr($primary_color); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="secondary_color"><?php _e('Secondary Color', 'partyminder'); ?></label></th>
                        <td><input type="color" id="secondary_color" name="secondary_color" value="<?php echo esc_attr($secondary_color); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="button_style"><?php _e('Button Style', 'partyminder'); ?></label></th>
                        <td>
                            <select id="button_style" name="button_style">
                                <option value="rounded" <?php selected($button_style, 'rounded'); ?>><?php _e('Rounded', 'partyminder'); ?></option>
                                <option value="square" <?php selected($button_style, 'square'); ?>><?php _e('Square', 'partyminder'); ?></option>
                                <option value="pill" <?php selected($button_style, 'pill'); ?>><?php _e('Pill', 'partyminder'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="form_layout"><?php _e('Form Layout', 'partyminder'); ?></label></th>
                        <td>
                            <select id="form_layout" name="form_layout">
                                <option value="card" <?php selected($form_layout, 'card'); ?>><?php _e('Card Style', 'partyminder'); ?></option>
                                <option value="minimal" <?php selected($form_layout, 'minimal'); ?>><?php _e('Minimal', 'partyminder'); ?></option>
                                <option value="classic" <?php selected($form_layout, 'classic'); ?>><?php _e('Classic', 'partyminder'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function admin_notices() {
        if (!get_option('partyminder_ai_api_key') && !get_option('partyminder_demo_mode')) {
            $screen = get_current_screen();
            if ($screen && strpos($screen->id, 'partyminder') !== false) {
                echo '<div class="notice notice-warning"><p>';
                printf(__('PartyMinder AI features require an API key. <a href="%s">Configure settings</a> or enable demo mode.', 'partyminder'),
                    admin_url('admin.php?page=partyminder-settings'));
                echo '</p></div>';
            }
        }
    }
}