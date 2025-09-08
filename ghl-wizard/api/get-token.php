<?php

add_action('init', function() {

    // check collision with other $_GET['code']
    $referrer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
    
    if ( ! str_contains( $referrer, 'gohighlevel') ) {
        return '';
    }

    if ( isset( $_GET['code'] ) ) {
        
        $code = $_GET['code'];
        
        $hlwpw_client_id           = get_option( 'hlwpw_client_id' );
        $hlwpw_client_secret       = get_option( 'hlwpw_client_secret' );
        
        $result = hlwpw_get_first_auth_code($code, $hlwpw_client_id, $hlwpw_client_secret);
        
        $hlwpw_access_token = $result->access_token;
        $hlwpw_refresh_token = $result->refresh_token;
        $hlwpw_locationId = $result->locationId;
        
        // Save data
        update_option( 'hlwpw_access_token', $hlwpw_access_token );
        update_option( 'hlwpw_refresh_token', $hlwpw_refresh_token );
        update_option( 'hlwpw_locationId', $hlwpw_locationId );
        update_option( 'hlwpw_location_connected', 1 );

        // delete old transient (if exists any)
        delete_transient('hlwpw_location_tags');
        delete_transient('hlwpw_location_campaigns');
        delete_transient('hlwpw_location_wokflow');

        wp_redirect( admin_url( 'admin.php?page=bw-hlwpw' ) );
        exit();
    }
});

add_action('init', function() {

    $hlwpw_locationId = lcw_get_location_id();
    $is_access_token_valid = get_transient('is_access_token_valid');

    if ( ! empty( $hlwpw_locationId ) && ! $is_access_token_valid ) {
        
        // renew the access token
        hlwpw_get_new_access_token();
    }

});

function hlwpw_get_new_access_token()
{
	$key = 'is_access_token_valid';
    $expiry = 59  * 60 * 24; // almost 1 day

	$hlwpw_client_id 		= get_option( 'hlwpw_client_id' );
	$hlwpw_client_secret 	= get_option( 'hlwpw_client_secret' );
	$refreshToken 			= get_option( 'hlwpw_refresh_token' );
	
	$endpoint = "https://services.leadconnectorhq.com/oauth/token";
	$body = array(
		'client_id' 	=> $hlwpw_client_id,
		'client_secret' => $hlwpw_client_secret,
		'grant_type' 	=> 'refresh_token',
		'refresh_token' => $refreshToken
	);

	$request_args = array(
		'body' 		=> $body,
		'headers' 	=> array(
            'Accept'        => 'application/json',
			'Content-Type'  => 'application/x-www-form-urlencoded'
		),
	);

	$response = wp_remote_post( $endpoint, $request_args );
	$http_code = wp_remote_retrieve_response_code( $response );

	if ( 200 === $http_code ) {

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		$new_hlwpw_access_token = $body->access_token;
		$new_hlwpw_refresh_token = $body->refresh_token;

		update_option( 'hlwpw_access_token', $new_hlwpw_access_token );
		update_option( 'hlwpw_refresh_token', $new_hlwpw_refresh_token );

        // Set location is connected value
        update_option( 'hlwpw_location_connected', 1 );

		// Set 'is_access_token_valid' value true
		// That Means, token is still valid
		set_transient( $key, true, $expiry );
	}else{

        // Set location is NOT connected value
        update_option( 'hlwpw_location_connected', 0 );

    }

	return null;
}

function hlwpw_get_first_auth_code($code, $client_id, $client_secret){

    $key = 'is_access_token_valid';
    $expiry = 59  * 60 * 24; // almost 1 day

    $endpoint = "https://services.leadconnectorhq.com/oauth/token";
    $body = array(
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'grant_type'    => 'authorization_code',
        'code'          => $code
    );

    $request_args = array(
        'body'      => $body,
        'headers'   => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
    );

    $response = wp_remote_post( $endpoint, $request_args );
    $http_code = wp_remote_retrieve_response_code( $response );

    if ( 200 === $http_code ) {

        // Set 'is_access_token_valid' value true
        // That Means, token is still valid
        set_transient( $key, true, $expiry );

        $body = json_decode( wp_remote_retrieve_body( $response ) );
        return $body;
    }    
}