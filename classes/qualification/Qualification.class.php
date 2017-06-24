<?php
/**
 * Qualification
 *
 * This is the overall class that deals with Qualification
 * 
 * This stores the general information about the qualification
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

namespace GT;

class Qualification {
    
    public $hash;
    protected $id = false;
    protected $name;
    protected $structureID; // This is not in the table, it's stored when submitting form data for easier access to it
    protected $buildID;
    protected $build;
    protected $deleted = 0;
    
    protected $units = false;
    protected $courses = false;
    protected $groups = false;
    protected $assessments = false;
    
    protected $customFormElements; // These are the FormElement objects as loaded with a valid Qualification
    protected $errors = array();
    
    protected $featuresEnabled = array();
    protected $levelsEnabled = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        $GTEXE = \GT\Execution::getInstance();
        
        $this->hash = \gt_rand_str(5);
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_qualifications", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->name = $record->name;
                $this->buildID = $record->buildid;
                $this->deleted = $record->deleted;
                                
                // Load custom form elements
                if (!isset($GTEXE->QUAL_MIN_LOAD) || !$GTEXE->QUAL_MIN_LOAD){
                    $this->loadCustomFormElements();
                }
                
            }
            
        }
    }
    
    /**
     * Is it a valid qual from the DB?
     * @return type
     */
    public function isValid(){
        return ($this->id !== false);
    }
    
    public function isDeleted(){
        return ($this->deleted == 1);
    }
    
    public function getID(){
        return $this->id;
    }
    
    public function getName(){
        return \gt_html($this->name);
    }
    
    public function setID($id){
        $this->id = $id;
        return $this;
    }
    
    public function setName($name){
        $this->name = trim($name);
    }
    
    public function setBuildID($id){
        $this->buildID = $id;
        $this->build = null;
        return $this;
    }
    
    public function setDeleted($val){
        $this->delete = $val;
        return $this;
    }
    
    public function setStructureID($id){
        $this->structureID = $id;
        return $this;
    }
    
    /**
     * Get the display name of the qualification
     * @return type
     */
    public function getDisplayName(){
        
        if (isset($this->displayName)){
            return $this->displayName;
        }
        
        // CHeck if we have a custom display name first
        $QualStructure = new \GT\QualificationStructure($this->getStructureID());
        if ( ($format = $QualStructure->getCustomDisplayNameFormat()) ){
            
            $level = $this->getBuild()->getLevel();
            $subType = $this->getBuild()->getSubType();

            $name = $format;
            $name = str_replace("%sn%", $QualStructure->getName(), $name);
            $name = str_replace("%sdn%", $QualStructure->getDisplayName(), $name);
            $name = str_replace("%ln%", $level->getName(), $name);
            $name = str_replace("%lns%", $level->getShortName(), $name);
            $name = str_replace("%sbn%", $subType->getName(), $name);
            $name = str_replace("%sbns%", $subType->getShortName(), $name);
            $name = str_replace("%n%", $this->getName(), $name);
            
        } else {
        
            $name = "";
            $name .= $this->getStructureName() . " ";
            $name .= $this->getLevelName() . " ";
            $name .= $this->getSubTypeName() . " ";
            $name .= "- ";
            $name .= $this->getName();
        
        }
        
        $this->displayName = $name;
        return $this->displayName;
        
    }
    
    /**
     * Get the short display name of the qualification
     * @return type
     */
    public function getShortDisplayName(){
        
        if (isset($this->shortDisplayName)){
            return $this->shortDisplayName;
        }
        
        $name = "";
        $name .= $this->getStructureName() . " ";
        $name .= $this->getLevelShortName() . " ";
        $name .= $this->getSubTypeShortName() . " ";
        $name .= "- ";
        $name .= $this->getName();
        
        $this->shortDisplayName = $name;
        
        return $this->shortDisplayName;
        
    }
    
    public function getBuildID(){
        return $this->buildID;
    }
    
    /**
     * Get the Build object from the build id
     * @return type
     */
    public function getBuild(){
        
        if (!isset($this->build)){
                
            $this->build = false;
            $build = new \GT\QualificationBuild($this->buildID);
            $this->build = $build;
        
        }
        
        return $this->build;
        
    }
    
    /**
     * Get the default number of credits expected by the Build of this Qualification
     * @return boolean
     */
    public function getDefaultCredits(){
        
        $this->getBuild();
        if (!$this->build || !$this->build->isValid()) return false;
        
        return $this->build->getDefaultCredits();
        
    }
    
    /**
     * Count up the number of credits on the units on this qualification
     * @return type
     */
    public function countUnitCredits(){
        
        $total = 0;
        
        if (!$this->units){
            $this->loadUnits();
        }
        
        if ($this->units)
        {
            foreach($this->units as $unit)
            {
                $total += $unit->getCredits(); 
            }
        }
        
        return $total;
        
    }
    
    public function getDeleted(){
        return $this->deleted;
    } 
    
    public function getStructureID(){
                
        if (!is_null($this->structureID)){
            return $this->structureID;
        }
        
        $this->structureID = $this->getBuild()->getStructureID();
        return $this->structureID;
        
    }
    
    public function getStructure(){
        
        $structure = new \GT\QualificationStructure($this->getStructureID());
        return $structure; 
    }
    
    /**
     * Get the structure name
     * @return \GT\QualificationStructure
     */
    public function getStructureName(){
        
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        return ($structure->isValid()) ? $structure->getDisplayName() : false;
        
    }
    
    /**
     * Get the structure name
     * @return \GT\QualificationStructure
     */
    public function getStructureExactName(){
        
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        return ($structure->isValid()) ? $structure->getName() : false;
        
    }
        
    /**
     * Get the level name
     * @return type
     */
    public function getLevelName(){
        
        $level = $this->getBuild()->getLevel();
        return ($level) ? $level->getName() : false;
        
    }
    
    /**
     * Get the shortname of the level
     * @return type
     */
    public function getLevelShortName(){
        
        $level = $this->getBuild()->getLevel();
        return ($level) ? $level->getShortName() : false;
        
    }
    
    public function getLevelOrderNum(){
        
        $level = $this->getBuild()->getLevel();
        return ($level) ? $level->getOrderNumber() : false;
        
    }
    
    /**
     * Get the subtype name
     * @return type
     */
    public function getSubTypeName(){
        
        $subType = $this->getBuild()->getSubType();
        return ($subType) ? $subType->getName() : false;
        
    }
    
    /**
     * Get the subtype shortname
     * @return type
     */
    public function getSubTypeShortName(){
        
        $subType = $this->getBuild()->getSubType();
        return ($subType) ? $subType->getShortName() : false;
        
    }
    
    /**
     * Get just one unit
     * @global \GT\type $DB
     * @param type $unitID
     * @return \GT\Unit|boolean
     */
    public function getOneUnit($unitID){
        
        global $DB;
        
        // If it's already been retrieved, return that
        if (array_key_exists($unitID, $this->units)){
            return $this->units[$unitID];
        }
        
        // Otherwise get it out of the database and load it into the units array
        $qualUnit = $DB->get_record("bcgt_qual_units", array("qualid" => $this->id, "unitid" => $unitID));
        if ($qualUnit){
            $unit = new \GT\Unit($qualUnit->unitid);
            if ($unit->isValid())
            {
                $unit->setQualStructureID($this->getStructureID());
                $this->units[$unit->getID()] = $unit;
                return $unit;
            }
        }
        
        // Otherwise return false
        return false;
        
    }
    
    /**
     * Get a specific unit from the qualification
     * @param int $unitID
     * @return type
     */
    public function getUnit($unitID){
                
        $this->getUnits();
        return ($this->units && array_key_exists($unitID, $this->units)) ? $this->units[$unitID] : false;
        
    }
    
    /**
     * Return the units on this qualification. Loading them first if we've not tried yet.
     * @return type
     */
    public function getUnits(){
                        
        // If units is false, that means we've not tried to access them yet in this object
        if ($this->units === false){
            $this->loadUnits();
        }
        
        return $this->units;
        
    }
    
    /**
     * Count units on qual
     * @return type
     */
    public function countUnits(){
        
        $units = $this->getUnits();
        return count($units);
        
    }
    
    /**
     * Load the units on this qualification
     * @global \GT\type $DB
     */
    public function loadUnits(){
        
        global $DB;
        
        $GTEXE = \GT\Execution::getInstance();
                        
        $this->units = array();
        $qualUnits = $DB->get_records("bcgt_qual_units", array("qualid" => $this->id));
        if ($qualUnits)
        {
            foreach($qualUnits as $qualUnit)
            {
                $unit = new \GT\Unit($qualUnit->unitid);
                if ($unit->isValid() && !$unit->isDeleted())
                {
                    $unit->setQualStructureID($this->getStructureID());
                    $this->units[$unit->getID()] = $unit;
                }
            }
        }
        
        // Default sort
        if (!isset($GTEXE->UNIT_NO_SORT) || !$GTEXE->UNIT_NO_SORT){
            $this->sortUnits();
        }
        
    }
    
    protected function sortUnits(){
        
        $Sorter = new \GT\Sorter();
        $structure = $this->getStructure();
        
        $customOrder = $structure->getCustomOrder('units');
        if ($customOrder){
            $Sorter->sortUnitsCustom($this->units, $customOrder);
        } else {
            $Sorter->sortUnits($this->units);
        }
        
    }
    
    /**
     * Get a unique list of the unit award names for all units on this qual, used in reporting
     * @return type
     */
    public function getUnitAwards(){

        $return = array();
        $units = $this->getUnits();
        
        if ($units)
        {
            foreach ($units as $unit){
                $unitAwards = $unit->getPossibleAwards();
                foreach ($unitAwards as $award){
                    $return[] = $award->getName();
                }
            }
        }
        
        return array_unique($return);
        
    }
    
    /**
     * Get the users on this qualification, of specific role
     * @param $role STUDENT or STAFF
     * @param int $courseID If this is set, then the user must also be enrolled on this course in this role
     * @return type
     */
    public function getUsers($role, $courseID = false, $groupID = false, $page = false){
        
        // Reload them if we haven't loaded yet, or if we specify a page
        // If we are using a page number, they don't get stored in the array
        if (!isset($this->users[$role][$courseID][$groupID]) || $page > 0){
            return $this->loadUsers($role, $courseID, $groupID, $page);
        }
        
        return $this->users[$role][$courseID][$groupID];
        
    }
    
    /**
     * Count users of role on qual
     * @param type $role
     * @return type
     */
    public function countUsers($role){
        
        $users = $this->getUsers($role);
        return count($users);
        
    }
    
    /**
     * Load users on this qualification, of specific role
     * @global \GT\type $DB
     * @param type $role
     * @param int $courseID If this is set, then the user must also be enrolled on this course in this role
     */
    public function loadUsers($role, $courseID = false, $groupID = false, $page = false){
        
        global $DB, $GT;
        
        // Store in array so don't have to get this exact lot again in this process
        if (!$page){
            $this->users[$role][$courseID][$groupID] = array();
        }
        
        $return = array();
        $params = array();
                
        $sql = "SELECT DISTINCT u.id
                FROM {user} u
                INNER JOIN {bcgt_user_quals} uq ON uq.userid = u.id ";
        
                
        // Only apply course & group filters when we're getting students
        if ($role == 'STUDENT')
        {
        
            // Group ID
            if ($groupID > 0){
                
                $sql .= "
                            INNER JOIN
                            (
                                SELECT userid
                                FROM {groups_members}
                                WHERE groupid = ?
                            ) gm ON gm.userid = u.id
                        ";
                
                $params[] = $groupID;
                
            }
            
            // Course ID
            elseif ($courseID > 0){

                $shortnames = $GT->getStudentRoles();
                $in = \gt_create_sql_placeholders($shortnames);
                
                $sql .= "
                            INNER JOIN
                            (
                                SELECT ra.userid
                                FROM {role_assignments} ra
                                INNER JOIN {context} x ON x.id = ra.contextid
                                INNER JOIN {role} r ON r.id = ra.roleid
                                WHERE x.instanceid = ? AND r.shortname IN ({$in})
                            ) ra ON ra.userid = u.id 
                        ";

                $params[] = $courseID;
                $params = array_merge($params, $shortnames);
                
            }
        
        }
        
        $sql .= " WHERE uq.qualid = ? and uq.role = ? and u.deleted = 0
                  ORDER BY u.lastname, u.firstname, u.username";
        
        $params[] = $this->id;
        $params[] = $role;
        
        // Page - If we are looking at a class grid
        $limit = \GT\Setting::getSetting('class_grid_paging');
        if ($limit <= 0) $limit = false;
        
        if ($page && is_numeric($limit)){
            $start = ($page - 1) * $limit;
        } else {
            $limit = null;
            $start = null;
        }
                
        $records = $DB->get_records_sql($sql, $params, $start, $limit);
        
        if ($records)
        {
            foreach($records as $record)
            {
                $user = new \GT\User($record->id);
                $return[] = $user;
            }
        }
                        
        // Sort them
        $Sorter = new \GT\Sorter();
        $Sorter->sortUsers($return);
        
        
        // Store in array
        if (!$page){
            $this->users[$role][$courseID][$groupID] = $return;
        }
        
        return $return;
        
    }
    
    /**
     * Get any courses linked to this qualification.
     * Loading them if they haven't already been loaded
     * @return type
     */
    public function getCourses(){
        
        if ($this->courses === false){
            $this->loadCourses();
        }
        
        return $this->courses;
        
    }
    
    /**
     * Get a specific course attached to the qual
     * @param type $courseID
     * @return type
     */
    public function getCourse($courseID){
        
        $courses = $this->getCourses();
        return (array_key_exists($courseID, $courses)) ? $courses[$courseID] : false;
        
    }
    
    /**
     * Load courses that are linked to this qualification
     * @global \GT\type $DB
     * @return \GT\Course
     */
    public function loadCourses(){
        
        global $DB;
        
        $records = $DB->get_records("bcgt_course_quals", array("qualid" => $this->id));
        if ($records)
        {
            foreach($records as $record)
            {
                $course = new \GT\Course($record->courseid);
                if ($course->isValid())
                {
                    $this->courses[$course->id] = $course;
                }
            }
        }
                
    }
    
    
    /**
     * Get any groups linked to this qualification.
     * Loading them if they haven't already been loaded
     * emptyGroups variable is for hiding empty groups
     * @return type
     */
    public function getGroups($courseID){
        
        if (!$this->groups){
            $this->groups = array();
        }
        
        // Load the groups on this course
        if (!array_key_exists($courseID, $this->groups)){
            $this->groups[$courseID] = $this->loadGroups($courseID);
        }
                        
        return $this->groups[$courseID];
        
    }
    
    
    /**
     * Load courses that are linked to this qualification
     * @global \GT\type $DB
     * @return \GT\Course
     */
    public function loadGroups($courseID){
                
        if (!$this->groups){
            $this->groups = array();
        }
        
        $this->groups[$courseID] = array(
            'parent' => array(),
            'direct' => array(),
            'child' => array()
        );
        
        $course = $this->getCourse($courseID);
        if ($course)
        {
            
            // Direct
            $groups = groups_get_all_groups($course->id);
            foreach($groups as $group){
                $members = groups_get_members($group->id);
                $group->usercnt = count($members);
                if ($group->usercnt > 0){
                    $this->groups[$courseID]['direct'][$group->id] = $group;
                }
            }
            
            // Parent
            $parents = $course->getParentCourses();
            if ($parents){
                foreach($parents as $parent){
                    $this->groups[$courseID]['parent'][$parent->id] = array();
                    $groups = groups_get_all_groups($parent->id);
                    foreach($groups as $group){
                        $members = groups_get_members($group->id);
                        $group->usercnt = count($members);
                        if ($group->usercnt > 0){
                            $this->groups[$courseID]['parent'][$parent->id][$group->id] = $group;
                        }
                    }
                    
                    // Remove empty
                    if (!$this->groups[$courseID]['parent'][$parent->id]){
                        unset($this->groups[$courseID]['parent'][$parent->id]);
                    }
                    
                }
            }
            
            // Children
            $children = $course->getChildCourses();
            if ($children){
                foreach($children as $child){
                    $this->groups[$courseID]['child'][$child->id] = array();
                    $groups = groups_get_all_groups($child->id);
                    foreach($groups as $group){
                        $members = groups_get_members($group->id);
                        $group->usercnt = count($members);
                        if ($group->usercnt > 0){
                            $this->groups[$courseID]['child'][$child->id][$group->id] = $group;
                        }
                    }
                    
                    // Remove empty
                    if (!$this->groups[$courseID]['child'][$child->id]){
                        unset($this->groups[$courseID]['child'][$child->id]);
                    }
                    
                }
            }
            
        }
        
        return $this->groups[$courseID];
        
    }
    
    
    /**
     * Count the number of columns we need in the assessment student grid, so we can do the colspans correctly
     * @return int
     */
    public function countAssessmentGridColumns(){
        
        $cols = 0;
        
        $cols++; // Qual title
        
        // Target Grade
        if ($this->isFeatureEnabledByName("targetgrades"))
            $cols++;
        
        // Weighted target Grade
        if ($this->isFeatureEnabledByName("weightedtargetgrades"))
            $cols++;
        
        // CETA
        if ($this->isFeatureEnabledByName("cetagrades"))
            $cols++;
        
        // Qual Weighting (ALPS)
        //todo
        
        // Assessments
        $assessments = $this->getAssessments();
        if ($assessments)
            $cols += count($assessments);
        
        // If ceta grades enabled, add an extra column for each assessment
        if ($this->isFeatureEnabledByName("cetagrades"))
            $cols += count($assessments);
        
        return $cols;
        
    }
    
    /**
     * Get the (formal) assessments linked to the qualification
     * @return array
     */
    public function getAssessments(){
        
        if (!$this->assessments){
            $this->loadAssessments();
        }
        
        return $this->assessments;
        
    }
    
    /**
     * Get a specific assessment on the qual
     * @param type $id
     * @return type
     */
    public function getAssessment($id){
        
        $this->getAssessments();
        return (array_key_exists($id, $this->assessments)) ? $this->assessments[$id] : false;
        
    }
    
    /**
     * Load any Assessments linked to this qual
     * By this we mean the Formal Assessments, or whatever they have been called in the system
     * @global \GT\type $DB
     * @return \GT\Assessment
     */
    protected function loadAssessments(){
        
        global $DB;
        
        $this->assessments = array();
        $return = array();
        
        $records = $DB->get_records("bcgt_assessment_quals", array("qualid" => $this->id));
        if($records)
        {
            foreach($records as $record)
            {
                $assessment = new \GT\Assessment($record->assessmentid);
                if ($assessment->isValid())
                {
                    $assessment->setQualification($this);
                    
                    // If we are actually in the UserQualification when this is called and we have a student loaded
                    // load them into each assessment as well
                    if (isset($this->student) && $this->student){
                        $assessment->loadStudent($this->student->id);
                    }
                    
                    $return[$assessment->getID()] = $assessment;
                }
            }
        }
        
        // Sort them by date
        $Sorter = new \GT\Sorter();
        $Sorter->sortAssessmentsByDate($return);
                
        $this->assessments = $return;
        return $return;
        
    }
    
    
    
    
    /**
     * Get the assessment that is current, based on date (closest one not in the future)
     * e.g. If the date is Jan 10th and you have 3 assessments: Jan 1st, Jan 8th, Jan 11th
     * It will return the Jan 8th one, as that is the closest one that is not in the future
     * @param type $from This might be 'summary' if we are calling from summary, and therefore 
     *                   want to check to make sure the assessment is summary enabled
     * @param bool $cetaEnabledOnly Similarly, we might want only assessments which have CETA enabled
     * @param bool $mustHaveCetaGrade Only return ones where the student actually has a grade for it
     * @return boolean
     */
    public function getCurrentAssessment($from = false, $cetaEnabledOnly = false)
    {
                
        $assessments = $this->getAssessments();
        if (!$assessments){
            return false;
        }
        
        // Ensure they are sorted by date
        $Sorter = new \GT\Sorter();
        $Sorter->sortAssessmentsByDate($assessments);
        
        // Reverse the order, so they are in DESC date order
        $assessments = array_reverse($assessments, true);
          
        $now = time();
        
        foreach($assessments as $assessment)
        {
            
            // If we are loading from the summary, we want only assessments who are enabled in the summary
            if ($from == 'summary' && !$assessment->isSummaryEnabled())
            {
                continue;
            }
            
            // If we want ceta enabled only, byt it doesn't have it enabled, skip it
            if ($cetaEnabledOnly && !$assessment->isCetaEnabled())
            {
                continue;
            }
                        
            // If the assessment's date is before or equal to today, use this one
            if ($assessment->getDate() <= $now)
            {
                return $assessment;
            }
            
        }
        
        return false;
        
    }
    
    /**
     * Get the value added comparison between 2 awards
     * @param type $value
     * @param type $targetValue
     * @param type $type
     * @return boolean
     */
    public function getValueAddedComparison($value, $targetValue, $type, $defaultReturn = false)
    {
        
        if (!$value || !$targetValue || !$value->isValid() || !$targetValue->isValid() || ($type != 'grade' && $type != 'ceta')){
            return $defaultReturn;
        }
                
        // This is comparing the grade given to an assessment, with the target or weighted target
        // That means comparing the "points" of a CriteriaAward object, with the "rank" of a QualificationAward object
        if ($type == 'grade'){
            
            $points = $value->getPoints();
            $rank = $targetValue->getRank();
            return ($points - $rank);
            
        }
        
        // This is comparing the CETA given to an assessment, with the target/weighted target
        // This means comparing the "rank" of two QualificationAward objects
        elseif ($type == 'ceta'){
            
            $current = $value->getRank();
            $target = $targetValue->getRank();
            return ($current - $target);
            
        }

        return $defaultReturn;
        
    }
    
    
    
    
    /**
     * Set the units by ids
     * @param type $unitIDs
     */
    public function setUnitsByID($unitIDs){
        
        // Clear any loaded units
        $this->units = array();
        
        // Load these ones
        if ($unitIDs)
        {
            foreach($unitIDs as $unitID)
            {
                $unit = new \GT\Unit($unitID);
                if ($unit->isValid())
                {
                    $this->units[$unit->getID()] = $unit;
                }
            }
        }
        
    }
    
    /**
     * Add a unit to the Qual, to be saved
     * @param type $unit
     */
    public function addUnit($unit){
        $this->units[$unit->getID()] = $unit;
    }
    
    /**
     * Set the courses by the ids
     * @param type $courseIDs
     */
    public function setCoursesByID($courseIDs){
        
        // Clear any loaded units
        $this->courses = array();
        
        // Load these ones
        if ($courseIDs)
        {
            foreach($courseIDs as $courseID)
            {
                $course = new \GT\Course($courseID);
                if ($course->isValid())
                {
                    $this->courses[$course->id] = $course;
                }
            }
        }
        
    }
    
    /**
     * Take the values from the Qualification form and load them into the FormElement objects
     * @param type $array
     * @return \GT\Qualification
     */
    public function setCustomElementValues($array){
        
        // Reset saved values on all elements
        if ($this->customFormElements)
        {
            foreach($this->customFormElements as $element)
            {
                $element->setValue(null);
            }
        }
        
        // Now load in the ones we have submitted
        if ($array)
        {
            
            foreach($array as $name => $value)
            {
                
                $element = $this->getCustomFormElementByName($name);
                if ($element)
                {
                    $element->setValue($value);
                }
                
            }
            
        }
                
        return $this;
    }
        
    /**
     * Get the array of FormElement objects
     * @return type
     */
    public function getCustomFormElements(){
        return $this->customFormElements;
    }
    
    /**
     * Get the value of a specific FormElement loaded into the object
     * @param type $name
     * @return type
     */
    public function getCustomFormElementValue($name){
        
        $element = $this->getCustomFormElementByName($name);
        return ($element) ? $element->getValue() : false;
        
    }
    
    /**
     * Get a specific element from the loaded elements, by its name
     * @param type $name
     * @return boolean
     */
    public function getCustomFormElementByName($name){
        
        if ($this->customFormElements){
            
            foreach($this->customFormElements as $element){
                
                if ($element->getName() == $name){
                    return $element;
                }
                
            }
            
        }
        
        return false;
        
    }
    
    /**
     * Get any errors
     * @return type
     */
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Load the custom form elements into the qualification, with any values as well
     */
    public function loadCustomFormElements(){
                
        // Get the possible elements for the qualification form
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        $elements = $structure->getCustomFormElements('qualification');
        
        // Get the saved
        if ($this->isValid())
        {
            if ($elements)
            {
                foreach($elements as $element)
                {
                    $value = $this->getAttribute("custom_{$element->getID()}");
                    $element->setValue($value);
                }
            }
        }
        
        $this->customFormElements = $elements;
                        
    }
    
    /**
     * Gets a distinct list of all the possible criteria values for this qualification to put into the key
     * @return array
     */
    public function getAllPossibleValues(){
        
        $values = array();
        
        if ($this->getUnits()){
            
            foreach($this->getUnits() as $unit){
                               
                $criteria = $unit->loadCriteriaIntoFlatArray();
                                
                if ($criteria){
                    
                    foreach($criteria as $criterion){
                        
                        $possibleValues = $criterion->getPossibleValues();
                        if ($possibleValues){
                            
                            foreach($possibleValues as $value){
                                
                                $values[$value->getShortName().':'.$value->getName()] = $value;
                                
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
        }
        
        $Sorter = new \GT\Sorter();
        $Sorter->sortCriteriaValues($values);
        
        return $values;
        
    }
    
    /**
     * Get the short criteria headers, e.g. "P", "M", "D" for BTEC, used for reporting
     * @param type $names
     * @return type
     */
    public function getHeaderCriteriaNamesShort($names = false){
        
        if (!$names){
            $names = $this->getHeaderCriteriaNames();
        }
        
        $unique = array();
        foreach ($names as $name){
            $shortname = substr($name['name'], 0, 1);
            $unique[] = $shortname;
        }
        
        return array_unique($unique);
        
    }
    
    /**
     * Get a distinct list of all the top-level criteria names to display in the header, based on their
     * types and their sub levels
     * @return type
     */
    public function getHeaderCriteriaNames($forceReload = false){
                
        if (!$forceReload && isset($this->headerCriteriaNames)){
            return $this->headerCriteriaNames;
        }
        
        $names = array();
        
        if ($this->getUnits()){
            
            foreach($this->getUnits() as $unit){
                               
                // If this is being called from the UserQualification we want to only gets ones the student
                // is attached to
                if (isset($this->student) && $this->student && $this->student->isValid() && !$this->student->isOnQualUnit($this->id, $unit->getID(), "STUDENT")){
                    continue;
                }
                
                $criteria = $unit->getCriteria();
                                
                if ($criteria){
                    
                    foreach($criteria as $criterion){
                                                
                        // If this isn't already in the array, add it
                        if (!array_key_exists($criterion->getName(), $names)){
                            $names[$criterion->getName()] = array("name" => $criterion->getName(), "sub" => array());
                        } 
                        
                        // If this has child levels, we might want some of them in the header as well
                        if ($criterion->countChildLevels() > 0) {
                            
                            switch( get_class($criterion) )
                            {
                                
                                // Standard criterion
                                case 'GT\Criteria\StandardCriterion':
                                    
                                    // If only 1 level of sub criteria, add them in
                                    // Though if this top level criterion has the setting "force popup" don't show the sub criteria in the grid table
                                    if ($criterion->getAttribute('forcepopup') != 1)
                                    {
                                        foreach($criterion->getChildren() as $child){

                                            if (!in_array($child->getName(), $names[$criterion->getName()]['sub'])){
                                                $names[$criterion->getName()]['sub'][] = $child->getName();
                                            }

                                        }
                                    }
                                    
                                break;
                            
                            
                                // Detail criterion - Only top level go in the header
                                case 'GT\Criteria\DetailCriterion':
                                    
                                break;
                            
                            
                                // Numeric criterion - Only top level go in the header
                                case 'GT\Criteria\NumericCriterion':
                                    
                                break;
                            
                            
                                // Ranged criterion - Only top level go in the header
                                case 'GT\Criteria\RangedCriterion':
                                    
                                break;
                                
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
        }
                              
        $Sorter = new \GT\Sorter();
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        $customOrder = $structure->getCustomOrder('criteria');
        if ($customOrder){
            $Sorter->sortCriteriaCustom($names, $customOrder, false, true);
        } else {
            $Sorter->sortCriteria($names, false, true);
        }
        
        $this->headerCriteriaNames = $names;
        return $this->headerCriteriaNames;
        
    }
    
    /**
     * Parse a URL and replace things like %qid% and %sid% with relevant values
     * @param type $url
     */
    public function parseURL($url, $params = false){
        
        global $CFG;
                
        $url = str_replace( array('%www%', '%qid%', '%sid%', '%cid%', '%uid%'), 
                            array(
                                $CFG->wwwroot, 
                                $this->id, 
                                ($this->student) ? $this->student->id : 0, 
                                ($this->course) ? $this->course->id : 0,
                                (isset($params['uid'])) ? $params['uid'] : 0
                                ), 
                            $url );
        
        return $url;
        
    }
    
    /**
     * Check if feature is enabled on the structure
     * @param type $name
     * @return boolean
     */
    public function isFeatureEnabledByName($name){
        
        // if we already checked, return that result, don't load up the structure and waste processing time
        if (array_key_exists($name, $this->featuresEnabled)){
            return $this->featuresEnabled[$name];
        }
        
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        if (!$structure->isValid()) return false;
        
        $this->featuresEnabled[$name] = $structure->isFeatureEnabledByName($name);
        return $this->featuresEnabled[$name];
        
    }
    
    /**
     * Is a specific level enabled, e.g. Units, Standard Criteria, etc...
     * @param type $name
     * @return boolean
     */
    public function isLevelEnabled($name){
        
        // if we already checked, return that result, don't load up the structure and waste processing time
        if (array_key_exists($name, $this->levelsEnabled)){
            return $this->levelsEnabled[$name];
        }
        
        
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        if (!$structure->isValid()) return false;
        
        $this->levelsEnabled[$name] = $structure->isLevelEnabled($name);
        return $this->levelsEnabled[$name];
        
    }
    
    /**
     * Check to make sure there are no errors with the qualification before saving
     * @global \GT\type $DB
     * @return type
     */
    public function hasNoErrors(){
        
        global $DB;
        
        $Structure = new \GT\QualificationStructure( $this->structureID );
               
        // Check build is valid
        if (!$this->getBuild()->isValid()){
            $this->errors[] = get_string('errors:qual:build', 'block_gradetracker');
        }
        
        // Check name
        if (strlen($this->name) == 0){
            $this->errors[] = get_string('errors:qual:name', 'block_gradetracker');
        }
        
        // Check for duplicate name & build combination
        $check = $DB->get_records("bcgt_qualifications", array("name" => $this->name, "buildid" => $this->buildID, "deleted" => 0));
        if ($check)
        {
            foreach($check as $checkQual)
            {
                if ($checkQual->id <> $this->id)
                {
                    $this->errors[] = get_string('errors:qual:name:duplicate', 'block_gradetracker');
                }
            }
        }
        
        
        // Check custom elements
        $elements = $Structure->getCustomFormElements('qualification');
        if ($elements){
            
            foreach($elements as $element){
                
                // Is it required?
                if ($element->hasValidation("REQUIRED")){
                    
                    $value = $this->getCustomFormElementValue($element->getName());
                    if (strlen($value) == 0 || $value === false){
                        $this->errors[] = sprintf( get_string('errors:qual:custom', 'block_gradetracker'), $element->getName() );
                    }
                    
                }
                
            }
            
        }
        
        // Check units
        if ($this->units)
        {
            foreach($this->units as $unit)
            {
                
                // Unit should be of the same type (structure) as the qual, otherwise we could have CG units on BTEC quals, etc...
                if ($unit->getStructureID() <> $this->structureID)
                {
                    $this->errors[] = sprintf( get_string('errors:qual:unit', 'block_gradetracker'), $unit->getName(), $unit->getStructureName(), $this->getStructureName() );
                }
                
            }
        }
               
        
        return (!$this->errors);
        
    }
    
    /**Delete qual sets the deleted attribute to 1*/
    public function delete(){
        
        global $DB;
        
        $this->deleted = 1;
        
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = $this->deleted;
        
        return $DB->update_record("bcgt_qualifications", $obj);
    }
    
    /**Restore qual sets the deleted attribute to 0*/
    public function restore(){
        
        global $DB;
        
        $this->deleted = 0;
        
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = $this->deleted;
        
        return $DB->update_record("bcgt_qualifications", $obj);
    }
    
    public function copyQual(){
        
        global $DB;

        // create new qualificaiton object
        $newqual = new \GT\Qualification();
        $newqual->setBuildID($this->buildID);
        $newqual->setName($this->name." (copy)");
        
        $newqual->save();

        // get attribute information
        $atts = $this->getQualificationAttributes();
            foreach ($atts as $a){
                $newqual->updateAttribute($a->attribute, $a->value);
            }
        // redirect to edit page for newly copied qualification
        header('location:/blocks/gradetracker/config.php?view=quals&section=edit&id='.$newqual->getID());
            
    }
    
    
    public function save(){
        
        global $DB;
        
        $obj = new \stdClass();
        $obj->buildid = $this->buildID;
        $obj->name = $this->name;
        $obj->deleted = $this->deleted;
                
        if ($this->isValid()){
            $obj->id = $this->id;
            $result = $DB->update_record("bcgt_qualifications", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_qualifications", $obj);
            $result = $this->id;
        }
        
        if (!$result){
            $this->errors[] = get_string('errors:save', 'block_gradetracker');
            return false;
        }
                
        
        // Custom Form Elements
        
        // Clear any set previously
        $DB->delete_records("bcgt_qual_attributes", array("qualid" => $this->id, "userid" => null));
        
        // Save new ones
        if ($this->customFormElements){
            
            foreach($this->customFormElements as $element){
                
                $this->updateAttribute("custom_{$element->getID()}", $element->getValue());
                
            }
            
        }
        
        
        // Qual Units
        $this->saveQualUnits();
        
        // Qual Courses
        $this->saveQualCourses();
        
        
        return true;
        
        
    }
    
    /**
     * Save the qual unit links
     * @global \GT\type $DB
     */
    public function saveQualUnits()
    {
        
        global $DB;
        
        // Loop through loaded units and add them to qual_units table if not there
        if ($this->units)
        {
            foreach($this->units as $unit)
            {
                
                $check = $DB->get_records("bcgt_qual_units", array("qualid" => $this->id, "unitid" => $unit->getID()));
                if (!$check)
                {
                    $obj = new \stdClass();
                    $obj->qualid = $this->id;
                    $obj->unitid = $unit->getID();
                    $DB->insert_record("bcgt_qual_units", $obj);
                }
                
            }
        }
        
        // Then remove any links to ones we've removed
        $records = $DB->get_records("bcgt_qual_units", array("qualid" => $this->id));
        if ($records)
        {
            foreach($records as $record)
            {
                if (!array_key_exists($record->unitid, $this->units))
                {
                    $DB->delete_records("bcgt_qual_units", array("id" => $record->id));
                }
            }
        }
        
    }
    
    
    /**
     * Save the qual course links
     * @global \GT\type $DB
     */
    private function saveQualCourses()
    {
        
        global $DB;
        
        // Loop through loaded courses and add them to qual_units table if not there
        if ($this->courses)
        {
            foreach($this->courses as $course)
            {
                
                $Course = new \GT\Course($course->id);
                $Course->addCourseQual($this->id);
                
            }
        }
        
        // Then remove any links to ones we've removed
        $records = $DB->get_records("bcgt_course_quals", array("qualid" => $this->id));
        if ($records)
        {
            foreach($records as $record)
            {
                if (!array_key_exists($record->courseid, $this->courses))
                {
                    $DB->delete_records("bcgt_course_quals", array("id" => $record->id));
                }
            }
        }
        
        // Then remove any students who are still attached to this qual, but are not enrolled on
        // any of the courses the qual is attached to
        $this->removeStudentsNotOnQualCourse();
        
    }
    
    /**
     * Remove students who are attached to the qual, but not any of its courses
     * @global \GT\type $DB
     */
    public function removeStudentsNotOnQualCourse(){
        
        global $DB;
                
        // Find any students who are linked to this qualification but are not on any courses which are linked to this qualification
        $sql = "SELECT DISTINCT u.id
                FROM {bcgt_user_quals} uq
                INNER JOIN {user} u ON u.id = uq.userid
                WHERE uq.qualid = ? AND uq.role = 'STUDENT'
                AND u.id NOT IN 
                (

                    SELECT DISTINCT u.id
                    FROM {bcgt_user_quals} uq
                    INNER JOIN {user} u ON u.id = uq.userid
                    LEFT JOIN {bcgt_course_quals} cq ON cq.qualid = uq.qualid
                    LEFT JOIN {role_assignments} ra ON ra.userid = u.id
                    LEFT JOIN {role} r ON (r.id = ra.roleid)
                    LEFT JOIN {context} x ON (x.id = ra.contextid AND x.contextlevel = ? AND x.instanceid = cq.courseid)
                    LEFT JOIN {course} c ON c.id = x.instanceid
                    WHERE uq.qualid = ? AND c.id IS NOT NULL

                )";

        $records = $DB->get_records_sql($sql, array($this->id, CONTEXT_COURSE, $this->id));
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\User($record->id);
                $obj->removeFromQual($this->id);
            }
        }
        
    }
    
    /**
     * Get the weighting percentile for the current FA grade
     */
    public function getClassAssessmentWeightingPercentile(\GT\Assessment $assessment, array $students){
        
        if (!$assessment) return false;
        
        // Defaults
        $assessmentUCAS = 0;
        $targetUCAS = 0;
        $userCnt = 0;
        
        if ($students)
        {
            
            foreach($students as $student)
            {
                
                // Load the student into the assessment
                $assessment->loadStudent($student->id);
                
                // Get the grade for this assessment
                $assessmentAward = false;
                $assessmentGrade = $assessment->getUserGrade();
                if ($assessmentGrade){
                    $assessmentAward = \GT\QualificationAward::findAwardByName($this->getBuildID(), $assessmentGrade->getName());
                }
                
                // Get the user's target grade
                $targetGrade = $student->getUserGrade('target', array('qualID' => $this->id), false, true);
                
                // If they have both
                if ($targetGrade && $assessmentAward){
                    $targetUCAS += $targetGrade->getUcas();
                    $assessmentUCAS += $assessmentAward->getUcas();
                    $userCnt++;
                }
                
            }
            
        }
        
        // If they there is a total UCAS points for the assessment grade and target grades
        if ($assessmentUCAS && $targetUCAS && $userCnt)
        {
            
            
            // Get the multiplier for this build
            $multiplier = $this->getBuild()->getAttribute('build_default_weighting_multiplier');
            
            $QualWeighting = new \GT\QualificationWeighting();
            return $QualWeighting->calculateWeightingPercentile($targetUCAS, $assessmentUCAS, $multiplier, $this->id, $userCnt);
            
        }
        
        return false;
        
    }
    
    
    /**
     * Get the weighting percentile for the current FA grade
     */
    public function getClassAssessmentCetaWeightingPercentile(\GT\Assessment $assessment, array $students){
        
        if (!$assessment) return false;
        
        // Defaults
        $assessmentUCAS = 0;
        $targetUCAS = 0;
        $userCnt = 0;
        
        if ($students)
        {
            
            foreach($students as $student)
            {
                
                // Load the student into the assessment
                $assessment->loadStudent($student->id);
                
                // Get the grade for this assessment
                $cetaGrade = $assessment->getUserCeta();
                
                // Get the user's target grade
                $targetGrade = $student->getUserGrade('target', array('qualID' => $this->id), false, true);
                
                // If they have both
                if ($targetGrade && $cetaGrade && $cetaGrade->isValid()){
                    $targetUCAS += $targetGrade->getUcas();
                    $assessmentUCAS += $cetaGrade->getUcas();
                    $userCnt++;
                }
                
            }
            
        }
        
        // If they there is a total UCAS points for the assessment grade and target grades
        if ($assessmentUCAS && $targetUCAS && $userCnt)
        {
            
            // Get the multiplier for this build
            $multiplier = $this->getBuild()->getAttribute('build_default_weighting_multiplier');
            
            $QualWeighting = new \GT\QualificationWeighting();
            return $QualWeighting->calculateWeightingPercentile($targetUCAS, $assessmentUCAS, $multiplier, $this->id, $userCnt);
            
        }
        
        return false;
        
    }
    
    
    /**
     * Get a qualification attribute
     * @global \GT\type $DB
     * @param type $attribute
     * @param type $userID
     * @return type
     */
    public function getAttribute($attribute, $userID = null){
        
        global $DB;
        
        $check = $DB->get_record("bcgt_qual_attributes", array("qualid" => $this->id, "userid" => $userID, "attribute" => $attribute));
        return ($check) ? $check->value : false;
        
    }
    
    public function getQualificationAttributes(){
        global $DB;
        $check = $DB->get_records("bcgt_qual_attributes", array ("qualid" => $this->id));
        return $check;
    }
    

    
    /**
     * Update a qualification attribute
     * @global type $DB
     * @param type $setting
     * @param type $value
     * @param type $userID
     */
    public function updateAttribute($attribute, $value, $userID = null){
        
        global $DB;
        
        $check = $DB->get_record("bcgt_qual_attributes", array("qualid" => $this->id, "userid" => $userID, "attribute" => $attribute));
        if ($check)
        {
            $check->value = $value;
            $check->lastupdate = time();
            $DB->update_record("bcgt_qual_attributes", $check);
        }
        else
        {
            $ins = new \stdClass();
            $ins->qualid = $this->id;
            $ins->userid = $userID;
            $ins->attribute = $attribute;
            $ins->value = $value;
            $ins->lastupdate = time();
            $DB->insert_record("bcgt_qual_attributes", $ins);
        }
                
    }
    
    /**
     * Get the weighting coefficient of this qualification
     * @return boolean
     */
    public function getWeightingCoefficient(){
        
        // Which percentile are we using?
        $default = \GT\Setting::getSetting('default_weighting_percentile');
        if (!$default){
            \gt_debug("No default percentile defined in the configuration settings");
            return false;
        }
        
        // Get the coefficient for this qual and this percentile
        $coefficient = $this->getAttribute('coefficient_' . $default);
        if (!$coefficient){
            \gt_debug("No coefficient defined for this qualification, and percentile {$default}");
            return false;
        }
        
        return $coefficient;
        
    }
    
    /**
     * Get a system setting
     * @param type $setting
     * @param type $userID
     * @return type
     */
    public function getSystemSetting($setting, $userID = null){
        
        $GT = new \GT\GradeTracker();
        $settings = unserialize( $GT->cache->get('settings') );
        $buildID = $this->getBuildID();
        
        // Is this setting for this qual build cached?
        if (isset( $settings->$buildID->$setting )){
            return $settings->$buildID->$setting;
        }
        
        // Otherwise return normal setting
        return \GT\Setting::getSetting($setting, $userID);
    }
    
    /**
     * Get all the qualifications from the system
     * @return type
     */
    public static function getAllQualifications($enabled = false){
        
        $params = ($enabled) ? array('enabled' => true) : false;
        return self::search($params);
        
    }
    
    /**
     * Search for qualifications, based on params supplied
     * @global \GT\type $DB
     * @param type $params
     * @return type
     */
    public static function search($params){
        
        global $DB;
        
        $results = array();
        $sqlParams = array();
        
        $sql = "";
        $sql .= "SELECT q.id
                 FROM {bcgt_qualifications} q
                 INNER JOIN {bcgt_qual_builds} b ON b.id = q.buildid
                 INNER JOIN {bcgt_qual_structures} s ON s.id = b.structureid
                 INNER JOIN {bcgt_qual_levels} l ON l.id = b.levelid
                 INNER JOIN {bcgt_qual_subtypes} st ON st.id = b.subtypeid
                 WHERE q.deleted = ? 
                 AND b.deleted = 0 ";
        
        // Enabled?
        if (isset($params['enabled']) && $params['enabled']){
            $sql .= "AND s.enabled = 1 ";
        }
        
        //is this deleted?
        $sqlParams[] = (isset($params['deleted'])) ? $params['deleted'] : 0;
        
        // Structure
        if (isset($params['structureID']) && $params['structureID']){
            $sql .= "AND b.structureid = ? ";
            $sqlParams[] = $params['structureID'];
        }
        
        // Level
        if (isset($params['levelID']) && $params['levelID']){
            $sql .= "AND b.levelid = ? ";
            $sqlParams[] = $params['levelID'];
        }
        
        // Sub Type
        if (isset($params['subTypeID']) && $params['subTypeID']){
            $sql .= "AND b.subtypeid = ? ";
            $sqlParams[] = $params['subTypeID'];
        }
        
        // Name
        if (isset($params['name']) && strlen($params['name'])){
            
            // Exact
            if (preg_match("/[\"|'](.*?)[\"|']/U", $params['name'], $match)){
                $sql .= "AND q.name = ? ";
                $sqlParams[] = $match[1];
            }
            
            // Like
            else
            {
                $sql .= "AND q.name LIKE ? ";
                $sqlParams[] = '%'.$params['name'].'%';
            }
            
            
        }
        
        // Order
        $sql .= "ORDER BY s.name ASC, l.ordernum ASC, st.name ASC, q.name ASC";
        
        $records = $DB->get_records_sql($sql, $sqlParams);
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\Qualification($record->id);
                if ($obj->isValid())
                {
                    
                    $ok = true;
                    
                    // Are we also checking custom form elements?
                    if (isset($params['structureID']) && $params['structureID'] && isset($params['custom']) && $params['custom'])
                    {
                        foreach($params['custom'] as $id => $value)
                        {
                            if ($obj->getAttribute("custom_{$id}") != $value)
                            {
                                $ok = false;
                            }
                        }
                    }
                    
                    // Are we ok to include this result?
                    if ($ok)
                    {
                        $results[] = $obj;
                    }
                    
                }
            }
        }
        
        return $results;
        
    }
    
    /**
     * Get the display name of a qual based on its ID
     * @param type $qID
     * @return type
     */
    public static function getNameByID($qID, $name=false){
        
        $qual = new \GT\Qualification($qID);
        if ($name) return ($qual->isValid()) ? $qual->getName() : false;
        return ($qual->isValid()) ? $qual->getDisplayName() : false;
        
    }
    
    /**
     * Retrieve a qual by its type, level, subtype and name (used in importing data)
     * @global \GT\type $DB
     * @param type $type
     * @param type $level
     * @param type $subType
     * @param type $name
     * @return type
     */
    public static function retrieve($type, $level, $subType, $name){
        
        global $DB;
        
        $sql = "SELECT q.id
                FROM {bcgt_qualifications} q
                INNER JOIN {bcgt_qual_builds} b ON b.id = q.buildid
                INNER JOIN {bcgt_qual_structures} t ON t.id = b.structureid
                INNER JOIN {bcgt_qual_levels} l ON l.id = b.levelid
                INNER JOIN {bcgt_qual_subtypes} s ON s.id = b.subtypeid
                WHERE t.deleted = 0 AND q.deleted = 0
                AND t.name = ?
                AND l.name = ?
                AND s.name = ?
                AND q.name = ?";
        
        $record = $DB->get_record_sql($sql, array($type, $level, $subType, $name));
        return ($record) ? new \GT\Qualification($record->id) : false;
        
    }
    
    /**
     * Check if a qualification has any assessments, when we only have the qual id, not a qual 
     * @param type $qID
     * @return type
     */
    public static function hasAssessments($qID){
        
        $qual = new \GT\Qualification($qID);
        return $qual->getAssessments();
        
    }
    
    /**
     * Get a QualBuild from the qualification id
     * @global \GT\type $DB
     * @param type $qualID
     * @return boolean|\GT\QualificationBuild
     */
    public static function getBuildFromQualID($qualID){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_qualifications", array("id" => $qualID));
        if ($record)
        {
            return new \GT\QualificationBuild($record->buildid);
        }
        
        return false;
        
    }
    
    /**
     * Get a QualStructure from the qualification id
     * @global \GT\type $DB
     * @param type $qualID
     * @return boolean|\GT\QualificationStructure
     */
    public static function getStructureFromQualID($qualID){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_qualifications", array("id" => $qualID));
        if ($record)
        {
            $qualBuild = new \GT\QualificationBuild($record->buildid);
            if ($qualBuild->isValid())
            {
                return new \GT\QualificationStructure($qualBuild->getStructureID());
            }
        }
        
        return false;
        
    }
    
    /**
     * Count non-deleted qualifications in the system
     * @global \GT\type $DB
     * @return type
     */
    public static function countQuals(){
        
        global $DB;
        
        $count = $DB->count_records("bcgt_qualifications", array("deleted" => 0));
        return $count;
        
    }
    
}
