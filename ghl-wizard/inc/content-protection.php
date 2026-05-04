<?php

//content protection settings

// Check if a particular post has accees to the
// Curent logged in user
function hlwpw_has_access( $post_id, $user_id = null ){
    
    $is_explicit_user = null !== $user_id;
    $user_id = $is_explicit_user ? absint( $user_id ) : get_current_user_id();
    $is_logged_in_context = $is_explicit_user ? ( $user_id > 0 ) : is_user_logged_in();

    if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
		return true;
	}

	$has_access = false;
	$location_id = lcw_get_location_id();
	$membership_meta_key = $location_id . "_hlwpw_memberships";

	// check things
	// Which restrictions are applied.
	$login_restriction_value 		= get_post_meta( $post_id, 'hlwpw_logged_in_user', true );
	$membership_restriction_value 	= get_post_meta( $post_id, $membership_meta_key, true );
	$tag_restriction_value 			= get_post_meta( $post_id, 'hlwpw_required_tags', true );
	$and_tag_restriction_value 		= get_post_meta( $post_id, 'hlwpw_and_required_tags', true );

	// if there are no restrictions for this post
	if ( empty( $login_restriction_value ) && empty( $membership_restriction_value )  && empty( $tag_restriction_value) && empty( $and_tag_restriction_value) ) {
		return true;
	}

	// 1. login Restriction
	if ( "logged_in" == $login_restriction_value )  {
		$has_access = $is_logged_in_context ? true : false;
	}elseif( "logged_out" == $login_restriction_value ){
		$has_access = $is_logged_in_context ? false : true;
	}

	// 2. Membership Restriction
	if ( !empty ($membership_restriction_value )) {
		//print_r( $membership_restriction_value );
		//echo $post_id . "<br>";

		// If any_membership is selected?
		if ( in_array( 1, $membership_restriction_value ) ) {

			$memberships_levels = array_keys( lcw_get_memberships() );
			$has_access = hlwpw_membership_restriction( $memberships_levels, $user_id );

		}else{

			$has_access = hlwpw_membership_restriction( $membership_restriction_value, $user_id );
		}

		//var_dump($has_access);
		//echo "<br>";
	}	

	// 3. Tag Restriction
	if ( !empty($tag_restriction_value) && !empty($and_tag_restriction_value) ) {
		
		$tag_restriction 		= hlwpw_contact_has_tag( $tag_restriction_value, 'any', $user_id );
		$and_tag_restriction 	= hlwpw_contact_has_tag( $and_tag_restriction_value, 'any', $user_id );

		if ( $tag_restriction && $and_tag_restriction ) {
			$has_access = true;
		}

	}elseif ( !empty($tag_restriction_value) ) {
		$has_access = hlwpw_contact_has_tag( $tag_restriction_value, 'any', $user_id );
	}

	
/*
echo "tag_restriction - ";
	var_dump($tag_restriction);
	echo "and_tag_restriction - ";
	var_dump($and_tag_restriction);
	echo "<br>";

	if ( empty( $and_tag_restriction_value ) ) {
		$has_access = $tag_restriction;

		//var_dump($has_access);
		//echo " tag - <br>";

	}elseif ( $tag_restriction && $and_tag_restriction ) {
		$has_access = true;

		//var_dump($has_access);
		//echo "AND tag - <br>";
	}
*/
	//var_dump( $has_access );


	return $has_access;
}



// $m = hlwpw_membership_restriction('gold');
// var_dump($m);

function hlwpw_membership_restriction( $memberships, $user_id = null ){

	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );

	if ( ! $user_id ) {
		return false;
	}

	// Provide access to admin
	if ( user_can( $user_id, 'manage_options') ) {
		return true;
	}

	$memberships = lcw_string_to_array( $memberships );
	if ( empty( $memberships ) ) {
		return false;
	}

	$memberships_levels = lcw_get_memberships();

	foreach ( $memberships as $membershp ) {

		// Check membership levels here, if the top level has access, return true
		
		if ( $memberships_levels[$membershp]['membership_name'] == $membershp ) {
			
			$membership_tags_set = $memberships_levels[$membershp]['membership_tag_name'];
			$membership_tag = $membership_tags_set['membership_tag'];
			$_payf_tag = $membership_tags_set['_payf_tag'];
			$_susp_tag = $membership_tags_set['_susp_tag'];
			$_canc_tag = $membership_tags_set['_canc_tag'];

			// Check membership
			if ( hlwpw_contact_has_tag( $membership_tag, 'any', $user_id ) && ! hlwpw_contact_has_tag( [$_payf_tag, $_susp_tag, $_canc_tag ], 'any', $user_id ) ) {

			 	return true;
			} 

		}

	}

	return false;

}




// @v 1.1
// Need to check why login restriction was removed


// Is Post Restricted?
// Check if a post has the restriction enabled.
// Return True if there is any restriction
function hlwpw_is_post_restricted( $post_id ){

	$location_id = lcw_get_location_id();
	$membership_meta_key = $location_id . "_hlwpw_memberships";

	// check things
	// Which restrictions are applied.
	
// login restriction
// Login restriction is moved to seperate function
	
	// Other restrictions
	$membership_restriction_value 	= get_post_meta( $post_id, $membership_meta_key, true );
	$tag_restriction_value 			= get_post_meta( $post_id, 'hlwpw_required_tags', true );
	$and_tag_restriction_value 		= get_post_meta( $post_id, 'hlwpw_and_required_tags', true );

	if ( ! empty( $membership_restriction_value )  || !empty( $tag_restriction_value) || !empty( $and_tag_restriction_value) ) {
		// No restriction Found
		return true;
	}else{
		// Restricted
		return false;
	}
}


// Is Post has login Restriction?
// Check if a post has login/logout restriction enabled.
// Return true if login & logout restriction enabled
function hlwpw_is_post_has_login_restriction( $post_id ){
	
	// login restriction
	$login_restriction_value = get_post_meta( $post_id, 'hlwpw_logged_in_user', true );

	if ( !empty( $login_restriction_value ) ) {
		// No restriction Found
		return true;
	}else{
		// Restricted
		return false;
	}
}

function hlwpw_contact_has_tag( $tags, $condition = 'any', $user_id = null ){
	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );
	if ( ! $user_id ) {
		return false;
	}

	$tags = lcw_string_to_array( $tags );
	if ( empty( $tags ) ) {
		return false;
	}

	$contact_tags = lcw_get_user_tags( $user_id );
	
	// Query Parents' tags and merge with current user tags
	$parent_ids = lwc_get_user_parent_ids( $user_id );

	if ( ! empty( $parent_ids ) ) {
		$parent_tags = array_map( function( $parent_id ) {
            $tags = lcw_get_user_tags( $parent_id );
            if ( is_wp_error( $tags ) ) {
                return [];
            }
            return $tags;
        }, $parent_ids );
		
		$contact_tags = array_unique( array_merge( $contact_tags, ...$parent_tags ) );
	}

	return lcw_check_tag_condition( $tags, $contact_tags, $condition );
}

/**
 * Get user parent ids.
 * 
 * @param int $user_id
 * @return int[]
 */
function lwc_get_user_parent_ids( $user_id ) {
    $user_data = lcw_get_user_data( $user_id );
    
    if ( ! $user_data || empty( $user_data->parent_user_id ) ) {
        return [];
    }

    $parent_user_ids = $user_data->parent_user_id;

    // Decode JSON string into PHP array
    $decoded = json_decode( $parent_user_ids, true );

    // Make sure it's an array and cast values to int
    if ( is_array( $decoded ) ) {
        return array_map( 'intval', $decoded );
    }

    // Fallback: return as single integer inside array
    return [ intval( $parent_user_ids ) ];
}


function hlwpw_no_access_restriction() {

	$post_id = get_queried_object_id();

	if ( ! hlwpw_has_access( $post_id ) ) {

		// if ( ! is_user_logged_in() ) {
		// 	wp_redirect( wp_login_url( get_permalink( $post_id ) ) );
		// 	exit;
		// }

		if ( wp_is_serving_rest_request() || wp_doing_ajax() ) {
			wp_send_json_error(
				[
					'code'    => 'no_access',
					'message' => 'You do not have permission to access this content.',
				],
				403
			);
		}		
		
		$default_no_access_redirect_to = get_option( 'default_no_access_redirect_to' );
		$post_redirect_to = get_post_meta($post_id, 'hlwpw_no_access_redirect_to', true);

		if ( !empty( $post_redirect_to )) {
			wp_redirect( $post_redirect_to );
			exit;
		}elseif ( !empty( $default_no_access_redirect_to ) ) {
			wp_redirect( $default_no_access_redirect_to );
			exit;
		}

		wp_redirect( home_url( '/no-access-page/' ) );
		exit;
	}

}
add_action( 'template_redirect', 'hlwpw_no_access_restriction' );





// Keep Track about restricted Pages
// For new posts


// When posts updated
// Needs to recalculate the page restriction
// so version cache and warm it asynchronously.
function hlwpw_get_restriction_cache_version() {
	$version = (int) get_option( 'lcw_restriction_cache_version', 1 );
	return $version > 0 ? $version : 1;
}

function hlwpw_bump_restriction_cache_version() {
	$version = hlwpw_get_restriction_cache_version() + 1;
	update_option( 'lcw_restriction_cache_version', $version );
}

function hlwpw_get_restriction_cache_ttl() {
	$ttl = absint( apply_filters( 'lcw_restriction_cache_ttl', DAY_IN_SECONDS ) );
	return $ttl > 0 ? $ttl : DAY_IN_SECONDS;
}

function hlwpw_get_restriction_post_types() {
	$lcw_post_types = get_option( 'lcw_post_types', [] );
	if ( ! is_array( $lcw_post_types ) ) {
		$lcw_post_types = [];
	}

	$post_types = array_map( 'sanitize_key', array_merge( [ 'page' ], $lcw_post_types ) );
	$post_types = array_values( array_unique( array_filter( $post_types ) ) );

	return ! empty( $post_types ) ? $post_types : [ 'page' ];
}

function hlwpw_get_restriction_meta_keys() {
	$location_id = lcw_get_location_id();
	return [
		$location_id . '_hlwpw_memberships',
		'hlwpw_required_tags',
		'hlwpw_and_required_tags',
		'hlwpw_logged_in_user',
		'lcw_ld_auto_enrollment_tags',
	];
}

function hlwpw_get_restriction_cache_key( $cache_group, array $post_types ) {
	sort( $post_types );
	$post_types_hash = substr( md5( wp_json_encode( $post_types ) ), 0, 12 );
	$location_id     = lcw_get_location_id();
	$location_part   = ! empty( $location_id ) ? sanitize_key( (string) $location_id ) : 'no_location';

	return sprintf(
		'lcw_%s_v%s_%s_%s',
		sanitize_key( $cache_group ),
		hlwpw_get_restriction_cache_version(),
		$location_part,
		$post_types_hash
	);
}

function hlwpw_get_batched_post_ids( array $query_args, $batch_size_filter = 'lcw_restriction_query_batch_size' ) {
	$batch_size = absint( apply_filters( $batch_size_filter, 50 ) );
	$batch_size = $batch_size > 0 ? $batch_size : 50;

	$page    = 1;
	$post_ids = [];

	while ( true ) {
		$args = array_merge(
			[
				'fields'                 => 'ids',
				'posts_per_page'         => $batch_size,
				'paged'                  => $page,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			],
			$query_args
		);

		$current_ids = get_posts( $args );
		if ( empty( $current_ids ) ) {
			break;
		}

		$post_ids = array_merge( $post_ids, array_map( 'intval', $current_ids ) );
		if ( count( $current_ids ) < $batch_size ) {
			break;
		}

		$page++;
	}

	return $post_ids;
}

function hlwpw_schedule_restriction_cache_warmup() {
	if ( ! wp_next_scheduled( 'lcw_warm_restriction_caches' ) ) {
		wp_schedule_single_event( time() + 15, 'lcw_warm_restriction_caches' );
	}
}

function hlwpw_invalidate_restriction_caches() {
	static $already_invalidated = false;
	if ( $already_invalidated ) {
		return;
	}

	hlwpw_bump_restriction_cache_version();
	hlwpw_schedule_restriction_cache_warmup();
	$already_invalidated = true;
}

function hlwpw_handle_restriction_post_change( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	hlwpw_invalidate_restriction_caches();
}
add_action( 'save_post', 'hlwpw_handle_restriction_post_change', 10, 1 );
add_action( 'deleted_post', 'hlwpw_handle_restriction_post_change', 10, 1 );
add_action( 'trashed_post', 'hlwpw_handle_restriction_post_change', 10, 1 );
add_action( 'untrashed_post', 'hlwpw_handle_restriction_post_change', 10, 1 );

function hlwpw_maybe_invalidate_restriction_cache_by_meta( $meta_key ) {
	if ( in_array( $meta_key, hlwpw_get_restriction_meta_keys(), true ) ) {
		hlwpw_invalidate_restriction_caches();
	}
}

function hlwpw_handle_restriction_meta_added( $meta_id, $object_id, $meta_key ) {
	hlwpw_maybe_invalidate_restriction_cache_by_meta( $meta_key );
}
add_action( 'added_post_meta', 'hlwpw_handle_restriction_meta_added', 10, 3 );

function hlwpw_handle_restriction_meta_updated( $meta_id, $object_id, $meta_key ) {
	hlwpw_maybe_invalidate_restriction_cache_by_meta( $meta_key );
}
add_action( 'updated_post_meta', 'hlwpw_handle_restriction_meta_updated', 10, 3 );

function hlwpw_handle_restriction_meta_deleted( $meta_ids, $object_id, $meta_key ) {
	hlwpw_maybe_invalidate_restriction_cache_by_meta( $meta_key );
}
add_action( 'deleted_post_meta', 'hlwpw_handle_restriction_meta_deleted', 10, 3 );

function hlwpw_maybe_invalidate_restriction_cache_on_option_change( $option_name ) {
	if ( in_array( $option_name, [ 'lcw_post_types', 'hlwpw_locationId' ], true ) ) {
		hlwpw_invalidate_restriction_caches();
	}
}

function hlwpw_handle_restriction_option_updated( $option_name, $old_value, $value ) {
	hlwpw_maybe_invalidate_restriction_cache_on_option_change( $option_name );
}
add_action( 'updated_option', 'hlwpw_handle_restriction_option_updated', 10, 3 );

function hlwpw_handle_restriction_option_added( $option_name, $value ) {
	hlwpw_maybe_invalidate_restriction_cache_on_option_change( $option_name );
}
add_action( 'added_option', 'hlwpw_handle_restriction_option_added', 10, 2 );

function hlwpw_handle_restriction_option_deleted( $option_name ) {
	hlwpw_maybe_invalidate_restriction_cache_on_option_change( $option_name );
}
add_action( 'deleted_option', 'hlwpw_handle_restriction_option_deleted', 10, 1 );

function hlwpw_warm_restriction_caches() {
	hlwpw_get_all_restricted_posts();
	hlwpw_get_all_login_restricted_posts();
}
add_action( 'lcw_warm_restriction_caches', 'hlwpw_warm_restriction_caches' );



// Keep Track about restricted Pages
// need to use this hook on post updates
function hlwpw_get_all_restricted_posts(){

	// only needs to update when a post is updated
	// or created

	// if ( is_admin() ) {
	// 	return;
	// }

	$expiry         = hlwpw_get_restriction_cache_ttl();
	$lcw_post_types = hlwpw_get_restriction_post_types();
	$key            = hlwpw_get_restriction_cache_key( 'restricted_posts', $lcw_post_types );

	$restricted_posts = get_transient($key);

	if ( false !== $restricted_posts ) {
		// delete_transient($key);
		return $restricted_posts;
	}

//var_dump($lcw_post_types);

// meta query doesn't work because of array
// empty array also save serialized string

 	$location_id = lcw_get_location_id();
	$membership_meta_key = $location_id . "_hlwpw_memberships";

	$meta_query = array(
        'relation' => 'OR',
        array(
            'key' => $membership_meta_key,
            'compare' => 'EXISTS'
        ),
        array(
            'key' => 'hlwpw_required_tags',
            'compare' => 'EXISTS'
        ),
        array(
            'key' => 'hlwpw_and_required_tags',
            'compare' => 'EXISTS'
        ),
    );

	
	$all_posts = hlwpw_get_batched_post_ids(
		array(
			'post_type'  => $lcw_post_types,
			'meta_query' => $meta_query,
		),
		'lcw_restriction_query_batch_size'
	);


// echo "<pre>";
// print_r( $all_posts );
// echo "</pre>";


	$restricted_posts = [];

	foreach ( $all_posts as $post_id ) {

		$is_restricted = hlwpw_is_post_restricted( $post_id );

		if ( $is_restricted ) {
			array_push( $restricted_posts, $post_id );
		}
	}

	set_transient( $key, $restricted_posts, $expiry );
	return $restricted_posts;	
}

// echo "<pre>";
// print_r( hlwpw_get_all_restricted_posts() );
// echo "</pre>";



// Keep Track about login/logout restricted Pages
// need to use this hook on post updates
function hlwpw_get_all_login_restricted_posts(){

	$expiry         = hlwpw_get_restriction_cache_ttl();
	$lcw_post_types = hlwpw_get_restriction_post_types();
	$key            = hlwpw_get_restriction_cache_key( 'login_restricted_posts', $lcw_post_types );

	$login_restricted_posts = get_transient($key);

	if ( false !== $login_restricted_posts ) {
        //delete_transient($key);
		return $login_restricted_posts;
	}

	$all_posts = hlwpw_get_batched_post_ids(
		array(
			'post_type'  => $lcw_post_types,
			'meta_query' => array(
				array(
					'key'     => 'hlwpw_logged_in_user',
					'compare' => 'EXISTS',
				),
			),
		),
		'lcw_restriction_query_batch_size'
	);

// echo "<pre>";
// print_r( $all_posts );
// echo "</pre>";

	$login_restricted_posts = [];

	foreach ( $all_posts as $post_id ) {

	    $login_restriction_value = get_post_meta( $post_id, 'hlwpw_logged_in_user', true );
	    
	    if( 'logged_in' == $login_restriction_value ){
	        $login_restricted_posts['logged_in'][] = $post_id;
	    }else{
	        $login_restricted_posts['logged_out'][] = $post_id;
	    }
	}

	set_transient( $key, $login_restricted_posts, $expiry );
	return $login_restricted_posts;
}
// echo "<pre>";
// print_r( hlwpw_get_all_login_restricted_posts() );
// echo "</pre>";



// Update restricted post main task after checking it's required
function lcw_update_restricted_posts( $user_id ) {

	if ( ! $user_id ) {
		return;
	}

	global $wpdb;
	$table_lcw_contact = $wpdb->prefix . 'lcw_contacts';

	$restricted_posts = hlwpw_get_all_restricted_posts();
	$has_not_access   = [];

	foreach ( $restricted_posts as $post_id ) {
		// Set the parent access condition
		if ( ! hlwpw_has_access( $post_id, $user_id ) ) {
			$has_not_access[] = $post_id;
		}
	}

	// Save has_not_access posts to database
	$result = $wpdb->update(
		$table_lcw_contact,
		array(
			'has_not_access_to'     => serialize( $has_not_access ),
			'updated_on'            => current_time( 'mysql' ),
			'need_to_update_access' => 0
		),
		array( 'user_id' => $user_id )
	);

		// Manage LearnDash course auto enrollment.
		lcw_manage_learndash_course_auto_enrollment( $user_id );

	return $result;
}

// Update restricted posts 
// on user login
// or a shortcode to force update 
// restricted posts list
/***********************************
    Update post restrictions 
    of a user if needed
    @ v: 1.1
***********************************/
function lcw_update_restricted_posts_if_needed( $user_id = null ) {
	$is_explicit_user_id = null !== $user_id;

	if ( null === $user_id ) {
		$current_user = wp_get_current_user();
		$user_id      = isset( $current_user->ID ) ? absint( $current_user->ID ) : 0;
	} else {
		$user_id = absint( $user_id );
	}

	if ( ! $user_id ) {
		return;
	}

	if ( is_admin() && ! $is_explicit_user_id ) {
		return;
	}

	if ( user_can( $user_id, 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	$table_lcw_contact = $wpdb->prefix . 'lcw_contacts';

	$user_data = lcw_get_user_data( $user_id );
	if ( ! $user_data ) {
		return;
	}

	$need_to_update_access = isset( $user_data->need_to_update_access ) ? (int) $user_data->need_to_update_access : 0;

	if ( $need_to_update_access ) {

		// this section is moved to a function.
		return lcw_update_restricted_posts( $user_id );

		/* $restricted_posts = hlwpw_get_all_restricted_posts();
		$has_not_access   = [];

		foreach ( $restricted_posts as $post_id ) {
			// Set the parent access condition
			if ( ! hlwpw_has_access( $post_id ) ) {
				$has_not_access[] = $post_id;
			}
		}

		// Save has_not_access posts to database
		$result = $wpdb->update(
	        $table_lcw_contact,
	        array(
				'has_not_access_to'     => serialize( $has_not_access ),
				'updated_on'            => current_time( 'mysql' ),
				'need_to_update_access' => 0
	        ),
	        array( 'user_id' => $user_id )
	    );

			// Manage LearnDash course auto enrollment.
			lcw_manage_learndash_course_auto_enrollment( $user_id );

		return $result; */
	}
}
// Add it to woocommerce_thankyou hook - DONE
// and create a workflow for add/remove tag and implement that.


// Manage LearnDash Course Access based on auto-enrollment tags
function lcw_manage_learndash_course_auto_enrollment( $user_id = null ){
	
	if ( ! defined( 'LEARNDASH_VERSION' ) ) {
		return;
	}	

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( 0 == $user_id || current_user_can('manage_options') ) {
		return;
	}

	// get all ids of LearnDash courses/groups that use auto enrollment tags
	$learndash_course_ids = hlwpw_get_batched_post_ids(
		array(
			'post_type'  => array( 'sfwd-courses', 'groups' ),
			'meta_query' => array(
				array(
					'key'     => 'lcw_ld_auto_enrollment_tags',
					'compare' => 'EXISTS',
				),
			),
		),
		'lcw_learndash_query_batch_size'
	);

	if ( empty( $learndash_course_ids ) ) {
		return;
	}
	
	$user_tags = unserialize (lcw_get_contact_tags_by_wp_id( $user_id ));
	
	// combine parent tags with user tags
	$parent_user_ids = lwc_get_user_parent_ids( $user_id );
	foreach ( $parent_user_ids as $parent_user_id ) {
		$parent_tags = unserialize (lcw_get_contact_tags_by_wp_id( $parent_user_id ));
		$user_tags = array_unique( array_merge( $user_tags, $parent_tags ) );
	}

	if (!empty($user_tags)) {
		foreach ($learndash_course_ids as $ld_id) {
			$course_tags = get_post_meta($ld_id, 'lcw_ld_auto_enrollment_tags', true);
			if (!empty($course_tags)) {
				$should_enroll = true;
				foreach ($course_tags as $tag) {
					if (in_array($tag, $user_tags)) {
						$should_enroll = false;
						break;
					}
				}
				
                $post_type = get_post_type( $ld_id );
                
                if ( $post_type === 'sfwd-courses' ) {
                	ld_update_course_access( $user_id, $ld_id, $should_enroll );
                } elseif ( $post_type === 'groups' ) {
                	ld_update_group_access( $user_id, $ld_id, $should_enroll );
                }
			}
		}
	}

	return;

}

// Turn on post access update
function lcw_turn_on_post_access_update($user_id){

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	if ( empty ($user_id ) ) {

		return ['error' => 'no user ID provided'];
	}

	// Turn on contact sync
	$result = $wpdb->update(
        $table_lcw_contact,
        array(
            'need_to_update_access' => 1
        ),
        array( 'user_id' => $user_id )
    );

    return $result;

}


/***********************************
    Get user restricted posts (with caching)
    @ v: 1.1
***********************************/
function lcw_get_user_restricted_posts($user_id){

	if ( 0 == $user_id || current_user_can('manage_options') ) {
		return;
	}

	$cache_key = 'lcw_user_restricted_posts_' . intval( $user_id );
	$cached = wp_cache_get( $cache_key, 'lcw_user_restricted_posts' );
	if ( false !== $cached ) {
		return $cached;
	}

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	$sql = $wpdb->prepare(
		"SELECT has_not_access_to FROM {$table_lcw_contact} WHERE user_id = %d",
		$user_id
	);

	$result = $wpdb->get_var( $sql );
	wp_cache_set( $cache_key, $result, 'lcw_user_restricted_posts', MINUTE_IN_SECONDS * 2 );

	return $result;
	
}

// Get all has not access IDS
// including login and logout restriction
function lcw_get_has_not_access_ids(){
	static $request_cache = array();

	$user_id = get_current_user_id();
	$cache_key = (string) $user_id;
	if ( isset( $request_cache[ $cache_key ] ) ) {
		return $request_cache[ $cache_key ];
	}

	if (current_user_can('manage_options')) {
		$request_cache[ $cache_key ] = [];
		return $request_cache[ $cache_key ];
	}

	$restricted_posts = hlwpw_get_all_restricted_posts();
    
    $login_restricted_pages = hlwpw_get_all_login_restricted_posts();
    $logged_in_posts = isset( $login_restricted_pages['logged_in'] ) ? $login_restricted_pages['logged_in'] : [];
    $logged_out_posts = isset ( $login_restricted_pages['logged_out'] ) ? $login_restricted_pages['logged_out'] : [];

    $has_not_access =  lcw_get_user_restricted_posts($user_id);
    $has_not_access = ( ! empty( $has_not_access ) ) ? unserialize ( $has_not_access ) : [];
    
    if ( 0 != $user_id ){
        
        $has_not_access = array_merge( $has_not_access, $logged_out_posts );
        
    }else{
        
        $has_not_access = array_merge( $restricted_posts, $logged_in_posts );
   
    }

	$request_cache[ $cache_key ] = $has_not_access;
	return $request_cache[ $cache_key ];

}


// Content protection on loop

/**
 * Check tag conditions between required tags and contact tags
 * 
 * @param array $tags Array of required tags to check
 * @param array $contact_tags Array of contact's tags
 * @param string $condition Optional. Condition to check. Default 'any'.
 *                         Accepts 'any', 'all', 'none', 'not_any'
 * @return bool True if condition is met, false otherwise
 */
function lcw_check_tag_condition( array $tags, array $contact_tags, $condition = 'any' ) {
    if ( empty( $tags ) || empty( $contact_tags ) ) {
        return false;
    }

    $intersection = array_intersect( $tags, $contact_tags );
    
    switch ( $condition ) {
        case 'all':
            // All tags must exist
            return count( $intersection ) === count( $tags );
            
        case 'none':
            // No tags should exist
            return empty( $intersection );
            
        case 'not_any':
            // At least one tag should not exist
            return count( $intersection ) < count( $tags );
            
        case 'any':
        default:
            // At least one tag exists
            return ! empty( $intersection );
    }
}
