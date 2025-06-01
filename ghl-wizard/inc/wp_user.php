<?php

// Turn on contact sync
function lcw_turn_on_contact_sync( $user_id ) {
	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $user_id ) ) {
		return array( 'error' => 'no user ID provided' );
	}

	// Turn on contact sync
	$result = $wpdb->update(
		$table_lcw_contact,
		array(
			'need_to_sync' => 1,
		),
		array( 'user_id' => $user_id )
	);

	return $result;
}

// Turn on contact sync
// TODO: if the user is new, how it will work?
function lcw_turn_on_contact_sync_by_contact_id( $contact_id ) {
	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $contact_id ) ) {
		return array( 'error' => 'no contact ID provided' );
	}

	// Turn on contact sync
	$result = $wpdb->update(
		$table_lcw_contact,
		array(
			'need_to_sync' => 1,
		),
		array( 'contact_id' => $contact_id )
	);

	return $result;
}

/**
 * Add WP User to lcw_contact table on Login
 *
 * @param string $user_login The user's login name.
 * @param WP_User $user WP_User object of the logged-in user.
 */
function lcw_sync_contact_on_user_logged_in( $user_login, $user ) {
	$user_id    = $user->ID;
	$contact_id = lcw_get_contact_id_by_wp_user_id( $user_id );

	// If $contact_id is null, the contact data isn't in contact table.
	// Need to retrieve Contact data
	if ( ! empty( $contact_id ) ) {
		lcw_turn_on_contact_sync_by_contact_id( $contact_id );
		return null;
	}

	$location_id = get_option( 'hlwpw_locationId' );
	$first_name  = get_user_meta( $user_id, 'first_name', true );
	$last_name   = get_user_meta( $user_id, 'last_name', true );

	$contact_data = array(
		'locationId' => $location_id,
		'firstName'  => $first_name,
		'lastName'   => $last_name,
		'email'      => $user->user_email,
	);

	// Get Contact Data
	$contact = hlwpw_get_location_contact_data( $contact_data );

	// if failed to retrieve contact data
	if ( ! isset( $contact->id ) ) {
		return;
	}

	// Add $contact_id to lcw_contact table
	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	$contact_id    = $contact->id;
	$contact_email = $contact->email;

	// Insert data to lcw_contact table
	$add_row = $wpdb->insert(
		$table_lcw_contact,
		array(
			'user_id'       => $user_id,
			'contact_id'    => $contact_id,
			'contact_email' => $contact_email,
		)
	);

	if ( 1 !== $add_row ) {
		// Error occurred
		// delete the table row and it will be inserted again
		$wpdb->delete( $table_lcw_contact, array( 'contact_email' => $contact_email ) );
	}

	// update post access on user login
	lcw_turn_on_post_access_update($user_id);
}
add_action( 'wp_login', 'lcw_sync_contact_on_user_logged_in', 10, 2 );

/**
 * Sync User on Register and update
 *
 * @param int $user_id User ID.
 */
function hlwpw_user_on_register_and_update( $user_id ) {
	$location_id = get_option( 'hlwpw_locationId' );
	$user        = get_user_by( 'id', $user_id );

	// the syncing process is same as login.
	lcw_sync_contact_on_user_logged_in( '', $user );
}
add_action( 'user_register', 'hlwpw_user_on_register_and_update', 10, 1 );
add_action( 'profile_update', 'hlwpw_user_on_register_and_update', 10, 1 );

/**
 * Get Contact ID by WP user ID
 *
 * @param int $user_id User ID.
 * @return string|null Contact ID or null on failure.
 */
function lcw_get_contact_id_by_wp_user_id( $user_id ) {
	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $user_id ) ) {
		return 0;
	}

	$sql        = $wpdb->prepare( "SELECT contact_id FROM {$table_lcw_contact} WHERE user_id = %d", $user_id );
	$contact_id = $wpdb->get_var( $sql ); // return string or null on failure.

	return $contact_id;
}

/**
 * Sync contact data if needed
 */
function lcw_sync_contact_data_if_needed() {
	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;

	if ( 0 === $user_id ) {
		return;
	}

	// 1. check if the row already inserted
	// 2. check if sync needed

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	$sql  = $wpdb->prepare( "SELECT contact_id, contact_email, need_to_sync FROM {$table_lcw_contact} WHERE user_id = %d", $user_id );
	$data = $wpdb->get_row( $sql );

	// if the row is null, then there are no data in the table
	// so add data to the table
	if ( null === $data ) {
		lcw_sync_contact_on_user_logged_in( '', $current_user );
		return;
	}

	$user_email = strtolower( $current_user->user_email );
	if ( $data->contact_email ) {
		$contact_email = strtolower( $data->contact_email );
	} else {
		return;
	}

	// if contact email & user_email mismatched
	if ( $user_email !== $contact_email ) {
		// delete the table row and it will be inserted again
		$wpdb->delete( $table_lcw_contact, array( 'contact_email' => $user_email ) );
		$wpdb->delete( $table_lcw_contact, array( 'contact_email' => $contact_email ) );
	}

	if ( isset( $data->need_to_sync ) && 1 === (int) $data->need_to_sync ) {
		$contact_id = $data->contact_id;
		return lcw_sync_contact_data_to_wp( $contact_id );
	}

	// if contact id is blank
	if ( isset( $data->need_to_sync ) && empty( $data->contact_id ) ) {
		// contact id is blank
		// take necessary action
		return $wpdb->delete( $table_lcw_contact, array( 'user_id' => $user_id ) );
	}
}
// it's calling in every page load, it needs to be restricted
add_action( 'init', 'lcw_sync_contact_data_if_needed' );

// Turn on data sync if a contact is updated inside GHL
add_action(
	'init',
	function() {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$data         = file_get_contents( 'php://input' );
			$contact_data = json_decode( $data );

			$contact_id = isset( $contact_data->contact_id ) ? $contact_data->contact_id : null;

			if ( empty( $contact_id ) ) {
				return;
			}
			
			$contact_email          = $contact_data->email;
			$first_name             = $contact_data->first_name;
			$last_name              = $contact_data->last_name;
			$lcw_create_wp_user     = isset( $contact_data->customData->lcw_create_wp_user ) ? $contact_data->customData->lcw_create_wp_user : 0;
			$need_to_update         = isset( $contact_data->customData->lcw_contact_update ) ? $contact_data->customData->lcw_contact_update : 0;
			$lcw_add_wp_user_role   = isset( $contact_data->customData->lcw_add_wp_user_role ) ? $contact_data->customData->lcw_add_wp_user_role : false;
			$lcw_remove_wp_user_role= isset( $contact_data->customData->lcw_remove_wp_user_role ) ? $contact_data->customData->lcw_remove_wp_user_role : false;

			// unblock other webhooks other than LC Wizard
			if ( $lcw_create_wp_user == 1 || $need_to_update == 1 || !empty( $lcw_add_wp_user_role ) || !empty( $lcw_remove_wp_user_role ) ){
				// go further
			} else {
				return;
			}

			$data = get_option( 'leadconnectorwizardpro_license_options' );

			// check if user exist & get user id
			$wp_user = get_user_by( 'email', $contact_email );
			$wp_user_id = $wp_user->ID;
			
			$message = array();

			if ( ! $wp_user ) {
				$wp_user_id = wp_create_user( $contact_email, $contact_id, $contact_email );

				wp_update_user(
					array(
						'ID'         => $wp_user_id,
						'first_name' => $first_name,
						'last_name'  => $last_name,
					)
				);
				wp_new_user_notification( $wp_user_id, null, 'user' );
				
				// add ghl id to this wp user
				$ghl_location_id = $contact_data->location->id;
				$ghl_id_key      = 'ghl_id_' . $ghl_location_id;

				update_user_meta( $wp_user_id, $ghl_id_key, $contact_id );

				$wp_user = get_user( $wp_user_id );
				
				$message['lcw_wp_user_created'] = true;
			}

			$message['lcw_wp_user_id'] = $wp_user_id;

			// this is removed on v@ 1.2.10
			if ( 1 === (int) $need_to_create_wp_user ) {
				// create wp user
				// we removed it, because it's necessary to 
				// create user if not exists
			}

			// TODO: check if data exist in the table
			if ( 1 === (int) $need_to_update ) {
				// turn on sync
				lcw_turn_on_contact_sync_by_contact_id( $contact_id );				
				$message['lcw_wp_user_updated'] = true;
			}

			// Remove wp user roll
			if ( $lcw_remove_wp_user_role ) {
			    if ( ! isset( $data['sc_activation_id'] ) ) {
			        $message['lcw_remove_wp_user_role'] = "It's a premium feature, please buy a license";
			    }else{
    				$wp_user->remove_role( $lcw_remove_wp_user_role );
    				$message['lcw_remove_wp_user_role'] = $lcw_remove_wp_user_role;
			    }
			}

			// Add wp user roll
			if ( $lcw_add_wp_user_role ) {
			    if ( ! isset( $data['sc_activation_id'] ) ) {
			        $message['lcw_add_wp_user_role'] = "It's a premium feature, please buy a license";
			    }else{
    				$wp_user->add_role( $lcw_add_wp_user_role );
    				$message['lcw_add_wp_user_role'] = $lcw_add_wp_user_role;
			    }
			}
			
			$message = json_encode( $message, JSON_UNESCAPED_SLASHES );
			wp_die( $message,'', ['response' => 'Success','code' => 200]);

		}
	}
);

/**
 * Sync contact data
 *
 * @param string $contact_id Contact ID.
 * @return bool|int|WP_Error The number of rows updated, or false on error.
 */
function lcw_sync_contact_data_to_wp( $contact_id ) {
	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $contact_id ) ) {
		// if no contact id
		// add a flag to add contact_id
		return false;
	}

	// get contact data
	$hlwpw_access_token = get_option( 'hlwpw_access_token' );
	$endpoint           = "https://services.leadconnectorhq.com/contacts/{$contact_id}";
	$ghl_version        = '2021-07-28';

	$request_args = array(
		'headers' => array(
			'Authorization' => "Bearer {$hlwpw_access_token}",
			'Version'       => $ghl_version,
		),
	);

	$response  = wp_remote_get( $endpoint, $request_args );
	$http_code = wp_remote_retrieve_response_code( $response );

	if ( 200 === $http_code ) {
		$body    = json_decode( wp_remote_retrieve_body( $response ) );
		$contact = $body->contact;

		$contact_email = $contact->email;
		$tags          = $contact->tags;

		$first_name         = isset( $contact->firstName ) ? $contact->firstName : '';
		$last_name          = isset( $contact->lastName ) ? $contact->lastName : '';
		$email              = isset( $contact->email ) ? $contact->email : '';
		$country            = isset( $contact->country ) ? $contact->country : '';
		$type               = isset( $contact->type ) ? $contact->type : '';
		$date_added         = isset( $contact->dateAdded ) ? $contact->dateAdded : '';
		$phone              = isset( $contact->phone ) ? $contact->phone : '';
		$date_of_birth      = isset( $contact->dateOfBirth ) ? $contact->dateOfBirth : '';
		$additional_phones  = isset( $contact->additionalPhones ) ? $contact->additionalPhones : '';
		$website            = isset( $contact->website ) ? $contact->website : '';
		$city               = isset( $contact->city ) ? $contact->city : '';
		$address1           = isset( $contact->address1 ) ? $contact->address1 : '';
		$company_name       = isset( $contact->companyName ) ? $contact->companyName : '';
		$state              = isset( $contact->state ) ? $contact->state : '';
		$postal_code        = isset( $contact->postalCode ) ? $contact->postalCode : '';
		$additional_emails  = isset( $contact->additionalEmails ) ? $contact->additionalEmails : '';

		$contact_fields = array(
			'firstName'         => $first_name,
			'lastName'          => $last_name,
			'email'             => $email,
			'country'           => $country,
			'type'              => $type,
			'dateAdded'         => $date_added,
			'phone'             => $phone,
			'dateOfBirth'       => $date_of_birth,
			'additionalPhones'  => $additional_phones,
			'website'           => $website,
			'city'              => $city,
			'address1'          => $address1,
			'companyName'       => $company_name,
			'state'             => $state,
			'postalCode'        => $postal_code,
			'additionalEmails'  => $additional_emails,
		);
		$custom_fields_value = $contact->customFields;
		$custom_fields       = array();

		foreach ( $custom_fields_value as $value ) {
			$key                 = $value->id;
			$custom_fields[$key] = $value->value;
		}

		// update data into table
		// and turn on update post access
		$result = $wpdb->update(
			$table_lcw_contact,
			array(
				'contact_email'         => $contact_email,
				'tags'                  => serialize( $tags ),
				'contact_fields'        => serialize( $contact_fields ),
				'custom_fields'         => serialize( $custom_fields ),
				'updated_on'            => current_time( 'mysql' ),
				'need_to_sync'          => 0,
				'need_to_update_access' => 1,
			),
			array( 'contact_id' => $contact_id )
		);

		return $result;
	} else {
		return $wpdb->delete( $table_lcw_contact, array( 'user_id' => get_current_user_id() ) );
	}
}

/**
 * Get single contact data
 *
 * @param string $contact_id Contact ID.
 * @return object|array Contact data or error array.
 */
function lcw_get_contact_data( $contact_id ) {
	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $contact_id ) ) {
		return array( 'error' => 'empty contact_id' );
	}

	$sql = $wpdb->prepare( "SELECT tags, contact_fields, custom_fields FROM {$table_lcw_contact} WHERE contact_id = %s", $contact_id );
	return $wpdb->get_row( $sql );
}

/**
 * Get contact data by WordPress user ID
 *
 * @param int $user_id WordPress user ID.
 * @return object|array Contact data or error array.
 */
function lcw_get_contact_data_by_wp_id( $user_id ) {
	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $user_id ) ) {
		return array( 'error' => 'empty user_id' );
	}

	$sql = $wpdb->prepare( "SELECT tags, contact_fields, custom_fields FROM {$table_lcw_contact} WHERE user_id = %d", $user_id );
	return $wpdb->get_row( $sql );
}

/**
 * Get contact tags by WordPress user ID
 *
 * @param int $user_id WordPress user ID.
 * @return string|array Contact tags or error array.
 */
function lcw_get_contact_tags_by_wp_id( $user_id ) {
	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty( $user_id ) ) {
		return array( 'error' => 'empty user_id' );
	}

	$sql = $wpdb->prepare( "SELECT tags FROM {$table_lcw_contact} WHERE user_id = %d", $user_id );
	return $wpdb->get_var( $sql );
}

/**
 * Update GHL contact fields by WooCommerce data
 *
 * @param string $contact_id Contact ID.
 * @param array $contact_fields Contact fields to update.
 * @return int HTTP response code.
 */
function lcw_update_ghl_contact_fields_by_woocommerce_data( $contact_id, $contact_fields ) {
	$hlwpw_access_token = get_option( 'hlwpw_access_token' );
	$endpoint           = "https://services.leadconnectorhq.com/contacts/{$contact_id}";
	$ghl_version        = '2021-07-28';

	$request_args = array(
		'method'  => 'PUT',
		'body'    => $contact_fields,
		'headers' => array(
			'Authorization' => "Bearer {$hlwpw_access_token}",
			'Version'       => $ghl_version,
		),
	);

	$response = wp_remote_request( $endpoint, $request_args );
	return wp_remote_retrieve_response_code( $response );
}

/**
 * Update Contact Fields
 *
 * @param string $contact_id Contact ID.
 * @param array $fields Fields to update.
 * @return object Response body.
 */
function lcw_update_contact_fields( $contact_id, $fields ) {
	$hlwpw_access_token = get_option( 'hlwpw_access_token' );
	$endpoint           = "https://services.leadconnectorhq.com/contacts/{$contact_id}";
	$ghl_version        = '2021-07-28';

	// process contact fields
	global $contact_fields;
	$custom_fields = lcw_get_location_custom_fields();

	$processed_contact_fields = array();
	$processed_custom_fields  = array();

	foreach ( $fields as $key => $value ) {
		if ( in_array( $key, $contact_fields, true ) ) {
			// this is basic contact values
			$processed_contact_fields[ $key ] = $value;
		} elseif ( isset( $custom_fields[ $key ] ) ) {
			$processed_custom_fields[] = array(
				'id'          => $custom_fields[ $key ],
				'key'         => $key,
				'field_value' => $value,
			);
		}
	}

	$request_body = array_merge( $processed_contact_fields, array( 'customFields' => $processed_custom_fields ) );

	$request_args = array(
		'method'  => 'PUT',
		'body'    => $request_body,
		'headers' => array(
			'Authorization' => "Bearer {$hlwpw_access_token}",
			'Version'       => $ghl_version,
		),
	);

	$response      = wp_remote_request( $endpoint, $request_args );
	$response_body = json_decode( wp_remote_retrieve_body( $response ) );

	// turn on sync
	lcw_turn_on_contact_sync_by_contact_id( $contact_id );

	return $response_body;
}

/**
 * Create a new note for a contact
 *
 * @param string $contact_id Contact ID.
 * @param string $note Note content.
 * @return object Response body.
 */
function lcw_create_contact_note( $contact_id, $note ) {
	$hlwpw_access_token = get_option( 'hlwpw_access_token' );
	$endpoint           = "https://services.leadconnectorhq.com/contacts/{$contact_id}/notes";
	$ghl_version        = '2021-07-28';

	$request_args = array(
		'method'  => 'POST',
		'body'    => array( 'body' => $note ),
		'headers' => array(
			'Authorization' => "Bearer {$hlwpw_access_token}",
			'Version'       => $ghl_version,
		),
	);

	$response = wp_remote_request( $endpoint, $request_args );
	return json_decode( wp_remote_retrieve_body( $response ) );
}