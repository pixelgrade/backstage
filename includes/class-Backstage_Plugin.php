<?php
/**
 * Document for class Backstage_Plugin.
 *
 * @package Backstage
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
final class Backstage_Plugin extends Backstage_Plugin_Init {

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
	 * @var Backstage_Metaboxes
	 * @access  public
	 * @since   1.0.0
	 */
	public $metaboxes = null;

	/**
	 * Plugin settings class object.
	 * @var Backstage_Settings
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * Options class object
	 * @var     Backstage_Options
	 * @access  public
	 * @since   1.0.0
	 */
	public $options = null;

	/**
	 * Main class class object.
	 * @var Backstage
	 * @access  public
	 * @since   1.0.0
	 */
	public $backstage = null;

	/**
	 * REST API class class object.
	 * @var Backstage_REST_Controller_v1
	 * @access  public
	 * @since   1.0.0
	 */
	public $rest_api = null;


	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	protected $file;

	/**
	 * Minimal Required PHP Version.
	 * @var string
	 * @access  private
	 * @since   1.0.0
	 */
	protected $minimalRequiredPhpVersion = '5.4.0';

	protected function __construct( $file, $version = '1.0.0' ) {
		// The main plugin file (the one that loads all this).
		$this->file = $file;
		// The current plugin version.
		$this->_version = $version;

		// Setup the helper variables for easily retrieving PATHS and URLS everywhere (these are already trailingslashit).
		$this->plugin_basepath = plugin_dir_path( $file );
		$this->plugin_baseuri  = plugin_dir_url( $file );
		$this->base_url        = home_url();

		parent::__construct( 'Backstage' );

		// Only load and run the init function if we know PHP version can parse it.
		if ( $this->php_version_check() ) {
			// Initialize the options API.
			require_once( trailingslashit( $this->plugin_basepath ) . 'includes/lib/class-Backstage_Options.php' );
			if ( is_null( $this->options ) ) {
				$this->options = Backstage_Options::getInstance( 'backstage' );
			}

			// Make sure the upgrade routines class is loaded.
			require_once( trailingslashit( $this->plugin_basepath ) . 'includes/class-Backstage_Upgrade_Routines.php' );
			$this->init();
		}
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {

		/* Initialize the metaboxes logic (CMB2). */
		require_once( trailingslashit( $this->plugin_basepath ) . 'includes/class-Backstage_Metaboxes.php' );
		if ( is_null( $this->metaboxes ) ) {
			$this->metaboxes = Backstage_Metaboxes::getInstance( 'backstage' );
		}

		/* Initialize the settings page. */
		require_once( trailingslashit( $this->plugin_basepath ) . 'includes/class-Backstage_Settings.php' );
		if ( is_null( $this->settings ) ) {
			$this->settings = Backstage_Settings::getInstance( $this );
		}

		/* Initialize the core logic. */
		require_once( trailingslashit( $this->plugin_basepath ) . 'includes/class-Backstage.php' );
		if ( is_null( $this->backstage ) ) {
			$this->backstage = Backstage::getInstance( $this );
		}

		/* Initialize the REST API logic. */
		require_once( trailingslashit( $this->plugin_basepath ) . 'includes/class-Backstage_REST_Controller_v1.php' );
		if ( is_null( $this->rest_api ) ) {
			$this->rest_api = new Backstage_REST_Controller_v1();
		}

		// Register all the needed hooks
		$this->register_hooks();
	}

	/**
	 * Register our actions and filters
	 */
	public function register_hooks() {
		/* Handle the install and uninstall logic. */
		register_activation_hook( $this->file, array( 'Backstage_Plugin', 'activate' ) );
		register_deactivation_hook( $this->file, array( 'Backstage_Plugin', 'deactivate' ) );
		register_uninstall_hook( $this->file, array( 'Backstage_Plugin', 'uninstall' ) );

		add_action( 'admin_init', array( $this, 'upgrade' ), 5 );
		add_action( 'admin_init', array( $this, 'check_setup' ), 10 );

		add_action( 'wpmu_new_blog', array( 'Backstage', 'multisite_maybe_add_user_to_new_blog' ), 10, 1 );

		/* Handle localisation. */
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		add_action( 'rest_api_init', array( $this->rest_api, 'register_routes' ), 5 );
	}

	/**
	 * Check that everything needed for the plugin to function is alright.
	 */
	public function check_setup() {
		Backstage::check_setup();
	}

	/**
	 * Check if the plugin has been activated network-wide.
	 *
	 * @return bool
	 */
	public function is_plugin_network_activated() {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( is_multisite() && is_plugin_active_for_network( plugin_basename( $this->get_file() ) ) ) {
			return  true;
		}

		return false;
	}

	/**
	 * Add an admin notice depending on how the plugin was activated: network-wide or per site.
	 *
	 * @param $callable
	 */
	public static function add_admin_notice( $callable ) {
		$hook = 'admin_notices';
		if ( Backstage_Plugin()->is_plugin_network_activated() ) {
			$hook = 'network_admin_notices';
		}

		add_action( $hook, $callable );
	}

	/**
	 * Setup everything needed on plugin activation.
	 */
	public static function activate( $network_wide = false ) {

		// Make sure the needed user role exists.
		Backstage::maybe_create_user_role( $network_wide );

		// Make sure that the needed user exists.
		Backstage::maybe_create_customizer_user( $network_wide );
	}

	/**
	 * Remove anything related to the user and capabilities on plugin deactivation.
	 */
	public static function deactivate( $network_deactivating = false ) {

		// Make sure that the user is removed.
		Backstage::maybe_remove_customizer_user( $network_deactivating );

		// Make sure that the user role is removed.
		Backstage::maybe_remove_user_role( $network_deactivating );
	}

	/**
	 * Uninstall everything we added.
	 */
	public static function uninstall() {

		// If we are in a multisite installation, we need to remove data from all the sites.
		$network_wide = is_multisite();

		// Make sure that the user is removed.
		Backstage::maybe_remove_customizer_user( $network_wide );

		// Make sure that the user role is removed.
		Backstage::maybe_remove_user_role( $network_wide );

		// Remove any data saved in the DB.
		Backstage_Settings::cleanup();
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->_version;
	}

	/**
	 * Get the plugin main file absolute path.
	 *
	 * @return string
	 */
	public function get_file() {
		return $this->file;
	}

	/**
	 * Get the plugin basename (`directory/main_file.php`) that uniquely identifies each plugin.
	 *
	 * @return string
	 */
	public function get_basename() {
		return plugin_basename( $this->file );
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Get the plugin base URI.
	 *
	 * @return null|string
	 */
	public function get_baseuri() {
		return $this->plugin_baseuri;
	}

	/**
	 * Get the plugin base path.
	 *
	 * @return null|string
	 */
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
		$this->load_textdomain();
	}

	/**
	 * Registers the plugin's text domain path
	 * @since  1.0.0
	 */
	public function load_textdomain() {
		$loaded = load_plugin_textdomain( 'backstage', false, dirname( $this->get_basepath() ) . '/languages/' );

		if ( ! $loaded ) {
			$loaded = load_muplugin_textdomain( 'backstage', dirname( $this->get_basepath() ) . '/languages/' );
		}

		if ( ! $loaded ) {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'backstage' );
			$mofile = dirname( $this->get_basepath() ) . '/languages/backstage-' . $locale . '.mo';
			load_textdomain( 'backstage', $mofile );
		}
	}
}
