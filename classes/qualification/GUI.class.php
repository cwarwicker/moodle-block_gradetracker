<?php
/**
 * GT\Qualification\GUI
 *
 * This class handles all the qualification interfaces, such as creation forms, editing forms, adding/removing
 * forms, etc...
 * 
 * @copyright 2015 Bedford College
 * @package Bedford College Grade Tracker
 * @version 1.0
 * @author Conn Warwicker <cwarwicker@bedford.ac.uk> <conn@cmrwarwicker.com> <moodlesupport@bedford.ac.uk>
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

namespace GT\Qualification;

require_once 'Qualification.class.php';

class GUI extends \GT\Qualification {
    
    private $tpl;
    
    public function __construct($id = false) {
        parent::__construct($id);
    }
    
    public function loadTemplate(&$TPL){
        $this->tpl = $TPL;
    }
    
    /**
     * Save the new qualification/edit qualification form
     * @global type $MSGS
     */
    public function saveFormNewQualification()
    {
        
        global $MSGS;
        
        // Take the data from the form and load it into the qualification object
        $qualID = (isset($_POST['qual_id'])) ? $_POST['qual_id'] : false;
        $structureID = (isset($_POST['qual_type'])) ? $_POST['qual_type'] : false;
        $buildID = (isset($_POST['qual_build'])) ? $_POST['qual_build'] : false;
        $name = (isset($_POST['qual_name'])) ? $_POST['qual_name'] : '';
        $customElements = (isset($_POST['custom_elements'])) ? $_POST['custom_elements'] : false;
        $unitIDs = (isset($_POST['qual_units'])) ? $_POST['qual_units'] : false;
        $courseIDs = (isset($_POST['qual_courses'])) ? $_POST['qual_courses'] : false;
        
        $this->setStructureID($structureID);
        $this->setID($qualID);
        $this->setBuildID($buildID);
        $this->setName($name);
        $this->setUnitsByID($unitIDs);
        $this->setCoursesByID($courseIDs);
        $this->loadCustomFormElements();
        $this->setCustomElementValues($customElements);
        
        // Save the qualification
        if (isset($_POST['save_qualification']))
        {
            
            if ($this->hasNoErrors() && $this->save())
            {
                $MSGS['success'] = get_string('qualsaved', 'block_gradetracker');
            }
            else
            {
                $MSGS['errors'] = $this->getErrors();
            }
            
        }
                
    }
    
    /**
     * Set various variables required in the new/edit qualification form
     * @return string
     */
    public function displayFormNewQualification()
    {
        
        global $CFG;
        require_once $CFG->dirroot.'/lib/coursecatlib.php';

        // Load the builds based on the structure selected
        $structureID = $this->getStructureID();
        $Structure = new \GT\QualificationStructure($structureID);
        if ($Structure->isValid())
        {
            $builds = \GT\QualificationBuild::getAllBuilds($structureID);
            $this->tpl->set("builds", $builds);
        }
        else
        {
            $Structure = false;
        }
                
        $this->tpl->set("Structure", $Structure);
        $this->tpl->set("structures", \GT\QualificationStructure::getAllStructures());
        $this->tpl->set("allLevels", \GT\Level::getAllLevels());
        $this->tpl->set("allSubTypes", \GT\SubType::getAllSubTypes());
        $this->tpl->set("allCats", \coursecat::make_categories_list());
        
        // Load units and courses
        $this->getUnits();
        $this->getCourses();
        
        // Sort the units by level for the select menus
        $Sorter = new \GT\Sorter();
        $Sorter->sortUnitsByLevel($this->units);
        
    }
    
    /**
     * Display the search qualifications form
     */
    public function displayFormSearchQualifications()
    {
        
        $structureID = (isset($this->searchParams['structureID'])) ? $this->searchParams['structureID'] : false;
        $Structure = new \GT\QualificationStructure($structureID);
        if ($Structure->isValid())
        {
            $builds = \GT\QualificationBuild::getAllBuilds($structureID);
            $this->tpl->set("builds", $builds);
        }
        else
        {
            $Structure = false;
        }
        
        $this->tpl->set("Structure", $Structure);
                
        if (isset($this->searchParams)){
            $this->tpl->set("searchParams", $this->searchParams);
        }
        $this->tpl->set("structures", \GT\QualificationStructure::getAllStructures());
        $this->tpl->set("allLevels", \GT\Level::getAllLevels());
        $this->tpl->set("allSubTypes", \GT\SubType::getAllSubTypes());

    }
    
    /**
     * Search for qualifications
     * @global \GT\Qualification\type $MSGS
     */
    public function submitFormSearch($deleted = false)
    {
        
        global $MSGS;
        
        $structureID = (isset($_POST['qual_type'])) ? $_POST['qual_type'] : false;
        $levelID = (isset($_POST['qual_level'])) ? $_POST['qual_level'] : false;
        $subTypeID = (isset($_POST['qual_sub_type'])) ? $_POST['qual_sub_type'] : false;
        $name = (isset($_POST['qual_name'])) ? $_POST['qual_name'] : false;
        $custom = (isset($_POST['qual_custom_elements'])) ? array_filter($_POST['qual_custom_elements']) : false;
        
        
        $this->searchParams = array();
        $this->searchParams['structureID'] = $structureID;
        $this->searchParams['levelID'] = $levelID;
        $this->searchParams['subTypeID'] = $subTypeID;
        $this->searchParams['name'] = $name;
        $this->searchParams['custom'] = $custom;
        
        if ($deleted == true)
        {
            $this->searchParams['deleted'] = 1;
        }
        else
        {
            $this->searchParams['deleted'] = 0;
        }
        
        $results = self::search($this->searchParams);
        
        return $results;

        
    }
    
}
