<?php
/*
Plugin Name: UpdatePulse Server Plugins Optimizer
Description: Bypass plugins when handling API requests.
Version: 2.0
Author: Alexandre Froger
Author URI: https://froger.me/
*/

/**
 * Keep only a selection of plugins (@see upserv_mu_optimizer_active_plugins filter below)
 *
 * Place this file in a wp-content/mu-plugin folder and it will be loaded automatically.
 *
 * Use the following hooks in your own MU plugin for customization purposes:
 * - @see `upserv_mu_optimizer_active_plugins` - filter; filter the plugins to be kept active during UpdatePulse Server  API calls
 *
 * @see `updatepulse-server/updatepulse-server.php` and documentation for more MU plugin hooks.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function upserv_mu_optimizer_plugins() {
	$active_plugins = apply_filters(
		'upserv_mu_optimizer_active_plugins',
		array(
			'updatepulse-server/updatepulse-server.php',
			'action-scheduler/action-scheduler.php',
		)
	);

	add_filter(
		'option_active_plugins',
		function ( $plugins ) use ( $active_plugins ) {

			foreach ( $plugins as $key => $plugin ) {

				if ( ! in_array( $plugin, $active_plugins, true ) ) {
					unset( $plugins[ $key ] );
				}
			}

			return $plugins;
		},
		PHP_INT_MAX - 100,
		1
	);

	add_filter(
		'upserv_mu_optimizer_info',
		function ( $info ) use ( $active_plugins ) {
			$info['active_plugins'] = $active_plugins;

			return $info;
		},
		10,
		1
	);
}

if ( ! defined( 'WP_CLI' ) ) {
	add_action( 'upserv_mu_optimizer_default_applied', 'upserv_mu_optimizer_plugins' );
}
