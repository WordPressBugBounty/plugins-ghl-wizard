<?php

// Register the Tab
if ( ! function_exists( 'hlwpw_product_data_tab' ) ) {
    
    function hlwpw_product_data_tab( $tabs ) {
        $tabs['hlwpw-tab'] = array(
            'label'     => __( 'Connector Wizard', 'hlwpw' ),
            'target'    => 'hlwpw-tab',
            'class'     => array(),
        );
        return $tabs;
    }
    add_filter( 'woocommerce_product_data_tabs', 'hlwpw_product_data_tab' );
}

// Settings Fields.
if ( ! function_exists( 'hlwpw_single_product_settings_fields' ) ) {
    
    function hlwpw_single_product_settings_fields() {
        
        global $post;
        $post_id = $post->ID;

        $refresh_url = admin_url( "admin.php?page=connector-wizard-app#/tools" );
        ?>

        <div id='hlwpw-tab' class = 'panel woocommerce_options_panel'>
        	<div class = 'options_group' > 

                <div class="hlwpw-tab-field">
                    <label>Add tags upon successful purchase</label>
                    <select name="hlwpw_location_tags[]" id="hlwpw-tag-box" multiple="multiple">
                        <?php echo hlwpw_get_location_tag_options($post_id); ?>
                    </select>
                </div>

                <div class="hlwpw-tab-field">
                    <label>Add to campaigns upon successful purchase</label>
                    <select name="hlwpw_location_campaigns[]" id="hlwpw-campaign-box" multiple="multiple">
                        <?php echo hlwpw_get_location_campaign_options($post_id); ?>
                    </select>
                </div>

                <div class="hlwpw-tab-field">
                    <label>Add to workflow upon successful purchase</label>

                    <select name="hlwpw_location_wokflow[]" id="hlwpw-wokflow-box" multiple="multiple">
                        <?php echo hlwpw_get_location_workflow_options($post_id); ?>
                    </select>
                </div>

                <hr style='margin: 20px 0'>

                <h2> Book an Appointment on Successful Purchase </h2>

                <div class="hlwpw-tab-field">
                    <label for="lcw-calendar-id">Select a calendar</label>
                    <select name="lcw_calendar_id" id="lcw-calendar-id">
                        <?php echo lcw_get_calendar_options( $post_id ); ?>
                    </select>
                </div>

                <div class="hlwpw-tab-field">
                    <label for="lcw-calendar-start-date">Start date</label>
                    <input type="date" id="lcw-calendar-start-date">
                </div>

                <div class="hlwpw-tab-field">
                    <label for="lcw-calendar-end-date">End date</label>
                    <input type="date" id="lcw-calendar-end-date">
                    <p class="description">The date range cannot exceed 31 days.</p>
                </div>

                <div class="hlwpw-tab-field">
                    <label>&nbsp;</label>
                    <button type="button" class="button" id="lcw-get-time-slots">Get Time Slots</button>
                    <span id="lcw-calendar-timeslot-message" class="description"></span>
                </div>

                <div class="hlwpw-tab-field">
                    <label for="lcw-calendar-time-slot">Select the time slot</label>
                    <select name="lcw_calendar_time_slot" id="lcw-calendar-time-slot">
                        <?php echo lcw_get_saved_calendar_timeslot_option( $post_id ); ?>
                    </select>
                </div>

                <div class="hlwpw-tab-field">
                    <label for="lcw-appointment-title">Appointment Title</label>
                    <input
                        type="text"
                        name="lcw_appointment_title"
                        id="lcw-appointment-title"
                        value="<?php echo esc_attr( lcw_get_appointment_field_value( $post_id, 'lcw_appointment_title', get_the_title( $post_id ) ) ); ?>"
                    >
                </div>

                <div class="hlwpw-tab-field">
                    <label for="lcw-appointment-address">Appointment Address</label>
                    <input
                        type="text"
                        name="lcw_appointment_address"
                        id="lcw-appointment-address"
                        value="<?php echo esc_attr( lcw_get_appointment_field_value( $post_id, 'lcw_appointment_address' ) ); ?>"
                        placeholder="Example: Zoom Meeting, Google Meet, etc."
                    >
                </div>

                <div class="hlwpw-tab-field">
                    <label for="lcw-appointment-description">Appointment Description</label>
                    <input
                        type="text"
                        name="lcw_appointment_description"
                        id="lcw-appointment-description"
                        value="<?php echo esc_attr( lcw_get_appointment_field_value( $post_id, 'lcw_appointment_description' ) ); ?>"
                    >
                </div>

                <hr style='margin: 20px 0'>

                <?php
                $data = get_option( 'leadconnectorwizardpro_license_options' );
                if ( isset( $data['sc_activation_id'] ) ) { ?>

                    <h2> Apply Tags On Different Order Status </h2>

                    <div id="hlwpw-order-status-action-area">
                        <?php echo hlwpw_get_order_status_options_html($post_id); ?>
                    </div>

                <?php } else { ?>
                    <div>
                        <img src='<?php echo plugins_url('images/apply-tags.png', __DIR__ . '/../../'); ?>'>
                        <p> This is a premium feature, <a href="<?php echo admin_url('admin.php?page=lcw-power-up'); ?>">power up</a> to use this feature.
                    </div>
                <?php } ?>

                <hr style='margin: 20px 0'>

                <div style='margin: 50px 0px'>
                    <a class="button refresh-btn" href=<?php echo $refresh_url; ?> target="_blank"> Refresh Data </a>
                </div>

    		</div>
        </div><?php
    }
    add_action('woocommerce_product_data_panels', 'hlwpw_single_product_settings_fields');
}




// Save data
if ( ! function_exists( 'woocom_save_data_for_hlwpw_tab' ) ) {

    function woocom_save_data_for_hlwpw_tab($post_id) {

        $hlwpw_location_tags        = isset( $_POST['hlwpw_location_tags'] ) ? hlwpw_recursive_sanitize_array( $_POST['hlwpw_location_tags'] ) : array();
        $hlwpw_location_campaigns   = isset( $_POST['hlwpw_location_campaigns'] ) ? hlwpw_recursive_sanitize_array( $_POST['hlwpw_location_campaigns'] ) : array();
        $hlwpw_location_wokflow     = isset( $_POST['hlwpw_location_wokflow'] ) ? hlwpw_recursive_sanitize_array( $_POST['hlwpw_location_wokflow'] ) : array();
        $hlwpw_order_status_tag     = isset( $_POST['hlwpw_order_status_tag'] ) ? hlwpw_recursive_sanitize_array( $_POST['hlwpw_order_status_tag'] ) : array();
        $lcw_calendar_id            = isset( $_POST['lcw_calendar_id'] ) ? sanitize_text_field( wp_unslash( $_POST['lcw_calendar_id'] ) ) : '';
        $lcw_calendar_time_slot     = isset( $_POST['lcw_calendar_time_slot'] ) ? sanitize_text_field( wp_unslash( $_POST['lcw_calendar_time_slot'] ) ) : '';
        $lcw_appointment_title      = isset( $_POST['lcw_appointment_title'] ) ? sanitize_text_field( wp_unslash( $_POST['lcw_appointment_title'] ) ) : '';
        $lcw_appointment_address    = isset( $_POST['lcw_appointment_address'] ) ? sanitize_text_field( wp_unslash( $_POST['lcw_appointment_address'] ) ) : '';
        $lcw_appointment_description = isset( $_POST['lcw_appointment_description'] ) ? sanitize_text_field( wp_unslash( $_POST['lcw_appointment_description'] ) ) : '';

        update_post_meta( $post_id, 'hlwpw_location_tags', $hlwpw_location_tags );
        update_post_meta( $post_id, 'hlwpw_location_campaigns', $hlwpw_location_campaigns );
        update_post_meta( $post_id, 'hlwpw_location_wokflow', $hlwpw_location_wokflow );
        update_post_meta( $post_id, 'hlwpw_order_status_tag', $hlwpw_order_status_tag );
        update_post_meta( $post_id, 'lcw_calendar_id', $lcw_calendar_id );
        update_post_meta( $post_id, 'lcw_calendar_time_slot', $lcw_calendar_time_slot );
        lcw_update_optional_product_meta( $post_id, 'lcw_appointment_title', $lcw_appointment_title );
        lcw_update_optional_product_meta( $post_id, 'lcw_appointment_address', $lcw_appointment_address );
        lcw_update_optional_product_meta( $post_id, 'lcw_appointment_description', $lcw_appointment_description );
    }

    add_action( 'woocommerce_process_product_meta_simple', 'woocom_save_data_for_hlwpw_tab'  );
    add_action( 'woocommerce_process_product_meta_variable', 'woocom_save_data_for_hlwpw_tab'  );
}

if ( ! function_exists( 'hlwpw_get_location_tag_options' ) ) {
    
    function hlwpw_get_location_tag_options($post_id) {

        $tags = hlwpw_get_location_tags();
        $options    = "";
        $hlwpw_location_tags = get_post_meta( $post_id, 'hlwpw_location_tags', true );

        $hlwpw_location_tags = ( !empty($hlwpw_location_tags) ) ? $hlwpw_location_tags :  [];

        foreach ($tags as $tag ) {
            $tag_id   = $tag->id;
            $tag_name = $tag->name;
            $selected = "";

            if ( in_array( $tag_name, $hlwpw_location_tags )) {
                $selected = "selected";
            }

            $options .= "<option value='{$tag_name}' {$selected}>";
            $options .= $tag_name;
            $options .= "</option>";
        }

        return $options;
    }
}

if ( ! function_exists( 'hlwpw_get_order_status_options_html' ) ) {
    
    function hlwpw_get_order_status_options_html($post_id) {

        $order_statuses = wc_get_order_statuses();

        $hlwpw_order_status_tag = get_post_meta( $post_id, 'hlwpw_order_status_tag', true );    
        $hlwpw_order_status_tag = ( !empty($hlwpw_order_status_tag) ) ? $hlwpw_order_status_tag :  [];

        $html = "";

        foreach ($order_statuses as $status => $label) {

            // remove wc- from the statuses
            $status = str_replace('wc-', '', $status);
            $selected_tags = isset($hlwpw_order_status_tag[$status]) ? $hlwpw_order_status_tag[$status] : [];

            $html .= "<div class='status-item hlwpw-tab-field'>";
                $html .= "<label>";
                    $html .= "Apply tags for the order status: <b>" . $label . "</b>";
                $html .= "</label>";

                $html .= "<select name='hlwpw_order_status_tag[{$status}][]' class='hlwpw-status-tag-box' multiple='multiple'>";
                    $html .= hlwpw_get_order_status_tag_options($selected_tags);
                $html .= '</select>';
            $html .= '</div>';
        }

        return $html;

    }
}

if ( ! function_exists( 'hlwpw_get_order_status_tag_options' ) ) {
    
    function hlwpw_get_order_status_tag_options($selected_tags) {

        $tags = hlwpw_get_location_tags();
        $options    = "";
        $selected_tags = ( !empty($selected_tags) ) ? $selected_tags :  [];

        foreach ($tags as $tag ) {
            $tag_id   = $tag->id;
            $tag_name = $tag->name;
            $selected = "";

            if ( in_array( $tag_name, $selected_tags )) {
                $selected = "selected";
            }

            $options .= "<option value='{$tag_name}' {$selected}>";
            $options .= $tag_name;
            $options .= "</option>";
        }

        return $options;
    }
}

if ( ! function_exists( 'hlwpw_get_location_campaign_options' ) ) {
    
    function hlwpw_get_location_campaign_options($post_id) {

        $campaigns = hlwpw_get_location_campaigns();
        $options    = "";
        $hlwpw_location_campaigns = get_post_meta( $post_id, 'hlwpw_location_campaigns', true );

        $hlwpw_location_campaigns = ( !empty($hlwpw_location_campaigns) ) ? $hlwpw_location_campaigns :  [];

        foreach ($campaigns as $campaign ) {
            $campaign_id   = $campaign->id;
            $campaign_name = $campaign->name;
            $campaign_status = $campaign->status;
            $selected = "";
            $disabled = "";

            if ( in_array( $campaign_id, $hlwpw_location_campaigns )) {
                $selected = "selected";
            }

            if ( 'draft' == $campaign_status ) {
                $disabled = "disabled";
            }

            $options .= "<option value='{$campaign_id}' {$selected} {$disabled}>";
            $options .= $campaign_name;
            $options .= "</option>";
        }

        return $options;
    }
}

if ( ! function_exists( 'hlwpw_get_location_workflow_options' ) ) {
    
    function hlwpw_get_location_workflow_options($post_id) {

        $workflows  = hlwpw_get_location_workflows();
        $options    = "";
        $hlwpw_location_wokflow = get_post_meta( $post_id, 'hlwpw_location_wokflow', true );

        $hlwpw_location_wokflow = ( !empty($hlwpw_location_wokflow) ) ? $hlwpw_location_wokflow :  [];

        foreach ($workflows as $workflow ) {
            $workflow_id        = $workflow->id;
            $workflow_name      = $workflow->name;
            $workflow_status    = $workflow->status;
            $selected           = "";
            $disabled           = "";

            if ( in_array( $workflow_id, $hlwpw_location_wokflow )) {
                $selected = "selected";
            }

            if ( 'draft' == $workflow_status ) {
                $disabled = "disabled";
            }

            $options .= "<option value='{$workflow_id}' {$selected} {$disabled}>";
            $options .= $workflow_name;
            $options .= "</option>";
        }

        return $options;
    }
}



// Add variation
// from @v1.1.02
function lc_wizard_add_tag_to_variation_options( $loop, $variation_data, $variation ) {

    $variation_obj = wc_get_product( $variation->ID );

    woocommerce_wp_select( array(
        'id' => '_variation_tag[' . $loop . ']',
        'options' => lc_wizard_variation_tags_option(),
        'label'       => __('Variation Tag','hlwpw'),
        'desc_tip'    => 'true',
        'description' => __( 'This variation tag will be added to the contact if a contact purchase this variation.', 'hlwpw' ),
        'value'       => $variation_obj->get_meta( '_variation_tag', true ),
        'wrapper_class' => 'form-row form-row-first',
    ) );

}
add_action( 'woocommerce_product_after_variable_attributes', 'lc_wizard_add_tag_to_variation_options', 10, 3 );

// Save variation
function lc_wizard_save_tag_to_variation_options( $variation, $i ) {

    if ( isset( $_POST['_variation_tag'][$i] ) && 0 != $_POST['_variation_tag'][$i] ) {
        $variation->update_meta_data( '_variation_tag', wc_clean( $_POST['_variation_tag'][$i] ) );
    }

}
add_action( 'woocommerce_admin_process_variation_object', 'lc_wizard_save_tag_to_variation_options', 10, 2 );


// Variation tags
function lc_wizard_variation_tags_option(){

    $data = get_option('leadconnectorwizardpro_license_options');
    if ( ! isset( $data['sc_activation_id'] ) ) {
        return [' - This is a premium feature, please upgrade - '];
    }

    $tags = hlwpw_get_location_tags();
    $tag_list[0] = '- Select a tag for this variation -';

    foreach ( $tags as $tag ) {

        $tag_list[$tag->name] = $tag->name;

    }

    return $tag_list;
}

if ( ! function_exists( 'lcw_get_calendar_options' ) ) {
	function lcw_get_calendar_options( $post_id ) {
		$calendars            = lcw_get_location_calendars();
		$selected_calendar_id = get_post_meta( $post_id, 'lcw_calendar_id', true );
		$options              = '<option value="">' . esc_html__( '- Select a calendar -', 'ghl-wizard' ) . '</option>';

		foreach ( $calendars as $calendar ) {
			if ( empty( $calendar->id ) ) {
				continue;
			}

			$calendar_name = ! empty( $calendar->name ) ? $calendar->name : $calendar->id;
			$options      .= sprintf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $calendar->id ),
				selected( $selected_calendar_id, $calendar->id, false ),
				esc_html( $calendar_name )
			);
		}

		return $options;
	}
}

if ( ! function_exists( 'lcw_get_saved_calendar_timeslot_option' ) ) {
	function lcw_get_saved_calendar_timeslot_option( $post_id ) {
		$saved_slot = get_post_meta( $post_id, 'lcw_calendar_time_slot', true );
		$options    = '<option value="">' . esc_html__( '- Select a time slot -', 'ghl-wizard' ) . '</option>';

		if ( ! empty( $saved_slot ) ) {
			$options .= sprintf(
				'<option value="%1$s" selected>%2$s</option>',
				esc_attr( $saved_slot ),
				esc_html( $saved_slot )
			);
		}

		return $options;
	}
}

if ( ! function_exists( 'lcw_get_appointment_field_value' ) ) {
	function lcw_get_appointment_field_value( $post_id, $meta_key, $default_value = '' ) {
		$value = get_post_meta( $post_id, $meta_key, true );

		return '' !== $value ? $value : $default_value;
	}
}

if ( ! function_exists( 'lcw_update_optional_product_meta' ) ) {
	function lcw_update_optional_product_meta( $post_id, $meta_key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, $value );
	}
}

add_action( 'wp_ajax_lcw_get_calendar_timeslots', 'lcw_get_calendar_timeslots_ajax' );

function lcw_get_calendar_timeslots_ajax() {
	check_ajax_referer( 'lcw_calendar_timeslots_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_products' ) && ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to fetch calendar time slots.', 'ghl-wizard' ) ), 403 );
	}

	$calendar_id = isset( $_POST['calendar_id'] ) ? sanitize_text_field( wp_unslash( $_POST['calendar_id'] ) ) : '';
	$start_date  = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
	$end_date    = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';

	$validation_error = lcw_validate_calendar_timeslot_range( $calendar_id, $start_date, $end_date );
	if ( is_wp_error( $validation_error ) ) {
		wp_send_json_error( array( 'message' => $validation_error->get_error_message() ), 400 );
	}

	$result = lcw_get_calendar_timeslots( $calendar_id, $start_date, $end_date );
	if ( is_wp_error( $result ) || empty( $result['code'] ) || 200 !== (int) $result['code'] ) {
		wp_send_json_error( array( 'message' => __( 'Unable to fetch calendar time slots.', 'ghl-wizard' ) ), 500 );
	}

	wp_send_json_success(
		array(
			'slots' => lcw_normalize_calendar_timeslots( $result['body'] ),
		)
	);
}

function lcw_validate_calendar_timeslot_range( $calendar_id, $start_date, $end_date ) {
	if ( empty( $calendar_id ) || empty( $start_date ) || empty( $end_date ) ) {
		return new WP_Error( 'lcw_missing_calendar_timeslot_fields', __( 'Select a calendar, start date, and end date.', 'ghl-wizard' ) );
	}

	$start = DateTime::createFromFormat( 'Y-m-d', $start_date );
	$end   = DateTime::createFromFormat( 'Y-m-d', $end_date );

	if ( ! $start || ! $end || $start->format( 'Y-m-d' ) !== $start_date || $end->format( 'Y-m-d' ) !== $end_date ) {
		return new WP_Error( 'lcw_invalid_calendar_timeslot_dates', __( 'Enter valid dates.', 'ghl-wizard' ) );
	}

	if ( $end < $start ) {
		return new WP_Error( 'lcw_invalid_calendar_timeslot_order', __( 'End date cannot be earlier than start date.', 'ghl-wizard' ) );
	}

	if ( (int) $start->diff( $end )->days > 31 ) {
		return new WP_Error( 'lcw_calendar_timeslot_range_too_long', __( 'Date range cannot exceed 31 days.', 'ghl-wizard' ) );
	}

	return true;
}

function lcw_normalize_calendar_timeslots( $body ) {
	$slots = array();

	if ( ! is_object( $body ) ) {
		return $slots;
	}

	foreach ( get_object_vars( $body ) as $date => $availability ) {
		$date_slots = array();

		if ( is_object( $availability ) && isset( $availability->slots ) && is_array( $availability->slots ) ) {
			$date_slots = $availability->slots;
		} elseif ( is_array( $availability ) ) {
			$date_slots = $availability;
		}

		foreach ( $date_slots as $slot ) {
			if ( ! is_string( $slot ) || '' === $slot ) {
				continue;
			}

			if ( preg_match( '/^(\d{4}-\d{2}-\d{2})T(\d{2}):(\d{2}):\d{2}([+-]\d{2}:\d{2})$/', $slot, $matches ) ) {
				$slot_date = $matches[1];
				$hour_24   = (int) $matches[2];
				$minute    = $matches[3];
				$timezone  = $matches[4];

				$timestamp = strtotime( $slot_date );

				$label_date = date( 'l, F j, Y', $timestamp );

				$ampm    = $hour_24 >= 12 ? 'PM' : 'AM';
				$hour_12 = $hour_24 % 12;
				$hour_12 = 0 === $hour_12 ? 12 : $hour_12;

				$label_time = $hour_12 . ':' . $minute . ' ' . $ampm;

				$label = sprintf(
					'%s at %s (GMT%s)',
					$label_date,
					$label_time,
					$timezone
				);
			} else {
				$label = $date . ' - ' . $slot;
			}

			$slots[] = array(
				'value' => $slot,
				'label' => $label,
			);
		}
	}

	return $slots;
}
