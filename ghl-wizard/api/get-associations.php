<?php

if ( ! function_exists( 'hlwpw_get_associations' ) ) {
	function hlwpw_get_associations() {
		$location_id = lcw_get_location_id();
		if ( empty( $location_id ) ) {
			return array();
		}

		return LCW_GHL_API_Client::get_cached_collection_dataset(
			'associations',
			'/associations/',
			'associations',
			'2021-07-28',
			array(
				'locationId' => $location_id,
				'skip'       => 0,
				'limit'      => 0,
			),
			array()
		);
	}
}
