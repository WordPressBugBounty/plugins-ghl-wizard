<?php

if ( ! function_exists( 'hlwpw_get_location_campaigns' ) ) {
	function hlwpw_get_location_campaigns() {
		$location_id = lcw_get_location_id();
		if ( empty( $location_id ) ) {
			return array();
		}

		return LCW_GHL_API_Client::get_cached_collection_dataset(
			'campaigns',
			'/campaigns/',
			'campaigns',
			'2021-04-15',
			array( 'locationId' => $location_id ),
			array()
		);
	}
}
