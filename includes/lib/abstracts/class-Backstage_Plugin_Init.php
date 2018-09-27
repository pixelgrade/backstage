<?php
/**
 * Document for abstract class Backstage_Plugin_Init.
 *
 * @package Backstage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class to check php and plugin version, and do updates if necessary.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 * @since       1.0.0
 */
abstract class Backstage_Plugin_Init extends Backstage_Singleton_Registry {

	/**
	 * Minimal Required PHP Version
	 * @var string
	 * @access  private
	 * @since   1.0.0
	 */
	protected $minimalRequiredPhpVersion = '5.6.0';

	/**
	 * Plugin Name.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	public $plugin_name;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * New versions installed by this upgrade
	 * @var     array
	 * @access  private
	 * @since   1.0.0
	 */
	private $new_versions;


	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @param string $name Optional. The plugin name.
	 * @return  void
	 */
	protected function __construct( $name = '' ) {

		$this->plugin_name = $name;
	} // End __construct ().

	/**
	 * Provide a useful error message when the user's PHP version is less than the required version
	 */
	public function notice_php_version_wrong() {
		$allowed = array(
			'div'    => array(
				'class' => array(),
				'id'    => array(),
			),
			'p'      => array(),
			'br'     => array(),
			'strong' => array(),
		);

		$html = '<div class="updated fade">' .
		        sprintf( esc_html__( 'Error: plugin "%s" requires a newer version of PHP to be running.', 'backstage' ), $this->plugin_name ) .
		        '<br/>' . sprintf( esc_html__( 'Minimal version of PHP required: %s', 'backstage' ), '<strong>' . $this->minimalRequiredPhpVersion . '</strong>' ) .
				'<br/>' . sprintf( esc_html__( 'Your server\'s PHP version: %s', 'backstage' ), '<strong>' . phpversion() . '</strong>' ) .
				'</div>';
		echo wp_kses( $html, $allowed );
	}

	/**
	 * PHP version check
	 */
	protected function php_version_check() {

		if ( version_compare( phpversion(), $this->minimalRequiredPhpVersion ) < 0 ) {
			add_action( 'admin_notices', array( $this, 'notice_php_version_wrong' ) );

			return false;
		}

		return true;
	}

	/**
	 * Check plugin version and do any necessary actions if new version has been installed.
	 */
	public function upgrade() {

		$upgradeOk    = true;
		$savedVersion = $this->get_version_saved();
		$newVersion   = $this->_version;
		$new_versions = array();

		if ( $this->is_version_less_than( $savedVersion, $newVersion ) ) {
			$new_versions[] = $newVersion;
		}

		// Post-upgrade, save the new version in the options.
		if ( $upgradeOk && $savedVersion !== $newVersion ) {
			$this->new_versions = $new_versions;
			add_action( 'admin_notices', array( $this, 'notice_new_version' ) );
			$this->save_version_number();
		}

	}

	/**
	 * Compares version numbers and determines if the result is less than zero.
	 *
	 * @param  string $version1 A version string such as '1', '1.1', '1.1.1', '2.0', etc.
	 * @param  string $version2 A version string such as '1', '1.1', '1.1.1', '2.0', etc.
	 *
	 * @return bool true if version_compare of $versions1 and $version2 shows $version1 as earlier.
	 */
	public function is_version_less_than( $version1, $version2 ) {
		return ( version_compare( $version1, $version2 ) < 0 );
	}

	/**
	 * Provide a useful error message if the Plugin has been updated.
	 */
	public function notice_new_version() {

		foreach ( $this->new_versions as $new_version ) {
			echo '<div class="notice notice-success is-dismissible"><p>' .
			     sprintf( __( 'The <strong>%s</strong> plugin has been updated to version %s. Enjoy!', 'backstage' ), $this->plugin_name, $new_version ) .
			     '</p></div>';
		}
	}

	/**
	 * Get the version string in the options. This is useful if you install upgrade and
	 * need to check if an older version was installed to see if you need to do certain
	 * upgrade housekeeping (e.g. changes to DB schema).
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @return null
	 */
	public function get_version_saved() {

		return Backstage_Options::getInstance()->get_option( 'version' );
	}

	/**
	 * Save the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function save_version_number() {
		Backstage_Options::getInstance()->update_option( 'version', $this->_version );
	} // End save_version_number ()

	public function debug( $what ) {
		echo '<pre style="margin-left: 160px">';
		var_dump( $what );
		echo '</pre>';
	}
}
