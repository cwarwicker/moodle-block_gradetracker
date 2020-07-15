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
 * Number of Quals on Entry reporting element
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
 * Number of Quals on Entry reporting element
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
class numberofqoe extends \BCDB\Report\Element {

    protected $level = 'aggregate';
    protected $type = 'sql';

    public function __construct($params = null) {

        // What options can they choose?
        $this->options = array(
            array('select', get_string('reportoption:count', 'block_bc_dashboard'), array('total' => get_string('total', 'block_bc_dashboard'), 'average' => get_string('average', 'block_bc_dashboard')))
        );

        parent::__construct($params);

    }

    public function get() {

        $this->sql['select'] = "count(distinct {$this->alias}.id)";
        $this->sql['join'][] = "left join {bcgt_user_qoe} {$this->alias} on ({$this->alias}.userid = u.id)";

    }

    /**
     * Aggregate the attendance/punctuality values into an average
     * @param type $results
     */
    public function aggregate($results) {

        $field = $this->getAliasName();
        $type = $this->getParam(0);

        $ttl = 0;
        $cnt = count($results);

        // Loop through the users
        foreach ($results as $row) {
            $ttl += $row[$field];
        }

        if ($type == 'average') {
            $ttl = ($cnt > 0) ? round( ($ttl / $cnt), 2 ) : 0;
        }

        return array($field => $ttl);

    }

    public function call(&$results) {

    }

}
