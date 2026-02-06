<?php

if ( ! defined( 'ABSPATH' ) ) {
    return;
}
require_once( __DIR__ . '/membership-api.php');

function lcw_get_setting_keys() {
    return [
        // Content protection
        'post_types'             => 'lcw_post_types',
        'no_access_redirect_url' => 'default_no_access_redirect_to',
        // Chat
        'enable_chat' => 'lcw_enable_chat',
        'chat_id'     => 'lcw_chat_id',
        // WooCommerce
        'order_status' => 'hlwpw_order_status',
        'order_tag'    => 'lcw_default_order_tag',
        // Associations
        'enable_associations' => 'lcw_enable_associations',
        'association_id'      => 'lcw_association_id',
        // Autologin
        'auto_login_key'                => 'lcw_auth_key',
        'create_new_user'               => 'lcw_autologin_create_new_user',
        'apply_tag_to_new_users'        => 'lcw_tag_to_autologin_new_user',
        'apply_tag_to_auto_login_users' => 'lcw_tag_to_auto_login_user',
        // Additional
        'hide_admin_bar'         => 'lcw_hide_admin_bar',
        'enable_login_redirect'  => 'lcw_enable_login_redirect',
        'login_redirect_page'    => 'lcw_login_redirect_page',
        'enable_logout_redirect' => 'lcw_enable_logout_redirect',
        'logout_redirect_page'   => 'lcw_logout_redirect_page',
        'disable_new_user_email' => 'lcw_disable_new_user_email',
    ];
}

function lcw_register_rest_routes() {
    // Get settings values
    register_rest_route('connector-wizard/v1', '/settings', [
        'methods' => 'GET',
        'callback' => 'lcw_rest_get_settings',
        'permission_callback' => static function() {
            return current_user_can( 'manage_options' );
        }
    ]);

    // Save settings
    register_rest_route('connector-wizard/v1', '/settings', [
        'methods' => 'POST',
        'callback' => 'lcw_rest_save_settings',
        'permission_callback' => static function() {
            return current_user_can( 'manage_options' );
        }
    ]);

    // Get location tags
    register_rest_route('connector-wizard/v1', '/location-tags', [
        'methods' => 'GET',
        'callback' => 'lcw_rest_get_location_tags',
        'permission_callback' => static function() {
            return current_user_can( 'manage_options' );
        }
    ]);

    // Get associations
    register_rest_route('connector-wizard/v1', '/associations', [
        'methods' => 'GET',
        'callback' => 'lcw_rest_get_associations',
        'permission_callback' => static function() {
            return current_user_can( 'manage_options' );
        }
    ]);

    // Refresh data
    register_rest_route('connector-wizard/v1', '/refresh-data', [
        'methods' => 'GET',
        'callback' => 'lcw_rest_refresh_data',
        'permission_callback' => static function() {
            return current_user_can( 'manage_options' );
        }
    ]);

    // Sync data
    register_rest_route('connector-wizard/v1', '/sync-data', [
        'methods' => 'GET',
        'callback' => 'lcw_rest_sync_data',
        'permission_callback' => static function() {
            return current_user_can( 'manage_options' );
        }
    ] );
}
add_action('rest_api_init', 'lcw_register_rest_routes' );

function lcw_rest_get_settings() {
    $setting_keys = lcw_get_setting_keys();
    $settings = [];
    foreach ($setting_keys as $key => $option_key) {
        if ( 'create_new_user' === $key ) {
            $default_value = true;
        } else {
            $default_value = false;
        }

        $value = get_option($option_key, $default_value );

        if ( is_string( $value ) && 'disabled' === $value ) {
            $value = false;
        }

        $settings[$key] = $value;
    }
    return rest_ensure_response($settings);
}

function lcw_rest_save_settings( $request ) {
    $settings = $request->get_json_params();
    
    // Sanitize settings before saving
    $sanitized = [];
    foreach ( $settings as $key => $value ) {
        $sanitized[ $key ] = is_scalar( $value ) ? sanitize_text_field( $value ) : $value;
    }
    
    $setting_keys = lcw_get_setting_keys();
    foreach ( $setting_keys as $key => $option_key ) {
        update_option( $option_key, $sanitized[ $key ], false );
    }
    
    return rest_ensure_response([
        'success' => true,
        'message' => 'Settings saved successfully'
    ]);
}

function lcw_rest_get_location_tags() {
    $_tags = hlwpw_get_location_tags();

    if ( empty( $_tags ) ) {
        $tags = [];
    }

    $tags = [];
    foreach ( $_tags as $tag ) {
        $tags[] = [
            'id' => $tag->name,
            'label' => $tag->name
        ];
    }

    return rest_ensure_response($tags);
}

function lcw_rest_get_associations() {
    $_associations = hlwpw_get_associations();

    if ( empty( $_associations ) ) {
        rest_ensure_response( [] );
    }

    $_associations = array_filter($_associations, function( $_association ) {
		return $_association->associationType === 'USER_DEFINED';
	});

	// Re-index array
	$_associations = array_values( $_associations );
    
    $associations = [];
    foreach ( $_associations as $association ) {
        $associations[] = [
            'id'    => $association->id,
            'label' => $association->key
        ];
    }

    return rest_ensure_response($associations);
}

function lcw_rest_refresh_data() {
    refresh_data_for_location();

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Data refreshed successfully'
    ], 200);
}

function lcw_rest_sync_data() {
    if ( ! lcw_is_pro_active() ) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'WP users to GHL is available only in pro version'
        ], 400 );
    }

    $callback = apply_filters( 'lcw_wp_users_sync_callback', null );

    if ( ! is_callable( $callback ) ) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Something went wrong cannot sync WP users to GHL'
        ], 400 );
    }

    $callback();

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Data synced successfully'
    ], 200);
}

function lcw_register_membership_routes() {
    // Create membership
    register_rest_route('connector-wizard/v1', '/memberships', [
        'methods' => 'POST',
        'callback' => 'lcw_rest_create_membership',
        'permission_callback' => static function() {
            return current_user_can('manage_options');
        },
        'args' => lcw_get_membership_schema()
    ]);

    // Edit membership
    register_rest_route('connector-wizard/v1', '/memberships/(?P<id>[a-zA-Z0-9\-_]+)', [
        'methods' => 'PUT',
        'callback' => 'lcw_rest_edit_membership',
        'permission_callback' => static function() {
            return current_user_can('manage_options');
        },
        'args' => lcw_get_membership_schema()
    ]);

    // Get single membership
    register_rest_route('connector-wizard/v1', '/memberships/(?P<id>[a-zA-Z0-9\-_]+)', [
        'methods' => 'GET',
        'callback' => 'lcw_rest_get_membership',
        'permission_callback' => static function() {
            return current_user_can('manage_options');
        }
    ]);

    // Get all memberships
    register_rest_route('connector-wizard/v1', '/memberships', [
        'methods' => 'GET',
        'callback' => 'lcw_rest_get_memberships',
        'permission_callback' => static function() {
            return current_user_can('manage_options');
        }
    ]);

    // Delete membership
    register_rest_route('connector-wizard/v1', '/memberships/(?P<id>[a-zA-Z0-9\-_]+)', [
        'methods' => 'DELETE',
        'callback' => 'lcw_rest_delete_membership',
        'permission_callback' => static function() {
            return current_user_can('manage_options');
        }
    ]);
}
add_action('rest_api_init', 'lcw_register_membership_routes');
