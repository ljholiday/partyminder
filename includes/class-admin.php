<?php

class PartyMinder_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function admin_menu() {
		add_menu_page(
			__( 'PartyMinder', 'partyminder' ),
			__( 'PartyMinder', 'partyminder' ),
			'manage_options',
			'partyminder',
			array( $this, 'dashboard_page' ),
			'dashicons-calendar-alt',
			30
		);

		add_submenu_page(
			'partyminder',
			__( 'Dashboard', 'partyminder' ),
			__( 'Dashboard', 'partyminder' ),
			'manage_options',
			'partyminder',
			array( $this, 'dashboard_page' )
		);

		add_submenu_page(
			'partyminder',
			__( 'All Events', 'partyminder' ),
			__( 'All Events', 'partyminder' ),
			'manage_options',
			'partyminder-events',
			array( $this, 'events_page' )
		);

		add_submenu_page(
			'partyminder',
			__( 'AI Assistant', 'partyminder' ),
			__( 'AI Assistant', 'partyminder' ),
			'manage_options',
			'partyminder-ai',
			array( $this, 'ai_page' )
		);

		// Communities menu (only if feature is enabled)
		if ( PartyMinder_Feature_Flags::show_communities_in_admin() ) {
			add_submenu_page(
				'partyminder',
				__( 'Communities', 'partyminder' ),
				__( 'Communities', 'partyminder' ),
				'manage_options',
				'partyminder-communities',
				array( $this, 'communities_page' )
			);

			if ( PartyMinder_Feature_Flags::is_communities_enabled() ) {
				add_submenu_page(
					'partyminder',
					__( 'Community Members', 'partyminder' ),
					__( 'Members', 'partyminder' ),
					'manage_options',
					'partyminder-members',
					array( $this, 'members_page' )
				);
			}
		}

		add_submenu_page(
			'partyminder',
			__( 'Settings', 'partyminder' ),
			__( 'Settings', 'partyminder' ),
			'manage_options',
			'partyminder-settings',
			array( $this, 'settings_page' )
		);
	}

	public function register_settings() {
		// AI Settings
		register_setting( 'partyminder_ai_settings', 'partyminder_ai_provider' );
		register_setting( 'partyminder_ai_settings', 'partyminder_ai_api_key' );
		register_setting( 'partyminder_ai_settings', 'partyminder_ai_model' );
		register_setting( 'partyminder_ai_settings', 'partyminder_ai_cost_limit_monthly' );

		// Email Settings
		register_setting( 'partyminder_email_settings', 'partyminder_email_from_name' );
		register_setting( 'partyminder_email_settings', 'partyminder_email_from_address' );

		// Feature Settings
		register_setting( 'partyminder_feature_settings', 'partyminder_enable_public_events' );
		register_setting( 'partyminder_feature_settings', 'partyminder_demo_mode' );
		register_setting( 'partyminder_feature_settings', 'partyminder_track_analytics' );

		// Communities Feature Settings
		register_setting( 'partyminder_communities_settings', 'partyminder_enable_communities' );
		register_setting( 'partyminder_communities_settings', 'partyminder_enable_at_protocol' );
		register_setting( 'partyminder_communities_settings', 'partyminder_communities_require_approval' );
		register_setting( 'partyminder_communities_settings', 'partyminder_max_communities_per_user' );

		// Style Settings
		register_setting( 'partyminder_style_settings', 'partyminder_primary_color' );
		register_setting( 'partyminder_style_settings', 'partyminder_secondary_color' );
		register_setting( 'partyminder_style_settings', 'partyminder_button_style' );
		register_setting( 'partyminder_style_settings', 'partyminder_form_layout' );
	}

	public function dashboard_page() {
		$event_manager = new PartyMinder_Event_Manager();
		$guest_manager = new PartyMinder_Guest_Manager();
		$ai_assistant  = new PartyMinder_AI_Assistant();

		// Get stats
		// Count events from custom table
		global $wpdb;
		$events_table    = $wpdb->prefix . 'partyminder_events';
		$total_events    = $wpdb->get_var( "SELECT COUNT(*) FROM $events_table WHERE event_status = 'active'" ) ?? 0;
		$upcoming_events = $event_manager->get_upcoming_events( 5 );
		$ai_usage        = $ai_assistant->get_monthly_usage();

		?>
		<div class="wrap">
			<h1><?php _e( 'PartyMinder Dashboard', 'partyminder' ); ?></h1>
			
			<?php if ( get_option( 'partyminder_demo_mode', true ) ) : ?>
			<div class="notice notice-info">
				<p><strong><?php _e( 'Demo Mode Active', 'partyminder' ); ?></strong> - 
				<a href="<?php echo admin_url( 'admin.php?page=partyminder-settings' ); ?>"><?php _e( 'Configure AI settings', 'partyminder' ); ?></a> <?php _e( 'to unlock full functionality.', 'partyminder' ); ?></p>
			</div>
			<?php endif; ?>
			
			<div class="partyminder-dashboard">
				<!-- Stats Cards -->
				<div class="stats-grid">
					<div class="stat-card">
						<div class="stat-icon">🎉</div>
						<div class="stat-content">
							<div class="stat-number"><?php echo number_format( $total_events ); ?></div>
							<div class="stat-label"><?php _e( 'Total Events', 'partyminder' ); ?></div>
						</div>
					</div>
					
					<div class="stat-card">
						<div class="stat-icon">🤖</div>
						<div class="stat-content">
							<div class="stat-number"><?php echo $ai_usage['interactions']; ?></div>
							<div class="stat-label"><?php _e( 'AI Plans Generated', 'partyminder' ); ?></div>
							<div class="stat-sublabel">$<?php echo number_format( $ai_usage['total'], 2 ); ?> <?php _e( 'this month', 'partyminder' ); ?></div>
						</div>
					</div>
					
					<div class="stat-card">
						<div class="stat-icon">📅</div>
						<div class="stat-content">
							<div class="stat-number"><?php echo count( $upcoming_events ); ?></div>
							<div class="stat-label"><?php _e( 'Upcoming Events', 'partyminder' ); ?></div>
						</div>
					</div>
				</div>
				
				<!-- Quick Actions -->
				<div class="quick-actions">
					<h2><?php _e( 'Quick Actions', 'partyminder' ); ?></h2>
					<div class="action-buttons">
						<a href="<?php echo esc_url( PartyMinder::get_create_event_url() ); ?>" class="button button-primary button-large">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php _e( 'Create New Event', 'partyminder' ); ?>
						</a>
						
						<a href="<?php echo admin_url( 'admin.php?page=partyminder-ai' ); ?>" class="button button-secondary">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php _e( 'AI Assistant', 'partyminder' ); ?>
						</a>
						
						<a href="<?php echo admin_url( 'admin.php?page=partyminder-events' ); ?>" class="button button-secondary">
							<span class="dashicons dashicons-calendar-alt"></span>
							<?php _e( 'View All Events', 'partyminder' ); ?>
						</a>
						
						<a href="<?php echo esc_url( PartyMinder::get_profile_url() ); ?>" class="button button-secondary">
							<span class="dashicons dashicons-admin-users"></span>
							<?php _e( 'My Profile', 'partyminder' ); ?>
						</a>
					</div>
				</div>
				
				<!-- Upcoming Events -->
				<?php if ( ! empty( $upcoming_events ) ) : ?>
				<div class="upcoming-events">
					<h2><?php _e( 'Upcoming Events', 'partyminder' ); ?></h2>
					<div class="events-list">
						<?php foreach ( $upcoming_events as $event ) : ?>
							<div class="event-item">
								<div class="event-date">
									<div class="date-day"><?php echo date( 'j', strtotime( $event->event_date ) ); ?></div>
									<div class="date-month"><?php echo date( 'M', strtotime( $event->event_date ) ); ?></div>
								</div>
								<div class="event-details">
									<h3><a href="<?php echo home_url( '/events/' . $event->slug ); ?>"><?php echo esc_html( $event->title ); ?></a></h3>
									<div class="event-meta">
										<span><?php echo date( 'g:i A', strtotime( $event->event_date ) ); ?></span>
										<span><?php echo $event->guest_stats->confirmed; ?> confirmed</span>
										<?php if ( $event->venue_info ) : ?>
											<span><?php echo esc_html( $event->venue_info ); ?></span>
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
					<h2><?php _e( 'Getting Started', 'partyminder' ); ?></h2>
					<div class="steps">
						<div class="step">
							<div class="step-number">1</div>
							<div class="step-content">
								<h3><?php _e( 'Configure AI Settings', 'partyminder' ); ?></h3>
								<p><?php _e( 'Add your OpenAI API key to enable intelligent party planning.', 'partyminder' ); ?></p>
								<a href="<?php echo admin_url( 'admin.php?page=partyminder-settings' ); ?>"><?php _e( 'Go to Settings', 'partyminder' ); ?></a>
							</div>
						</div>
						
						<div class="step">
							<div class="step-number">2</div>
							<div class="step-content">
								<h3><?php _e( 'Create Your First Event', 'partyminder' ); ?></h3>
								<p><?php _e( 'Set up a party event and start inviting guests.', 'partyminder' ); ?></p>
								<a href="<?php echo esc_url( PartyMinder::get_create_event_url() ); ?>"><?php _e( 'Create Event', 'partyminder' ); ?></a>
							</div>
						</div>
						
						<div class="step">
							<div class="step-number">3</div>
							<div class="step-content">
								<h3><?php _e( 'Use AI Planning', 'partyminder' ); ?></h3>
								<p><?php _e( 'Generate intelligent party plans with menu suggestions and timelines.', 'partyminder' ); ?></p>
								<a href="<?php echo admin_url( 'admin.php?page=partyminder-ai' ); ?>"><?php _e( 'Try AI Assistant', 'partyminder' ); ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function events_page() {
		$event_manager = new PartyMinder_Event_Manager();

		// Get all events from custom table
		global $wpdb;
		$events_table = $wpdb->prefix . 'partyminder_events';
		$events       = $wpdb->get_results( "SELECT * FROM $events_table ORDER BY event_date DESC" );

		// Add guest stats to each event
		foreach ( $events as $event ) {
			$event->guest_stats = $event_manager->get_guest_stats( $event->id );
		}

		?>
		<div class="wrap">
			<h1><?php _e( 'All Events', 'partyminder' ); ?></h1>
			
			<a href="<?php echo esc_url( PartyMinder::get_create_event_url() ); ?>" class="page-title-action">
				<?php _e( 'Add New Event', 'partyminder' ); ?>
			</a>
			
			<?php if ( empty( $events ) ) : ?>
				<div class="no-events">
					<p><?php _e( 'No events found.', 'partyminder' ); ?></p>
					<a href="<?php echo esc_url( PartyMinder::get_create_event_url() ); ?>" class="button button-primary">
						<?php _e( 'Create Your First Event', 'partyminder' ); ?>
					</a>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php _e( 'Title', 'partyminder' ); ?></th>
							<th><?php _e( 'Event Date', 'partyminder' ); ?></th>
							<th><?php _e( 'Venue', 'partyminder' ); ?></th>
							<th><?php _e( 'Guests', 'partyminder' ); ?></th>
							<th><?php _e( 'Status', 'partyminder' ); ?></th>
							<th><?php _e( 'Actions', 'partyminder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $events as $event ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $event->title ); ?></strong>
									<div class="row-actions">
										<span class="view">
											<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" target="_blank">
												<?php _e( 'View', 'partyminder' ); ?>
											</a>
										</span>
										<span class="edit"> | 
											<a href="<?php echo PartyMinder::get_edit_event_url( $event->id ); ?>">
												<?php _e( 'Edit', 'partyminder' ); ?>
											</a>
										</span>
										<span class="delete"> | 
											<a href="#" class="delete-event-link" 
												data-event-id="<?php echo esc_attr( $event->id ); ?>"
												data-event-title="<?php echo esc_attr( $event->title ); ?>"
												style="color: #dc3545;">
												<?php _e( 'Delete', 'partyminder' ); ?>
											</a>
										</span>
									</div>
								</td>
								<td><?php echo date( 'M j, Y g:i A', strtotime( $event->event_date ) ); ?></td>
								<td><?php echo esc_html( $event->venue_info ); ?></td>
								<td>
									<?php echo $event->guest_stats->confirmed; ?> confirmed
									<?php if ( $event->guest_limit > 0 ) : ?>
										/ <?php echo $event->guest_limit; ?> max
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $event->event_status ); ?></td>
								<td>
									<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="button button-small" target="_blank">
										<?php _e( 'View', 'partyminder' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			// Handle delete event clicks
			$('.delete-event-link').on('click', function(e) {
				e.preventDefault();
				
				const eventId = $(this).data('event-id');
				const eventTitle = $(this).data('event-title');
				const deleteLink = $(this);
				const row = deleteLink.closest('tr');
				
				const confirmMessage = '<?php _e( 'Are you sure you want to delete', 'partyminder' ); ?> "' + eventTitle + '"?\n\n<?php _e( 'This action cannot be undone. All RSVPs, invitations, and related data will be permanently deleted.', 'partyminder' ); ?>';
				
				if (!confirm(confirmMessage)) {
					return;
				}
				
				// Show loading state
				deleteLink.text('<?php _e( 'Deleting...', 'partyminder' ); ?>').css('color', '#666');
				
				$.ajax({
					url: partyminder_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'partyminder_admin_delete_event',
						event_id: eventId,
						nonce: partyminder_admin.event_nonce
					},
					success: function(response) {
						if (response.success) {
							// Remove the row with animation
							row.fadeOut(300, function() {
								$(this).remove();
								
								// Check if table is now empty
								if ($('.wp-list-table tbody tr').length === 0) {
									location.reload();
								}
							});
							
							// Show success message
							$('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
								.insertAfter('.wrap h1')
								.delay(3000)
								.fadeOut();
						} else {
							alert(response.data || '<?php _e( 'Error deleting event', 'partyminder' ); ?>');
							deleteLink.text('<?php _e( 'Delete', 'partyminder' ); ?>').css('color', '#dc3545');
						}
					},
					error: function() {
						alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
						deleteLink.text('<?php _e( 'Delete', 'partyminder' ); ?>').css('color', '#dc3545');
					}
				});
			});
		});
		</script>
		
		<?php
	}

	/**
	 * Event creation is now handled by the public page at /create-event/
	 *
	 * This admin method was removed as part of standardizing all pages to use
	 * Method 2 (Public Pages with Content Injection) rather than duplicate admin pages.
	 *
	 * @deprecated Use public create-event page instead
	 */
	// create_event_page method removed - see /create-event/ public page

	public function ai_page() {
		$ai_assistant = new PartyMinder_AI_Assistant();
		$usage        = $ai_assistant->get_monthly_usage();
		?>
		<div class="wrap">
			<h1><?php _e( 'AI Party Planning Assistant', 'partyminder' ); ?></h1>
			
			<div class="ai-usage-summary">
				<h2><?php _e( 'Monthly Usage', 'partyminder' ); ?></h2>
				<p>
				<?php
				printf(
					__( 'Used: $%1$s of $%2$s limit (%3$d interactions)', 'partyminder' ),
					number_format( $usage['total'], 2 ),
					number_format( $usage['limit'], 2 ),
					$usage['interactions']
				);
				?>
					</p>
			</div>
			
			<div class="ai-generator">
				<h2><?php _e( 'Generate Party Plan', 'partyminder' ); ?></h2>
				
				<form id="ai-plan-form">
					<table class="form-table">
						<tr>
							<th><label for="event_title"><?php _e( 'Event Title', 'partyminder' ); ?></label></th>
							<td>
								<input type="text" id="event_title" name="event_title" placeholder="<?php _e( 'e.g., Birthday Party, Dinner Gathering, Game Night', 'partyminder' ); ?>" class="regular-text" required>
							</td>
						</tr>
						<tr>
							<th><label for="guest_count"><?php _e( 'Number of Guests', 'partyminder' ); ?></label></th>
							<td><input type="number" id="guest_count" name="guest_count" value="8" min="2" max="50" required></td>
						</tr>
						<tr>
							<th><label for="dietary"><?php _e( 'Dietary Restrictions', 'partyminder' ); ?></label></th>
							<td><input type="text" id="dietary" name="dietary" placeholder="<?php _e( 'e.g., vegetarian, gluten-free', 'partyminder' ); ?>"></td>
						</tr>
						<tr>
							<th><label for="budget"><?php _e( 'Budget', 'partyminder' ); ?></label></th>
							<td>
								<select id="budget" name="budget" required>
									<option value="budget"><?php _e( 'Budget ($15-25/person)', 'partyminder' ); ?></option>
									<option value="moderate" selected><?php _e( 'Moderate ($25-40/person)', 'partyminder' ); ?></option>
									<option value="premium"><?php _e( 'Premium ($40+/person)', 'partyminder' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
					
					<p class="submit">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php _e( 'Generate AI Plan', 'partyminder' ); ?>
						</button>
					</p>
				</form>
				
				<div id="ai-result" style="display: none;">
					<h3><?php _e( 'Generated Plan', 'partyminder' ); ?></h3>
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
				$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e( 'Generating...', 'partyminder' ); ?>');
				
				$.ajax({
					url: partyminder_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'partyminder_generate_ai_plan',
						nonce: partyminder_admin.nonce,
						event_title: $('#event_title').val(),
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
		if ( isset( $_POST['submit'] ) ) {
			if ( ! wp_verify_nonce( $_POST['partyminder_settings_nonce'] ?? '', 'partyminder_settings' ) || ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Security check failed', 'partyminder' ) );
			}
			// Save settings
			update_option( 'partyminder_ai_provider', sanitize_text_field( $_POST['ai_provider'] ) );
			update_option( 'partyminder_ai_api_key', sanitize_text_field( $_POST['ai_api_key'] ) );
			update_option( 'partyminder_ai_model', sanitize_text_field( $_POST['ai_model'] ) );
			update_option( 'partyminder_ai_cost_limit_monthly', intval( $_POST['ai_cost_limit_monthly'] ) );

			update_option( 'partyminder_email_from_name', sanitize_text_field( $_POST['email_from_name'] ) );
			update_option( 'partyminder_email_from_address', sanitize_email( $_POST['email_from_address'] ) );

			update_option( 'partyminder_enable_public_events', isset( $_POST['enable_public_events'] ) );
			update_option( 'partyminder_demo_mode', isset( $_POST['demo_mode'] ) );
			update_option( 'partyminder_track_analytics', isset( $_POST['track_analytics'] ) );

			update_option( 'partyminder_primary_color', sanitize_hex_color( $_POST['primary_color'] ) );
			update_option( 'partyminder_secondary_color', sanitize_hex_color( $_POST['secondary_color'] ) );
			update_option( 'partyminder_button_style', sanitize_text_field( $_POST['button_style'] ) );
			update_option( 'partyminder_form_layout', sanitize_text_field( $_POST['form_layout'] ) );

			// Communities settings
			update_option( 'partyminder_enable_communities', isset( $_POST['enable_communities'] ) );
			update_option( 'partyminder_enable_at_protocol', isset( $_POST['enable_at_protocol'] ) );
			update_option( 'partyminder_communities_require_approval', isset( $_POST['communities_require_approval'] ) );
			update_option( 'partyminder_max_communities_per_user', intval( $_POST['max_communities_per_user'] ) );

			echo '<div class="notice notice-success"><p>' . __( 'Settings saved!', 'partyminder' ) . '</p></div>';
		}

		// Get current values
		$ai_provider   = get_option( 'partyminder_ai_provider', 'openai' );
		$ai_api_key    = get_option( 'partyminder_ai_api_key', '' );
		$ai_model      = get_option( 'partyminder_ai_model', 'gpt-4' );
		$ai_cost_limit = get_option( 'partyminder_ai_cost_limit_monthly', 50 );

		$email_from_name    = get_option( 'partyminder_email_from_name', get_bloginfo( 'name' ) );
		$email_from_address = get_option( 'partyminder_email_from_address', get_option( 'admin_email' ) );

		$enable_public_events = get_option( 'partyminder_enable_public_events', true );
		$demo_mode            = get_option( 'partyminder_demo_mode', true );
		$track_analytics      = get_option( 'partyminder_track_analytics', true );

		$primary_color   = get_option( 'partyminder_primary_color', '#667eea' );
		$secondary_color = get_option( 'partyminder_secondary_color', '#764ba2' );
		$button_style    = get_option( 'partyminder_button_style', 'rounded' );
		$form_layout     = get_option( 'partyminder_form_layout', 'card' );
		?>
		<div class="wrap">
			<h1><?php _e( 'PartyMinder Settings', 'partyminder' ); ?></h1>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'partyminder_settings', 'partyminder_settings_nonce' ); ?>
				
				<h2><?php _e( 'AI Configuration', 'partyminder' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="ai_provider"><?php _e( 'AI Provider', 'partyminder' ); ?></label></th>
						<td>
							<select id="ai_provider" name="ai_provider">
								<option value="openai" <?php selected( $ai_provider, 'openai' ); ?>>OpenAI (GPT-4)</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ai_api_key"><?php _e( 'API Key', 'partyminder' ); ?></label></th>
						<td>
							<input type="password" id="ai_api_key" name="ai_api_key" value="<?php echo esc_attr( $ai_api_key ); ?>" class="regular-text" />
							<p class="description"><?php _e( 'Get your API key from OpenAI. Leave blank for demo mode.', 'partyminder' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="ai_model"><?php _e( 'Model', 'partyminder' ); ?></label></th>
						<td>
							<select id="ai_model" name="ai_model">
								<option value="gpt-4" <?php selected( $ai_model, 'gpt-4' ); ?>>GPT-4</option>
								<option value="gpt-3.5-turbo" <?php selected( $ai_model, 'gpt-3.5-turbo' ); ?>>GPT-3.5 Turbo</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ai_cost_limit_monthly"><?php _e( 'Monthly Cost Limit ($)', 'partyminder' ); ?></label></th>
						<td><input type="number" id="ai_cost_limit_monthly" name="ai_cost_limit_monthly" value="<?php echo esc_attr( $ai_cost_limit ); ?>" min="1" max="1000" /></td>
					</tr>
				</table>
				
				<h2><?php _e( 'Email Settings', 'partyminder' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="email_from_name"><?php _e( 'From Name', 'partyminder' ); ?></label></th>
						<td><input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr( $email_from_name ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="email_from_address"><?php _e( 'From Email', 'partyminder' ); ?></label></th>
						<td><input type="email" id="email_from_address" name="email_from_address" value="<?php echo esc_attr( $email_from_address ); ?>" class="regular-text" /></td>
					</tr>
				</table>
				
				<h2><?php _e( 'Features', 'partyminder' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php _e( 'Public Events', 'partyminder' ); ?></th>
						<td><label><input type="checkbox" name="enable_public_events" value="1" <?php checked( $enable_public_events ); ?> /> <?php _e( 'Allow public event listings', 'partyminder' ); ?></label></td>
					</tr>
					<tr>
						<th><?php _e( 'Demo Mode', 'partyminder' ); ?></th>
						<td><label><input type="checkbox" name="demo_mode" value="1" <?php checked( $demo_mode ); ?> /> <?php _e( 'Use demo AI responses when no API key configured', 'partyminder' ); ?></label></td>
					</tr>
					<tr>
						<th><?php _e( 'Analytics', 'partyminder' ); ?></th>
						<td><label><input type="checkbox" name="track_analytics" value="1" <?php checked( $track_analytics ); ?> /> <?php _e( 'Track usage analytics', 'partyminder' ); ?></label></td>
					</tr>
				</table>
				
				<h2><?php _e( 'Styling', 'partyminder' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="primary_color"><?php _e( 'Primary Color', 'partyminder' ); ?></label></th>
						<td><input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr( $primary_color ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="secondary_color"><?php _e( 'Secondary Color', 'partyminder' ); ?></label></th>
						<td><input type="color" id="secondary_color" name="secondary_color" value="<?php echo esc_attr( $secondary_color ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="button_style"><?php _e( 'Button Style', 'partyminder' ); ?></label></th>
						<td>
							<select id="button_style" name="button_style">
								<option value="rounded" <?php selected( $button_style, 'rounded' ); ?>><?php _e( 'Rounded', 'partyminder' ); ?></option>
								<option value="square" <?php selected( $button_style, 'square' ); ?>><?php _e( 'Square', 'partyminder' ); ?></option>
								<option value="pill" <?php selected( $button_style, 'pill' ); ?>><?php _e( 'Pill', 'partyminder' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="form_layout"><?php _e( 'Form Layout', 'partyminder' ); ?></label></th>
						<td>
							<select id="form_layout" name="form_layout">
								<option value="card" <?php selected( $form_layout, 'card' ); ?>><?php _e( 'Card Style', 'partyminder' ); ?></option>
								<option value="minimal" <?php selected( $form_layout, 'minimal' ); ?>><?php _e( 'Minimal', 'partyminder' ); ?></option>
								<option value="classic" <?php selected( $form_layout, 'classic' ); ?>><?php _e( 'Classic', 'partyminder' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				
				<!-- Communities Settings Section -->
				<h2><?php _e( 'Communities & AT Protocol', 'partyminder' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php _e( 'Communities Feature', 'partyminder' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enable_communities" value="1" 
										<?php checked( get_option( 'partyminder_enable_communities', false ) ); ?> />
								<?php _e( 'Enable communities feature', 'partyminder' ); ?>
							</label>
							<p class="description">
								<?php _e( '⚠️ FEATURE FLAG: Communities feature is disabled by default for safe deployment. Enable only when ready to use.', 'partyminder' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'AT Protocol Integration', 'partyminder' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enable_at_protocol" value="1" 
										<?php checked( get_option( 'partyminder_enable_at_protocol', false ) ); ?> />
								<?php _e( 'Enable AT Protocol DID generation', 'partyminder' ); ?>
							</label>
							<p class="description">
								<?php _e( '⚠️ FEATURE FLAG: AT Protocol integration is disabled by default. Enable for federated identity features.', 'partyminder' ); ?>
							</p>
						</td>
					</tr>
					<?php if ( PartyMinder_Feature_Flags::is_communities_enabled() ) : ?>
					<tr>
						<th><?php _e( 'Community Approval', 'partyminder' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="communities_require_approval" value="1" 
										<?php checked( get_option( 'partyminder_communities_require_approval', true ) ); ?> />
								<?php _e( 'Require admin approval for new communities', 'partyminder' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="max_communities_per_user"><?php _e( 'Max Communities per User', 'partyminder' ); ?></label></th>
						<td>
							<input type="number" id="max_communities_per_user" name="max_communities_per_user" 
									value="<?php echo esc_attr( get_option( 'partyminder_max_communities_per_user', 10 ) ); ?>" 
									min="1" max="50" />
							<p class="description"><?php _e( 'Maximum number of communities each user can create.', 'partyminder' ); ?></p>
						</td>
					</tr>
					<?php endif; ?>
				</table>
				
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function admin_notices() {
		if ( ! get_option( 'partyminder_ai_api_key' ) && ! get_option( 'partyminder_demo_mode' ) ) {
			$screen = get_current_screen();
			if ( $screen && strpos( $screen->id, 'partyminder' ) !== false ) {
				echo '<div class="notice notice-warning"><p>';
				printf(
					__( 'PartyMinder AI features require an API key. <a href="%s">Configure settings</a> or enable demo mode.', 'partyminder' ),
					admin_url( 'admin.php?page=partyminder-settings' )
				);
				echo '</p></div>';
			}
		}
	}

	/**
	 * Communities admin page
	 */
	public function communities_page() {
		global $wpdb;
		?>
		<div class="wrap">
			<h1><?php _e( 'Communities Management', 'partyminder' ); ?></h1>
			
			<?php if ( ! PartyMinder_Feature_Flags::is_communities_enabled() ) : ?>
				<div class="notice notice-warning">
					<p>
						<strong><?php _e( 'Communities Feature Disabled', 'partyminder' ); ?></strong><br>
						<?php
						printf(
							__( 'The communities feature is currently disabled. <a href="%s">Enable it in settings</a> to start using communities.', 'partyminder' ),
							admin_url( 'admin.php?page=partyminder-settings' )
						);
						?>
					</p>
				</div>
				
				<div class="communities-preview">
					<h2><?php _e( 'Communities Feature Preview', 'partyminder' ); ?></h2>
					<p><?php _e( 'Communities allow members to create overlapping groups for different purposes:', 'partyminder' ); ?></p>
					<ul>
						<li><?php _e( '🏢 Work communities for office events', 'partyminder' ); ?></li>
						<li><?php _e( '⛪ Church or religious communities', 'partyminder' ); ?></li>
						<li><?php _e( '👨‍👩‍👧‍👦 Family and friend groups', 'partyminder' ); ?></li>
						<li><?php _e( '🌍 Global communities across multiple sites', 'partyminder' ); ?></li>
					</ul>
					<p><?php _e( 'Each community gets its own AT Protocol DID for cross-site compatibility and member identity portability.', 'partyminder' ); ?></p>
				</div>
				
			<?php else : ?>
				<?php
				// Communities are enabled - show management interface
				$community_manager = new PartyMinder_Community_Manager();
				$communities       = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}partyminder_communities ORDER BY created_at DESC LIMIT 20" );
				?>
				
				<div class="communities-stats">
					<h2><?php _e( 'Community Statistics', 'partyminder' ); ?></h2>
					<?php
					global $wpdb;
					$communities_table = $wpdb->prefix . 'partyminder_communities';
					$members_table     = $wpdb->prefix . 'partyminder_community_members';

					$total_communities  = $wpdb->get_var( "SELECT COUNT(*) FROM $communities_table WHERE is_active = 1" );
					$total_members      = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM $members_table WHERE status = 'active'" );
					$active_communities = $wpdb->get_var( "SELECT COUNT(*) FROM $communities_table WHERE is_active = 1 AND member_count > 1" );
					?>
					<div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
						<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
							<h3 style="margin: 0; font-size: 2em; color: #667eea;"><?php echo $total_communities; ?></h3>
							<p style="margin: 5px 0 0 0;"><?php _e( 'Total Communities', 'partyminder' ); ?></p>
						</div>
						<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
							<h3 style="margin: 0; font-size: 2em; color: #28a745;"><?php echo $total_members; ?></h3>
							<p style="margin: 5px 0 0 0;"><?php _e( 'Community Members', 'partyminder' ); ?></p>
						</div>
						<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
							<h3 style="margin: 0; font-size: 2em; color: #764ba2;"><?php echo $active_communities; ?></h3>
							<p style="margin: 5px 0 0 0;"><?php _e( 'Active Communities', 'partyminder' ); ?></p>
						</div>
					</div>
				</div>
				
				<div class="communities-list">
					<h2><?php _e( 'Recent Communities', 'partyminder' ); ?></h2>
					
					<?php if ( empty( $communities ) ) : ?>
						<div class="no-communities">
							<p><?php _e( 'No communities created yet.', 'partyminder' ); ?></p>
							<p><?php _e( 'Communities will appear here once users start creating them.', 'partyminder' ); ?></p>
						</div>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php _e( 'Community', 'partyminder' ); ?></th>
									<th><?php _e( 'Creator', 'partyminder' ); ?></th>
									<th><?php _e( 'Members', 'partyminder' ); ?></th>
									<th><?php _e( 'Privacy', 'partyminder' ); ?></th>
									<th><?php _e( 'Created', 'partyminder' ); ?></th>
									<th><?php _e( 'AT Protocol DID', 'partyminder' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $communities as $community ) : ?>
									<?php $creator = get_user_by( 'id', $community->creator_id ); ?>
									<tr>
										<td>
											<strong><?php echo esc_html( $community->name ); ?></strong><br>
											<small style="color: #666;"><?php echo esc_html( $community->description ); ?></small>
										</td>
										<td>
											<?php echo $creator ? esc_html( $creator->display_name ) : esc_html( $community->creator_email ); ?>
										</td>
										<td><?php echo (int) $community->member_count; ?></td>
										<td>
											<span class="privacy-badge" style="padding: 2px 8px; border-radius: 10px; font-size: 0.8em; <?php echo $community->privacy === 'public' ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;'; ?>">
												<?php echo esc_html( ucfirst( $community->privacy ) ); ?>
											</span>
										</td>
										<td><?php echo date( 'M j, Y', strtotime( $community->created_at ) ); ?></td>
										<td>
											<?php if ( $community->at_protocol_did ) : ?>
												<code style="font-size: 0.8em;"><?php echo esc_html( $community->at_protocol_did ); ?></code>
											<?php else : ?>
												<span style="color: #666;">—</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
				
				<?php if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) : ?>
					<div class="at-protocol-tools">
						<h2><?php _e( 'AT Protocol Tools', 'partyminder' ); ?></h2>
						<p><?php _e( 'Advanced tools for managing AT Protocol integration.', 'partyminder' ); ?></p>
						
						<div class="button-group">
							<button type="button" class="button" onclick="bulkCreateDIDs()">
								<?php _e( 'Bulk Create DIDs for Existing Users', 'partyminder' ); ?>
							</button>
							<button type="button" class="button" onclick="syncATProtocol()">
								<?php _e( 'Sync AT Protocol Data', 'partyminder' ); ?>
							</button>
						</div>
						
						<script>
						function bulkCreateDIDs() {
							if (confirm('<?php _e( 'This will create AT Protocol DIDs for all existing users. Continue?', 'partyminder' ); ?>')) {
								// TODO: Implement bulk DID creation
								alert('<?php _e( 'Feature coming soon!', 'partyminder' ); ?>');
							}
						}
						
						function syncATProtocol() {
							if (confirm('<?php _e( 'This will sync all member identities with AT Protocol. Continue?', 'partyminder' ); ?>')) {
								// TODO: Implement AT Protocol sync
								alert('<?php _e( 'Feature coming soon!', 'partyminder' ); ?>');
							}
						}
						</script>
					</div>
				<?php endif; ?>
				
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Members admin page
	 */
	public function members_page() {
		if ( ! PartyMinder_Feature_Flags::is_communities_enabled() ) {
			wp_redirect( admin_url( 'admin.php?page=partyminder-communities' ) );
			exit;
		}

		$identity_manager = new PartyMinder_Member_Identity_Manager();
		$member_stats     = $identity_manager->get_member_stats();
		?>
		<div class="wrap">
			<h1><?php _e( 'Community Members', 'partyminder' ); ?></h1>
			
			<div class="member-stats">
				<h2><?php _e( 'Member Identity Statistics', 'partyminder' ); ?></h2>
				<div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
					<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
						<h3 style="margin: 0; font-size: 2em; color: #667eea;"><?php echo $member_stats['total_identities']; ?></h3>
						<p style="margin: 5px 0 0 0;"><?php _e( 'Total Identities', 'partyminder' ); ?></p>
					</div>
					<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
						<h3 style="margin: 0; font-size: 2em; color: #28a745;"><?php echo $member_stats['verified_identities']; ?></h3>
						<p style="margin: 5px 0 0 0;"><?php _e( 'Verified', 'partyminder' ); ?></p>
					</div>
					<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
						<h3 style="margin: 0; font-size: 2em; color: #764ba2;"><?php echo $member_stats['synced_identities']; ?></h3>
						<p style="margin: 5px 0 0 0;"><?php _e( 'Synced', 'partyminder' ); ?></p>
					</div>
					<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
						<h3 style="margin: 0; font-size: 2em; color: #dc3545;"><?php echo $member_stats['sync_pending']; ?></h3>
						<p style="margin: 5px 0 0 0;"><?php _e( 'Sync Pending', 'partyminder' ); ?></p>
					</div>
				</div>
			</div>
			
			<div class="recent-members">
				<h2><?php _e( 'Recent Member Identities', 'partyminder' ); ?></h2>
				<?php
				global $wpdb;
				$identities_table  = $wpdb->prefix . 'partyminder_member_identities';
				$recent_identities = $wpdb->get_results( "SELECT * FROM $identities_table ORDER BY created_at DESC LIMIT 20" );
				?>
				
				<?php if ( empty( $recent_identities ) ) : ?>
					<div class="no-identities">
						<p><?php _e( 'No member identities created yet.', 'partyminder' ); ?></p>
						<p><?php _e( 'Member identities will be created automatically when AT Protocol is enabled and users log in.', 'partyminder' ); ?></p>
					</div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php _e( 'Member', 'partyminder' ); ?></th>
								<th><?php _e( 'AT Protocol DID', 'partyminder' ); ?></th>
								<th><?php _e( 'Handle', 'partyminder' ); ?></th>
								<th><?php _e( 'Status', 'partyminder' ); ?></th>
								<th><?php _e( 'Last Sync', 'partyminder' ); ?></th>
								<th><?php _e( 'Created', 'partyminder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_identities as $identity ) : ?>
								<tr>
									<td>
										<strong><?php echo esc_html( $identity->display_name ); ?></strong><br>
										<small style="color: #666;"><?php echo esc_html( $identity->email ); ?></small>
									</td>
									<td>
										<code style="font-size: 0.8em;"><?php echo esc_html( $identity->at_protocol_did ); ?></code>
									</td>
									<td>
										<?php if ( $identity->at_protocol_handle ) : ?>
											<code style="font-size: 0.8em;"><?php echo esc_html( $identity->at_protocol_handle ); ?></code>
										<?php else : ?>
											<span style="color: #666;">—</span>
										<?php endif; ?>
									</td>
									<td>
										<span class="status-badge" style="padding: 2px 8px; border-radius: 10px; font-size: 0.8em; <?php echo $identity->is_verified ? 'background: #d4edda; color: #155724;' : 'background: #fff3cd; color: #856404;'; ?>">
											<?php echo $identity->is_verified ? __( 'Verified', 'partyminder' ) : __( 'Pending', 'partyminder' ); ?>
										</span>
									</td>
									<td>
										<?php if ( $identity->last_sync_at ) : ?>
											<?php echo human_time_diff( strtotime( $identity->last_sync_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'partyminder' ); ?>
										<?php else : ?>
											<span style="color: #666;"><?php _e( 'Never', 'partyminder' ); ?></span>
										<?php endif; ?>
									</td>
									<td><?php echo date( 'M j, Y', strtotime( $identity->created_at ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}