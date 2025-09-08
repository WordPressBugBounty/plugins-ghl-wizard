<?php

if ( ! function_exists( 'hlwpw_get_associations' ) ) {
    
    function hlwpw_get_associations() {

		$hlwpw_locationId = lcw_get_location_id();
		$hlwpw_access_token = lcw_get_access_token();

		$endpoint = "https://services.leadconnectorhq.com/associations/?locationId={$hlwpw_locationId}&skip=0&limit=0";
		$ghl_version = '2021-07-28';

		$request_args = array(
			'headers' => array(
				'Authorization' => "Bearer {$hlwpw_access_token}",
				'Content-Type' => 'application/json',
				'Version' => $ghl_version,
			),
		);

		$response = wp_remote_get( $endpoint, $request_args );
		$http_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $http_code ) {

			$body = wp_remote_retrieve_body( $response );
			$associations = json_decode( $body )->associations;

			return $associations;

		}
    }
}