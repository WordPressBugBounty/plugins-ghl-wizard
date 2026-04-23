<?php

if ( ! class_exists( 'LCW_GHL_API_Client' ) ) {
	/**
	 * LeadConnector API client helper.
	 */
	class LCW_GHL_API_Client {
		const BASE_URL = 'https://services.leadconnectorhq.com';

		/**
		 * Send an API request.
		 *
		 * @param string $method HTTP method.
		 * @param string $path   Endpoint path.
		 * @param array  $args   Request options.
		 * @return array|WP_Error
		 */
		public static function request( $method, $path, $args = array() ) {
			$defaults = array(
				'body'                  => null,
				'headers'               => array(),
				'version'               => '2021-07-28',
				'query'                 => array(),
				'use_auth'              => true,
				'retry_on_unauthorized' => true,
				'decode_json'           => true,
			);

			$args = wp_parse_args( $args, $defaults );

			$endpoint     = self::build_endpoint( $path, $args['query'] );
			$request_args = self::build_request_args( $method, $args );

			$response = wp_remote_request( $endpoint, $request_args );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );

			if ( 401 === $code && ! empty( $args['use_auth'] ) && ! empty( $args['retry_on_unauthorized'] ) ) {
				hlwpw_get_new_access_token();
				$request_args['headers'] = self::build_headers(
					$args['version'],
					$args['headers'],
					! empty( $args['use_auth'] )
				);

				$response = wp_remote_request( $endpoint, $request_args );
				if ( is_wp_error( $response ) ) {
					return $response;
				}

				$code = (int) wp_remote_retrieve_response_code( $response );
			}

			$raw_body = wp_remote_retrieve_body( $response );
			$body     = $raw_body;

			if ( ! empty( $args['decode_json'] ) ) {
				$decoded_body = json_decode( $raw_body );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					$body = $decoded_body;
				}
			}

			return array(
				'code'     => $code,
				'body'     => $body,
				'raw_body' => $raw_body,
				'response' => $response,
			);
		}

		/**
		 * Get location cached collection dataset by response key.
		 *
		 * @param string $data_type     Dataset key.
		 * @param string $path          Endpoint path.
		 * @param string $response_key  Response body key.
		 * @param string $version       API version.
		 * @param array  $query         Query args.
		 * @param mixed  $default_value Default value.
		 * @return mixed
		 */
		public static function get_cached_collection_dataset( $data_type, $path, $response_key, $version, $query = array(), $default_value = array() ) {
			return lcw_get_location_cached_dataset(
				$data_type,
				function() use ( $path, $query, $version, $response_key ) {
					$result = self::request(
						'GET',
						$path,
						array(
							'query'   => $query,
							'version' => $version,
						)
					);

					if ( is_wp_error( $result ) || 200 !== $result['code'] ) {
						return null;
					}

					$body = $result['body'];
					if ( ! is_object( $body ) || ! isset( $body->{$response_key} ) || ! is_array( $body->{$response_key} ) ) {
						return array();
					}

					return $body->{$response_key};
				},
				$default_value
			);
		}

		/**
		 * Check whether code is successful.
		 *
		 * @param int   $status_code HTTP code.
		 * @param array $allowed     Allowed codes.
		 * @return bool
		 */
		public static function is_success( $status_code, $allowed = array( 200, 201 ) ) {
			return in_array( (int) $status_code, $allowed, true );
		}

		/**
		 * Build endpoint URL.
		 *
		 * @param string $path  Endpoint path.
		 * @param array  $query Query arguments.
		 * @return string
		 */
		private static function build_endpoint( $path, $query = array() ) {
			$endpoint = untrailingslashit( self::BASE_URL ) . '/' . ltrim( (string) $path, '/' );

			if ( ! empty( $query ) ) {
				$endpoint = add_query_arg( $query, $endpoint );
			}

			return $endpoint;
		}

		/**
		 * Build request arguments.
		 *
		 * @param string $method HTTP method.
		 * @param array  $args   Request args.
		 * @return array
		 */
		private static function build_request_args( $method, $args ) {
			$request_args = array(
				'method'  => strtoupper( $method ),
				'headers' => self::build_headers( $args['version'], $args['headers'], ! empty( $args['use_auth'] ) ),
			);

			if ( null !== $args['body'] ) {
				$request_args['body'] = $args['body'];
			}

			return $request_args;
		}

		/**
		 * Build request headers.
		 *
		 * @param string $version        API version.
		 * @param array  $custom_headers Custom headers.
		 * @param bool   $use_auth       Whether to include bearer token.
		 * @return array
		 */
		private static function build_headers( $version, $custom_headers = array(), $use_auth = true ) {
			$headers = array(
				'Accept'  => 'application/json',
				'Version' => $version,
			);

			if ( $use_auth ) {
				$headers['Authorization'] = 'Bearer ' . lcw_get_access_token();
			}

			if ( ! empty( $custom_headers ) ) {
				$headers = array_merge( $headers, $custom_headers );
			}

			return $headers;
		}
	}
}
