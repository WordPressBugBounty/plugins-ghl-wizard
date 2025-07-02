<?php
/***********************************
    Get Auth connection
    @ v: 1.2.18
***********************************/
add_action('init', function() {
    if ( isset( $_GET['get_auth'] ) && $_GET['get_auth'] == 'success' && isset( $_GET['lid'] ) ) {
        $hlwpw_access_token 	= sanitize_text_field( $_GET['atn'] );
        $hlwpw_refresh_token 	= sanitize_text_field( $_GET['rtn'] );
        $hlwpw_locationId 		= sanitize_text_field( $_GET['lid'] );
        $hlwpw_client_id 		= sanitize_text_field( $_GET['cid'] );
        $hlwpw_client_secret 	= sanitize_text_field( $_GET['cst'] );

        // Save data
        update_option( 'hlwpw_access_token', $hlwpw_access_token );
        update_option( 'hlwpw_refresh_token', $hlwpw_refresh_token );
        update_option( 'hlwpw_locationId', $hlwpw_locationId );
        update_option( 'hlwpw_client_id', $hlwpw_client_id );
        update_option( 'hlwpw_client_secret', $hlwpw_client_secret );
        update_option( 'hlwpw_location_connected', 1 );

        // delete old transient (if exists any)
        delete_transient('hlwpw_location_tags');
        delete_transient('hlwpw_location_campaigns');
        delete_transient('hlwpw_location_wokflow');
        delete_transient('hlwpw_location_custom_values');
        delete_transient('lcw_location_cutom_fields');

        wp_redirect(admin_url('admin.php?page=bw-hlwpw'));
        exit();

        // Need to update on Database
        // on next version
    }
});

/***********************************
    AJAX handler for password reset
    @ v: 1.2.19
***********************************/ 
add_action('wp_ajax_lcw_reset_password_ajax', 'lcw_reset_password_ajax');
function lcw_reset_password_ajax() {

    if (!is_user_logged_in()) {
        wp_send_json(['message' => '<p class="hlwpw-warning">You must be logged in.</p>']);
    }

    if (!wp_verify_nonce($_POST['nonce'], 'lcw_reset_password_nonce')) {
        wp_send_json(['message' => '<p class="hlwpw-error">Security check failed.</p>']);
    }

    $user_id = get_current_user_id();
    $password = sanitize_text_field($_POST['password']);
    $confirm_password = sanitize_text_field($_POST['confirm_password']);
    $set_tags = sanitize_text_field($_POST['set_tags']);
    $remove_tags = sanitize_text_field($_POST['remove_tags']);
    $success_message = sanitize_text_field($_POST['success_message']);
    $redirect_to = sanitize_text_field($_POST['redirect_to']);

    if ($password !== $confirm_password) {
        wp_send_json(['message' => '<p class="hlwpw-error">Passwords do not match!</p>']);
    }

    if (current_user_can('administrator') || current_user_can('editor')) {
        wp_send_json(['message' => '<p class="hlwpw-warning">Admins and editors cannot reset password here.</p>']);
    }

    wp_set_password($password, $user_id);

    // Re-authenticate the user after password change
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true, false);

    $message = $success_message;

    // === Premium Feature Logic ===
    $license_data = get_option('leadconnectorwizardpro_license_options');

    if (!empty($set_tags) || !empty($remove_tags)) {
        if (isset($license_data['sc_activation_id'])) {

            $contact_id = lcw_get_contact_id_by_wp_user_id($user_id);

            // Set tags
            if (!empty($set_tags)) {
                $tags = array_map('trim', explode(',', $set_tags));
                $tags = array_filter($tags);
                if (!empty($tags)) {
                    hlwpw_loation_add_contact_tags($contact_id, ['tags' => $tags]);
                }
            }

            // Remove tags
            if (!empty($remove_tags)) {
                $tags = array_map('trim', explode(',', $remove_tags));
                $tags = array_filter($tags);
                if (!empty($tags)) {
                    hlwpw_loation_remove_contact_tags($contact_id, ['tags' => $tags]);
                }
            }

            // Turn on sync
            lcw_turn_on_post_access_update($user_id);

        } else {
            $message = __('Set or Remove tags are premium features. Please activate your license.', 'ghl-wizard') . ' ' . $success_message;
        }
    }

    $redirect = !empty($redirect_to) ? home_url($redirect_to) : '';

    wp_send_json([
        'message' => '<p class="hlwpw-success">' . $message . '</p>',
        'redirect' => $redirect
    ]);
}


/***********************************
    Create Auto Login
    @ v: 1.2
***********************************/
add_action('init', function(){

    if( isset($_REQUEST['lcw_auto_login']) && $_REQUEST['lcw_auto_login'] == 1 ){

        $auto_login_message = lcw_process_auto_login();
        
        if( !empty($auto_login_message) ){
            $message = "<div class='auth-error-message'>";
            $message .= "<p>" . $auto_login_message . "</p>";
            $message .= "</div>";
            echo $message;
        }
    }
});

function lcw_process_auto_login(){
    $auth_key = sanitize_text_field($_REQUEST['lcw_auth_key']);
    $saved_auth_key = get_option('lcw_auth_key', '');
    $autologin_error_transient_key = 'lcw_autologin_error';
    $message = '';

    if ($auth_key != $saved_auth_key || empty($saved_auth_key)) {
        return $message = __('Invalid authentication.', 'ghl-wizard');
    }

    $user_email = sanitize_text_field($_REQUEST['email']);
    if (empty($user_email)) {
        return $message = __('There was no email address provided, please provide a valid email address..', 'ghl-wizard');
    }

    $user = get_user_by('email', $user_email);
    if (!$user) {
        return $message = sprintf(__('We could not find any account associated with your email: %s', 'ghl-wizard'), $user_email);
    }

    $data = get_option('leadconnectorwizardpro_license_options');
    if (!isset($data['sc_activation_id'])) {
        return $message = __('This is a premium feature, please contact with your administrator', 'ghl-wizard');
    }

    //restrict it for admin users
    if( user_can( $user->ID, 'manage_options' ) ){
        return $message = __('Admin is not allowed to auto logged in', 'ghl-wizard');
    }

    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);
    do_action('wp_login', $user->user_login, $user);

    // if you use the 'plugins_loaded' hook, you must set the 'wp_login' hook inside 'init' hook.
    // add_action('init', function() use($user) {
    //     do_action('wp_login', $user->user_login, $user);
    // });

    $redirect_to = isset($_REQUEST['redirect_to']) ? sanitize_text_field($_REQUEST['redirect_to']) : '';
    $redirect_url = !empty($redirect_to) ? home_url($redirect_to) : home_url();
    
    if (!wp_safe_redirect($redirect_url)) {
        wp_redirect($redirect_url);
    }
    exit;
}

// Display Autologin error message
// this was set by transient, now we are using return value

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