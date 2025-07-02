<?php
/**
 * @wordpress-plugin
 * Plugin Name:       LC Wizard
 * Plugin URI:        https://betterwizard.com/lead-connector-wizard/
 * Description:       Connect WordPress with the popular LeadConnector CRM(HighLevel) and combine the power of automation and excellent user experience. Including memberships, content protection, WooCommerce automation, custom fields & many more...
 * Version:           1.2.19
 * Author:            Better Wizard
 * Author URI:        https://betterwizard.com/
 * License:           GPLv2 or later
 * Text Domain:       ghl-wizard
 */

if ( ! defined( 'ABSPATH' )) {
    exit();
}


/***********************************
    Create Tables
    @ v: 1.1
***********************************/
register_activation_hook( __FILE__, "lcw_activation_hook" );

if ( ! function_exists( 'lcw_activation_hook' ) ) {
    function lcw_activation_hook() {

        // create db table
        lcw_create_location_and_contact_table();

    }
}


/***********************************
    Default Values
***********************************/
define( 'HLWPW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'HLWPW_LOCATION_CONNECTED', false );
define( 'LCW_PLUGIN_VERSION', 1.1 );

/***********************************
    Load JS & CSS Files
***********************************/
if ( ! function_exists( 'hlwpw_style_and_scripts' ) ) {
    function hlwpw_style_and_scripts() {

        wp_enqueue_style( 'hlwpw_style', plugins_url( '/css/styles.css', __FILE__ ), '', '1.2.15' );
        wp_enqueue_script( 'hlwpw_script', plugins_url( '/js/scripts.js', __FILE__ ) , array('jquery'), '1.2.19', true);
        wp_localize_script( 'hlwpw_script', 'hlwpw_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

    }
    add_action( 'wp_enqueue_scripts', 'hlwpw_style_and_scripts' );
}

/***********************************
    Load JS & CSS for admin screen
***********************************/
if ( ! function_exists( 'hlwpw_admin_style_and_scripts' ) ) {
    function hlwpw_admin_style_and_scripts() {

        wp_enqueue_script( 'select2', plugins_url( '/js/select2.min.js', __FILE__ ) , array('jquery'), '1.0.0', true );
        wp_enqueue_script( 'hlwpw_admin_script', plugins_url( '/js/admin-scripts.js', __FILE__ ) , array('jquery'), '1.2.14', true );
        wp_enqueue_style( 'select2_css', plugins_url( '/css/select2.min.css', __FILE__ ), '', '1.0' );
        wp_enqueue_style( 'hlwpw_admin_style', plugins_url( '/css/admin-styles.css', __FILE__ ), '', '1.0' );
    }
    add_action( 'admin_enqueue_scripts', 'hlwpw_admin_style_and_scripts' );
}


/***********************************
    Admin Notice
***********************************/

add_action( 'admin_notices', function(){

    $hlwpw_location_connected   = get_option( 'hlwpw_location_connected', HLWPW_LOCATION_CONNECTED );

    if ( $hlwpw_location_connected == 1 ) {
        return;
    }

    $class = 'notice notice-error';
    $url = admin_url('admin.php?page=bw-hlwpw');
    $link_text = __('Connect Here','hlwpw');
    $message = __( 'Your WordPress isn\'t connected with the CRM. You must connect it to make "LC Wizard" plugin work.', 'hlwpw' );

    printf( '<div class="%1$s"><p>%2$s <a href="%3$s"> %4$s </a></p></div>', esc_attr( $class ), esc_html( $message ), $url, esc_html($link_text) );

});


/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_ghl_wizard() {

    if ( ! class_exists( 'Appsero\Client' ) ) {
      require_once __DIR__ . '/lib/client/src/Client.php';
    }

    $client = new Appsero\Client( '72d4c258-70c6-4454-84a7-67dd3938836b', 'LeadConnector Wizard', __FILE__ );

    // Active insights
    $opt_in_message = "Allow <strong>LC Wizard</strong> to collect diagnostic data to enhance your journey. We never collect any sensitive data. Allow now for a smoother, personalized experience!";
    $client->insights()
            ->notice( $opt_in_message )
            ->init();

}
appsero_init_tracker_ghl_wizard();


/***********************************
    Required Files
***********************************/

require_once( plugin_dir_path( __FILE__ ) . 'api/apis.php' );
require_once( plugin_dir_path( __FILE__ ) . 'inc/includes.php' );