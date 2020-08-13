<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * This class deals with Moodle Courses, and any methods relating them to the Grade Tracker
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

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
        if ($course) {
            $props = get_object_vars($course);
            foreach ($props as $prop => $val) {
                $this->$prop = $val;
            }
        }

    }

    public function isValid() {
        return (isset($this->id) && $this->id > 0);
    }

    /**
     * Get the name of the course, in specific format
     * @return boolean
     */
    public function getName() {

        global $GT;

        if ($this->isValid()) {

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
    public function getNameWithCategory($name = false, $category = false) {

        if (!$category) {
            $category = $this->getCategory();
        }

        $courseName = ($name) ? $name : $this->getName();
        $name = $category->name . ' / ' . $courseName;

        if ($category->hasParent()) {
            $name = $this->getNameWithCategory($name, $category->getParent());
        }

        return $name;

    }

    /**
     * Get the course category for this course
     * @return type
     */
    public function getCategory() {

        if (!$this->courseCategory) {
            $this->courseCategory = new \block_gradetracker\CourseCategory($this->category);
        }

        return $this->courseCategory;

    }

    /**
     * Get a specific group from the course
     * @param  [type] $groupID [description]
     * @return [type]          [description]
     */
    public function getGroup($groupID) {

        $groups = $this->getGroups();

        foreach ($groups as $type => $typeGroups) {

            foreach ($typeGroups as $id => $group) {

                if ($id === $groupID) {
                    return $group;
                }

            }

        }

        return false;

    }

    /**
     * Get the gropus on this course and its parent/child courses
     * @return type
     */
    public function getGroups() {
        if (!$this->groups) {
            $this->loadGroups();
        }
        return $this->groups;
    }

    /**
     * Load groups on this course and its parent and child courses
     * @return type
     */
    public function loadGroups() {

        $this->groups = array(
            'parent' => array(),
            'direct' => array(),
            'child' => array()
        );

        // Direct
        $groups = groups_get_all_groups($this->id);
        foreach ($groups as $group) {
            $members = groups_get_members($group->id);
            $group->usercnt = count($members);
            if ($group->usercnt > 0) {
                $this->groups['direct'][$group->id] = $group;
            }
        }

        // Parent.
        $parents = $this->getParentCourses();
        if ($parents) {
            foreach ($parents as $parent) {
                $this->groups['parent'][$parent->id] = array();
                $groups = groups_get_all_groups($parent->id);
                foreach ($groups as $group) {
                    $members = groups_get_members($group->id);
                    $group->usercnt = count($members);
                    if ($group->usercnt > 0) {
                        $this->groups['parent'][$parent->id][$group->id] = $group;
                    }
                }

                // Remove empty
                if (!$this->groups['parent'][$parent->id]) {
                    unset($this->groups['parent'][$parent->id]);
                }

            }
        }

        // Children
        $children = $this->getChildCourses();
        if ($children) {
            foreach ($children as $child) {
                $this->groups['child'][$child->id] = array();
                $groups = groups_get_all_groups($child->id);
                foreach ($groups as $group) {
                    $members = groups_get_members($group->id);
                    $group->usercnt = count($members);
                    if ($group->usercnt > 0) {
                        $this->groups['child'][$child->id][$group->id] = $group;
                    }
                }

                // Remove empty
                if (!$this->groups['child'][$child->id]) {
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
    public function isQualificationOnCourse($qualID) {

        $this->getCourseQualifications(false, true);
        return (array_key_exists($qualID, $this->quals));

    }

    /**
     * Get the qualifications attached to this course
     * @return type
     */
    public function getCourseQualifications($children = false, $forceReload = false) {

        if (!$this->quals || $forceReload) {
            $this->loadCourseQualifications($children);
        }

        return $this->quals;

    }

    /**
     * Load qualifications on this course from the DB
     * @global \block_gradetracker\type $DB
     * @param bool $children DO we want to get them off any child courses as well?
     */
    private function loadCourseQualifications($children = false) {

        global $DB;

        $this->quals = array();

        $records = $DB->get_records("bcgt_course_quals", array("courseid" => $this->id));
        if ($records) {
            foreach ($records as $record) {
                $obj = new \block_gradetracker\Qualification\UserQualification($record->qualid);
                $structure = $obj->getStructure();
                if ($obj->isValid() && !$obj->isDeleted() && $structure->isEnabled()) {
                    $this->quals[$obj->getID()] = $obj;
                }
            }
        }

        // Children
        if ($children) {
            if ($this->getChildCourses()) {
                foreach ($this->getChildCourses() as $child) {

                    $records = $DB->get_records("bcgt_course_quals", array("courseid" => $child->id));
                    if ($records) {
                        foreach ($records as $record) {
                            $obj = new \block_gradetracker\Qualification\UserQualification($record->qualid);
                            if ($obj->isValid() && !$obj->isDeleted()) {
                                $this->quals[$obj->getID()] = $obj;
                            }
                        }
                    }

                }
            }
        }

        // Order them
        $Sorter = new \block_gradetracker\Sorter();
        $Sorter->sortQualifications($this->quals);

    }

    /**
     * Count how many qualifications are linked to the course
     * @return type
     */
    public function countCourseQualifications($children = false) {

        $quals = $this->getCourseQualifications($children);
        return count($quals);

    }


    /**
     * Get a parent -> child relationship of courses in both directions around this course
     * @return type
     */
    public function getRelationshipHierarchy() {

        $parents = $this->getParentCourses();
        $children = $this->getChildCourses();

        $this->hierarchyLevel = 0;

        $results = array();
        $results = array_merge($results, $parents);
        $results[$this->id] = $this;
        $results = array_merge($results, $children);

        usort($results, function ($a, $b) {

            if ($a->hierarchyLevel == $b->hierarchyLevel) {
                return 0;
            }
            return ($a->hierarchyLevel > $b->hierarchyLevel) ? -1 : 1;
        });

        return $results;

    }

    /**
     * Get the students on this course, on any role as defined in our student role setting
     * @global \block_gradetracker\type $DB
     * @global type $GT
     * @return \block_gradetracker\User
     */
    public function getStudents($direct = false, $reload = false) {

        global $DB, $GT;

        if ($reload) {
            $this->studentsArray = array();
        } else if ($this->studentsArray) {
            return $this->studentsArray;
        }

        $return = array();

        $roles = $GT->getStudentRoles();
        if (!$roles) {
            \gt_debug("Tried to get students on course ({$this->id}), but no student roles have been defined in the settings");
            return false;
        }

        // If we want direct enrolments, we don't want any students attached by course meta link
        $and = '';
        if ($direct) {
            $and = " AND ra.component != 'enrol_meta' ";
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

        if ($records) {
            foreach ($records as $record) {
                $obj = new \block_gradetracker\User($record->id);
                if ($obj->isValid()) {
                    $return[$obj->id] = $obj;
                }
            }
        }

        $this->studentsArray = $return;
        return $this->studentsArray;

    }

    public function countStaff() {
        return count($this->getStaff());
    }

    public function countStudents() {
        return count($this->getStudents());
    }


    /** Get the students on this course, on any role as defined in our student role setting
     * @global \block_gradetracker\type $DB
     * @global type $GT
     * @return \block_gradetracker\User
     */
    public function getStaff($reload = false) {

        global $DB, $GT;

        if ($reload) {
            $this->staffArray = array();
        } else if ($this->staffArray) {
            return $this->staffArray;
        }

        $return = array();

        $roles = $GT->getStaffRoles();
        if (!$roles) {
            \gt_debug("Tried to get staff on course ({$this->id}), but no staff roles have been defined in the settings");
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

        if ($records) {
            foreach ($records as $record) {
                $obj = new \block_gradetracker\User($record->id);
                if ($obj->isValid()) {
                    $return[$obj->id] = $obj;
                }
            }
        }

        // Then from any parent courses
        $parents = $this->getParentCourses();
        if ($parents) {
            foreach ($parents as $parent) {

                $parentStaff = $parent->getStaff();
                $return = $return + $parentStaff;

            }
        }

        // Then get any category enrolments
        $category = $this->getCategory();
        $return = $return + $category->getStaff();

        $this->staffArray = $return;
        return $this->staffArray;

    }

    /**
     * Does this course have a specific child course?
     * @param type $cID
     * @return type
     */
    public function hasChild($cID) {

        $children = $this->getChildCourses();
        return (array_key_exists($cID, $children));

    }

    /**
     * Get the child courses of this course
     * @return type
     */
    public function getChildCourses($recursion = true) {

        $false = false;

        if (!$this->childCourses) {
            $this->loadChildCourses($false, $false, $recursion);
        }

        return $this->childCourses;

    }

    /**
     * Get the parent courses of this course
     */
    public function getParentCourses() {

        if (!$this->parentCourses) {
            $this->loadParentCourses();
        }

        return $this->parentCourses;

    }



    /**
     * Load the child courses of this course and any of its children, recursively
     * @global \block_gradetracker\type $DB
     * @param type $courseID
     * @param type $courses
     * @param bool $recursion Use recursion to get lower levels?
     * @return type
     */
    private function loadChildCourses($courseID = false, &$courses = false, $recursion = true, $cnt = -1) {

        global $DB;

        if ($courseID) {

            $childCourses = $DB->get_records_sql("SELECT c.id
                                                  FROM {course} c
                                                  INNER JOIN {enrol} e ON e.customint1 = c.id
                                                  WHERE e.enrol = 'meta' AND e.status = 0 AND e.courseid = ?
                                                  ORDER BY fullname asc", array($courseID));

            if ($childCourses) {
                foreach ($childCourses as $child) {
                    $obj = new \block_gradetracker\Course($child->id);
                    if ($obj->isValid()) {

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

            if ($childCourses) {
                foreach ($childCourses as $child) {
                    $obj = new \block_gradetracker\Course($child->id);
                    if ($obj->isValid()) {

                        // Add to array
                        $obj->hierarchyLevel = $cnt;
                        $courses[$obj->id] = $obj;

                        // Then does this have any children of its own?
                        if ($recursion) {
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
     * @global \block_gradetracker\type $DB
     * @param type $courseID
     * @param type $courses
     * @return type
     */
    private function loadParentCourses($courseID = false, &$courses = false, $cnt = 1) {

        global $DB;

        if ($courseID) {

            $parentCourses = $DB->get_records_sql("SELECT c.id
                                                  FROM {course} c
                                                  INNER JOIN {enrol} e ON e.courseid = c.id
                                                  WHERE e.enrol = 'meta' AND e.status = 0 AND e.customint1 = ?
                                                  ORDER BY fullname asc", array($courseID));

            if ($parentCourses) {
                foreach ($parentCourses as $parent) {
                    $obj = new \block_gradetracker\Course($parent->id);
                    if ($obj->isValid()) {

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

            if ($parentCourses) {
                foreach ($parentCourses as $parent) {
                    $obj = new \block_gradetracker\Course($parent->id);
                    if ($obj->isValid()) {

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
     * @global \block_gradetracker\type $DB
     * @global \block_gradetracker\type $MSGS
     */
    public function saveFormUserUnits() {

        global $DB, $MSGS;

        $settings = array(
            'user_qual_units' => df_optional_param_array_recursive('user_qual_units', false, PARAM_TEXT),
            'staff_qual_units' => df_optional_param_array_recursive('staff_qual_units', false, PARAM_TEXT),
        );

        // Students
        $qualUsers = array();

        // If we ticked any
        if ($settings['user_qual_units']) {
            foreach ($settings['user_qual_units'] as $qualID => $userUnits) {
                if ($userUnits) {
                    foreach ($userUnits as $unitID => $users) {

                        if (!isset($qualUsers[$qualID])) {
                            $qualUsers[$qualID] = array();
                        }

                        $qualUsers[$qualID][$unitID] = $users;

                        if ($users) {
                            foreach ($users as $userID) {
                                $user = new \block_gradetracker\User($userID);
                                if ($user->isValid()) {
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
        $qualUsers = array();

        // If we ticked any
        if ($settings['staff_qual_units']) {
            foreach ($settings['staff_qual_units'] as $qualID => $userUnits) {
                if ($userUnits) {
                    foreach ($userUnits as $unitID => $users) {

                        if (!isset($qualUsers[$qualID])) {
                            $qualUsers[$qualID] = array();
                        }

                        $qualUsers[$qualID][$unitID] = $users;

                        if ($users) {
                            foreach ($users as $userID) {
                                $user = new \block_gradetracker\User($userID);
                                if ($user->isValid()) {
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
        $Log = new \block_gradetracker\Log();
        $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_COURSE_USER_UNITS;
        $Log->afterjson = $_POST; // This usage of $_POST is just to store the submitted data in a log.
        $Log->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_COURSEID, $this->id);
        $Log->save();
        // ------------ Logging Info

    }

    /**
     * Remove any user qualification links we didn't tick this time
     * @global \block_gradetracker\type $DB
     * @param type $users
     * @param type $role
     */
    private function removeOldUserQualUnits($users, $role) {

        global $DB;

        if ($this->getCourseQualifications(true, true)) {

            foreach ($this->getCourseQualifications(true) as $qual) {

                $qualID = $qual->getID();

                if ($qual->getUnits()) {

                    foreach ($qual->getUnits() as $unit) {

                        $unitID = $unit->getID();
                        $qualUnitUsers = (isset($users[$qualID][$unitID])) ? $users[$qualID][$unitID] : array();

                        $currentIDs = array();

                        if ($role == "STUDENT") {
                            $people = $this->getStudents();
                        } else if ($role == "STAFF") {
                            $people = $this->getStaff();
                        }

                        // If there are any people on the course, get their IDs if they have a record for this qual_unit in the DB
                        if ($people) {
                            foreach ($people as $person) {
                                $current = $DB->get_record("bcgt_user_qual_units", array("qualid" => $qualID, "unitid" => $unitID, "userid" => $person->id, "role" => $role));
                                if ($current) {
                                    $currentIDs[] = $person->id;
                                }
                            }
                        }

                        $removed = array_diff($currentIDs, $qualUnitUsers);

                        // If any have been removed, delete the user_qual_unit record
                        if ($removed) {
                            foreach ($removed as $userID) {
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
     * @global \block_gradetracker\type $DB
     * @global \block_gradetracker\type $MSGS
     */
    public function saveFormUserQuals() {

        global $DB, $MSGS;

        $settings = array(
            'user_quals' => df_optional_param_array_recursive('user_quals', false, PARAM_TEXT),
            'staff_quals' => df_optional_param_array_recursive('staff_quals', false, PARAM_TEXT),
        );

        // Student Quals
        $qualUsers = array();

        // If we ticked any, loop through them and link them up
        if ($settings['user_quals']) {

            foreach ($settings['user_quals'] as $qualID => $users) {

                if ($users) {

                    $qualUsers[$qualID] = $users;

                    foreach ($users as $userID) {

                        $user = new \block_gradetracker\User($userID);
                        if ($user->isValid()) {
                            $user->addToQual($qualID, "STUDENT");
                        }

                    }

                }

            }

        }

        $this->removeOldUserQuals($qualUsers, "STUDENT");

        // Staff Quals
        $qualUsers = array();

        // If we ticked any, loop through them and link them up
        if ($settings['staff_quals']) {

            foreach ($settings['staff_quals'] as $qualID => $users) {

                if ($users) {

                    $qualUsers[$qualID] = $users;

                    foreach ($users as $userID) {

                        $user = new \block_gradetracker\User($userID);
                        if ($user->isValid()) {
                            $user->addToQual($qualID, "STAFF");
                        }

                    }

                }

            }

        }

        $this->removeOldUserQuals($qualUsers, "STAFF");

        $MSGS['success'] = get_string('userqualssaved', 'block_gradetracker');

        // ------------ Logging Info
        $Log = new \block_gradetracker\Log();
        $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_COURSE_USER_QUALS;
        $Log->afterjson = $_POST; // This usage of $_POST is just to store submitted data in a log.
        $Log->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_COURSEID, $this->id);
        $Log->save();
        // ------------ Logging Info

    }

    /**
     * Remove any user qualification links we didn't tick this time
     * @global \block_gradetracker\type $DB
     * @param type $users
     */
    private function removeOldUserQuals($users, $role) {

        global $DB;

        // Doing it for this course and its child courses
        $array = array($this);
        $children = $this->getChildCourses();
        if ($children) {
            foreach ($children as $child) {
                $array[] = $child;
            }
        }

        foreach ($array as $course) {

            $quals = $course->getCourseQualifications();
            if ($quals) {

                foreach ($quals as $qual) {

                    $qualID = $qual->getID();
                    $qualUsers = (isset($users[$qualID])) ? $users[$qualID] : array();

                    $currentIDs = array();

                    if ($role == "STUDENT") {
                        $people = $this->getStudents();
                    } else if ($role == "STAFF") {
                        $people = $this->getStaff();
                    }

                    if ($people) {

                        foreach ($people as $person) {

                            $current = $DB->get_record("bcgt_user_quals", array("qualid" => $qualID, "userid" => $person->id, "role" => $role));
                            if ($current) {
                                $currentIDs[] = $person->id;
                            }

                        }

                    }

                    $removed = array_diff($currentIDs, $qualUsers);

                    if ($removed) {
                        foreach ($removed as $userID) {
                            $user = new \block_gradetracker\User($userID);
                            $user->removeFromQual($qualID, $role);
                        }
                    }

                }

            }

        }

    }

    /**
     * Add a link between this course and a given qualID
     * @global \block_gradetracker\type $DB
     * @param type $qualID
     * @return boolean
     */
    public function addCourseQual($qualID) {

        global $DB;

        // Insert new record if it doesn't exist already
        $check = $DB->get_record("bcgt_course_quals", array("courseid" => $this->id, "qualid" => $qualID));
        if (!$check) {
            $ins = new \stdClass();
            $ins->courseid = $this->id;
            $ins->qualid = $qualID;
            $course_qual = $DB->insert_record("bcgt_course_quals", $ins);

            $GT = new \block_gradetracker\GradeTracker();

            if ($GT->getSetting('use_auto_enrol_quals') == 1) {

                $students = $this->getStudents();

                foreach ($students as $student) {
                    $GT_User = new \block_gradetracker\User($student->id);
                    $GT_User->addToQual($qualID, "STUDENT");
                }

                $staffs = $this->getStaff();

                foreach ($staffs as $staff) {
                    $GT_User = new \block_gradetracker\User($staff->id);
                    $GT_User->addToQual($qualID, "STAFF");
                }

            }

            if ($GT->getSetting('use_auto_enrol_units') == 1) {

                $Qual = new \block_gradetracker\Qualification($qualID);
                $units = $Qual->getUnits();

                $students = $this->getStudents();

                foreach ($students as $student) {
                    $GT_User = new \block_gradetracker\User($student->id);

                    foreach ($units as $unit) {
                        $GT_User->addToQualUnit($qualID, $unit->getID(), "STUDENT");
                    }
                }

                $staffs = $this->getStaff();

                foreach ($staffs as $staff) {
                    $GT_User = new \block_gradetracker\User($staff->id);

                    foreach ($units as $unit) {
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
     * @global \block_gradetracker\type $DB
     * @global type $MSGS
     */
    public function saveFormCourseQuals() {

        global $DB, $MSGS;

        $qualIDs = df_optional_param_array_recursive('quals', false, PARAM_INT);

        // Add new links
        if ($qualIDs) {
            foreach ($qualIDs as $qualID) {
                $this->addCourseQual($qualID);
            }
        }

        // Remove ones we didn't submit this time
        $this->removeOldCourseQuals($qualIDs);

        $MSGS['success'] = get_string('coursequalssaved', 'block_gradetracker');

        // ------------ Logging Info
        $Log = new \block_gradetracker\Log();
        $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_COURSE_QUALS;
        $Log->afterjson = $_POST; // This usage of $_POST is just to store the submitted data in a log.
        $Log->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_COURSEID, $this->id);
        $Log->save();
        // ------------ Logging Info

    }

    /**
     * Remove links to course quals that we didn't submit this time
     * Also if student is not linked to the qual on any other course, remove their qual link as well
     * @global \block_gradetracker\type $DB
     */
    private function removeOldCourseQuals($qualIDs) {

        global $DB;

        $courseQuals = $this->getCourseQualifications();

        // Loop through course quals
        if ($courseQuals) {

            foreach ($courseQuals as $courseQual) {

                // If it doesn't exist in the submitted array, remove it
                if (!in_array($courseQual->getID(), $qualIDs)) {

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
     * @global \block_gradetracker\type $DB
     */
    public function getSupportedActivities() {

        global $DB;

        $return = array();
        $records = $DB->get_records_sql("SELECT cm.id, bm.id as 'linkid', cm.instance
                                         FROM {course_modules} cm
                                         INNER JOIN {bcgt_mods} bm ON bm.modid = cm.module
                                         WHERE cm.course = ? AND bm.deleted = 0", array($this->id));

        if ($records) {
            foreach ($records as $record) {
                $obj = new \block_gradetracker\ModuleLink($record->linkid);
                $obj->setRecordID($record->instance);
                $obj->setCourseModID($record->id);
                $return[] = $obj;
            }
        }

        // Order them by the name of the instance
        usort($return, function($a, $b) {
            return strnatcasecmp($a->getRecordName(), $b->getRecordName());
        });

        return $return;

    }

    /**
     * Get specific activity on this course
     * @global \block_gradetracker\type $DB
     * @param type $cmID
     * @return boolean|\block_gradetracker\ModuleLink
     */
    public function getActivity($cmID) {

        global $DB;

        $record = $DB->get_record_sql("SELECT cm.id, bm.id as 'linkid', cm.instance
                                       FROM {course_modules} cm
                                       INNER JOIN {bcgt_mods} bm ON bm.modid = cm.module
                                       WHERE cm.id = ? AND cm.course = ?", array($cmID, $this->id));

        if ($record) {

            $obj = new \block_gradetracker\ModuleLink($record->linkid);
            $obj->setRecordID($record->instance);
            $obj->setCourseModID($record->id);
            return $obj;

        }

        return false;

    }

    /**
     * Get a course module, based on the module type and instance id
     * @global \block_gradetracker\type $DB
     * @param type $moduleID
     * @param type $instanceID
     * @return type
     */
    public function getCourseModule($moduleID, $instanceID) {

        global $DB;
        return $DB->get_record("course_modules", array("course" => $this->id, "module" => $moduleID, "instance" => $instanceID));

    }


    /**
     * Search for courses
     * @global type $DB
     * @param type $params
     * @return \block_gradetracker\Course
     */
    public static function search($params = false) {

        global $DB;

        $return = array();
        $sqlParams = array();
        $sql = "SELECT DISTINCT c.id
                FROM {course} c ";

        // Should it have a qualification link?
        if (isset($params['hasQual']) && $params['hasQual'] == true) {
            $sql .= "INNER JOIN {bcgt_course_quals} cq ON cq.courseid = c.id ";
            if (isset($params['enabled']) && $params['enabled']) {
                $sql .= "INNER JOIN {bcgt_qualifications} q ON q.id = cq.qualid
                         INNER JOIN {bcgt_qual_builds} b ON b.id = q.buildid
                         INNER JOIN {bcgt_qual_structures} s ON s.id = b.structureid ";
            }
        }

        $sql .= "WHERE c.id > 0 ";

        // Structure enabled
        if (isset($params['hasQual']) && $params['hasQual'] && isset($params['enabled']) && $params['enabled']) {
            $sql .= "AND s.enabled = 1 ";
        }

        // Filter by category
        if (isset($params['catID']) && $params['catID'] != "") {
            $sql .= "AND c.category = ?";
            $sqlParams[] = $params['catID'];
        }

        // Filter by name
        if (isset($params['name']) && !\gt_is_empty($params['name'])) {
            $sql .= "AND (c.shortname LIKE ? OR c.idnumber LIKE ? OR c.fullname LIKE ?) ";
            $sqlParams[] = '%'.trim($params['name']).'%';
            $sqlParams[] = '%'.trim($params['name']).'%';
            $sqlParams[] = '%'.trim($params['name']).'%';
        }

        // Limit
        $limit = (isset($params['limit'])) ? (int)$params['limit'] : self::SEARCH_LIMIT;

        $records = $DB->get_records_sql($sql, $sqlParams, 0, $limit);
        if ($records) {
            foreach ($records as $record) {
                $course = new \block_gradetracker\Course($record->id);
                if ($course->isValid()) {

                    $return[$course->id] = $course;

                    // If we are getting just courses with quals, check if this has any parents as we want to
                    // include those as well, if they don't have their own quals
                    if (isset($params['hasQual']) && $params['hasQual'] == true) {

                        if ($course->getParentCourses()) {

                            foreach ($course->getParentCourses() as $parent) {

                                $return[$parent->id] = $parent;

                            }

                        }

                    }

                }

            }

        }

        // Order them again as parent courses will have messed up order
        if (isset($params['hasQual']) && $params['hasQual'] == true) {
            $Sorter = new \block_gradetracker\Sorter();
            $Sorter->sortCourses($return);
        }

        return $return;

    }

    /**
     * Get all courses in the system
     * @return type
     */
    public static function getAllCourses() {
        return self::search( array('limit' => 0) );
    }

    /**
     * Get all courses in the system that are linked to qualifications
     * @return type
     */
    public static function getAllCoursesWithQuals() {
        return self::search( array('hasQual' => true, 'enabled' => true) );
    }

    /**
     * Get the name of a course by its id
     * @param type $cID
     * @return type
     */
    public static function getNameById($cID) {

        $obj = new \block_gradetracker\Course($cID);
        return ($obj->isValid()) ? $obj->getName() : false;

    }

    public static function retrieve($type, $value) {

        global $DB;

        if ($type == 'idnumber') {
            $result = $DB->get_record('course', array('idnumber' => $value));
        } else if ($type == 'shortname') {
            $result = $DB->get_record('course', array('shortname' => $value));
        } else if ($type == 'id') {
            $result = $DB->get_record('course', array('id' => $value));
        } else {
            $result = false;
        }

        if ($result) {
            $course = new \block_gradetracker\Course($result->id);
            return $course;
        }

        return false;
    }


    /**
     * Count all non-deleted & active (confirmed) users
     * @global \block_gradetracker\type $DB
     * @return type
     */
    public static function countCourses() {

        global $DB;

        $count = $DB->count_records("course");
        return $count;

    }

}
