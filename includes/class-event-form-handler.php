<?php
/**
 * Event Form Handler
 * Handles validation and processing of event creation forms
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PartyMinder_Event_Form_Handler {

	/**
	 * Validate event form data
	 *
	 * @param array $post_data The $_POST data
	 * @return array Array of validation errors (empty if valid)
	 */
	public static function validate_event_form( $post_data ) {
		$form_errors = array();

		// Validate required fields
		if ( empty( $post_data['event_title'] ) ) {
			$form_errors[] = __( 'Event title is required.', 'partyminder' );
		}
		if ( empty( $post_data['start_date'] ) ) {
			$form_errors[] = __( 'Start date is required.', 'partyminder' );
		}
		if ( empty( $post_data['host_email'] ) ) {
			$form_errors[] = __( 'Host email is required.', 'partyminder' );
		}

		// Validate start time if not all-day event
		if ( empty( $post_data['all_day'] ) && empty( $post_data['start_time'] ) ) {
			$form_errors[] = __( 'Start time is required for timed events.', 'partyminder' );
		}

		// Validate end date/time consistency
		if ( ! empty( $post_data['end_date'] ) ) {
			$start_datetime = $post_data['start_date'];
			if ( ! empty( $post_data['start_time'] ) && empty( $post_data['all_day'] ) ) {
				$start_datetime .= ' ' . $post_data['start_time'];
			}
			
			$end_datetime = $post_data['end_date'];
			if ( ! empty( $post_data['end_time'] ) && empty( $post_data['all_day'] ) ) {
				$end_datetime .= ' ' . $post_data['end_time'];
			}
			
			if ( strtotime( $end_datetime ) <= strtotime( $start_datetime ) ) {
				$form_errors[] = __( 'End date/time must be after start date/time.', 'partyminder' );
			}
		}

		return $form_errors;
	}

	/**
	 * Process event form data into structured event data
	 *
	 * @param array $post_data The $_POST data
	 * @return array Processed event data array
	 */
	public static function process_event_form_data( $post_data ) {
		// Build event datetime from separate fields
		$event_datetime = $post_data['start_date'];
		if ( ! empty( $post_data['start_time'] ) && empty( $post_data['all_day'] ) ) {
			$event_datetime .= ' ' . $post_data['start_time'];
		} else {
			$event_datetime .= ' 00:00:00';
		}

		// Build end datetime if provided
		$end_datetime = null;
		if ( ! empty( $post_data['end_date'] ) ) {
			$end_datetime = $post_data['end_date'];
			if ( ! empty( $post_data['end_time'] ) && empty( $post_data['all_day'] ) ) {
				$end_datetime .= ' ' . $post_data['end_time'];
			} else {
				$end_datetime .= ' 23:59:59';
			}
		}

		$event_data = array(
			'title'       => sanitize_text_field( wp_unslash( $post_data['event_title'] ) ),
			'description' => wp_kses_post( wp_unslash( $post_data['event_description'] ) ),
			'event_date'  => sanitize_text_field( $event_datetime ),
			'venue'       => sanitize_text_field( $post_data['venue_info'] ),
			'guest_limit' => intval( $post_data['guest_limit'] ),
			'host_email'  => sanitize_email( $post_data['host_email'] ),
			'host_notes'  => wp_kses_post( wp_unslash( $post_data['host_notes'] ) ),
			'all_day'     => ! empty( $post_data['all_day'] ) ? 1 : 0,
			'end_date'    => $end_datetime ? sanitize_text_field( $end_datetime ) : null,
			'recurrence_type' => sanitize_text_field( $post_data['recurrence_type'] ?? 'none' ),
		);

		// Add recurrence data if specified
		if ( ! empty( $post_data['recurrence_type'] ) && $post_data['recurrence_type'] !== 'none' ) {
			$event_data['recurrence_interval'] = intval( $post_data['recurrence_interval'] ?? 1 );
			
			if ( $post_data['recurrence_type'] === 'weekly' && ! empty( $post_data['weekly_days'] ) ) {
				$event_data['recurrence_days'] = implode( ',', array_map( 'sanitize_text_field', $post_data['weekly_days'] ) );
			}
			
			if ( $post_data['recurrence_type'] === 'monthly' ) {
				$event_data['monthly_type'] = sanitize_text_field( $post_data['monthly_type'] ?? 'date' );
				if ( $post_data['monthly_type'] === 'weekday' ) {
					$event_data['monthly_week'] = sanitize_text_field( $post_data['monthly_week'] ?? '' );
					$event_data['monthly_day'] = sanitize_text_field( $post_data['monthly_day'] ?? '' );
				}
			}

			if ( $post_data['recurrence_type'] === 'custom' && ! empty( $post_data['custom_days'] ) ) {
				$event_data['recurrence_days'] = implode( ',', array_map( 'sanitize_text_field', $post_data['custom_days'] ) );
				$event_data['recurrence_interval'] = intval( $post_data['custom_interval'] ?? 1 );
			}
		}

		return $event_data;
	}
}