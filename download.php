<?php
/**
 * Download a file from the dataroot
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

require_once '../../config.php';
require_once $CFG->dirroot . '/lib/filelib.php';
//require_login(); // Took this out so parent portal can access the grid images.
// I don't think it should matter much, it's not used for anything that needs to be secured

$f = required_param('f', PARAM_TEXT);

$record = $DB->get_record("bcgt_file_codes", array("code" => $f));
if (!$record || !file_exists($record->path)){
    print_error( get_string('filenotfound', 'block_gradetracker') );
    exit;
}

\send_file($record->path, basename($record->path));
exit;