<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_REST_API_Capabilities
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_REST_API_Capabilities {
	protected static $_access_restrictions;
	/**
	 * Returns an array that describes what capabilities are required to view/edit/create/delete
	 * on certain endpoints.
	 * Basically, top-level keys are model names;
	 * next-level-keys indicate whether teh permissions are with regards to reading,
	 * creating, updating, or deleting those models;
	 * next-level keys are field names, or "*" to indicate a fallback catch-all;
	 * next-level keys are permission names, and their values are an EE_API_Access_Restriction object
	 * which describes how their access is limited if they don't have the associated permission.
	 * @return array
	 */
	public static function get_access_restrictions() {
		if( ! self::$_access_restrictions ) {
			$restrictions = array(
				'Event' => array(
					 WP_JSON_Server::READABLE => array(
						 '*' => array(
							 'ee_read_events' => new EE_API_Access_Entity_If( array( 'status' => 'publish', 'Datetime.DTT_EVT_end' => array( '<=', current_time('mysql' ) ) ) ),

						 )
					 )
				)
			);
			//permissions array DOESN'T account for how someone might have permission to see ALL
			//registrations, but not use the specific page. eg they might be able to access 'registrations/' but not 'registration/10'
			$restrictions =  apply_filters( 'FHEE__EE_Models_Rest_Read_Controller__get_permissions', $restrictions );
			foreach( $restrictions as $model_name => $request_types_handled ) {
				foreach( $request_types_handled as $request_type_handled => $api_fields ){
					foreach( $api_fields as $api_field_name => $permissions_and_access_restrictions ) {
						foreach( $permissions_and_access_restrictions as $capability => $access_restriction ){
							if( ! $access_restriction instanceof EE_API_Access_Restriction ) {
								throw new EE_Error( sprintf( __( 'You must provide an EE_API_Access_Restriction object that describes how to restrict access to users who dont have a particular permission for the model "%s", request type "%s", at the capability "%s".', 'event_espresso' ), $model_name, $request_type_handled, $capability ) );
							}else{
								$access_restriction->set_model_name( $model_name );
							}
						}
					}
				}
			}
			self::$_access_restrictions = $restrictions;
		}
		return self::$_access_restrictions;
	}

	/**
	 *
	 * @param string $model_name
	 * @param type $request_type
	 * @return boolean
	 */
	public static function current_user_can_access_any( $model_name, $request_type = WP_JSON_Server::READABLE ) {
		$access_restrictions = self::get_access_restrictions();
		if( isset( $access_restrictions[ $model_name ] ) && isset( $access_restrictions[ $model_name ][ $request_type ] ) ) {
			return true;
		}else{
			return false;
		}
	}
}

/**
 * Used in the API's EE_Models_Rest_Read_Controller::get_permissions() array
 * to indicate that if if a certain permission is missing, these additional WHERE conditions should be added
 */
abstract class EE_API_Access_Restriction{
	protected $_model_name;
	public function set_model_name( $model_name ){
		$this->_model_name = $model_name;
	}
	/**
	 *
	 * @return EEM_Base
	 */
	protected function _get_model(){
		return EE_Registry::instance()->load_model( $this->_model_name );
	}
	/**
	 * Returns an array like EEM_Base::get_all() 's index of 0, ie
	 * only the WHERE conditions in the query. These are conditions that are
	 * added onto queries when the user is missing the associated permission
	 * @return array
	 */
	abstract public function get_where_conditions();
}
/**
 * If the user doesn't have the indicated permission, they should only have access
 * to model objects that meet this criteria
 */
class EE_API_Access_Entity_If extends EE_API_Access_Restriction{
	protected $_where_conditions = array();

	public function __construct( $where_conditions = array() ){
		$this->_where_conditions = $where_conditions;
	}
	public function get_where_conditions() {
		return $this->_where_conditions;
	}
}
/**
 * If the user doesn't ahve the indicated permission, they should only be able
 * to access model objects that belong to them.
 */
class EE_API_Access_Entity_If_Owner extends EE_API_Access_Restriction{
	public function get_where_conditions() {
		return $this->_get_model()->alter_query_params_to_only_include_mine();
	}
}
/**
 * Ie if the user doesn't have the indicated permission, they shouldn't be able
 * to access anything
 */
class EE_API_Access_Entity_Never extends EE_API_Access_Restriction{
	//mostly marker and unfortunately logic is in client code
	//but this should indicate to NOT bothe running a query because nothing should be returned
	public function get_where_conditions() {
		$model = $this->_get_model();
		if( $model->has_primary_key_field() ) {
			return array( $model->primary_key_name() => -1 );
		}else{
			$fk_field = $model->get_a_field_of_type( 'EE_Foreign_Key_Field_Base' );
			return array( $fk_field->get_name() => -1 );
		}
	}
}
// End of file EE_REST_API_Capabilities.class.php