<?php
/**
 * GT\Unit\GUI
 *
 * This class handles all the unit interfaces, such as creation forms, editing forms, etc...
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

namespace GT\Unit;

require_once 'Unit.class.php';

class GUI extends \GT\Unit {
    
    private $tpl;
    
    public function __construct($id = false) {
        parent::__construct($id);
    }
    
    public function loadTemplate(&$TPL){
        $this->tpl = $TPL;
    }
    
    /**
     * Do the relevant template bits required by the new unit form
     */
    public function displayFormNewUnit(){
        
        // Load the builds based on the structure selected
        $structureID = $this->getStructureID();
        $Structure = new \GT\QualificationStructure($structureID);
        if ($Structure->isValid())
        {
            
            // Levels
            $levels = \GT\Level::getAllStructureLevels($structureID);
            $this->tpl->set("levels", $levels);
            
            // Unit Grading Structures
            $this->tpl->set("unitGradingStructures", $Structure->getUnitGradingStructures(true));
            
            // Criteria Grading Structures
            $this->tpl->set("criteriaGradingStructures", $Structure->getCriteriaGradingStructures(true));
            
            // Criteria letters
            $letters = $Structure->getCriteriaLetters();
            $this->tpl->set("criteriaLetters", $letters);
            
            // Criteria
            if ($this->getCriteria())
            {
                $flatCriteria = $this->loadCriteriaIntoFlatArray();
                $flatCriteria = $this->sortCriteria($flatCriteria, false, true);
                $this->tpl->set("flatCriteria", $flatCriteria);
            }
            
            // Count up how many of each there currently are
            $lettersCount = array();
            $useCriteria = (isset($flatCriteria)) ? $flatCriteria : false;
            if ($letters)
            {
                foreach($letters as $letter)
                {
                    $lettersCount[$letter] = $this->countCriteriaByLetter($letter, $useCriteria);
                }
            }
            
            $this->tpl->set("lettersCount", $lettersCount);
                        
        }
        else
        {
            $Structure = false;
        }
        
        $this->tpl->set("Structure", $Structure);
        $this->tpl->set("structures", \GT\QualificationStructure::getAllStructures());        
        
    }
    
    /**
     * 
     * @global type $MSGS
     */
    public function saveFormNewUnit()
    {
        
        global $MSGS;
                        
        // Take the data from the form and load it into the qualification object
        $unitID = (isset($_POST['id'])) ? $_POST['id'] : false;
        $structureID = (isset($_POST['unit_type'])) ? $_POST['unit_type'] : false;
        $levelID = (isset($_POST['unit_level'])) ? $_POST['unit_level'] : false;
        $name = (isset($_POST['unit_name'])) ? $_POST['unit_name'] : '';
        $number = (isset($_POST['unit_number'])) ? $_POST['unit_number'] : null;
        $code = (isset($_POST['unit_code'])) ? $_POST['unit_code'] : '';
        $credits = (isset($_POST['unit_credits'])) ? $_POST['unit_credits'] : null;
        $desc = (isset($_POST['unit_desc'])) ? $_POST['unit_desc'] : '';
        $grading = (isset($_POST['unit_grading_structure'])) ? $_POST['unit_grading_structure'] : '';
        $criteria = (isset($_POST['unit_criteria'])) ? $_POST['unit_criteria'] : false;
        
        $customElements = (isset($_POST['custom_elements'])) ? $_POST['custom_elements'] : false;

        // If the ID isn't already set by the valid object
        if (!$this->isValid()){
            $this->setID($unitID);
            $this->setStructureID($structureID); 
        }
        
        $this->setLevelID($levelID);
        $this->setUnitNumber($number);
        $this->setName($name);
        $this->setCode($code);
        $this->setCredits($credits);
        $this->setDescription($desc);
        $this->setGradingStructureID($grading);
        $this->setCriteriaPostData($criteria);
        $this->loadCustomFormElements();
        $this->setCustomElementValues($customElements);
                                
        // Save the qualification
        if (isset($_POST['save_unit']))
        {
            
            if ($this->hasNoErrors() && $this->save())
            {
                $MSGS['success'] = get_string('unitsaved', 'block_gradetracker');
            }
            else
            {
                $MSGS['errors'] = $this->getErrors();
            }
            
        }
  
    }
    
    public function displayFormSearchUnits()
    {
        
        if (isset($this->searchParams)){
            $this->tpl->set("searchParams", $this->searchParams);
        }
        $this->tpl->set("structures", \GT\QualificationStructure::getAllStructures());
        $this->tpl->set("allLevels", \GT\Level::getAllLevels());

    }
    
    public function submitFormUnitSearch($deleted = false)
    {
        
        global $MSGS;
        
        $structureID = (isset($_POST['unit_type'])) ? $_POST['unit_type'] : false;
        $levelID = (isset($_POST['unit_level'])) ? $_POST['unit_level'] : false;
        $unitNumber = (isset($_POST['unitNumber'])) ? trim($_POST['unitNumber']) : false;
        $code = (isset($_POST['code'])) ? trim($_POST['code']) : false;
        $name = (isset($_POST['name'])) ? trim($_POST['name']) : false;

        $this->searchParams = array();
        $this->searchParams['structureID'] = $structureID;
        $this->searchParams['levelID'] = $levelID;
        $this->searchParams['unitNumber'] = $unitNumber;
        $this->searchParams['name'] = $name;
        $this->searchParams['code'] = $code;
        $this->searchParams['deleted'] = ($deleted) ? 1 : 0;
         
        $results = self::search($this->searchParams);
        
        return $results;
        
    }
    
}
