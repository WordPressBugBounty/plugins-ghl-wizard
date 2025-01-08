<?php
/***********************************
    Create Auto Login
    @ v: 1.2
***********************************/
if( isset($_REQUEST['lcw_auto_login']) && $_REQUEST['lcw_auto_login'] == 1 ){

    add_action('init', 'lcw_process_auto_login');

}

function lcw_process_auto_login(){
    $auth_key = sanitize_text_field($_REQUEST['lcw_auth_key']);
    $saved_auth_key = get_option('lcw_auth_key', '');
    $autologin_error_transient_key = 'lcw_autologin_error';

    if ($auth_key !== $saved_auth_key || empty($saved_auth_key)) {
        set_transient($autologin_error_transient_key, __('Invalid authentication.', 'ghl-wizard'));
        wp_redirect(home_url());
        exit;
    }

    $user_email = sanitize_text_field($_REQUEST['email']);
    if (empty($user_email)) {
        set_transient($autologin_error_transient_key, __('There was no email address provided, please provide a valid email address.', 'ghl-wizard'));
        wp_redirect(home_url());
        exit;
    }

    $user = get_user_by('email', $user_email);
    if (!$user) {
        set_transient($autologin_error_transient_key, sprintf(__('We could not find any account associated with your email: %s', 'ghl-wizard'), $user_email));
        wp_redirect(home_url());
        exit;
    }

    $data = get_option('leadconnectorwizardpro_license_options');
    if (!isset($data['sc_activation_id'])) {
        set_transient($autologin_error_transient_key, __('This is a premium feature, please contact with your administrator', 'ghl-wizard'));
        wp_redirect(home_url());
        exit;
    }

    wp_clear_auth_cookie();
    wp_set_auth_cookie($user->ID);
    wp_set_current_user($user->ID);

    $redirect_to = sanitize_text_field($_REQUEST['redirect_to']);
    wp_redirect(!empty($redirect_to) ? home_url($redirect_to) : home_url());
    exit;
}

// Display Autologin error message
if( !empty( get_transient('lcw_autologin_error') ) ){
    add_action('wp_footer', 'lcw_auto_login_error_message');
}

function lcw_auto_login_error_message(){
    $message = "<div class='auth-error-message'>";
    $message .= "<p>" . get_transient('lcw_autologin_error') . "</p>";
    $message .= "</div>";

    echo $message;

    delete_transient('lcw_autologin_error');
}

/***********************************
    Create Tables Function
    @ v: 1.1
***********************************/

if ( ! function_exists( 'lcw_create_location_and_contact_table' ) ) {

    function lcw_create_location_and_contact_table() {

        global $table_prefix, $wpdb;

        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        // Include Upgrade Script
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );


        $table_lcw_location = $table_prefix . 'lcw_locations';
        $table_lcw_contact = $table_prefix . 'lcw_contacts';

        // Create lcw_locations Table if not exist
        if( $wpdb->get_var( "show tables like '$table_lcw_location'" ) != $table_lcw_location ) {

            // Query - Create Table
            $sql_location = "CREATE TABLE `$table_lcw_location` ";
            $sql_location .= "(";
            $sql_location .= " `id` int(10) NOT NULL auto_increment, ";
            $sql_location .= " `location_id` varchar(100) NOT NULL, ";
            $sql_location .= " `data_type` varchar(50) NOT NULL, ";
            $sql_location .= " `data_value` longtext DEFAULT NULL, ";
            $sql_location .= " `updated_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00', ";
            $sql_location .= " `need_to_sync` tinyint(1) NOT NULL DEFAULT 1, ";
            $sql_location .= " PRIMARY KEY (`id`), ";
            $sql_location .= " KEY location_id (`location_id`), ";
            $sql_location .= " KEY data_type (`data_type`)";
            $sql_location .= ")";
            $sql_location .= $collate;
        
            // Create Table
            dbDelta( $sql_location );
        }

        // Create lcw_contacts Table if not exist
        if( $wpdb->get_var( "show tables like '$table_lcw_contact'" ) != $table_lcw_contact ) {

            // Query - Create Table
            $sql_contact = "CREATE TABLE `$table_lcw_contact` ";
            $sql_contact .= "(";
            $sql_contact .= " `id` bigint(20) NOT NULL auto_increment, ";
            $sql_contact .= " `user_id` bigint(20) NULL, ";
            $sql_contact .= " `contact_id` varchar(100) NOT NULL, ";
            $sql_contact .= " `contact_email` varchar(100) NOT NULL, ";
            $sql_contact .= " `tags` longtext DEFAULT NULL, ";
            $sql_contact .= " `contact_fields` longtext DEFAULT NULL, ";
            $sql_contact .= " `custom_fields` longtext DEFAULT NULL, ";
            $sql_contact .= " `has_not_access_to` longtext DEFAULT NULL, ";
            $sql_contact .= " `notes` longtext DEFAULT NULL, ";
            $sql_contact .= " `updated_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00', ";
            $sql_contact .= " `need_to_sync` tinyint(1) NOT NULL DEFAULT 1, ";
            $sql_contact .= " `need_to_update_access` tinyint(1) NOT NULL DEFAULT 1, ";
            $sql_contact .= " `is_active` tinyint(1) NOT NULL DEFAULT 1, ";
            $sql_contact .= " PRIMARY KEY (`id`), ";
            $sql_contact .= " UNIQUE KEY user_id (`user_id`), ";
            $sql_contact .= " UNIQUE KEY contact_id (`contact_id`), ";
            $sql_contact .= " UNIQUE KEY contact_email (`contact_email`)";
            $sql_contact .= ")";
            $sql_contact .= $collate;
        
            // Create Table
            dbDelta( $sql_contact );
        }

        update_option( 'lcw_db_table_exists', 1 );
    }
}


// Sanitize Array
function hlwpw_recursive_sanitize_array( $array ) {
    foreach ( $array as $key => &$value ) {
        if ( is_array( $value ) ) {
            $value = hlwpw_recursive_sanitize_array( $value );
        }
        else {
            $value = sanitize_text_field( $value );
        }
    }

    return $array;
}

// Refresh data function
function refresh_data_for_location(){
	$key_tags       	= 'hlwpw_location_tags';
    $key_campaigns  	= 'hlwpw_location_campaigns';
    $key_workflow   	= 'hlwpw_location_wokflow';
    $key_custom_values  = 'hlwpw_location_custom_values';
    $key_custom_fields	= 'lcw_location_cutom_fields';



    delete_transient($key_tags);
    delete_transient($key_campaigns);
    delete_transient($key_workflow);
    delete_transient($key_custom_values);
    delete_transient($key_custom_fields);
}
// Refresh Data
if ( isset( $_GET['ghl_refresh'] ) && $_GET['ghl_refresh'] == 1 ) {
    refresh_data_for_location();
}


// Show a notice to Wordpress user
// add notices on different user status ad activity



// imported from sa
if ( ! function_exists( 'hlwpw_get_tag_options' ) ) {
    
    function hlwpw_get_tag_options( $post_id, $key = '' ) {

        $tags = hlwpw_get_location_tags();
        $options    = "";
        $hlwpw_tags = get_post_meta( $post_id, $key, true );

        $hlwpw_tags = ( !empty($hlwpw_tags) ) ? $hlwpw_tags :  [];

        foreach ($tags as $tag ) {
            $tag_id   = $tag->id;
            $tag_name = $tag->name;
            $selected = "";

            if ( in_array( $tag_name, $hlwpw_tags )) {
                $selected = "selected";
            }

            $options .= "<option value='{$tag_name}' {$selected}>";
            $options .= $tag_name;
            $options .= "</option>";
        }

        return $options;
    }
}



if ( ! function_exists( 'hlwpw_get_required_tag_options' ) ) {
    
    function hlwpw_get_required_tag_options($post_id) {

        $tags = hlwpw_get_location_tags();
        $options    = "";
        $hlwpw_required_tags = get_post_meta( $post_id, 'hlwpw_required_tags', true );

        $hlwpw_required_tags = ( !empty($hlwpw_required_tags) ) ? $hlwpw_required_tags :  [];

        foreach ($tags as $tag ) {
            $tag_id   = $tag->id;
            $tag_name = $tag->name;
            $selected = "";

            if ( in_array( $tag_name, $hlwpw_required_tags )) {
                $selected = "selected";
            }

            $options .= "<option value='{$tag_name}' {$selected}>";
            $options .= $tag_name;
            $options .= "</option>";
        }

        return $options;
    }
}


// Create location tags
// accept Tag name
// Return tag id.
function hlwpw_create_location_tag($tag_name){

    $hlwpw_locationId = get_option( 'hlwpw_locationId' );
    $hlwpw_access_token = get_option( 'hlwpw_access_token' );
    $endpoint = "https://services.leadconnectorhq.com/locations/{$hlwpw_locationId}/tags";
    $ghl_version = '2021-07-28';

    $request_args = array(
        'body'      => ["name" => $tag_name],
        'headers'   => array(
            'Authorization' => "Bearer {$hlwpw_access_token}",
            'Version'       => $ghl_version
        ),
    );

    $response = wp_remote_post( $endpoint, $request_args );
    $http_code = wp_remote_retrieve_response_code( $response );

    if ( 200 === $http_code || 201 === $http_code ) {

        $body = json_decode( wp_remote_retrieve_body( $response ) );
        $tag = $body->tag;
        $tag_id = $tag->id;
        
        return $tag_id;
    }
}

// End imported from sa


// Delete a membership
function lcw_delete_a_membership(){

    $location_id = get_option( 'hlwpw_locationId' );
    $membership_meta_key = $location_id . "_hlwpw_memberships";
    $memberships = get_option( $membership_meta_key, [] );

    $membership = $_GET['delete_membership'];

    if ( ! empty( $membership ) ){

        unset( $memberships[$membership]);
        update_option( $membership_meta_key, $memberships );

    }

    wp_redirect( admin_url( 'admin.php?page=lcw-membership-pro' ) );
    exit;

}

// Run delete a membership
if ( isset( $_GET['delete_membership'] ) ) {
    add_action('init', 'lcw_delete_a_membership');
}



// Check whether database table is created
add_action('init', function(){

    if ( ! is_admin() ) {
        return;
    }

    $lcw_db_table_exists = get_option('lcw_db_table_exists', '');

    if ( 1 != $lcw_db_table_exists ) {
        lcw_create_location_and_contact_table();
    }


});


// Enable Chat
function lcw_enable_hl_chat_widget(){

    $locationId = get_option( 'hlwpw_locationId' );

    $widget = "";
    $widget .= "<chat-widget location-id='{$locationId}' show-consent-checkbox='true'></chat-widget>";

    $widget .= '<script src="https://widgets.leadconnectorhq.com/loader.js" data-resources-url="https://widgets.leadconnectorhq.com/chat-widget/loader.js" > </script>';

    $lcw_enable_chat = get_option( 'lcw_enable_chat', 'disabled' );

    if ( 'disabled' != $lcw_enable_chat ) {
        echo $widget;
    }

}
add_action('wp_footer','lcw_enable_hl_chat_widget');

// Define contact fields
$contact_fields = array(
    'firstName',
    'lastName',
    'email',
    'country',
    'type',
    'dateAdded',
    'phone',
    'dateOfBirth',
    'additionalPhones',
    'website',
    'city', 
    'address1',
    'companyName',
    'state',
    'postalCode',
    'additionalEmails'
);