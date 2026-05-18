<?php

function lcw_get_location_calendars() {
	$location_id = lcw_get_location_id();
	if ( empty( $location_id ) ) {
		return array();
	}

	return LCW_GHL_API_Client::get_cached_collection_dataset(
		'calendars',
		'/calendars/',
		'calendars',
		'2021-07-28',
		array( 'locationId' => $location_id ),
		array()
	);
}
