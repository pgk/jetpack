<?php
/**
 * Register WP REST API endpoints for Jetpack.
 *
 * @author Automattic
 */

/**
 * Disable direct access.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_Error for error messages.
require_once ABSPATH . '/wp-includes/class-wp-error.php';

// Register endpoints when WP REST API is initialized.
add_action( 'rest_api_init', array( 'Jetpack_Core_Json_Api_Endpoints', 'register_endpoints' ) );

/**
 * Class Jetpack_Core_Json_Api_Endpoints
 *
 * @since 4.1.0
 */
class Jetpack_Core_Json_Api_Endpoints {

	public static $user_permissions_error_msg;

	function __construct() {
		self::$user_permissions_error_msg = esc_html__(
			'You do not have the correct user permissions to perform this action.
			Please contact your site admin if you think this is a mistake.',
			'jetpack'
		);
	}

	/**
	 * Declare the Jetpack REST API endpoints.
	 *
	 * @since 4.1.0
	 */
	public static function register_endpoints() {
		// Get current connection status of Jetpack
		register_rest_route( 'jetpack/v4', '/connection-status', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::jetpack_connection_status',
		) );

		// Fetches a fresh connect URL
		register_rest_route( 'jetpack/v4', '/connect-url', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::build_connect_url',
			'permission_callback' => __CLASS__ . '::connect_url_permission_callback',
		) );

		// Get current user connection data
		register_rest_route( 'jetpack/v4', '/user-connection-data', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::get_user_connection_data',
			'permission_callback' => __CLASS__ . '::get_user_connection_data_permission_callback',
		) );

		// Disconnect site from WordPress.com servers
		register_rest_route( 'jetpack/v4', '/disconnect/site', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::disconnect_site',
			'permission_callback' => __CLASS__ . '::disconnect_site_permission_callback',
		) );

		// Disconnect/unlink user from WordPress.com servers
		register_rest_route( 'jetpack/v4', '/unlink', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::unlink_user',
			'permission_callback' => __CLASS__ . '::link_user_permission_callback',
			'args' => array(
				'id' => array(
					'default' => get_current_user_id(),
					'validate_callback' => __CLASS__  . '::validate_posint',
				),
			),
		) );

		register_rest_route( 'jetpack/v4', '/recheck-ssl', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::recheck_ssl',
		) );

		// Get current site data
		register_rest_route( 'jetpack/v4', '/site', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::get_site_data',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// Return all modules
		register_rest_route( 'jetpack/v4', '/modules', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::get_modules',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// Return a single module
		register_rest_route( 'jetpack/v4', '/module/(?P<slug>[a-z\-]+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::get_module',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// Activate a module
		register_rest_route( 'jetpack/v4', '/module/(?P<slug>[a-z\-]+)/activate', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::activate_module',
			'permission_callback' => __CLASS__ . '::manage_modules_permission_check',
		) );

		// Deactivate a module
		register_rest_route( 'jetpack/v4', '/module/(?P<slug>[a-z\-]+)/deactivate', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::deactivate_module',
			'permission_callback' => __CLASS__ . '::manage_modules_permission_check',
		) );

		// Update a module
		register_rest_route( 'jetpack/v4', '/module/(?P<slug>[a-z\-]+)/update', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::update_module',
			'permission_callback' => __CLASS__ . '::configure_modules_permission_check',
			'args' => self::get_module_updating_parameters(),
		) );

		// Activate many modules
		register_rest_route( 'jetpack/v4', '/modules/activate', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::activate_modules',
			'permission_callback' => __CLASS__ . '::manage_modules_permission_check',
			'args' => array(
				'modules' => array(
					'default'           => '',
					'type'              => 'array',
					'required'          => true,
					'validate_callback' => __CLASS__ . '::validate_module_list',
				),
			),
		) );

		// Reset all Jetpack options
		register_rest_route( 'jetpack/v4', '/reset/(?P<options>[a-z\-]+)', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::reset_jetpack_options',
			'permission_callback' => __CLASS__ . '::manage_modules_permission_check',
		) );

		// Return miscellaneous settings
		register_rest_route( 'jetpack/v4', '/settings', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::get_settings',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// Update miscellaneous setting
		register_rest_route( 'jetpack/v4', '/setting/update', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::update_setting',
			'permission_callback' => __CLASS__ . '::update_settings',
		) );

		// Jumpstart
		register_rest_route( 'jetpack/v4', '/jumpstart/activate', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::jumpstart_activate',
			'permission_callback' => __CLASS__ . '::manage_modules_permission_check',
		) );

		register_rest_route( 'jetpack/v4', '/jumpstart/deactivate', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::jumpstart_deactivate',
			'permission_callback' => __CLASS__ . '::manage_modules_permission_check',
		) );

		// Protect: get blocked count
		register_rest_route( 'jetpack/v4', '/module/protect/count/get', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::protect_get_blocked_count',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// Akismet: get spam count
		register_rest_route( 'jetpack/v4', '/akismet/stats/get', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::akismet_get_stats_data',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// Monitor: get last downtime
		register_rest_route( 'jetpack/v4', '/module/monitor/downtime/last', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::monitor_get_last_downtime',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// Updates: get number of plugin updates available
		register_rest_route( 'jetpack/v4', '/updates/plugins', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::get_plugin_update_count',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// Verification: get services that this site is verified with
		register_rest_route( 'jetpack/v4', '/module/verification-tools/services', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::get_verified_services',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// VaultPress: get date last backup or status and actions for user to take
		register_rest_route( 'jetpack/v4', '/module/vaultpress/data', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::vaultpress_get_site_data',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );

		// Dismiss Jetpack Notices
		register_rest_route( 'jetpack/v4', '/dismiss-jetpack-notice/(?P<notice>[a-z\-_]+)', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::dismiss_jetpack_notice',
			'permission_callback' => __CLASS__ . '::view_admin_page_permission_check',
		) );
	}

	/**
	 * Handles dismissing of Jetpack Notices
	 *
	 * @since 4.1.0
	 *
	 * @return array|wp-error
	 */
	public static function dismiss_jetpack_notice( $data ) {
		$notice = $data['notice'];
		if ( isset( $notice ) && ! empty( $notice ) ) {
			switch( $notice ) {
				case 'feedback_dash_request':
				case 'welcome':
					$notices = get_option( 'jetpack_dismissed_notices', array() );
					$notices[ $notice ] = true;
					update_option( 'jetpack_dismissed_notices', $notices );
					return rest_ensure_response( get_option( 'jetpack_dismissed_notices', array() ) );

				default:
					return new WP_Error( 'invalid_param', esc_html__( 'Invalid parameter "notice".', 'jetpack' ), array( 'status' => 404 ) );
			}
		}

		return new WP_Error( 'required_param', esc_html__( 'Missing parameter "notice".', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * Verify that the user can disconnect the site.
	 *
	 * @since 4.1.0
	 *
	 * @return bool|WP_Error True if user is able to disconnect the site.
	 */
	public static function disconnect_site_permission_callback() {
		if ( current_user_can( 'jetpack_disconnect' ) ) {
			return true;
		}

		return new WP_Error( 'invalid_user_permission_jetpack_disconnect', self::$user_permissions_error_msg, array( 'status' => self::rest_authorization_required_code() ) );

	}

	/**
	 * Verify that the user can get a connect/link URL
	 *
	 * @since 4.1.0
	 *
	 * @return bool|WP_Error True if user is able to disconnect the site.
	 */
	public static function connect_url_permission_callback() {
		if ( current_user_can( 'jetpack_connect_user' ) ) {
			return true;
		}

		return new WP_Error( 'invalid_user_permission_jetpack_disconnect', self::$user_permissions_error_msg, array( 'status' => self::rest_authorization_required_code() ) );

	}

	/**
	 * Verify that a user can use the link endpoint.
	 *
	 * @since 4.1.0
	 *
	 * @return bool|WP_Error True if user is able to link to WordPress.com
	 */
	public static function link_user_permission_callback() {
		if ( current_user_can( 'jetpack_connect_user' ) ) {
			return true;
		}

		return new WP_Error( 'invalid_user_permission_link_user', self::$user_permissions_error_msg, array( 'status' => self::rest_authorization_required_code() ) );
	}

	/**
	 * Verify that a user can get the data about the current user.
	 * Only those who can connect.
	 *
	 * @since 4.1.0
	 *
	 * @uses Jetpack::is_user_connected();
	 *
	 * @return bool|WP_Error True if user is able to unlink.
	 */
	public static function get_user_connection_data_permission_callback() {
		if ( current_user_can( 'jetpack_connect_user' ) ) {
			return true;
		}

		return new WP_Error( 'invalid_user_permission_unlink_user', self::$user_permissions_error_msg, array( 'status' => self::rest_authorization_required_code() ) );
	}

	/**
	 * Verify that a user can use the unlink endpoint.
	 * Either needs to be an admin of the site, or for them to be currently linked.
	 *
	 * @since 4.1.0
	 *
	 * @uses Jetpack::is_user_connected();
	 *
	 * @return bool|WP_Error True if user is able to unlink.
	 */
	public static function unlink_user_permission_callback() {
		if ( current_user_can( 'jetpack_connect' ) || Jetpack::is_user_connected( get_current_user_id() ) ) {
			return true;
		}

		return new WP_Error( 'invalid_user_permission_unlink_user', self::$user_permissions_error_msg, array( 'status' => self::rest_authorization_required_code() ) );
	}

	/**
	 * Verify that user can manage Jetpack modules.
	 *
	 * @since 4.1.0
	 *
	 * @return bool Whether user has the capability 'jetpack_manage_modules'.
	 */
	public static function manage_modules_permission_check() {
		if ( current_user_can( 'jetpack_manage_modules' ) ) {
			return true;
		}

		return new WP_Error( 'invalid_user_permission_manage_modules', self::$user_permissions_error_msg, array( 'status' => self::rest_authorization_required_code() ) );
	}

	/**
	 * Verify that user can update Jetpack modules.
	 *
	 * @since 4.1.0
	 *
	 * @return bool Whether user has the capability 'jetpack_configure_modules'.
	 */
	public static function configure_modules_permission_check() {
		if ( current_user_can( 'jetpack_configure_modules' ) ) {
			return true;
		}

		return new WP_Error( 'invalid_user_permission_configure_modules', self::$user_permissions_error_msg, array( 'status' => self::rest_authorization_required_code() ) );
	}

	/**
	 * Verify that user can view Jetpack admin page.
	 *
	 * @since 4.1.0
	 *
	 * @return bool Whether user has the capability 'jetpack_admin_page'.
	 */
	public static function view_admin_page_permission_check() {
		if ( current_user_can( 'jetpack_admin_page' ) ) {
			return true;
		}

		return new WP_Error( 'invalid_user_permission_view_admin', self::$user_permissions_error_msg, array( 'status' => self::rest_authorization_required_code() ) );
	}

	/**
	 * Verify that user can update Jetpack options.
	 *
	 * @since 4.1.0
	 *
	 * @return bool Whether user has the capability 'jetpack_admin_page'.
	 */
	public static function update_settings() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error( 'invalid_user_permission_manage_settings', self::$user_permissions_error_msg, array( 'status' => self::rest_authorization_required_code() ) );
	}

	/**
	 * Contextual HTTP error code for authorization failure.
	 *
	 * Taken from rest_authorization_required_code() in WP-API plugin until is added to core.
	 * @see https://github.com/WP-API/WP-API/commit/7ba0ae6fe4f605d5ffe4ee85b1cd5f9fb46900a6
	 *
	 * @since 4.1.0
	 *
	 * @return int
	 */
	public static function rest_authorization_required_code() {
		return is_user_logged_in() ? 403 : 401;
	}

	/**
	 * Get connection status for this Jetpack site.
	 *
	 * @since 4.1.0
	 *
	 * @return bool True if site is connected
	 */
	public static function jetpack_connection_status() {
		return rest_ensure_response( array(
				'isActive'  => Jetpack::is_active(),
				'isStaging' => Jetpack::is_staging_site(),
				'devMode'   => array(
					'isActive' => Jetpack::is_development_mode(),
					'constant' => defined( 'JETPACK_DEV_DEBUG' ) && JETPACK_DEV_DEBUG,
					'url'      => site_url() && false === strpos( site_url(), '.' ),
					'filter'   => apply_filters( 'jetpack_development_mode', false ),
				),
			)
		);
	}

	public static function recheck_ssl() {
		$result = Jetpack::permit_ssl( true );
		return array(
			'enabled' => $result,
			'message' => get_transient( 'jetpack_https_test_message' )
		);
	}

	/**
	 * Disconnects Jetpack from the WordPress.com Servers
	 *
	 * @uses Jetpack::disconnect();
	 * @since 4.1.0
	 * @return bool|WP_Error True if Jetpack successfully disconnected.
	 */
	public static function disconnect_site() {
		if ( Jetpack::is_active() ) {
			Jetpack::disconnect();
			return rest_ensure_response( array( 'code' => 'success' ) );
		}

		return new WP_Error( 'disconnect_failed', esc_html__( 'Was not able to disconnect the site.  Please try again.', 'jetpack' ), array( 'status' => 400 ) );
	}

	/**
	 * Gets a new connect URL with fresh nonce
	 *
	 * @uses Jetpack::disconnect();
	 * @since 4.1.0
	 * @return bool|WP_Error True if Jetpack successfully disconnected.
	 */
	public static function build_connect_url() {
		if ( require_once( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			$url = Jetpack::init()->build_connect_url( true, false, false );
			return rest_ensure_response( $url );
		}

		return new WP_Error( 'build_connect_url_failed', esc_html__( 'Unable to build the connect URL.  Please reload the page and try again.', 'jetpack' ), array( 'status' => 400 ) );
	}

	/**
	 * Get miscellaneous settings for this Jetpack installation, like Holiday Snow.
	 *
	 * @since 4.1.0
	 *
	 * @return object $response {
	 *     Array of miscellaneous settings.
	 *
	 *     @type bool $holiday-snow Did Jack steal Christmas?
	 * }
	 */
	public static function get_settings() {
		$response = array(
			jetpack_holiday_snow_option_name() => get_option( jetpack_holiday_snow_option_name() ) == 'letitsnow',
		);
		return rest_ensure_response( $response );
	}

	/**
	 * Get miscellaneous user data related to the connection. Similar data available in old "My Jetpack".
	 * Information about the master/primary user.
	 * Information about the current user.
	 *
	 * @since 4.1.0
	 *
	 * @return object
	 */
	public static function get_user_connection_data() {
		require_once( JETPACK__PLUGIN_DIR . '_inc/lib/admin-pages/class.jetpack-react-page.php' );

		$response = array(
			'othersLinked' => jetpack_get_other_linked_users(),
			'currentUser'  => jetpack_current_user_data(),
		);
		return rest_ensure_response( $response );
	}



	/**
	 * Update a single miscellaneous setting for this Jetpack installation, like Holiday Snow.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data
	 *
	 * @return object Jetpack miscellaneous settings.
	 */
	public static function update_setting( $data ) {
		// Get parameters to update the module.
		$param = $data->get_json_params();

		// Exit if no parameters were passed.
		if ( ! is_array( $param ) ) {
			return new WP_Error( 'missing_setting', esc_html__( 'Missing setting.', 'jetpack' ), array( 'status' => 404 ) );
		}

		// Get option name and value.
		$option = key( $param );
		$value  = current( $param );

		// Log success or not
		$updated = false;

		switch ( $option ) {
			case jetpack_holiday_snow_option_name():
				$updated = update_option( $option, ( true == (bool) $value ) ? 'letitsnow' : '' );
				break;
		}

		if ( $updated ) {
			return rest_ensure_response( array(
				'code' 	  => 'success',
				'message' => esc_html__( 'Setting updated.', 'jetpack' ),
				'value'   => $value,
			) );
		}

		return new WP_Error( 'setting_not_updated', esc_html__( 'The setting was not updated.', 'jetpack' ), array( 'status' => 400 ) );
	}

	/**
	 * Unlinks a user from the WordPress.com Servers.
	 * Default $data['id'] will default to current_user_id if no value is given.
	 *
	 * Example: '/unlink?id=1234'
	 *
	 * @since 4.1.0
	 * @uses  Jetpack::unlink_user
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 *
	 *     @type int $id ID of user to unlink.
	 * }
	 *
	 * @return bool|WP_Error True if user successfully unlinked.
	 */
	public static function unlink_user( $data ) {
		if ( isset( $data['id'] ) && Jetpack::unlink_user( $data['id'] ) ) {
			return rest_ensure_response(
				array(
					'code' => 'success'
				)
			);
		}

		return new WP_Error( 'unlink_user_failed', esc_html__( 'Was not able to unlink the user.  Please try again.', 'jetpack' ), array( 'status' => 400 ) );
	}

	/**
	 * Get site data, including for example, the site's current plan.
	 *
	 * @since 4.1.0
	 *
	 * @return array Array of Jetpack modules.
	 */
	public static function get_site_data() {

		if ( $site_id = Jetpack_Options::get_option( 'id' ) ) {
			$response = Jetpack_Client::wpcom_json_api_request_as_blog( sprintf( '/sites/%d', $site_id ), '1.1' );

			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return new WP_Error( 'site_data_fetch_failed', esc_html__( 'Failed fetching site data. Try again later.', 'jetpack' ), array( 'status' => 400 ) );
			}

			return rest_ensure_response( array(
					'code' => 'success',
					'message' => esc_html__( 'Site data correctly received.', 'jetpack' ),
					'data' => wp_remote_retrieve_body( $response ),
				)
			);
		}

		return new WP_Error( 'site_id_missing', esc_html__( 'The ID of this site does not exist.', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * Is Akismet registered and active?
	 *
	 * @since 4.1.0
	 *
	 * @return bool|WP_Error True if Akismet is active and registered. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function akismet_is_active_and_registered() {
		if ( ! file_exists( WP_PLUGIN_DIR . '/akismet/class.akismet.php' ) ) {
			return new WP_Error( 'not_installed', esc_html__( 'Please install Akismet.', 'jetpack' ), array( 'status' => 400 ) );
		}

		if ( ! class_exists( 'Akismet' ) ) {
			return new WP_Error( 'not_active', esc_html__( 'Please activate Akismet.', 'jetpack' ), array( 'status' => 400 ) );
		}

		// What about if Akismet is put in a sub-directory or maybe in mu-plugins?
		require_once WP_PLUGIN_DIR . '/akismet/class.akismet.php';
		require_once WP_PLUGIN_DIR . '/akismet/class.akismet-admin.php';
		$akismet_key = Akismet::verify_key( Akismet::get_api_key() );

		if ( ! $akismet_key || 'invalid' === $akismet_key || 'failed' === $akismet_key ) {
			return new WP_Error( 'invalid_key', esc_html__( 'Invalid Akismet key. Please contact support.', 'jetpack' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Get a list of all Jetpack modules and their information.
	 *
	 * @since 4.1.0
	 *
	 * @return array Array of Jetpack modules.
	 */
	public static function get_modules() {
		require_once( JETPACK__PLUGIN_DIR . 'class.jetpack-admin.php' );

		$modules = Jetpack_Admin::init()->get_modules();
		foreach ( $modules as $slug => $properties ) {
			$modules[ $slug ]['options'] = self::prepare_options_for_response( $slug );
		}

		return $modules;
	}

	/**
	 * Get information about a specific and valid Jetpack module.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 *
	 *     @type string $slug Module slug.
	 * }
	 *
	 * @return mixed|void|WP_Error
	 */
	public static function get_module( $data ) {
		if ( Jetpack::is_module( $data['slug'] ) ) {

			$module = Jetpack::get_module( $data['slug'] );

			$module['options'] = self::prepare_options_for_response( $data['slug'] );

			return $module;
		}

		return new WP_Error( 'not_found', esc_html__( 'The requested Jetpack module was not found.', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * If it's a valid Jetpack module, activate it.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 *
	 *     @type string $slug Module slug.
	 * }
	 *
	 * @return bool|WP_Error True if module was activated. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function activate_module( $data ) {
		if ( Jetpack::is_module( $data['slug'] ) ) {
			if ( Jetpack::activate_module( $data['slug'], false, false ) ) {
				return rest_ensure_response( array(
					'code' 	  => 'success',
					'message' => esc_html__( 'The requested Jetpack module was activated.', 'jetpack' ),
				) );
			}
			return new WP_Error( 'activation_failed', esc_html__( 'The requested Jetpack module could not be activated.', 'jetpack' ), array( 'status' => 424 ) );
		}

		return new WP_Error( 'not_found', esc_html__( 'The requested Jetpack module was not found.', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * Activate a list of valid Jetpack modules.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 *
	 *     @type string $slug Module slug.
	 * }
	 *
	 * @return bool|WP_Error True if modules were activated. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function activate_modules( $data ) {
		$params = $data->get_json_params();
		if ( isset( $params['modules'] ) && is_array( $params['modules'] ) ) {
			$activated = array();
			$failed = array();

			foreach ( $params['modules'] as $module ) {
				if ( Jetpack::activate_module( $module, false, false ) ) {
					$activated[] = $module;
				} else {
					$failed[] = $module;
				}
			}

			if ( empty( $failed ) ) {
				return rest_ensure_response( array(
					'code' 	  => 'success',
					'message' => esc_html__( 'All modules activated.', 'jetpack' ),
				) );
			} else {
				$error = '';

				$activated_count = count( $activated );
				if ( $activated_count > 0 ) {
					$activated_last = array_pop( $activated );
					$activated_text = $activated_count > 1 ? sprintf(
						/* Translators: first variable is a list followed by a last item. Example: dog, cat and bird. */
						__( '%s and %s', 'jetpack' ),
						join( ', ', $activated ), $activated_last ) : $activated_last;

					$error = sprintf(
						/* Translators: the plural variable is a list followed by a last item. Example: dog, cat and bird. */
						_n( 'The module %s was activated.', 'The modules %s were activated.', $activated_count, 'jetpack' ),
						$activated_text ) . ' ';
				}

				$failed_count = count( $failed );
				if ( count( $failed ) > 0 ) {
					$failed_last = array_pop( $failed );
					$failed_text = $failed_count > 1 ? sprintf(
						/* Translators: first variable is a list followed by a last item. Example: dog, cat and bird. */
						__( '%s and %s', 'jetpack' ),
						join( ', ', $failed ), $failed_last ) : $failed_last;

					$error = sprintf(
						/* Translators: the plural variable is a list followed by a last item. Example: dog, cat and bird. */
						_n( 'The module %s failed to be activated.', 'The modules %s failed to be activated.', $failed_count, 'jetpack' ),
						$failed_text ) . ' ';
				}
			}
			return new WP_Error( 'activation_failed', esc_html( $error ), array( 'status' => 424 ) );
		}

		return new WP_Error( 'not_found', esc_html__( 'The requested Jetpack module was not found.', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * Reset Jetpack options
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 *
	 *     @type string $options Available options to reset are options|modules
	 * }
	 *
	 * @return bool|WP_Error True if options were reset. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function reset_jetpack_options( $data ) {
		if ( isset( $data['options'] ) ) {
			$data = $data['options'];

			switch( $data ) {
				case ( 'options' ) :
					$options_to_reset = Jetpack::get_jetpack_options_for_reset();

					// Reset the Jetpack options
					foreach ( $options_to_reset['jp_options'] as $option_to_reset ) {
						Jetpack_Options::delete_option( $option_to_reset );
					}

					foreach ( $options_to_reset['wp_options'] as $option_to_reset ) {
						delete_option( $option_to_reset );
					}

					// Reset to default modules
					$default_modules = Jetpack::get_default_modules();
					Jetpack_Options::update_option( 'active_modules', $default_modules );

					// Jumpstart option is special
					Jetpack_Options::update_option( 'jumpstart', 'new_connection' );
					return rest_ensure_response( array(
						'code' 	  => 'success',
						'message' => esc_html__( 'Jetpack options reset.', 'jetpack' ),
					) );
					break;

				case 'modules':
					$default_modules = Jetpack::get_default_modules();
					Jetpack_Options::update_option( 'active_modules', $default_modules );

					return rest_ensure_response( array(
						'code' 	  => 'success',
						'message' => esc_html__( 'Modules reset to default.', 'jetpack' ),
					) );
					break;

				default:
					return new WP_Error( 'invalid_param', esc_html__( 'Invalid Parameter', 'jetpack' ), array( 'status' => 404 ) );
			}
		}

		return new WP_Error( 'required_param', esc_html__( 'Missing parameter "type".', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * Activates a series of valid Jetpack modules and initializes some options.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 * }
	 *
	 * @return bool|WP_Error True if Jumpstart succeeded. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function jumpstart_activate( $data ) {
		$modules = Jetpack::get_available_modules();
		$activate_modules = array();
		foreach ( $modules as $module ) {
			$module_info = Jetpack::get_module( $module );
			if ( isset( $module_info['feature'] ) && is_array( $module_info['feature'] ) && in_array( 'Jumpstart', $module_info['feature'] ) ) {
				$activate_modules[] = $module;
			}
		}

		// Collect success/error messages like modules that are properly activated.
		$result = array(
			'activated_modules' => array(),
			'failed_modules'    => array(),
		);

		// Update the jumpstart option
		if ( 'new_connection' === Jetpack_Options::get_option( 'jumpstart' ) ) {
			$result['jumpstart_activated'] = Jetpack_Options::update_option( 'jumpstart', 'jumpstart_activated' );
		}

		// Check for possible conflicting plugins
		$module_slugs_filtered = Jetpack::init()->filter_default_modules( $activate_modules );

		foreach ( $module_slugs_filtered as $module_slug ) {
			Jetpack::log( 'activate', $module_slug );
			if ( Jetpack::activate_module( $module_slug, false, false ) ) {
				$result['activated_modules'][] = $module_slug;
			} else {
				$result['failed_modules'][] = $module_slug;
			}
			Jetpack::state( 'message', 'no_message' );
		}

		// Set the default sharing buttons and set to display on posts if none have been set.
		$sharing_services = get_option( 'sharing-services' );
		$sharing_options  = get_option( 'sharing-options' );
		if ( empty( $sharing_services['visible'] ) ) {
			// Default buttons to set
			$visible = array(
				'twitter',
				'facebook',
				'google-plus-1',
			);
			$hidden = array();

			// Set some sharing settings
			$sharing = new Sharing_Service();
			$sharing_options['global'] = array(
				'button_style'  => 'icon',
				'sharing_label' => $sharing->default_sharing_label,
				'open_links'    => 'same',
				'show'          => array( 'post' ),
				'custom'        => isset( $sharing_options['global']['custom'] ) ? $sharing_options['global']['custom'] : array()
			);

			$result['sharing_options']  = update_option( 'sharing-options', $sharing_options );
			$result['sharing_services'] = update_option( 'sharing-services', array( 'visible' => $visible, 'hidden' => $hidden ) );
		}

		// If all Jumpstart modules were activated
		if ( empty( $result['failed_modules'] ) ) {
			return rest_ensure_response( array(
				'code' 	  => 'success',
				'message' => esc_html__( 'Jumpstart done.', 'jetpack' ),
				'data'    => $result,
			) );
		}

		return new WP_Error( 'jumpstart_failed', esc_html( sprintf( _n( 'Jumpstart failed activating this module: %s.', 'Jumpstart failed activating these modules: %s.', count( $result['failed_modules'] ), 'jetpack' ), join( ', ', $result['failed_modules'] ) ) ), array( 'status' => 400 ) );
	}

	/**
	 * Dismisses Jumpstart so user is not prompted to go through it again.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 * }
	 *
	 * @return bool|WP_Error True if Jumpstart was disabled or was nothing to dismiss. Otherwise, a WP_Error instance with a message.
	 */
	public static function jumpstart_deactivate( $data ) {

		// If dismissed, flag the jumpstart option as such.
		if ( 'new_connection' === Jetpack_Options::get_option( 'jumpstart' ) ) {
			if ( Jetpack_Options::update_option( 'jumpstart', 'jumpstart_dismissed' ) ) {
				return rest_ensure_response( array(
					'code' 	  => 'success',
					'message' => esc_html__( 'Jumpstart dismissed.', 'jetpack' ),
				) );
			} else {
				return new WP_Error( 'jumpstart_failed_dismiss', esc_html__( 'Jumpstart could not be dismissed.', 'jetpack' ), array( 'status' => 400 ) );
			}
		}

		// If this was not a new connection and there was nothing to dismiss, don't fail.
		return rest_ensure_response( array(
			'code' 	  => 'success',
			'message' => esc_html__( 'Nothing to dismiss. This was not a new connection.', 'jetpack' ),
		) );
	}

	/**
	 * If it's a valid Jetpack module, deactivate it.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 *
	 *     @type string $slug Module slug.
	 * }
	 *
	 * @return bool|WP_Error True if module was activated. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function deactivate_module( $data ) {
		if ( Jetpack::is_module( $data['slug'] ) ) {
			if ( ! Jetpack::is_module_active( $data['slug'] ) ) {
				return new WP_Error( 'already_inactive', esc_html__( 'The requested Jetpack module was already inactive.', 'jetpack' ), array( 'status' => 409 ) );
			}
			if ( Jetpack::deactivate_module( $data['slug'] ) ) {
				return rest_ensure_response( array(
					'code' 	  => 'success',
					'message' => esc_html__( 'The requested Jetpack module was deactivated.', 'jetpack' ),
				) );
			}
			return new WP_Error( 'deactivation_failed', esc_html__( 'The requested Jetpack module could not be deactivated.', 'jetpack' ), array( 'status' => 400 ) );
		}

		return new WP_Error( 'not_found', esc_html__( 'The requested Jetpack module was not found.', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * If it's a valid Jetpack module and configuration parameters have been sent, update it.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 *
	 *     @type string $slug Module slug.
	 * }
	 *
	 * @return bool|WP_Error True if module was updated. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function update_module( $data ) {
		if ( ! Jetpack::is_module( $data['slug'] ) ) {
			return new WP_Error( 'not_found', esc_html__( 'The requested Jetpack module was not found.', 'jetpack' ), array( 'status' => 404 ) );
		}

		if ( ! Jetpack::is_module_active( $data['slug'] ) ) {
			return new WP_Error( 'inactive', esc_html__( 'The requested Jetpack module is inactive.', 'jetpack' ), array( 'status' => 409 ) );
		}

		// Get parameters to update the module.
		$param = $data->get_json_params();

		// Exit if no parameters were passed.
		if ( ! is_array( $param ) ) {
			return new WP_Error( 'missing_option', esc_html__( 'Missing option.', 'jetpack' ), array( 'status' => 404 ) );
		}

		// Get option name and value.
		$option = key( $param );
		$value  = current( $param );

		// Get available module options.
		$options = self::get_module_available_options();

		// If option is invalid, don't go any further.
		if ( ! in_array( $option, array_keys( $options ) ) ) {
			return new WP_Error( 'invalid_param', esc_html(	sprintf( __( 'The option %s is invalid for this module.', 'jetpack' ), $option ) ), array( 'status' => 404 ) );
		}

		// Used if response is successful. The message can be overwritten and additional data can be added here.
		$response = array(
			'code' 	  => 'success',
			'message' => esc_html__( 'The requested Jetpack module was updated.', 'jetpack' ),
		);

		// Used if there was an error. Can be overwritten with specific error messages.
		/* Translators: the variable is a module option name. */
		$error = sprintf( __( 'The option %s was not updated.', 'jetpack' ), $option );

		// Set to true if the option update was successful.
		$updated = false;

		// Properly cast value based on its type defined in endpoint accepted args.
		$value = self::cast_value( $value, $options[ $option ] );

		switch ( $option ) {
			case 'monitor_receive_notifications':
				$monitor = new Jetpack_Monitor();

				// If we got true as response, consider it done.
				$updated = true === $monitor->update_option_receive_jetpack_monitor_notification( $value );
				break;

			case 'post_by_email_address':
				if ( 'create' == $value ) {
					$result = self::_process_post_by_email(
						'jetpack.createPostByEmailAddress',
						esc_html__( 'Unable to create the Post by Email address. Please try again later.', 'jetpack' )
					);
				} elseif ( 'regenerate' == $value ) {
					$result = self::_process_post_by_email(
						'jetpack.regeneratePostByEmailAddress',
						esc_html__( 'Unable to regenerate the Post by Email address. Please try again later.', 'jetpack' )
					);
				} elseif ( 'delete' == $value ) {
					$result = self::_process_post_by_email(
						'jetpack.deletePostByEmailAddress',
						esc_html__( 'Unable to delete the Post by Email address. Please try again later.', 'jetpack' )
					);
				} else {
					$result = false;
				}

				// If we got an email address (create or regenerate) or 1 (delete), consider it done.
				if ( preg_match( '/[a-z0-9]+@post.wordpress.com/', $result ) ) {
					$response[ $option ] = $result;
					$updated = true;
				} elseif ( 1 == $result ) {
					$updated = true;
				} elseif ( is_array( $result ) && isset( $result['message'] ) ) {
					$error = $result['message'];
				}
				break;

			case 'jetpack_protect_key':
				$protect = Jetpack_Protect_Module::instance();
				if ( 'create' == $value ) {
					$result = $protect->get_protect_key();
				} else {
					$result = false;
				}

				// If we got one of Protect keys, consider it done.
				if ( preg_match( '/[a-z0-9]{40,}/i', $result ) ) {
					$response[ $option ] = $result;
					$updated = true;
				}
				break;

			case 'jetpack_protect_global_whitelist':
				$updated = jetpack_protect_save_whitelist( explode( PHP_EOL, str_replace( ' ', '', $value ) ) );
				if ( is_wp_error( $updated ) ) {
					$error = $updated->get_error_message();
				}
				break;

			case 'show_headline':
			case 'show_thumbnails':
				$grouped_options = $grouped_options_current = Jetpack_Options::get_option( 'relatedposts' );
				$grouped_options[ $option ] = $value;

				// If option value was the same, consider it done.
				$updated = $grouped_options_current != $grouped_options ? Jetpack_Options::update_option( 'relatedposts', $grouped_options ) : true;
				break;

			case 'google':
			case 'bing':
			case 'pinterest':
				$grouped_options = $grouped_options_current = get_option( 'verification_services_codes' );
				$grouped_options[ $option ] = $value;

				// If option value was the same, consider it done.
				$updated = $grouped_options_current != $grouped_options ? update_option( 'verification_services_codes', $grouped_options ) : true;
				break;

			case 'sharing_services':
				$sharer = new Sharing_Service();

				// If option value was the same, consider it done.
				$updated = $value != $sharer->get_blog_services() ? $sharer->set_blog_services( $value['visible'], $value['hidden'] ) : true;
				break;

			case 'button_style':
			case 'sharing_label':
			case 'show':
				$sharer = new Sharing_Service();
				$grouped_options = $sharer->get_global_options();
				$grouped_options[ $option ] = $value;
				$updated = $sharer->set_global_options( $grouped_options );
				break;

			case 'custom':
				$sharer = new Sharing_Service();
				$updated = $sharer->new_service( stripslashes( $value['sharing_name'] ), stripslashes( $value['sharing_url'] ), stripslashes( $value['sharing_icon'] ) );

				// Return new custom service
				$response[ $option ] = $updated;
				break;

			case 'sharing_delete_service':
				$sharer = new Sharing_Service();
				$updated = $sharer->delete_service( $value );
				break;

			case 'jetpack-twitter-cards-site-tag':
				$value = trim( ltrim( strip_tags( $value ), '@' ) );
				$updated = get_option( $option ) !== $value ? update_option( $option, $value ) : true;
				break;

			case 'onpublish':
			case 'onupdate':
			case 'Bias Language':
			case 'Cliches':
			case 'Complex Expression':
			case 'Diacritical Marks':
			case 'Double Negative':
			case 'Hidden Verbs':
			case 'Jargon Language':
			case 'Passive voice':
			case 'Phrases to Avoid':
			case 'Redundant Expression':
			case 'guess_lang':
				if ( in_array( $option, array( 'onpublish', 'onupdate' ) ) ) {
					$atd_option = 'AtD_check_when';
				} elseif ( 'guess_lang' == $option ) {
					$atd_option = 'AtD_guess_lang';
					$option = 'true';
				} else {
					$atd_option = 'AtD_options';
				}
				$user_id = get_current_user_id();
				$grouped_options_current = AtD_get_options( $user_id, $atd_option );
				unset( $grouped_options_current['name'] );
				$grouped_options = $grouped_options_current;
				if ( $value && ! isset( $grouped_options [ $option ] ) ) {
					$grouped_options [ $option ] = $value;
				} elseif ( ! $value && isset( $grouped_options [ $option ] ) ) {
					unset( $grouped_options [ $option ] );
				}
				// If option value was the same, consider it done, otherwise try to update it.
				$options_to_save = implode( ',', array_keys( $grouped_options ) );
				$updated = $grouped_options != $grouped_options_current ? AtD_update_setting( $user_id, $atd_option, $options_to_save ) : true;
				break;

			case 'ignored_phrases':
			case 'unignore_phrase':
				$user_id = get_current_user_id();
				$atd_option = 'AtD_ignored_phrases';
				$grouped_options = $grouped_options_current = explode( ',', AtD_get_setting( $user_id, $atd_option ) );
				if ( 'ignored_phrases' == $option ) {
					$grouped_options[] = $value;
				} else {
					$index = array_search( $value, $grouped_options );
					if ( false !== $index ) {
						unset( $grouped_options[ $index ] );
						$grouped_options = array_values( $grouped_options );
					}
				}
				$ignored_phrases = implode( ',', array_filter( array_map( 'strip_tags', $grouped_options ) ) );
				$updated = $grouped_options != $grouped_options_current ? AtD_update_setting( $user_id, $atd_option, $ignored_phrases ) : true;
				break;

			default:
				// If option value was the same, consider it done.
				$updated = get_option( $option ) != $value ? update_option( $option, $value ) : true;
				break;
		}

		// The option was not updated.
		if ( ! $updated ) {
			return new WP_Error( 'module_option_not_updated', esc_html( $error ), array( 'status' => 400 ) );
		}

		// The option was updated.
		return rest_ensure_response( $response );
	}

	/**
	 * Calls WPCOM through authenticated request to create, regenerate or delete the Post by Email address.
	 * @todo: When all settings are updated to use endpoints, move this to the Post by Email module and replace __process_ajax_proxy_request.
	 *
	 * @since 4.1.0
	 *
	 * @param string $endpoint Process to call on WPCOM to create, regenerate or delete the Post by Email address.
	 * @param string $error	   Error message to return.
	 *
	 * @return array
	 */
	private static function _process_post_by_email( $endpoint, $error ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return array( 'message' => $error );
		}
		Jetpack::load_xml_rpc_client();
		$xml = new Jetpack_IXR_Client( array(
			'user_id' => get_current_user_id(),
		) );
		$xml->query( $endpoint );

		if ( $xml->isError() ) {
			return array( 'message' => $error );
		}

		$response = $xml->getResponse();
		if ( empty( $response ) ) {
			return array( 'message' => $error );
		}

		// Used only in Jetpack_Core_Json_Api_Endpoints::get_remote_value.
		update_option( 'post_by_email_address', $response );

		return $response;
	}

	/**
	 * Get the query parameters for module updating.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	public static function get_module_updating_parameters() {
		$parameters = array(
			'context'     => array(
				'default' => 'edit',
			),
		);

		return array_merge( $parameters, self::get_module_available_options() );
	}

	/**
	 * Returns a list of module options that can be updated.
	 *
	 * @since 4.1.0
	 *
	 * @param string $module Module slug. If empty, it's assumed we're updating a module and we'll try to get its slug.
	 * @param bool $cache Whether to cache the options or return always fresh.
	 *
	 * @return array
	 */
	public static function get_module_available_options( $module = '', $cache = true ) {
		if ( $cache ) {
			static $options;
		} else {
			$options = null;
		}

		if ( isset( $options ) ) {
			return $options;
		}

		if ( empty( $module ) ) {
			$module = self::get_module_requested( '/module/(?P<slug>[a-z\-]+)/update' );
			if ( empty( $module ) ) {
				return array();
			}
		}

		switch ( $module ) {

			// Carousel
			case 'carousel':
				$options = array(
					'carousel_background_color' => array(
						'description'        => esc_html__( 'Background color.', 'jetpack' ),
						'type'               => 'string',
						'default'            => 'black',
						'enum'				 => array(
							'black' => esc_html__( 'Black', 'jetpack' ),
							'white' => esc_html__( 'White', 'jetpack' ),
						),
						'validate_callback'  => __CLASS__ . '::validate_list_item',
					),
					'carousel_display_exif' => array(
						'description'        => wp_kses( sprintf( __( 'Show photo metadata (<a href="http://en.wikipedia.org/wiki/Exchangeable_image_file_format" target="_blank">Exif</a>) in carousel, when available.', 'jetpack' ) ), array( 'a' => array( 'href' => true, 'target' => true ) )  ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Comments
			case 'comments':
				$options = array(
					'highlander_comment_form_prompt' => array(
						'description'        => esc_html__( 'Greeting Text', 'jetpack' ),
						'type'               => 'string',
						'default'            => esc_html__( 'Leave a Reply', 'jetpack' ),
						'sanitize_callback'  => 'sanitize_text_field',
					),
					'jetpack_comment_form_color_scheme' => array(
						'description'        => esc_html__( "Color Scheme", 'jetpack' ),
						'type'               => 'string',
						'default'            => 'light',
						'enum'				 => array(
							'light'       => esc_html__( 'Light', 'jetpack' ),
							'dark'        => esc_html__( 'Dark', 'jetpack' ),
							'transparent' => esc_html__( 'Transparent', 'jetpack' ),
						),
						'validate_callback'  => __CLASS__ . '::validate_list_item',
					),
				);
				break;

			// Custom Content Types
			case 'custom-content-types':
				$options = array(
					'jetpack_portfolio' => array(
						'description'        => esc_html__( 'Enable or disable Jetpack portfolio post type.', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'jetpack_portfolio_posts_per_page' => array(
						'description'        => esc_html__( 'Number of entries to show at most in Portfolio pages.', 'jetpack' ),
						'type'               => 'integer',
						'default'            => 10,
						'validate_callback'  => __CLASS__ . '::validate_posint',
					),
					'jetpack_testimonial' => array(
						'description'        => esc_html__( 'Enable or disable Jetpack testimonial post type.', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'jetpack_testimonial_posts_per_page' => array(
						'description'        => esc_html__( 'Number of entries to show at most in Testimonial pages.', 'jetpack' ),
						'type'               => 'integer',
						'default'            => 10,
						'validate_callback'  => __CLASS__ . '::validate_posint',
					),
				);
				break;

			// Galleries
			case 'tiled-gallery':
				$options = array(
					'tiled_galleries' => array(
						'description'        => esc_html__( 'Display all your gallery pictures in a cool mosaic.', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Gravatar Hovercards
			case 'gravatar-hovercards':
				$options = array(
					'gravatar_disable_hovercards' => array(
						'description'        => esc_html__( "View people's profiles when you mouse over their Gravatars", 'jetpack' ),
						'type'               => 'string',
						'default'            => 'enabled',
						// Not visible. This is used as the checkbox value.
						'enum'				 => array( 'enabled', 'disabled' ),
						'validate_callback'  => __CLASS__ . '::validate_list_item',
					),
				);
				break;

			// Infinite Scroll
			case 'infinite-scroll':
				$options = array(
					'infinite_scroll' => array(
						'description'        => esc_html__( 'To infinity and beyond', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 1,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'infinite_scroll_google_analytics' => array(
						'description'        => esc_html__( 'Use Google Analytics with Infinite Scroll', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Likes
			case 'likes':
				$options = array(
					'wpl_default' => array(
						'description'        => esc_html__( 'WordPress.com Likes are', 'jetpack' ),
						'type'               => 'string',
						'default'            => 'on',
						'enum'				 => array(
							'on'  => esc_html__( 'On for all posts', 'jetpack' ),
							'off' => esc_html__( 'Turned on per post', 'jetpack' ),
						),
						'validate_callback'  => __CLASS__ . '::validate_list_item',
					),
					'social_notifications_like' => array(
						'description'        => esc_html__( 'Send email notification when someone likes a posts', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 1,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Markdown
			case 'markdown':
				$options = array(
					'wpcom_publish_comments_with_markdown' => array(
						'description'        => esc_html__( 'Use Markdown for comments.', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Mobile Theme
			case 'minileven':
				$options = array(
					'wp_mobile_excerpt' => array(
						'description'        => esc_html__( 'Excerpts', 'jetpack' ),
						'type'               => 'string',
						'default'            => '0',
						'enum'				 => array(
							'1'  => esc_html__( 'Enable excerpts on front page and on archive pages', 'jetpack' ),
							'0' => esc_html__( 'Show full posts on front page and on archive pages', 'jetpack' ),
						),
						'validate_callback'  => __CLASS__ . '::validate_list_item',
					),
					'wp_mobile_featured_images' => array(
						'description'        => esc_html__( 'Featured Images', 'jetpack' ),
						'type'               => 'string',
						'default'            => '0',
						'enum'				 => array(
							'0'  => esc_html__( 'Hide all featured images', 'jetpack' ),
							'1' => esc_html__( 'Display featured images', 'jetpack' ),
						),
						'validate_callback'  => __CLASS__ . '::validate_list_item',
					),
					'wp_mobile_app_promos' => array(
						'description'        => esc_html__( 'Show a promo for the WordPress mobile apps in the footer of the mobile theme.', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Monitor
			case 'monitor':
				$options = array(
					'monitor_receive_notifications' => array(
						'description'        => esc_html__( 'Receive Monitor Email Notifications.', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Post by Email
			case 'post-by-email':
				$options = array(
					'post_by_email_address' => array(
						'description'       => esc_html__( 'Email Address', 'jetpack' ),
						'type'              => 'string',
						'default'           => '',
						'enum'              => array(
							'create'     => esc_html__( 'Create Post by Email address', 'jetpack' ),
							'regenerate' => esc_html__( 'Regenerate Post by Email address', 'jetpack' ),
							'delete'     => esc_html__( 'Delete Post by Email address', 'jetpack' ),
						),
						'validate_callback' => __CLASS__ . '::validate_list_item',
					),
				);
				break;

			// Protect
			case 'protect':
				$options = array(
					'jetpack_protect_key' => array(
						'description'        => esc_html__( 'Protect API key', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'validate_callback'  => __CLASS__ . '::validate_alphanum',
					),
					'jetpack_protect_global_whitelist' => array(
						'description'        => esc_html__( 'Protect global whitelist', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'validate_callback'  => __CLASS__ . '::validate_string',
						'sanitize_callback'  => 'esc_textarea',
					),
				);
				break;

			// Sharing
			case 'sharedaddy':
				$options = array(
					'sharing_services' => array(
						'description'        => esc_html__( 'Enabled Services and those hidden behind a button', 'jetpack' ),
						'type'               => 'array',
						'default'            => array(
							'visible' => array( 'twitter', 'facebook', 'google-plus-1' ),
							'hidden'  => array(),
						),
						'validate_callback'  => __CLASS__ . '::validate_services',
					),
					'button_style' => array(
						'description'       => esc_html__( 'Button Style', 'jetpack' ),
						'type'              => 'string',
						'default'           => 'icon',
						'enum'              => array(
							'icon-text' => esc_html__( 'Icon + text', 'jetpack' ),
							'icon'      => esc_html__( 'Icon only', 'jetpack' ),
							'text'      => esc_html__( 'Text only', 'jetpack' ),
							'official'  => esc_html__( 'Official buttons', 'jetpack' ),
						),
						'validate_callback' => __CLASS__ . '::validate_list_item',
					),
					'sharing_label' => array(
						'description'        => esc_html__( 'Sharing Label', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'validate_callback'  => __CLASS__ . '::validate_string',
						'sanitize_callback'  => 'esc_html',
					),
					'show' => array(
						'description'        => esc_html__( 'Views where buttons are shown', 'jetpack' ),
						'type'               => 'array',
						'default'            => array( 'post' ),
						'validate_callback'  => __CLASS__ . '::validate_sharing_show',
					),
					'jetpack-twitter-cards-site-tag' => array(
						'description'        => esc_html__( "The Twitter username of the owner of this site's domain.", 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'validate_callback'  => __CLASS__ . '::validate_twitter_username',
						'sanitize_callback'  => 'esc_html',
					),
					'sharedaddy_disable_resources' => array(
						'description'        => esc_html__( 'Disable CSS and JS', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'custom' => array(
						'description'        => esc_html__( 'Custom sharing services added by user.', 'jetpack' ),
						'type'               => 'array',
						'default'            => array(
							'sharing_name' => '',
							'sharing_url'  => '',
							'sharing_icon' => '',
						),
						'validate_callback'  => __CLASS__ . '::validate_custom_service',
					),
					// Not an option, but an action that can be perfomed on the list of custom services passing the service ID.
					'sharing_delete_service' => array(
						'description'        => esc_html__( 'Delete custom sharing service.', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'validate_callback'  => __CLASS__ . '::validate_custom_service_id',
					),
				);
				break;

			// SSO
			case 'sso':
				$options = array(
					'jetpack_sso_require_two_step' => array(
						'description'        => esc_html__( 'Require Two-Step Authentication', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'jetpack_sso_match_by_email' => array(
						'description'        => esc_html__( 'Match by Email', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Site Icon
			case 'site-icon':
				$options = array(
					'site_icon_id' => array(
						'description'        => esc_html__( 'Site Icon ID', 'jetpack' ),
						'type'               => 'integer',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_posint',
					),
					'site_icon_url' => array(
						'description'        => esc_html__( 'Site Icon URL', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'sanitize_callback'  => 'esc_url',
					),
				);
				break;

			// Subscriptions
			case 'subscriptions':
				$options = array(
					'stb_enabled' => array(
						'description'        => esc_html__( "Show a <em>'follow blog'</em> option in the comment form", 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 1,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'stc_enabled' => array(
						'description'        => esc_html__( "Show a <em>'follow comments'</em> option in the comment form", 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 1,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Related Posts
			case 'related-posts':
				$options = array(
					'show_headline' => array(
						'description'        => esc_html__( 'Show a "Related" header to more clearly separate the related section from posts', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 1,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'show_thumbnails' => array(
						'description'        => esc_html__( 'Use a large and visually striking layout', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
				);
				break;

			// Spelling and Grammar - After the Deadline
			case 'after-the-deadline':
				$options = array(
					'onpublish' => array(
						'description'        => esc_html__( 'Proofread when a post or page is first published.', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'onupdate' => array(
						'description'        => esc_html__( 'Proofread when a post or page is updated.', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Bias Language' => array(
						'description'        => esc_html__( 'Bias Language', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Cliches' => array(
						'description'        => esc_html__( 'Clichés', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Complex Expression' => array(
						'description'        => esc_html__( 'Complex Phrases', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Diacritical Marks' => array(
						'description'        => esc_html__( 'Diacritical Marks', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Double Negative' => array(
						'description'        => esc_html__( 'Double Negatives', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Hidden Verbs' => array(
						'description'        => esc_html__( 'Hidden Verbs', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Jargon Language' => array(
						'description'        => esc_html__( 'Jargon', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Passive voice' => array(
						'description'        => esc_html__( 'Passive Voice', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Phrases to Avoid' => array(
						'description'        => esc_html__( 'Phrases to Avoid', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'Redundant Expression' => array(
						'description'        => esc_html__( 'Redundant Phrases', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'guess_lang' => array(
						'description'        => esc_html__( 'Use automatically detected language to proofread posts and pages', 'jetpack' ),
						'type'               => 'boolean',
						'default'            => 0,
						'validate_callback'  => __CLASS__ . '::validate_boolean',
					),
					'ignored_phrases' => array(
						'description'        => esc_html__( 'Add Phrase to be ignored', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'sanitize_callback'  => 'esc_html',
					),
					'unignore_phrase' => array(
						'description'        => esc_html__( 'Remove Phrase from being ignored', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'sanitize_callback'  => 'esc_html',
					),
				);
				break;

			// Verification Tools
			case 'verification-tools':
				$options = array(
					'google' => array(
						'description'        => esc_html__( 'Google Search Console', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'validate_callback'  => __CLASS__ . '::validate_alphanum',
					),
					'bing' => array(
						'description'        => esc_html__( 'Bing Webmaster Center', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'validate_callback'  => __CLASS__ . '::validate_alphanum',
					),
					'pinterest' => array(
						'description'        => esc_html__( 'Pinterest Site Verification', 'jetpack' ),
						'type'               => 'string',
						'default'            => '',
						'validate_callback'  => __CLASS__ . '::validate_alphanum',
					),
				);
				break;
		}

		return $options;
	}

	/**
	 * Validates that the parameter is either a pure boolean or a numeric string that can be mapped to a boolean.
	 *
	 * @since 4.1.0
	 *
	 * @param string|bool $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_boolean( $value, $request, $param ) {
		if ( ! is_bool( $value ) && ! in_array( $value, array( 0, 1 ) ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be true, false, 0 or 1.', 'jetpack' ), $param ) );
		}
		return true;
	}

	/**
	 * Validates that the parameter is a positive integer.
	 *
	 * @since 4.1.0
	 *
	 * @param int $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_posint( $value = 0, $request, $param ) {
		if ( ! is_numeric( $value ) || $value <= 0 ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be a positive integer.', 'jetpack' ), $param ) );
		}
		return true;
	}

	/**
	 * Validates that the parameter belongs to a list of admitted values.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_list_item( $value = '', $request, $param ) {
		$attributes = $request->get_attributes();
		if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s not recognized', 'jetpack' ), $param ) );
		}
		$args = $attributes['args'][ $param ];
		if ( ! empty( $args['enum'] ) ) {

			// If it's an associative array, use the keys to check that the value is among those admitted.
			$enum = ( count( array_filter( array_keys( $args['enum'] ), 'is_string' ) ) > 0 ) ? array_keys( $args['enum'] ) : $args['enum'];
			if ( ! in_array( $value, $enum ) ) {
				return new WP_Error( 'invalid_param_value', sprintf( esc_html__( '%s must be one of %s', 'jetpack' ), $param, implode( ', ', $enum ) ) );
			}
		}
		return true;
	}

	/**
	 * Validates that the parameter belongs to a list of admitted values.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_module_list( $value = '', $request, $param ) {
		if ( ! is_array( $value ) ) {
			return new WP_Error( 'invalid_param_value', sprintf( esc_html__( '%s must be an array', 'jetpack' ), $param ) );
		}

		$modules = Jetpack::get_available_modules();

		if ( count( array_intersect( $value, $modules ) ) != count( $value ) ) {
			return new WP_Error( 'invalid_param_value', sprintf( esc_html__( '%s must be a list of valid modules', 'jetpack' ), $param ) );
		}

		return true;
	}

	/**
	 * Validates that the parameter is an alphanumeric or empty string (to be able to clear the field).
	 *
	 * @since 4.1.0
	 *
	 * @param string $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_alphanum( $value = '', $request, $param ) {
		if ( ! empty( $value ) && ( ! is_string( $value ) || ! preg_match( '/[a-z0-9]+/i', $value ) ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be an alphanumeric string.', 'jetpack' ), $param ) );
		}
		return true;
	}

	/**
	 * Validates that the parameter is among the views where the Sharing can be displayed.
	 *
	 * @since 4.1.0
	 *
	 * @param string|bool $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_sharing_show( $value, $request, $param ) {
		$views = array( 'index', 'post', 'page', 'attachment', 'jetpack-portfolio' );
		if ( ! array_intersect( $views, $value ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be %s.', 'jetpack' ), $param, join( ', ', $views ) ) );
		}
		return true;
	}

	/**
	 * Validates that the parameter is among the views where the Sharing can be displayed.
	 *
	 * @since 4.1.0
	 *
	 * @param string|bool $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_services( $value, $request, $param ) {
		if ( ! is_array( $value ) || ! isset( $value['visible'] ) || ! isset( $value['hidden'] ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be an array with visible and hidden items.', 'jetpack' ), $param ) );
		}

		// Allow to clear everything.
		if ( empty( $value['visible'] ) && empty( $value['hidden'] ) ) {
			return true;
		}

		if ( ! class_exists( 'Sharing_Service' ) && ! @include( JETPACK__PLUGIN_DIR . 'modules/sharing/sharing-service.php' ) ) {
			return new WP_Error( 'invalid_param', esc_html__( 'Failed loading required dependency Sharing_Service.', 'jetpack' ) );
		}
		$sharer = new Sharing_Service();
		$services = array_keys( $sharer->get_all_services() );

		if (
			( ! empty( $value['visible'] ) && ! array_intersect( $value['visible'], $services ) )
			||
			( ! empty( $value['hidden'] ) && ! array_intersect( $value['hidden'], $services ) ) )
		{
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s visible and hidden items must be a list of %s.', 'jetpack' ), $param, join( ', ', $services ) ) );
		}
		return true;
	}

	/**
	 * Validates that the parameter has enough information to build a custom sharing button.
	 *
	 * @since 4.1.0
	 *
	 * @param string|bool $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_custom_service( $value, $request, $param ) {
		if ( ! is_array( $value ) || ! isset( $value['sharing_name'] ) || ! isset( $value['sharing_url'] ) || ! isset( $value['sharing_icon'] ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be an array with sharing name, url and icon.', 'jetpack' ), $param ) );
		}

		// Allow to clear everything.
		if ( empty( $value['sharing_name'] ) && empty( $value['sharing_url'] ) && empty( $value['sharing_icon'] ) ) {
			return true;
		}

		if ( ! class_exists( 'Sharing_Service' ) && ! @include( JETPACK__PLUGIN_DIR . 'modules/sharing/sharing-service.php' ) ) {
			return new WP_Error( 'invalid_param', esc_html__( 'Failed loading required dependency Sharing_Service.', 'jetpack' ) );
		}

		if ( ( ! empty( $value['sharing_name'] ) && ! is_string( $value['sharing_name'] ) )
		|| ( ! empty( $value['sharing_url'] ) && ! is_string( $value['sharing_url'] ) )
		|| ( ! empty( $value['sharing_icon'] ) && ! is_string( $value['sharing_icon'] ) ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s needs sharing name, url and icon.', 'jetpack' ), $param ) );
		}
		return true;
	}

	/**
	 * Validates that the parameter is a custom sharing service ID like 'custom-1461976264'.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_custom_service_id( $value = '', $request, $param ) {
		if ( ! empty( $value ) && ( ! is_string( $value ) || ! preg_match( '/custom\-[0-1]+/i', $value ) ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( "%s must be a string prefixed with 'custom-' and followed by a numeric ID.", 'jetpack' ), $param ) );
		}

		if ( ! class_exists( 'Sharing_Service' ) && ! @include( JETPACK__PLUGIN_DIR . 'modules/sharing/sharing-service.php' ) ) {
			return new WP_Error( 'invalid_param', esc_html__( 'Failed loading required dependency Sharing_Service.', 'jetpack' ) );
		}
		$sharer = new Sharing_Service();
		$services = array_keys( $sharer->get_all_services() );

		if ( ! empty( $value ) && ! in_array( $value, $services ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s is not a registered custom sharing service.', 'jetpack' ), $param ) );
		}

		return true;
	}

	/**
	 * Validates that the parameter is a Twitter username or empty string (to be able to clear the field).
	 *
	 * @since 4.1.0
	 *
	 * @param string $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_twitter_username( $value = '', $request, $param ) {
		if ( ! empty( $value ) && ( ! is_string( $value ) || ! preg_match( '/^@?\w{1,15}$/i', $value ) ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be a Twitter username.', 'jetpack' ), $param ) );
		}
		return true;
	}

	/**
	 * Validates that the parameter is a string.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value Value to check.
	 * @param WP_REST_Request $request
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function validate_string( $value = '', $request, $param ) {
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be a string.', 'jetpack' ), $param ) );
		}
		return true;
	}

	/**
	 * Get the currently accessed route and return the module slug in it.
	 *
	 * @since 4.1.0
	 *
	 * @param string $route Regular expression for the endpoint with the module slug to return.
	 *
	 * @return array
	 */
	public static function get_module_requested( $route ) {

		if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			return '';
		}

		preg_match( "#$route#", $GLOBALS['wp']->query_vars['rest_route'], $module );

		if ( empty( $module['slug'] ) ) {
			return '';
		}

		return $module['slug'];
	}

	/**
	 * Remove 'validate_callback' item from options available for module.
	 * Fetch current option value and add to array of module options.
	 * Prepare values of module options that need special handling, like those saved in wpcom.
	 *
	 * @since 4.1.0
	 *
	 * @param string $module Module slug.
	 * @return array
	 */
	public static function prepare_options_for_response( $module = '' ) {
		$options = self::get_module_available_options( $module, false );

		if ( ! is_array( $options ) || empty( $options ) ) {
			return $options;
		}

		foreach ( $options as $key => $value ) {

			if ( isset( $options[ $key ]['validate_callback'] ) ) {
				unset( $options[ $key ]['validate_callback'] );
			}

			$default_value = isset( $options[ $key ]['default'] ) ? $options[ $key ]['default'] : '';

			$current_value = get_option( $key, $default_value );

			$options[ $key ]['current_value'] = self::cast_value( $current_value, $options[ $key ] );
		}

		// Some modules need special treatment.
		switch ( $module ) {

			case 'monitor':
				// Status of user notifications
				$options['monitor_receive_notifications']['current_value'] = self::cast_value( self::get_remote_value( 'monitor', 'monitor_receive_notifications' ), $options['monitor_receive_notifications'] );
				break;

			case 'post-by-email':
				// Email address
				$options['post_by_email_address']['current_value'] = self::cast_value( self::get_remote_value( 'post-by-email', 'post_by_email_address' ), $options['post_by_email_address'] );
				break;

			case 'protect':
				// Protect
				$options['jetpack_protect_key']['current_value'] = get_site_option( 'jetpack_protect_key', false );
				if ( ! function_exists( 'jetpack_protect_format_whitelist' ) ) {
					@include( JETPACK__PLUGIN_DIR . 'modules/protect/shared-functions.php' );
				}
				$options['jetpack_protect_global_whitelist']['current_value'] = jetpack_protect_format_whitelist();
				break;

			case 'related-posts':
				// It's local, but it must be broken apart since it's saved as an array.
				$options = self::split_options( $options, Jetpack_Options::get_option( 'relatedposts' ) );
				break;

			case 'verification-tools':
				// It's local, but it must be broken apart since it's saved as an array.
				$options = self::split_options( $options, get_option( 'verification_services_codes' ) );
				break;

			case 'sharedaddy':
				// It's local, but it must be broken apart since it's saved as an array.
				if ( ! class_exists( 'Sharing_Service' ) && ! @include( JETPACK__PLUGIN_DIR . 'modules/sharing/sharing-service.php' ) ) {
					break;
				}
				$sharer = new Sharing_Service();
				$options = self::split_options( $options, $sharer->get_global_options() );
				$options['sharing_services']['current_value'] = $sharer->get_blog_services();
				break;

			case 'site-icon':
				// Return site icon ID and URL to make it more complete.
				$options['site_icon_id']['current_value'] = Jetpack_Options::get_option( 'site_icon_id' );
				if ( ! function_exists( 'jetpack_site_icon_url' ) ) {
					@include( JETPACK__PLUGIN_DIR . 'modules/site-icon/site-icon-functions.php' );
				}
				$options['site_icon_url']['current_value'] = jetpack_site_icon_url();
				break;

			case 'after-the-deadline':
				if ( ! function_exists( 'AtD_get_options' ) ) {
					@include( JETPACK__PLUGIN_DIR . 'modules/after-the-deadline.php' );
				}
				$atd_options = array_merge( AtD_get_options( get_current_user_id(), 'AtD_options' ), AtD_get_options( get_current_user_id(), 'AtD_check_when' ) );
				unset( $atd_options['name'] );
				foreach ( $atd_options as $key => $value ) {
					$options[ $key ]['current_value'] = self::cast_value( $value, $options[ $key ] );
				}
				$atd_options = AtD_get_options( get_current_user_id(), 'AtD_guess_lang' );
				$options['guess_lang']['current_value'] = self::cast_value( isset( $atd_options['true'] ), $options[ 'guess_lang' ] );
				$options['ignored_phrases']['current_value'] = AtD_get_setting( get_current_user_id(), 'AtD_ignored_phrases' );
				unset( $options['unignore_phrase'] );
				break;
		}

		return $options;
	}

	/**
	 * Splits module options saved as arrays like relatedposts or verification_services_codes into separate options to be returned in the response.
	 *
	 * @since 4.1.0
	 *
	 * @param array  $separate_options Array of options admitted by the module.
	 * @param array  $grouped_options Option saved as array to be splitted.
	 * @param string $prefix Optional prefix for the separate option keys.
	 *
	 * @return array
	 */
	public static function split_options( $separate_options, $grouped_options, $prefix = '' ) {
		if ( is_array( $grouped_options ) ) {
			foreach ( $grouped_options as $key => $value ) {
				$option_key = $prefix . $key;
				if ( isset( $separate_options[ $option_key ] ) ) {
					$separate_options[ $option_key ]['current_value'] = self::cast_value( $grouped_options[ $key ], $separate_options[ $option_key ] );
				}
			}
		}
		return $separate_options;
	}

	/**
	 * Perform a casting to the value specified in the option definition.
	 *
	 * @since 4.1.0
	 *
	 * @param mixed $value Value to cast to the proper type.
	 * @param array $definition Type to cast the value to.
	 *
	 * @return bool|float|int|string
	 */
	public static function cast_value( $value, $definition ) {
		if ( isset( $definition['type'] ) ) {
			switch ( $definition['type'] ) {
				case 'boolean':
					if ( 'true' === $value ) {
						return true;
					} elseif ( 'false' === $value ) {
						return false;
					}
					return (bool) $value;
					break;

				case 'integer':
					return (int) $value;
					break;

				case 'float':
					return (float) $value;
					break;
			}
		}
		return $value;
	}

	/**
	 * Get a value not saved locally.
	 *
	 * @since 4.1.0
	 *
	 * @param string $module Module slug.
	 * @param string $option Option name.
	 *
	 * @return bool Whether user is receiving notifications or not.
	 */
	public static function get_remote_value( $module, $option ) {

		// If option doesn't exist, 'does_not_exist' will be returned.
		$value = get_option( $option, 'does_not_exist' );

		// If option exists, just return it.
		if ( 'does_not_exist' !== $value ) {
			return $value;
		}

		// Only check a remote option if Jetpack is connected.
		if ( ! Jetpack::is_active() ) {
			return false;
		}

		// If the module is inactive, load the class to use the method.
		if ( ! did_action( 'jetpack_module_loaded_' . $module ) ) {
			// Class can't be found so do nothing.
			if ( ! @include( Jetpack::get_module_path( $module ) ) ) {
				return false;
			}
		}

		// Do what is necessary for each module.
		switch ( $module ) {
			case 'monitor':
				$monitor = new Jetpack_Monitor();
				$value = $monitor->user_receives_notifications( false );
				break;

			case 'post-by-email':
				$post_by_email = new Jetpack_Post_By_Email();
				$value = $post_by_email->get_post_by_email_address();
				break;
		}

		// Normalize value to boolean.
		if ( is_wp_error( $value ) || is_null( $value ) ) {
			$value = false;
		}

		// Save option to use it next time.
		update_option( $option, $value );

		return $value;
	}

	/**
	 * Get number of blocked intrusion attempts.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed|WP_Error Number of blocked attempts if protection is enabled. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function protect_get_blocked_count() {
		if ( Jetpack::is_module_active( 'protect' ) ) {
			return get_site_option( 'jetpack_protect_blocked_attempts' );
		}

		return new WP_Error( 'not_active', esc_html__( 'The requested Jetpack module is not active.', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * Get number of spam messages blocked by Akismet.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $data {
	 *     Array of parameters received by request.
	 *
	 *     @type string $date Date range to restrict results to.
	 * }
	 *
	 * @return int|string Number of spam blocked by Akismet. Otherwise, an error message.
	 */
	public static function akismet_get_stats_data( WP_REST_Request $data ) {
		if ( ! is_wp_error( $status = self::akismet_is_active_and_registered() ) ) {
			return rest_ensure_response( Akismet_Admin::get_stats( Akismet::get_api_key() ) );
		} else {
			return $status->get_error_code();
		}
	}

	/**
	 * Get date of last downtime.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed|WP_Error Number of days since last downtime. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function monitor_get_last_downtime() {
		if ( Jetpack::is_module_active( 'monitor' ) ) {
			$monitor       = new Jetpack_Monitor();
			$last_downtime = $monitor->monitor_get_last_downtime();
			if ( is_wp_error( $last_downtime ) ) {
				return $last_downtime;
			} else {
				return rest_ensure_response( array(
					'code' => 'success',
					'date' => human_time_diff( strtotime( $last_downtime ), strtotime( 'now' ) ),
				) );
			}
		}

		return new WP_Error( 'not_active', esc_html__( 'The requested Jetpack module is not active.', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * Get number of plugin updates available.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed|WP_Error Number of plugin updates available. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function get_plugin_update_count() {
		$updates = wp_get_update_data();
		if ( isset( $updates['counts'] ) && isset( $updates['counts']['plugins'] ) ) {
			$count = $updates['counts']['plugins'];
			if ( 0 == $count ) {
				$response = array(
					'code'    => 'success',
					'message' => esc_html__( 'All plugins are up-to-date. Keep up the good work!', 'jetpack' ),
					'count'   => 0,
				);
			} else {
				$response = array(
					'code'    => 'updates-available',
					'message' => esc_html( sprintf( _n( '%s plugin need updating.', '%s plugins need updating.', $count, 'jetpack' ), $count ) ),
					'count'   => $count,
				);
			}
			return rest_ensure_response( $response );
		}

		return new WP_Error( 'not_found', esc_html__( 'Could not check updates for plugins on this site.', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * Get services that this site is verified with.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed|WP_Error List of services that verified this site. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function get_verified_services() {
		if ( Jetpack::is_module_active( 'verification-tools' ) ) {
			$verification_services_codes = get_option( 'verification_services_codes' );
			if ( is_array( $verification_services_codes ) && ! empty( $verification_services_codes ) ) {
				$services = array();
				foreach ( jetpack_verification_services() as $name => $service ) {
					if ( is_array( $service ) && ! empty( $verification_services_codes[ $name ] ) ) {
						switch ( $name ) {
							case 'google':
								$services[] = 'Google';
								break;
							case 'bing':
								$services[] = 'Bing';
								break;
							case 'pinterest':
								$services[] = 'Pinterest';
								break;
						}
					}
				}
				if ( ! empty( $services ) ) {
					if ( 2 > count( $services ) ) {
						$message = esc_html( sprintf( __( 'Your site is verified with %s.', 'jetpack' ), $services[0] ) );
					} else {
						$copy_services = $services;
						$last = count( $copy_services ) - 1;
						$last_service = $copy_services[ $last ];
						unset( $copy_services[ $last ] );
						$message = esc_html( sprintf( __( 'Your site is verified with %s and %s.', 'jetpack' ), join( ', ', $copy_services ), $last_service ) );
					}
					return rest_ensure_response( array(
						'code'     => 'success',
						'message'  => $message,
						'services' => $services,
					) );
				}
			}
			return new WP_Error( 'empty', esc_html__( 'Site not verified with any service.', 'jetpack' ), array( 'status' => 404 ) );
		}

		return new WP_Error( 'not_active', esc_html__( 'The requested Jetpack module is not active.', 'jetpack' ), array( 'status' => 404 ) );
	}

	/**
	 * Get VaultPress site data including, among other things, the date of tge last backup if it was completed.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed|WP_Error VaultPress site data. Otherwise, a WP_Error instance with the corresponding error.
	 */
	public static function vaultpress_get_site_data() {
		if ( class_exists( 'VaultPress' ) ) {
			$vaultpress = new VaultPress();
			if ( ! $vaultpress->is_registered() ) {
				return rest_ensure_response( array(
					'code'    => 'not_registered',
					'message' => esc_html( __( 'You need to register for VaultPress.', 'jetpack' ) )
				) );
			}
			$data = json_decode( base64_decode( $vaultpress->contact_service( 'plugin_data' ) ) );
			if ( is_wp_error( $data ) ) {
				return $data;
			} else {
				return rest_ensure_response( array(
					'code'    => 'success',
					'message' => esc_html( sprintf( __( 'Your site was successfully backed-up %s ago.', 'jetpack' ), human_time_diff( $data->backups->last_backup, current_time( 'timestamp' ) ) ) ),
					'data'    => $data,
				) );
			}
		}

		return new WP_Error( 'not_active', esc_html__( 'The requested Jetpack module is not active.', 'jetpack' ), array( 'status' => 404 ) );
	}

} // class end
