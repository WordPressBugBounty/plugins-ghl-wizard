<?php


	use SureCart\Integrations\Contracts\IntegrationInterface;
	use SureCart\Integrations\Contracts\PurchaseSyncInterface;
	use SureCart\Integrations\IntegrationService;

	class LC_Wizard_GHL_Integration extends IntegrationService implements IntegrationInterface, PurchaseSyncInterface {


	/**
	 * The name for the integration.
	 * Names have to be structured as namespace/integration-name, where namespace is the name of your plugin or theme.
	 *
	 * @return string
	 */
	public function getName() {
		return 'hlwpw/ghl_integration';
	}

	/**
	 * Get the SureCart model used for the integration.
	 * Only 'product' is supported at this time.
	 *
	 * @return string
	 */
	public function getModel() {
		return 'product';
	}

	/**
	 * Get the integration logo url.
	 * This url needs to be an absolute url to png, jpg, webp or svg.
	 *
	 * @return string
	 */
	public function getLogo() {
		return esc_url_raw( trailingslashit( plugin_dir_url( __FILE__ ) ) . '../images/ghl-large.png' );
	}

	/**
	 * The display name for the integration in the dropdown.
	 * This is displayed in a dropdown menu when a merchant selects an integration.
	 *
	 * @return string
	 */
	public function getLabel() {
		return __( 'LC Tag Integration', 'surecart' );
	}

	/**
	 * The label for the integration item that will be chosen.
	 * This is displayed in the second dropdown after a person selects your integration.
	 *
	 * @return string
	 */
	public function getItemLabel() {
		return __( 'Apply a tag', 'surecart' );
	}

	/**
	 * Help text for the integration item chooser.
	 * Additional help text for the integration item chooser.
	 *
	 * @return string
	 */
	public function getItemHelp() {
		return __( 'Apply a tag when a successful purchase is made. The tag will be removed if a subscription is canceled and reapplied if the subscription is reactivated.', 'surecart' );
	}

	protected function get_tags( $id = null ){
		$ghl_tags = hlwpw_get_location_tags();

		$tags = [];
		foreach ($ghl_tags as $key => $value) {

			$sub['id']      = $value->id;
		 	$sub['label']   = $value->name;

			$tags[$value->id] =  $sub;
		}

		return $tags;
	}


	/**
	 * @param array $items The integration items.
	 * @param string $search The search term.
	 *
	 * @return array The items for the integration.
	 */
	public function getItems( $items = [], $search = '' ) {

		return $this->get_tags();
	}

	/**
	 * Get the individual item.
	 *
	 * @param string $id The item role.
	 *
	 * @return array The item for the integration.
	 */
	public function getItem( $id ) {

		$data = get_option( 'leadconnectorwizardpro_license_options' );

		if ( ! isset( $data['sc_activation_id'] ) ) {
			return [
				'id'    => $id,
				'label' => $this->get_tags()[$id]['label'] . " (This is a premium feature, won't work on free version)",
			];
		}

		return [
			'id'    => $id,
			'label' => $this->get_tags()[$id]['label'],
		];
	}
	
	
	protected function onPurchaseCreatedAndInvoked( $integration, $wp_user ) {
	    
	    $user_id = $wp_user->ID;
		$email = $wp_user->email;
		
		// get contact ID
		$contact_id = lcw_get_contact_id_by_wp_user_id( $user_id );
		

		// if a new user upsert it
		if ( ! $contact_id ) {
			
			$location_id = lcw_get_location_id();
			$first_name  = ! empty( get_user_meta( $user_id, 'first_name', true ) ) ? get_user_meta( $user_id, 'first_name', true ) : $user->display_name;
			$last_name   = get_user_meta( $user_id, 'last_name', true );

			$contact_data = array(
				'locationId' => $location_id,
				'firstName'  => $first_name,
				'lastName'   => $last_name,
				'email'      => $email,
			);

			$contact_id = hlwpw_get_location_contact_id($contact_data);

		}

		// apply tag
		$tag_name = $this->get_tags()[$integration->integration_id]['label'];
		$tags = [ 'tags' => [$tag_name] ];

		return hlwpw_loation_add_contact_tags($contact_id, $tags, $user_id);

	}


	/**
	 * @param \SureCart\Models\Integration $integration The integrations.
	 * @param \WP_User                     $wp_user The user.
	 *
	 * @return boolean|void Returns true if the user course access updation was successful otherwise false.
	 */
	public function onPurchaseCreated( $integration, $wp_user ) {
	    
	    $data = get_option( 'leadconnectorwizardpro_license_options' );

		if ( isset( $data['sc_activation_id'] ) ) {
			$this->onPurchaseCreatedAndInvoked( $integration, $wp_user );
		}
	}

	/**
	 * @param \SureCart\Models\Integration $integration The integrations.
	 * @param \WP_User                     $wp_user The user.
	 *
	 * @return boolean|void Returns true if the user course access updation was successful otherwise false.
	 */
	public function onPurchaseInvoked( $integration, $wp_user ) {
		
		$this->onPurchaseCreatedAndInvoked( $integration, $wp_user );
	}

	/**
	 * @param \SureCart\Models\Integration $integration The integrations.
	 * @param \WP_User                     $wp_user The user.
	 *
	 * @return boolean|void Returns true if the user course access updation was successful otherwise false.
	 */
	public function onPurchaseRevoked( $integration, $wp_user ) {
	    
		$user_id = $wp_user->ID;
		$email = $wp_user->email;
		
		// get contact ID
		$contact_id = lcw_get_contact_id_by_wp_user_id( $user_id );
		

		// if a new user upsert it
		if ( ! empty ($contact_id) ) {
			
			// Remove tag
    		$tag_name = $this->get_tags()[$integration->integration_id]['label'];
    		$tags = [ 'tags' => [$tag_name] ];
    
    		return hlwpw_loation_remove_contact_tags($contact_id, $tags, $user_id);

		}
		
	}
}


// bootstrap the integration.
(new LC_Wizard_GHL_Integration())->bootstrap();