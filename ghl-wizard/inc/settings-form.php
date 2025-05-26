<?php

	if ( isset( $_GET['get_auth'] ) && $_GET['get_auth'] == 'success' ) {

		$hlwpw_access_token 	= sanitize_text_field( $_GET['atn'] );
		$hlwpw_refresh_token 	= sanitize_text_field( $_GET['rtn'] );
		$hlwpw_locationId 		= sanitize_text_field( $_GET['lid'] );
		$hlwpw_client_id 		= sanitize_text_field( $_GET['cid'] );
		$hlwpw_client_secret 	= sanitize_text_field( $_GET['cst'] );

		// Save data
	    update_option( 'hlwpw_access_token', $hlwpw_access_token );
	    update_option( 'hlwpw_refresh_token', $hlwpw_refresh_token );
	    update_option( 'hlwpw_locationId', $hlwpw_locationId );
	    update_option( 'hlwpw_client_id', $hlwpw_client_id );
	    update_option( 'hlwpw_client_secret', $hlwpw_client_secret );
	    update_option( 'hlwpw_location_connected', 1 );

	    // delete old transient (if exists any)
	    delete_transient('hlwpw_location_tags');
	    delete_transient('hlwpw_location_campaigns');
	    delete_transient('hlwpw_location_wokflow');
	    delete_transient('hlwpw_location_custom_values');
	    delete_transient('lcw_location_cutom_fields');

	    wp_redirect('admin.php?page=bw-hlwpw');
	    exit();

	    // Need to update on Database
	    // on next version
	}



	$hlwpw_location_connected	= get_option( 'hlwpw_location_connected', HLWPW_LOCATION_CONNECTED );
	$hlwpw_client_id 			= get_option( 'hlwpw_client_id' );
	$hlwpw_client_secret 		= get_option( 'hlwpw_client_secret' );
	$hlwpw_locationId 			= get_option( 'hlwpw_locationId' );
	$redirect_page 				= get_site_url(null, '/wp-admin/admin.php?page=bw-hlwpw');
	$redirect_uri 				= get_site_url();

	$auth_end_point = 'https://marketplace.leadconnectorhq.com/oauth/chooselocation';
	$token_endpoint = 'https://services.leadconnectorhq.com/oauth/token';
	$scopes = "payments/orders.write payments/integration.readonly payments/integration.write calendars.readonly calendars.write calendars/events.readonly calendars/events.write calendars/groups.readonly calendars/groups.write calendars/resources.readonly calendars/resources.write campaigns.readonly conversations.readonly conversations.write conversations/message.readonly conversations/message.write conversations/reports.readonly contacts.readonly contacts.write courses.write courses.readonly forms.readonly forms.write invoices.readonly invoices.write invoices/schedule.readonly invoices/schedule.write invoices/template.readonly invoices/template.write links.readonly lc-email.readonly links.write locations.readonly locations/customValues.readonly locations/customValues.write locations/customFields.readonly locations/customFields.write locations/tasks.readonly locations/tasks.write locations/tags.readonly locations/tags.write locations/templates.readonly medias.readonly medias.write funnels/redirect.readonly funnels/redirect.write opportunities.readonly opportunities.write payments/orders.readonly payments/transactions.readonly payments/subscriptions.readonly products.readonly products.write products/prices.readonly products/prices.write surveys.readonly users.readonly users.write workflows.readonly";

	$connect_url = "https://betterwizard.com/lc-wizard?get_code=1&redirect_page={$redirect_page}";

	if ( ! empty( $hlwpw_client_id ) && ! ( str_contains( $hlwpw_client_id, 'lvtkmmp9' ) OR str_contains( $hlwpw_client_id, 'l73d3ee1' ) )  ) {
		
		$connect_url = urldecode( $auth_end_point . "?response_type=code&redirect_uri={$redirect_uri}&client_id={$hlwpw_client_id}&scope={$scopes}");
	}
?>

<div id="bw-hlwpw">
	<h1> <?php _e('Connect With The CRM', 'hlwpw'); ?> </h1>
	<hr />

	<form id="hlwpw-settings-form" method='POST' action="<?php echo admin_url('admin-post.php'); ?>">

		<?php wp_nonce_field('hlwpw'); ?>

		<input type="hidden" name="action" value="hlwpw_admin_settings">
		<input type="hidden" name="settings_page" value="connection">

		<table class="form-table" role="presentation">

			<tbody>

				<tr>
					<th scope="row">
						<label> <?php _e( 'Client ID (Optional)', 'hlwpw' ); ?> </label>
					</th>
					<td>
						<input type="password" name="hlwpw_client_id"placeholder='optional' value="<?php esc_html_e( $hlwpw_client_id ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label> <?php _e( 'Client Secret (Optional)', 'hlwpw' ); ?> </label>
					</th>
					<td>
						<input type="password" name="hlwpw_client_secret" placeholder='optional' value="<?php esc_html_e( $hlwpw_client_secret ); ?>">
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label> <?php _e( 'APP Redirect URI', 'hlwpw' ); ?> </label>
					</th>
					<td>
						<?php echo esc_url( $redirect_uri ); ?>			
						<p class="description">If you use your own 'cliend id' and 'client secret' set this url as the Redirect URL of your APP.</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label> <?php _e( 'Connect Your Location', 'hlwpw' ); ?> </label>
					</th>
					<td>
						<?php if( $hlwpw_location_connected ){ ?>
							<button class="button disabled">Connected</button>
							<p class="description">Location ID: <?php echo esc_html( $hlwpw_locationId ); ?></p>
							<a class="button" href="<?php echo esc_url( $connect_url ); ?>">Connect Another Location</a>
							<p class="description">Do it with caution. It may affect your previous data.</p>
						<?php } else { ?>

							<a class="button" href="<?php echo esc_url( $connect_url ); ?>">Connect Your Location</a>
							<p>If you use your own client_id and & client_secret, Please save the value first then click connect.</p>
						<?php } ?>
					</td>
				</tr>

			</tbody>
		</table>

		<div>
			<?php submit_button('Update'); ?>
		</div>

	</form>
</div>