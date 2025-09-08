<?php

if ( ! function_exists( 'hlwpw_get_location_custom_values' ) ) {
    
    function hlwpw_get_location_custom_values() {

    	$key = 'hlwpw_location_custom_values';
    	$expiry = 60  * 60 * 24; // 1 day

    	$custom_values = get_transient($key);

    	if ( !empty( $custom_values ) ) {
    		//delete_transient($key);
    		return $custom_values;
    	}

    	$custom_values = array();
		$hlwpw_locationId = lcw_get_location_id();
		$hlwpw_access_token = lcw_get_access_token();

		$endpoint = "https://services.leadconnectorhq.com/locations/{$hlwpw_locationId}/customValues";
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

			$body = json_decode( wp_remote_retrieve_body( $response ) );
			$all_custom_values = $body->customValues;

			foreach ($all_custom_values as $item_key => $value_item) {

				$field_key = str_replace('{{', '', $value_item->fieldKey);
				$field_key = trim (str_replace('}}', '', $field_key));
				$field_key = str_replace('custom_values.', '', $field_key);

				$custom_values[$field_key] = $value_item->value;
			}

			set_transient( $key, $custom_values, $expiry );
			return $custom_values;

		}
    }
}

//$custom_values = hlwpw_get_location_custom_values();

//echo $custom_values['calendarid_saturday_mastermind'];

//echo "<pre>";
//print_r($custom_values);
//echo "</pre>";