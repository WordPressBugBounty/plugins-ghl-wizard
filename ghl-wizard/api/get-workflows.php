<?php

if ( ! function_exists( 'hlwpw_get_location_workflows' ) ) {
	function hlwpw_get_location_workflows() {
		$location_id = lcw_get_location_id();
		if ( empty( $location_id ) ) {
			return array();
		}

		return LCW_GHL_API_Client::get_cached_collection_dataset(
			'workflows',
			'/workflows/',
			'workflows',
			'2021-04-15',
			array( 'locationId' => $location_id ),
			array()
		);
	}
}
