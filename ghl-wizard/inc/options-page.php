<?php

	$power_up_link = admin_url('admin.php?page=lcw-power-up');
	$power_up_text = __( "This is a premium feature. please <a href='{$power_up_link}'>power up</a> to avail this.", 'hlwpw' );
	$lcw_enable_chat = get_option( 'lcw_enable_chat', 'disabled' );
	$chat_enabled = ( $lcw_enable_chat != 'disabled' ) ? 'checked' : '';
	$lcw_auth_key = get_option('lcw_auth_key', '');

?>
<div id="hlwpw-options">
	<h1> <?php _e('Set Options', 'hlwpw'); ?> </h1>
	<hr />

	<form id="hlwpw-settings-form" method='POST' action="<?php echo admin_url('admin-post.php'); ?>">

		<?php wp_nonce_field('hlwpw'); ?>

		<input type="hidden" name="action" value="hlwpw_admin_settings">
		<input type="hidden" name="settings_page" value="options">

		<table class="form-table" role="presentation">

			<tbody>

				<tr>
					<th scope="row">
						<label> <?php _e( 'Enable Chat Widget:', 'hlwpw' ); ?> </label>
						<p style="font-weight: 300;"> <?php _e( 'If you want to enable your Chat Widget into WordPress', 'hlwpw' ); ?> </p>
					</th>
					<td>
						<input name="lcw_enable_chat" type="checkbox" id="lcw_enable_chat" value="enabled" <?php esc_html_e( $chat_enabled ); ?> />
						<label for="lcw_enable_chat"> Enable </label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label> <?php _e( 'Enable Content Protection For:', 'hlwpw' ); ?> </label>
						<p style="font-weight: 300;"> <?php _e( 'Which post types do you want to protect', 'hlwpw' ); ?> </p>
					</th>
					<td>
						<?php lcw_display_post_types_for_content_protection_basic(); ?>
						<p> <?php echo $power_up_text; ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label> <?php _e( 'Auto login authentication key:', 'hlwpw' ); ?> </label></p>
						<p style="font-weight: 300;"> <?php _e( 'Set this like a password, make it long and hard to guess. Create a custom value and update it with the same value. Please follow the documentation 10.2 to create the auto login trigger link.', 'hlwpw' ); ?> </p>
					</th>
					<td>
						<input type='text' name='lcw_auth_key' value='<?php echo esc_attr($lcw_auth_key); ?>' pattern="[a-zA-Z0-9]*" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label> <?php _e( 'No access redirect to:', 'hlwpw' ); ?> </label></p>
					</th>
					<td>
						<?php lcw_display_no_access_actions_basic(); ?>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label> <?php _e( 'Trigger will be fired when the WooCommerce order status is:', 'hlwpw' ); ?> </label>
					</th>
					<td>
						<?php hlwpw_get_all_order_statuses(); ?>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label> <?php _e( 'Apply a specific tag when an order is placed:', 'hlwpw' ); ?> </label>
					</th>
					<td>
						<input type="text" placeholder=" - Select a tag - " disabled>
						<p style="font-weight: 300;"> <?php echo $power_up_text; ?> </p>

					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label> <?php _e( 'Select association type:', 'hlwpw' ); ?> </label>
						<p style="font-weight: 300;"> <?php _e( 'This is actually for the Parent - Child realtion. In this case each child can access to their parent\'s items.', 'hlwpw' ); ?> </p>
						<p style="font-weight: 300;"> <?php _e( 'This is a premium feature.', 'hlwpw' ); ?> </p>
					</th>
					<td>
						<?php lcw_display_association_type_free(); ?>
					</td>
				</tr>	

				<tr>
					<th scope="row">
						<label> <?php _e( 'Refresh Location data', 'hlwpw' ); ?> </label>
						<p style="font-weight: 300;"> <?php _e( 'If you need to SYNC location tags, campaigns, workflows, custom values & custom fields with your location, click on this button.', 'hlwpw' ); ?> </p>
					</th>
					<td>
						<?php
							$refresh_url = admin_url( basename( $_SERVER['REQUEST_URI'] ) );
        
					        if( ! strpos( $refresh_url, 'ghl_refresh=1' ) ) {
					            $refresh_url .= '&ghl_refresh=1';
					        }
						?>
						<a class="button" href="<?php echo esc_url($refresh_url); ?>"><?php _e( 'Refresh Now', 'hlwpw' ); ?></a>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label> <?php _e( 'Add all WP users to GHL', 'hlwpw' ); ?> </label>
						<p style="font-weight: 300;"> <?php _e( 'It only sync first name, last name, email & phone(if billing phone is there).', 'hlwpw' ); ?> </p>
					</th>
					<td>
						<?php
							$add_user_to_ghl_url = admin_url( basename( $_SERVER['REQUEST_URI'] ) );
        
					        if( ! strpos( $add_user_to_ghl_url, 'wp_user_to_ghl=1' ) ) {
					            $add_user_to_ghl_url .= '&wp_user_to_ghl=1';
					        }
						?>
						<a class="button" href="#" disabled><?php _e( 'Add WP users to GHL', 'hlwpw' ); ?></a>

						<p style="font-weight: 300;"> <?php echo $power_up_text; ?> </p>
					</td>
				</tr>

			</tbody>
		</table>

		<div>
			<?php submit_button('Update'); ?>
		</div>

	</form>
</div>

<?php

function hlwpw_get_all_order_statuses() {

	if ( class_exists( 'WooCommerce' ) ) {

		$order_statuses = wc_get_order_statuses();
		$hlwpw_order_status = get_option('hlwpw_order_status');
		$selected = !empty($hlwpw_order_status) ? $hlwpw_order_status : 'wc-processing';

		$statuses = "<select name='hlwpw_order_status'>";
		foreach ( $order_statuses as $key => $status ) {

			$selected_status = ( $selected == $key ) ? 'selected' : '';
			$statuses .= "<option value='{$key}' {$selected_status}> {$status} </option>";
		}
		$statuses .= "</select>";

		echo $statuses;

	}else{

		_e( 'WooCommerce is required for this option.', 'hlwpw' );

	}
}


// Display All post types for content protection
function lcw_display_post_types_for_content_protection_basic() {

	$args = array(
		'public' => true,
	);
	$post_types = get_post_types($args);
	$lcw_post_types = get_option('lcw_post_types');
	if ( 'array' != gettype( $lcw_post_types ) ) {
		$lcw_post_types = [];
	}

	unset($post_types['attachment']);
	unset($post_types['page']);

	$post_type_html = "";

	$post_type_html .= "<div> <input type='checkbox' checked disabled> <label> page </label> </div>";

	foreach ($post_types as $post_type) {

		$checked = in_array( $post_type, $lcw_post_types ) ? 'checked' : '';

		$post_type_html .= "<div>";
			$post_type_html .= "<input type='checkbox' value='' {$checked} disabled>";
			$post_type_html .= "<label> {$post_type} </label>";
		$post_type_html .= "</div>";
	}

	echo $post_type_html;
}


// Display no access action
function lcw_display_no_access_actions_basic() {

	$default_no_access_redirect_to = get_option('default_no_access_redirect_to');

	$html = "";

	$html .= "<div>";
		$html .= "<input type='text' name='default_no_access_redirect_to' value='{$default_no_access_redirect_to}' placeholder='/no-access-page/'>";
	$html .= "</div>";

	echo $html;

}


// Display association type
function lcw_display_association_type_free() {

	$lcw_association_id = get_option('lcw_association_id');

	$associations = hlwpw_get_associations();
	$user_defined_associations = array_filter($associations, function($item) {
		return $item->associationType === 'USER_DEFINED';
	});

	// Re-index array
	$user_defined_associations = array_values($user_defined_associations);

	$associations_html = "<option value='0'> - No user defined association - </option>";
	foreach ( $user_defined_associations as $association ) {
		$selected = ( $lcw_association_id == $association->id ) ? 'selected' : '';
		$associations_html .= "<option value='{$association->id}' {$selected}> {$association->key} </option>";
	}

	$html = "";
	
	$html .= "<div>";
		$html .= "<select name='lcw_association_id'>";	
			$html .= $associations_html;
		$html .= "</select>";
	$html .= "</div>";

	echo $html;
}