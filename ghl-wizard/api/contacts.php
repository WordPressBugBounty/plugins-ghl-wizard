<?php

// Get Contact Data
if ( ! function_exists( 'hlwpw_get_location_contact_data' ) ) {
    
    function hlwpw_get_location_contact_data($contact_data) {

    	// get contact data
		$hlwpw_access_token = lcw_get_access_token();
		$endpoint = "https://services.leadconnectorhq.com/contacts/upsert";
		$ghl_version = '2021-07-28';

		$request_args = array(
			'body' 		=> $contact_data,
			'headers' 	=> array(
				'Authorization' => "Bearer {$hlwpw_access_token}",
				'Version' 		=> $ghl_version
			),
		);

		$response = wp_remote_post( $endpoint, $request_args );
		$http_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $http_code || 201 === $http_code ) {

			$body = json_decode( wp_remote_retrieve_body( $response ) );
			$contact = $body->contact;

			return $contact;
		}

		return "";
    }
}



// Get Contact ID
if ( ! function_exists( 'hlwpw_get_location_contact_id' ) ) {
    
    function hlwpw_get_location_contact_id($contact_data) {

    	// Check if contact id is exists
    	$wp_user_email = $contact_data['email'];
    	$ghl_location_id = $contact_data['locationId'];
    	$ghl_id_key = 'ghl_id_' . $ghl_location_id;
    	$wp_user = get_user_by( 'email', $wp_user_email );

    	if ( $wp_user ) { // get_user_by() return false on failure
    		$wp_user_id = $wp_user->ID;    		
    		$ghl_contact_id = get_user_meta( $wp_user_id, $ghl_id_key, true );
			
			if ( !empty( $ghl_contact_id ) ) {
	    		return $ghl_contact_id;
			}
    	}

		$contact = hlwpw_get_location_contact_data($contact_data);

		if ( !empty($contact) ) {

			$ghl_contact_id = $contact->id;

			if ( $wp_user ) {
	    		$wp_user_id = $wp_user->ID;
	    		add_user_meta( $wp_user_id, $ghl_id_key, $ghl_contact_id, true );
	    	}
			
			return $ghl_contact_id;
		}
    }
}

// Add Contact Tags
// Inputs 
// $contactId: GHL contact ID string
// $tags: Array of tags
// $user_id: WP user ID, optional

if ( ! function_exists( 'hlwpw_loation_add_contact_tags' ) ) {
    
    function hlwpw_loation_add_contact_tags($contactId, $tags, $user_id = 0) {

		//$hlwpw_access_token = get_option('hlwpw_access_token');
		$hlwpw_access_token = lcw_get_access_token();
		$endpoint = "https://services.leadconnectorhq.com/contacts/{$contactId}/tags";
		$ghl_version = '2021-04-15';

		$request_args = array(
			'body' 		=> $tags,
			'headers' 	=> array(
				'Authorization' => "Bearer {$hlwpw_access_token}",
				'Version' 		=> $ghl_version
			),
		);

		$response = wp_remote_post( $endpoint, $request_args );
		$http_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $http_code || 201 === $http_code ) {
			lcw_add_contact_tags_to_wp_user($user_id, $contactId, $tags['tags']);
			return wp_remote_retrieve_body( $response );
		}
    }
}

// Add Contact Tags to WP user in lcw_contacts table
function lcw_add_contact_tags_to_wp_user($user_id, $contactId, $tags) {

	if ( empty( $user_id ) || empty( $contactId ) || empty( $tags ) ) {
		return false;
	}

	// if tags is comma separated string, convert it to array
	if ( is_string( $tags ) ) {
		$tags = explode(',', $tags );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'lcw_contacts';

	// get existing tags
	$sql = $wpdb->prepare( "SELECT tags FROM {$table_name} WHERE user_id = %d", $user_id );
	$existing_tags = $wpdb->get_var( $sql );

	if ( !empty( $existing_tags ) ) {
		$existing_tags = unserialize( $existing_tags );
		if ( !empty( $existing_tags ) ) {
			$tags = array_unique( array_merge( $existing_tags, $tags ) );
		}
	}

	// update tags when user_id & contactId are matched
	$sql = $wpdb->prepare( "UPDATE {$table_name} SET tags = %s WHERE user_id = %d AND contact_id = %s", serialize( $tags ), $user_id, $contactId );
	return $wpdb->query( $sql );
}


// Remove Contact Tags
if ( ! function_exists( 'hlwpw_loation_remove_contact_tags' ) ) {
    
    function hlwpw_loation_remove_contact_tags($contactId, $tags, $user_id = 0) {

		$hlwpw_access_token = lcw_get_access_token();
		$endpoint = "https://services.leadconnectorhq.com/contacts/{$contactId}/tags";
		$ghl_version = '2021-07-28';

		$request_args = array(
			'method'    => 'DELETE',
			'body' 		=> $tags,
			'headers' 	=> array(
				'Authorization' => "Bearer {$hlwpw_access_token}",
				'Version' 		=> $ghl_version
			),
		);

		$response 	= wp_remote_request( $endpoint, $request_args );
		$http_code 	= wp_remote_retrieve_response_code( $response );

		if ( 200 === $http_code || 201 === $http_code ) {
			lcw_remove_contact_tags_from_wp_user($user_id, $contactId, $tags['tags']);
			return wp_remote_retrieve_body( $response );			
		}
    }
}

// Remove Contact Tags from WP user in lcw_contacts table    
function lcw_remove_contact_tags_from_wp_user($user_id, $contactId, $tags) {
	
	if ( empty( $user_id ) || empty( $contactId ) || empty( $tags ) ) {
		return false;
	}
	
	// if tags is comma separated string, convert it to array
	if ( is_string( $tags ) ) {
		$tags = explode(',', $tags );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'lcw_contacts';

	// get existing tags
	$sql = $wpdb->prepare( "SELECT tags FROM {$table_name} WHERE user_id = %d", $user_id );
	$existing_tags = $wpdb->get_var( $sql );

	if ( !empty( $existing_tags ) ) {
		$existing_tags = unserialize( $existing_tags );
		if ( !empty( $existing_tags ) ) {
			$tags = array_values( array_diff( $existing_tags, $tags ) );
		}
	}

	// update tags when user_id & contactId are matched
	$sql = $wpdb->prepare( "UPDATE {$table_name} SET tags = %s WHERE user_id = %d AND contact_id = %s", serialize( $tags ), $user_id, $contactId );
	return $wpdb->query( $sql );
}

// Add Contact to Campaign
if ( ! function_exists( 'hlwpw_loation_add_contact_to_campaign' ) ) {
    
    function hlwpw_loation_add_contact_to_campaign( $contactId, $campaign_id ) {

		$hlwpw_access_token = lcw_get_access_token();
		$endpoint = "https://services.leadconnectorhq.com/contacts/{$contactId}/campaigns/{$campaign_id}";
		$ghl_version = '2021-04-15';

		$request_args = array(
			'body' 		=> '',
			'headers' 	=> array(
				'Authorization' => "Bearer {$hlwpw_access_token}",
				'Version' 		=> $ghl_version
			),
		);

		$response = wp_remote_post( $endpoint, $request_args );
		$http_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $http_code || 201 === $http_code ) {

			return wp_remote_retrieve_body( $response );			
		}
    }
}

// Add Contact to Workflow
if ( ! function_exists( 'hlwpw_loation_add_contact_to_workflow' ) ) {
    
    function hlwpw_loation_add_contact_to_workflow( $contactId, $workflow_id ) {

		$hlwpw_access_token = lcw_get_access_token();
		$endpoint = "https://services.leadconnectorhq.com/contacts/{$contactId}/workflow/{$workflow_id}";
		$ghl_version = '2021-04-15';

		$request_args = array(
			'body' 		=> '',
			'headers' 	=> array(
				'Authorization' => "Bearer {$hlwpw_access_token}",
				'Version' 		=> $ghl_version
			),
		);

		$response = wp_remote_post( $endpoint, $request_args );
		$http_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $http_code || 201 === $http_code ) {

			return wp_remote_retrieve_body( $response );			
		}
    }
}

// Sync User on Login
// it's moved to wp_user.php

// Sync User on Register and update
// it's moved to wp_user.php


// Display data on user profile
function hlwpw_show_tags_on_profile( $user ) {

	$tags = unserialize( lcw_get_contact_tags_by_wp_id ( $user->ID ) );
	$title = __("Lead Connector Tags");

	echo "<h2> {$title} </h2>";

	if ( ! empty( $tags ) ) {

		foreach ($tags as $tag) {		
			echo "<span class='tag'>{$tag}</span>";
		}
	}else{
		echo "<p>No tags added yet.</p>";
	}

}
add_action( 'show_user_profile', 'hlwpw_show_tags_on_profile' );
add_action( 'edit_user_profile', 'hlwpw_show_tags_on_profile' );