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
 * This class defines the scheduled task to refresh site registration
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker\task;

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

class refresh_site_registration extends \core\task\scheduled_task {

    /**
     * Get the name of the task
     * @return type
     */
    public function get_name() {
        return get_string('task:refresh_site_registration', 'block_gradetracker');
    }

    public function execute() {

        mtrace("Attempting to update site registration");

        $Site = new \GT\Site();
        $result = $Site->cron();
        mtrace("Result: " . $result);

    }

}