<?php
/**
 * Document for class Backstage_Settings.
 *
 * @package Backstage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle the settings page logic.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 * @since       1.0.0
 */
class Backstage_Settings extends Backstage_Singleton_Registry {

	/**
 	 * Option key, and, at the same time, option page slug
 	 * @var string
 	 */
	public static $key = null;

	/**
	 * Settings Page title
	 * @var string
	 * @access protected
	 * @since 1.0.0
	 */
	protected $title = '';

	/**
	 * Settings Page hook
	 * @var string
	 * @access protected
	 * @since 1.0.0
	 */
	protected $options_page = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {

		self::$key = $this->prefix('options' );

		// Set our settings page title.
		$this->title = esc_html__( 'Backstage Setup', 'backstage' );

		$this->add_hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since 1.0.0
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'cmb2_admin_init', array( $this, 'cmb2_init' ) );

		// For when the plugin is used on a per site basis
		add_action( 'load-appearance_page_' . self::$key, array( $this, 'register_admin_scripts' ) );
		// For when the plugin is network activated
		add_action( 'load-settings_page_' . self::$key, array( $this, 'register_admin_scripts' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Do general things on the `init` hook.
	 *
	 * @since  1.0.0
	 */
	public function init() {

	}

	/**
	 * Register the fields, tabs, etc for our settings page.
	 *
	 * @since  1.0.0
	 */
	public function cmb2_init() {
		// If we are in a multisite context and the plugin is network activated (rather than on individual blogs in the network),
		// We will only add a network-wide settings page.

		$box_args = array(
			'id'           => $this->prefix( 'setup_page' ),
			'title'        => $this->title,
			'desc'         => 'description',
			'object_types' => array( 'options-page' ),
			'option_key'   => self::$key, // The option key and admin menu page slug.
			'menu_title'   => esc_html__( 'Backstage', 'backstage' ),
			'autoload'     => true,
			'show_in_rest' => false,
		);

		if ( Backstage_Plugin()->is_plugin_network_activated() ) {
			$box_args['admin_menu_hook'] = 'network_admin_menu'; // 'network_admin_menu' to add network-level options page.
			$box_args['parent_slug'] = 'settings.php'; // Make options page a submenu item of the settings menu.
		} else {
			$box_args['parent_slug'] = 'themes.php'; // Make options page a submenu item of the themes menu.
			$box_args['capability'] = 'manage_options';
		}


		$cmb = new_cmb2_box( apply_filters( 'backstage_cmb2_box_args', $box_args ) );

		/* ================================
		 * Fields for Customizer behavior.
		 * ================================ */

		$cmb->add_field( array(
			'name' => esc_html__( 'Customizer Behavior', 'backstage' ),
			'desc' => 'Setup how things will behave once the guest user is in the Customizer.',
			'id'   => $this->prefix( 'customizer_title' ),
			'type' => 'title',
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Button Text', 'backstage' ),
			'desc' => esc_html__( 'Input the text of the button at the top of the Customizer sidebar (that replaces the Publish button). This button will bring the visitor back to the URL it entered the Customizer from.', 'backstage' ),
			'id'   => $this->prefix( 'customizer_back_button_text' ),
			'type' => 'text',
			'default' => esc_html__( 'Back to Demo', 'backstage' ),
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Notice Style', 'backstage' ),
			'desc' => esc_html__( 'Set the style/type of the Customizer notice. If "Custom", you can target the notification with the ".notice-backstage-custom" CSS selector.', 'backstage' ),
			'id'   => $this->prefix( 'customizer_notice_type' ),
			'type' => 'select',
			'default' => 'info',
			'options' => array(
				'info'        => esc_html__( 'Info', 'backstage' ),
				'warning'     => esc_html__( 'Warning', 'backstage' ),
				'success'     => esc_html__( 'Success', 'backstage' ),
				'error'       => esc_html__( 'Error', 'backstage' ),
				'backstage-custom' => esc_html__( 'Custom', 'backstage' ),
			),
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Notice HTML', 'backstage' ),
			'desc' => esc_html__( 'Set the text or HTML of the Customizer notice. Leave empty if you don\'t want to show a notification.', 'backstage' ),
			'id'   => $this->prefix( 'customizer_notice_text' ),
			'type' => 'textarea_code',
			'default' => wp_kses_post( __( '<b>Demo Mode</b><p>You can\'t upload images and save settings.</p>', 'backstage' ) ),
			'attributes' => array(
				'rows' => 3,
				'data-codeeditor' => json_encode( array(
					'codemirror' => array(
						'mode' => 'text/html',
					),
				) ),
			),
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Dismissible Notice', 'backstage' ),
			'desc' => esc_html__( 'Decide if the notice should be dismissible by the user or not. It will only be dismissed for the current session.', 'backstage' ),
			'id'   => $this->prefix( 'customizer_notice_dismissible' ),
			'type' => 'checkbox',
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Hide Customizing Info', 'backstage' ),
			'desc' => esc_html__( 'Check to hide the top Customizer sidebar info that starts with "You are customizing...".', 'backstage' ),
			'id'   => $this->prefix( 'customizer_hide_info' ),
			'type' => 'checkbox',
		) );

		/* ================================
		 * Fields for frontend output.
		 * ================================ */

		$cmb->add_field( array(
			'name' => esc_html__( 'Frontend Behavior', 'backstage' ),
			'desc' => 'Setup how things will behave when the guest visits your site.',
			'id'   => $this->prefix( 'frontend_title' ),
			'type' => 'title',
		) );

		$cmb->add_field( array(
			'name'             => esc_html__( 'Output Mode', 'backstage' ),
			'desc'             => esc_html__( 'Here you can decide if you want us to output a button on the frontend with a link to the Customizer, or if you want to do that yourself.', 'backstage' ),
			'id'               => $this->prefix( 'frontend_button_mode' ),
			'type'             => 'select',
			'show_option_none' => false,
			'default'          => 'auto',
			'options'          => array(
				'auto'   => esc_html__( 'Output a button for me', 'backstage' ),
				'custom' => esc_html__( 'Let me specify the button markup and CSS', 'backstage' ),
				'self'   => esc_html__( 'I will handle the button myself', 'backstage' ),
			),
		) );

		/* ================================
		 * Fields for auto frontend output.
		 * ================================ */

		$cmb->add_field( array(
			'name' => esc_html__( 'Button Text', 'backstage' ),
			'desc' => esc_html__( 'Set here the text for the frontend button.', 'backstage' ),
			'id'   => $this->prefix( 'frontend_button_text' ),
			'type' => 'text',
			'default' => esc_html__( 'Customize Styles', 'backstage' ),
			'attributes' => array(
				'required'               => true, // Will be required only if visible.
				'data-conditional-id'    => $this->prefix( 'frontend_button_mode' ),
				'data-conditional-value' => 'auto',
			),
		) );
		$cmb->add_field( array(
			'name' => esc_html__( 'Button Classes', 'backstage' ),
			'desc' => esc_html__( 'Set here custom class(es) for the frontend button. If multiple, please separate them with a comma and a space.', 'backstage' ),
			'id'   => $this->prefix( 'frontend_button_classes' ),
			'type' => 'text',
			'default' => '',
			'attributes' => array(
				'required'               => false,
				'data-conditional-id'    => $this->prefix( 'frontend_button_mode' ),
				'data-conditional-value' => 'auto',
			),
		) );
		$cmb->add_field( array(
			'name' => esc_html__( 'Button Wrapper Classes', 'backstage' ),
			'desc' => esc_html__( 'Set here custom class(es) for the frontend button wrapper. If multiple, please separate them with a comma and a space.', 'backstage' ),
			'id'   => $this->prefix( 'frontend_button_wrapper_classes' ),
			'type' => 'text',
			'default' => '',
			'attributes' => array(
				'required'               => false,
				'data-conditional-id'    => $this->prefix( 'frontend_button_mode' ),
				'data-conditional-value' => 'auto',
			),
		) );

		/* ==================================
		 * Fields for custom frontend output.
		 * ================================== */

		$cmb->add_field( array(
			'name' => esc_html__( 'Custom HTML', 'backstage' ),
			'desc' => sprintf( esc_html__( 'Add here the custom HTML you want to output on the frontend of your site. You must include the %s content tag so it can be replaced with the URL for Customizer access.', 'backstage' ), '<code>%customizer_link%</code>'),
			'id'   => $this->prefix( 'frontend_custom_html' ),
			'type' => 'textarea_code',
			'default' => '<div class="backstage-customizer-access-wrapper">
	<div class="backstage-customizer-access-button">
		<a class="backstage-customizer-access-link" href="%customizer_link%">Customize Styles</a>
	</div>
</div>',
			'attributes' => array(
				'required'               => true, // Will be required only if visible.
				'data-conditional-id'    => $this->prefix( 'frontend_button_mode' ),
				'data-conditional-value' => 'custom',

				'data-codeeditor' => json_encode( array(
					'codemirror' => array(
						'mode' => 'text/html',
					),
				) ),
			),
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Custom CSS', 'backstage' ),
			'desc' => esc_html__( 'Add here the custom CSS you want to output on the frontend of your site. It\'s OK to leave it empty if you have the CSS elsewhere.', 'backstage' ),
			'id'   => $this->prefix( 'frontend_custom_css' ),
			'type' => 'textarea_code',
			'default' => '.backstage-customizer-access-wrapper {
	position: fixed;
	bottom: 0;
	right: 0;
	z-index: 1000;
}
.backstage-customizer-access-button {
	font-size: 16px;
	font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	float: right;
	background-color: #2196f3;
}
.backstage-customizer-access-button:hover {
	background-color: #1a70b5;
}
.backstage-customizer-access-button a {
	padding: 10px 14px;
    float: right;
	color: #fff;
	font-weight: normal !important;
	-webkit-font-smoothing: subpixel-antialiased !important;
	border-bottom: none; 
	text-decoration: none;
}
.backstage-customizer-access-button a:before {
	content: "";
	position: relative;
	top: 4px;
	display: inline-block;
	height: 18px;
	width: 18px;
	margin-right: 8px;
	background-image: url("data:image/svg+xml,%3Csvg class=\'feather feather-sliders\' fill=\'%23fff\' stroke=\'%23fff\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' viewBox=\'0 0 24 24\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cline x1=\'4\' x2=\'4\' y1=\'21\' y2=\'14\'/%3E%3Cline x1=\'4\' x2=\'4\' y1=\'10\' y2=\'3\'/%3E%3Cline x1=\'12\' x2=\'12\' y1=\'21\' y2=\'12\'/%3E%3Cline x1=\'12\' x2=\'12\' y1=\'8\' y2=\'3\'/%3E%3Cline x1=\'20\' x2=\'20\' y1=\'21\' y2=\'16\'/%3E%3Cline x1=\'20\' x2=\'20\' y1=\'12\' y2=\'3\'/%3E%3Cline x1=\'1\' x2=\'7\' y1=\'14\' y2=\'14\'/%3E%3Cline x1=\'9\' x2=\'15\' y1=\'8\' y2=\'8\'/%3E%3Cline x1=\'17\' x2=\'23\' y1=\'16\' y2=\'16\'/%3E%3C/svg%3E");
	background-position: center;
	background-repeat: no-repeat;
}',
			'attributes' => array(
				'required'               => false,
				'data-conditional-id'    => $this->prefix( 'frontend_button_mode' ),
				'data-conditional-value' => 'custom',

				'data-codeeditor' => json_encode( array(
					'codemirror' => array(
						'mode' => 'text/css',
					),
				) ),
			),
		) );

		/* ================================
		 * Fields for self frontend output.
		 * ================================ */

		$cmb->add_field( array(
			'name' => esc_html__( 'Custom Button Instructions', 'backstage' ),
			'desc' => esc_html__( 'Since you wish to have control and handle your own button, we will make it easy for you. You use the "backstage_get_customizer_link()" PHP function to get the link to the Customizer. Output it directly or send it to JS via a localized variable.', 'backstage' ),
			'id'   => $this->prefix( 'frontend_custom_button_instructions' ),
			'type' => 'title',
			'attributes' => array(
				'data-conditional-id'    => $this->prefix( 'frontend_button_mode' ),
				'data-conditional-value' => 'self',
			),
		) );

		/* ================================
		 * Fields for advanced settings.
		 * ================================ */

		$cmb->add_field( array(
			'name' => esc_html__( 'Advanced', 'backstage' ),
			'desc' => esc_html__( 'Advanced options that you should take extra care when modifying them.', 'backstage' ),
			'id'   => $this->prefix( 'advanced_title' ),
			'type' => 'title',
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'URL Auto-login Key', 'backstage' ),
			'desc' => esc_html__( 'Set the key (parameter name) that will be used to auto-login the visitor and gain access to the Customizer.', 'backstage' ),
			'id'   => $this->prefix( 'auto_login_key' ),
			'type' => 'text',
			'default' => Backstage::$default_auto_login_key,
			'attributes' => array(
				'required' => true,
			),
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Enable REST API Endpoint', 'backstage' ),
			'desc' => esc_html__( 'Enable a REST API endpoint for dynamically fetching the secure URL to access the Customizer.', 'backstage' ),
			'id'   => $this->prefix( 'enable_rest_api' ),
			'type' => 'checkbox',
		) );

		if ( Backstage_Plugin()->is_plugin_network_activated() ) {
			$rest_route_url_details = __( '<p>The plugin is network activated, but each blog in the network will have its own REST endpoint for fetching the customizer link.</p><p>Here is an example endpoint URL: <code>' . Backstage_Plugin()->rest_api->get_customizer_link_route_url() . '</code></p><p>To get the link for each blog in the network, replace the part before <code>/wp-json/</code> with the home URL of that blog.</p>', 'backstage');
		} else {
			$rest_route_url_details = __( '<p>Here is the endpoint URL to use: <code>' . Backstage_Plugin()->rest_api->get_customizer_link_route_url() . '</code></p>', 'backstage');
		}
		$rest_route_url_details .= __( '<p>Use the "GET" HTTP method. The successful response will be a JSON, with the following structure:</p>', 'backstage' );
		$rest_route_url_details .= '<p><pre>
{ 
   "code":"success",
   "message":"",
   "data":{ 
      "url":"the-customizer-url-ready-to-use"
   }
}
</pre></p>';

		$cmb->add_field( array(
			'name' => '',
			'desc' => wp_kses_post( $rest_route_url_details ),
			'id'   => $this->prefix( 'rest_route_url_title' ),
			'type' => 'title',
			'attributes' => array(
				'data-conditional-id'    => $this->prefix( 'enable_rest_api' ),
				'data-conditional-value' => 'on',
			),
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'REST API Secret Key Check', 'backstage' ),
			'desc' => esc_html__( 'Enable this to only provide the customizer link response to REST API requests that provide the secret key bellow.', 'backstage' ),
			'id'   => $this->prefix( 'enable_rest_api_secret_key' ),
			'type' => 'checkbox',
			'attributes' => array(
				'data-conditional-id'    => $this->prefix( 'enable_rest_api' ),
				'data-conditional-value' => 'on',
			),
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Secret Key', 'backstage' ),
			'desc' => esc_html__( 'Set a secret (private) key that must be provided in each REST API request (under the name `secret_key`). In a Multisite installation, with the plugin network activated, this key is shared across all blogs in the network.', 'backstage' ),
			'id'   => $this->prefix( 'rest_api_secret_key' ),
			'type' => 'text',
			'default' => $this->get_rest_api_default_secret_key(),
			'attributes' => array(
				'data-conditional-id'    => $this->prefix( 'enable_rest_api_secret_key' ),
				'data-conditional-value' => 'on',
			),
		) );

	}

	protected function get_rest_api_default_secret_key() {
		$default_secret_key = $this->get_option( 'rest_api_default_secret_key' );
		if ( empty( $default_secret_key ) ) {
			// We will save the default in the DB so it stays the same, but unique to each installation.
			$default_secret_key = wp_generate_password( 20, false, false );
			$this->update_option( 'rest_api_default_secret_key', $default_secret_key );
		}

		return $default_secret_key;
	}

	public function is_settings_page() {
		$current_screen = get_current_screen();

		if ( ! empty( $current_screen ) && $current_screen instanceof WP_Screen ) {
			if ( in_array( $current_screen->id, array( 'appearance_page_backstage_options', 'settings_page_backstage_options-network' ) ) ) {
				return true;
			}
		}

		return false;
	}

	public function register_admin_scripts() {
		// The styles.
		wp_register_style( $this->prefix( 'admin-style' ), plugins_url( 'assets/css/admin.css', Backstage_Plugin()->get_file() ), array(), Backstage_Plugin()->get_version() );

		wp_register_script( $this->prefix( 'settings-js' ), plugins_url( 'assets/js/settings-page.js', Backstage_Plugin()->get_file() ),
			array(
				'jquery',
				$this->prefix( 'cmb2-conditionals' ),
				'wp-api',
			), Backstage_Plugin()->get_version() );
	}

	public function enqueue_admin_scripts() {
		if ( ! $this->is_settings_page() ) {
			return;
		}

		// The styles.
		wp_enqueue_style( $this->prefix( 'admin-style' ) );

		// The scripts.
		wp_enqueue_script( $this->prefix( 'settings-js' ) );
	}

	/**
	 * Retrieves an option, even before the CMB2 has loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id
	 * @param mixed $default Optional. The default value in case the option wasn't saved.
	 * @return mixed
	 */
	public function get_option( $id, $default = false ) {
		if ( Backstage_Plugin()->is_plugin_network_activated() ) {
			$options = get_site_option( self::$key );
		} else {
			$options = get_option( self::$key );
		}
		if ( isset( $options[ $this->prefix( $id ) ] ) ) {
			return $options[ $this->prefix( $id ) ];
		}
		return $default;
	}

	/**
	 * Update an option, even before the CMB2 has loaded.
	 *
	 * @since 1.3.0
	 *
	 * @param string $id
	 * @param mixed $value
	 */
	public function update_option( $id, $value ) {
		if ( Backstage_Plugin()->is_plugin_network_activated() ) {
			$options = get_site_option( self::$key );
			if ( empty( $options ) ) {
				$options = array();
			}
			$options[ $this->prefix( $id ) ] = $value;
			update_site_option( self::$key, $options );
		} else {
			$options = get_option( self::$key );
			if ( empty( $options ) ) {
				$options = array();
			}
			$options[ $this->prefix( $id ) ] = $value;
			update_option( self::$key, $options );
		}
	}

	/**
	 * Public getter method for retrieving protected/private properties.
	 *
	 * @throws Exception
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

	/**
	 * Adds a prefix to an option name.
	 *
	 * @param string $option
	 * @param bool $private Optional. Whether this option name should also get a '_' in front, marking it as private.
	 *
	 * @return string
	 */
	public function prefix( $option, $private = false ) {
		$option = backstage_prefix( $option );

		if ( true === $private ) {
			return '_' . $option;
		}

		return $option;
	}

	/**
	 * Remove any data we may have in the DB. This is intended to only be called on plugin uninstall.
	 */
	public static function cleanup() {

		if ( is_multisite() ) {
			delete_site_option( self::$key );

			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $site_id ) {
				delete_blog_option( $site_id, self::$key );
			}
		} else {
			delete_option( self::$key );
		}
	}
}
