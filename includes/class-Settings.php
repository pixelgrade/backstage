<?php
/**
 * Document for class CGDA_Settings.
 *
 * @package Customizer-Guest-Demo-Access
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
class CGDA_Settings extends CGDA_Singleton_Registry {

	/**
 	 * Option key, and, at the same time, option page slug
 	 * @var string
 	 */
	protected $key = null;

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

		$this->key = $this->prefix('options' );

		// Set our settings page title.
		$this->title = esc_html__( 'Customizer Guest Demo Access Setup', 'cgda' );

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

		add_action( 'load-appearance_page_' . $this->key, array( $this, 'register_admin_scripts' ) );
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

		$cmb = new_cmb2_box( array(
			'id'           => $this->prefix( 'cgda_setup_page' ),
			'title'        => $this->title,
			'desc'         => 'description',
			'object_types' => array( 'options-page' ),
			'option_key'   => $this->key, // The option key and admin menu page slug.
			'menu_title'   => esc_html__( 'Customizer Guest Access', 'cgda' ),
			'parent_slug'  => 'themes.php', // Make options page a submenu item of the themes menu.
			'capability'   => 'manage_options',
			'autoload'     => true,
			'show_in_rest' => false,
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'URL Auto-login Key', 'cgda' ),
			'desc' => esc_html__( 'Set the key (parameter name) that will be used to auto-login the visitor and gain access to the Customizer.', 'cgda' ),
			'id'   => $this->prefix( 'auto_login_key' ),
			'type' => 'text',
			'default' => 'cgda_auto_login',
			'attributes' => array(
				'required'               => true, // Will be required only if visible.
			),
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Customizer Button Text', 'cgda' ),
			'desc' => esc_html__( 'Set the text of the button at the top of the Customizer sidebar. This button will bring the visitor back from where it came.', 'cgda' ),
			'id'   => $this->prefix( 'customizer_back_button_text' ),
			'type' => 'text',
			'default' => esc_html__( 'Back to Demo', 'cgda' ),
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Customizer Notice Text', 'cgda' ),
			'desc' => esc_html__( 'Set the text of notice shown in the Customizer.', 'cgda' ),
			'id'   => $this->prefix( 'customizer_notice_text' ),
			'type' => 'text',
			'default' => esc_html__( 'You can\'t upload images and save settings.', 'cgda' ),
		) );

		$cmb->add_field( array(
			'name'             => esc_html__( 'Frontend Button Output Mode', 'cgda' ),
			'desc'             => esc_html__( 'Here you can decide if you want us to output a button on the frontend with a link to the Customizer, or if you want to do that yourself.', 'cgda' ),
			'id'               => $this->prefix( 'frontend_button_mode' ),
			'type'             => 'select',
			'show_option_none' => false,
			'default'          => 'auto',
			'options'          => array(
				'auto'   => esc_html__( 'Output a button for me', 'cgda' ),
				'custom' => esc_html__( 'Let me specify the button markup and CSS', 'cgda' ),
				'self'   => esc_html__( 'I will handle the button myself', 'cgda' ),
			),
		) );

		/* ================================
		 * Fields for auto frontend output.
		 * ================================ */

		$cmb->add_field( array(
			'name' => esc_html__( 'Frontend Button Text', 'cgda' ),
			'desc' => esc_html__( 'Set here the text for the frontend button.', 'cgda' ),
			'id'   => $this->prefix( 'frontend_button_text' ),
			'type' => 'text',
			'default' => esc_html__( 'Customize Styles', 'cgda' ),
			'attributes' => array(
				'required'               => true, // Will be required only if visible.
				'data-conditional-id'    => $this->prefix( 'frontend_button_mode' ),
				'data-conditional-value' => 'auto',
			),
		) );
		$cmb->add_field( array(
			'name' => esc_html__( 'Frontend Button Classes', 'cgda' ),
			'desc' => esc_html__( 'Set here custom class(es) for the frontend button. If multiple, please separate them with a comma and a space.', 'cgda' ),
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
			'name' => esc_html__( 'Frontend Button Wrapper Classes', 'cgda' ),
			'desc' => esc_html__( 'Set here custom class(es) for the frontend button wrapper. If multiple, please separate them with a comma and a space.', 'cgda' ),
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
			'name' => esc_html__( 'Custom CSS', 'cgda' ),
			'desc' => esc_html__( 'Add here the custom CSS you want to output on the frontend of your site. It\'s OK to leave it empty if you have the CSS elsewhere.', 'cgda' ),
			'id'   => $this->prefix( 'frontend_custom_css' ),
			'type' => 'textarea_code',
			'default' => '',
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
		$cmb->add_field( array(
			'name' => esc_html__( 'Custom HTML', 'cgda' ),
			'desc' => sprintf( esc_html__( 'Add here the custom HTML you want to output on the frontend of your site. You must include the %s content tag so it can be replaced with the URL for Customizer access.', 'cgda' ), '<code>%customizer_link%</code>'),
			'id'   => $this->prefix( 'frontend_custom_html' ),
			'type' => 'textarea_code',
			'default' => '<div class="cgda-customizer-access-wrapper">
	<div class="cgda-customizer-access-button">
		<a class="cgda-customizer-access-link" href="%customizer_link%">Customize Styles</a>
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

		/* ================================
		 * Fields for self frontend output.
		 * ================================ */

		$cmb->add_field( array(
			'name' => esc_html__( 'Custom Frontend Button Instructions', 'cgda' ),
			'desc' => esc_html__( 'Since you wish to have control and handle your own button, we will make it easy for you. You have access to a localized JavaScript object called "cgda" with the needed URL, and you can also use the "cgda_get_customizer_link()" PHP function.', 'cgda' ),
			'id'   => $this->prefix( 'frontend_custom_button_instructions' ),
			'type' => 'title',
			'attributes' => array(
				'required'               => false,
				'data-conditional-id'    => $this->prefix( 'frontend_button_mode' ),
				'data-conditional-value' => 'self',
			),
		) );

	}

	public function register_admin_scripts() {
		// The styles.
		wp_register_style( $this->prefix( 'admin-style' ), plugins_url( 'assets/css/admin.css', CGDA_Plugin()->get_file() ), array(), CGDA_Plugin()->get_version() );

		wp_register_script( $this->prefix( 'settings-js' ), plugins_url( 'assets/js/settings-page.js', CGDA_Plugin()->get_file() ),
			array(
				'jquery',
				$this->prefix( 'cmb2-conditionals' ),
				'wp-api',
			), CGDA_Plugin()->get_version() );
	}

	public function enqueue_admin_scripts() {
		// The styles.
		wp_enqueue_style( $this->prefix( 'admin-style' ) );

		wp_enqueue_script( $this->prefix( 'settings-js' ) );
	}

	/**
	 * Get a list of posts
	 *
	 * Generic function to return an array of posts formatted for CMB2. Simply pass
	 * in your WP_Query arguments and get back a beautifully formatted CMB2 options
	 * array.
	 *
	 * @param array $query_args WP_Query arguments
	 * @return array CMB2 options array
	 */
	protected function get_cmb_options_array_posts( $query_args = array() ) {
		$defaults = array(
			'posts_per_page' => -1
		);
		$query = new WP_Query( array_replace_recursive( $defaults, $query_args ) );
		return wp_list_pluck( $query->get_posts(), 'post_title', 'ID' );
	}

	/**
	 * Get a list of terms.
	 *
	 * Generic function to return an array of taxonomy terms formatted for CMB2.
	 * Simply pass in your get_terms arguments and get back a beautifully formatted
	 * CMB2 options array.
	 *
	 * @param string|array $taxonomies Taxonomy name or list of Taxonomy names.
	 * @param  array|string $query_args Optional. Array or string of arguments to get terms.
	 * @return array CMB2 options array.
	 */
	protected function get_cmb_options_array_tax( $taxonomies, $query_args = '' ) {
		$defaults = array(
			'hide_empty' => false
		);
		$args = wp_parse_args( $query_args, $defaults );
		$terms = get_terms( $taxonomies, $args );
		$terms_array = array();
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$terms_array[$term->term_id] = $term->name;
			}
		}
		return $terms_array;
	}

	/**
	 * Retrieves an option before the CMB2 has loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id
	 * @param mixed $default Optional. The default value in case the option wasn't saved.
	 * @return mixed
	 */
	public function get_option( $id, $default = false ) {
		$options = get_option( $this->key );
		if ( isset( $options[ $this->prefix( $id ) ] ) ) {
			return $options[ $this->prefix( $id ) ];
		}
		return $default;
	}

	/**
	 * Public getter method for retrieving protected/private variables.
	 *
	 * @throws Exception
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'title', 'options_page' ), true ) ) {
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
		$option = cgda_prefix( $option );

		if ( true === $private ) {
			return '_' . $option;
		}

		return $option;
	}
}
