<?php

if ( ! function_exists( 'hlwpw_get_location_campaigns' ) ) {
    
    function hlwpw_get_location_campaigns() {

    	$key = 'hlwpw_location_campaigns';
    	$expiry = 60  * 60 * 24; // 1 day

    	$campaigns = get_transient($key);

    	if ( !empty( $campaigns ) ) {
    		//delete_transient($key);
    		return $campaigns;
    	}

		$hlwpw_locationId = lcw_get_location_id();
		$hlwpw_access_token = lcw_get_access_token();

		$endpoint = "https://services.leadconnectorhq.com/campaigns/?locationId={$hlwpw_locationId}";
		$ghl_version = '2021-04-15';

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
			$campaigns = json_decode( $body )->campaigns;
			set_transient( $key, $campaigns, $expiry );
			return $campaigns;

		}elseif( 401 === $http_code ){

			hlwpw_get_new_access_token();
			
		}
    }
}