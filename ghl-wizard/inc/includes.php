<?php
require_once('utility.php');
require_once('settings-page.php');
require_once('product-page-settings.php');
require_once('wp_user.php');
require_once('woo.php');
require_once('metaboxes.php');
require_once('content-protection.php');
require_once('shortcodes.php');

add_action('plugins_loaded', function(){
	if ( defined( 'SURECART_APP_URL' ) ) {
		
		require_once('surecart.php');
	}
	
});