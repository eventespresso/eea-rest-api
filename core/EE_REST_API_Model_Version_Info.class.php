<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_REST_API_Model_Version_Info
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 * Class for things that bridge the gap between API resources and PHP models describing
 * the underlying data.
 * This should really be the only place in the API that is directly aware of models,
 * everywhere should go through here to learn about the models and interact with them.
 * This is done so the API can serve requests for a previous version from data
 * from the current version of core
 *
 */
class EE_REST_API_Model_Version_Info {

	/**
	 * Constant used in the $_model_changes array to indicate that a model
	 * was completely new in this version
	 */
	const model_added = 'model_added_in_this_version';

	/**
	 * Top-level keys are versions (major and minor version numbers, eg "4.6")
	 * next-level keys are model names (eg "Event") that underwent some change in that version
	 * and the value is either EE_REST_API_Model_Version_Info::model_added (indicating the model is completely NEW in this version),
	 * or it's an array where the values are model field names, or API resource properties (ie, non-model fields that appear in REST API results)
	 * If a version is missing then we don't know anything about what changes it introduced from the previous version
	 * @var array
	 */
	protected $_model_changes = null;

	/**
	 *
	 * @var string indicating what version of the API was requested
	 * (eg although core might be at version 4.8.11, they may have sent a request
	 * for 4.6)
	 */
	protected $_requested_version = null;

	/**
	 * Keys are model names, values are their classnames.
	 * We cache this so we only need to calculate this once per request
	 * @var array
	 */
	protected $_cached_models_for_requested_version = null;

	/**
	 *
	 * @var array
	 */
	protected $_cached_model_changes_between_requested_version_and_current = null;

	/**
	 * 2d array where top-level keys are model names, 2nd-level keys are field names
	 * and values are the actual field objects
	 * @var array
	 */
	protected $_cached_fields_on_models = array();


	public function __construct( $requested_version ) {
		$this->_requested_version = $requested_version;
		$this->_model_changes = array(
			'4.6' => array(
				//this is the first version of the API supported,
				//so we don't need to say how it's different from 4.5
			),
			'4.7' => array(
				'Registration_Payment' => EE_REST_API_Model_Version_Info::model_added,
				'Registration' => array(
					'REG_paid',
				)
			),
			'4.8' => array(
				//didn't actually make any changes to the models, just how line items are organized
			)
		);
	}

	/**
	 * Returns a slice of EE_REST_API_Model_Version_Info::model_changes()'s array
	 * indicating exactly what changes happened between the current core version,
	 * and the version requested
	 * @param type $requested_version
	 * @param type $current_version
	 * @return array
	 */
	public function model_changes_between_requested_version_and_current() {
		if( $this->_cached_model_changes_between_requested_version_and_current === null ) {
			$model_changes = array();
			foreach( $this->model_changes() as $version => $models_changed_in_version ) {
				if( $version <= EED_REST_API::core_version()  && $version > $this->requested_version()  ) {
					$model_changes[ $version ] = $models_changed_in_version;
				}
			}
			$this->_cached_model_changes_between_requested_version_and_current = $model_changes;
		}
		return $this->_cached_model_changes_between_requested_version_and_current;
	}

	/**
	 * If a request was sent to 'wp-json/ee/v4.7/events' this would be '4.7'
	 * @return string like '4.6'
	 */
	public function requested_version(){
		return $this->_requested_version;
	}

	/**
	 * Returns an array describing how the models have changed in each version of core
	 * that supports the API (starting at 4.6)
	 * Top-level keys are versions (major and minor version numbers, eg "4.6")
	 * next-level keys are model names (eg "Event") that underwent some change in that version
	 * and the value is either NULL (indicating the model is completely NEW in this version),
	 * or it's an array where fields are value names.
	 * If a version is missing then we don't know anything about what changes it introduced from the previous version
	 * @return array
	 */
	public function model_changes(){
		return $this->_model_changes;
	}

	/**
	 * Takes into account the requested version, and the current version, and
	 * what changed between the two, and tries to return.
	 * Analogous to EE_Registry::instance()->non_abstract_db_models
	 * @return array keys are model names, values are their classname
	 */
	public function models_for_requested_version() {
		if( $this->_cached_models_for_requested_version === null ) {
			$all_models_in_current_version = EE_Registry::instance()->non_abstract_db_models;
			$current_version = EED_REST_API::core_version();
			$requested_version = $this->requested_version();
			foreach( $this->model_changes_between_requested_version_and_current() as $version => $models_changed ) {
				foreach( $models_changed as $model_name => $new_indicator_or_fields_added ) {
					if( $new_indicator_or_fields_added === EE_REST_API_Model_Version_Info::model_added ) {
						unset( $all_models_in_current_version[ $model_name ] );
					}
				}
			}
			$this->_cached_models_for_requested_version = $all_models_in_current_version;
		}
		return $this->_cached_models_for_requested_version;
	}
	/**
	 * Determines if this is a valid model name in the requested version.
	 * Similar to EE_Registry::instance()->is_model_name(), but takes the requested
	 * version's models into account
	 * @param string $model_name eg 'Event'
	 * @return boolean
	 */
	public function is_model_name_in_this_verison( $model_name ) {
		$model_names = $this->models_for_requested_version();
		if( isset( $model_names[ $model_name ] ) ) {
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Wrapper for EE_Registry::instance()->load_model(), but takes the requested
	 * version's models into account
	 * @param string $model_name
	 * @return EEM_Base
	 * @throws EE_Error
	 */
	public function load_model( $model_name ) {
		if( $this->is_model_name_in_this_verison(  $model_name ) ) {
			return EE_Registry::instance()->load_model( $model_name );
		}else{
			throw new EE_Error( sprintf( __( 'Cannot load model "%1$s" because it does not exist in version %2$s of Event Espresso', 'event_espresso' ), $model_name, $this->requested_version() ) );
		}
	}

	/**
	 * Gets all the fields that should exist on this model right now
	 * @param EEM_Base $model
	 */
	public function fields_on_model_in_this_version( $model ) {
		if( ! isset( $this->_cached_fields_on_models[ $model->get_this_model_name() ] ) ) {
			//get all model changes between the requested version and current core version
			$changes = $this->model_changes_between_requested_version_and_current();
			//fetch all fields currently on this model
			$current_fields = $model->field_settings();
			//remove all fields that have been added since
			foreach( $changes as $version => $changes_in_version ) {
				if( isset( $changes_in_version[ $model->get_this_model_name() ] ) && $changes_in_version[ $model->get_this_model_name() ] !== EE_REST_API_Model_Version_Info::model_added ) {
					$current_fields = array_diff_key( $current_fields, array_flip( $changes_in_version[ $model->get_this_model_name() ] ) );
				}
			}
			$this->_cached_fields_on_models = $current_fields;
		}
		return $this->_cached_fields_on_models;
	}

}

// End of file EE_REST_API_Model_Version_Info.class.php