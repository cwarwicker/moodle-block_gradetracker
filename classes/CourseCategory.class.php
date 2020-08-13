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
 * This class deals with Course Categories in the gradetracker
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */
namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

class CourseCategory {

    public $id;
    public $name;
    public $parent;

    private $children = array();
    private $courses = array();

    private $staffArray = array();

    public function __construct($id = false) {

        global $DB;

        $GTEXE = \block_gradetracker\Execution::getInstance();

        $record = $DB->get_record("course_categories", array("id" => $id));
        if ($record) {

            $this->id = $record->id;
            $this->name = $record->name;
            $this->parent = $record->parent;

            if (!isset($GTEXE->COURSE_CAT_MIN_LOAD) || !$GTEXE->COURSE_CAT_MIN_LOAD) {
                $this->loadChildren();
            }

        }

    }

    public function hasParent() {
        return ($this->parent > 1);
    }

    /**
     * Load child categories
     * @global type $DB
     * @return type
     */
    private function loadChildren() {

        global $DB;

        $this->children = array();

        $records = $DB->get_records("course_categories", array("parent" => $this->id));
        if ($records) {
            foreach ($records as $record) {
                $this->children[$record->id] = new \block_gradetracker\CourseCategory($record->id);
            }
        }

        return $this->children;

    }

    /**
     * Get child categories
     * @return type
     */
    public function getChildren() {

        if (!$this->children) {
            return $this->loadChildren();
        }

        return $this->children;

    }

    public function loadCourses() {

        global $DB;

        $this->courses = array();

        $records = $DB->get_records("course", array("category" => $this->id));
        if ($records) {
            foreach ($records as $record) {
                $course = new \block_gradetracker\Course($record->id);
                $this->courses[$record->id] = $course;
            }
        }

        // Order courses
        $Sort = new \block_gradetracker\Sorter();
        $Sort->sortCourses($this->courses);

        return $this->courses;

    }

    public function getCourses() {

        if (!$this->courses) {
            return $this->loadCourses();
        }

        return $this->courses;

    }

    /**
     * Convert all the courses and child courses into one array
     * @param type $thisCourse
     * @param type $array
     * @return type
     */
    public function convertCoursesToFlatArray($thisCourse = false, &$array = false) {

        // Use these ones
        if ($thisCourse && $array) {

            $array[$thisCourse->id] = $thisCourse;
            if ( ($childCourses = $thisCourse->getChildCourses()) ) {
                foreach ($childCourses as $childCourse) {
                    $this->convertCoursesToFlatArray($childCourse, $array);
                }
            }

            return;

        }

        // Array to return
        $return = array();

        // Load courses if we haven't done so yet
        $this->getCourses();

        if ($this->courses) {
            foreach ($this->courses as $course) {
                $return[$course->id] = $course;
                if ( ($childCourses = $course->getChildCourses()) ) {
                    foreach ($childCourses as $childCourse) {
                        $this->convertCoursesToFlatArray($childCourse, $return);
                    }
                }
            }
        }

        // Order courses
        $Sort = new \block_gradetracker\Sorter();
        $Sort->sortCourses($return);

        $this->courses = $return;
        return $this->courses;

    }

    /**
     * Go through the courses and filter out any without qualification links
     */
    public function filterOutCoursesWithoutQualifications() {

        // Go through the courses and take out any that don't have a qualification attached
        if ($this->courses) {

            foreach ($this->courses as $key => $course) {

                $quals = $course->getCourseQualifications();
                if (!$quals) {
                    unset($this->courses[$key]);
                }

            }

        }

    }

    public function getParent() {
        return new \block_gradetracker\CourseCategory($this->parent);
    }


    public function getStaff($reload = false) {

        global $GT, $DB;

        if ($reload) {
            $this->staffArray = array();
        } else if ($this->staffArray) {
            return $this->staffArray;
        }

        $return = array();

        $roles = $GT->getStaffRoles();
        if (!$roles) {
            \gt_debug("Tried to get staff on category ({$this->id}), but no staff roles have been defined in the settings");
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
        $params[] = CONTEXT_COURSECAT;
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

        // Are there parent categories above this we want to check?
        if ($this->hasParent()) {
            $parent = new \block_gradetracker\CourseCategory($this->parent);
            $return = $return + $parent->getStaff();
        }

        return $return;

    }

}