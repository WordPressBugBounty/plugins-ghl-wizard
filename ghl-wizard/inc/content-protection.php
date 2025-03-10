<?php

//content protection settings

// Check if a particular post has accees to the
// Curent logged in user
function hlwpw_has_access( $post_id ){
    
    if ( current_user_can('manage_options') ) {
		return true;
	}

	$has_access = false;
	$location_id = get_option( 'hlwpw_locationId' );
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
		$has_access = is_user_logged_in() ? true : false;
	}elseif( "logged_out" == $login_restriction_value ){
		$has_access = is_user_logged_in() ? false : true;
	}

	// 2. Membership Restriction
	if ( !empty ($membership_restriction_value )) {
		//print_r( $membership_restriction_value );
		//echo $post_id . "<br>";

		// If any_membership is selected?
		if ( in_array( 1, $membership_restriction_value ) ) {

			$memberships_levels = array_keys( get_option( $membership_meta_key, [] ) );
			$has_access = hlwpw_membership_restriction( $memberships_levels );

		}else{

			$has_access = hlwpw_membership_restriction( $membership_restriction_value );
		}

		//var_dump($has_access);
		//echo "<br>";
	}	

	// 3. Tag Restriction
	if ( !empty($tag_restriction_value) && !empty($and_tag_restriction_value) ) {
		
		$tag_restriction 		= hlwpw_contact_has_tag( $tag_restriction_value );
		$and_tag_restriction 	= hlwpw_contact_has_tag( $and_tag_restriction_value );

		if ( $tag_restriction && $and_tag_restriction ) {
			$has_access = true;
		}

	}elseif ( !empty($tag_restriction_value) ) {
		$has_access = hlwpw_contact_has_tag( $tag_restriction_value );
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

function hlwpw_membership_restriction( $memberships ){

	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Provide access to admin
	if ( current_user_can( 'manage_options') ) {
		return true;
	}

	if ( gettype( $memberships ) == 'string' ) {
		
		$memberships = explode( ',', $memberships );

	} elseif ( gettype( $memberships ) != 'array' ) {

		// if the input isn't string or array, it can't be processed
		return false;
	}

	$location_id = get_option( 'hlwpw_locationId' );
	$membership_meta_key = $location_id . "_hlwpw_memberships";
	$memberships_levels = get_option( $membership_meta_key, [] );

	foreach ( $memberships as $membershp ) {
		
		if ( $memberships_levels[$membershp]['membership_name'] == $membershp ) {
			
			$membership_tags_set = $memberships_levels[$membershp]['membership_tag_name'];
			$membership_tag = $membership_tags_set['membership_tag'];
			$_payf_tag = $membership_tags_set['_payf_tag'];
			$_susp_tag = $membership_tags_set['_susp_tag'];
			$_canc_tag = $membership_tags_set['_canc_tag'];

			// Check membership
			if ( hlwpw_contact_has_tag( $membership_tag ) && ! hlwpw_contact_has_tag( [$_payf_tag, $_susp_tag, $_canc_tag ] ) ) {

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

	$location_id = get_option( 'hlwpw_locationId' );
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

function hlwpw_contact_has_tag( $tags ){

	if ( ! is_user_logged_in() ) {
		return false;
	}
	
	// Provide access to admin
	if ( current_user_can( 'manage_options') ) {
		return true;
	}

	if ( gettype( $tags ) == 'string' ) {
		
		$tags = explode( ',', $tags );

	} elseif ( gettype( $tags ) != 'array' ) {

		// if the input isn't string or array, it can't be processed
		return false;
	}

	$locationId = get_option( 'hlwpw_locationId' );
	$meta_key = "ghl_{$locationId}_tags";

	// this is updated @v1.1
	//$contact_tags_value = get_user_meta( get_current_user_id(), $meta_key, true );
	$contact_tags_value = unserialize( lcw_get_contact_tags_by_wp_id ( get_current_user_id() ) ); 

	$contact_tags = ( !empty( $contact_tags_value ) ) ? $contact_tags_value : [];

	//print_r($contact_tags);

	foreach ( $tags as $tag ) {
		if ( in_array( $tag, $contact_tags ) ) {
			return true;
		}
	}

	return false;
}


function hlwpw_no_access_restriction() {

	$post_id = get_queried_object_id();

	if ( ! hlwpw_has_access( $post_id ) ) {

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
// so delete the transient so it will regenerate
add_action('post_updated', function(){
    
	$key_restricted_posts = 'hlwpw_restricted_posts';
	$key_login_restriction = 'hlwpw_login_restricted_posts';
	
	delete_transient($key_restricted_posts);
	delete_transient($key_login_restriction);
	
});



// Keep Track about restricted Pages
// need to use this hook on post updates
function hlwpw_get_all_restricted_posts(){

	// only needs to update when a post is updated
	// or created

	// if ( is_admin() ) {
	// 	return;
	// }

	$key = 'hlwpw_restricted_posts';
	$expiry = 60  * 60 * 24; // 1 day

	$restricted_posts = get_transient($key);

	if ( !empty( $restricted_posts ) ) {
		// delete_transient($key);
		return $restricted_posts;
	}


	$lcw_post_types = get_option('lcw_post_types');
	if ( 'array' != gettype( $lcw_post_types ) ) {
		$lcw_post_types = [];
	}
	$lcw_post_types = array_merge( ['page'], $lcw_post_types );

//var_dump($lcw_post_types);

// meta query doesn't work because of array
// empty array also save serialized string

 	$location_id = get_option( 'hlwpw_locationId' );
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

	
	$all_posts = get_posts(
		array(
		    'fields'			=> 'ids', // Only get post IDs
		    'posts_per_page'	=> -1,
		    'post_type' 		=> $lcw_post_types,
		    'meta_query' 		=> $meta_query,
		)
	);
	wp_reset_postdata();


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

	$key = 'hlwpw_login_restricted_posts';
	$expiry = 60  * 60 * 24; // 1 day

	$login_restricted_posts = get_transient($key);

	if ( !empty( $login_restricted_posts ) ) {
        //delete_transient($key);
		return $login_restricted_posts;
	}

	$lcw_post_types = get_option('lcw_post_types',[]);
	if ( 'array' != gettype( $lcw_post_types ) ) {
		$lcw_post_types = [];
	}
	$lcw_post_types = array_merge( ['page'], $lcw_post_types );

	$all_posts = get_posts(
		array(
		    'fields'			=> 'ids', // Only get post IDs
		    'posts_per_page'	=> -1,
		    'post_type' 		=> $lcw_post_types,
			'meta_query' 		=> array(
				array(
					'key' 	  => 'hlwpw_logged_in_user',
					'compare' => 'EXISTS'
				)
			)
		)
	);
	wp_reset_postdata();

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



// Update restricted posts 
// on user login
// or a shortcode to force update 
// restricted posts list
/***********************************
    Update post restrictions 
    of a user if needed
    @ v: 1.1
***********************************/
function lcw_update_restricted_posts_if_needed(){

	$current_user = wp_get_current_user();	
	$user_id = $current_user->ID;

	if ( 0 == $user_id || is_admin() ) {
		return;
	}

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	$sql = "SELECT need_to_update_access FROM {$table_lcw_contact} WHERE user_id = '{$user_id}'";
	$need_to_update_access = $wpdb->get_var( $sql );

	if ( 1 == $need_to_update_access ) {

		$restricted_posts = hlwpw_get_all_restricted_posts();

		$has_not_access = [];

		foreach ( $restricted_posts as $post_id ) {

			if ( !hlwpw_has_access( $post_id ) ) {
				array_push( $has_not_access, $post_id );
			}
		}

		// Save has_not_access posts to database
		$result = $wpdb->update(
	        $table_lcw_contact,
	        array(
	            'has_not_access_to' => serialize( $has_not_access ),
	            'updated_on' => current_time( 'mysql' ),
	            'need_to_update_access' => 0
	        ),
	        array( 'user_id' => $user_id )
	    );

		return $result;
	}
	
}
// Add it to woocommerce_thankyou hook - DONE
// and create a workflow for add/remove tag and implement that.

add_action( 'init', 'lcw_update_restricted_posts_if_needed' );



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
    Get user restricted posts
    @ v: 1.1
***********************************/
function lcw_get_user_restricted_posts($user_id){

	if ( 0 == $user_id || current_user_can('manage_options') ) {
		return;
	}

	global $table_prefix, $wpdb;
	$table_lcw_contact = $table_prefix . 'lcw_contacts';

	$sql = "SELECT has_not_access_to FROM {$table_lcw_contact} WHERE user_id = '{$user_id}'";
	return $wpdb->get_var( $sql );
	
}

// Get all has not access IDS
// including login and logout restriction
function lcw_get_has_not_access_ids(){

	if (current_user_can('manage_options')) {
		return [];
	}

	$user_id = get_current_user_id();
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

	return $has_not_access;

}


// Hide Menu based on _access
function sa_hide_open_login_logout_menu_item( $items, $menu, $args ) {

	$has_not_access = lcw_get_has_not_access_ids();
   
    foreach ( $items as $key => $item ){
        
        $post_id = $item->object_id;
        
        if ( in_array( $post_id, $has_not_access ) ){
            
            unset( $items[$key] );
            
        }
        
    }

    return $items;
}
add_filter( 'wp_get_nav_menu_items', 'sa_hide_open_login_logout_menu_item', 10, 3 );


// Content protection on loop