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
 * List of user's qualifications reporting element
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
namespace block_gradetracker\bc_dashboard;

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

/**
 * List of user's qualifications reporting element
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
class listofquals extends \block_bc_dashboard\Report\Element {

    protected $level = 'individual';
    protected $type = 'function';
    protected $datatype = 'string';

    public function call(&$results) {

        $GTEXE = \block_gradetracker\Execution::getInstance();
        $GTEXE->min();

        $alias = $this->getAliasName();

        if ($results['users']) {
            foreach ($results['users'] as $key => $row) {

                $array = array();

                // Get their list of quals
                $user = new \block_gradetracker\User($row['id']);
                $quals = $user->getQualifications("STUDENT");
                if ($quals) {
                    foreach ($quals as $qual) {
                        $array[] = $qual->getDisplayName();
                    }
                }

                $results['users'][$key][$alias] = implode("; ", $array);

            }
        }

    }

    public function get() {

    }

}
