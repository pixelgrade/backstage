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

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param $parent
	 */
	protected function __construct( $parent = null ) {

		$this->init();
	}

	/**
	 * Initialize this module.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Hook up.
		$this->add_hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since 1.0.0
	 */
	public function add_hooks() {

		/*
		 * Handle the main logic regarding accessing the Customizer.
		 */
		// Auth and Show Customizer
		add_action( 'plugins_loaded', array( $this, 'show_customizer' ), 1 );

		// Disable wp-admin access.
		add_action( 'init', array( $this, 'clear_user_auth' ) );
		add_action( 'admin_init', array( $this, 'clear_user_auth' ) );
		add_filter( 'woocommerce_prevent_admin_access', array( $this, 'wc_prevent_admin_access' ) );

		// Remove Customizer Ajax Save Action
		add_action( 'admin_init', array( $this, 'remove_save_action' ) );

		// Prevent Changeset Save
		add_filter( 'customize_changeset_save_data', array( $this, 'prevent_changeset_save' ), 50, 2 );
		// Add a JS to display a notification
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'prevent_changeset_save_notification' ), 100 );

		// Remove the switch theme panel from the Customizer.
		add_action( 'customize_register', array( $this, 'remove_switch_theme_panel' ), 12 );

		/*
		 * Scripts enqueued in the Customizer.
		 */
		add_action( 'customize_controls_init', array( $this, 'register_admin_customizer_scripts' ), 10 );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_admin_customizer_scripts' ), 10 );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'customize_controls_templates' ) );

		/*
		 * Scripts enqueued in the frontend.
		 */
		add_action( 'wp_head', array( $this, 'frontend_js_variables' ) );

		/*
		 * Markup outputted in the frontend.
		 */
		add_action( 'wp_footer', array( $this, 'frontend_output' ) );
	}

	public function wc_prevent_admin_access( $default ) {
		return ( is_customize_preview() && $this->is_customizer_user() ) ? false : $default;
	}

	public function customize_controls_templates() { ?>
		<script type="text/html" id="tmpl-customizer-preview-for-demo-notice">
			<div id="customizer-preview-notice" class="accordion-section customize-info">
				<div class="accordion-section-title"><span class="preview-notice">{{ data.preview_notice || "<?php esc_html_e( 'You can\'t upload images and save settings.', 'cgda'); ?>" }}</span></div>
			</div>
		</script>
		<script type="text/html" id="tmpl-customizer-preview-for-demo-button">
			<a class="button button-primary" target="{{ data.button_target }}" href="{{ data.button_link }}">{{ data.button_text }}</a>
		</script>
		<?php
	}

	// Logout User
	public function clear_user_auth() {
		// If one is a customizer user and navigates away from it, we will log him out.
		if ( ! defined( 'DOING_AJAX' ) && ! is_customize_preview() && $this->is_customizer_user() ) {
			wp_logout();
			wp_safe_redirect( esc_url( home_url( '/' ) ) );
			die();
		}
	}

	/**
	 * If we are accessing the Customizer, we need to check if we should auto-login.
	 */
	public function show_customizer() {
		if ( is_admin() && 'customize.php' == basename( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ) ) {
			// Check if we need to auto-login the guest.
			if ( $this->check_customizer_link() && ! is_user_logged_in() ) {
				$this->customizer_user_auth();
				wp_safe_redirect( esc_url( add_query_arg( 'url', $_GET['return_url'], admin_url( 'customize.php' ) ) ) );
				die();
			}
		}
	}

	/**
	 * Check if the current request URL is a valid customizer auto-login link.
	 *
	 * @return bool
	 */
	protected function check_customizer_link() {
		$auto_login_key = CGDA_Plugin()->settings->get_option( 'auto_login_key' );
		if ( empty( $auto_login_key ) ) {
			$auto_login_key = 'cgda_auto_login';
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
	 * Remove ajax save action for security reason
	 */
	public function remove_save_action() {
		if ( $this->is_customizer_user() ) {
			global $wp_customize;
			remove_action( 'wp_ajax_customize_save', array( $wp_customize, 'save' ) );
		}
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
	 *
	 */
	public function prevent_changeset_save_notification() {
		if ( $this->is_customizer_user() ) { ?>
			<script type="application/javascript">
                (function ($, exports, wp) {
                    'use strict';
                    // when the customizer is ready add our notification
                    wp.customize.bind('ready', function () {

                    });
                })(jQuery, window, wp);
			</script>
		<?php }
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

		return in_array( 'cgda-customizer-preview', $user->roles );
	}

	/**
	 * Auto login Customizer pseudo-guest user.
	 *
	 * @param string $username Optional.
	 */
	public function customizer_user_auth( $username = 'cgda_customizer_user' ) {
		$user          = get_user_by( 'login', trim( $username ) );
		$secure_cookie = is_ssl() ? true : false;
		if ( $user ) {
			$user_id = $user->ID;
			wp_set_current_user( $user_id );

			wp_set_auth_cookie( $user_id, true, $secure_cookie );
			do_action( 'wp_login', $user->user_login, $user );
		}
	}

	/**
	 * Register Customizer admin scripts.
	 */
	function register_admin_customizer_scripts() {
		wp_register_script( cgda_prefix('customizer' ), plugins_url( 'assets/js/customizer.js', CGDA_Plugin()->get_file() ), array( 'jquery' ), CGDA_Plugin()->get_version() );
	}

	/**
	 * Enqueue Customizer admin scripts
	 */
	function enqueue_admin_customizer_scripts() {
		if ( $this->is_customizer_user() ) {
			// Enqueue the needed scripts, already registered.
			wp_enqueue_script( cgda_prefix( 'customizer' ) );

			wp_localize_script( cgda_prefix( 'customizer' ), 'cgda', array(
				'button_text'    => esc_attr( CGDA_Plugin()->settings->get_option( 'customizer_back_button_text' ) ),
				'button_link'    => esc_url( ! empty( $_GET['url'] ) ? $_GET['url'] : get_home_url() ),
				'button_target'  => esc_attr( '' ),
				'preview_notice' => CGDA_Plugin()->settings->get_option( 'customizer_notice_text' ),
			) );
		}
	}

	/**
	 * Remove the switch/preview theme panel.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Customize_Manager $wp_customize
	 */
	public function remove_switch_theme_panel( $wp_customize ) {
		if ( $this->is_customizer_user() ) {
			$wp_customize->remove_panel( 'themes' );
		}
	}

	/**
	 * Output an inline JS in <head> with the global variables, if that is the case.
	 */
	public function frontend_js_variables() {
		// We will only output if the button frontend mode is set to 'self' meaning the site administrator is handling the output.
		if ( 'self' !== CGDA_Plugin()->settings->get_option( 'frontend_button_mode' ) ) {
			return;
		}

		$data = array( 'customizerLink' => cgda_get_customizer_link(), );

		/**
		 * Filters the data we localize on the frontend.
		 *
		 * @param array $data
		 */
		$data = apply_filters( 'cgda_frontend_localized_data', $data );

		// Make sure things are sane.
		if ( empty( $data ) || ! is_array( $data ) ) {
			$data = array();
		}
		?>
		<script type="text/javascript">
            var cgda = <?php echo json_encode( $data ); ?>;
		</script>
	<?php }

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
		$html = '';
		if ( 'custom' === CGDA_Plugin()->settings->get_option( 'frontend_button_mode' ) ) {
			$html .= CGDA_Plugin()->settings->get_option( 'frontend_custom_html' );

			// We need to parse the %customizer_link% content tag and replace it with the appropriate URL.
			$html = str_replace( '%customizer_link%', cgda_get_customizer_link(), $html );
		} else {
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

			$html .= '<a class="cgda-customizer-access-link" href="' . esc_url( cgda_get_customizer_link() ) . '">' . CGDA_Plugin()->settings->get_option( 'frontend_button_text' ) . '</a>' . PHP_EOL;

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
