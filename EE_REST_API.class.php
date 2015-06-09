<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit(); }
/**
 * ------------------------------------------------------------------------
 *
 * Class  EE_REST_API
 *
 * @package			Event Espresso
 * @subpackage		eea-rest-api
 * @author			    Brent Christensen
 * @ version		 	$VID:$
 *
 * ------------------------------------------------------------------------
 */
// define the plugin directory path and URL
define( 'EE_REST_API_BASENAME', plugin_basename( EE_REST_API_PLUGIN_FILE ));
define( 'EE_REST_API_PATH', plugin_dir_path( __FILE__ ));
define( 'EE_REST_API_URL', plugin_dir_url( __FILE__ ));
define( 'EE_REST_API_ADMIN', EE_REST_API_PATH . 'admin' . DS . 'rest_api' . DS );
Class  EE_REST_API extends EE_Addon {

	/**
	 * class constructor
	 */
	public function __construct() {
	}

	public static function register_addon() {
		// register addon via Plugin API
		EE_Register_Addon::register(
			'REST_API',
			array(
				'version' 					=> EE_REST_API_VERSION,
				'min_core_version' => '4.6.32.rc.000',
				'main_file_path' 				=> EE_REST_API_PLUGIN_FILE,
				'admin_path' 			=> EE_REST_API_ADMIN,
				'admin_callback'		=> 'additional_admin_hooks',
				'config_class' 			=> 'EE_REST_API_Config',
				'config_name' 		=> 'EE_REST_API',
				'autoloader_paths' => array(
					'EE_REST_API_Config' 			=> EE_REST_API_PATH . 'EE_REST_API_Config.php',
					'Rest_Api_Admin_Page' 		=> EE_REST_API_ADMIN . 'Rest_Api_Admin_Page.core.php',
					'Rest_Api_Admin_Page_Init' => EE_REST_API_ADMIN . 'Rest_Api_Admin_Page_Init.core.php',
					'EE_REST_API_Capabilities' => EE_REST_API_PATH . 'core/EE_REST_API_Capabilities.class.php',
					'EE_REST_API_Model_Version_Info' => EE_REST_API_PATH . 'core/EE_REST_API_Model_Version_Info.class.php'
					//note, we also just autoloaded the entire controllers folder
				),

				'module_paths' 		=> array( EE_REST_API_PATH . 'EED_REST_API.module.php' ),
				// if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
				'pue_options'			=> array(
					'pue_plugin_slug' => 'eea-rest-api',
					'plugin_basename' => EE_REST_API_BASENAME,
					'checkPeriod' => '24',
					'use_wp_update' => FALSE,
					),
				'capabilities' => array(
					'administrator' => array(
						'read_addon', 'edit_addon', 'edit_others_addon', 'edit_private_addon'
						),
					),
			)
		);
		//autoload call controller
		EE_Registry::instance()->load_helper( 'Activation' );
		EEH_Autoloader::register_autoloaders_for_each_file_in_folder( EE_REST_API_PATH . 'controllers' );
		//update the routes wp option whenever any other EE addon is updated or activated, or core is updated
		add_action( 'AHEE__EEH_Activation__initialize_db_content', array( 'EED_REST_API', 'save_ee_routes' ) );
		add_action( 'AHEE__EE_Addon__initialize_default_data__begin', array( 'EED_REST_API', 'save_ee_routes' ) );
	}



	/**
	 * 	additional_admin_hooks
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function additional_admin_hooks() {
		// is admin and not in M-Mode ?
		if ( is_admin() && ! EE_Maintenance_Mode::instance()->level() ) {
			add_filter( 'plugin_action_links', array( $this, 'plugin_actions' ), 10, 2 );
		}
	}



	/**
	 * plugin_actions
	 *
	 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
	 * @param $links
	 * @param $file
	 * @return array
	 */
	public function plugin_actions( $links, $file ) {
		if ( $file == EE_REST_API_BASENAME ) {
			// before other links
			array_unshift( $links, '<a href="admin.php?page=espresso_rest_api">' . __('Settings') . '</a>' );
		}
		return $links;
	}

	/**
	 * Initialize the routes option upon installation. This way we don't have to
	 * go through the somewhat lengthy process of initializing the routes on every request
	 * (instead we just do it when this addon is activated or upgraded, or when any other
	 * addon or core is activated or upgraded)
	 */
	public function initialize_db() {
		parent::initialize_db();
		EED_REST_API::save_ee_routes();
	}






}
// End of file EE_REST_API.class.php
// Location: wp-content/plugins/eea-rest-api/EE_REST_API.class.php
