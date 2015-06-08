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
	const model_added = null;

	/**
	 * Top-level keys are versions (major and minor version numbers, eg "4.6")
	 * next-level keys are model names (eg "Event") that underwent some change in that version
	 * and the value is either EE_REST_API_Model_Version_Info::model_added/NULL (indicating the model is completely NEW in this version),
	 * or it's an array where fields are value names.
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

	protected $_cached_model_changes_between_requested_version_and_current = null;

	public function __construct( $requested_version ) {
		$this->_requested_version = $requested_version;
		$this->_model_changes = array(
			'4.6' => array(
				//this is the first version of the API supported,
				//so we don't need to say how it's different from 4.5
			),
			'4.7' => array(
				'Registration_Payment' => null,
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
	 * Takes into account the requested version, and the current version, and
	 * what changed between the two, and tries to return
	 */
	public function get_all_models_for_requested_version() {
		if( $this->_cached_models_for_requested_version === null ) {
			$all_models_in_current_version = EE_Registry::instance()->non_abstract_db_models;
			$current_version = EED_REST_API::core_version();
			$requested_version = $this->requested_version();
			foreach( $this->get_all_model_changes_between_requested_version_and_current() as $version => $models_changed ) {
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
	 * Returns a slice of EE_REST_API_Model_Version_Info::model_changes()'s array
	 * indicating exactly what changes happened between the current core version,
	 * and the version requested
	 * @param type $requested_version
	 * @param type $current_version
	 * @return array
	 */
	public function get_all_model_changes_between_requested_version_and_current() {
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
}

// End of file EE_REST_API_Model_Version_Info.class.php