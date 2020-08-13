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
 * Pass Criteria Summary Report
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
namespace block_gradetracker\Reports;

use local_df_hub\excel;

defined('MOODLE_INTERNAL') or die();

require_once('Report.class.php');

class PassCriteriaSummaryReport extends \block_gradetracker\Reports\Report {

    protected $name = 'PASS_CRIT_SUMMARY';

    public function run(array $params) {

        global $CFG, $USER;

        $User = new \block_gradetracker\User($USER->id);
        $params = (isset($params['params'])) ? $params['params'] : false;

        // Make sure qual structure has been passed through
        $qualStructure = false;
        $structureID = $this->extractParam("structureID", $params);
        if ($structureID != 'all') {
            $qualStructure = new \block_gradetracker\QualificationStructure($structureID);
            if (!$qualStructure->isValid()) {
                gt_ajax_progress(false, array(
                    'error' => get_string('blockbcgtdata:err:invalidqualstructure', 'block_gradetracker')
                ));
                return false;
            }
        }

        // Make sure at least 1 course category selected
        $cats = $this->extractParam("categories", $params);
        if (!is_array($cats) || empty($cats)) {
            gt_ajax_progress(false, array(
                'error' => get_string('invalidcoursecat', 'block_gradetracker')
            ));
            return false;
        }

        // Are we looking for any specific criteria awards as well?
        $specificAwards = $this->extractParam('extraAwardNames', $params);

        // Setup Spreadsheet
        $filename = 'PassCriteriaSummaryReport_' . $User->id . '.xlsx';
        $precision = ini_get('precision');
        $objPHPExcel = new excel($filename);
        ini_set('precision', $precision); # PHPExcel fucks up the native round() function by changing the precision

        $objPHPExcel->getSpreadsheet()->getProperties()
            ->setCreator($User->getDisplayName())
            ->setLastModifiedBy($User->getDisplayName())
            ->setTitle( get_string('reports:passsummary', 'block_gradetracker') )
            ->setSubject( get_string('reports:passsummary', 'block_gradetracker') )
            ->setDescription( get_string('reports:passsummary', 'block_gradetracker') . " " . get_string('generatedbygt', 'block_gradetracker'))
            ->setCustomProperty( "GT-REPORT" , $this->name, 's');

        $formats = array();
        $formats['centre'] = $objPHPExcel->add_format(['align' => 'center']);
        $formats['qual'] = $objPHPExcel->add_format(['bg_color' => '#538DD5', 'color' => '#ffffff', 'bold' => 1]);
        $formats['course'] = $objPHPExcel->add_format(['bg_color' => '#ffff33', 'color' => '#000000', 'bold' => 1]);
        $formats['totals'] = $objPHPExcel->add_format(['bg_color' => '#B1A0C7', 'color' => '#ffffff']);
        $formats['max'] = $objPHPExcel->add_format(['bg_color' => '#948A54', 'color' => '#ffffff']);

        $GTEXE = \block_gradetracker\Execution::getInstance();
        $GTEXE->COURSE_CAT_MIN_LOAD = true;
        $GTEXE->QUAL_MIN_LOAD = true;

        $structures = array();
        $structureSettings = array();

        $catArray = array();
        $total = 0;
        $usedFieldNames = array();

        // Calculate how many courses we are going to need to process, so we can do a progress bar
        if ($cats) {
            foreach ($cats as $catID) {

                $Cat = new \block_gradetracker\CourseCategory($catID);
                $Cat->convertCoursesToFlatArray();
                $Cat->filterOutCoursesWithoutQualifications();
                $catArray[$catID] = $Cat;
                $total += count($Cat->getCourses());

            }
        }

        // Okay, now we know how many courses we are going to need to process, let's start processing!
        $done = 0;
        $sheetIndex = 0;

        if ($catArray) {
            foreach ($catArray as $cat) {

                // Create a sheet for this category
                $sheet = $objPHPExcel->addWorksheet( $this->convertStringToWorksheetName($cat->name) );

                $sheetIndex++;
                $row = 0;

                // Headers
                $sheet->writeString($row, 'A', get_string('course'));
                $sheet->writeString($row, 'B', get_string('qualification', 'block_gradetracker'));
                $sheet->writeString($row, 'C', get_string('students'));

                // "Pass" criteria achieved based on total on qual (This is the Pass - Total column on the PassProg report)
                $sheet->writeString($row, 'D', get_string('reports:passprog:header:passcritachievedtotal', 'block_gradetracker'));
                $sheet->getComment($row, 'D')->getText()->createTextRun('Student "Pass" No. / Total "Pass" No.');

                // Percentage of weighted total assessed (This is the All - Best column on the PassProg report)
                $sheet->writeString($row, 'E', get_string('reports:passprog:header:weightedscoreachieved', 'block_gradetracker'));
                $sheet->getComment($row, 'E')->getText()->createTextRun('Student Weighting / Max Weighting');

                // Proportion of assessed "Pass" criteria achieved
                $sheet->writeString($row, 'F', get_string('reports:passsummary:header:propassach', 'block_gradetracker'));
                $sheet->mergeCells( $row, 'F', $row, 'I');

                // Proportion of weighted criteria
                $sheet->writeString($row, 'J',  get_string('reports:passsummary:header:propwtach', 'block_gradetracker'));
                $sheet->mergeCells( $row, 'J', $row, 'M');

                $sheet->applyRangeFormat('A', $row, 'M', $row, $formats['qual']);

                $row++;

                // Status headers
                $sheet->writeString($row, 'F', '≥85', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT - 1));
                $sheet->writeString($row, 'G', '≥70', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD - 1));
                $sheet->writeString($row, 'H', '≥50', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR - 1));
                $sheet->writeString($row, 'I', '<50', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD - 1));
                $sheet->writeString($row, 'J', '≥85', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT - 1));
                $sheet->writeString($row, 'K', '≥70', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD - 1));
                $sheet->writeString($row, 'L', '≥50', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR - 1));
                $sheet->writeString($row, 'M', '<50', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD - 1));

                $row++;
                // End of status headers

                $courses = $cat->getCourses();
                if ($courses) {
                    foreach ($courses as $course) {

                        $quals = $course->getCourseQualifications();
                        if ($quals) {

                            foreach ($quals as $qual) {

                                // If we chose a specific qual structure, make sure this qual is that one, otherwise skip
                                if ($qualStructure && $qual->getStructureID() <> $qualStructure->getID()) {
                                    continue;
                                }

                                if (array_key_exists($qual->getStructureID(), $structures)) {

                                    $structure = $structures[$qual->getStructureID()];
                                    $method = $structureSettings[$qual->getStructureID()]['pass_method'];
                                    $methodValue = $structureSettings[$qual->getStructureID()]['pass_method_value'];
                                    $view = $structureSettings[$qual->getStructureID()]['view'];
                                    $nameWeightings = $structureSettings[$qual->getStructureID()]['weightings'];

                                } else {

                                    $structure = $qual->getStructure();
                                    $method = $structure->getSetting('reporting_pass_criteria_method');
                                    $methodValue = $structure->getSetting('reporting_pass_criteria_method_value');
                                    $view = $structure->getSetting('custom_dashboard_view');
                                    $nameWeightings = json_decode($structure->getSetting('reporting_short_criteria_weighted_scores'));

                                    $structures[$qual->getStructureID()] = $structure;
                                    $structureSettings[$qual->getStructureID()]['view'] = $view;
                                    $structureSettings[$qual->getStructureID()]['weightings'] = $nameWeightings;
                                    $structureSettings[$qual->getStructureID()]['pass_method'] = $method;
                                    $structureSettings[$qual->getStructureID()]['pass_method_value'] = $methodValue;

                                }

                                // Make sure this structure hasn't set to disabled
                                if ($method === false || is_null($method)) {
                                    continue;
                                }

                                $shortCriteriaNames = false;
                                if ($view == 'view-criteria-short') {
                                    $shortCriteriaNames = $qual->getHeaderCriteriaNamesShort();
                                }

                                // Invalid method
                                if ($method == 'byletter' && !$shortCriteriaNames) {
                                    $method = 'all';
                                }

                                $sheet->writeString($row, 'A', $course->getNameWithCategory());
                                $sheet->writeString($row, 'B', $qual->getDisplayName());

                                $dataQualification = new \block_gradetracker\Qualification\DataQualification($qual->getID());
                                $data = $dataQualification->getQualificationReportStudents(false, $view, false, $shortCriteriaNames, $course->id, $specificAwards);

                                $cntStudents = count($data);
                                $sheet->writeString($row, 'C', $cntStudents);

                                if ($data) {

                                    // Calculate totals
                                    $gradingStructureCriteriaCountArray = array();
                                    $critTotals = array();
                                    $maxArray = array();
                                    $studentWeightings = array();
                                    $studentPassArray = array();
                                    $studentPassPercentageArray = array();
                                    $totalPass = 0;
                                    $maxPass = 0;
                                    $maxWeightedScore = 0;

                                    // Max Weighted Score
                                    if ($shortCriteriaNames) {
                                        foreach ($shortCriteriaNames as $crit) {

                                            $fieldName = 'critawardcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);
                                            $fieldNameTtl = 'critcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);

                                            // Get the highest value of this field in the results data
                                            $maxArray[$crit] = $this->getMaxValue($fieldName, $data);
                                            $critWeighting = $this->getCriteriaNameWeighting($crit, $nameWeightings);
                                            $maxWeightedScore += ($critWeighting * $maxArray[$crit]);

                                        }

                                    } else {
                                        $maxWeightedScore = null;
                                    }

                                    // Status Array for the Proportion of Weighted Criteria Achived
                                    $statusArray = array(
                                        \block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD => 0,
                                        \block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR => 0,
                                        \block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD => 0,
                                        \block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT => 0
                                    );

                                    $passStatusArray = array(
                                        \block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD => 0,
                                        \block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR => 0,
                                        \block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD => 0,
                                        \block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT => 0
                                    );

                                    foreach ($data as $student) {

                                        $max = 0;
                                        $studentWeighting = 0;
                                        $studentPass = 0;
                                        $studentPassPercentageBest = 0;

                                        // Add up total weighting, if it has short criteria names
                                        if ($shortCriteriaNames) {
                                            foreach ($shortCriteriaNames as $crit) {

                                                $fieldName = 'critawardcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);
                                                $fieldNameTtl = 'critcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);

                                                $met = (int)$student->$fieldName;
                                                $critTotal = (int)$student->$fieldNameTtl;

                                                if (!array_key_exists($crit, $critTotals)) {
                                                    $critTotals[$crit] = 0;
                                                }

                                                $critTotals[$crit] += $critTotal;

                                                // Student Weightings
                                                $weighting = $this->getCriteriaNameWeighting($crit, $structureSettings[$qual->getStructureID()]['weightings']);
                                                $studentWeighting += ( $met * $weighting );

                                            }

                                        }

                                        // Total student weighting
                                        $studentWeightings[$student->id] = $studentWeighting;

                                        // First need to work out what constitutes a "Pass" criteria
                                        // By First Letter
                                        if ($method == 'byletter') {

                                            $firstLetterArray = explode(",", $methodValue);
                                            if ($firstLetterArray) {
                                                foreach ($firstLetterArray as $firstLetter) {

                                                    // Get the field name for this student's count
                                                    $fieldName = 'critawardcnt_'.\gt_make_db_field_safe($firstLetter, $usedFieldNames);
                                                    $fieldNameTtl = 'critcnt_'.\gt_make_db_field_safe($firstLetter, $usedFieldNames);

                                                    $met = (int)@$student->$fieldName;
                                                    $critTotal = (int)@$student->$fieldNameTtl;

                                                    // Increment this student's met total for this "Pass" criterion letter
                                                    $studentPass += $met;

                                                    // Look for the max for this letter as well
                                                    $maxPassLetter = $this->getMaxValue($fieldName, $data);
                                                    $max += (int)$maxPassLetter;

                                                    // Increment the total number of Pass criteria of this letter
                                                    $totalPass += $critTotal;

                                                }
                                            }

                                            $studentPassPercentageBest = ($max > 0) ? (float)round( @($studentPass / $max) * 100, 1 ) : 100;
                                            $studentPassPercentageArray[$student->id] = $studentPassPercentageBest;

                                        } else if ($method == 'all') {

                                            $studentPass += (int)$student->critawardcnt_all; # Total of all criteria the student has achieved
                                            $totalPass += (int)$student->critcnt_all; # Total of all criteria the student is attached to

                                            // Look for the max for this letter as well
                                            $maxPassLetter = $this->getMaxValue('critawardcnt_all', $data);
                                            $max += (int)$maxPassLetter;

                                            $studentPassPercentageBest = ($max > 0) ? (float)round( @($studentPass / $max) * 100, 1 ) : 100;
                                            $studentPassPercentageArray[$student->id] = $studentPassPercentageBest;

                                        } else if ($method == 'bygradestructure') {

                                            $gradeStructureIDs = explode(",", $methodValue);
                                            if ($gradeStructureIDs) {

                                                $studentMet = 0;

                                                foreach ($gradeStructureIDs as $gradeStructureID) {

                                                    // Count the total criteria on this qual with this grading structure ID
                                                    if (array_key_exists($gradeStructureID, $gradingStructureCriteriaCountArray)) {
                                                        $critTotal = $gradingStructureCriteriaCountArray[$gradeStructureID];
                                                    } else {
                                                        $critTotal = $dataQualification->countCriteriaByGradingStructureID($gradeStructureID);
                                                        $gradingStructureCriteriaCountArray[$gradeStructureID] = $critTotal;
                                                    }

                                                    $totalPass += $critTotal;

                                                    // Now count the number of these criteria this student has passed
                                                    $met = $dataQualification->countCriteriaAwardsByGradingStructure($gradeStructureID, $student->id);
                                                    $studentPass += $met;
                                                    $studentMet += $met;

                                                }

                                                $studentPassArray[$student->id] = $studentPass; # Total number of "Pass" criteria achieved

                                                if ($studentMet > $max) {
                                                    $max = $studentMet;
                                                }

                                            }

                                        }

                                        if ($max > $maxPass) {
                                            $maxPass = $max;
                                        }

                                    }

                                    // Now if we were doing by grade structure, need to loop through students again
                                    // As in the previous loop we worked out the best pass score, now we can divide by it
                                    if ($method == 'bygradestructure') {

                                        foreach ($data as $student)
                                        {
                                            $studentPassPercentageBest = ($maxPass > 0) ? (float)round( @($studentPassArray[$student->id] / $maxPass) * 100, 1 ) : 100;
                                            $studentPassPercentageArray[$student->id] = $studentPassPercentageBest;
                                        }

                                    }

                                    // Pass - Total
                                    $passTotal = round( @($totalPass / $cntStudents), 1 );
                                    $passTotalPercentage = (float)round( @($maxPass / $passTotal) * 100, 1 );
                                    $sheet->writeString($row, 'D', $passTotalPercentage . '%', $this->getPercentageStyle($passTotalPercentage));
                                    $sheet->getComment($row, 'D')->getText()->createTextRun($maxPass . '/' . $passTotal);

                                    // All - Best
                                    $totalWeighting = 0;
                                    if ($shortCriteriaNames) {
                                        foreach ($shortCriteriaNames as $crit) {
                                            // It does an average because there is no "total" number of criteria on the qual
                                            // As students may be on differrent combinations of units, with different numbers of criteria
                                            $avgTotal = ceil( @($critTotals[$crit] / $cntStudents) );
                                            $weighting = $this->getCriteriaNameWeighting($crit, $nameWeightings);
                                            $totalWeighting += ($weighting * $avgTotal);
                                        }

                                    }

                                    $maxWeightingTotalPercentage = (!is_null($maxWeightedScore)) ? (float)round( @($maxWeightedScore / $totalWeighting) * 100, 1 ) : get_string('na', 'block_gradetracker');
                                    $sheet->writeString($row, 'E', $maxWeightingTotalPercentage . ( (!is_null($maxWeightedScore)) ? '%' : '' ), $this->getPercentageStyle($maxWeightingTotalPercentage));
                                    $sheet->getComment($row, 'E')->getText()->createTextRun($maxWeightedScore . '/' . $totalWeighting);

                                    foreach ($data as $student) {

                                        // This student's weighting
                                        $weighting = $studentWeightings[$student->id];

                                        // Calculate percentage based on the best
                                        $studentWeightingPercentage = ($maxWeightedScore > 0) ? (int)round( @($weighting / $maxWeightedScore) * 100 ) : 100;

                                        // Add to status array
                                        if ($studentWeightingPercentage < \block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD) {
                                            $statusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD]++;
                                        } else if ($studentWeightingPercentage < \block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR) {
                                            $statusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR]++;
                                        } else if ($studentWeightingPercentage < \block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD) {
                                            $statusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD]++;
                                        } else if ($studentWeightingPercentage <= \block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT) {
                                            $statusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT]++;
                                        }

                                        // Pass Status
                                        $studentPassPercentageBest = @$studentPassPercentageArray[$student->id];

                                        if ($studentPassPercentageBest < \block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD) {
                                            $passStatusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD]++;
                                        } else if ($studentPassPercentageBest < \block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR) {
                                            $passStatusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR]++;
                                        } else if ($studentPassPercentageBest < \block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD) {
                                            $passStatusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD]++;
                                        } else if ($studentPassPercentageBest <= \block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT) {
                                            $passStatusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT]++;
                                        }

                                    }

                                    // Pass Status columns
                                    $letter = 'F';
                                    $percent = round( @($passStatusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT] / $cntStudents) * 100, 1 );
                                    $sheet->writeString($row, $letter, $percent . '%', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT - 1) );
                                    $letter++;

                                    $percent = round( @($passStatusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD] / $cntStudents) * 100, 1 );
                                    $sheet->writeString($row, $letter, $percent . '%', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD - 1));
                                    $letter++;

                                    $percent = round( @($passStatusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR] / $cntStudents) * 100, 1 );
                                    $sheet->writeString($row, $letter, $percent . '%', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR - 1));
                                    $letter++;

                                    $percent = round( @($passStatusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD] / $cntStudents) * 100, 1 );
                                    $sheet->writeString($row, $letter, $percent . '%', $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD - 1));
                                    $letter++;

                                    // Status columns
                                    $percent = round( @($statusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT] / $cntStudents) * 100, 1 ) . '%';
                                    if (!$shortCriteriaNames) {
                                        $percent = get_string('na', 'block_gradetracker');
                                    }
                                    $sheet->writeString($row, $letter, $percent);
                                    if ($shortCriteriaNames) {
                                        $sheet->applyFormat($row, $letter, $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_EXCELLENT - 1) );
                                    }
                                    $letter++;

                                    $percent = round( @($statusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD] / $cntStudents) * 100, 1 ) . '%';
                                    if (!$shortCriteriaNames) {
                                        $percent = get_string('na', 'block_gradetracker');
                                    }
                                    $sheet->writeString($row, $letter, $percent);
                                    if ($shortCriteriaNames) {
                                        $sheet->applyFormat($row, $letter, $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_GOOD - 1) );
                                    }
                                    $letter++;

                                    $percent = round( @($statusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR] / $cntStudents) * 100, 1 ) . '%';
                                    if (!$shortCriteriaNames) {
                                        $percent = get_string('na', 'block_gradetracker');
                                    }
                                    $sheet->writeString($row, $letter, $percent);
                                    if ($shortCriteriaNames) {
                                        $sheet->applyFormat($row, $letter, $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_POOR - 1) );
                                    }
                                    $letter++;

                                    $percent = round( @($statusArray[\block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD] / $cntStudents) * 100, 1 ) . '%';
                                    if (!$shortCriteriaNames) {
                                        $percent = get_string('na', 'block_gradetracker');
                                    }
                                    $sheet->writeString($row, $letter, $percent);
                                    if ($shortCriteriaNames) {
                                        $sheet->applyFormat($row, $letter, $this->getPercentageStyle(\block_gradetracker\Reports\CriteriaProgressReport::STATUS_BAD - 1) );
                                    }
                                    $letter++;

                                }

                                $row++;

                            }

                        }

                        $done++;

                        // Flush out progress
                        $progress = round(($done / $total) * 100, 1);
                        \gt_ajax_progress('pending', array(
                            'progress' => $progress,
                            'time' => time()
                        ));

                    }
                }

                // Autosize columns
                $lastColumn = $sheet->getWorksheet()->getHighestColumn();
                for ($col = 'A'; $col <= $lastColumn; $col++) {
                    $sheet->getWorksheet()->getColumnDimension($col)->setAutoSize(true);
                }

            }
        }

        // End the Spreadsheet generation and save it
        \gt_create_data_directory('reports');
        $file = \block_gradetracker\GradeTracker::dataroot() . '/reports/' . $filename;
        $objPHPExcel->save($file);

        $download = \gt_create_data_path_code($file);

        // Finished
        \gt_ajax_progress(true, array(
            'file' => $download,
            'time' => time()
        ));

    }

}
