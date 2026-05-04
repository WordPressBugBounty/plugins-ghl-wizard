<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lcw_get_menu_tag_logic_options() {
	return array(
		'any'     => __( 'Match any of the tags (OR)', 'hlwpw' ),
		'all'     => __( 'Match all of the tags (AND)', 'hlwpw' ),
		'not_any' => __( 'Does not have any of the tags', 'hlwpw' ),
		'none'    => __( 'Does not have all of the tags', 'hlwpw' ),
	);
}

function lcw_get_menu_item_visibility_meta( $item_id ) {
	$legacy_visibility = get_post_meta( $item_id, '_lcw_menu_visibility', true );
	$logged_in         = get_post_meta( $item_id, '_lcw_menu_logged_in', true );
	$logged_out        = get_post_meta( $item_id, '_lcw_menu_logged_out', true );
	$membership_any    = get_post_meta( $item_id, '_lcw_menu_membership_any', true );
	$tag_logic         = get_post_meta( $item_id, '_lcw_menu_tag_logic', true );
	$tags              = get_post_meta( $item_id, '_lcw_menu_visibility_tags', true );
	$memberships       = get_post_meta( $item_id, '_lcw_menu_visibility_memberships', true );

	$tags        = is_array( $tags ) ? $tags : array();
	$memberships = is_array( $memberships ) ? $memberships : array();

	if ( 'logged_in' === $legacy_visibility ) {
		$logged_in = 'yes';
	}

	if ( 'logged_out' === $legacy_visibility ) {
		$logged_out = 'yes';
	}

	if ( ! array_key_exists( $tag_logic, lcw_get_menu_tag_logic_options() ) ) {
		$tag_logic = 'any';
	}

	return array(
		'logged_in'      => 'yes' === $logged_in,
		'logged_out'     => 'yes' === $logged_out,
		'membership_any' => 'yes' === $membership_any || in_array( '1', $memberships, true ) || in_array( 1, $memberships, true ),
		'tag_logic'      => $tag_logic,
		'tags'           => array_values( array_filter( array_map( 'sanitize_text_field', $tags ) ) ),
		'memberships'    => array_values( array_filter( array_map( 'sanitize_text_field', $memberships ) ) ),
	);
}

function lcw_menu_select_options( array $options, $selected_value ) {
	$html = '';

	foreach ( $options as $value => $label ) {
		$html .= sprintf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $value ),
			selected( $selected_value, $value, false ),
			esc_html( $label )
		);
	}

	return $html;
}

function lcw_menu_visibility_tag_options( array $selected_tags ) {
	$html = '';
	$tags = function_exists( 'hlwpw_get_location_tags' ) ? hlwpw_get_location_tags() : array();

	if ( empty( $tags ) ) {
		return '<option value="" disabled>' . esc_html__( 'No tags found', 'hlwpw' ) . '</option>';
	}

	foreach ( $tags as $tag ) {
		if ( empty( $tag->name ) ) {
			continue;
		}

		$tag_name = (string) $tag->name;
		$html .= sprintf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $tag_name ),
			selected( in_array( $tag_name, $selected_tags, true ), true, false ),
			esc_html( $tag_name )
		);
	}

	return ! empty( $html ) ? $html : '<option value="" disabled>' . esc_html__( 'No tags found', 'hlwpw' ) . '</option>';
}

function lcw_menu_visibility_membership_options( array $selected_memberships ) {
	$html        = '';
	$memberships = function_exists( 'lcw_get_memberships' ) ? lcw_get_memberships() : array();

	if ( empty( $memberships ) ) {
		return '<option value="" disabled>' . esc_html__( 'No membership added yet', 'hlwpw' ) . '</option>';
	}

	foreach ( $memberships as $membership ) {
		if ( empty( $membership['membership_name'] ) ) {
			continue;
		}

		$membership_name = (string) $membership['membership_name'];
		$html .= sprintf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $membership_name ),
			selected( in_array( $membership_name, $selected_memberships, true ), true, false ),
			esc_html( $membership_name )
		);
	}

	return ! empty( $html ) ? $html : '<option value="" disabled>' . esc_html__( 'No membership added yet', 'hlwpw' ) . '</option>';
}

function lcw_add_menu_item_visibility_fields( $item_id, $item, $depth, $args ) {
	$meta                  = lcw_get_menu_item_visibility_meta( $item_id );
	$tag_options           = lcw_menu_visibility_tag_options( $meta['tags'] );
	$membership_options    = lcw_menu_visibility_membership_options( $meta['memberships'] );
	$has_tag_options       = false === strpos( $tag_options, 'No tags found' );
	$has_membership_options = false === strpos( $membership_options, 'No membership added yet' );
	?>
	<p class="field-lcw-menu-logged-in description description-wide">
		<label for="edit-lcw-menu-logged-in-<?php echo esc_attr( $item_id ); ?>">
			<input id="edit-lcw-menu-logged-in-<?php echo esc_attr( $item_id ); ?>" type="checkbox" name="lcw_menu_logged_in[<?php echo esc_attr( $item_id ); ?>]" value="yes" <?php checked( $meta['logged_in'] ); ?>>
			<?php esc_html_e( 'Only logged in users', 'hlwpw' ); ?>
		</label>
		<br>
		<label for="edit-lcw-menu-logged-out-<?php echo esc_attr( $item_id ); ?>">
			<input id="edit-lcw-menu-logged-out-<?php echo esc_attr( $item_id ); ?>" type="checkbox" name="lcw_menu_logged_out[<?php echo esc_attr( $item_id ); ?>]" value="yes" <?php checked( $meta['logged_out'] ); ?>>
			<?php esc_html_e( 'Only logged out users', 'hlwpw' ); ?>
		</label>
	</p>
	<p class="field-lcw-menu-tags description description-wide">
		<label for="edit-lcw-menu-tags-<?php echo esc_attr( $item_id ); ?>">
			<?php esc_html_e( 'Required tags', 'hlwpw' ); ?><br>
			<select id="edit-lcw-menu-tags-<?php echo esc_attr( $item_id ); ?>" name="lcw_menu_visibility_tags[<?php echo esc_attr( $item_id ); ?>][]" class="widefat lcw-menu-visibility-tags" multiple="multiple" data-placeholder="<?php echo esc_attr( $has_tag_options ? __( 'Select tags', 'hlwpw' ) : __( 'No tags found', 'hlwpw' ) ); ?>">
				<?php echo $tag_options; ?>
			</select>
		</label>
	</p>
	<p class="field-lcw-menu-tag-logic description description-wide">
		<label for="edit-lcw-menu-tag-logic-<?php echo esc_attr( $item_id ); ?>">
			<?php esc_html_e( 'Tag logic', 'hlwpw' ); ?><br>
			<select id="edit-lcw-menu-tag-logic-<?php echo esc_attr( $item_id ); ?>" name="lcw_menu_tag_logic[<?php echo esc_attr( $item_id ); ?>]" class="widefat">
				<?php echo lcw_menu_select_options( lcw_get_menu_tag_logic_options(), $meta['tag_logic'] ); ?>
			</select>
		</label>
	</p>
	<p class="field-lcw-menu-membership-any description description-wide">
		<label for="edit-lcw-menu-membership-any-<?php echo esc_attr( $item_id ); ?>">
			<input id="edit-lcw-menu-membership-any-<?php echo esc_attr( $item_id ); ?>" type="checkbox" name="lcw_menu_membership_any[<?php echo esc_attr( $item_id ); ?>]" value="yes" <?php checked( $meta['membership_any'] ); ?>>
			<?php esc_html_e( 'Any Membership', 'hlwpw' ); ?>
		</label>
	</p>
	<p class="field-lcw-menu-memberships description description-wide">
		<label for="edit-lcw-menu-memberships-<?php echo esc_attr( $item_id ); ?>">
			<?php esc_html_e( 'Required memberships', 'hlwpw' ); ?><br>
			<select id="edit-lcw-menu-memberships-<?php echo esc_attr( $item_id ); ?>" name="lcw_menu_visibility_memberships[<?php echo esc_attr( $item_id ); ?>][]" class="widefat lcw-menu-visibility-memberships" multiple="multiple" data-placeholder="<?php echo esc_attr( $has_membership_options ? __( 'Select memberships', 'hlwpw' ) : __( 'No membership added yet', 'hlwpw' ) ); ?>">
				<?php echo $membership_options; ?>
			</select>
		</label>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'lcw_add_menu_item_visibility_fields', 10, 4 );

function lcw_save_menu_item_visibility_fields( $menu_id, $menu_item_db_id ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$posted_logged_in      = isset( $_POST['lcw_menu_logged_in'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['lcw_menu_logged_in'][ $menu_item_db_id ] ) ) : '';
	$posted_logged_out     = isset( $_POST['lcw_menu_logged_out'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['lcw_menu_logged_out'][ $menu_item_db_id ] ) ) : '';
	$posted_membership_any = isset( $_POST['lcw_menu_membership_any'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['lcw_menu_membership_any'][ $menu_item_db_id ] ) ) : '';
	$posted_tag_logic      = isset( $_POST['lcw_menu_tag_logic'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['lcw_menu_tag_logic'][ $menu_item_db_id ] ) ) : 'any';
	$posted_tags           = isset( $_POST['lcw_menu_visibility_tags'][ $menu_item_db_id ] ) && is_array( $_POST['lcw_menu_visibility_tags'][ $menu_item_db_id ] ) ? wp_unslash( $_POST['lcw_menu_visibility_tags'][ $menu_item_db_id ] ) : array();
	$posted_memberships    = isset( $_POST['lcw_menu_visibility_memberships'][ $menu_item_db_id ] ) && is_array( $_POST['lcw_menu_visibility_memberships'][ $menu_item_db_id ] ) ? wp_unslash( $_POST['lcw_menu_visibility_memberships'][ $menu_item_db_id ] ) : array();

	if ( ! array_key_exists( $posted_tag_logic, lcw_get_menu_tag_logic_options() ) ) {
		$posted_tag_logic = 'any';
	}

	if ( 'yes' === $posted_logged_out ) {
		$posted_logged_in      = '';
		$posted_membership_any = '';
		$posted_tags           = array();
		$posted_memberships    = array();
	}

	$posted_tags        = array_values( array_filter( array_map( 'sanitize_text_field', $posted_tags ) ) );
	$posted_memberships = array_values( array_filter( array_map( 'sanitize_text_field', $posted_memberships ) ) );

	delete_post_meta( $menu_item_db_id, '_lcw_menu_visibility' );

	'yes' === $posted_logged_in ? update_post_meta( $menu_item_db_id, '_lcw_menu_logged_in', 'yes' ) : delete_post_meta( $menu_item_db_id, '_lcw_menu_logged_in' );
	'yes' === $posted_logged_out ? update_post_meta( $menu_item_db_id, '_lcw_menu_logged_out', 'yes' ) : delete_post_meta( $menu_item_db_id, '_lcw_menu_logged_out' );
	'yes' === $posted_membership_any ? update_post_meta( $menu_item_db_id, '_lcw_menu_membership_any', 'yes' ) : delete_post_meta( $menu_item_db_id, '_lcw_menu_membership_any' );
	update_post_meta( $menu_item_db_id, '_lcw_menu_tag_logic', $posted_tag_logic );
	update_post_meta( $menu_item_db_id, '_lcw_menu_visibility_tags', $posted_tags );
	update_post_meta( $menu_item_db_id, '_lcw_menu_visibility_memberships', $posted_memberships );
}
add_action( 'wp_update_nav_menu_item', 'lcw_save_menu_item_visibility_fields', 10, 2 );

function lcw_current_user_has_menu_membership( array $selected_memberships, $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || empty( $selected_memberships ) ) {
		return false;
	}

	$memberships = lcw_get_memberships();
	if ( empty( $memberships ) ) {
		return false;
	}

	$selected_memberships = array_values( array_filter( array_map( 'sanitize_text_field', $selected_memberships ) ) );
	$check_all            = in_array( '1', $selected_memberships, true ) || in_array( 1, $selected_memberships, true );

	foreach ( $memberships as $membership ) {
		if ( empty( $membership['membership_name'] ) || empty( $membership['membership_tag_name'] ) || ! is_array( $membership['membership_tag_name'] ) ) {
			continue;
		}

		$membership_name = (string) $membership['membership_name'];
		if ( ! $check_all && ! in_array( $membership_name, $selected_memberships, true ) ) {
			continue;
		}

		$membership_tags = $membership['membership_tag_name'];
		$active_tag      = isset( $membership_tags['membership_tag'] ) ? $membership_tags['membership_tag'] : '';
		$inactive_tags   = array_filter(
			array(
				isset( $membership_tags['_payf_tag'] ) ? $membership_tags['_payf_tag'] : '',
				isset( $membership_tags['_susp_tag'] ) ? $membership_tags['_susp_tag'] : '',
				isset( $membership_tags['_canc_tag'] ) ? $membership_tags['_canc_tag'] : '',
			)
		);

		if ( empty( $active_tag ) ) {
			continue;
		}

		if ( hlwpw_contact_has_tag( $active_tag, 'any', $user_id ) && ! hlwpw_contact_has_tag( $inactive_tags, 'any', $user_id ) ) {
			return true;
		}
	}

	return false;
}

function lcw_menu_item_is_visible_for_current_user( $item ) {
	$meta    = lcw_get_menu_item_visibility_meta( $item->ID );
	$user_id = get_current_user_id();

	if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
		return true;
	}

	if ( ! empty( $meta['logged_in'] ) && ! is_user_logged_in() ) {
		return false;
	}

	if ( ! empty( $meta['logged_out'] ) && is_user_logged_in() ) {
		return false;
	}

	if ( ! empty( $meta['tags'] ) ) {
		if ( ! $user_id || ! hlwpw_contact_has_tag( $meta['tags'], $meta['tag_logic'], $user_id ) ) {
			return false;
		}
	}

	if ( ! empty( $meta['membership_any'] ) || ! empty( $meta['memberships'] ) ) {
		$memberships = ! empty( $meta['membership_any'] ) ? array( '1' ) : $meta['memberships'];
		if ( ! lcw_current_user_has_menu_membership( $memberships, $user_id ) ) {
			return false;
		}
	}

	return true;
}

function lcw_hide_menu_items_based_on_access( $items, $menu, $args ) {
	if ( empty( $items ) || ! is_array( $items ) ) {
		return $items;
	}

	foreach ( $items as $key => $item ) {
		if ( ! lcw_menu_item_is_visible_for_current_user( $item ) ) {
			unset( $items[ $key ] );
		}
	}

	return $items;
}
add_filter( 'wp_get_nav_menu_items', 'lcw_hide_menu_items_based_on_access', 10, 3 );
