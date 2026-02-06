<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lcw_get_membership_schema() {
    return [
        'name' => [
            'required' => true,
            'type' => 'string',
            'description' => 'Membership name',
            'sanitize_callback' => static function( $value ) {
                $value = sanitize_text_field( $value );
                $value = strtolower( $value );
                return preg_replace('/[^a-z0-9_]/', '_', $value );
            },
            'validate_callback' => static function($value) {
                return !empty($value);
            }
        ],
        'new_tag_name' => [
            'type' => 'string',
            'description' => 'New tag name',
            'sanitize_callback' => static function( $value ) {
                $value = sanitize_text_field( $value );
                $value = strtolower( $value );
                return preg_replace('/[^a-z0-9_]/', '_', $value );
            },
        ],
        'tags' => [
            'required' => false,
            'type' => 'array',
            'description' => 'Membership tags',
            'default' => [],
            'items' => [
                'type' => 'string'
            ],
            'sanitize_callback' => static function($tags) {
                return array_map('sanitize_text_field', (array) $tags);
            }
        ],
        'level' => [
            'required' => false,
            'type' => 'integer',
            'description' => 'Membership level',
            'default' => 0,
            'sanitize_callback' => 'absint',
            'validate_callback' => static function($value) {
                return is_numeric($value) && $value >= 0;
            }
        ],
        'redirect_to' => [
            'required' => false,
            'type' => 'string',
            'description' => 'Redirect page ID',
            'default' => '',
            'sanitize_callback' => static function( $value ) {
                return (string) absint( $value );
            },
            'validate_callback' => static function($value) {
                if (empty($value)) {
                    return true;
                }
                return get_post_status($value) !== false;
            }
        ]
    ];
}

function lcw_update_memberships( $memberships = [] ) {
    $option_key = lcw_get_location_id() . '_hlwpw_memberships';
    update_option( $option_key, $memberships, false );
}

function lcw_normalize_membership_for_api( $membership ) {
    return [
        'id'          => $membership['membership_name'] ?? uniqid('membership-'),
        'name'        => $membership['membership_name'] ?? '',
        'tags'        => isset( $membership['membership_tag_name'] ) ? array_values( $membership['membership_tag_name'] ) : [],
        'level'       => $membership['membership_level'] ?? 0,
        'redirect_to' => $membership['membership_redirect_to'] ?? '',
    ];
}

function lcw_normalize_membership_for_db( $membership ) {
    return [
        'membership_name'        => $membership['name'] ?? '',
        'membership_tag_name'    => $membership['tags'] ?? [],
        'membership_level'       => $membership['level'] ?? 0,
        'membership_redirect_to' => $membership['redirect_to'] ?? '',
    ];
}

/**
 * Database helper functions (implement based on your storage method)
 */

function lcw_upsert_membership($data) {
    $defaults = [
        'name'        => '',
        'tags'        => [],
        'level'       => 0,
        'redirect_to' => '',
    ];
    
    $membership_data = wp_parse_args( $data, $defaults );
    
    if ( empty( $membership_data['name'] ) ) {
        return new WP_Error( 'invalid_name', 'Membership name is required' );
    }

    $memberships   = lcw_get_memberships();
    $membership_id = $membership_data['name'];

    if ( ! empty( $memberships[ $membership_id ] ) ) {
        $membership_data = lcw_normalize_membership_for_db( $membership_data );
        
        unset( $membership_data['membership_name'] );
        unset( $membership_data['membership_tag_name'] );

        $updatable_membership = $memberships[ $membership_id ];
        $membership_data      = array_merge( $updatable_membership, $membership_data );

        $memberships[ $membership_id ] = $membership_data;
    } else {
        $membership_data = lcw_normalize_membership_for_db( $membership_data );

        $memberships[ $membership_id ] = $membership_data;
    }

    lcw_update_memberships( $memberships );
    
    return [
        $membership_id,
        $memberships[ $membership_id ]
    ];
}

function lcw_rest_get_memberships() {
    $memberships = lcw_get_memberships();

    if ( empty( $memberships ) || ! is_array( $memberships ) ) {
        rest_ensure_response( [] );
    }

    $memberships = array_map( 'lcw_normalize_membership_for_api', $memberships );

    return rest_ensure_response( array_values( $memberships ) );
}

/**
 * Create a new membership
 */
function lcw_rest_create_membership(WP_REST_Request $request) {
    $params = $request->get_params();
    
    // Prepare membership data
    $membership_data = [
        'name'        => $params['name'],
        'tags'        => $params['tags'] ?? [],
        'level'       => $params['level'] ?? 0,
        'redirect_to' => $params['redirect_to'] ?? '',
    ];

    $memberships = lcw_get_memberships();
    if ( isset( $memberships[ $membership_data['name'] ] ) ) {
        return new WP_REST_Response([
            'success' => false,
            'message' => sprintf( '"%s" membership already exists', $membership_data['name'] ),
        ], 500);
    }

    if ( empty( $request['new_tag_name'] ) && empty( $request['tags'] ) ) {
        return new WP_REST_Response([
            'success' => false,
            'message' => "Tag is required, select existing tag or give a new tag name"
        ], 500);
    }

    if ( ! empty( $request['new_tag_name'] ) ) {
        $tags = array(
            'membership_tag' 	=> $request['new_tag_name'],
            '_payf_tag' 		=> $request['new_tag_name'] . "_payf",
            '_susp_tag' 		=> $request['new_tag_name'] . "_susp",
            '_canc_tag' 		=> $request['new_tag_name'] . "_canc"
        );
    } elseif ( isset( $membership_data['tags'][0] ) && ! empty( $membership_data['tags'][0] ) ) {
        $tags = array(
			'membership_tag' 	=> $membership_data['tags'][0],
			'_payf_tag' 		=> $membership_data['tags'][0] . "_payf",
			'_susp_tag' 		=> $membership_data['tags'][0] . "_susp",
			'_canc_tag' 		=> $membership_data['tags'][0] . "_canc"
		);
    }

    $membership_data['tags'] = $tags;

    // Save to database (using custom table or post meta)
    $membership = lcw_upsert_membership($membership_data);

    if (is_wp_error($membership)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $membership->get_error_message()
        ], 500);
    }

    // Create Location Tags
	foreach ( $tags as $tag ) {
		hlwpw_create_location_tag($tag);
	}

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Membership created successfully',
        'data' => [
            'id' => $membership[0],
            'membership' => lcw_normalize_membership_for_api( $membership[1] )
        ]
    ], 201);
}

/**
 * Create a new membership
 */
function lcw_rest_edit_membership(WP_REST_Request $request) {
    $params = $request->get_params();

    $memberships = lcw_get_memberships();
    if ( empty( $memberships[ $params['id'] ] ) ) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Membership did not found'
        ], 500);
    }

    // Prepare membership data
    $membership_data = [
        'name'        => $params['name'],
        'level'       => $params['level'] ?? 0,
        'redirect_to' => $params['redirect_to'] ?? '',
    ];

    // Save to database (using custom table or post meta)
    $membership = lcw_upsert_membership($membership_data);

    if (is_wp_error($membership)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $membership->get_error_message()
        ], 500);
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Membership created successfully',
        'data' => [
            'id' => $membership[0],
            'membership' => lcw_normalize_membership_for_api( $membership[1] )
        ]
    ], 201);
}

function lcw_rest_delete_membership(WP_REST_Request $request) {
    $id = $request->get_param('id');

    $memberships = lcw_get_memberships();
    if ( empty( $memberships ) || ! isset( $memberships[ $id ] ) ) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Membership not found'
        ], 404);
    }

    $memberships = lcw_get_memberships();
    unset( $memberships[ $id ] );

    lcw_update_memberships( $memberships );

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Membership deleted successfully'
    ], 200);
}
