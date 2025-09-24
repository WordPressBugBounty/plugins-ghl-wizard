<?php
if ( ! class_exists( 'BW_HLWPW_Settings_Page' ) ) {
	class BW_HLWPW_Settings_Page {
		
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'lcw_create_page' ) );
			add_action( 'admin_menu', array( $this, 'lcw_create_option_page' ) );
			add_action( 'admin_menu', array( $this, 'lcw_create_membership_page' ) );
			add_action( 'admin_menu', array( $this, 'lcw_create_power_up_page' ) );
			add_action( 'admin_menu', array( $this, 'lcw_create_support_page' ) );
			add_action( 'admin_post_hlwpw_admin_settings', array( $this, 'hlwpw_save_settings' ) );
			add_filter( 'plugin_action_links_' . HLWPW_PLUGIN_BASENAME , array( $this , 'hlwpw_add_settings_link' ) );
		}

		public function lcw_create_page() {
	    
			$page_title 	= __( 'Lead Connector Wizard', 'hlwpw' );
			$menu_title 	= __( 'Connector Wizard', 'hlwpw' );
			$capability 	= 'manage_options';
			$menu_slug 		= 'bw-hlwpw';
			$callback   	= array( $this, 'hlwpw_page_content' );
			$icon_url   	= plugin_dir_url( __DIR__ ).'images/logo-star-icon.svg';

			add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url, 4 );
		}

		public function lcw_create_membership_page() {
	    	
	    	$parent_slug 	= 'bw-hlwpw';
			$page_title 	= __( 'Membership', 'hlwpw' );
			$menu_title 	= __( 'Membership', 'hlwpw' );
			$capability 	= 'manage_options';
			$menu_slug 		= 'lcw-membership';
			$callback   	= array( $this, 'hlwpw_membership_page_content' );
			
			add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback );
		}

		public function lcw_create_option_page() {
	    	
	    	$parent_slug 	= 'bw-hlwpw';
			$page_title 	= __( 'Options', 'hlwpw' );
			$menu_title 	= __( 'Options', 'hlwpw' );
			$capability 	= 'manage_options';
			$menu_slug 		= 'lcw-options';
			$callback   	= array( $this, 'hlwpw_option_page_content' );
			
			add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback );
		}

		public function lcw_create_power_up_page() {
	    	
	    	$parent_slug 	= 'bw-hlwpw';
			$page_title 	= __( 'power-up', 'hlwpw' );
			$menu_title 	= __( 'power-up', 'hlwpw' );
			$capability 	= 'manage_options';
			$menu_slug 		= 'lcw-power-up';
			$callback   	= array( $this, 'hlwpw_power_up_page_content' );
			
			add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback );
		}

		public function lcw_create_support_page() {
	    	
	    	$parent_slug 	= 'bw-hlwpw';
			$page_title 	= __( 'Support', 'hlwpw' );
			$menu_title 	= __( 'Support', 'hlwpw' );
			$capability 	= 'manage_options';
			$menu_slug 		= 'support';
			$callback   	= array( $this, 'hlwpw_support_page_content' );
			
			add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback );
		}

		public function hlwpw_membership_page_content() {
	    	require_once plugin_dir_path( __FILE__ )."/membership-page.php";
		}

		public function hlwpw_option_page_content() {
	    	require_once plugin_dir_path( __FILE__ )."/options-page.php";
		}

		public function hlwpw_power_up_page_content() {
	    	require_once plugin_dir_path( __FILE__ )."/power-up.php";
		}

		public function hlwpw_support_page_content() {
	    	require_once plugin_dir_path( __FILE__ )."/support-page.php";
		}

		public function hlwpw_page_content() {
			require_once plugin_dir_path( __FILE__ )."/settings-form.php";	
		}

		public function hlwpw_save_settings() {

			check_admin_referer( "hlwpw" );

	        // Connection Page
	        $hlwpw_client_id 		= sanitize_text_field( $_POST['hlwpw_client_id'] );
	        $hlwpw_client_secret 	= sanitize_text_field( $_POST['hlwpw_client_secret'] );

	        // Options Page
	        $lcw_enable_chat 		= isset( $_POST['lcw_enable_chat'] ) ? 'enabled' : 'disabled';
	        $lcw_enable_invoice 	= isset( $_POST['lcw_enable_invoice'] ) ? 'enabled' : 'disabled';
			
	        $hlwpw_order_status 	= sanitize_text_field( $_POST['hlwpw_order_status'] );
	        $lcw_default_order_tag 	= sanitize_text_field( $_POST['lcw_default_order_tag'] );
	        $lcw_post_types 		= hlwpw_recursive_sanitize_array( $_POST['lcw_post_types'] );
			$lcw_auth_key 			= sanitize_text_field( $_POST['lcw_auth_key'] );
			$lcw_association_id 	= sanitize_text_field( $_POST['lcw_association_id'] );
			
	        //$default_no_access_action 	= sanitize_text_field( $_POST['default_no_access_action'] );
	        $default_no_access_redirect_to 	= sanitize_url( $_POST['default_no_access_redirect_to'] );
	        
	        $settings_page 	= sanitize_text_field( $_POST['settings_page'] );

	        $referer = sanitize_url( $_POST['_wp_http_referer']);

	        // Save data
	        // Connection Page	        
	        if ( 'connection' == $settings_page ) {
	        	update_option( 'hlwpw_client_id', $hlwpw_client_id );
	        	update_option( 'hlwpw_client_secret', $hlwpw_client_secret );
	        }

	        if ( 'options' == $settings_page ) {
	        	update_option( 'lcw_enable_chat', $lcw_enable_chat );
	        	update_option( 'lcw_enable_invoice', $lcw_enable_invoice );
	        	update_option( 'hlwpw_order_status', $hlwpw_order_status );
	        	update_option( 'lcw_post_types', $lcw_post_types );
	        	update_option( 'lcw_default_order_tag', $lcw_default_order_tag );
	        	update_option( 'default_no_access_redirect_to', $default_no_access_redirect_to );
	        	update_option( 'lcw_auth_key', $lcw_auth_key );
	        	update_option( 'lcw_association_id', $lcw_association_id );
	        	//update_option( 'default_no_access_action', $default_no_access_action );
	        }

			wp_redirect( $referer );
        	exit();

		}

		public function hlwpw_add_settings_link( $links ) {
	        $newlink = sprintf( "<a href='%s'>%s</a>" , admin_url( 'admin.php?page=bw-hlwpw' ) , __( 'Settings' , 'hlwpw' ) );
	        $links[] = $newlink;
	        return $links;
	    }

	}
	new BW_HLWPW_Settings_Page();
}