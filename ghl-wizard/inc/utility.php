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

        wp_redirect(admin_url('admin.php?page=connector-wizard-app'));
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
                    hlwpw_loation_add_contact_tags($contact_id, ['tags' => $tags], $user_id);
                }
            }

            // Remove tags
            if (!empty($remove_tags)) {
                $tags = array_map('trim', explode(',', $remove_tags));
                $tags = array_filter($tags);
                if (!empty($tags)) {
                    hlwpw_loation_remove_contact_tags($contact_id, ['tags' => $tags], $user_id);
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

// Create a new WP user
// Usually, the password is GHL contact ID but we call it password here
function lcw_create_new_wp_user($email, $password = '', $first_name = '', $last_name = '') {
    
    if ( empty($email) ) {
        return new WP_Error('empty_email', __('Email is required.', 'ghl-wizard'));
    }

    if ( empty($password) ) {
        $password = wp_generate_password();
    }

    $wp_user_id = wp_create_user( $email, $password, $email );
    if( is_wp_error($wp_user_id) ) return $wp_user_id;

    if( !empty($first_name) || !empty($last_name) ) {
        wp_update_user(
            array(
                'ID'         => $wp_user_id,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            )
        );
    }

    if ( empty( get_option( 'lcw_disable_new_user_email' ) ) ) {
        wp_new_user_notification( $wp_user_id, null, 'user' );
    }

    return $wp_user_id;
}

/***********************************
    AJAX handler for auto login
    @ v: 1.4.2
***********************************/
add_action('wp_ajax_lcw_auto_login_ajax', 'lcw_auto_login_ajax');
add_action('wp_ajax_nopriv_lcw_auto_login_ajax', 'lcw_auto_login_ajax');

function lcw_auto_login_ajax() {
    //check_ajax_referer('lcw_auto_login_nonce', 'security'); // TODO: Uncomment this later

    $auth_key     = sanitize_text_field($_POST['lcw_auth_key']);
    $saved_auth   = get_option('lcw_auth_key', '');
    $email        = sanitize_email($_POST['email']);
    $redirect_to  = isset($_POST['redirect_to']) ? sanitize_text_field($_POST['redirect_to']) : '';
    $first_name   = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name    = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    $id           = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $set_tags     = isset($_POST['set_tags']) ? sanitize_text_field($_POST['set_tags']) : '';
    $remove_tags  = isset($_POST['remove_tags']) ? sanitize_text_field($_POST['remove_tags']) : '';
    $success_message = isset($_POST['success_message']) ? sanitize_text_field($_POST['success_message']) : '';

    $data = get_option('leadconnectorwizardpro_license_options');
    if (empty($data['sc_activation_id'])) {
        wp_send_json_error(['message' => __('This is a premium feature, please contact the administrator.', 'ghl-wizard')]);
    }

    if ($auth_key !== $saved_auth || empty($saved_auth)) {
        wp_send_json_error(['message' => __('Invalid authentication.', 'ghl-wizard')]);
    }

    if (empty($email)) {
        wp_send_json_error(['message' => __('Please provide a valid email address.', 'ghl-wizard')]);
    }

    $user = get_user_by('email', $email);

    if ($user && user_can($user->ID, 'manage_options')) {
        wp_send_json_error(['message' => __('Admin users are not allowed to auto login.', 'ghl-wizard')]);
    }

    if (!$user) {

        $lcw_autologin_create_new_user = get_option( 'lcw_autologin_create_new_user' );

        if ( empty( $lcw_autologin_create_new_user ) || $lcw_autologin_create_new_user != 1 ) {
            wp_send_json_error(['message' => __('You are not registered yet. Please contact the site administrator.
            If you are the site administrator, please enable the "Create New User If Not Exists" option in the Connector Wizard settings.', 'ghl-wizard')]);
        }

        $lcw_tag_to_autologin_new_user = get_option( 'lcw_tag_to_autologin_new_user' ) ? get_option( 'lcw_tag_to_autologin_new_user' ) : '';
        
        $user_id = lcw_create_new_wp_user($email, $id, $first_name, $last_name);
        if( is_wp_error($user_id) ) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }
        $user = new WP_User($user_id);
        // Add contact data to wp contacts table
		lcw_sync_contact_in_wp_contacts_table( '', $user );
    }

    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);

    $lcw_tag_to_auto_login_user = get_option( 'lcw_tag_to_auto_login_user' ) ? get_option( 'lcw_tag_to_auto_login_user' ) : '';

    // Merge the 3 strings separated with comma (if not empty)
    $tag_pieces = [];
    if (!empty($set_tags)) {
        $tag_pieces[] = $set_tags;
    }
    if (!empty($lcw_tag_to_auto_login_user)) {
        $tag_pieces[] = $lcw_tag_to_auto_login_user;
    }
    if (isset($lcw_tag_to_autologin_new_user) && !empty($lcw_tag_to_autologin_new_user)) {
        $tag_pieces[] = $lcw_tag_to_autologin_new_user;
    }
    $auto_login_tags = implode(',', $tag_pieces);


    if ( ! empty($auto_login_tags)) {
        $contact_id = lcw_get_contact_id_by_wp_user_id($user->ID);
        $tags = array_map('trim', explode(',', $auto_login_tags));
        $tags = array_filter($tags);
        if (!empty($tags)) {
            hlwpw_loation_add_contact_tags($contact_id, ['tags' => $tags], $user->ID);
        }
    }

    if ( ! empty($remove_tags)) {
        $contact_id = lcw_get_contact_id_by_wp_user_id($user->ID);
        $tags = array_map('trim', explode(',', $remove_tags));
        $tags = array_filter($tags);
        if (!empty($tags)) {
            hlwpw_loation_remove_contact_tags($contact_id, ['tags' => $tags], $user->ID);
        }
    }

    $redirect_url = !empty($redirect_to) ? home_url($redirect_to) : home_url();

    wp_send_json_success([
        'message'  => $success_message,
        'redirect' => esc_url_raw($redirect_url)
    ]);
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
            $sql_contact .= " `parent_user_id` longtext DEFAULT NULL, ";
            $sql_contact .= " `children_user_id` longtext DEFAULT NULL, ";
            $sql_contact .= " `updated_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, ";
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

        // Ensure columns exist (for plugin updates)
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM $table_lcw_contact", 0 );

        if ( ! in_array( 'children_user_id', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table_lcw_contact ADD `children_user_id` longtext DEFAULT NULL AFTER `notes`" );
        }

        if ( ! in_array( 'parent_user_id', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table_lcw_contact ADD `parent_user_id` longtext DEFAULT NULL AFTER `notes`" );
        }

        update_option( 'lcw_db_table_exists', 1 );
        update_option( 'lcw_db_version', LCW_DB_VERSION );
    }
}

function lcw_check_db_update() {
    if ( get_option( 'lcw_db_version' ) != LCW_DB_VERSION ) {
        lcw_create_location_and_contact_table();
    }
}
add_action( 'plugins_loaded', 'lcw_check_db_update' );


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

    $hlwpw_locationId = lcw_get_location_id();
    $hlwpw_access_token = lcw_get_access_token();
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

    $location_id = lcw_get_location_id();
    $membership_meta_key = $location_id . "_hlwpw_memberships";
    $memberships = lcw_get_memberships();

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
    $chat_enabled = get_option( 'lcw_enable_chat', false );
    if ( empty( $chat_enabled ) || $chat_enabled === 'disabled' ) {
        return;
    }

    $widget = '';
    if ( empty( get_option( 'lcw_chat_id' ) ) ) {
        $widget = '<chat-widget location-id="' . esc_attr( lcw_get_location_id() ) . '" show-consent-checkbox="true"></chat-widget>';
        $widget .= '<script src="https://widgets.leadconnectorhq.com/loader.js" data-resources-url="https://widgets.leadconnectorhq.com/chat-widget/loader.js"></script>';
    } else {
        $widget .= '<script src="https://widgets.leadconnectorhq.com/loader.js" data-resources-url="https://widgets.leadconnectorhq.com/chat-widget/loader.js" data-widget-id="' . esc_attr( get_option( 'lcw_chat_id' ) ) . '"></script>';
    }
    
    echo $widget;
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

if ( ! function_exists( 'lcw_get_location_id' ) ) {
	function lcw_get_location_id() {
		return get_option( 'hlwpw_locationId' );
	}
}

if ( ! function_exists( 'lcw_get_memberships' ) ) {
	function lcw_get_memberships() {
		$option_key = lcw_get_location_id() . '_hlwpw_memberships';
		$memberships = get_option( $option_key, [] );

		if ( empty( $memberships ) || ! is_array( $memberships ) ) {
			return [];
		}

		return $memberships;
	}
}

function lcw_get_access_token() {
    return get_option( 'hlwpw_access_token' );
}

/**
 * Normalize input to an array.
 *
 * @param string|array $input Comma-separated string or array.
 * @return array Normalized array of values.
 */
function lcw_string_to_array( $input ) {
    // Return empty array if input is empty or not string/array
    if ( empty( $input ) || ( ! is_string( $input ) && ! is_array( $input ) ) ) {
        return [];
    }

    // If it's already an array, return it as-is
    if ( is_array( $input ) ) {
        return $input;
    }

    // Convert comma-separated string to array and filter out empty values
    return array_filter( array_map( 'trim', explode( ',', $input ) ) );
}

/**
 * Get encrypted email and website URL of current user.
 *
 * @return string Base64-encoded encrypted string containing email and website URL separated by pipe.
 */
function lcw_get_encrypted_parcel() {
    $user = wp_get_current_user();
    $email     = $user->user_email;
    $first_name = $user->first_name;
    $last_name = $user->last_name;
    $website   = home_url();
    $combined  = $email . '|' . $website . '|' . $first_name . '|' . $last_name;
    $encrypted = base64_encode( openssl_encrypt( $combined, 'aes-256-cbc', 'WizardOfGHL', 0, '1234567890123456' ) );
    return $encrypted;
}

function lcw_is_pro_active() {
    return is_plugin_active( 'lead-connector-wizard-pro/lead-connector-wizard-pro.php' );
}

function lcw_get_plugin_version() {
    $plugins = get_plugins();
    if ( lcw_is_pro_active() ) {
        $version = $plugins['lead-connector-wizard-pro/lead-connector-wizard-pro.php']['Version'];
    } else {
        $version = $plugins['ghl-wizard/ghl-wizard.php']['Version'];
    }

    return $version;
}

function lcw_get_connect_url() {
    return add_query_arg( [
        'get_code'      => 1,
        'parcel'        => lcw_get_encrypted_parcel(),
        'redirect_page' => admin_url( 'admin.php?page=connector-wizard-app' ),
    ], 'https://betterwizard.com/lc-wizard' );
}

function lcw_is_crm_connected() {
    return ( get_option( 'hlwpw_location_connected' ) == 1 );
}

// when a user is deleted from Wordpress, delete them from lcw_contacts table
function lcw_delete_user_from_contacts_table( $user_id ) {
    $user_email = get_user_by( 'id', $user_id )->user_email;
    global $wpdb;
    $table_name = $wpdb->prefix . 'lcw_contacts';
    $wpdb->delete( $table_name, array( 'contact_email' => $user_email ) );
}
add_action( 'delete_user', 'lcw_delete_user_from_contacts_table' );
