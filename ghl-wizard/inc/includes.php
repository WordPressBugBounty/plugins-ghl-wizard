<?php
require_once( __DIR__ . '/utility.php');
require_once( __DIR__ . '/settings-page.php');
require_once( __DIR__ . '/product-page-settings.php');
require_once( __DIR__ . '/wp_user.php');
require_once( __DIR__ . '/woo.php');
require_once( __DIR__ . '/metaboxes.php');
require_once( __DIR__ . '/content-protection.php');
require_once( __DIR__ . '/shortcodes.php');
require_once( __DIR__ . '/elementor.php');
require_once( __DIR__ . '/rest-api.php');
require_once( __DIR__ . '/filters.php');

add_action('plugins_loaded', function(){
	if ( defined( 'SURECART_APP_URL' ) ) {
		
		require_once( __DIR__ . '/surecart.php');
	}
	
});