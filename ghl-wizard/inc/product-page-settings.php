<?php

// Register the Tab
if ( ! function_exists( 'hlwpw_product_data_tab' ) ) {
    
    function hlwpw_product_data_tab( $tabs ) {
        $tabs['hlwpw-tab'] = array(
            'label'     => __( 'LC Wizard', 'hlwpw' ),
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

        $refresh_url = admin_url( basename( $_SERVER['REQUEST_URI'] ) );
        
        if( ! strpos( $refresh_url, 'ghl_refresh=1' ) ) {
            $refresh_url .= '&ghl_refresh=1';
        }
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
                    <a class="button refresh-btn" href=<?php echo $refresh_url; ?>> Refresh Data </a>
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

        update_post_meta( $post_id, 'hlwpw_location_tags', $hlwpw_location_tags );
        update_post_meta( $post_id, 'hlwpw_location_campaigns', $hlwpw_location_campaigns );
        update_post_meta( $post_id, 'hlwpw_location_wokflow', $hlwpw_location_wokflow );
        update_post_meta( $post_id, 'hlwpw_order_status_tag', $hlwpw_order_status_tag );
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

            // remove wc- from the stutuses
            $status = str_replace('wc-', '', $status);
            $selected_tags = $hlwpw_order_status_tag[$status];

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