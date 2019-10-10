<?php
/**
 * Document for class Backstage.
 *
 * @package Backstage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Backstage' ) ) :

/**
 * This is the class that handles the overall logic for the Backstage.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 * @since       1.0.0
 */
class Backstage extends Backstage_Singleton_Registry {

	public static $username = 'backstage_customizer_user';
	public static $user_role = 'backstage-customizer-demo-access';

	public static $default_auto_login_key = 'customizer_auto_login';

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

		// Bail if the plugin is network activated and we are not in the network admin dashboard.
		if ( Backstage_Plugin()->is_plugin_network_activated() ) {
			if ( ! is_network_admin() ) {
				return $all_good;
			}

			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );

				if ( ! get_role( self::$user_role ) ) {
					$all_good = false;
					Backstage_Plugin::add_admin_notice( array( 'Backstage', 'user_role_missing_error_notice' ) );
				}

				if ( ! username_exists( self::$username ) ) {
					$all_good = false;
					Backstage_Plugin::add_admin_notice( array( 'Backstage', 'user_missing_error_notice' ) );
				}

				restore_current_blog();

				// We take it one step at a time. No need to fill up the screen with multiple notifications.
				if ( ! $all_good ) {
					return $all_good;
				}
			}
		}

		if ( ! get_role( self::$user_role ) ) {
			$all_good = false;
			Backstage_Plugin::add_admin_notice( array( 'Backstage', 'user_role_missing_error_notice' ) );
		}

		if ( ! username_exists( self::$username ) ) {
			$all_good = false;
			Backstage_Plugin::add_admin_notice( array( 'Backstage', 'user_missing_error_notice' ) );
		}

		return $all_good;
	}

	/**
	 * Handle the creation of the custom user role we are using to identify and restrict the auto logged in user.
	 *
	 * @param bool $network_wide
	 */
	public static function maybe_create_user_role( $network_wide = false ) {

		// Customizer access user capabilities.
		$user_capabilities = apply_filters( 'backstage_user_capabilities', array(
			'read'               => true,
			'edit_posts'         => false,
			'delete_posts'       => false,
			'edit_pages'         => false,
			'edit_theme_options' => true,
			'manage_options'     => true,
			'customize'          => true,
			'upload_files'       => false,
		) );

		if ( is_multisite() && $network_wide ) {
			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );

				add_role( self::$user_role, esc_html__( 'Customizer Preview', 'backstage' ), $user_capabilities );

				restore_current_blog();
			}
		} else {
			add_role( self::$user_role, esc_html__( 'Customizer Preview', 'backstage' ), $user_capabilities );
		}
	}

	/**
	 * Handle the removal of the created user role.
	 *
	 * @param bool $network_wide
	 */
	public static function maybe_remove_user_role( $network_wide = false ) {

		if ( is_multisite() && $network_wide ) {
			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );

				remove_role( self::$user_role );

				restore_current_blog();
			}
		}

		remove_role( self::$user_role );
	}

	/**
	 * Display an admin error notice if the needed user role is missing.
	 */
	public static function user_role_missing_error_notice() {
		?>
		<div class="error notice">
			<p><?php esc_html_e( 'Something is wrong! We couldn\'t find the user role needed for Backstage. Try to deactivate and activate the plugin again.', 'backstage' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Create Customizer user if user not exists.
	 *
	 * @param bool $network_wide
	 *
	 * @return int|false The user id or false on failure.
	 */
	public static function maybe_create_customizer_user( $network_wide = false ) {
		$user_id = username_exists( self::$username );
		if ( ! $user_id ) {
			// Generate a random password. This is not actually used anywhere, so no one needs to know it.
			$password = wp_generate_password();

			$new_user_data = array(
				'user_login' => self::$username,
				'user_pass'  => $password,
				'role'       => self::$user_role,
			);

			$user_id = wp_insert_user( $new_user_data );
			if ( empty( $user_id ) || is_wp_error( $user_id ) ) {
				Backstage_Plugin::add_admin_notice( array( 'Backstage', 'user_creation_error_notice' ) );
				return false;
			}

			if ( $network_wide && is_multisite() ) {
				$sites = get_sites( array( 'fields' => 'ids' ) );
				foreach ( $sites as $site_id ) {
					add_user_to_blog( $site_id, $user_id, self::$user_role );
				}
			}
		}

		return $user_id;
	}

	/**
	 * Add our user to a newly created blog in a multisite environment, if the plugin is network-wide activated.
	 *
	 * @param int $site_id
	 */
	public static function multisite_maybe_add_user_to_new_blog( $site_id ) {
		$user = get_user_by( 'login', self::$username );
		// This should not happen. The user is created upon plugin activation.
		// We will show a notice in the admin via self::check_setup()
		if ( ! $user ) {
			return;
		}

		if ( Backstage_Plugin()->is_plugin_network_activated() ) {
			add_user_to_blog( $site_id, $user->ID, self::$user_role );
		}
	}

	/**
	 * Display an admin error notice if we failed to create the needed user.
	 */
	public static function user_creation_error_notice() {
		?>
		<div class="error notice">
			<p><?php esc_html_e( 'Something is very wrong! We couldn\'t create the user needed for Backstage.', 'backstage' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Display an admin error notice if we couldn't find the needed user.
	 */
	public static function user_missing_error_notice() {
		?>
		<div class="error notice">
			<p><?php esc_html_e( 'Something is very wrong! We couldn\'t find the user needed for Backstage. Try to deactivate and activate the plugin again.', 'backstage' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Handle the removal of the self created user.
	 *
	 * @param bool $network_wide
	 */
	public static function maybe_remove_customizer_user( $network_wide = false ) {

		$user = get_user_by( 'login', self::$username );
		if ( ! $user ) {
			return;
		}

		if ( is_multisite() && $network_wide ) {
			wpmu_delete_user( $user->ID );
		} else {
			wp_delete_user( $user->ID );
		}
	}

	public function wc_prevent_admin_access( $default ) {

		return ( is_customize_preview() && $this->is_customizer_user() ) ? false : $default;
	}

	/**
	 * Is the current logged-in user our user?
	 *
	 * @return bool
	 */
	public function is_customizer_user() {

		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user = wp_get_current_user();

		return apply_filters( 'backstage_is_customizer_user', in_array( self::$user_role, $user->roles ) );
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
		if ( true === apply_filters( 'backstage_should_auto_logout_user', $should_auto_logout ) ) {
			wp_logout();

			do_action( 'backstage_did_auto_logout' );

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
			if ( true === apply_filters( 'backstage_customizer_should_auto_login', $should_auto_login ) ) {
				$this->customizer_user_auth();

				do_action( 'backstage_did_auto_login' );

				// We will put the return URL in the url key because the return_url key is used by the Customizer logic and we want to avoid collisions.
				$url = add_query_arg( 'url', $_GET['return_url'], admin_url( 'customize.php' ) );

				if ( ! empty( $_GET['button_text'] ) ) {
					$url = add_query_arg( 'button_text', $_GET['button_text'], $url );
				}

				// Allow others to have say in this.
				$url = apply_filters( 'backstage_auto_login_redirect_url', $url );

				wp_safe_redirect( esc_url( $url ) );
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

	/**
	 * Force the _edit_lock meta to have a value of false when in the Customizer.
	 *
	 * @param $meta_value
	 * @param $object_id
	 * @param $meta_key
	 *
	 * @return bool
	 */
	public function prevent_meta_edit_lock( $meta_value, $object_id, $meta_key ) {
		if ( '_edit_lock' === $meta_key ) {
			return false;
		}

		return $meta_value;
	}

	/**
	 * Increase the heartbeat interval.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
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

		$auto_login_key = Backstage_Plugin()->settings->get_option( 'auto_login_key' );
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

		// We will now do some extra checks, just to be sure that things are in proper order, server-side.
		// In a multisite setting, we really want the plugin being active network-wide or active on the current blog.
		// This should not normally happen, but it is best to be safe and not expose ourselves to some cross multisite blogs issues.
		if ( is_multisite() && ! is_plugin_active( Backstage_Plugin()->get_basename() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Remove Customizer ajax save action for security reasons and replace it with a mock handler to allow the Customizer JS logic to function properly.
	 */
	public function remove_customize_save_action() {

		if ( true === apply_filters( 'backstage_should_remove_customize_save_action', $this->is_customizer_user() ) ) {
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
			'changeset_status' => 'draft',
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
		$response = apply_filters( 'backstage_mock_customize_save_response', $response );

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

		wp_register_script( backstage_prefix('customizer' ), plugins_url( 'assets/js/customizer.js', Backstage_Plugin()->get_file() ), array( 'jquery', 'customize-controls' ), Backstage_Plugin()->get_version() );
	}

	/**
	 * Enqueue Customizer admin scripts
	 */
	public function enqueue_admin_customizer_scripts() {

		if ( $this->is_customizer_user() ) {
			// Enqueue the needed scripts, already registered.
			wp_enqueue_script( backstage_prefix( 'customizer' ) );

			wp_localize_script( backstage_prefix( 'customizer' ), 'backstage', apply_filters( 'backstage_customizer_localized_data', array(
				'button_text'    => esc_html( ! empty( $_GET['button_text'] ) ? $_GET['button_text'] : Backstage_Plugin()->settings->get_option( 'customizer_back_button_text', __( 'Back to Demo', 'backstage' ) ) ),
				'button_link'    => esc_url( ! empty( $_GET['url'] ) ? $_GET['url'] : get_home_url() ),
				'notice_type' => esc_attr( trim(  Backstage_Plugin()->settings->get_option( 'customizer_notice_type', 'info' ) ) ),
				'notice_text' => trim( Backstage_Plugin()->settings->get_option( 'customizer_notice_text', __( '<b>Demo Mode</b><p>You can\'t upload images and save settings.</p>', 'backstage' ) ) ),
				'notice_dismissible' => Backstage_Plugin()->settings->get_option( 'customizer_notice_dismissible', false ),
				'hide_info' => Backstage_Plugin()->settings->get_option( 'customizer_hide_info', false ),
			) ) );
		}
	}

	/**
	 * Print the Customizer templates used for button and notice.
	 */
	public function customize_controls_templates() {

		if ( true === apply_filters( 'backstage_customizer_output_tmpl', $this->is_customizer_user() ) ) { ?>
			<script type="text/html" id="tmpl-backstage-customizer-notice">
				<div class="backstage-customizer-notice">{{{ data.notice_text }}}</div>
			</script>
			<script type="text/html" id="tmpl-backstage-customizer-button">
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

		// We will not show for any logged in users, including our own user.
		// Our user should be auto logged out in the frontend.
		if ( is_user_logged_in() ) {
			return false;
		}

		// We will not show the output in the Customizer preview
		if ( is_customize_preview() ) {
			return false;
		}

		// We will only output if the button frontend mode is not set to 'self'.
		if ( 'self' === Backstage_Plugin()->settings->get_option( 'frontend_button_mode' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Output the frontend button markup, if that is the case.
	 */
	public function frontend_output() {

		// Bail if we are not supposed to output.
		if ( true !== apply_filters( 'backstage_frontend_should_output', $this->frontend_should_output() ) ) {
			return;
		}

		do_action( 'backstage_before_frontend_output' );

		// First output the CSS.
		echo $this->frontend_css();

		// Second, output the HTML.
		echo $this->frontend_html();

		do_action( 'backstage_after_frontend_output' );
	}

	/**
	 * Retrieve the HTML that should be shown on the frontend.
	 *
	 * @return string
	 */
	protected function frontend_html() {
		$mode = Backstage_Plugin()->settings->get_option( 'frontend_button_mode', 'auto' );
		$html = '';
		if ( 'custom' === $mode ) {
			$html .= Backstage_Plugin()->settings->get_option( 'frontend_custom_html' );

			// We need to parse the %customizer_link% content tag and replace it with the appropriate URL.
			$html = str_replace( '%customizer_link%', backstage_get_customizer_link(), $html );
		} elseif ( 'auto' === $mode ) {
			$wrapper_classes = [ 'backstage-customizer-access-wrapper' ];
			$additional_wrapper_classes = backstage_maybe_explode_list( Backstage_Plugin()->settings->get_option( 'frontend_button_wrapper_classes' ) );
			if ( ! empty( $additional_wrapper_classes ) ) {
				$wrapper_classes = array_merge( $wrapper_classes, $additional_wrapper_classes );
			}
			/**
			 * Filters the list of CSS classes for the button wrapper.
			 *
			 * @param array $classes An array of classes.
			 */
			$wrapper_classes = apply_filters( 'backstage_frontend_button_wrapper_classes', $wrapper_classes );

			$button_classes = [ 'backstage-customizer-access-button' ];
			$additional_button_classes = backstage_maybe_explode_list( Backstage_Plugin()->settings->get_option( 'frontend_button_classes' ) );
			if ( ! empty( $additional_button_classes ) ) {
				$button_classes = array_merge( $button_classes, $additional_button_classes );
			}
			/**
			 * Filters the list of CSS classes for the button.
			 *
			 * @param array $classes An array of classes.
			 */
			$button_classes = apply_filters( 'backstage_frontend_button_classes', $button_classes );

			$html .= '<div ' . backstage_css_class( $wrapper_classes ) . '>' . PHP_EOL;
			$html .= '<div ' . backstage_css_class( $button_classes ) . '>' . PHP_EOL;

			$html .= '<a class="backstage-customizer-access-link" href="' . esc_url( backstage_get_customizer_link() ) . '">' . Backstage_Plugin()->settings->get_option( 'frontend_button_text', esc_html__( 'Customize Styles', 'backstage' ) ) . '</a>' . PHP_EOL;

			$html .= '</div>' . PHP_EOL;
			$html .= '</div>' . PHP_EOL;

			// Add a piece of JavaScript that will try to ensure that when clicking the button,
			// we will bust from any iframe since the Customizer doesn't work well when opened in an iframe.
			$html .= '<script>
	(function(window) {
		var button = document.getElementsByClassName("backstage-customizer-access-link")[0];
		if (typeof button !== "undefined") {
			button.addEventListener("click", function(e) {
				// If we are in an iframe, bust out of it on click.
				if (window.top.location !== window.location) {
					e.preventDefault();
					window.top.location = e.srcElement.attributes.href.textContent;
				}
			})
		}
	})(this)
</script>';
		}

		/**
		 * Filters the frontend html, in case the site admin doesn't handle it on it's own.
		 *
		 * @param string $html
		 */
		return apply_filters( 'backstage_frontend_html', $html );
	}

	/**
	 * Retrieve the custom CSS that should be included in the frontend.
	 *
	 * @return string
	 */
	protected function frontend_css() {

		$css = '';
		if ( 'custom' === Backstage_Plugin()->settings->get_option( 'frontend_button_mode' ) ) {
			$css .= Backstage_Plugin()->settings->get_option( 'frontend_custom_css' );
		} else {
			$css .= '
	.backstage-customizer-access-wrapper {
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
	}
';
		}

		/**
		 * Filters the frontend CSS, in case the site admin doesn't handle it on it's own.
		 *
		 * @param string $html
		 */
		$css = apply_filters( 'backstage_frontend_css', $css );

		// Wrap the CSS rules, it is not already wrapped.
		if ( ! empty( $css ) && false === strpos( $css, '</style>' ) ) {
			$css = '<style id="backstage-frontend-css" type="text/css">' . $css . '</style>' . PHP_EOL;
		}

		return $css;
	}
}

endif;
