<?php

$location_id = get_option( 'hlwpw_locationId' );
$membership_meta_key = $location_id . "_hlwpw_memberships";
$memberships = get_option( $membership_meta_key, [] );

// Create Membership
if ( isset( $_POST['hlwpw_create_membership'] ) && 'yes' == $_POST['hlwpw_create_membership'] && !empty( $_POST['hlwpw_membership_name'] ) ) {

	hlwpw_create_membership();

}

function hlwpw_create_membership(){

	//global $memberships;
	//global $membership_meta_key;

	$location_id = get_option( 'hlwpw_locationId' );
	$membership_meta_key = $location_id . "_hlwpw_memberships";
	$memberships = get_option( $membership_meta_key, [] );

	$hlwpw_membership_name 			= sanitize_text_field( strtolower( $_POST['hlwpw_membership_name'] ) );
	$hlwpw_tag_type 				= sanitize_text_field( strtolower( $_POST['hlwpw_tag_type'] ) );
	$hlwpw_selected_new_tag 		= sanitize_text_field( strtolower( $_POST['hlwpw_selected_new_tag'] ) );
	$hlwpw_selected_existing_tag	= sanitize_text_field( strtolower( $_POST['hlwpw_selected_existing_tag'] ) );
	$hlwpw_membership_level 		= sanitize_text_field( strtolower( $_POST['hlwpw_membership_level'] ) );

	if( "tag_new" == $hlwpw_tag_type && !empty( $hlwpw_selected_new_tag ) ){

		$membership_tags_set = array(
			'membership_tag' 	=> $hlwpw_selected_new_tag,
			'_payf_tag' 		=> $hlwpw_selected_new_tag . "_payf",
			'_susp_tag' 		=> $hlwpw_selected_new_tag . "_susp",
			'_canc_tag' 		=> $hlwpw_selected_new_tag . "_canc"
		);

	} elseif( "tag_existing" == $hlwpw_tag_type && !empty( $hlwpw_selected_existing_tag ) ){
		$membership_tags_set = array(
			'membership_tag' 	=> $hlwpw_selected_existing_tag,
			'_payf_tag' 		=> $hlwpw_selected_existing_tag . "_payf",
			'_susp_tag' 		=> $hlwpw_selected_existing_tag . "_susp",
			'_canc_tag' 		=> $hlwpw_selected_existing_tag . "_canc"
		);

	}else{
		echo "<div class='error'> A tag is required for any membership. </div>";
		return;
	}

	$memberships[ $hlwpw_membership_name ] = array(
		'membership_name' 		=> $hlwpw_membership_name,
		'membership_tag_name' 	=> $membership_tags_set,
		'membership_level' 		=> $hlwpw_membership_level,
	);

	// array_unique($memberships);
	// Need to filter duplicate values

	// echo "membership_meta_key: " . $membership_meta_key;

	update_option( $membership_meta_key, $memberships );

	// Create Location Tags
	foreach ($membership_tags_set as $key => $tag_name) {
		hlwpw_create_location_tag($tag_name);
	}
}



?>

<div id="bw-hlwpw">
	<h1> <?php _e('Membership Settings', 'hlwpw'); ?> </h1>
	<hr />

	<div class="current-membership">
		<h3>
			<?php _e( 'Current Memberships', 'hlwpw' ) ?>
		</h3>

		<table class="tbl-current-membership" cellspacing="0" border="0">
			<tr>
				<th><?php _e( 'Name', 'hlwpw' ) ?></th>
				<th><?php _e( 'Tags', 'hlwpw' ) ?></th>
				<th><?php _e( 'Level', 'hlwpw' ) ?></th>
				<th><?php _e( 'Redirect After Login', 'hlwpw' ) ?></th>
				<th><?php _e( 'Action', 'hlwpw' ) ?></th>
			</tr><?php

			//$memberships = get_option( 'hlwpw_memberships', [] );

			if ( !empty( $memberships ) ) {

// echo "<pre>";
// print_r($memberships);
// echo "</pre>";

				foreach ($memberships as $membership) {

					$membership_name = $membership['membership_name'];
					$tags = $membership['membership_tag_name'];

					$delete_url = admin_url( basename( $_SERVER['REQUEST_URI'] ) );
					$delete_url .= "&delete_membership={$membership_name}"; ?>

					<tr>
						<td> <?php echo $membership_name; ?> </td>
						<td> <?php 

							foreach ($tags as $key => $value) {
								echo $value;
								echo "<br />";
							} ?>
							
						</td>
						<td> <?php echo $membership['membership_level']; ?> </td>
						<td></td>
						<td> <a href="<?php echo $delete_url; ?>"> delete </td>
					</tr> <?php
				}
			}else{ ?>

				<tr>
					<td colspan="5">No Memberships added yet.</td>
				</tr>
			<?php } ?>
			
		</table>
		
	</div><!-- current-membership -->

	<hr/>

	<div class="create-membership">
		<h3>
			<?php _e( 'Create New Memberships', 'hlwpw' ) ?>
		</h3>

		<form method="POST">
			<input type="hidden" name="hlwpw_create_membership" value="yes">

			<table class="tbl-create-membership">
				<tr>
					<th><?php _e( 'Name: ', 'hlwpw' ) ?><span style="color: red;">*</span></th>
					<td>
						<input type="text" name="hlwpw_membership_name" placeholder="membership_name" required pattern="[A-Za-z0-9_]{1,40}">
						<span class="description">Lower case character, number and underscore only allowed.</span>
					</td>
				</tr>

				<tr>
					<th>
						<input type="radio" name="hlwpw_tag_type" id="hlwpw_tag_type_new" value="tag_new" checked>
						<label for="hlwpw_tag_type_new">
							<?php _e( 'Create New Tag:', 'hlwpw' ) ?>
						</label>
					</th>
					<td>
						<input type="text" name="hlwpw_selected_new_tag" placeholder="Create New Tag">
					</td>
				</tr>

				<tr>
					<th>
						<input type="radio" name="hlwpw_tag_type" id="hlwpw_tag_type_existing" value="tag_existing" >
						<label for="hlwpw_tag_type_existing">
							<?php _e( 'Select Existing Tag:', 'hlwpw' ) ?>
						</label>
					</th>
					<td>
						<select name="hlwpw_selected_existing_tag" id="hlwpw_selected_existing_tag">
							<option value="">None</option>
							<?php echo hlwpw_get_tag_options(0); ?>
						</select>
					</td>
				</tr>

				<tr>
					<th><?php _e( 'Membership Level:', 'hlwpw' ) ?></th>
					<td>
						<input type="text" name="hlwpw_membership_level" value="0" required>
					</td>
				</tr>

				<tr>
					<th></th>
					<td>
						<input type="" name="" id="submit" class="button button-primary disabled" value="Create Membership" title="premium feature">
						<p class="description">This is a premium Feature, Power up your website by unlocking the premium features <a href="admin.php?page=lcw-power-up">here</a></p>
					</td>
				</tr>
				
			</table>
		</form>
		
	</div><!-- create-membership -->

</div>