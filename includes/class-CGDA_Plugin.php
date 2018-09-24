<?php
/**
 * Document for class CGDA_Plugin.
 *
 * @package Customizer-Guest-Demo-Access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main plugin class.
 *
 * This loads all the components that make up the plugin.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 * @since       1.0.0
 */
final class CGDA_Plugin extends CGDA_Plugin_Init {

	/**
	 * The plugin's base path.
	 * @var null|string
	 * @access public
	 * @since 1.0.0
	 */
	public $plugin_basepath = null;

	/**
	 * The plugin's base URL.
	 * @var null|string
	 * @access public
	 * @since 1.0.0
	 */
	public $plugin_baseuri = null;

	/**
	 * Unique identifier for your plugin.
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 * @since    1.0.0
	 * @var      string
	 */
	protected $plugin_slug = 'customizer-guest-demo-access';

	/**
	 * The site's base URL.
	 * @var null|string
	 * @access protected
	 * @since 1.0.0
	 */
	protected $base_url = null;

	/**
	 * Metaboxes class object.
	 * @var CGDA_Metaboxes
	 * @access  public
	 * @since   1.0.0
	 */
	public $metaboxes = null;

	/**
	 * Plugin settings class object.
	 * @var CGDA_Settings
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * Options class object
	 * @var     CGDA_Options
	 * @access  public
	 * @since   1.0.0
	 */
	public $options = null;

	/**
	 * Main class class object.
	 * @var StyleManager
	 * @access  public
	 * @since   1.0.0
	 */
	public $cgda = null;


	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * Minimal Required PHP Version.
	 * @var string
	 * @access  private
	 * @since   1.0.0
	 */
	private $minimalRequiredPhpVersion = 5.2;

	protected function __construct( $file, $version = '1.0.0' ) {
		// The main plugin file (the one that loads all this).
		$this->file = $file;
		// The current plugin version.
		$this->_version = $version;

		// Setup the helper variables for easily retrieving PATHS and URLS everywhere (these are already trailingslashit).
		$this->plugin_basepath = plugin_dir_path( $file );
		$this->plugin_baseuri  = plugin_dir_url( $file );
		$this->base_url        = home_url();

		// Initialize the options API.
		require_once( $this->plugin_basepath . 'includes/lib/class-Options.php' );
		if ( is_null( $this->options ) ) {
			$this->options = CGDA_Options::getInstance( 'cgda' );
		}

		parent::__construct( 'Customizer Guest Demo Access' );

		// Only load and run the init function if we know PHP version can parse it.
		if ( $this->php_version_check() ) {
			$this->upgrade();
			$this->init();
		}
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {

		/* Initialize the metaboxes logic (CMB2). */
		require_once( trailingslashit( $this->plugin_basepath ) . 'includes/class-Metaboxes.php' );
		if ( is_null( $this->metaboxes ) ) {
			$this->metaboxes = CGDA_Metaboxes::getInstance( 'cgda' );
		}

		/* Initialize the settings page. */
		require_once( trailingslashit( $this->plugin_basepath ) . 'includes/class-Settings.php' );
		if ( is_null( $this->settings ) ) {
			$this->settings = CGDA_Settings::getInstance( $this );
		}

		/* Initialize the core logic. */
		require_once( trailingslashit( $this->plugin_basepath ) . 'includes/class-CGDA.php' );
		if ( is_null( $this->cgda ) ) {
			$this->cgda = CGDA::getInstance( $this );
		}

		// Register all the needed hooks
		$this->register_hooks();
	}

	/**
	 * Register our actions and filters
	 */
	function register_hooks() {
		/* Handle the install and uninstall logic. */
		register_activation_hook( $this->file, array( 'CGDA_Plugin', 'install' ) );
		register_deactivation_hook( $this->file, array( 'CGDA_Plugin', 'uninstall' ) );

		/* Handle localisation. */
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	}

	public function get_version() {
		return $this->_version;
	}

	public function get_file() {
		return $this->file;
	}

	public function get_slug() {
		return $this->plugin_slug;
	}

	/*
	 * Install everything needed.
	 */
	static public function install() {
		if ( ! get_role( 'cgda-customizer-preview' ) ) {

			// Customizer access user capabilities
			$user_capabilities = apply_filters( 'cgda_user_capabilities', array(
				'read'               => true,
				'edit_posts'         => false,
				'delete_posts'       => false,
				'edit_pages'         => false,
				'edit_theme_options' => true,
				'manage_options'     => true,
				'customize'          => true,
			) );

			add_role( 'cgda-customizer-preview', esc_html__( 'Customizer Preview', 'cgda' ), $user_capabilities );
		}

		self::create_customizer_user();

	}

	/**
	 * Create customizer user if user not exists
	 */
	static protected function create_customizer_user() {
		if ( ! username_exists( 'cgda_customizer_user' ) ) {
			// Generate a random password. This is not actually used anywhere, so no need to know it.
			$password = wp_generate_password();

			$new_user_data = array(
				'user_login' => 'cgda_customizer_user',
				'user_pass'  => $password,
				'role'       => 'cgda-customizer-preview'
			);

			wp_insert_user( $new_user_data );
		}
	}

	/*
	 * Uninstall everything we added.
	 */
	static public function uninstall() {
		if ( get_role( 'cgda-customizer-preview' ) ) {
			remove_role( 'cgda-customizer-preview' );
		}
	}

	public function get_baseuri() {
		return $this->plugin_baseuri;
	}

	public function get_basepath() {
		return $this->plugin_basepath;
	}

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation() {
		$this->l10ni18n();
	}

	/**
	 * Registers Style Manager text domain path
	 * @since  1.0.0
	 */
	public function l10ni18n() {
		$loaded = load_plugin_textdomain( 'cgda', false, dirname( $this->get_basepath() ) . '/languages/' );

		if ( ! $loaded ) {
			$loaded = load_muplugin_textdomain( 'cgda', dirname( $this->get_basepath() ) . '/languages/' );
		}

		if ( ! $loaded ) {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'cgda' );
			$mofile = dirname( $this->get_basepath() ) . '/languages/sm-' . $locale . '.mo';
			load_textdomain( 'cgda', $mofile );
		}
	}
}
