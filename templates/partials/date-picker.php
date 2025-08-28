<?php
/**
 * Enhanced Date Picker Partial Template
 * Provides advanced date/time selection with ranges, repeats, and all-day options
 * Used in create-event, edit-event, and create-community-event forms
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set default values if not provided
$start_date = $start_date ?? ($_POST['start_date'] ?? '');
$start_time = $start_time ?? ($_POST['start_time'] ?? '18:00');
$end_date = $end_date ?? ($_POST['end_date'] ?? '');
$end_time = $end_time ?? ($_POST['end_time'] ?? '21:00');
$all_day = $all_day ?? ($_POST['all_day'] ?? false);
$date_range = $date_range ?? ($_POST['date_range'] ?? false);
$repeat_type = $repeat_type ?? ($_POST['repeat_type'] ?? 'none');
$repeat_end = $repeat_end ?? ($_POST['repeat_end'] ?? '');

// For backward compatibility, also populate the single event_date field
$event_date_value = '';
if ($start_date) {
	$event_date_value = $start_date;
	if (!$all_day && $start_time) {
		$event_date_value .= ' ' . $start_time;
	}
}
?>

<!-- Enhanced Date & Time Section -->
<div class="pm-section pm-date-picker-section pm-mb-4">
	<h3 class="pm-section-header"><?php _e('When is your event?', 'partyminder'); ?></h3>
	
	<!-- Quick Options -->
	<div class="pm-date-options pm-mb-4">
		<label class="pm-option-toggle">
			<input type="checkbox" id="all_day" name="all_day" value="1" class="pm-checkbox" 
				   <?php checked($all_day); ?>>
			<span class="pm-option-label"><?php _e('All day event', 'partyminder'); ?></span>
		</label>
		<label class="pm-option-toggle">
			<input type="checkbox" id="date_range" name="date_range" value="1" class="pm-checkbox" 
				   <?php checked($date_range); ?>>
			<span class="pm-option-label"><?php _e('Multi-day event', 'partyminder'); ?></span>
		</label>
	</div>
	
	<!-- Date Selection -->
	<div class="pm-date-time-grid">
		<!-- Start Date & Time -->
		<div class="pm-form-group">
			<label for="start_date" class="pm-form-label"><?php _e('Start Date', 'partyminder'); ?> *</label>
			<input type="text" id="start_date" name="start_date" class="pm-form-input pm-date-input" 
				   value="<?php echo esc_attr($start_date); ?>"
				   placeholder="<?php _e('Select start date...', 'partyminder'); ?>" required />
		</div>
		
		<div class="pm-form-group pm-time-group">
			<label for="start_time" class="pm-form-label"><?php _e('Start Time', 'partyminder'); ?></label>
			<input type="text" id="start_time" name="start_time" class="pm-form-input pm-time-input" 
				   value="<?php echo esc_attr($start_time); ?>"
				   placeholder="<?php _e('6:00 PM', 'partyminder'); ?>" />
		</div>
		
		<!-- End Date & Time (initially hidden) -->
		<div class="pm-form-group pm-end-date-group" style="<?php echo $date_range ? '' : 'display: none;'; ?>">
			<label for="end_date" class="pm-form-label"><?php _e('End Date', 'partyminder'); ?></label>
			<input type="text" id="end_date" name="end_date" class="pm-form-input pm-date-input" 
				   value="<?php echo esc_attr($end_date); ?>"
				   placeholder="<?php _e('Select end date...', 'partyminder'); ?>" />
		</div>
		
		<div class="pm-form-group pm-time-group pm-end-time-group" style="<?php echo $date_range ? '' : 'display: none;'; ?>">
			<label for="end_time" class="pm-form-label"><?php _e('End Time', 'partyminder'); ?></label>
			<input type="text" id="end_time" name="end_time" class="pm-form-input pm-time-input" 
				   value="<?php echo esc_attr($end_time); ?>"
				   placeholder="<?php _e('9:00 PM', 'partyminder'); ?>" />
		</div>
	</div>
	
	<!-- Repeat Options -->
	<div class="pm-form-group pm-repeat-section pm-mt-4">
		<label for="repeat_type" class="pm-form-label"><?php _e('Repeat Event', 'partyminder'); ?></label>
		<select id="repeat_type" name="repeat_type" class="pm-form-input pm-repeat-select">
			<option value="none" <?php selected($repeat_type, 'none'); ?>><?php _e('Does not repeat', 'partyminder'); ?></option>
			<option value="daily" <?php selected($repeat_type, 'daily'); ?>><?php _e('Daily', 'partyminder'); ?></option>
			<option value="weekly" <?php selected($repeat_type, 'weekly'); ?>><?php _e('Weekly', 'partyminder'); ?></option>
			<option value="monthly" <?php selected($repeat_type, 'monthly'); ?>><?php _e('Monthly', 'partyminder'); ?></option>
			<option value="yearly" <?php selected($repeat_type, 'yearly'); ?>><?php _e('Yearly', 'partyminder'); ?></option>
		</select>
	</div>
	
	<!-- Repeat End Date (shown when repeating) -->
	<div class="pm-form-group pm-repeat-end-group" style="<?php echo ($repeat_type && $repeat_type !== 'none') ? '' : 'display: none;'; ?>">
		<label for="repeat_end" class="pm-form-label"><?php _e('Repeat Until', 'partyminder'); ?></label>
		<input type="text" id="repeat_end" name="repeat_end" class="pm-form-input pm-date-input" 
			   value="<?php echo esc_attr($repeat_end); ?>"
			   placeholder="<?php _e('Select end date...', 'partyminder'); ?>" />
	</div>
	
	<!-- Hidden field for backward compatibility -->
	<input type="hidden" id="event_date" name="event_date" value="<?php echo esc_attr($event_date_value); ?>" />
</div>