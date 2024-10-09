<?php

// Turn on contact sync
function lcw_turn_on_contact_sync($user_id){

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty ($user_id ) ) {

		return ['error' => 'no user ID provided'];
	}

	// Turn on contact sync
	$result = $wpdb->update(
        $table_lcw_contact,
        array(
            'need_to_sync' => 1
        ),
        array( 'user_id' => $user_id )
    );

    return $result;

}


// Turn on contact sync
function lcw_turn_on_contact_sync_by_contact_id($contact_id){

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty ($contact_id ) ) {

		return ['error' => 'no contact ID provided'];
	}

	// Turn on contact sync
	$result = $wpdb->update(
        $table_lcw_contact,
        array(
            'need_to_sync' => 1
        ),
        array( 'contact_id' => $contact_id )
    );

    return $result;

}


/***************************************************
    Add WP User to lcw_contact table on Login
    @ updated in v: 1.1
***************************************************/
function lcw_sync_contact_on_user_logged_in($user_login, $user) {

	$user_id = $user->ID;
	$contact_id = lcw_get_contact_id_by_wp_user_id( $user_id );

	// If $contact_id is null, the contat data isn't in contact table.
	// Need to retrieve Contact data
	if ( ! empty( $contact_id) ) {

		lcw_turn_on_contact_sync_by_contact_id($contact_id);
		return null;
	}

	$locationId = get_option( 'hlwpw_locationId' );

	$firstName = ! empty ( get_user_meta( $user_id, 'first_name', true ) ) ? get_user_meta( $user_id, 'first_name', true ) : $user->display_name;
	$lastName = get_user_meta( $user_id, 'last_name', true );

	$contact_data = array(
		"locationId"    => $locationId,
        "firstName"     => $firstName,
        "lastName"      => $lastName,
        "email"         => $user->user_email
	);

	// Get Contact Data
	$contact = hlwpw_get_location_contact_data($contact_data);

	// if failed to retreive contact data
	if ( ! isset( $contact->id )  ) {
		return;
	}

	// Add $contact_id to lcw_contact table
	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	$contact_id = $contact->id;
	$contact_email = $contact->email;

	// Insert data to lcw_contact table
	$add_row = $wpdb->insert( $table_lcw_contact, array(
	    'user_id' => $user_id,
	    'contact_id' => $contact_id,
	    'contact_email' => $contact_email
	));

	if ( 1 != $add_row ) {
		// Error occurred
		// delete the table row and it will be inserted again
		$delete = $wpdb->delete( $table_lcw_contact, array( 'contact_email' => $contact_email ) );
	}
}
add_action('wp_login', 'lcw_sync_contact_on_user_logged_in', 10, 2);


/***************************************************
    Sync User on Register and update
    @ updated in v: 1.1
***************************************************/

function hlwpw_user_on_register_and_update($user_id) {

	$locationId = get_option( 'hlwpw_locationId' );
	$user = get_user_by('id', $user_id);

	// the syncing process is same as login.
	lcw_sync_contact_on_user_logged_in('', $user);

}
add_action('user_register', 'hlwpw_user_on_register_and_update', 10, 1);
add_action('profile_update', 'hlwpw_user_on_register_and_update', 10, 1);


// Get Contact ID by WP user ID
function lcw_get_contact_id_by_wp_user_id( $user_id ) {

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty ($user_id ) ) {

		return 0;
	}

	$sql = "SELECT contact_id FROM {$table_lcw_contact} WHERE user_id = '{$user_id}'";
	$contact_id = $wpdb->get_var( $sql ); // return string or null on failer.

	return $contact_id;
}

/***********************************
    Sync contact data if needed
    @ v: 1.1
***********************************/
function lcw_sync_contact_data_if_needed(){

	$current_user = wp_get_current_user();	
	$user_id = $current_user->ID;

	if ( 0 == $user_id ) {
		return;
	}

	// 1. check if the row already inserted
	// 2. check if sync needed

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	$sql = "SELECT contact_id,contact_email,need_to_sync FROM {$table_lcw_contact} WHERE user_id = '{$user_id}'";
	$data = $wpdb->get_row( $sql );

	//if the row is null, then there are no data in the table
	// so add data to the table
	if ( $data == null ) {
		lcw_sync_contact_on_user_logged_in( '', $current_user );
		return;
	}

	$user_email = strtolower( $current_user->user_email );
	if ( $data->contact_email ) {
		$contact_email = strtolower( $data->contact_email );
	}else{
		return;
	}
	

	// if contact email & user_email missmatched
	if ( $user_email != $contact_email ) {
		
		// delete the table row and it will be inserted again
		$wpdb->delete( $table_lcw_contact, array( 'contact_email' => $user_email ) );
		$wpdb->delete( $table_lcw_contact, array( 'contact_email' => $contact_email ) );

	}

	if ( isset( $data->need_to_sync ) && 1 == $data->need_to_sync ) {
		
		$contact_id = $data->contact_id;
		return lcw_sync_contact_data_to_wp ( $contact_id );
	}

	// if contact id is blank
	if ( isset( $data->need_to_sync ) && empty( $data->contact_id ) ) {
		// contact id is blank
		// take necessary action

		return $wpdb->delete( $table_lcw_contact, array( 'user_id' => $user_id ) );
	}

}
// it's calling in every page load, it needs to be restricted
add_action( 'init', 'lcw_sync_contact_data_if_needed');


// Turn on data sync if a contact is updated inside GHL
add_action('init', function(){

    if ($_SERVER['REQUEST_METHOD'] == 'POST'){
 
        $data = file_get_contents("php://input");

        $contact_data = json_decode( $data );

        $contact_id 	= $contact_data->contact_id;
        $contact_email 	= $contact_data->email;
        $first_name 	= $contact_data->first_name;
        $last_name 		= $contact_data->last_name;

        $need_to_update = isset( $contact_data->customData->lcw_contact_update ) ? $contact_data->customData->lcw_contact_update : 0;
        $need_to_create_wp_user = isset( $contact_data->customData->lcw_create_wp_user ) ? $contact_data->customData->lcw_create_wp_user : 0;

        if ( 1 == $need_to_create_wp_user ){
            // create wp user
            
            // check if user exist
            $wp_user = get_user_by( 'email', $contact_email );

            if ( ! $wp_user ) {
            	$wp_user_id = wp_create_user( $contact_email, $contact_id, $contact_email );

            	wp_update_user([
				    'ID' => $wp_user_id,
				    'first_name' => $first_name,
				    'last_name' => $last_name,
				]);

				// add ghl id to this wp user
				$ghl_location_id = $contact_data->location->id;
    			$ghl_id_key = 'ghl_id_' . $ghl_location_id;

    			add_user_meta( $wp_user_id, $ghl_id_key, $contact_id, true );

            }            
            
        }

        if ( 1 == $need_to_update ){
            // turn on sync
            lcw_turn_on_contact_sync_by_contact_id($contact_id);
            
        }
    }

});


/***********************************
    Sync contact data
    @ v: 1.1
***********************************/

function lcw_sync_contact_data_to_wp ( $contact_id ) {

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $contact_id ) ) {
		// if no contact id
		// add a flag to add contact_id
		return false;
	}

	// get contact data
	$hlwpw_access_token = get_option( 'hlwpw_access_token' );
	$endpoint = "https://services.leadconnectorhq.com/contacts/{$contact_id}";
	$ghl_version = '2021-07-28';

	$request_args = array(
		'headers' 	=> array(
			'Authorization' => "Bearer {$hlwpw_access_token}",
			'Version' 		=> $ghl_version
		),
	);

	$response = wp_remote_get( $endpoint, $request_args );
	$http_code = wp_remote_retrieve_response_code( $response );

	if ( 200 === $http_code ) {

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		$contact = $body->contact;

		$contact_email 	= $contact->email;
		$tags 			= $contact->tags;

		$firstName 			= isset($contact->firstName) ? $contact->firstName : '';
		$lastName 			= isset($contact->lastName) ? $contact->lastName : '';
		$email 				= isset($contact->email) ? $contact->email : '';
		$country 			= isset($contact->country) ? $contact->country : '';
		$type 				= isset($contact->type) ? $contact->type : '';
		$dateAdded 			= isset($contact->dateAdded) ? $contact->dateAdded : '';
		$phone 				= isset($contact->phone) ? $contact->phone : '';
		$dateOfBirth 		= isset($contact->dateOfBirth) ? $contact->dateOfBirth : '';
		$additionalPhones 	= isset($contact->additionalPhones) ? $contact->additionalPhones : '';
		$website 			= isset($contact->website) ? $contact->website : '';
		$city 				= isset($contact->city) ? $contact->city : '';
		$address1 			= isset($contact->address1) ? $contact->address1 : '';
		$companyName 		= isset($contact->companyName) ? $contact->companyName : '';
		$state 				= isset($contact->state) ? $contact->state : '';
		$postalCode 		= isset($contact->postalCode) ? $contact->postalCode : '';
		$additionalEmails 	= isset($contact->additionalEmails) ? $contact->additionalEmails : '';

		$contact_fields	= array(
			'firstName' 		=> $firstName,
			'lastName' 			=> $lastName,
			'email' 			=> $email,
			'country' 			=> $country,
			'type' 				=> $type,
			'dateAdded' 		=> $dateAdded,
			'phone' 			=> $phone,
			'dateOfBirth' 		=> $dateOfBirth,
			'additionalPhones'	=> $additionalPhones,
			'website'			=> $website,
			'city'				=> $city,
			'address1'			=> $address1,
			'companyName'		=> $companyName,
			'state'				=> $state,
			'postalCode'		=> $postalCode,
			'additionalEmails' 	=> $additionalEmails
		);
		$custom_fields_value = $contact->customFields;
		$custom_fields = array();

		foreach ($custom_fields_value as $value) {
			$key = $value->id;
			$custom_fields[$key] = $value->value;
		}

		// update data into table
		// and turn on update post access
		$result = $wpdb->update(
	        $table_lcw_contact,
	        array(
	            'contact_email' => $contact_email,
	            'tags' => serialize( $tags ),
	            'contact_fields' => serialize( $contact_fields ),
	            'custom_fields' => serialize( $custom_fields ),
	            'updated_on' => current_time( 'mysql' ),
	            'need_to_sync' => 0,
	            'need_to_update_access' => 1
	        ),
	        array( 'contact_id' => $contact_id )
	    );

		return $result;

	}else{
		return $wpdb->delete( $table_lcw_contact, array( 'user_id' => get_current_user_id() ) );
	}

	return $http_code;

}

// lcw_sync_contact_data_to_wp ( "2M3zC6YMDnoHQY946pZx" );
// 2M3zC6YMDnoHQY946pZx


/***********************************
    Get single contact data
    @ v: 1.1
***********************************/
function lcw_get_contact_data ( $contact_id ) {

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $contact_id ) ) {
		return ['error' => 'empty contact_id'];
	}

	$sql = "SELECT tags,contact_fields,custom_fields FROM {$table_lcw_contact} WHERE contact_id = '{$contact_id}'";
	return $wpdb->get_row( $sql );
}

// echo "<pre>";
// print_r (lcw_get_contact_data ( "2M3zC6YMDnoHQY946pZx" ) );
// echo "</pre>";


function lcw_get_contact_data_by_wp_id ( $user_id ) {

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $user_id ) ) {
		return ['error' => 'empty user_id'];
	}

	$sql = "SELECT tags,contact_fields,custom_fields FROM {$table_lcw_contact} WHERE user_id = '{$user_id}'";
	return $wpdb->get_row( $sql );
}

// echo "<pre>";
// print_r (lcw_get_contact_data_by_wp_id ( "2M3zC6YMDnoHQY946pZx" ) );
// echo "</pre>";


function lcw_get_contact_tags_by_wp_id ( $user_id ) {

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $user_id ) ) {
		return ['error' => 'empty user_id'];
	}

	$sql = "SELECT tags FROM {$table_lcw_contact} WHERE user_id = '{$user_id}'";
	return $wpdb->get_var( $sql );
}

// echo "<pre>";
// print_r (lcw_get_contact_tags_by_wp_id ( 1 ) );
// echo "</pre>";

// this work direct, so no data processing needed
// if you need to receive data from users use the next function
function lcw_update_ghl_contact_fields_by_woocommerce_data($contact_id, $contact_fields){

	$hlwpw_access_token = get_option( 'hlwpw_access_token' );
	$endpoint = "https://services.leadconnectorhq.com/contacts/{$contact_id}";
	$ghl_version = '2021-07-28';

	$request_args = array(
		'method' 	=> 'PUT',
		'body' 		=> $contact_fields,
		'headers' 	=> array(
			'Authorization' => "Bearer {$hlwpw_access_token}",
			'Version' 		=> $ghl_version
		),
	);

	$response = wp_remote_request( $endpoint, $request_args );
	return wp_remote_retrieve_response_code( $response );
}


// Update Contact Fields
// Receive an array of key value pair

function lcw_update_contact_fields( $contact_id, $fields ){

	$hlwpw_access_token = get_option( 'hlwpw_access_token' );
	$endpoint = "https://services.leadconnectorhq.com/contacts/{$contact_id}";
	$ghl_version = '2021-07-28';

	// process contact fields
	global $contact_fields;
	$custom_fields = lcw_get_location_custom_fields();

	$processed_contact_fields = [];
	$processed_custom_fields = [];
	
	foreach ($fields as $key => $value) {
		
		if ( in_array( $key, $contact_fields) ) {

			// this is basic contact values
			$processed_contact_fields[$key] = $value;

		}else if( isset( $custom_fields[$key] ) ){

			$processed_custom_fields[] = array(
				'id' 			=> $custom_fields[$key],
				'key' 			=> $key,
				'field_value' 	=> $value
			);

		}

	}

	$request_body = array_merge( $processed_contact_fields, ["customFields" => $processed_custom_fields] );

	$request_args = array(
		'method' 	=> 'PUT',
		'body' 		=> $request_body,
		'headers' 	=> array(
			'Authorization' => "Bearer {$hlwpw_access_token}",
			'Version' 		=> $ghl_version
		),
	);

	$response = wp_remote_request( $endpoint, $request_args );
	$response_body = json_decode( wp_remote_retrieve_body( $response ) );

	// turn on sync
    lcw_turn_on_contact_sync_by_contact_id($contact_id);

	return $response_body;

}

// $fields = array(
// 	'dateOfBirth' => '15-07-1990',
// 	'companyName' => 'BW',
// 	'product_specific_note' => "Nothing helps.."
// );

// lcw_update_contact_fields("4OUwY73Lz5raCxz015Nr", $fields);



// Create a new note for a contact
function lcw_create_contact_note( $contact_id, $note ){
	$hlwpw_access_token = get_option( 'hlwpw_access_token' );
	$endpoint = "https://services.leadconnectorhq.com/contacts/{$contact_id}/notes";
	$ghl_version = '2021-07-28';

	$request_args = array(
		'method' 	=> 'POST',
		'body' 		=> ['body'=> $note],
		'headers' 	=> array(
			'Authorization' => "Bearer {$hlwpw_access_token}",
			'Version' 		=> $ghl_version
		),
	);

	$response = wp_remote_request( $endpoint, $request_args );
	return json_decode( wp_remote_retrieve_body( $response ) );
}
//lcw_create_contact_note("4OUwY73Lz5raCxz015Nr", "New note....1");