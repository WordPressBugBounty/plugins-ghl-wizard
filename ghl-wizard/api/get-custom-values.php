<?php

if ( ! function_exists( 'hlwpw_get_location_custom_values' ) ) {
	function hlwpw_get_location_custom_values() {
		$location_id = lcw_get_location_id();
		if ( empty( $location_id ) ) {
			return array();
		}

		$path = sprintf( '/locations/%s/customValues', rawurlencode( $location_id ) );

		return lcw_get_location_cached_dataset(
			'custom_values',
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
				if ( ! is_object( $body ) || empty( $body->customValues ) || ! is_array( $body->customValues ) ) {
					return array();
				}

				$custom_values = array();
				foreach ( $body->customValues as $value_item ) {
					$field_key = isset( $value_item->fieldKey ) ? $value_item->fieldKey : '';
					$field_key = str_replace( '{{', '', $field_key );
					$field_key = trim( str_replace( '}}', '', $field_key ) );
					$field_key = str_replace( 'custom_values.', '', $field_key );

					if ( '' !== $field_key ) {
						$custom_values[ $field_key ] = isset( $value_item->value ) ? $value_item->value : '';
					}
				}

				return $custom_values;
			},
			array()
		);
	}
}
