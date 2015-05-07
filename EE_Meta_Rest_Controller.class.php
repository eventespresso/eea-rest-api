<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Models_Rest_Read_Controller
 *
 * Handles requests relating to meta info
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Meta_Rest_Controller {



	public static function handle_request_models_meta() {
		$response = array();
		foreach( EE_Registry::instance()->non_abstract_db_models as $model_name => $model_classname ){
			$model = EE_Registry::instance()->load_model( $model_name );
			$fields_json = array();
			foreach( $model->field_settings() as $field_name => $field_obj ) {

				if( $field_obj instanceof EE_Boolean_Field ) {
					$datatype = 'Boolean';
				}elseif( $field_obj->get_wpdb_data_type() == '%d' ) {
					$datatype = 'Number';
				}elseif( $field_name instanceof EE_Serialized_Text_Field ) {
					$datatype = 'Object';
				}else{
					$datatype = 'String';
				}
				$field_json = array(
					'name' => $field_name,
					'nicename' => $field_obj->get_nicename(),
					'raw' => true,
					'type' => str_replace('EE_', '', get_class( $field_obj ) ),
					'datatype' => $datatype,
					'nullable' => $field_obj->is_nullable(),
					'default' => $field_obj->get_default_value() === INF ? EE_INF_IN_DB : $field_obj->get_default_value(),
					'table_alias' => $field_obj->get_table_alias(),
					'table_column' => $field_obj->get_table_column(),
					'always_available' => true
				);
				if( EE_Models_Rest_Read_Controller::is_subclass_of_one( $field_obj, EE_Models_Rest_Read_Controller::fields_ignored() ) ) {
					continue;
				}
				if( EE_Models_Rest_Read_Controller::is_subclass_of_one( $field_obj, EE_Models_Rest_Read_Controller::fields_raw() ) ) {
					$raw_field_json = $field_json;
					//specify that the non-raw version isn't queryable or editable
					$field_json[ 'raw' ] = false;
					$field_json[ 'always_available' ] = false;

					//change the name of the 'raw' version
					$raw_field_json[ 'name' ] = $field_json[ 'name' ] . '_raw';
					$raw_field_json[ 'nicename' ] = sprintf( __( '%1$s (%2$s)', 'event_espresso'), $field_json[ 'nicename' ], 'raw' );
					$fields_json[ $raw_field_json[ 'name' ] ] = $raw_field_json;
				}
				if( EE_Models_Rest_Read_Controller::is_subclass_of_one( $field_obj, EE_Models_Rest_Read_Controller::fields_pretty() ) ) {
					$pretty_field_json = $field_json;
					//specify that the non-raw version isn't queryable or editable
					$pretty_field_json[ 'raw' ] = false;

					//change the name of the 'raw' version
					$pretty_field_json[ 'name' ] = $field_json[ 'name' ] . '_pretty';
					$pretty_field_json[ 'nicename' ] = sprintf( __( '%1$s (%2$s)', 'event_espresso'), $field_json[ 'nicename' ], 'pretty' );
					$fields_json[ $pretty_field_json[ 'name' ] ] = $pretty_field_json;
				}
				$fields_json[ $field_json[ 'name' ] ] = $field_json;

			}
			$response[ $model_name ]['fields'] = apply_filters( 'FHEE__EE_Meta_Rest_Controller__handle_request_models_meta__fields', $fields_json, $model );
			$relations_json = array();
			foreach( $model->relation_settings()  as $relation_name => $relation_obj ) {
				$relation_json = array(
					'name' => $relation_name,
					'type' => str_replace( 'EE_', '', get_class( $relation_obj ) )
				);
				$relations_json[ $relation_name ] = $relation_json;
			}
			$response[ $model_name ][ 'relations' ] = apply_filters( 'FHEE__EE_Meta_Rest_Controller__handle_request_models_meta__relations', $relations_json, $model );
		}
		return $response;

	}

	public static function filter_ee_metadata_into_index( $existing_index_info ) {
		$addons = array();
		foreach( EE_Registry::instance()->addons as $addon){
			$addon_json = array(
				'name' => $addon->name(),
				'version' => $addon->version()
			);
			$addons[ $addon_json[ 'name' ] ] = $addon_json;
		}
		$existing_index_info[ 'ee' ] = array(
			'version' => EEM_System_Status::instance()->get_ee_version(),
			'addons' => $addons,
			'maintenance_mode' => EE_Maintenance_Mode::instance()->level() 
		);
		return $existing_index_info;
	}
}


// End of file EE_Models_Rest_Read_Controller.class.php