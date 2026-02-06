<?php
if ( ! class_exists( 'BW_HLWPW_Settings_Page' ) ) {
	class BW_HLWPW_Settings_Page {
		
		public function __construct() {
			add_filter( 'plugin_action_links_' . HLWPW_PLUGIN_BASENAME , array( $this , 'add_action_links' ) );

			add_action( 'admin_menu', array( $this, 'add_menu' ) );

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

			add_action( 'admin_menu', [ $this, 'fix_admin_menu' ], 999 );

			add_action( 'admin_head', [ $this, 'disable_admin_notices' ] );
		}

		public function disable_admin_notices() {
			// Get the current screen
			$screen = get_current_screen();
			
			// Check if we're on your specific page
			if ( $screen && $screen->id === 'toplevel_page_connector-wizard-app' ) {
				// Remove all admin notices
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			}
		}

		public function enqueue_admin_scripts( $hook ) {
			$assets_file = plugin_dir_path(LCW_PLUGIN_FILE ) . 'build/index.asset.php';
			if ( ! is_readable( $assets_file ) ) {
				return;
			}

			$script_asset = require $assets_file;

			wp_enqueue_script(
				'lcw-app',
				plugin_dir_url(LCW_PLUGIN_FILE ) . 'build/index.js',
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);

			wp_localize_script(
				'lcw-app',
				'ConnectorWizardApp',
				array(
					'pro'           => lcw_is_pro_active(),
					'version'       => lcw_get_plugin_version(),
					'crm_connected' => lcw_is_crm_connected(),
					'connect_url'   => lcw_get_connect_url(),
					'location'      => lcw_get_location_id()
				)
			);

			wp_enqueue_style(
				'lcw-app',
				plugin_dir_url(LCW_PLUGIN_FILE ) . 'build/index.css',
				['wp-components'],
				$script_asset['version']
			);
		}

		public function fix_admin_menu() {
			global $submenu;

			if ( isset( $submenu['connector-wizard-app'] ) ) {
				foreach ( $submenu['connector-wizard-app'] as $key => $item ) {
					if ( $item[2] === 'connector-wizard-app' ) {
						$submenu['connector-wizard-app'][$key][0] = __( 'Settings', 'hlwpw' );
						$submenu['connector-wizard-app'][$key][2] = 'admin.php?page=connector-wizard-app#/settings';
					}
				}
			}
		}

		public function add_menu() {
			add_menu_page(
				__( 'Lead Connector Wizard', 'hlwpw' ),
				__( 'Connector Wizard', 'hlwpw' ),
				'manage_options',
				'connector-wizard-app',
				static function() {
					echo '<div id="lcw-app-root"></div>';
				},
				plugin_dir_url( __DIR__ ).'images/logo-star-icon.svg',
				4
			);

			add_submenu_page(
				'connector-wizard-app',
				__( 'Memberships', 'hlwpw' ),
				__( 'Memberships', 'hlwpw' ),
				'manage_options',
				'connector-wizard-app#/memberships',
				static function() {
					echo '<div id="lcw-app-root"></div>';
				},
			);

			add_submenu_page(
				'connector-wizard-app',
				__( 'Tools', 'hlwpw' ),
				__( 'Tools', 'hlwpw' ),
				'manage_options',
				'connector-wizard-app#/tools',
				static function() {
					echo '<div id="lcw-app-root"></div>';
				},
			);
			
			add_submenu_page(
				'connector-wizard-app',
				__( 'Support', 'hlwpw' ),
				__( 'Support', 'hlwpw' ),
				'manage_options',
				'connector-wizard-app#/support',
				static function() {
					echo '<div id="lcw-app-root"></div>';
				},
			);
		}

		public function add_action_links( $links ) {
	        $links[] = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=connector-wizard-app' ),
				__( 'Settings' , 'hlwpw' )
			);

			$links[] = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=connector-wizard-app#/memberships' ),
				__( 'Memberships' , 'hlwpw' )
			);

			if ( ! lcw_is_pro_active() ) {
				$links[] = sprintf(
					'<a href="https://connectorwizard.app/#pricing" target="_blank" style="color:#48ce28;font-weight:500">%s</a>',
					__( 'Automate with PRO' , 'hlwpw' )
				);
			}

	        return $links;
	    }

	}

	new BW_HLWPW_Settings_Page();
}
