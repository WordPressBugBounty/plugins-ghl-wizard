<?php
function lcw_get_calendar_timeslots( $calendar_id, $start_date, $end_date ) {
	if ( empty( $calendar_id ) || empty( $start_date ) || empty( $end_date ) ) {
		return new WP_Error( 'lcw_missing_timeslot_args', __( 'Calendar and date range are required.', 'ghl-wizard' ) );
	}

	$start_date = strtotime($start_date) * 1000; // Convert to milliseconds	
	$end_date = strtotime($end_date) * 1000; // Convert to milliseconds

	return LCW_GHL_API_Client::request(
		'GET',
		'/calendars/' . rawurlencode( $calendar_id ) . '/free-slots',
		array(
			'version' => '2023-02-21',
			'query'   => array(
				'startDate' => $start_date,
				'endDate'   => $end_date,
			),
		)
	);
}