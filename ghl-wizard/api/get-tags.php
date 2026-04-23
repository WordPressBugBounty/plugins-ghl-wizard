<?php

if ( ! function_exists( 'hlwpw_get_location_tags' ) ) {
	function hlwpw_get_location_tags() {
		$location_id = lcw_get_location_id();
		if ( empty( $location_id ) ) {
			return array();
		}

		$path = sprintf( '/locations/%s/tags', rawurlencode( $location_id ) );

		return LCW_GHL_API_Client::get_cached_collection_dataset(
			'tags',
			$path,
			'tags',
			'2021-04-15',
			array(),
			array()
		);
	}
}
