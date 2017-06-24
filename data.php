<?php
/**
 * Display the data in a tmp/data file
 * 
 * @copyright 2016 Bedford College
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
require_once 'lib.php';

if ( !isset($_GET['data']) ) exit;

$contents = file_get_contents( \GT\GradeTracker::dataroot() . '/tmp/data/' . $_GET['data'] . '.data' );
if ($contents)
{
    $data = unserialize($contents);
    if ($data)
    {
        foreach($data as $row)
        {
            
            if (isset($_GET['output'])){
                
                $output = $_GET['output'];

                preg_match_all("/%(.*?)%/", $output, $matches);
                if ($matches)
                {
                    foreach($matches[1] as $key => $match)
                    {
                        if (isset($row->$match))
                        {
                            $output = str_replace($matches[0][$key], $row->$match, $output);
                        }
                    }
                }

                echo $output . "<br>";
                
            }
            
            elseif ( isset($_GET['context']) && isset($_GET['field']) && ($field = $_GET['field']) && isset($row->$field) ){
                                
                switch($_GET['context'])
                {
                    case 'qual':
                        $obj = new \GT\Qualification($row->$field);
                        if ($obj->isValid())
                        {
                            echo "<a href='{$CFG->wwwroot}/blocks/gradetracker/config.php?view=quals&section=edit&id={$row->$field}' target='_blank'>[{$obj->getID()}] ".$obj->getDisplayName() . "</a><br>";
                        }
                    break;
                    case 'unit':
                        $obj = new \GT\Unit($row->$field);
                        if ($obj->isValid())
                        {
                            echo "<a href='{$CFG->wwwroot}/blocks/gradetracker/config.php?view=units&section=edit&id={$row->$field}' target='_blank'>[{$obj->getID()}] ".$obj->getDisplayName() . "</a><br>";
                        }
                    break;
                }
                
            }
            
            else
            {
                print_object($row);
            }
                        
        }
    }
} 

exit;