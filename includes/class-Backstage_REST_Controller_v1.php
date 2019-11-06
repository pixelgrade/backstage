<?php

class Backstage_REST_Controller_v1 extends WP_REST_Controller {
	const REST_NAMESPACE = 'backstage';
	const REST_FRONT_BASE = 'front';

	public function register_routes() {

		// This route should only be registered if it is enabled in the settings.
		if ( ! empty( Backstage_Plugin()->settings->get_option( 'enable_rest_api' ) ) ) {

			$args = array(
				'return_url'  => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => array( 'Backstage_REST_Controller_v1', 'sanitize_string' ),
					//'validate_callback' => ...
					'description'       => esc_html__( 'The return URL to use for the back button in the Customizer.', 'backstage' ),
				),
				'button_text' => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => array( 'Backstage_REST_Controller_v1', 'sanitize_string' ),
					'description'       => esc_html__( 'A custom text for the back button in the Customizer, to overwrite the one set in the plugin\'s settings just when using this link.', 'backstage' ),
					'default'           => '',
				),
				'extra_query_args' => array(
					'required'          => false,
					'type'              => 'array',
					'sanitize_callback' => array( 'Backstage_REST_Controller_v1', 'sanitize_extra_query_args' ),
					'description'       => esc_html__( 'Extra query args to add to the URL. This must be an associative array with the key being the parameter name.', 'backstage' ),
					'default'           => array(),
				),
			);

			// Add the required secret_key param if the secret key check is enabled.
			if ( Backstage_Plugin()->settings->get_option( 'enable_rest_api_secret_key' ) == 'on' ) {
				$args['secret_key']  = array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => array( 'Backstage_REST_Controller_v1', 'sanitize_string' ),
					'description'       => esc_html__( 'The secret key as set in the Backstage settings.', 'backstage' ),
				);
			}

			register_rest_route( Backstage_REST_Controller_v1::REST_NAMESPACE, '/' . Backstage_REST_Controller_v1::REST_FRONT_BASE . '/customizer_link', array(
				'methods'       => WP_REST_Server::READABLE,
				'callback'      => array( $this, 'get_customizer_link' ),
				'permission_callback' => array( $this, 'get_customizer_link_permissions_check' ),
				'args'          => $args,
				// This should be available in the index since it is public (API discovery)
				'show_in_index' => true,
			) );
		}
	}

	public function get_customizer_link_route_url() {
		return get_rest_url( null, Backstage_REST_Controller_v1::REST_NAMESPACE . '/' . Backstage_REST_Controller_v1::REST_FRONT_BASE . '/customizer_link' );
	}

	/**
	 * Check and verify the get customizer link request.
	 *
	 * @param WP_REST_Request $request
	 * @return false|int
	 */
	public function get_customizer_link_permissions_check( $request ) {
		$params = $request->get_params();

		// If the secret key check is enabled in the plugin settings, we need to receive it in a parameter and it must match.
		if ( Backstage_Plugin()->settings->get_option( 'enable_rest_api_secret_key' ) == 'on' ) {
			$secret_key = trim( Backstage_Plugin()->settings->get_option( 'rest_api_secret_key' ) );
			if ( ! isset( $params['secret_key'] ) || empty( $secret_key ) || $params['secret_key'] !== $secret_key ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_customizer_link( $request ) {

		$params = $request->get_params();

		if ( empty( $params['return_url'] ) ) {
			return rest_ensure_response( array(
				'code' => 'missing_return_url',
				'message' => 'You need to provide a return URL.',
				'data' => array(),
			) );
		}

		$return_url = $params['return_url'];
		$button_text = false;
		if ( ! empty( $params['button_text'] ) ) {
			$button_text = $params['button_text'];
		}

		$extra_query_args = array();
		if ( ! empty( $params['extra_query_args'] ) ) {
			$extra_query_args = $params['extra_query_args'];
		}

		return rest_ensure_response( array(
			'code'    => 'success',
			'message' => '',
			'data'    => array(
				'url' => backstage_get_customizer_link( $return_url, $button_text, $extra_query_args ),
			),
		) );
	}

	/**
	 * Jut a little bit of trimming.
	 *
	 * @param $string
	 * @param $request
	 * @param $param
	 *
	 * @return string
	 */
	public static function sanitize_string( $string, $request, $param ) {
		return trim( $string );
	}

	/**
	 * Make sure that we get an int.
	 *
	 * @param $int
	 * @param $request
	 * @param $param
	 *
	 * @return string
	 */
	public static function sanitize_int( $int, $request, $param ) {
		return intval( $int );
	}

	/**
	 * Make sure that we get a safe text.
	 *
	 * @param $text
	 * @param $request
	 * @param $param
	 *
	 * @return string
	 */
	public static function sanitize_text_field( $text, $request, $param ) {
		return sanitize_text_field( $text );
	}

	/**
	 * Make sure that we get safe extra query args.
	 *
	 * @param $extra_query_args
	 * @param $request
	 * @param $param
	 *
	 * @return array
	 */
	public static function sanitize_extra_query_args( $extra_query_args, $request, $param ) {
		if ( ! is_array( $extra_query_args ) || wp_is_numeric_array( $extra_query_args ) ) {
			return array();
		}

		$sanitized = array();
		foreach( $extra_query_args as $key => $value ) {
			$key = sanitize_text_field( $key );
			$value = sanitize_text_field( $value );

			$sanitized[ $key ] = $value;
		}

		return $sanitized;
	}
}
