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
 * Execution Class
 *
 * This class defines which aspects we want to load during the script execution, to avoid loading data we don't need.
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */
namespace GT;

defined('MOODLE_INTERNAL') or die();

class Execution
{

    const STUD_LOAD_LEVEL_QUAL = 1;
    const STUD_LOAD_LEVEL_UNIT = 2;
    const STUD_LOAD_LEVEL_ALL = 3;

    private static $instance;

    public $QUAL_STRUCTURE_MIN_LOAD = null;
    public $QUAL_BUILD_MIN_LOAD = null;
    public $QUAL_MIN_LOAD = null;
    public $UNIT_MIN_LOAD = null;
    public $UNIT_NO_SORT = null;
    public $CRIT_NO_SORT = null;
    public $STUDENT_LOAD_LEVEL = null;
    public $COURSE_CAT_MIN_LOAD = null;

    protected function __construct() {
    }

    private function __clone() {
    }

    private function __wakeup() {
    }

    public function min() {

        $this->QUAL_STRUCTURE_MIN_LOAD = true;
        $this->QUAL_BUILD_MIN_LOAD = true;
        $this->QUAL_MIN_LOAD = true;
        $this->UNIT_MIN_LOAD = true;
        $this->UNIT_NO_SORT = true;

    }

    public static function getInstance() {

        if (null === static::$instance) {
            static::$instance = new \GT\Execution();
        }

        return static::$instance;

    }

}