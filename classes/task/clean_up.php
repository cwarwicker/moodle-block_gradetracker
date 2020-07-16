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
 * This class defines the scheduled task to do clean up procedures
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker\task;

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

class clean_up extends \core\task\scheduled_task {

    /**
     * Get the name of the task
     * @return type
     */
    public function get_name() {
        return get_string('task:clean_up', 'block_gradetracker');
    }

    /**
     * Execute the clean up
     * @global type $DB
     */
    public function execute() {

        global $DB;

        $GT = new \GT\GradeTracker();

        // Clean up logs from the db
        $keepFor = $GT->getSetting('keep_logs_for');
        if (is_int($keepFor) && $keepFor > 0) {

            $ago = strtotime("-{$keepFor} days");
            mtrace("Deleting all logs created before " . date('d-m-Y, H:i', $ago));

            // Delete all logs and their attributes, that were created before that timestamp
            $logs = $DB->get_records_select("bcgt_logs", "timestamp < ?", array($ago));

            mtrace("Found " . count($logs) . " logs to delete");

            foreach ($logs as $log) {
                $DB->delete_records("bcgt_logs", array("id" => $log->id));
                $DB->delete_records("bcgt_log_attributes", array("logid" => $log->id));
            }

        } else {
            mtrace("'keep_logs_for' set to never delete logs");
        }

        // Clean up tmp files
        mtrace("Cleaning up tmp files from [dataroot]/gradetracker");
        $result = \GT\GradeTracker::gc();
        mtrace("Deleted {$result} tmp files");

    }

}