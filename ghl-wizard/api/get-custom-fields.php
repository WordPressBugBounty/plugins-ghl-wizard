<?php

if ( ! function_exists( 'lcw_get_location_custom_fields' ) ) {
	function lcw_get_location_custom_fields() {
		$location_id = lcw_get_location_id();
		if ( empty( $location_id ) ) {
			return array();
		}

		$path = sprintf( '/locations/%s/customFields', rawurlencode( $location_id ) );

		return lcw_get_location_cached_dataset(
			'custom_fields',
			function() use ( $path ) {
				$result = LCW_GHL_API_Client::request(
					'GET',
					$path,
					array(
						'version' => '2021-07-28',
					)
				);

				if ( is_wp_error( $result ) || 200 !== $result['code'] ) {
					return null;
				}

				$body = $result['body'];
				if ( ! is_object( $body ) || empty( $body->customFields ) || ! is_array( $body->customFields ) ) {
					return array();
				}

				$custom_fields = array();
				foreach ( $body->customFields as $value_item ) {
					$field_key = isset( $value_item->fieldKey ) ? str_replace( 'contact.', '', $value_item->fieldKey ) : '';
					$field_id  = isset( $value_item->id ) ? $value_item->id : '';

					if ( '' !== $field_key && '' !== $field_id ) {
						$custom_fields[ $field_key ] = $field_id;
					}
				}

				return $custom_fields;
			},
			array()
		);
	}
}
