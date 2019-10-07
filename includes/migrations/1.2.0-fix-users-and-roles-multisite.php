<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( ! is_multisite() || is_plugin_active_for_network( Backstage_Plugin()->get_basename() ) ) {
	return;
}

$user = get_user_by( 'login', Backstage::$username );
if ( ! $user ) {
	Backstage::maybe_create_user_role( false );
	$user_id = Backstage::maybe_create_customizer_user( false );
} else {
	$user_id = $user->ID;
}

// Bail without a user ID.
if ( ! $user_id ) {
	return;
}

// Make sure that roles and user are only assigned to multisite blogs that actually have the plugin activated.
$sites = get_sites( array( 'fields' => 'ids' ) );
foreach ( $sites as $site_id ) {
	switch_to_blog( $site_id );

	if ( in_array( Backstage_Plugin()->get_basename(), (array) get_option( 'active_plugins', array() ) ) ) {
		Backstage::maybe_create_user_role( false );

		add_user_to_blog( $site_id, $user_id, Backstage::$user_role );
	} else {
		// Make sure that the user is removed.
		Backstage::maybe_remove_customizer_user( false );

		// Make sure that the user role is removed.
		Backstage::maybe_remove_user_role( false );
	}

	restore_current_blog();
}
