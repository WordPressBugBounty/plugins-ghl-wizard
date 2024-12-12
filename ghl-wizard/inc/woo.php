<?php

function hlwpw_connect_to_ghl_based_on_order( $order_id, $old_status, $new_status ){

    $order = wc_get_order($order_id);

    $hlwpw_order_status = get_option('hlwpw_order_status', 'wc-processing');
    $set_new_status = str_ireplace( "wc-", "", $hlwpw_order_status );

    // Check it runs only once
    if ( $set_new_status != $new_status ) {
        return;
    }

    // updated @v1.1
    // get contact_id from database
    $user_id = $order->get_user_id();
    if ( 0 != $user_id ) {

        $contactId = lcw_get_contact_id_by_wp_user_id( $user_id );

    }else{

        $locationId = get_option( 'hlwpw_locationId' );    
        $contact_data = [
            "locationId"    => $locationId,
            "firstName"     => $order->get_billing_first_name(),
            "lastName"      => $order->get_billing_last_name(),
            "email"         => $order->get_billing_email(),
            "phone"         => $order->get_billing_phone()      
        ];        
        $contactId = hlwpw_get_location_contact_id($contact_data);

    }

    $contactEmail = $order->get_billing_email();

    // Add order note
    $order->add_order_note( "GHL contactId is: {$contactId} & GHL email is: {$contactEmail}" );

    // Get and Loop Over Order Items
    foreach ( $order->get_items() as $item_id => $item ) {
        
        $product_id             = $item->get_product_id();
        $product                = $item->get_product();
        $hlwpw_location_tags    = get_post_meta( $product_id, 'hlwpw_location_tags' );

        if ( !empty($hlwpw_location_tags) ) {
            
            $tags = [ 'tags' => $hlwpw_location_tags[0] ];
            hlwpw_loation_add_contact_tags($contactId, $tags);

            $tag_notes = implode(", ", $hlwpw_location_tags[0]);

            // Add order note
            $order->add_order_note( "Tag(s) \n" .  $tag_notes . "\nare sent to GHL." );
        }


        // Variation Tag
        // @from v1.1.02
        if ( $item->get_variation_id() ) {

            $variation_id   = $item->get_variation_id();
            $variation_tag  = get_post_meta( $variation_id, '_variation_tag', true );

            if ( $variation_tag ) {

                //Add Tag
                $tags = [ 'tags' => [$variation_tag] ];
                hlwpw_loation_add_contact_tags($contactId, $tags);

                $order->add_order_note( "variation Tag: \n" .  $variation_tag . "\nis sent to GHL." );

            }
        }
        
        $hlwpw_location_campaigns = get_post_meta( $product_id, 'hlwpw_location_campaigns' );

        if ( ! array_filter($hlwpw_location_campaigns) == []) {
            $hlwpw_location_campaigns = $hlwpw_location_campaigns[0];
            
            foreach ( $hlwpw_location_campaigns as $campaign_id ){
                hlwpw_loation_add_contact_to_campaign( $contactId, $campaign_id );
            }

            // Add order note
            $order->add_order_note( "Campaign list is sent to GHL");
        }

        $hlwpw_location_wokflow = get_post_meta( $product_id, 'hlwpw_location_wokflow' );

        if ( ! array_filter($hlwpw_location_wokflow) == []) {
            $hlwpw_location_wokflow = $hlwpw_location_wokflow[0];
            
            foreach ( $hlwpw_location_wokflow as $workflow_id ){
                hlwpw_loation_add_contact_to_workflow( $contactId, $workflow_id );
            }

            // Add order note
            $order->add_order_note( "Workflow list is sent to GHL");
        }


        // Add action to map product meta data
        do_action("lcw_update_product_meta", $product, $product_id, $contactId );

    }

    // Update Contact fields
    // from @v1.1.02

    $firstName  = !empty( $order->get_billing_first_name() ) ? $order->get_billing_first_name() : $order->get_shipping_first_name();
    $lastName   = !empty( $order->get_billing_last_name() ) ? $order->get_billing_last_name() : $order->get_shipping_last_name();
    $phone      = !empty( $order->get_billing_phone() ) ? $order->get_billing_phone() : $order->get_shipping_phone();
    $address1   = !empty( $order->get_billing_address_1() ) ? $order->get_billing_address_1() : $order->get_shipping_address_1();
    $city       = !empty( $order->get_billing_city() ) ? $order->get_billing_city() : $order->get_shipping_city();
    $state      = !empty( $order->get_billing_state() ) ? $order->get_billing_state() : $order->get_shipping_state();
    $postalCode = !empty( $order->get_billing_postcode() ) ? $order->get_billing_postcode() : $order->get_shipping_postcode();
    $country    = !empty( $order->get_billing_country() ) ? $order->get_billing_country() : $order->get_shipping_country();

    $contact_fields = array(
        'firstName' => $firstName,
        'lastName'  => $lastName,
        'phone'     => $phone,
        'address1'  => $address1,
        'city'      => $city,
        'state'     => $state,
        'postalCode'=> $postalCode,
        'country'   => $country
    );
    lcw_update_ghl_contact_fields_by_woocommerce_data($contactId, $contact_fields);

    // Create a hook to create invoice for this order
    do_action('lcw_create_ghl_invoice_on_order', $order, $order_id, $contactId);

    //Add a specific tag for each order
    $lcw_default_order_tag = get_option( 'lcw_default_order_tag', '' );
    if ( !empty( $lcw_default_order_tag ) ) {

        $tags = [ 'tags' => array ( $lcw_default_order_tag ) ];
        hlwpw_loation_add_contact_tags($contactId, $tags);
    }

    // Add action to map order meta data
    do_action("lcw_update_order_meta", $order, $order_id, $contactId );
    

    // Turn on sync
    // from @v1.1
    if ( 0 != $user_id ) {
        lcw_turn_on_contact_sync($user_id);
    }
}
add_action( 'woocommerce_order_status_changed', 'hlwpw_connect_to_ghl_based_on_order', 10, 3 );


function hlwpw_apply_tags_to_ghl_based_on_order_status( $order_id, $old_status, $new_status ){

    $order = wc_get_order($order_id);

    // updated @v1.1
    // get contact_id from database
    $user_id = $order->get_user_id();
    if ( 0 != $user_id ) {

        $contactId = lcw_get_contact_id_by_wp_user_id( $user_id );

    }else{

        $locationId = get_option( 'hlwpw_locationId' );    
        $contact_data = [
            "locationId"    => $locationId,
            "firstName"     => $order->get_billing_first_name(),
            "lastName"      => $order->get_billing_last_name(),
            "email"         => $order->get_billing_email(),
            "phone"         => $order->get_billing_phone()      
        ];        
        $contactId = hlwpw_get_location_contact_id($contact_data);

    }


    // Get and Loop Over Order Items
    foreach ( $order->get_items() as $item_id => $item ) {
        
        $product_id             = $item->get_product_id();
        $product                = $item->get_product();

        $hlwpw_order_status_tag    = get_post_meta( $product_id, 'hlwpw_order_status_tag', true );
        $hlwpw_order_status_tag    = ( !empty($hlwpw_order_status_tag) ) ? $hlwpw_order_status_tag :  [];
        $hlwpw_location_tags       = $hlwpw_order_status_tag[$new_status];

        if ( !empty($hlwpw_location_tags) ) {
            
            $tags = [ 'tags' => $hlwpw_location_tags ];
            hlwpw_loation_add_contact_tags($contactId, $tags);

            $tag_notes = implode(", ", $hlwpw_location_tags);

            // Add order note
            $order->add_order_note( "Tag(s) \n" .  $tag_notes . "\nare sent to GHL." );


            // Turn on sync
            // from @v1.1
            if ( 0 != $user_id ) {
                lcw_turn_on_contact_sync($user_id);
            }
        }

    }

}

add_action( 'woocommerce_order_status_changed', 'hlwpw_apply_tags_to_ghl_based_on_order_status', 10, 3 );