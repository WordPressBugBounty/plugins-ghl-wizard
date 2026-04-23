<?php

if ( ! function_exists( 'hlwpw_get_location_contact_data' ) ) {
	/**
	 * Upsert contact in LeadConnector.
	 *
	 * @param array $contact_data Contact payload.
	 * @return object|string
	 */
	function hlwpw_get_location_contact_data( $contact_data ) {
		$result = LCW_GHL_API_Client::request(
			'POST',
			'/contacts/upsert',
			array(
				'version' => '2021-07-28',
				'body'    => $contact_data,
			)
		);

		if ( is_wp_error( $result ) || ! LCW_GHL_API_Client::is_success( $result['code'] ) ) {
			return '';
		}

		$body = $result['body'];

		if ( is_object( $body ) && isset( $body->contact ) ) {
			return $body->contact;
		}

		return '';
	}
}

if ( ! function_exists( 'hlwpw_get_location_contact_id' ) ) {
	/**
	 * Get LeadConnector contact ID for user.
	 *
	 * @param array $contact_data Contact payload.
	 * @return string
	 */
	function hlwpw_get_location_contact_id( $contact_data ) {
		$wp_user_email   = isset( $contact_data['email'] ) ? $contact_data['email'] : '';
		$ghl_location_id = isset( $contact_data['locationId'] ) ? $contact_data['locationId'] : '';
		$ghl_id_key      = 'ghl_id_' . $ghl_location_id;
		$wp_user         = get_user_by( 'email', $wp_user_email );

		if ( $wp_user ) {
			$ghl_contact_id = get_user_meta( $wp_user->ID, $ghl_id_key, true );
			if ( ! empty( $ghl_contact_id ) ) {
				return $ghl_contact_id;
			}
		}

		$contact = hlwpw_get_location_contact_data( $contact_data );
		if ( empty( $contact ) || empty( $contact->id ) ) {
			return '';
		}

		$ghl_contact_id = $contact->id;

		if ( $wp_user ) {
			add_user_meta( $wp_user->ID, $ghl_id_key, $ghl_contact_id, true );
		}

		return $ghl_contact_id;
	}
}

if ( ! function_exists( 'hlwpw_loation_add_contact_tags' ) ) {
	/**
	 * Add tags to a contact.
	 *
	 * @param string $contact_id GHL contact ID.
	 * @param array  $tags       Tag payload.
	 * @param int    $user_id    WordPress user ID.
	 * @return string
	 */
	function hlwpw_loation_add_contact_tags( $contact_id, $tags, $user_id = 0 ) {
		$path = sprintf( '/contacts/%s/tags', rawurlencode( $contact_id ) );

		$result = LCW_GHL_API_Client::request(
			'POST',
			$path,
			array(
				'version' => '2021-04-15',
				'body'    => $tags,
			)
		);

		if ( is_wp_error( $result ) || ! LCW_GHL_API_Client::is_success( $result['code'] ) ) {
			return '';
		}

		$tags_to_add = isset( $tags['tags'] ) ? $tags['tags'] : array();
		lcw_add_contact_tags_to_wp_user( $user_id, $contact_id, $tags_to_add );

		return $result['raw_body'];
	}
}

/**
 * Add contact tags into lcw_contacts table.
 *
 * @param int          $user_id    WP user ID.
 * @param string       $contact_id GHL contact ID.
 * @param string|array $tags       Tags.
 * @return int|false
 */
function lcw_add_contact_tags_to_wp_user( $user_id, $contact_id, $tags ) {
	if ( empty( $user_id ) || empty( $contact_id ) || empty( $tags ) ) {
		return false;
	}

	$tags = lcw_prepare_tags_for_storage( $tags );
	if ( empty( $tags ) ) {
		return false;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'lcw_contacts';

	$existing_tags_raw = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT tags FROM {$table_name} WHERE user_id = %d",
			$user_id
		)
	);

	$existing_tags = maybe_unserialize( $existing_tags_raw );
	if ( is_array( $existing_tags ) && ! empty( $existing_tags ) ) {
		$tags = array_values( array_unique( array_merge( $existing_tags, $tags ) ) );
	}

	return $wpdb->update(
		$table_name,
		array( 'tags' => maybe_serialize( $tags ) ),
		array(
			'user_id'    => $user_id,
			'contact_id' => $contact_id,
		),
		array( '%s' ),
		array( '%d', '%s' )
	);
}

if ( ! function_exists( 'hlwpw_loation_remove_contact_tags' ) ) {
	/**
	 * Remove tags from a contact.
	 *
	 * @param string $contact_id GHL contact ID.
	 * @param array  $tags       Tag payload.
	 * @param int    $user_id    WordPress user ID.
	 * @return string
	 */
	function hlwpw_loation_remove_contact_tags( $contact_id, $tags, $user_id = 0 ) {
		$path = sprintf( '/contacts/%s/tags', rawurlencode( $contact_id ) );

		$result = LCW_GHL_API_Client::request(
			'DELETE',
			$path,
			array(
				'version' => '2021-07-28',
				'body'    => $tags,
			)
		);

		if ( is_wp_error( $result ) || ! LCW_GHL_API_Client::is_success( $result['code'] ) ) {
			return '';
		}

		$tags_to_remove = isset( $tags['tags'] ) ? $tags['tags'] : array();
		lcw_remove_contact_tags_from_wp_user( $user_id, $contact_id, $tags_to_remove );

		return $result['raw_body'];
	}
}

/**
 * Remove contact tags from lcw_contacts table.
 *
 * @param int          $user_id    WP user ID.
 * @param string       $contact_id GHL contact ID.
 * @param string|array $tags       Tags.
 * @return int|false
 */
function lcw_remove_contact_tags_from_wp_user( $user_id, $contact_id, $tags ) {
	if ( empty( $user_id ) || empty( $contact_id ) || empty( $tags ) ) {
		return false;
	}

	$tags = lcw_prepare_tags_for_storage( $tags );
	if ( empty( $tags ) ) {
		return false;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'lcw_contacts';

	$existing_tags_raw = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT tags FROM {$table_name} WHERE user_id = %d",
			$user_id
		)
	);

	$existing_tags = maybe_unserialize( $existing_tags_raw );
	if ( ! is_array( $existing_tags ) || empty( $existing_tags ) ) {
		return false;
	}

	$remaining_tags = array_values( array_diff( $existing_tags, $tags ) );

	return $wpdb->update(
		$table_name,
		array( 'tags' => maybe_serialize( $remaining_tags ) ),
		array(
			'user_id'    => $user_id,
			'contact_id' => $contact_id,
		),
		array( '%s' ),
		array( '%d', '%s' )
	);
}

if ( ! function_exists( 'hlwpw_loation_add_contact_to_campaign' ) ) {
	/**
	 * Add a contact to campaign.
	 *
	 * @param string $contact_id  GHL contact ID.
	 * @param string $campaign_id GHL campaign ID.
	 * @return string
	 */
	function hlwpw_loation_add_contact_to_campaign( $contact_id, $campaign_id ) {
		$path = sprintf( '/contacts/%s/campaigns/%s', rawurlencode( $contact_id ), rawurlencode( $campaign_id ) );

		$result = LCW_GHL_API_Client::request(
			'POST',
			$path,
			array(
				'version' => '2021-04-15',
				'body'    => '',
			)
		);

		if ( is_wp_error( $result ) || ! LCW_GHL_API_Client::is_success( $result['code'] ) ) {
			return '';
		}

		return $result['raw_body'];
	}
}

if ( ! function_exists( 'hlwpw_loation_add_contact_to_workflow' ) ) {
	/**
	 * Add a contact to workflow.
	 *
	 * @param string $contact_id  GHL contact ID.
	 * @param string $workflow_id GHL workflow ID.
	 * @return string
	 */
	function hlwpw_loation_add_contact_to_workflow( $contact_id, $workflow_id ) {
		$path = sprintf( '/contacts/%s/workflow/%s', rawurlencode( $contact_id ), rawurlencode( $workflow_id ) );

		$result = LCW_GHL_API_Client::request(
			'POST',
			$path,
			array(
				'version' => '2021-04-15',
				'body'    => '',
			)
		);

		if ( is_wp_error( $result ) || ! LCW_GHL_API_Client::is_success( $result['code'] ) ) {
			return '';
		}

		return $result['raw_body'];
	}
}

/**
 * Normalize tags for storage operations.
 *
 * @param string|array $tags Tags input.
 * @return array
 */
function lcw_prepare_tags_for_storage( $tags ) {
	if ( function_exists( 'lcw_string_to_array' ) ) {
		$tags = lcw_string_to_array( $tags );
	} elseif ( is_string( $tags ) ) {
		$tags = array_map( 'trim', explode( ',', $tags ) );
	}

	if ( ! is_array( $tags ) ) {
		return array();
	}

	return array_values( array_filter( $tags ) );
}

/**
 * Display tags on user profile.
 *
 * @param WP_User $user User object.
 * @return void
 */
function hlwpw_show_tags_on_profile( $user ) {
	$tags_raw = lcw_get_contact_tags_by_wp_id( $user->ID );
	$tags     = maybe_unserialize( $tags_raw );
	$title    = esc_html__( 'Lead Connector Tags' );

	echo '<h2>' . esc_html( $title ) . '</h2>';

	if ( is_array( $tags ) && ! empty( $tags ) ) {
		foreach ( $tags as $tag ) {
			echo '<span class="tag">' . esc_html( $tag ) . '</span>';
		}
		return;
	}

	echo '<p>' . esc_html__( 'No tags added yet.' ) . '</p>';
}

add_action( 'show_user_profile', 'hlwpw_show_tags_on_profile' );
add_action( 'edit_user_profile', 'hlwpw_show_tags_on_profile' );
