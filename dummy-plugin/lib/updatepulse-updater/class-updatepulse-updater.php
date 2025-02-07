<?php
/**
 * UpdatePulse Updater
 * Plugins and themes update library to enable with UpdatePulse Server
 *
 * @author Alexandre Froger
 * @version 2.0
 * @copyright Alexandre Froger - https://www.froger.me
 */

namespace Anyape\UpdatePulse\Updater\v2_0;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

use DateTime;
use DateTimeZone;
use WP_Error;
use RuntimeException;
use stdClass;
use YahnisElsts\PluginUpdateChecker\v5p3\PucFactory;

/* ================================================================================================ */
/*                                     UpdatePulse Updater                                          */
/* ================================================================================================ */

/**
* Copy/paste this section to your main plugin file or theme's functions.php and uncomment the sections below
* where appropriate to enable updates with UpdatePulse Server.
*
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
**/

/** Enable updates **/
/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
use Anyape\UpdatePulse\Updater\v2_0\UpdatePulse_Updater;
require_once __DIR__ . '/lib/updatepulse-updater/class-updatepulse-updater.php';

$prefix_updater = new UpdatePulse_Updater(
	wp_normalize_path( __FILE__ ),
	0 === strpos( __DIR__, WP_PLUGIN_DIR ) ? wp_normalize_path( __DIR__ ) : get_stylesheet_directory()
);
*/

/* ================================================================================================ */

if ( ! class_exists( __NAMESPACE__ . '\UpdatePulse_Updater' ) ) {

	class UpdatePulse_Updater {
		private $license_server_url;
		private $package_slug;
		private $update_server_url;
		private $package_path;
		private $package_url;
		private $update_checker;
		private $type;
		private $require_license;
		private $package_id;
		private $json_options;

		public function __construct( $package_file_path, $package_path ) {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			$this->package_path = trailingslashit( $package_path );

			if ( ! $wp_filesystem->exists( $this->package_path . 'updatepulse.json' ) ) {
				throw new RuntimeException(
					sprintf(
						'The package updater cannot find the updatepulse.json file in "%s". ',
						esc_html( htmlentities( $this->package_path ) )
					)
				);
			}

			$info               = array();
			$update_server_url  = $this->get_option( 'server' );
			$file_path          = '';
			$package_path_parts = explode( '/', untrailingslashit( $this->package_path ) );
			$package_slug       = $package_path_parts[ count( $package_path_parts ) - 1 ];

			$this->set_type();

			if ( 'Plugin' === $this->type ) {
				$this->package_url = plugin_dir_url( $package_file_path );
				$file_path         = $package_file_path;
			} elseif ( 'Theme' === $this->type ) {
				$this->package_url = trailingslashit( get_theme_root_uri() ) . $package_slug;
				$file_path         = dirname( $package_file_path ) . '/style.css';
			}

			if ( function_exists( 'get_file_data' ) ) {
				$info = get_file_data(
					$file_path,
					array(
						'licensed_with'   => 'Licensed With',
						'require_license' => 'Require License',
					)
				);
			}

			$require_license         = (
				isset( $info['require_license'] ) &&
				(
					strtolower( $info['require_license'] ) === 'yes' ||
					strtolower( $info['require_license'] ) === 'true'
				)
			);
			$package_file_path_parts = explode( '/', $package_file_path );
			$package_id_parts        = array_slice( $package_file_path_parts, -2, 2 );
			$package_id              = implode( '/', $package_id_parts );
			$this->package_id        = $package_id;
			$this->update_server_url = trailingslashit( $update_server_url ) . 'updatepulse-server-update-api/';
			$this->package_slug      = $package_slug;
			$this->require_license   = $require_license;
			$metadata_url            = trailingslashit( $this->update_server_url )
				. '?action=get_metadata&package_id=';
			$metadata_url           .= rawurlencode( $this->package_slug );

			if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5p3\PucFactory' ) ) {
				require $this->package_path . 'lib/plugin-update-checker/plugin-update-checker.php';
			}

			$this->update_checker = PucFactory::buildUpdateChecker( $metadata_url, $package_file_path );

			$this->update_checker->addQueryArgFilter( array( $this, 'filter_update_checks' ) );

			if ( $this->require_license ) {
				$this->license_server_url = trailingslashit( $update_server_url )
					. 'updatepulse-server-license-api/';

				$this->update_checker->addResultFilter( array( $this, 'set_license_error_notice_content' ) );

				if ( ! isset( $info['licensed_with'] ) || empty( $info['licensed_with'] ) ) {

					if ( 'Plugin' === $this->type ) {
						add_action( 'after_plugin_row_' . $this->package_id, array( $this, 'print_license_under_plugin' ), 10, 3 );
					} elseif ( 'Theme' === $this->type ) {
						add_action( 'admin_menu', array( $this, 'setup_theme_admin_menus' ), 10, 0 );

						add_filter( 'custom_menu_order', array( $this, 'alter_admin_appearence_submenu_order' ), 10, 1 );
						add_filter( 'wp_prepare_themes_for_js', array( $this, 'wp_prepare_themes_for_js' ), 10, 1 );
					}

					if ( ! $this->get_option( 'licenseKey' ) || ! $this->get_option( 'licenseSignature' ) ) {
						$suffix_type = strtolower( $this->type );

						add_filter( "auto_update_{$suffix_type}", array( $this, 'auto_update_package' ), 10, 2 );
						add_filter( "site_transient_update_{$suffix_type}s", array( $this, 'site_transient_update_packages' ), 10, 1 );
					}

					add_action( 'wp_ajax_upupdater_' . $this->package_id . '_activate_license', array( $this, 'activate_license' ), 10, 0 );
					add_action( 'wp_ajax_upupdater_' . $this->package_id . '_deactivate_license', array( $this, 'deactivate_license' ), 10, 0 );
					add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 99, 1 );
					add_action( 'admin_notices', array( $this, 'show_license_error_notice' ), 10, 0 );
					add_action( 'init', array( $this, 'load_textdomain' ), 10, 0 );
				} else {
					$this->update_option( 'licensed_with', $info['licensed_with'] );
				}

				add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 10, 2 );

				add_filter( 'upgrader_pre_install', array( $this, 'upgrader_pre_install' ), 10, 2 );
			}
		}

		/*******************************************************************
		 * Public methods
		 *******************************************************************/

		// WordPress hooks ---------------------------------------------

		public function wp_prepare_themes_for_js( $prepared_themes ) {

			if ( isset( $prepared_themes[ $this->package_slug ] ) ) {
				$prepared_themes[ $this->package_slug ]['description'] .= '<div>'
					. $this->get_license_form()
					. '</div>';
			}

			return $prepared_themes;
		}

		public function load_textdomain() {
			$i10n_path = trailingslashit( basename( $this->package_path ) ) . 'lib/updatepulse-updater/languages';

			if ( 'Plugin' === $this->type ) {
				load_plugin_textdomain( 'updatepulse-updater', false, $i10n_path );
			} else {
				load_theme_textdomain( 'updatepulse-updater', $i10n_path );
			}
		}

		public function setup_theme_admin_menus() {
			add_submenu_page(
				'themes.php',
				'Theme License',
				'Theme License',
				'manage_options',
				'theme-license',
				array( $this, 'theme_license_settings' )
			);
		}

		public function alter_admin_appearence_submenu_order( $menu_ord ) {
			global $submenu;

			$theme_menu     = $submenu['themes.php'];
			$reordered_menu = array();
			$first_key      = 0;
			$license_menu   = null;

			foreach ( $theme_menu as $key => $menu ) {

				if ( 'themes.php' === $menu[2] ) {
					$reordered_menu[ $key ] = $menu;
					$first_key              = $key;
				} elseif ( 'theme-license' === $menu[2] ) {
					$license_menu = $menu;
				} else {
					$reordered_menu[ $key + 1 ] = $menu;
				}
			}

			$reordered_menu[ $first_key + 1 ] = $license_menu;

			ksort( $reordered_menu );

			$submenu['themes.php'] = $reordered_menu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			return $menu_ord;
		}

		public function add_admin_scripts( $hook ) {
			$debug = (bool) ( constant( 'WP_DEBUG' ) );

			$condition = 'plugins.php' === $hook ||
				'themes.php' === $hook ||
				'appearance_page_theme-license' === $hook ||
				'appearance_page_parent-theme-license' === $hook &&
				! wp_script_is( 'updatepulse-updater-script' );

			if ( $condition ) {
				$js_ext = ( $debug ) ? '.js' : '.min.js';
				$ver_js = filemtime( $this->package_path . 'lib/updatepulse-updater/js/main' . $js_ext );
				$params = array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				);

				wp_enqueue_script(
					'updatepulse-updater-script',
					$this->package_url . '/lib/updatepulse-updater/js/main' . $js_ext,
					array( 'jquery' ),
					$ver_js,
					true
				);
				wp_localize_script( 'updatepulse-updater-script', 'UPupdater', $params );

				if ( ! wp_style_is( 'updatepulse-updater-style' ) ) {
					wp_register_style( 'updatepulse-updater-style', false, array(), '1.0' );
					wp_enqueue_style( 'updatepulse-updater-style' );

					$css = '
						.appearance_page_theme-license .license-error.notice {
							display: none;
						}

						.appearance_page_theme-license .postbox {
							max-width: 500px;
							margin: 0 auto;
						}

						.appearance_page_theme-license .wrap-license label {
							margin-bottom: 10px;
						}

						.appearance_page_theme-license .wrap-license input[type="text"],
						.theme-about .wrap-license input[type="text"] {
							width: 100%;
						}

						.appearance_page_theme-license .inside {
							margin: 2em;
						}

						.license-change {
							display: flex;
							flex-direction: column;
							gap: 1em;
							margin: 2em;
						}

						.plugin-update .license-change {
							flex-direction: row;
							align-items: center;
							margin: 1em 0;
						}

						.license-message {
							font-weight: bold;
						}

						.current-license-error {
						    background: #a00;
							color: white;
							padding: 0.25em;
						}
					';

					wp_add_inline_style( 'updatepulse-updater-style', $css );
				}
			}
		}

		public function filter_update_checks( $query_args ) {
			$licensed_with = $this->get_option( 'licensed_with' );
			$license       = null;

			if ( ! empty( $licensed_with ) ) {
				$query_args['licensed_with'] = $licensed_with;
				$license                     = $this->get_option( 'licenseKey', $licensed_with );
				$license_signature           = $this->get_option( 'licenseSignature', $licensed_with );
			} elseif ( $this->require_license ) {
				$license           = $this->get_option( 'licenseKey' );
				$license_signature = $this->get_option( 'licenseSignature' );
			}

			if ( $license && $license_signature ) {
				$query_args['license_key']       = rawurlencode( $license );
				$query_args['license_signature'] = rawurlencode( $license_signature );
			}

			$query_args['update_type'] = $this->type;

			return $query_args;
		}

		public function print_license_under_plugin( $plugin_file = null, $plugin_data = null, $status = null ) {
			$this->get_template(
				'plugin-page-license-row.php',
				array(
					'form'        => $this->get_license_form(),
					'plugin_file' => $plugin_file,
					'plugin_data' => $plugin_data,
					'slug'        => $this->package_slug,
					'status'      => $status,
				)
			);
		}

		public function activate_license() {
			$license_data = $this->do_query_license( 'activate' );

			if ( isset( $license_data->package_slug, $license_data->license_key ) ) {
				$this->update_option( 'licenseKey', $license_data->license_key );

				if ( isset( $license_data->license_signature ) ) {
					$this->update_option( 'licenseSignature', $license_data->license_signature );
				} else {
					$this->delete_option( 'licenseSignature' );
				}

				$license_data->may_deactivate  = true;
				$license_data->deactivate_text = __( 'Deactivate', 'updatepulse-updater' );

				if ( isset( $license_data->next_deactivate ) ) {
					$this->update_option( 'licenseNextDeactivate', $license_data->next_deactivate );

					if ( time() < $license_data->next_deactivate ) {
						$license_data->may_deactivate  = false;
						$license_data->deactivate_text = __( 'Deactivation is possible after:', 'updatepulse-updater' );
						$license_data->date_format     = get_option( 'date_format' ) . ' H:i:s';
					}
				} else {
					$this->delete_option( 'licenseNextDeactivate' );
				}
			} else {
				$error = new WP_Error( 'License', $license_data->message );

				if ( property_exists( $license_data, 'clear_key' ) && $license_data->clear_key ) {
					$this->delete_option( 'licenseSignature' );
					$this->delete_option( 'licenseKey' );
				}

				wp_send_json_error( $error );
			}

			if ( 'Theme' === $this->type ) {
				wp_update_themes();
			} else {
				wp_update_plugins();
			}

			$this->delete_option( 'licenseError' );
			wp_send_json_success( $license_data );
		}

		public function deactivate_license() {
			$license_data = $this->do_query_license( 'deactivate' );

			if ( isset( $license_data->package_slug, $license_data->license_key ) ) {
				$this->update_option( 'licenseKey', '' );

				if ( isset( $license_data->license_signature ) ) {
					$this->update_option( 'licenseSignature', '' );
				} else {
					$this->delete_option( 'licenseSignature' );
				}

				if ( isset( $license_data->next_deactivate ) ) {
					$this->update_option( 'licenseNextDeactivate', $license_data->next_deactivate );
				} else {
					$this->delete_option( 'licenseNextDeactivate' );
				}
			} else {
				$error = new WP_Error( 'License', $license_data->message );

				if ( $license_data->clear_key ) {
					$this->delete_option( 'licenseSignature' );
					$this->delete_option( 'licenseKey' );
				}

				wp_send_json_error( $error );
			}

			$transient_name  = 'Theme' === $this->type ? 'update_themes' : 'update_plugins';
			$update_packages = get_site_transient( $transient_name );

			if ( 'Theme' === $this->type ) {
				unset( $update_packages->response[ $this->package_slug ] );
			} else {
				unset( $update_packages->response[ $this->package_id ] );
			}

			set_site_transient( $transient_name, $update_packages );

			if ( 'Theme' === $this->type ) {
				wp_update_themes();
			} else {
				wp_update_plugins();
			}

			wp_send_json_success( $license_data );
		}

		public function set_license_error_notice_content( $package_info, $result ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			if (
				isset( $package_info->license_error ) &&
				is_object( $package_info->license_error )
			) {
				$license_data = $this->handle_license_errors( $package_info->license_error );

				$this->update_option( 'licenseError', $package_info->name . ': ' . $license_data->message );
			} else {
				$this->delete_option( 'licenseError' );
			}

			return $package_info;
		}

		public function show_license_error_notice() {
			$error = $this->get_option( 'licenseError' );

			if ( $error ) {
				$class = 'license-error license-error-' . $this->package_slug . ' notice notice-error is-dismissible';

				printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $error ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		public function upgrader_process_complete( $upgrader_object, $options ) {

			if ( 'update' === $options['action'] ) {

				if (
					'plugin' === $options['type'] &&
					isset( $options['plugins'] ) &&
					is_array( $options['plugins'] )
				) {

					foreach ( $options['plugins'] as $plugin ) {

						if ( $plugin === $this->package_id ) {
							$this->restore_updatepulse_options();
						}
					}
				}

				if (
					'theme' === $options['type'] &&
					isset( $options['themes'] ) &&
					is_array( $options['themes'] )
				) {

					foreach ( $options['themes'] as $theme ) {

						if ( $theme === $this->package_slug ) {
							$this->restore_updatepulse_options();
						}
					}
				}
			}
		}

		public function upgrader_pre_install( $_true, $hook_extra ) {

			if ( isset( $hook_extra['plugin'] ) ) {
				$plugin_to_update = $hook_extra['plugin'];

				if ( $plugin_to_update === $this->package_id ) {
					$this->save_updatepulse_options();
				}
			}

			if ( isset( $hook_extra['theme'] ) ) {
				$theme_to_update = $hook_extra['theme'];

				if ( $theme_to_update === $this->package_slug ) {
					$this->save_updatepulse_options();
				}
			}

			return $_true;
		}

		public function auto_update_package( $update, $info ) {

			if ( 'Theme' === $this->type && $this->package_slug === $info->theme ) {
				return false;
			} elseif ( 'Plugin' === $this->type && $this->package_id === $info->plugin ) {
				return false;
			}

			return $update;
		}

		public function site_transient_update_packages( $transient ) {

			if ( 'Theme' === $this->type ) {
				unset( $transient->response[ $this->package_slug ] );
			} else {
				unset( $transient->response[ $this->package_id ] );
			}

			return $transient;
		}

		// Misc. -------------------------------------------------------

		public function locate_template( $template_name, $load = false, $required_once = true ) {
			$template = apply_filters(
				'upupdater_' . $this->package_id . '_locate_template',
				$this->package_path . 'lib/updatepulse-updater/templates/' . $template_name,
				$template_name,
				str_replace( $template_name, '', $this->package_path . 'lib/updatepulse-updater/templates/' )
			);

			if ( $load && '' !== $template ) {
				load_template( $template, $required_once );
			}

			return $template;
		}

		public function get_template( $template_name, $args = array(), $load = true, $required_once = false ) {
			$template_name = apply_filters(
				'upupdater_' . $this->package_id . '_get_template_name',
				$template_name,
				$args
			);
			$template_args = apply_filters(
				'upupdater_' . $this->package_id . '_get_template_args',
				$args,
				$template_name
			);

			if ( ! empty( $template_args ) ) {

				foreach ( $template_args as $key => $arg ) {
					$key = is_numeric( $key ) ? 'var_' . $key : $key;

					set_query_var( $key, $arg );
				}
			}

			return $this->locate_template( $template_name, $load, $required_once );
		}

		public function theme_license_settings() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			$theme = wp_get_theme();
			$title = __( 'Theme License - ', 'updatepulse-updater' ) . $theme->get( 'Name' );

			$this->get_template(
				'theme-page-license.php',
				array(
					'form'  => $this->get_license_form(),
					'title' => $title,
					'theme' => $theme,
				)
			);
		}

		/*******************************************************************
		 * Protected methods
		 *******************************************************************/

		protected static function is_plugin_file( $absolute_path ) {
			$plugin_dir    = wp_normalize_path( WP_PLUGIN_DIR );
			$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );

			if (
				( 0 === strpos( $absolute_path, $plugin_dir ) ) ||
				( 0 === strpos( $absolute_path, $mu_plugin_dir ) )
			) {
				return true;
			}

			if ( ! is_file( $absolute_path ) ) {
				return false;
			}

			if ( function_exists( 'get_file_data' ) ) {
				$headers = get_file_data( $absolute_path, array( 'Name' => 'Plugin Name' ), 'plugin' );

				return ! empty( $headers['Name'] );
			}

			return false;
		}

		protected static function get_theme_directory_name( $absolute_path ) {

			if ( is_file( $absolute_path ) ) {
				$absolute_path = dirname( $absolute_path );
			}

			if ( file_exists( $absolute_path . '/style.css' ) ) {
				return basename( $absolute_path );
			}

			return null;
		}

		protected function get_option( $option, $other_package_slug = false ) {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			if ( $other_package_slug && $this->package_path ) {
				$parts           = explode( '/', untrailingslashit( $this->package_path ) );
				$index           = count( $parts ) - 1;
				$parts[ $index ] = $other_package_slug;
				$package_path    = trailingslashit( implode( '/', $package_path ) );

				if ( $wp_filesystem->exists( $package_path . 'updatepulse.json' ) ) {
					$updatepulse_json = $wp_filesystem->get_contents( $package_path . 'updatepulse.json' );
				}

				if ( $updatepulse_json ) {
					$json_options = json_decode( $updatepulse_json, true );

					if ( isset( $json_options[ $option ] ) ) {
						return $json_options[ $option ];
					}
				}
			} else {

				if (
					$this->package_path &&
					! isset( $this->json_options ) &&
					$wp_filesystem->exists( $this->package_path . 'updatepulse.json' )
				) {
					$updatepulse_json = $wp_filesystem->get_contents( $this->package_path . 'updatepulse.json' );

					if ( $updatepulse_json ) {
						$this->json_options = json_decode( $updatepulse_json, true );
					}
				}

				if ( isset( $this->json_options[ $option ] ) ) {
					return $this->json_options[ $option ];
				}
			}

			return '';
		}

		protected function update_option( $option, $value ) {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			if ( ! isset( $this->json_options ) ) {
				$updatepulse_json = $wp_filesystem->get_contents( $this->package_path . 'updatepulse.json' );

				if ( $updatepulse_json ) {
					$this->json_options = json_decode( $updatepulse_json, true );
				}
			}

			$this->json_options[ $option ] = $value;
			$updatepulse_json              = wp_json_encode(
				$this->json_options,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			);

			$wp_filesystem->put_contents( $this->package_path . 'updatepulse.json', $updatepulse_json, FS_CHMOD_FILE );
		}

		protected function delete_option( $option ) {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			if ( ! isset( $this->json_options ) ) {
				$updatepulse_json = $wp_filesystem->get_contents( $this->package_path . 'updatepulse.json' );

				if ( $updatepulse_json ) {
					$this->json_options = json_decode( $updatepulse_json, true );
				}
			}

			if ( isset( $this->json_options[ $option ] ) ) {
				unset( $this->json_options[ $option ] );

				$updatepulse_json = wp_json_encode(
					$this->json_options,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
				);

				$wp_filesystem->put_contents(
					$this->package_path . 'updatepulse.json',
					$updatepulse_json,
					FS_CHMOD_FILE
				);
			}
		}

		protected function save_updatepulse_options() {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			if ( ! isset( $this->json_options ) ) {
				$updatepulse_json = $wp_filesystem->get_contents( $this->package_path . 'updatepulse.json' );

				if ( $updatepulse_json ) {
					$this->json_options = json_decode( $updatepulse_json, true );
				}
			}

			update_option( 'updatepulse_' . $this->package_slug . '_options', $this->json_options );
		}

		protected function restore_updatepulse_options() {
			$updatepulse_options = get_option( 'updatepulse_' . $this->package_slug . '_options' );

			if ( $updatepulse_options ) {
				global $wp_filesystem;

				if ( ! isset( $wp_filesystem ) ) {
					include_once ABSPATH . 'wp-admin/includes/file.php';

					WP_Filesystem();
				}

				$server                        = $this->get_option( 'server' );
				$updatepulse_options['server'] = $server;
				$updatepulse_json              = wp_json_encode(
					$updatepulse_options,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
				);

				$wp_filesystem->put_contents(
					$this->package_path . 'updatepulse.json',
					$updatepulse_json,
					FS_CHMOD_FILE
				);
				delete_option( 'updatepulse_' . $this->package_slug . '_options' );
			}
		}

		protected function do_query_license( $query_type ) {

			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'license_nonce' ) ) {
				$error = new WP_Error( 'License', 'Unauthorised access.' );

				wp_send_json_error( $error );
			}

			$license_key        = $_REQUEST['license_key'];
			$this->package_slug = $_REQUEST['package_slug'];

			if ( empty( $license_key ) ) {
				$error = new WP_Error( 'License', 'A license key is required.' );

				wp_send_json_error( $error );
			}

			$api_params = array(
				'action'          => $query_type,
				'license_key'     => $license_key,
				'allowed_domains' => $_SERVER['SERVER_NAME'],
				'package_slug'    => rawurlencode( $this->package_slug ),
				'locale'          => get_locale(),
			);
			$query      = esc_url_raw( add_query_arg( $api_params, $this->license_server_url ) );
			$response   = wp_remote_get(
				$query,
				array(
					'timeout'   => 20,
					'sslverify' => true,
				)
			);

			if ( is_wp_error( $response ) ) {
				$license_data            = new stdClass();
				$license_data->clear_key = true;
				$license_data->message   = $response->get_error_message();

				return $license_data;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				$license_data          = new stdClass();
				$license_data->message = __( 'Unexpected Error! The query to retrieve the license data returned a malformed response.', 'updatepulse-updater' );

				return $license_data;
			}

			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$license_data = $this->handle_license_errors( $license_data );
			}

			return $license_data;
		}

		protected function handle_license_errors( $license_data ) {
			$timezone   = new DateTimeZone( wp_timezone_string() );
			$return     = array( 'clear_key' => false );
			$error_code = ( isset( $license_data->code ) ) ? $license_data->code : 'unknown';
			$error_data = ( isset( $license_data->data ) ) ? $license_data->data : (object) array();

			switch ( $error_code ) {
				case 'license_already_activated':
					$return['message'] = __( 'The license is already in use for this domain.', 'updatepulse-updater' );

					break;
				case 'max_domains_reached':
					$return['clear_key'] = true;
					$return['message']   = __( 'The license has reached the maximum number of activations and cannot be activated for this domain.', 'updatepulse-updater' );

					break;
				case 'license_already_deactivated':
					$return['clear_key'] = true;
					$return['message']   = __( 'The license is already inactive for this domain.', 'updatepulse-updater' );

					break;
				case 'too_early_deactivation':
					$return['message'] = __( 'The license may not be deactivated yet.', 'updatepulse-updater' );

					break;
				case 'illegal_license_status':
					$status = ( isset( $error_data->status ) ) ? $error_data->status : 'unknown';

					if ( 'blocked' === $status ) {
						$return['message'] = __( 'The license is blocked and cannot be updated anymore. Please use another license key.', 'updatepulse-updater' );
					} elseif ( 'pending' === $status ) {
						$return['clear_key'] = true;
						$return['message']   = __( 'The license has not been activated and its status is still pending. Please try again or use another license key.', 'updatepulse-updater' );
					} elseif ( 'invalid' === $status ) {
						$return['clear_key'] = true;
						$return['message']   = __( 'The provided license key is invalid. Please use another license key.', 'updatepulse-updater' );
					} elseif ( 'expired' === $status ) {

						if ( isset( $error_data->date_expiry ) ) {
							$date = new DateTime( 'now', $timezone );

							$date->setTimestamp( intval( $license_data->date_expiry ) );

							$return['message'] = sprintf(
								// translators: the license expiry date
								__( 'The license expired on %s and needs to be renewed to be updated.', 'updatepulse-updater' ),
								$date->format( get_option( 'date_format' ) )
							);
						} else {
							$return['message'] = __( 'The license expired and needs to be renewed to be updated.', 'updatepulse-updater' );
						}
					}

					break;
				case 'invalid_license_key':
					$return['clear_key'] = true;
					$return['message']   = __( 'The provided license key does not appear to be valid. Please use another license key.', 'updatepulse-updater' );

					break;
				case 'invalid_license':
					if ( 'Plugin' === $this->type ) {
						$return['message'] = __( 'An active license is required to update the plugin. Please provide a valid license key in Plugins > Installed Plugins.', 'updatepulse-updater' );
					} else {
						$return['message'] = __( 'An active license is required to update the theme. Please provide a valid license key in Appearence > Theme License.', 'updatepulse-updater' );
					}

					break;
				case 'license_error':
					$return['message']  = __( 'An unexpected error has occured.', 'updatepulse-updater' );
					$return['message'] .= '<br>' . $error_data->message;

					break;
				case 'unexpected_error':
				default:
					$return['message'] = __( 'An unexpected error has occured. Please try again. If the problem persists, please contact support.', 'updatepulse-updater' );

					break;
			}

			return (object) $return;
		}

		protected function get_license_form() {
			$license         = $this->get_option( 'licenseKey' );
			$next_deactivate = $this->get_option( 'licenseNextDeactivate' );
			$may_deactivate  = false;
			$deactivate_text = '';

			if ( time() < $next_deactivate ) {
				$deactivate_text = __( 'Deactivation is possible after:', 'updatepulse-updater' );
			} else {
				$may_deactivate  = true;
				$deactivate_text = __( 'Deactivate', 'updatepulse-updater' );
			}

			ob_start();

			$this->get_template(
				'license-form.php',
				array(
					'license'         => $license,
					'package_id'      => $this->package_id,
					'package_slug'    => $this->package_slug,
					'show_license'    => ( ! empty( $license ) ),
					'may_deactivate'  => $may_deactivate,
					'deactivate_text' => $deactivate_text,
					'next_deactivate' => intval( $next_deactivate ),
					'date_format'     => get_option( 'date_format' ) . ' H:i:s',
				)
			);

			return ob_get_clean();
		}

		protected function set_type() {
			$theme_directory = self::get_theme_directory_name( $this->package_path );

			if ( self::is_plugin_file( $this->package_path ) ) {
				$this->type = 'Plugin';
			} elseif ( null !== $theme_directory ) {
				$this->type = 'Theme';
			} else {
				throw new RuntimeException(
					sprintf(
						'The package updater cannot determine if "%s" is a plugin or a theme. ',
						esc_html( htmlentities( $this->package_path ) )
					)
				);
			}
		}
	}
}
