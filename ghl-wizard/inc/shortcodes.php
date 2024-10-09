<?php

/**********************************************
    Shortcodes to display Custom values
    @ updated in v: 1.1
**********************************************/

function lcw_display_custom_value( $atts ) {

	// Attributes
	$atts = shortcode_atts(
		array(
			'key' => ''
		),
		$atts,
		'lcw_custom_value'
	);

	$key = $atts['key'];

	if ( !empty( $key ) ) {

		$custom_values = hlwpw_get_location_custom_values();

		if ( isset( $custom_values[$key] ) ) {

			return $custom_values[$key];

		}else{

			return "<p class='hlwpw-warning'>Check the 'key' - ({$key}) is correct or refresh data on option tab.</p>";

		}

	}else{

		return "<p class='hlwpw-warning'>Custom value 'key' shouldn't be empty.</p>";

	}

}
add_shortcode( 'lcw_custom_value', 'lcw_display_custom_value' );



/**********************************************
    Force to sync contact
    @ v: 1.1
**********************************************/
function lcw_force_to_sync_contact(){

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
		lcw_turn_on_contact_sync($user_id);
	}
	
	return null;

}
add_shortcode( 'lcw_contact_sync', 'lcw_force_to_sync_contact' );



/**********************************************
    This is depricated
    will delete in next version
    @ depricated from v: 1.1
**********************************************/
// Shortcodes to display Custom values
function hlwpw_display_custom_value( $atts ) {

	// Attributes
	$atts = shortcode_atts(
		array(
			'key' => ''
		),
		$atts,
		'gw_custom_value'
	);

	$key = $atts['key'];

	if ( !empty( $key ) ) {

		$custom_values = hlwpw_get_location_custom_values();

		if ( isset( $custom_values[$key] ) ) {

			return $custom_values[$key];

		}else{

			return "<p class='hlwpw-warning'>Check the 'key' - ({$key}) is correct or refresh data on option tab.</p>";

		}

	}else{

		return "<p class='hlwpw-warning'>Custom value 'key' shouldn't be empty.</p>";

	}

}
add_shortcode( 'gw_custom_value', 'hlwpw_display_custom_value' );