<?php
/**
 * @wordpress-plugin
 * Plugin Name: Customizer Guest Demo Access
 * Plugin URI:  https://wordpress.org/plugins/customizer-guest-demo-access
 * Description: Allow your visitors to access the Customizer and play with it.
 * Version: 1.0.0
 * Author: Pixelgrade
 * Author URI: https://pixelgrade.com
 * Author Email: contact@pixelgrade.com
 * Requires at least: 4.9.0
 * Tested up to: 4.9.8
 * Text Domain: cgda
 * License:     GPL-2.0 or later.
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'includes/lib/abstracts/class-Singleton_Registry.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/lib/abstracts/class-Plugin_Init.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/lib/class-Array.php' );
require_once( plugin_dir_path( __FILE__ ) . 'extras.php' );

/**
 * Returns the main instance of CGDA_Plugin to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return CGDA_Plugin CGDA_Plugin instance.
 */
function CGDA_Plugin() {

	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-CGDA_Plugin.php' );

	return CGDA_Plugin::getInstance( __FILE__, '1.0.0' );
}

CGDA_Plugin();
