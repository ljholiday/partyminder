<?php
/**
 * Reusable Date Picker Template
 * Used in create-event, edit-event, and create-community-event forms
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set default values if not provided
$start_date = $start_date ?? ($_POST['start_date'] ?? '');
$start_time = $start_time ?? ($_POST['start_time'] ?? '');
$end_date = $end_date ?? ($_POST['end_date'] ?? '');
$end_time = $end_time ?? ($_POST['end_time'] ?? '');
$all_day = $all_day ?? ($_POST['all_day'] ?? false);
$recurrence_type = $recurrence_type ?? ($_POST['recurrence_type'] ?? 'none');
$recurrence_interval = $recurrence_interval ?? ($_POST['recurrence_interval'] ?? 1);
?>

<!-- Event Date & Time Section -->
<div class="pm-form-section">
	<h3 class="pm-heading pm-heading-sm pm-mb-4"><?php _e('When is your event?', 'partyminder'); ?></h3>
	
	<!-- All Day Toggle -->
	<div class="pm-form-group pm-mb-4">
		<label class="pm-form-label pm-flex pm-gap-2">
			<input type="checkbox" id="all_day" name="all_day" value="1" class="pm-form-checkbox" 
				   <?php checked($all_day); ?>>
			<?php _e('All day event', 'partyminder'); ?>
		</label>
	</div>
	
	<!-- Start Date & Time -->
	<div class="pm-form-group pm-grid pm-grid-2 pm-gap">
		<div>
			<label for="start_date" class="pm-form-label"><?php _e('Start Date *', 'partyminder'); ?></label>
			<input type="text" id="start_date" name="start_date" class="pm-form-input" 
				   value="<?php echo esc_attr($start_date); ?>"
				   placeholder="<?php _e('Select start date...', 'partyminder'); ?>" required />
		</div>
		<div class="pm-time-field">
			<label for="start_time" class="pm-form-label"><?php _e('Start Time *', 'partyminder'); ?></label>
			<input type="text" id="start_time" name="start_time" class="pm-form-input" 
				   value="<?php echo esc_attr($start_time); ?>"
				   placeholder="<?php _e('Select start time...', 'partyminder'); ?>" />
		</div>
	</div>
	
	<!-- End Date & Time -->
	<div class="pm-form-group pm-grid pm-grid-2 pm-gap">
		<div>
			<label for="end_date" class="pm-form-label"><?php _e('End Date', 'partyminder'); ?></label>
			<input type="text" id="end_date" name="end_date" class="pm-form-input" 
				   value="<?php echo esc_attr($end_date); ?>"
				   placeholder="<?php _e('Select end date...', 'partyminder'); ?>" />
		</div>
		<div class="pm-time-field">
			<label for="end_time" class="pm-form-label"><?php _e('End Time', 'partyminder'); ?></label>
			<input type="text" id="end_time" name="end_time" class="pm-form-input" 
				   value="<?php echo esc_attr($end_time); ?>"
				   placeholder="<?php _e('Select end time...', 'partyminder'); ?>" />
		</div>
	</div>
	
	<!-- Recurrence Options -->
	<div class="pm-form-group pm-mt-4">
		<label for="recurrence_type" class="pm-form-label"><?php _e('Repeat Event', 'partyminder'); ?></label>
		<select id="recurrence_type" name="recurrence_type" class="pm-form-input">
			<option value="none" <?php selected($recurrence_type, 'none'); ?>><?php _e('Does not repeat', 'partyminder'); ?></option>
			<option value="daily" <?php selected($recurrence_type, 'daily'); ?>><?php _e('Daily', 'partyminder'); ?></option>
			<option value="weekly" <?php selected($recurrence_type, 'weekly'); ?>><?php _e('Weekly', 'partyminder'); ?></option>
			<option value="monthly" <?php selected($recurrence_type, 'monthly'); ?>><?php _e('Monthly', 'partyminder'); ?></option>
			<option value="yearly" <?php selected($recurrence_type, 'yearly'); ?>><?php _e('Yearly', 'partyminder'); ?></option>
			<option value="custom" <?php selected($recurrence_type, 'custom'); ?>><?php _e('Custom...', 'partyminder'); ?></option>
		</select>
	</div>
	
	<!-- Recurrence Options -->
	<div class="pm-recurrence-options" style="display: none;">
		<div class="pm-recurrence-interval pm-form-group pm-mt-4">
			<label for="recurrence_interval" class="pm-form-label"><?php _e('Repeat every', 'partyminder'); ?></label>
			<input type="number" id="recurrence_interval" name="recurrence_interval" 
				   class="pm-form-input" min="1" value="<?php echo esc_attr($recurrence_interval); ?>" />
		</div>
	</div>
</div>