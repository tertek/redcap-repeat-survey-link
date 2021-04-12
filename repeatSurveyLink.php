<?php

// Set the namespace defined in your config file
namespace STPH\repeatSurveyLink;

use \REDCap;
use \Survey;
use \Records;
use \RepeatInstance;

// Fallback for versions prior to PHP 7.3.0
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}

// Declare your module class, which must extend AbstractExternalModule 
class repeatSurveyLink extends \ExternalModules\AbstractExternalModule {

    private $project_id;
    private $event_id;
    private $record_id;

    private $definitions;
    private $records;
    private $inserts;
    private $updates;
  
   /**
    * Constructs the class
    *
    */
    public function __construct()
    {        
        parent::__construct();
       // Other code to run when object is instantiated
    }

   /**
    * Hooks Repeat Survey Link module to redcap_module_save_configuration
    *
    */
    public function redcap_every_page_top($project_id) {
        
        //$this->init_rsl($project_id);

    }


   /**
    * Hooks Repeat Survey Link module to redcap_module_save_configuration
    *
    */
    public function redcap_module_save_configuration($project_id) {

        $this->init_rsl($project_id);

    }

   /**
    * Hooks Repeat Survey Link module to redcap_save_record
    *
    */    
    public function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {

        $this->init_rsl($project_id, $event_id,  $record);

    }        

   /**
    * Main
    *
    */      
    public function init_rsl($project_id, $event_id=null, $record_id = null) {

        $this->setParams($project_id, $event_id, $record_id);
        $this->setDefinitions();
        $this->setRecords();
        $this->setInsertsAndUpdates();
        $this->runQueries();        
    }

    private function setParams($project_id, $event_id, $record_id) {
        
        //  Set project_id
        $this->project_id = $project_id;

        //  Set event id
        if($event_id == null) {
            $this->event_id = getSingleEvent($project_id);
        } else {
            $this->event_id = $event_id;
        }

        //  Set record id
        $this->record_id = $record_id;
    }

    #  Set definitions by validation of settings
    private function setDefinitions() {        

        foreach ( $this->getSubSettings("repeat-survey-links") as $def ) {
            $field_name = $def["helper-variable"];
            $form_name  = $def["instrument-name"];

            //  Check if settings have been set
            if(empty($field_name) || empty($form_name)) {
                break;
            }
            //  Check if instrument is of type repeating (REDCap v9.7.6+)
            if( !in_array( $form_name, $this->framework->getRepeatingForms($event_id, $project_id) ) ) {
                break;
            }
            //  Check if field is of type text (REDCap v10.8.2+)
            /* if( $field->getType() != 'text') {
                break;
            } */

            $this->definitions[] = $def;
        }

    }

    #   Fetch records only once with all field data combined, not everytime
    private function setRecords(){
        $fields = array_merge( 
            array("record_id"), 
            array_column($this->definitions, 'helper-variable')
        );
        $this->records = REDCap::getData('array', null, $fields , null, null, true);        
    }

    private function setInsertsAndUpdates() {

        global $Proj;
        foreach ($this->definitions as $key => $definition) {
            $field_name = $definition["helper-variable"];
            $form_name  = $definition["instrument-name"];
   
            //  Loop over all(!) records
            foreach ($this->records as $record => $values) {
    
                //  Get field value
                $value = $values[$this->event_id][$field_name];
    
                # Taken from redcap_v10.9.1\Classes\Survey.php:displaySurveyQueueForRecord():492
                list ($instanceTotal, $instanceMax) = RepeatInstance::getRepeatFormInstanceMaxCount($record, $this->event_id, $form_name, $Proj);
                $repeatSurveyLink = REDCap::getSurveyLink($record, $form_name, $this->event_id, $instanceMax + 1);
    
                if(empty($value)) {                    
                    //  Add to inserts if has no value yet
                    $this->inserts[] =  [
                        "project_id" => $this->project_id,
                        "event_id" => $this->event_id,
                        "record_id" => $record, 
                        "field_name" => $field_name,
                        "repeat_survey_link" => $repeatSurveyLink
                    ];                    
                } elseif($value != $repeatSurveyLink) {
                    //  Add to updates if not empty and is different from last value
                    $this->updates[] =  [
                        "project_id" => $this->project_id,
                        "event_id" => $this->event_id,
                        "record_id" => $record, 
                        "field_name" => $field_name,
                        "repeat_survey_link" => $repeatSurveyLink
                    ];
                }
            }            
        }
    }

    private function runQueries() {
        //  Query Inserts
        $this->queryInserts();

        //  Update multiple RSL in database if has updates
        $this->queryUpdates();       
    }

    private function queryInserts() {
        if( count($this->inserts) > 0 ) {
            $INSERT_VALUES = implode(",", array_map(function($entry){
                return "(" . 
                db_escape($entry["project_id"]) . ", " 
                        . db_escape($entry["event_id"]) . ", " 
                        . db_escape($entry["record_id"]) . ", '" 
                        . db_escape($entry["field_name"]) . "', '" 
                        . db_escape($entry["repeat_survey_link"]) . 
                        "')";
            }, $this->inserts));
    
            $sql_insert = 'INSERT INTO redcap_data (project_id, event_id, record, field_name, value) VALUES ' . $INSERT_VALUES;
    
            //  Execute the query                                                  
            $this->query( $sql_insert, [] );
            //  add logs

        }
    }

    private function queryUpdates() {
        if( count($this->updates) > 0 ) {
            foreach ($this->updates as $key => $entry) {
                
                $JOIN_VALUES .= "SELECT " . 
                db_escape($entry["project_id"]) . " AS project_id, " . 
                db_escape($entry["event_id"]) . " AS event_id,  " . 
                db_escape($entry["record_id"]) . " AS record, '" . 
                db_escape($entry["field_name"]) . "' AS field_name, '" . 
                db_escape($entry["repeat_survey_link"]) . "' AS new_value ";
                
                if( $key < count($this->updates) -1) {
                    //  Omit on last entry
                    $JOIN_VALUES .= "UNION ALL ";
                }                    
            }
    
            $JOIN_STATEMENT = "JOIN( ";
            $JOIN_STATEMENT .= $JOIN_VALUES;
            $JOIN_STATEMENT .= " ) ";
            $sql_update = "UPDATE redcap_data rd " .
                            $JOIN_STATEMENT .
                            "vals ON rd.project_id = vals.project_id " . 
                            "AND rd.event_id = vals.event_id AND rd.record = vals.record AND rd.field_name = vals.field_name " .
                            "SET value = new_value ";
            
            //  Execute the query                                                  
            $this->query( $sql_update, [] );
            //  add logs
        }
    }    
}