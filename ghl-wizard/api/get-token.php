<?php

add_action( 'init', 'hlwpw_handle_auth_code_exchange' );
add_action( 'init', 'hlwpw_maybe_refresh_access_token' );

/**
 * Handle first OAuth callback code exchange.
 *
 * @return void
 */
function hlwpw_handle_auth_code_exchange() {
	$referrer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	if ( false === strpos( $referrer, 'gohighlevel' ) ) {
		return;
	}

	if ( ! isset( $_GET['code'] ) ) {
		return;
	}

	$code          = sanitize_text_field( wp_unslash( $_GET['code'] ) );
	$client_id     = get_option( 'hlwpw_client_id' );
	$client_secret = get_option( 'hlwpw_client_secret' );
	$result        = hlwpw_get_first_auth_code( $code, $client_id, $client_secret );

	if ( ! is_object( $result ) || empty( $result->access_token ) || empty( $result->refresh_token ) || empty( $result->locationId ) ) {
		return;
	}

	update_option( 'hlwpw_access_token', $result->access_token );
	update_option( 'hlwpw_refresh_token', $result->refresh_token );
	update_option( 'hlwpw_locationId', $result->locationId );
	update_option( 'hlwpw_location_connected', 1 );

	delete_transient( 'hlwpw_location_tags' );
	delete_transient( 'hlwpw_location_campaigns' );
	delete_transient( 'hlwpw_location_wokflow' );
	delete_transient( 'hlwpw_location_custom_values' );
	delete_transient( 'lcw_location_cutom_fields' );
	delete_transient( 'lcw_associations_' . $result->locationId );

	if ( function_exists( 'lcw_mark_location_data_dirty' ) ) {
		lcw_mark_location_data_dirty( array( 'tags', 'campaigns', 'workflows', 'custom_values', 'custom_fields', 'associations' ) );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=connector-wizard-app' ) );
	exit;
}

/**
 * Refresh access token when cached validity is expired.
 *
 * @return void
 */
function hlwpw_maybe_refresh_access_token() {
	$location_id = lcw_get_location_id();
	$valid_token = get_transient( 'is_access_token_valid' );

	if ( ! empty( $location_id ) && empty( $valid_token ) ) {
		hlwpw_get_new_access_token();
	}
}

/**
 * Refresh access token.
 *
 * @return null
 */
function hlwpw_get_new_access_token() {
	$client_id     = get_option( 'hlwpw_client_id' );
	$client_secret = get_option( 'hlwpw_client_secret' );
	$refresh_token = get_option( 'hlwpw_refresh_token' );

	$response = hlwpw_exchange_auth_token(
		array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_type'    => 'refresh_token',
			'refresh_token' => $refresh_token,
		)
	);

	if ( ! is_object( $response ) || empty( $response->access_token ) || empty( $response->refresh_token ) ) {
		update_option( 'hlwpw_location_connected', 0 );
		return null;
	}

	update_option( 'hlwpw_access_token', $response->access_token );
	update_option( 'hlwpw_refresh_token', $response->refresh_token );
	update_option( 'hlwpw_location_connected', 1 );
	set_transient( 'is_access_token_valid', true, 59 * 60 * 24 );

	return null;
}

/**
 * Exchange first auth code for token.
 *
 * @param string $code          OAuth code.
 * @param string $client_id     Client ID.
 * @param string $client_secret Client secret.
 * @return object|null
 */
function hlwpw_get_first_auth_code( $code, $client_id, $client_secret ) {
	$response = hlwpw_exchange_auth_token(
		array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_type'    => 'authorization_code',
			'code'          => $code,
		)
	);

	if ( is_object( $response ) ) {
		set_transient( 'is_access_token_valid', true, 59 * 60 * 24 );
	}

	return $response;
}

/**
 * Execute OAuth token exchange request.
 *
 * @param array $body Request body.
 * @return object|null
 */
function hlwpw_exchange_auth_token( $body ) {
	$base_url = class_exists( 'LCW_GHL_API_Client' ) ? LCW_GHL_API_Client::BASE_URL : 'https://services.leadconnectorhq.com';

	$response = wp_remote_post(
		$base_url . '/oauth/token',
		array(
			'body'    => $body,
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return null;
	}

	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}

	$parsed_body = json_decode( wp_remote_retrieve_body( $response ) );

	return is_object( $parsed_body ) ? $parsed_body : null;
}
