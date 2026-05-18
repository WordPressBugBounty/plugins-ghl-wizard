<?php
/**
 * Create an appointment in LeadConnector.
 *
 * @param array $appointment_data Appointment payload.
 * @return array|WP_Error
 */
function lcw_create_ghl_appointment( $appointment_data ) {
	$version               = '2023-02-21';
	$meeting_location_type = 'custom';

	if ( empty( $appointment_data['meetingLocationType'] ) ) {
		$appointment_data['meetingLocationType'] = $meeting_location_type;
	}

	return LCW_GHL_API_Client::request(
		'POST',
		'/calendars/events/appointments',
		array(
			'version' => $version,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $appointment_data ),
		)
	);
}