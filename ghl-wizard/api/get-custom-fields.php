<?php
/***********************************
    get Custom Fields
    @ v: 1.1
***********************************/
if ( ! function_exists( 'lcw_get_location_custom_fields' ) ) {
    
    function lcw_get_location_custom_fields() {

    	$key = 'lcw_location_cutom_fields';
    	$expiry = 60  * 60 * 24; // 1 day

    	$custom_fields = get_transient($key);

    	if ( !empty( $custom_fields ) ) {
    		//delete_transient($key);
    		return $custom_fields;
    	}

    	$custom_fields = array();
		$hlwpw_locationId = get_option( 'hlwpw_locationId' );
		$hlwpw_access_token = lcw_get_access_token();

		$endpoint = "https://services.leadconnectorhq.com/locations/{$hlwpw_locationId}/customFields";
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
			$all_custom_fields = $body->customFields;


// echo "<pre>";
// print_r($all_custom_fields);
// echo "</pre>";

			foreach ($all_custom_fields as $item_key => $value_item) {

				$field_key = str_replace('contact.', '', $value_item->fieldKey);

				// $custom_fields[$field_key] = array(
				// 	'id'	=> $value_item->id,
				// 	'name'	=> $value_item->name,
				// 	'fieldKey'	=> $field_key,
				// 	'dataType'	=> $value_item->dataType
				// );

				$custom_fields[$field_key] = $value_item->id;
			}

			set_transient( $key, $custom_fields, $expiry );
			return $custom_fields;

		}
    }
}

// $custom_fields = lcw_get_location_custom_fields();

// echo "<pre>";
// print_r($custom_fields);
// echo "</pre>";