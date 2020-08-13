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
 * Display the data in a tmp/data file
 *
 * This is used with the Statistics class to show the actual data behind the stats
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

require_once('../../config.php');
require_once('lib.php');

require_login();

$settings = array(
    'data' => optional_param('data', false, PARAM_RAW),
    'output' => optional_param('output', false, PARAM_TEXT),
    'context' => optional_param('context', false, PARAM_TEXT),
    'field' => optional_param('field', false, PARAM_TEXT),
);

if (!$settings['data']) {
    exit;
}

// This should be safe, as the file has to end with '.data' which is only used in the data directory, so even if they did ../../someotherfile.txt it won't load it.
// However, let's sanitise it anyway.
$settings['data'] = gt_sanitise_path($settings['data']);

$contents = file_get_contents( \block_gradetracker\GradeTracker::dataroot() . '/tmp/data/' . $settings['data'] . '.data' );
if ($contents) {
    $data = unserialize($contents);
    if ($data) {
        foreach ($data as $row) {

            if ($settings['output']) {

                $output = $settings['output'];

                preg_match_all("/%(.*?)%/", $output, $matches);
                if ($matches) {
                    foreach ($matches[1] as $key => $match) {
                        if (isset($row->$match)) {
                            $output = str_replace($matches[0][$key], $row->$match, $output);
                        }
                    }
                }

                echo $output . "<br>";

            } else if ( $settings['context'] && $settings['field'] && isset($row->{$settings['field']}) ) {

                switch ($settings['context']) {
                    case 'qual':
                        $obj = new \block_gradetracker\Qualification($row->{$settings['field']});
                        if ($obj->isValid()) {
                            echo "<a href='{$CFG->wwwroot}/blocks/gradetracker/config.php?view=quals&section=edit&id={$row->{$settings['field']}}' target='_blank'>[{$obj->getID()}] ".$obj->getDisplayName() . "</a><br>";
                        }
                        break;
                    case 'unit':
                        $obj = new \block_gradetracker\Unit($row->{$settings['field']});
                        if ($obj->isValid()) {
                            echo "<a href='{$CFG->wwwroot}/blocks/gradetracker/config.php?view=units&section=edit&id={$row->{$settings['field']}}' target='_blank'>[{$obj->getID()}] ".$obj->getDisplayName() . "</a><br>";
                        }
                        break;
                }

            } else {
                print_object($row);
            }

        }
    }
}

exit;