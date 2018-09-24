<?php
/**
 * Extras File
 *
 * Contains extra (helper) functions.
 *
 * @package Customizer-Guest-Demo-Access
 */

/**
 * Get the link for visitors to auto-login and access the Customizer.
 *
 * @return string
 */
function cgda_get_customizer_link() {
	global $wp;

	// This should not be used in the admin.
	if ( is_admin() ) {
		return '';
	}

	$auto_login_key = cgda_get_setting( 'auto_login_key' );
	if ( empty( $auto_login_key ) ) {
		$auto_login_key = 'cgda_auto_login';
	}

	// First, get the current frontend URL.
	$current_url = home_url( add_query_arg( array(), $wp->request ) );

	// Now get the Customizer URL.
	$link = wp_customize_url();
	// Next we need to add our own parameters to it, including the return URL (the current page).
	$auto_login_hash = wp_hash( $current_url );

	$link = add_query_arg( $auto_login_key, urlencode( $auto_login_hash ), $link );
	$link = add_query_arg( 'return_url', urlencode( $current_url ), $link );

	return apply_filters( 'cgda_get_customzer_link', $link );
}


/**
 * Helper function to prefix options, metas IDs.
 *
 * @param string $option The option ID to prefix.
 * @param string $separator Optional. The separator to use between prefix and the rest.
 * @return string
 */
function cgda_prefix( $option, $separator = '_' ) {
	return CGDA_Metaboxes::getInstance()->prefix( $option, $separator );
}

/**
 * Helper function to get/return the CGDA_Settings object
 *
 * @return CGDA_Settings
 */
function cgda_settings() {
	return CGDA_Settings::getInstance();
}

/**
 * Wrapper function around cmb2_get_option.
 *
 * @since 1.0.0
 *
 * @param  string  $setting Option key without any prefixing.
 * @param mixed $default Optional. The default value to retrieve in case the option was not found.
 * @return mixed        Option value
 */
function cgda_get_setting( $setting, $default = false ) {
	$instance = cgda_settings();
	return cmb2_get_option( $instance->key, $instance->prefix( $setting ), $default );
}

/**
 * Wrapper function around cmb2_update_option.
 *
 * @since  1.0.0
 *
 * @param  string  $setting Option key without any prefixing.
 * @param  mixed   $value      Value to update data with.
 * @param  boolean $single     Whether data should not be an array.
 * @return bool           Success/Failure
 */
function cgda_update_setting( $setting, $value, $single = true ) {
	$instance = cgda_settings();
	return cmb2_update_option( $instance->key, $instance->prefix( $setting ), $value, $single );
}

function cgda_array_sort( $array, $on, $order = SORT_ASC ) {
	$new_array = array();
    $sortable_array = array();

    if ( count( $array ) > 0 ) {
	    foreach ( $array as $k => $v ) {
		    if ( is_array( $v ) ) {
			    foreach ( $v as $k2 => $v2 ) {
				    if ( $k2 == $on ) {
					    $sortable_array[ $k ] = $v2;
				    }
			    }
		    } else {
			    $sortable_array[ $k ] = $v;
		    }
	    }

	    switch ( $order ) {
		    case SORT_ASC:
			    asort( $sortable_array );
			    break;
		    case SORT_DESC:
			    arsort( $sortable_array );
			    break;
	    }

	    foreach ( $sortable_array as $k => $v ) {
		    $new_array[ $k ] = $array[ $k ];
	    }
    }

    return $new_array;
}

function cgda_get_string_between( $string, $start, $end = null ){
	$string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);

    // If we couldn't find the end marker or it is null we will return everything 'till the end
    if ( null === $end || false === strpos($string, $end, $ini) ) {
		return substr($string, $ini);
    } else {
	    $len = strpos( $string, $end, $ini ) - $ini;

	    return substr( $string, $ini, $len );
    }
}

/**
 * Get the complete current URL including query args
 * @return string
 */
function cgda_get_current_url() {
	//@todo we should do this is more standard WordPress way
	$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
	$url .= $_SERVER["REQUEST_URI"];
	return $url;
}

function cgda_to_bool( $value ) {
	if ( empty( $value ) ) {
		return false;
	}

	//see this for more info: http://stackoverflow.com/questions/7336861/how-to-convert-string-to-boolean-php
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Return a boolean value for the current state of a checkbox (it usually has yes or no value)
 *
 * @param $post_id    int
 * @param $meta_key   string
 *
 * @return boolean
 */
function cgda_meta_to_bool( $post_id, $meta_key ) {

	$result = get_post_meta( $post_id, $meta_key, true );

	return cgda_to_bool( $result );
}

/**
 * Check if the $haystack contains any of the needles.
 *
 * @param string $haystack
 * @param array $needles
 *
 * @return bool
 */
function cgda_string_contains_any( $haystack, $needles ) {
	foreach ( $needles as $needle ) {
		if ( false !== strpos( $haystack, $needle ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Wrapper for _doing_it_wrong.
 *
 * Taken from WooCommerce - see wc_doing_it_wrong().
 *
 * @since  1.0.0
 * @param string $function Function used.
 * @param string $message Message to log.
 * @param string $version Version the message was added in.
 */
function cgda_doing_it_wrong( $function, $message, $version ) {
	// @codingStandardsIgnoreStart
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( is_ajax() ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( $function, $message, $version );
	}
	// @codingStandardsIgnoreEnd
}

/**
 * Autoloads the files in a theme's directory.
 *
 * We do not support child themes at this time.
 *
 * @param string $path The path of the theme directory to autoload files from.
 * @param int    $depth The depth to which we should go in the directory. A depth of 0 means only the files directly in that
 *                     directory. Depth of 1 means also the first level subdirectories, and so on.
 *                     A depth of -1 means load everything.
 * @param string $method The method to use to load files. Supports require, require_once, include, include_once.
 *
 * @return false|int False on failure, otherwise the number of files loaded.
 */
function cgda_autoload_dir( $path, $depth = 0, $method = 'require_once' ) {
	// If the $path starts with the absolute path to the WP install or the plugin directory, not good
	if ( strpos( $path, ABSPATH ) === 0 && strpos( $path, plugin_dir_path( __FILE__ ) ) !== 0 ) {
		cgda_doing_it_wrong( __FUNCTION__, esc_html__( 'Please provide only paths in the Style Manager for autoloading.', 'cgda' ), null );
		return false;
	}

	if ( ! in_array( $method, array( 'require', 'require_once', 'include', 'include_once' ) ) ) {
		cgda_doing_it_wrong( __FUNCTION__, esc_html__( 'We support only require, require_once, include, and include_once.', 'cgda' ), null );
		return false;
	}

	// If we have a relative path, make it absolute.
	if ( strpos( $path, plugin_dir_path( __FILE__ ) ) !== 0 ) {
		// Delete any / at the beginning.
		$path = ltrim( $path, '/' );

		// Add the current plugin path
		$path = trailingslashit( plugin_dir_path( __FILE__ ) ) . $path;
	}

	// Start the counter
	$counter = 0;

	$iterator = new DirectoryIterator( $path );
	// First we will load the files in the directory
	foreach ( $iterator as $file_info ) {
		if ( ! $file_info->isDir() && ! $file_info->isDot() && 'php' == strtolower( $file_info->getExtension() ) ) {
			switch ( $method ) {
				case 'require':
					require $file_info->getPathname();
					break;
				case 'require_once':
					require_once $file_info->getPathname();
					break;
				case 'include':
					include $file_info->getPathname();
					break;
				case 'include_once':
					include_once $file_info->getPathname();
					break;
				default:
					break;
			}

			$counter ++;
		}
	}

	// Now we load files in subdirectories if that's the case
	if ( $depth > 0 || -1 === $depth ) {
		if ( $depth > 0 ) {
			$depth --;
		}
		$iterator->rewind();
		foreach ( $iterator as $file_info ) {
			if ( $file_info->isDir() && ! $file_info->isDot() ) {
				$counter += cgda_autoload_dir( $file_info->getPathname(), $depth, $method );
			}
		}
	}

	return $counter;
}

/**
 * Does the same thing the JS encodeURIComponent() does
 *
 * @param string $str
 *
 * @return string
 */
function cgda_encodeURIComponent( $str ) {
	//if we get an array we just let it be
	if ( is_string( $str ) ) {
		$revert = array( '%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')' );

		$str = strtr( rawurlencode( $str ), $revert );
	} else {
		var_dump( 'boooom' );
		die;
	}

	return $str;
}

/**
 * Does the same thing the JS decodeURIComponent() does
 *
 * @param string $str
 *
 * @return string
 */
function cgda_decodeURIComponent( $str ) {
	// If we get an array we just let it be
	if ( is_string( $str ) ) {
		$revert = array( '!' => '%21', '*' => '%2A', "'" => '%27', '(' => '%28', ')' => '%29' );
		$str    = rawurldecode( strtr( $str, $revert ) );
	}

	return $str;
}

/**
 * Checks whether an array is associative or not
 *
 * @param array $array
 *
 * @return bool
 */
function cgda_is_assoc( $array ) {

	if ( ! is_array( $array ) ) {
		return false;
	}

	// Keys of the array
	$keys = array_keys( $array );

	// If the array keys of the keys match the keys, then the array must
	// not be associative (e.g. the keys array looked like {0:0, 1:1...}).
	return array_keys( $keys ) !== $keys;
}