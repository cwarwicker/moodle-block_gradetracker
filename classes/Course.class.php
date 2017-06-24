<?php
/**
 * Course
 *
 * This class deals with Moodle Courses, and any methods relating them to the Grade Tracker
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

class Course {
    
    const SEARCH_LIMIT = 100;
    
    private $childCourses = array();
    private $parentCourses = array();
    private $quals = array();
    
    private $studentsArray = array();
    private $staffArray = array();
    
    private $courseCategory = false;
    private $groups = false;
    
    public function __construct($id) {
                
        global $DB;
        
        $course = $DB->get_record("course", array("id" => $id));
        if ($course)
        {
            $props = get_object_vars($course);
            foreach($props as $prop => $val)
            {
                $this->$prop = $val;
            }
        }
        
        
    }
    
    public function isValid(){
        return (isset($this->id) && $this->id > 0);
    }
    
    /**
     * Get the name of the course, in specific format
     * @return boolean
     */
    public function getName(){
        
        global $GT;
        
        if ($this->isValid()){
            
            return str_replace( array(
                '%id%',
                '%fn%',
                '%sn%',
                '%idnum%'
            ), array(
                $this->id,
                $this->fullname,
                $this->shortname,
                $this->idnumber
            ), $GT->getCourseNameFormat() );
                        
        }
        
        return false;
        
    }
    
    /**
     * Get the course name with the category and any parent categories before it
     * @param type $name
     * @param type $category
     * @return type
     */
    public function getNameWithCategory($name = false, $category = false){
        
        if (!$category){
            $category = $this->getCategory();
        }
        
        $courseName = ($name) ? $name : $this->getName();
        $name = $category->name . ' / ' . $courseName;
        
        if ($category->hasParent()){
            $name = $this->getNameWithCategory($name, $category->getParent());
        }
        
        return $name;
        
    }
    
    /**
     * Get the course category for this course
     * @return type
     */
    public function getCategory(){
        
        if (!$this->courseCategory){
            $this->courseCategory = new \GT\CourseCategory($this->category);
        } 
        
        return $this->courseCategory;
        
    }
    
    /**
     * Get the gropus on this course and its parent/child courses
     * @return type
     */
    public function getGroups(){
        if (!$this->groups){
            $this->loadGroups();
        }
        return $this->groups;
    }
    
    /**
     * Load groups on this course and its parent and child courses
     * @return type
     */
    public function loadGroups(){
        
        $this->groups = array(
            'parent' => array(),
            'direct' => array(),
            'child' => array()
        );
        
        // Direct
        $groups = groups_get_all_groups($this->id);
        foreach($groups as $group){
            $members = groups_get_members($group->id);
            $group->usercnt = count($members);
            if ($group->usercnt > 0){
                $this->groups['direct'][$group->id] = $group;
            }
        }

        // Parent
        $parents = $this->getParentCourses();
        if ($parents){
            foreach($parents as $parent){
                $this->groups['parent'][$parent->id] = array();
                $groups = groups_get_all_groups($parent->id);
                foreach($groups as $group){
                    $members = groups_get_members($group->id);
                    $group->usercnt = count($members);
                    if ($group->usercnt > 0){
                        $this->groups['parent'][$parent->id][$group->id] = $group;
                    }
                }

                // Remove empty
                if (!$this->groups['parent'][$parent->id]){
                    unset($this->groups['parent'][$parent->id]);
                }

            }
        }

        // Children
        $children = $this->getChildCourses();
        if ($children){
            foreach($children as $child){
                $this->groups['child'][$child->id] = array();
                $groups = groups_get_all_groups($child->id);
                foreach($groups as $group){
                    $members = groups_get_members($group->id);
                    $group->usercnt = count($members);
                    if ($group->usercnt > 0){
                        $this->groups['child'][$child->id][$group->id] = $group;
                    }
                }

                // Remove empty
                if (!$this->groups['child'][$child->id]){
                    unset($this->groups['child'][$child->id]);
                }

            }
        }
            
                    
        return $this->groups;
                
    }
 
    /**
     * Check if a qualification is attached to this course
     * @param type $qualID
     * @return type
     */
    public function isQualificationOnCourse($qualID){
        
        $this->getCourseQualifications(false, true);
        return (array_key_exists($qualID, $this->quals));
        
    }
    
    /**
     * Get the qualifications attached to this course
     * @return type
     */
    public function getCourseQualifications($children = false, $forceReload = false){
        
        if (!$this->quals || $forceReload){
            $this->loadCourseQualifications($children);
        }
        
        return $this->quals;
        
    }
    
    /**
     * Load qualifications on this course from the DB
     * @global \GT\type $DB
     * @param bool $children DO we want to get them off any child courses as well?
     */
    private function loadCourseQualifications($children = false){
        
        global $DB;
        
        $this->quals = array();
        
        $records = $DB->get_records("bcgt_course_quals", array("courseid" => $this->id));
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\Qualification\UserQualification($record->qualid);
                $structure = $obj->getStructure();
                if ($obj->isValid() && !$obj->isDeleted() && $structure->isEnabled())
                {
                    $this->quals[$obj->getID()] = $obj;
                }
            }
        }
        
        // Children
        if ($children)
        {
            if ($this->getChildCourses())
            {
                foreach($this->getChildCourses() as $child)
                {
                    
                    $records = $DB->get_records("bcgt_course_quals", array("courseid" => $child->id));
                    if ($records)
                    {
                        foreach($records as $record)
                        {
                            $obj = new \GT\Qualification\UserQualification($record->qualid);
                            if ($obj->isValid() && !$obj->isDeleted())
                            {
                                $this->quals[$obj->getID()] = $obj;
                            }
                        }
                    }
                    
                }
            }
        }
        
        // Order them
        $Sorter = new \GT\Sorter();
        $Sorter->sortQualifications($this->quals);
        
    }
    
    /**
     * Count how many qualifications are linked to the course
     * @return type
     */
    public function countCourseQualifications($children = false){
        
        $quals = $this->getCourseQualifications($children);
        return count($quals);
        
    }

    
    /**
     * Get a parent -> child relationship of courses in both directions around this course
     * @return type
     */
    public function getRelationshipHierarchy(){
        
        $parents = $this->getParentCourses();        
        $children = $this->getChildCourses();
 
        $this->hierarchyLevel = 0;
        
        $results = array();
        $results = array_merge($results, $parents);
        $results[$this->id] = $this;
        $results = array_merge($results, $children);
        
        usort($results, function ($a, $b){
        
            if ($a->hierarchyLevel == $b->hierarchyLevel) {
                return 0;
            }
            return ($a->hierarchyLevel > $b->hierarchyLevel) ? -1 : 1;
        });
//        uasort($results, function($a, $b){
//            return ($a->hierarchyLevel < $b->hierarchyLevel);
//        });
        
        return $results;
        
    }
    
    /**
     * Get the students on this course, on any role as defined in our student role setting
     * @global \GT\type $DB
     * @global type $GT
     * @return \GT\User
     */
    public function getStudents($direct = false, $reload = false){
        
        global $DB, $GT;
        
        if ($reload){
            $this->studentsArray = array();
        } elseif ($this->studentsArray){
            return $this->studentsArray;
        }
        
        $return = array();
        
        $roles = $GT->getStudentRoles();
        if (!$roles){
            \gt_debug("Tried to get students on course ({$this->id}), but no student roles have been defined in the settings");
            return false;
        }
        
        // If we want direct enrolments, we don't want any students attached by course meta link
        $and = '';
        if ($direct)
        {
            $and = " AND ra.component = '' ";
        }
                
        $in = \gt_create_sql_placeholders($roles);
        
        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                INNER JOIN {role_assignments} ra ON ra.userid = u.id
                INNER JOIN {context} x ON x.id = ra.contextid
                INNER JOIN {role} r ON r.id = ra.roleid
                WHERE r.shortname IN ({$in}) AND x.contextlevel = ? AND x.instanceid = ? {$and}
                ORDER BY u.lastname, u.firstname, u.username";
        
        $params = $roles;
        $params[] = CONTEXT_COURSE;
        $params[] = $this->id;
        
        $records = $DB->get_records_sql($sql, $params);
        
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\User($record->id);
                if ($obj->isValid())
                {
                    $return[$obj->id] = $obj;
                }
            }
        }
         
        $this->studentsArray = $return;
        return $this->studentsArray;        
        
    }
    
    
    /** Get the students on this course, on any role as defined in our student role setting
     * @global \GT\type $DB
     * @global type $GT
     * @return \GT\User
     */
    public function getStaff($reload = false){
        
        global $DB, $GT;
        
        if ($reload){
            $this->staffArray = array();
        } elseif ($this->staffArray){
            return $this->staffArray;
        }
        
        $return = array();
        
        $roles = $GT->getStaffRoles();
        if (!$roles){
            \gt_debug("Tried to get staff on course ({$this->id}), but no student roles have been defined in the settings");
            return false;
        }
        
        
        $in = \gt_create_sql_placeholders($roles);
        
        // Get staff from this course
        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                INNER JOIN {role_assignments} ra ON ra.userid = u.id
                INNER JOIN {context} x ON x.id = ra.contextid
                INNER JOIN {role} r ON r.id = ra.roleid
                WHERE r.shortname IN ({$in}) AND x.contextlevel = ? AND x.instanceid = ?
                ORDER BY u.lastname, u.firstname, u.username";
        
        $params = $roles;
        $params[] = CONTEXT_COURSE;
        $params[] = $this->id;
        
        $records = $DB->get_records_sql($sql, $params);
        
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\User($record->id);
                if ($obj->isValid())
                {
                    $return[$obj->id] = $obj;
                }
            }
        }
        
        // Then from any parent courses
        $parents = $this->getParentCourses();
        if ($parents)
        {
            foreach($parents as $parent)
            {
                
                $parentStaff = $parent->getStaff();
                $return = $return + $parentStaff;
                
            }
        }
                
        $this->staffArray = $return;
        return $this->staffArray;        
        
    }
    
    /**
     * Does this course have a specific child course?
     * @param type $cID
     * @return type
     */
    public function hasChild($cID){
        
        $children = $this->getChildCourses();
        return (array_key_exists($cID, $children));        
        
    }
    
    /**
     * Get the child courses of this course
     * @return type
     */
    public function getChildCourses($recursion = true){
        
       $false = false; 
        
       if (!$this->childCourses){
           $this->loadChildCourses($false, $false, $recursion);
       }
              
       return $this->childCourses;
        
    }
    
    /**
     * Get the parent courses of this course
     */
    public function getParentCourses(){
       
        if (!$this->parentCourses){
            $this->loadParentCourses();
        }
                
        return $this->parentCourses;
        
    }
    
    
    
    /**
     * Load the child courses of this course and any of its children, recursively
     * @global \GT\type $DB
     * @param type $courseID
     * @param type $courses
     * @param bool $recursion Use recursion to get lower levels?
     * @return type
     */
    private function loadChildCourses($courseID = false, &$courses = false, $recursion = true, $cnt = -1){
        
        global $DB;
        
        if ($courseID){
            
            $childCourses = $DB->get_records_sql("SELECT c.id
                                                  FROM {course} c
                                                  INNER JOIN {enrol} e ON e.customint1 = c.id
                                                  WHERE e.enrol = 'meta' AND e.status = 0 AND e.courseid = ?
                                                  ORDER BY fullname asc", array($courseID));
            
            if ($childCourses)
            {
                foreach($childCourses as $child)
                {
                    $obj = new \GT\Course($child->id);
                    if ($obj->isValid())
                    {
                                                
                        // Add to array
                        $obj->hierarchyLevel = --$cnt;
                        $courses[$obj->id] = $obj;
                        
                        // Then does this have any children of its own?
                        $this->loadChildCourses($child->id, $courses, true, $obj->hierarchyLevel);
                                                
                    }
                }
            }
            
            return;
            
        } else {
            
            $courses = array();
            
            $childCourses = $DB->get_records_sql("SELECT c.id
                                                  FROM {course} c
                                                  INNER JOIN {enrol} e ON e.customint1 = c.id
                                                  WHERE e.enrol = 'meta' AND e.status = 0 AND e.courseid = ?
                                                  ORDER BY fullname asc", array($this->id));
            
            if ($childCourses)
            {
                foreach($childCourses as $child)
                {
                    $obj = new \GT\Course($child->id);
                    if ($obj->isValid())
                    {
                        
                        // Add to array
                        $obj->hierarchyLevel = $cnt;
                        $courses[$obj->id] = $obj;
                        
                        // Then does this have any children of its own?
                        if ($recursion){
                            $this->loadChildCourses($child->id, $courses, $recursion, $cnt);
                        }
                        
                    }
                }
            }
            
        }
        
        $this->childCourses = $courses;
        return $this->childCourses;
        
    }
    
    
    /**
     * Load the parent courses of this course and any of its parents, recursively
     * @global \GT\type $DB
     * @param type $courseID
     * @param type $courses
     * @return type
     */
    private function loadParentCourses($courseID = false, &$courses = false, $cnt = 1){
        
        global $DB;
        
        if ($courseID){
                        
            $parentCourses = $DB->get_records_sql("SELECT c.id
                                                  FROM {course} c
                                                  INNER JOIN {enrol} e ON e.courseid = c.id
                                                  WHERE e.enrol = 'meta' AND e.status = 0 AND e.customint1 = ?
                                                  ORDER BY fullname asc", array($courseID));
            
            if ($parentCourses)
            {
                foreach($parentCourses as $parent)
                {
                    $obj = new \GT\Course($parent->id);
                    if ($obj->isValid())
                    {
                        
                        // Add to array
                        $obj->hierarchyLevel = ++$cnt;
                        $courses[$obj->id] = $obj;
                        
                        // Then does this have any children of its own?
                        $this->loadParentCourses($parent->id, $courses, $obj->hierarchyLevel);
                        
                    }
                }
            }
            
            return;
            
        } else {
            
            $courses = array();
            
            $parentCourses = $DB->get_records_sql("SELECT c.id
                                                  FROM {course} c
                                                  INNER JOIN {enrol} e ON e.courseid = c.id
                                                  WHERE e.enrol = 'meta' AND e.status = 0 AND e.customint1 = ?
                                                  ORDER BY fullname asc", array($this->id));
                        
            if ($parentCourses)
            {
                foreach($parentCourses as $parent)
                {
                    $obj = new \GT\Course($parent->id);
                    if ($obj->isValid())
                    {
                        
                        // Add to array
                        $obj->hierarchyLevel = $cnt;
                        $courses[$obj->id] = $obj;
                        
                        // Then does this have any children of its own?
                        $this->loadParentCourses($parent->id, $courses, $cnt);
                        
                    }
                }
            }
            
        }
        
        $this->parentCourses = $courses;
        return $this->parentCourses;
        
    }
    
    /**
     * Save the user unit form
     * @global \GT\type $DB
     * @global \GT\type $MSGS
     */
    public function saveFormUserUnits(){
        
        global $DB, $MSGS;
        
        // Students
        $userQualUnits = (isset($_POST['user_qual_units'])) ? $_POST['user_qual_units'] : false;
        $qualUsers = array();
                
        // If we ticked any
        if ($userQualUnits)
        {
            foreach($userQualUnits as $qualID => $userUnits)
            {
                if ($userUnits)
                {
                    foreach($userUnits as $unitID => $users)
                    {
                        
                        if (!isset($qualUsers[$qualID])){
                            $qualUsers[$qualID] = array();
                        }
                        
                        $qualUsers[$qualID][$unitID] = $users;
                        
                        if ($users)
                        {
                            foreach($users as $userID)
                            {
                                $user = new \GT\User($userID);
                                if ($user->isValid())
                                {
                                    $user->addToQualUnit($qualID, $unitID, "STUDENT");
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Remove ones we didn't tick
        $this->removeOldUserQualUnits($qualUsers, "STUDENT");
        
        
        
        // Staff
        $userQualUnits = (isset($_POST['staff_qual_units'])) ? $_POST['staff_qual_units'] : false;
        $qualUsers = array();
        
        // If we ticked any
        if ($userQualUnits)
        {
            foreach($userQualUnits as $qualID => $userUnits)
            {
                if ($userUnits)
                {
                    foreach($userUnits as $unitID => $users)
                    {
                        
                        if (!isset($qualUsers[$qualID])){
                            $qualUsers[$qualID] = array();
                        }
                        
                        $qualUsers[$qualID][$unitID] = $users;
                        
                        if ($users)
                        {
                            foreach($users as $userID)
                            {
                                $user = new \GT\User($userID);
                                if ($user->isValid())
                                {
                                    $user->addToQualUnit($qualID, $unitID, "STAFF");
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Remove ones we didn't tick
        $this->removeOldUserQualUnits($qualUsers, "STAFF");
        
        $MSGS['success'] = get_string('userunitssaved', 'block_gradetracker');
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_COURSE_USER_UNITS;
        $Log->afterjson = $_POST;
        $Log->addAttribute(\GT\Log::GT_LOG_ATT_COURSEID, $this->id);
        $Log->save();
        // ------------ Logging Info
        
    }
    
    /**
     * Remove any user qualification links we didn't tick this time
     * @global \GT\type $DB
     * @param type $users
     * @param type $role
     */
    private function removeOldUserQualUnits($users, $role){
        
        global $DB;
                        
        if ($this->getCourseQualifications(true, true))
        {
                
            foreach($this->getCourseQualifications(true) as $qual)
            {
                
                $qualID = $qual->getID();
                                
                if ($qual->getUnits())
                {
                    
                    foreach($qual->getUnits() as $unit)
                    {
                        
                        $unitID = $unit->getID();
                        $qualUnitUsers = (isset($users[$qualID][$unitID])) ? $users[$qualID][$unitID] : array();

                        $currentIDs = array();

                        if ($role == "STUDENT"){
                            $people = $this->getStudents();
                        } elseif ($role == "STAFF"){
                            $people = $this->getStaff();
                        }
                        
                        // If there are any people on the course, get their IDs if they have a record for this qual_unit in the DB
                        if ($people)
                        {
                            foreach($people as $person)
                            {
                                $current = $DB->get_record("bcgt_user_qual_units", array("qualid" => $qualID, "unitid" => $unitID, "userid" => $person->id, "role" => $role));
                                if ($current)
                                {
                                    $currentIDs[] = $person->id;
                                }
                            }
                        }
                                                
                        $removed = array_diff($currentIDs, $qualUnitUsers);
                        
                        // If any have been removed, delete the user_qual_unit record
                        if ($removed)
                        {
                            foreach($removed as $userID)
                            {
                                $DB->delete_records("bcgt_user_qual_units", array("qualid" => $qualID, "unitid" => $unitID, "userid" => $userID, "role" => $role));
                            }
                        }
                                                
                    }
                    
                }
                
            }
        
        }
                                
    }
    
    
    
    
    /**
     * Save the user qualification form
     * @global \GT\type $DB
     * @global \GT\type $MSGS
     */
    public function saveFormUserQuals(){
        
        global $DB, $MSGS;
        
        // Student Quals      
        $userQuals = (isset($_POST['user_quals'])) ? $_POST['user_quals'] : false;
        $qualUsers = array();
                        
        // If we ticked any, loop through them and link them up
        if ($userQuals)
        {
            
            foreach($userQuals as $qualID => $users)
            {
                
                if ($users)
                {
                    
                    $qualUsers[$qualID] = $users;
                    
                    foreach($users as $userID)
                    {
                        
                        $user = new \GT\User($userID);
                        if ($user->isValid())
                        {
                            $user->addToQual($qualID, "STUDENT");
                        }
                        
                    }
                    
                }
                
            }
            
        }
        
        $this->removeOldUserQuals($qualUsers, "STUDENT");
        
        
        
        // Staff Quals
        $staffQuals = (isset($_POST['staff_quals'])) ? $_POST['staff_quals'] : false;
                
        $qualUsers = array();

        // If we ticked any, loop through them and link them up
        if ($staffQuals)
        {
            
            foreach($staffQuals as $qualID => $users)
            {
                
                if ($users)
                {
                    
                    $qualUsers[$qualID] = $users;
                    
                    foreach($users as $userID)
                    {
                        
                        $user = new \GT\User($userID);
                        if ($user->isValid())
                        {
                            $user->addToQual($qualID, "STAFF");
                        }
                        
                    }
                    
                }
                
            }
            
        }
        
        $this->removeOldUserQuals($qualUsers, "STAFF");
        
        $MSGS['success'] = get_string('userqualssaved', 'block_gradetracker');
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_COURSE_USER_QUALS;
        $Log->afterjson = $_POST;
        $Log->addAttribute(\GT\Log::GT_LOG_ATT_COURSEID, $this->id);
        $Log->save();
        // ------------ Logging Info
        
    }
    
    /**
     * Remove any user qualification links we didn't tick this time
     * @global \GT\type $DB
     * @param type $users
     */
    private function removeOldUserQuals($users, $role){
        
        global $DB;
        
        // Doing it for this course and its child courses
        $array = array($this);
        $children = $this->getChildCourses();
        if ($children){
            foreach($children as $child){
                $array[] = $child;
            }
        }
        
        foreach($array as $course)
        {
        
            $quals = $course->getCourseQualifications();
            if ($quals)
            {

                foreach($quals as $qual)
                {

                    $qualID = $qual->getID();
                    $qualUsers = (isset($users[$qualID])) ? $users[$qualID] : array();

                    $currentIDs = array();

                    if ($role == "STUDENT"){
                        $people = $this->getStudents();
                    } elseif ($role == "STAFF"){
                        $people = $this->getStaff();
                    }

                    if ($people)
                    {

                        foreach($people as $person)
                        {

                            $current = $DB->get_record("bcgt_user_quals", array("qualid" => $qualID, "userid" => $person->id, "role" => $role));
                            if ($current)
                            {
                                $currentIDs[] = $person->id;
                            }

                        }

                    }

                    $removed = array_diff($currentIDs, $qualUsers);

                    if ($removed)
                    {
                        foreach($removed as $userID)
                        {
                            $user = new \GT\User($userID);
                            $user->removeFromQual($qualID, $role);
                        }
                    }

                }

            }
        
        }
                
    }
    
    /**
     * Add a link between this course and a given qualID
     * @global \GT\type $DB
     * @param type $qualID
     * @return boolean
     */
    public function addCourseQual($qualID){
        
        global $DB;
        
        // Insert new record if it doesn't exist already
        $check = $DB->get_record("bcgt_course_quals", array("courseid" => $this->id, "qualid" => $qualID));
        if (!$check)
        {
            $ins = new \stdClass();
            $ins->courseid = $this->id;
            $ins->qualid = $qualID;
            $course_qual = $DB->insert_record("bcgt_course_quals", $ins);
            
            $GT = new \GT\GradeTracker();
        
            if ($GT->getSetting('use_auto_enrol_quals') == 1){
                

                $students = $this->getStudents();
                
                foreach ($students as $student){
                    $GT_User = new \GT\User($student->id);
                    $GT_User->addToQual($qualID, "STUDENT");
                }
                
                $staffs = $this->getStaff();
                
                foreach ($staffs as $staff){
                    $GT_User = new \GT\User($staff->id);
                    $GT_User->addToQual($qualID, "STAFF");
                }
                
            }

            if ($GT->getSetting('use_auto_enrol_units') == 1){

                $Qual = new \GT\Qualification($qualID);
                $units = $Qual->getUnits();
                
                $students = $this->getStudents();
                
                foreach ($students as $student){
                    $GT_User = new \GT\User($student->id);
                    
                    foreach ($units as $unit){
                        $GT_User->addToQualUnit($qualID, $unit->getID(), "STUDENT");
                    }
                }
                
                $staffs = $this->getStaff();
                
                foreach ($staffs as $staff){
                    $GT_User = new \GT\User($staff->id);
                    
                    foreach ($units as $unit){
                        $GT_User->addToQualUnit($qualID, $unit->getID(), "STAFF");
                    }
                }

            }
            
            return $course_qual;
        }
        
        return false;
        
    }
    
    /**
     * Save the course quals form
     * @global \GT\type $DB
     * @global type $MSGS
     */
    public function saveFormCourseQuals(){
        
        global $DB, $MSGS;
        
        $qualIDs = (isset($_POST['quals'])) ? $_POST['quals'] : array();
        
        // Add new links
        if ($qualIDs)
        {
            foreach($qualIDs as $qualID)
            {
                $this->addCourseQual($qualID);
            }
        }
        
        // Remove ones we didn't submit this time
        $this->removeOldCourseQuals($qualIDs);
        
        $MSGS['success'] = get_string('coursequalssaved', 'block_gradetracker');
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_COURSE_QUALS;
        $Log->afterjson = $_POST;
        $Log->addAttribute(\GT\Log::GT_LOG_ATT_COURSEID, $this->id);
        $Log->save();
        // ------------ Logging Info
        
    }
    
    /**
     * Remove links to course quals that we didn't submit this time
     * Also if student is not linked to the qual on any other course, remove their qual link as well
     * @global \GT\type $DB
     */
    private function removeOldCourseQuals($qualIDs){
        
        global $DB;
        
        $courseQuals = $this->getCourseQualifications();
        
        // Loop through course quals
        if ($courseQuals)
        {
            
            foreach($courseQuals as $courseQual)
            {
                
                // If it doesn't exist in the submitted array, remove it
                if (!in_array($courseQual->getID(), $qualIDs))
                {
                    
                    // Firstly remove the course qual link
                    $DB->delete_records("bcgt_course_quals", array("courseid" => $this->id, "qualid" => $courseQual->getID()));
                    
                    // Then remove students on qual, but not on any of its courses
                    $courseQual->removeStudentsNotOnQualCourse();                    
                    
                }
                
            }
            
        }
        
    }
    
    /**
     * Get an array of activities on the course, which can be linked up to the gradetracker (which have been
     * setup in the mod linking)
     * @global \GT\type $DB
     */
    public function getSupportedActivities(){
        
        global $DB;
        
        $return = array();
        $records = $DB->get_records_sql("SELECT cm.id, bm.id as 'linkid', cm.instance
                                         FROM {course_modules} cm
                                         INNER JOIN {bcgt_mods} bm ON bm.modid = cm.module
                                         WHERE cm.course = ? AND bm.deleted = 0", array($this->id));
        
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\ModuleLink($record->linkid);
                $obj->setRecordID($record->instance);
                $obj->setCourseModID($record->id);
                $return[] = $obj;
            }
        }
                
        
        // Order them by the name of the instance
        usort($return, function($a, $b){
            return strnatcasecmp($a->getRecordName(), $b->getRecordName());
        });
        
        return $return;
        
    }
    
    /**
     * Get specific activity on this course
     * @global \GT\type $DB
     * @param type $cmID
     * @return boolean|\GT\ModuleLink
     */
    public function getActivity($cmID){
        
        global $DB;
        
        $record = $DB->get_record_sql("SELECT cm.id, bm.id as 'linkid', cm.instance 
                                       FROM {course_modules} cm
                                       INNER JOIN {bcgt_mods} bm ON bm.modid = cm.module
                                       WHERE cm.id = ? AND cm.course = ?", array($cmID, $this->id));
        
        if ($record)
        {
            
            $obj = new \GT\ModuleLink($record->linkid);
            $obj->setRecordID($record->instance);
            $obj->setCourseModID($record->id);
            return $obj;
            
        }
        
        return false;
        
    }
    
    /**
     * Get a course module, based on the module type and instance id
     * @global \GT\type $DB
     * @param type $moduleID
     * @param type $instanceID
     * @return type
     */
    public function getCourseModule($moduleID, $instanceID){
        
        global $DB;
        return $DB->get_record("course_modules", array("course" => $this->id, "module" => $moduleID, "instance" => $instanceID));
        
    }
    
    
    /**
     * Search for courses
     * @global type $DB
     * @param type $params
     * @return \GT\Course
     */
    public static function search($params = false){
        
        global $DB;
                
        $return = array();
        $sqlParams = array();
        $sql = "SELECT DISTINCT c.id
                FROM {course} c ";
        
        // Should it have a qualification link?
        if (isset($params['hasQual']) && $params['hasQual'] == true){
            $sql .= "INNER JOIN {bcgt_course_quals} cq ON cq.courseid = c.id ";
            if (isset($params['enabled']) && $params['enabled']){
                $sql .= "INNER JOIN {bcgt_qualifications} q ON q.id = cq.qualid 
                         INNER JOIN {bcgt_qual_builds} b ON b.id = q.buildid 
                         INNER JOIN {bcgt_qual_structures} s ON s.id = b.structureid ";
            }
        }
        
        $sql .= "WHERE c.id > 0 ";
        
        // Structure enabled
        if (isset($params['hasQual']) && $params['hasQual'] && isset($params['enabled']) && $params['enabled']){
            $sql .= "AND s.enabled = 1 ";
        }
        
        // Filter by category
        if (isset($params['catID']) && $params['catID'] != ""){
            $sql .= "AND c.category = ?";
            $sqlParams[] = $params['catID'];
        }
        
        // Filter by name
        if (isset($params['name']) && !\gt_is_empty($params['name'])){
            $sql .= "AND (c.shortname LIKE ? OR c.idnumber LIKE ? OR c.fullname LIKE ?) ";
            $sqlParams[] = '%'.trim($params['name']).'%';
            $sqlParams[] = '%'.trim($params['name']).'%';
            $sqlParams[] = '%'.trim($params['name']).'%';
        }
               
        // Limit
        $limit = (isset($params['limit'])) ? (int)$params['limit'] : self::SEARCH_LIMIT;
        
        $records = $DB->get_records_sql($sql, $sqlParams, 0, $limit);
        if ($records)
        {
            foreach($records as $record)
            {
                $course = new \GT\Course($record->id);
                if ($course->isValid())
                {
                    
                    $return[$course->id] = $course;
                    
                    // If we are getting just courses with quals, check if this has any parents as we want to
                    // include those as well, if they don't have their own quals
                    if (isset($params['hasQual']) && $params['hasQual'] == true){  
                                
                        if ($course->getParentCourses()){
                            
                            foreach($course->getParentCourses() as $parent){
                                
                                $return[$parent->id] = $parent;
                                
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
        }
        
        // Order them again as parent courses will have messed up order
        if (isset($params['hasQual']) && $params['hasQual'] == true){  
            $Sorter = new \GT\Sorter();
            $Sorter->sortCourses($return);
        }
     
        return $return;
        
    }
    
    /**
     * Get all courses in the system
     * @return type
     */
    public static function getAllCourses(){
        return self::search( array('limit' => 0) );
    }
    
    /**
     * Get all courses in the system that are linked to qualifications
     * @return type
     */
    public static function getAllCoursesWithQuals(){
        return self::search( array('hasQual' => true, 'enabled' => true) );
    }
    
    /**
     * Get the name of a course by its id
     * @param type $cID
     * @return type
     */
    public static function getNameById($cID){
        
        $obj = new \GT\Course($cID);
        return ($obj->isValid()) ? $obj->getName() : false;
        
    }
    
    public static function retrieve($type, $value){
        
        global $DB;
        
        if ($type == 'idnumber')
        {
            $result = $DB->get_record('course', array('idnumber' => $value));
        }
        elseif ($type == 'shortname')
        {
            $result = $DB->get_record('course', array('shortname' => $value));
        }
        elseif ($type == 'id')
        {
            $result = $DB->get_record('course', array('id' => $value));
        }
        else
        {
            $result = false;
        }
        
        if ($result)
        {
            $course = new \GT\Course($result->id);
            return $course;
        }
        
        return false;
    }  
    
    
    /**
     * Count all non-deleted & active (confirmed) users
     * @global \GT\type $DB
     * @return type
     */
    public static function countCourses(){
        
        global $DB;
        
        $count = $DB->count_records("course");
        return $count;
        
    }
    
}
