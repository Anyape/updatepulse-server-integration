<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ================================================================================================                                  UpdatePulse Server
================================================================================================*/

/**
* WARNING - READ FIRST:
*
* Before deploying the plugin or theme, make sure to change the value of `server` in updatepulse.json
* with the URL of the server where UpdatePulse Server is installed.
*
* Also change $prefix_updater below - replace "prefix" in this variable's name with a unique prefix
*
* If the plugin or theme requires a license, change the header `Require License` to either `yes`, `true`, or `1`
* in the main plugin file or the `style.css` file.
*
* If the plugin or theme uses the license of another plugin or theme, add the header `Licensed With`
* with the slug of the plugin or theme that provides the license in the main plugin file or the `style.css` file.
*
* @see https://github.com/anyape/updatepulse-server/tree/main/integration/dummy-theme/lib/updatepulse-updater
**/

use Anyape\UpdatePulse\Updater\v2_0\UpdatePulse_Updater;
require_once plugin_dir_path( __FILE__ ) . 'lib/updatepulse-updater/class-updatepulse-updater.php';

/** Enable plugin updates**/
$dummy_plugin_updater = new UpdatePulse_Updater(
	wp_normalize_path( __FILE__ ),
	0 === strpos( __DIR__, WP_PLUGIN_DIR ) ? wp_normalize_path( __DIR__ ) : get_stylesheet_directory()
);

/* ================================================================================================ */

/* ================================================================================================ */

function dummy_theme_enqueue_styles() {
	$parent_style = 'twentyseventeen-style';

	wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css', array(), filemtime( __FILE__ ) );
	wp_enqueue_style(
		'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( $parent_style ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dummy_theme_enqueue_styles' );
