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
 * Value Added reporting element
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
namespace block_gradetracker\df_dashboard;

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

/**
 * Value Added reporting element
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
class valueadded extends \block_df_dashboard\Report\Element {

    protected $level = 'aggregate';
    protected $type = 'function';

    public function __construct($params = null) {

        $this->options = array(

            array('select', get_string('valueadded:gradecmp', 'block_gradetracker'), array(
                'award:average' => get_string('predictedgrade', 'block_gradetracker'),
                'award:final' => get_string('predictedfinalgrade', 'block_gradetracker'),
                'award:min' => get_string('predictedmingrade', 'block_gradetracker'),
                'award:max' => get_string('predictedmaxgrade', 'block_gradetracker'),
                'grade:ceta' => get_string('cetagrade', 'block_gradetracker')
            )),

            array('select', get_string('valueadded:targetcmp', 'block_gradetracker'), array(
                'target' => get_string('targetgrade', 'block_gradetracker'),
                'aspirational' => get_string('aspirationalgrade', 'block_gradetracker'),
                'ceta' => get_string('cetagrade', 'block_gradetracker'),
            ))

        );

        parent::__construct($params);

    }



    /**
     * Aggregate the avg gcse scores into an average
     * @param type $results
     * @return type
     */
    public function aggregate($results) {

        $field = $this->getAliasName();
        $ttl = 0;
        $cnt = count($results);

        // Loop through the users
        foreach ($results as $row) {
            $ttl += $row[$field];
        }

        // Average
        $ttl = ($cnt > 0) ? round($ttl / $cnt, 2) : 0;

        return array($field => $ttl);

    }


    public function call(&$results) {

        $alias = $this->getAliasName();

        $award = $this->getParam(0);
        $target = $this->getParam(1);

        // Load the award object
        $split = explode(":", $award);
        $objType = $split[0];
        $awardType = $split[1];

        // Loop through the users
        if ($results['users']) {
            foreach ($results['users'] as $key => $row) {

                $user = new \block_gradetracker\User($row['id']);
                $quals = $user->getQualifications("STUDENT");

                // Loop through their quals, in case they are on multiple ones, in which case we'll do an average
                if ($quals) {
                    foreach ($quals as $qual) {

                        // Get their award object
                        $awardObject = ($objType == 'grade') ? $user->getUserGrade($awardType, array('qualID' => $qual->getID()), false, true) : $qual->getUserAward($awardType);
                        $targetObject = $user->getUserGrade($target, array('qualID' => $qual->getID()), false, true);

                        // If we have both, compare them
                        if ($awardObject && $targetObject) {
                            $results['users'][$key][$alias] = $awardObject->getRank() - $targetObject->getRank();
                        }
                    }
                }

                if (!isset($results['users'][$key][$alias])) {
                    $results['users'][$key][$alias] = 0;
                }

            }
        }

    }

    public function get() {

    }

}
