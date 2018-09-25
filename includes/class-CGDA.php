<?php
/**
 * Document for class CGDA.
 *
 * @package Customizer-Guest-Demo-Access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CGDA' ) ) :

/**
 * This is the class that handles the overall logic for the Customizer Guest Demo Access.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 * @since       1.0.0
 */
class CGDA extends CGDA_Singleton_Registry {

	public static $username = 'cgda_customizer_user';
	public static $user_role = 'cgda-customizer-demo-access';

	public static $default_auto_login_key = 'cgda_auto_login';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param $parent
	 */
	protected function __construct( $parent = null ) {
		// We need to initialize at this action so we can do some checks before hooking up.
		add_action( 'plugins_loaded', array( $this, 'init' ), 1 );
	}

	/**
	 * Initialize this module.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		if ( ! self::check_setup() ) {
			return;
		}

		// Hook up.
		$this->add_hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since 1.0.0
	 */
	public function add_hooks() {

		/* =========================================================
		 * Handle the main logic regarding accessing the Customizer.
		 */

		// Auth and Show Customizer
		add_action( 'plugins_loaded', array( $this, 'maybe_auto_login' ), 2 );
		add_action( 'plugins_loaded', array( $this, 'adjust_default_behaviours' ), 10 );

		// Disable wp-admin access.
		// We need to hook late so we can let WordPress handle 404 and such.
		add_action( 'wp', array( $this, 'clear_user_auth' ) );
		add_action( 'admin_init', array( $this, 'clear_user_auth' ) );
		add_filter( 'woocommerce_prevent_admin_access', array( $this, 'wc_prevent_admin_access' ) );

		// Remove Customizer Ajax Save Action
		add_action( 'admin_init', array( $this, 'remove_customize_save_action' ) );

		// Prevent Changeset Save
		add_filter( 'customize_changeset_save_data', array( $this, 'prevent_changeset_save' ), 50, 2 );

		// Remove the switch theme panel from the Customizer.
		add_action( 'customize_register', array( $this, 'remove_switch_theme_panel' ), 12 );

		/* ===================================
		 * Scripts enqueued in the Customizer.
		 */
		add_action( 'customize_controls_init', array( $this, 'register_admin_customizer_scripts' ), 10 );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_admin_customizer_scripts' ), 10 );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'customize_controls_templates' ) );

		/* =================================
		 * Markup outputted in the frontend.
		 */
		add_action( 'wp_footer', array( $this, 'frontend_output' ) );
	}

	/**
	 * Check if everything needed for the core plugin logic is in place.
	 *
	 * @return bool
	 */
	public static function check_setup() {

		$all_good = true;
		if ( ! get_role( self::$user_role ) ) {
			$all_good = false;
			CGDA_Plugin::add_admin_notice( array( 'CGDA', 'user_role_missing_error_notice' ) );
		}

		if ( ! username_exists( self::$username ) ) {
			$all_good = false;
			CGDA_Plugin::add_admin_notice( array( 'CGDA', 'user_missing_error_notice' ) );
		}

		return $all_good;
	}

	public static function maybe_create_user_role() {

		if ( ! get_role( self::$user_role ) ) {

			// Customizer access user capabilities.
			$user_capabilities = apply_filters( 'cgda_user_capabilities', array(
				'read'               => true,
				'edit_posts'         => false,
				'delete_posts'       => false,
				'edit_pages'         => false,
				'edit_theme_options' => false,
				'manage_options'     => false,
				'customize'          => true,
			) );

			add_role( self::$user_role, esc_html__( 'Customizer Preview', 'cgda' ), $user_capabilities );
		}
	}

	public static function maybe_remove_user_role() {

		if ( get_role( self::$user_role ) ) {
			remove_role( self::$user_role );
		}
	}

	public static function user_role_missing_error_notice() {
		?>
		<div class="error notice">
			<p><?php esc_html_e( 'Something is wrong! We couldn\'t find the user role needed for Customizer Guest Demo Access. Try to deactivate and activate the plugin again.', 'cgda' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Create Customizer user if user not exists.
	 */
	public static function maybe_create_customizer_user() {

		if ( ! username_exists( self::$username ) ) {
			// Generate a random password. This is not actually used anywhere, so no one needs to know it.
			$password = wp_generate_password();

			$new_user_data = array(
				'user_login' => self::$username,
				'user_pass'  => $password,
				'role'       => self::$user_role,
			);

			$user_id = wp_insert_user( $new_user_data );
			if ( empty( $user_id ) || is_wp_error( $user_id ) ) {
				CGDA_Plugin::add_admin_notice( array( 'CGDA', 'user_creation_error_notice' ) );
				return;
			}

			// If we are in a multisite install, we need to add the user to all the of the sites.
			if ( is_multisite() ) {
				$sites = get_sites( array( 'fields' => 'ids' ) );
				foreach ( $sites as $site_id ) {
					add_user_to_blog( $site_id, $user_id, self::$user_role );
				}
			}
		}
	}

	public static function multisite_maybe_add_user_to_new_blog( $site_id ) {
		$user = get_user_by( 'login', self::$username );
		if ( ! $user ) {
			return;
		}

		add_user_to_blog( $site_id, $user->ID, self::$user_role );
	}

	public static function user_creation_error_notice() {
		?>
		<div class="error notice">
			<p><?php esc_html_e( 'Something is very wrong! We couldn\'t create the user needed for Customizer Guest Demo Access.', 'cgda' ); ?></p>
		</div>
		<?php
	}

	public static function user_missing_error_notice() {
		?>
		<div class="error notice">
			<p><?php esc_html_e( 'Something is very wrong! We couldn\'t find the user needed for Customizer Guest Demo Access. Try to deactivate and activate the plugin again.', 'cgda' ); ?></p>
		</div>
		<?php
	}

	public static function maybe_remove_customizer_user() {

		$user = get_user_by( 'login', self::$username );
		if ( ! $user ) {
			return;
		}

		// If we are in a multisite install, we need to remove the user from all the of the sites.
		if ( is_multisite() ) {
			wpmu_delete_user( $user->ID );
		} else {
			wp_delete_user( $user->ID );
		}
	}

	public function wc_prevent_admin_access( $default ) {

		return ( is_customize_preview() && $this->is_customizer_user() ) ? false : $default;
	}

	/**
	 * Is this user allowed to see the Customizer.
	 *
	 * @return bool
	 */
	public function is_customizer_user() {

		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user = wp_get_current_user();

		return apply_filters( 'cgda_is_customizer_user', in_array( self::$user_role, $user->roles ) );
	}

	/**
	 * Auto login Customizer pseudo-guest user.
	 */
	public function customizer_user_auth() {

		$user = get_user_by( 'login', self::$username );
		if ( $user ) {
			$secure_cookie = is_ssl() ? true : false;

			wp_set_current_user( $user->ID );

			wp_set_auth_cookie( $user->ID, true, $secure_cookie );
			do_action( 'wp_login', $user->user_login, $user );
		}
	}

	/**
	 * Logout the pseudo-guest user.
	 */
	public function clear_user_auth() {

		// If one is a customizer user and navigates away from it, we will log him out.
		$should_auto_logout = ! defined( 'DOING_AJAX' ) &&
		                      $this->is_customizer_user() &&
		                      ! is_customize_preview() &&
		                      ! is_404() &&
		                      ! ( is_admin() && 'customize.php' == basename( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ) );
		// Allow others to have a say in this.
		if ( true === apply_filters( 'cgda_should_auto_logout_user', $should_auto_logout ) ) {
			wp_logout();

			do_action( 'cgda_did_auto_logout' );

			wp_safe_redirect( esc_url( home_url( '/' ) ) );
			die();
		}
	}

	/**
	 * If we are accessing the Customizer, we need to check if we should auto-login.
	 */
	public function maybe_auto_login() {

		if ( is_admin() && 'customize.php' == basename( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ) ) {
			// Check if we need to auto-login the guest.
			$should_auto_login = $this->check_customizer_link() && ! is_user_logged_in();
			if ( true === apply_filters( 'cgda_customizer_should_auto_login', $should_auto_login ) ) {
				$this->customizer_user_auth();

				do_action( 'cgda_did_auto_login' );

				wp_safe_redirect( esc_url( add_query_arg( 'url', $_GET['return_url'], admin_url( 'customize.php' ) ) ) );
				die();
			}
		}
	}

	/**
	 * Adjust default behaviours like autosave interval or changeset locking.
	 */
	public function adjust_default_behaviours() {

		if ( is_admin() && 'customize.php' == basename( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ) && $this->is_customizer_user() ) {
			// We want a really big autosave interval to throttle down the requests number.
			if ( ! defined( 'AUTOSAVE_INTERVAL' ) ) {
				// An hour should do it.
				define( 'AUTOSAVE_INTERVAL', 60 * 60 );
			}

			// We don't want the changeset loc logic to kick in.
			global $wp_customize;
			remove_filter( 'heartbeat_received', array( $wp_customize, 'check_changeset_lock_with_heartbeat' ), 10 );

			// We also want to prevent sending via _wpCustomizeSettings that we should lock a certain user.
			add_filter( 'get_post_metadata', array( $this, 'prevent_meta_edit_lock' ), 100, 3 );

			add_filter( 'heartbeat_settings', array( $this, 'heartbeat_settings' ), 100, 1 );
		}
	}

	public function prevent_meta_edit_lock( $meta_value, $object_id, $meta_key ) {
		if ( '_edit_lock' === $meta_key ) {
			return false;
		}

		return $meta_value;
	}

	public function heartbeat_settings( $settings ) {
		if ( empty( $settings['interval'] ) ) {
			$settings['interval'] = 15 * MINUTE_IN_SECONDS;
		}

		return $settings;
	}

	/**
	 * Check if the current request URL is a valid customizer auto-login link.
	 *
	 * @return bool
	 */
	protected function check_customizer_link() {

		$auto_login_key = CGDA_Plugin()->settings->get_option( 'auto_login_key' );
		if ( empty( $auto_login_key ) ) {
			$auto_login_key = self::$default_auto_login_key;
		}

		// We really need the parameters to be present present
		if ( empty( $_GET[ $auto_login_key ] ) || empty(  $_GET['return_url'] ) ) {
			return false;
		}

		// Now we will check if the hash matches.
		$auto_login_hash = wp_hash( urldecode( $_GET['return_url'] ) );
		if ( urldecode( $_GET[ $auto_login_key ] ) !== $auto_login_hash ) {
			return false;
		}

		return true;
	}

	/**
	 * Remove Customizer ajax save action for security reasons and replace it with a mock handler to allow the Customizer JS logic to function properly.
	 */
	public function remove_customize_save_action() {

		if ( true === apply_filters( 'cgda_should_remove_customize_save_action', $this->is_customizer_user() ) ) {
			global $wp_customize;
			remove_action( 'wp_ajax_customize_save', array( $wp_customize, 'save' ) );

			add_action( 'wp_ajax_customize_save', array( $this, 'mock_customize_save' ) );
		}
	}

	/**
	 * Mock Customizer AJAX save handler.
	 *
	 * It just returns what the Customizer JS needs to "hear".
	 */
	public function mock_customize_save() {

		$response = array(
			'autosave' => false,
			'changeset_status' => 'publish',
			'setting_validities' => array(),
		);

		if ( ! empty( $_POST['customize_changeset_autosave'] ) ) {
			$response['autosave'] = true;
		}

		// Handle the settings we've received.
		if ( ! empty( $_POST['customize_changeset_data'] ) ) {
			$input_changeset_data = json_decode( wp_unslash( $_POST['customize_changeset_data'] ), true );
			if ( ! is_array( $input_changeset_data ) ) {
				wp_send_json_error( 'invalid_customize_changeset_data' );
			}
		} else {
			$input_changeset_data = array();
		}
		// Every setting we receive is valid :)
		foreach ( $input_changeset_data as $setting_id => $value ) {
			$response['setting_validities'][ $setting_id ] = true;
		}

		// Allow others to have a say in this.
		$response = apply_filters( 'cgda_mock_customize_save_response', $response );

		// Mock saving always ends in success.
		wp_send_json_success( $response );
	}

	/**
	 * Prevent saving of plugin options in the Customizer
	 *
	 * @param array $data The data to save
	 * @param array $filter_context
	 *
	 * @return array
	 */
	public function prevent_changeset_save( $data, $filter_context ) {

		if ( $this->is_customizer_user() ) {
			$data = array();
		}

		return $data;
	}

	/**
	 * Register Customizer admin scripts.
	 */
	public function register_admin_customizer_scripts() {

		wp_register_script( cgda_prefix('customizer' ), plugins_url( 'assets/js/customizer.js', CGDA_Plugin()->get_file() ), array( 'jquery' ), CGDA_Plugin()->get_version() );
	}

	/**
	 * Enqueue Customizer admin scripts
	 */
	public function enqueue_admin_customizer_scripts() {

		if ( $this->is_customizer_user() ) {
			// Enqueue the needed scripts, already registered.
			wp_enqueue_script( cgda_prefix( 'customizer' ) );

			wp_localize_script( cgda_prefix( 'customizer' ), 'cgda', apply_filters( 'cgda_customizer_localized_data', array(
				'button_text'    => esc_html( CGDA_Plugin()->settings->get_option( 'customizer_back_button_text', __( 'Back to Demo', 'cgda' ) ) ),
				'button_link'    => esc_url( ! empty( $_GET['url'] ) ? $_GET['url'] : get_home_url() ),
				'notice_type' => esc_attr( trim(  CGDA_Plugin()->settings->get_option( 'customizer_notice_type', 'info' ) ) ),
				'notice_text' => trim( CGDA_Plugin()->settings->get_option( 'customizer_notice_text', __( '<b>Demo Mode</b><p>You can\'t upload images and save settings.</p>', 'cgda' ) ) ),
			) ) );
		}
	}

	/**
	 * Print the Customizer templates used for button and notice.
	 */
	public function customize_controls_templates() {

		if ( true === apply_filters( 'cgda_customizer_output_tmpl', $this->is_customizer_user() ) ) { ?>
			<script type="text/html" id="tmpl-cgda-customizer-notice">
				<div class="cgda-customizer-notice">{{{ data.notice_text }}}</div>
			</script>
			<script type="text/html" id="tmpl-cgda-customizer-button">
				<a class="button button-primary" href="{{ data.button_link }}">{{ data.button_text }}</a>
			</script>
			<?php
		}
	}

	/**
	 * Remove the switch/preview theme panel.
	 *
	 * @param WP_Customize_Manager $wp_customize
	 */
	public function remove_switch_theme_panel( $wp_customize ) {

		if ( $this->is_customizer_user() ) {
			$wp_customize->remove_panel( 'themes' );
		}
	}

	/**
	 * Determine if we should output the frontend markup.
	 *
	 * @return bool
	 */
	public function frontend_should_output() {

		// We will not show for regular logged in users.
		if ( is_user_logged_in() && ! $this->is_customizer_user() ) {
			return false;
		}

		// We will not show the output in the Customizer preview
		if ( is_customize_preview() ) {
			return false;
		}

		// We will only output if the button frontend mode is not set to 'self'.
		if ( 'self' === CGDA_Plugin()->settings->get_option( 'frontend_button_mode' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Output the frontend button markup, if that is the case.
	 */
	public function frontend_output() {

		// Bail if we are not supposed to output.
		if ( true !== apply_filters( 'cgda_frontend_should_output', $this->frontend_should_output() ) ) {
			return;
		}

		do_action( 'cgda_before_frontend_output' );

		// First output the CSS.
		echo $this->frontend_css();

		// Second, output the HTML.
		echo $this->frontend_html();

		do_action( 'cgda_after_frontend_output' );
	}

	protected function frontend_html() {
		$mode = CGDA_Plugin()->settings->get_option( 'frontend_button_mode', 'auto' );
		$html = '';
		if ( 'custom' === $mode ) {
			$html .= CGDA_Plugin()->settings->get_option( 'frontend_custom_html' );

			// We need to parse the %customizer_link% content tag and replace it with the appropriate URL.
			$html = str_replace( '%customizer_link%', cgda_get_customizer_link(), $html );
		} elseif ( 'auto' === $mode ) {
			$wrapper_classes = [ 'cgda-customizer-access-wrapper' ];
			$additional_wrapper_classes = cgda_maybe_explode_list( CGDA_Plugin()->settings->get_option( 'frontend_button_wrapper_classes' ) );
			if ( ! empty( $additional_wrapper_classes ) ) {
				$wrapper_classes = array_merge( $wrapper_classes, $additional_wrapper_classes );
			}
			/**
			 * Filters the list of CSS classes for the button wrapper.
			 *
			 * @param array $classes An array of classes.
			 */
			$wrapper_classes = apply_filters( 'cgda_frontend_button_wrapper_classes', $wrapper_classes );

			$button_classes = [ 'cgda-customizer-access-button' ];
			$additional_button_classes = cgda_maybe_explode_list( CGDA_Plugin()->settings->get_option( 'frontend_button_classes' ) );
			if ( ! empty( $additional_button_classes ) ) {
				$button_classes = array_merge( $button_classes, $additional_button_classes );
			}
			/**
			 * Filters the list of CSS classes for the button.
			 *
			 * @param array $classes An array of classes.
			 */
			$button_classes = apply_filters( 'cgda_frontend_button_classes', $button_classes );

			$html .= '<div ' . cgda_css_class( $wrapper_classes ) . '>' . PHP_EOL;
			$html .= '<div ' . cgda_css_class( $button_classes ) . '>' . PHP_EOL;

			$html .= '<a class="cgda-customizer-access-link" href="' . esc_url( cgda_get_customizer_link() ) . '">' . CGDA_Plugin()->settings->get_option( 'frontend_button_text', esc_html__( 'Customize Styles', 'cgda' ) ) . '</a>' . PHP_EOL;

			$html .= '</div>' . PHP_EOL;
			$html .= '</div>' . PHP_EOL;
		}

		/**
		 * Filters the frontend html, in case the site admin doesn't handle it on it's own.
		 *
		 * @param string $html
		 */
		return apply_filters( 'cgda_frontend_html', $html );
	}

	protected function frontend_css() {

		$css = '';
		if ( 'custom' === CGDA_Plugin()->settings->get_option( 'frontend_button_mode' ) ) {
			$css .= CGDA_Plugin()->settings->get_option( 'frontend_custom_css' );
		} else {
			$css .= '
	.cgda-customizer-access-wrapper {
		position: fixed;
		bottom: 0;
		right: 0;
		z-index: 1000;
	}
	.cgda-customizer-access-button {
		padding: 10px 14px;
		font-size: 16px;
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		float: right;
		background-color: #0074a2;
	}
	.cgda-customizer-access-button:hover {
		background-color: #1e8cbe;
	}
	.cgda-customizer-access-button a {
		color: #fff;
		font-weight: normal !important;
		-webkit-font-smoothing: subpixel-antialiased !important;
		border-bottom: none; 
		text-decoration: none;
	}
	.cgda-customizer-access-button a:before {
		content: "";
		position: relative;
		top: 1px;
		display: inline-block;
		height: 17px;
		width: 17px;
		margin-right: 8px;
		background-image: url(https://demo.thethemefoundry.com/wp-content/plugins/customizer-demo/images/brush.svg);
		background-position: center;
		background-repeat: no-repeat;
	}
';
		}

		/**
		 * Filters the frontend CSS, in case the site admin doesn't handle it on it's own.
		 *
		 * @param string $html
		 */
		$css = apply_filters( 'cgda_frontend_css', $css );

		// Wrap the CSS rules, it is not already wrapped.
		if ( ! empty( $css ) && false === strpos( $css, '</style>' ) ) {
			$css = '<style id="cgda-frontend-css" type="text/css">' . $css . '</style>' . PHP_EOL;
		}

		return $css;
	}
}

endif;
