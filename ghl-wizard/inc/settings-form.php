<?php
/**
 * Get location connection status and ID
 */
$hlwpw_location_connected = get_option( 'hlwpw_location_connected', HLWPW_LOCATION_CONNECTED );
$hlwpw_locationId        = lcw_get_location_id();
$redirect_page           = admin_url( 'admin.php?page=bw-hlwpw' );

$connect_url = "https://betterwizard.com/lc-wizard?get_code=1&redirect_page={$redirect_page}";
?>

<div id="bw-hlwpw">
	<h1><?php esc_html_e( 'Connect With The CRM', 'ghl-wizard' ); ?></h1>
	<hr />

	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Connect Your Location', 'ghl-wizard' ); ?></label>
				</th>
				<td>
					<?php if ( $hlwpw_location_connected ) : ?>
						<button class="button disabled"><?php esc_html_e( 'Connected', 'ghl-wizard' ); ?></button>
						<p class="description"><?php esc_html_e( 'Location ID:', 'ghl-wizard' ); ?> <?php echo esc_html( $hlwpw_locationId ); ?></p>
						<a class="button" href="<?php echo esc_url( $connect_url ); ?>"><?php esc_html_e( 'Connect Another Location', 'ghl-wizard' ); ?></a>
						<p class="description"><?php esc_html_e( 'Do it with caution. It may affect your previous data.', 'ghl-wizard' ); ?></p>
					<?php else : ?>
						<a class="button" href="<?php echo esc_url( $connect_url ); ?>"><?php esc_html_e( 'Connect Your Location', 'ghl-wizard' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>