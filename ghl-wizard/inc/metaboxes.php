<?php

if ( ! class_exists( 'LCW_content_protection_Metaboxes' ) ) {
	class LCW_content_protection_Metaboxes {

		private $location_id;

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'bw_add_metabox' ) );
			add_action( 'save_post', array( $this, 'bw_save_metabox' ) );
			$this->location_id = get_option( 'hlwpw_locationId' );
		}

		private function is_secured( $nonce_field, $action, $post_id ) {
			$nonce = isset( $_POST[ $nonce_field ] ) ? $_POST[ $nonce_field ] : '';

			if ( $nonce == '' ) {
				return false;
			}
			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				return false;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return false;
			}

			if ( wp_is_post_autosave( $post_id ) ) {
				return false;
			}

			if ( wp_is_post_revision( $post_id ) ) {
				return false;
			}

			return true;

		}

		function bw_add_metabox() {

			$lcw_post_types = get_option('lcw_post_types');

			if ( 'array' != gettype( $lcw_post_types ) ) {
				$lcw_post_types = [];
			}
			$lcw_post_types = array_merge( ['page','post'], $lcw_post_types );

			add_meta_box(
				'hlwpw_content_protection',
				__( 'LCW Content Protection', 'hlwpw' ),
				array( $this, 'hlwpw_content_protection_metabox' ),
				// array('post','page','sfwd-courses'),
				$lcw_post_types,
				'side',
				'high'
			);
		}

		function bw_save_metabox( $post_id ) {

			if ( ! $this->is_secured( 'hlwpw_protection_settings_field', 'hlwpw_protection_settings', $post_id ) ) {
				return $post_id;
			}

			$membership_meta_key = $this->location_id . "_hlwpw_memberships";

			$hlwpw_logged_in_user = isset( $_POST['hlwpw_logged_in_user'] ) ? sanitize_text_field ( $_POST['hlwpw_logged_in_user'] ) : null;
			$hlwpw_memberships = isset( $_POST['hlwpw_memberships'] ) ? hlwpw_recursive_sanitize_array ( $_POST['hlwpw_memberships'] ) : null;
			$tags = isset( $_POST['hlwpw_required_tags'] ) ? hlwpw_recursive_sanitize_array ( $_POST['hlwpw_required_tags'] ) : null;
			$and_tags = isset( $_POST['hlwpw_and_required_tags'] ) ? hlwpw_recursive_sanitize_array ( $_POST['hlwpw_and_required_tags'] ) : null;
			$ld_auto_enrollment_tags = isset( $_POST['lcw_ld_auto_enrollment_tags'] ) ? hlwpw_recursive_sanitize_array ( $_POST['lcw_ld_auto_enrollment_tags'] ) : null;
			// $no_access_action = isset( $_POST['hlwpw_no_access_action'] ) ? sanitize_text_field ( $_POST['hlwpw_no_access_action'] ) : null;
			$hlwpw_no_access_redirect_to = isset( $_POST['hlwpw_no_access_redirect_to'] ) ? sanitize_url ( $_POST['hlwpw_no_access_redirect_to'] ) : null;

			( !empty( $hlwpw_logged_in_user ) ) ? update_post_meta( $post_id, 'hlwpw_logged_in_user', $hlwpw_logged_in_user ) : delete_post_meta( $post_id, 'hlwpw_logged_in_user' );
			( !empty( $hlwpw_memberships ) ) ? update_post_meta( $post_id, $membership_meta_key, $hlwpw_memberships ) : delete_post_meta( $post_id, $membership_meta_key );
			( !empty( $tags ) ) ? update_post_meta( $post_id, 'hlwpw_required_tags', $tags ) : delete_post_meta( $post_id, 'hlwpw_required_tags' );
			( !empty( $and_tags ) ) ? update_post_meta( $post_id, 'hlwpw_and_required_tags', $and_tags ) : delete_post_meta( $post_id, 'hlwpw_and_required_tags' );
			( !empty( $ld_auto_enrollment_tags ) ) ? update_post_meta( $post_id, 'lcw_ld_auto_enrollment_tags', $ld_auto_enrollment_tags ) : delete_post_meta( $post_id, 'lcw_ld_auto_enrollment_tags' );
			// ( !empty( $no_access_action ) ) ? update_post_meta( $post_id, 'hlwpw_no_access_action', $no_access_action ) : delete_post_meta( $post_id, 'hlwpw_no_access_action' );
			( !empty( $hlwpw_no_access_redirect_to ) ) ? update_post_meta( $post_id, 'hlwpw_no_access_redirect_to', $hlwpw_no_access_redirect_to ) : delete_post_meta( $post_id, 'hlwpw_no_access_redirect_to' );

		}

		function hlwpw_content_protection_metabox( $post ) {

			$post_id = $post->ID;
			$membership_meta_key = $this->location_id . "_hlwpw_memberships";			
			$memberships = get_option( $membership_meta_key, [] );

			$label_logged_in_user 				= __( 'Who can see this page', 'hlwpw' );
			$hlwpw_no_login_restriction			= __( 'No Login Restriction', 'hlwpw' );
			$hlwpw_logged_in_user_logged_in		= __( 'Only Logged in User', 'hlwpw' );
			$hlwpw_logged_in_user_logged_out 	= __( 'Only Logged Out User', 'hlwpw' );

			$label_membership_protection 	= __( 'Membership Protection', 'hlwpw' );
			$label_any_membership 			= __( 'Any Membership', 'hlwpw' );
			$label_no_membership 			= __( 'You haven\'t any membership yet.', 'hlwpw' );

			$label_required_tags 		= __( 'Required Tags', 'hlwpw' );
			$label_and_required_tags 	= __( 'And Required Tags', 'hlwpw' );
			$label_no_access_action 	= __( 'No access redirect to:', 'hlwpw' );
			
			// $label_no_access_default 	= __( 'Default', 'hlwpw' );
			// $label_no_access_hide 		= __( 'Hide', 'hlwpw' );
			// $label_no_access_excerpt 	= __( 'Show Excerpt', 'hlwpw' );
			// $label_no_access_redirect 	= __( 'Redirect to', 'hlwpw' );



			wp_nonce_field( 'hlwpw_protection_settings', 'hlwpw_protection_settings_field' );

			$hlwpw_logged_in_user = get_post_meta( $post_id, 'hlwpw_logged_in_user', true );
			$selected_memberships = get_post_meta($post_id, $membership_meta_key, true);
			$all_required_tags = hlwpw_get_tag_options($post_id, 'hlwpw_required_tags');
			$all_and_required_tags = hlwpw_get_tag_options($post_id, 'hlwpw_and_required_tags');
			$no_access_action = get_post_meta( $post_id, 'hlwpw_no_access_action', true );
			$hlwpw_no_access_redirect_to = esc_url( get_post_meta( $post_id, 'hlwpw_no_access_redirect_to', true ) );

			$selected_memberships = ( empty($selected_memberships) ) ? [] : $selected_memberships;
			$no_access_default = empty( $no_access_action ) ? 'checked' : '';
			$no_access_hide = ( 'hide' == $no_access_action ) ? 'checked' : '';
			$no_access_excerpt = ( 'excerpt' == $no_access_action ) ? 'checked' : '';
			$no_access_redirect = ( 'redirect' == $no_access_action ) ? 'checked' : '';
			$hlwpw_no_login_restriction_check = ( '' == $hlwpw_logged_in_user ) ? 'checked' : '';
			$hlwpw_logged_in_user_logged_in_check = ( 'logged_in' == $hlwpw_logged_in_user ) ? 'checked' : '';
			$hlwpw_logged_in_user_logged_out_check = ( 'logged_out' == $hlwpw_logged_in_user ) ? 'checked' : '';
			$any_membership_check = (in_array( 1, $selected_memberships )) ? 'checked' : '';

			$metabox_html = "";

			// Require login in
			$metabox_html .= "<p>";
				$metabox_html .= "<label>";
				$metabox_html .= $label_logged_in_user;
				$metabox_html .= "</label>";

				$metabox_html .= "<span class='no-access-action-value'>";
					$metabox_html .= "<input id='hlwpw_no_login_restriction' type='radio' name='hlwpw_logged_in_user' value='' {$hlwpw_no_login_restriction_check}>";
					$metabox_html .= "<label for='hlwpw_no_login_restriction'>";
					$metabox_html .= $hlwpw_no_login_restriction;
					$metabox_html .= "</label>";
				$metabox_html .= "</span>";

				$metabox_html .= "<span class='no-access-action-value'>";
					$metabox_html .= "<input id='hlwpw_logged_in_user_logged_in' type='radio' name='hlwpw_logged_in_user' value='logged_in' {$hlwpw_logged_in_user_logged_in_check}>";
					$metabox_html .= "<label for='hlwpw_logged_in_user_logged_in'>";
					$metabox_html .= $hlwpw_logged_in_user_logged_in;
					$metabox_html .= "</label>";
				$metabox_html .= "</span>";

				$metabox_html .= "<span class='no-access-action-value'>";
					$metabox_html .= "<input id='hlwpw_logged_in_user_logged_out' type='radio' name='hlwpw_logged_in_user' value='logged_out' {$hlwpw_logged_in_user_logged_out_check}>";
					$metabox_html .= "<label for='hlwpw_logged_in_user_logged_out'>";
					$metabox_html .= $hlwpw_logged_in_user_logged_out;
					$metabox_html .= "</label>";
				$metabox_html .= "</span>";
			$metabox_html .= "</p>";
			//$metabox_html .= "<p> Login condition will be applied if membership and tag conditions are empty.</p>";	

			$metabox_html .= "<hr />";

			// Membershp
			$metabox_html .= "<div>";
				$metabox_html .= "<p>";
					$metabox_html .= "<label>";
					$metabox_html .= $label_membership_protection;
					$metabox_html .= "</label>";
				$metabox_html .= "</p>";

				$metabox_html .= "<p>";

					if ( !empty( $memberships ) ) {

						$metabox_html .= "<label>";
						$metabox_html .= "<input type='checkbox' name='hlwpw_memberships[]' value='1' {$any_membership_check}>";
						$metabox_html .= $label_any_membership;
						$metabox_html .= "</label>";

					}else{

						$metabox_html .= "<label>";
						$metabox_html .= "<input type='checkbox'>";
						$metabox_html .= $label_no_membership;
						$metabox_html .= "</label>";

					}

					foreach ($memberships as $key => $membership) {

						$membership_name = $membership['membership_name'];
						$checked = '';
						$checked = (in_array( $membership_name, $selected_memberships )) ? 'checked' : '';

						$metabox_html .= "<label>";
						$metabox_html .= "<input type='checkbox' name='hlwpw_memberships[]' value='{$membership_name}' {$checked}>";
						$metabox_html .= $membership_name;
						$metabox_html .= "</label>";
					}

				$metabox_html .= "</p>";

			$metabox_html .= "</div>";

			$metabox_html .= "<hr />";

			// Metabox for required tags
			$metabox_html .= "<p>";
				$metabox_html .= "<label for='hlwpw_required_tags'>";
				$metabox_html .= $label_required_tags;
				$metabox_html .= "</label>";

				$metabox_html .= "<select name='hlwpw_required_tags[]' id='hlwpw-required-tag-box' multiple='multiple' style='width: 100%'>";
				$metabox_html .= $all_required_tags;
				$metabox_html .= "</select>";
			$metabox_html .= "</p>";

			// Metabox for AND required tags
			$metabox_html .= "<p>";
				$metabox_html .= "<label for='hlwpw_and_required_tags'>";
				$metabox_html .= $label_and_required_tags;
				$metabox_html .= "</label>";

				$metabox_html .= "<select name='hlwpw_and_required_tags[]' id='hlwpw-and-required-tag-box' multiple='multiple' style='width: 100%'>";
				$metabox_html .= $all_and_required_tags;
				$metabox_html .= "</select>";
			$metabox_html .= "</p>";

			$metabox_html .= "<hr />";

			// LearnDash auto enrollment tags
			if ( get_post_type() == 'sfwd-courses' ) {
				$metabox_html .= "<p>";
					$metabox_html .= "<label for='lcw_ld_auto_enrollment_tags'>";
					$metabox_html .= __( 'LearnDash auto enrollment tags', 'hlwpw' );
					$metabox_html .= "</label>";

					$metabox_html .= "<select name='lcw_ld_auto_enrollment_tags[]' id='lcw-ld-auto-enrollment-tags' multiple='multiple' style='width: 100%'>";
					$metabox_html .= hlwpw_get_tag_options($post_id, 'lcw_ld_auto_enrollment_tags');
					$metabox_html .= "</select>";
					$metabox_html .= "<span class='description'> " . __( 'If a user has access to a course, they will be automatically enrolled. To enroll them regardless of access, please use this option.', 'hlwpw' ) . "</span>";
				$metabox_html .= "</p>";
				$metabox_html .= "<hr />";
			}

			// No access action
			$metabox_html .= "<p>";
				$metabox_html .= "<label>";
				$metabox_html .= $label_no_access_action;
				$metabox_html .= "</label>";

				$metabox_html .= "<input type='url' placeholder='https://...' value='{$hlwpw_no_access_redirect_to}' name='hlwpw_no_access_redirect_to' style='width: 100%'>";
				$metabox_html .= "</span>";

			$metabox_html .= "</p>";

			echo $metabox_html;
		}
	}

	new LCW_content_protection_Metaboxes();
}
