<?php
/**
 * @wordpress-plugin
 * Plugin Name: Backstage
 * Plugin URI:  https://wordpress.org/plugins/customizer-guest-demo-access
 * Description: Customizer Demo Access for Everyone
 * Version: 1.2.0
 * Author: Pixelgrade
 * Author URI: https://pixelgrade.com
 * Author Email: contact@pixelgrade.com
 * Requires at least: 4.9.0
 * Tested up to: 5.2.3
 * Text Domain: backstage
 * License:     GPL-2.0 or later.
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'includes/lib/abstracts/class-Backstage_Singleton_Registry.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/lib/abstracts/class-Backstage_Plugin_Init.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/lib/class-Backstage_Array.php' );
require_once( plugin_dir_path( __FILE__ ) . 'extras.php' );

/**
 * Returns the main instance of Backstage_Plugin to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Backstage_Plugin Backstage_Plugin instance.
 */
function Backstage_Plugin() {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-Backstage_Plugin.php' );

	return Backstage_Plugin::getInstance( __FILE__, '1.2.0' );
}

Backstage_Plugin();
