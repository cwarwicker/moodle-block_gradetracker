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
 * User Grade reporting element
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
namespace GT\bc_dashboard;

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

/**
 * User Grade reporting element
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
class grade extends \BCDB\Report\Element {

    protected $level = 'individual';
    protected $type = 'function';
    protected $datatype = 'string';

    public function __construct($params = null) {

        $this->options = array(
            array('select', get_string('reportoption:type', 'block_gradetracker'), array('target' => get_string('targetgrade', 'block_gradetracker'), 'aspirational' => get_string('aspirationalgrade', 'block_gradetracker'), 'weighted' => get_string('weightedtargetgrade', 'block_gradetracker'), 'ceta' => get_string('cetagrade', 'block_gradetracker')))
        );
        parent::__construct($params);

    }

    public function call(&$results) {

        $type = $this->getParam(0);

        $GTEXE = \GT\Execution::getInstance();
        $GTEXE->min();

        $alias = $this->getAliasName();

        if ($results['users']) {
            foreach ($results['users'] as $key => $row) {

                $array = array();

                // Get their list of quals
                $user = new \GT\User($row['id']);
                $grades = $user->getAllUserGrades($type, null);
                if ($grades) {
                    foreach ($grades as $grade) {
                        $array[] = $grade['grade']->getName();
                    }
                }

                $results['users'][$key][$alias] = implode(", ", $array);

            }
        }

    }

    public function get() {

    }

}
