<?php

// Set the namespace defined in your config file
namespace STPH\repeatSurveyLink;



// Declare your module class, which must extend AbstractExternalModule 
class repeatSurveyLink extends \ExternalModules\AbstractExternalModule {

    private $moduleName = "Repeat Survey Link";  

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
    * Hooks Repeat Survey Link module to redcap_every_page_top
    *
    */
    public function redcap_every_page_top($project_id = null) {
        $this->renderModule();
    }

   /**
    * Renders the module
    *
    */
    private function renderModule() {
        
        

        print '<p class="repeat-survey-link">'.$this->helloFrom_repeatSurveyLink().'<p>';

    }

    public function helloFrom_repeatSurveyLink() {

                
        return 'Hello from '.$this->moduleName;
        

    }

    

    
}