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
 * Criteria Progress Report
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
namespace GT\Reports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

defined('MOODLE_INTERNAL') or die();

require_once('Report.class.php');

/**
 * Description of CriteriaProgressReport
 *
 * @author cwarwicker
 */
class CriteriaProgressReport extends \GT\Reports\Report {

    const STATUS_EXCELLENT = 100;
    const STATUS_GOOD = 85;
    const STATUS_POOR = 70;
    const STATUS_BAD = 50;

    protected $name = 'CRIT_PROGRESS';

    public function run( array $params ) {

        global $CFG, $USER;

        $User = new \GT\User($USER->id);
        $params = (isset($params['params'])) ? $params['params'] : false;

        // Make sure qual structure has been passed through
        $qualStructure = false;
        $structureID = $this->extractParam("structureID", $params);
        if ($structureID != 'all') {
            $qualStructure = new \GT\QualificationStructure($structureID);
            if (!$qualStructure->isValid()) {
                gt_ajax_progress(false, array(
                    'error' => get_string('errors:invalidqualstructure', 'block_gradetracker')
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
        $filename = 'CriteriaProgressReport_' . $User->id . '.xlsx';
        $precision = ini_get('precision');
        $objPHPExcel = new \GT\Excel($filename);
        ini_set('precision', $precision); # PHPExcel fucks up the native round() function by changing the precision

        $objPHPExcel->getSpreadsheet()->getProperties()
            ->setCreator($User->getDisplayName())
            ->setLastModifiedBy($User->getDisplayName())
            ->setTitle( get_string('reports:critprog', 'block_gradetracker') )
            ->setSubject( get_string('reports:critprog', 'block_gradetracker') )
            ->setDescription( get_string('reports:critprog', 'block_gradetracker') . " " . get_string('generatedbygt', 'block_gradetracker'))
            ->setCustomProperty( "GT-REPORT" , $this->name, 's');

        $formats = array();
        $formats['centre'] = $objPHPExcel->add_format(['align' => 'center']);
        $formats['qual'] = $objPHPExcel->add_format(['bg_color' => '#538DD5', 'color' => '#ffffff', 'bold' => 1]);
        $formats['course'] = $objPHPExcel->add_format(['bg_color' => '#ffff33', 'color' => '#000000', 'bold' => 1]);
        $formats['totals'] = $objPHPExcel->add_format(['bg_color' => '#B1A0C7', 'color' => '#ffffff']);
        $formats['max'] = $objPHPExcel->add_format(['bg_color' => '#948A54', 'color' => '#ffffff']);

        $GTEXE = \GT\Execution::getInstance();
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

                $Cat = new \GT\CourseCategory($catID);
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

                // Create a sheet for this category.
                $sheet = $objPHPExcel->addWorksheet( $this->convertStringToWorksheetName($cat->name) );

                $sheetIndex++;
                $row = 0;

                $courses = $cat->getCourses();
                if ($courses) {
                    foreach ($courses as $course) {

                        // Course name
                        $sheet->writeString($row, Coordinate::columnIndexFromString('A'), $course->getNameWithCategory());
                        $sheet->mergeCells($row, Coordinate::columnIndexFromString('A'), $row, Coordinate::columnIndexFromString('C'));

                        $courseRow = $row;
                        $row++;

                        // Get qualifications on course
                        $quals = $course->getCourseQualifications();
                        if ($quals) {

                            foreach ($quals as $qual) {

                                // If we chose a specific qual structure, make sure this qual is that one, otherwise skip
                                if ($qualStructure && $qual->getStructureID() <> $qualStructure->getID()) {
                                    continue;
                                }

                                // Get settings for this qual
                                if (array_key_exists($qual->getStructureID(), $structures)) {
                                    $structure = $structures[$qual->getStructureID()];
                                    $view = $structureSettings[$qual->getStructureID()]['view'];
                                    $nameWeightings = $structureSettings[$qual->getStructureID()]['weightings'];
                                } else {

                                    $structure = $qual->getStructure();
                                    $view = $structure->getSetting('custom_dashboard_view');
                                    $nameWeightings = json_decode($structure->getSetting('reporting_short_criteria_weighted_scores'));

                                    $structures[$qual->getStructureID()] = $structure;
                                    $structureSettings[$qual->getStructureID()]['view'] = $view;
                                    $structureSettings[$qual->getStructureID()]['weightings'] = $nameWeightings;

                                }

                                $studentWeightings = array();

                                // Row for Qual name and Criteria headers

                                // Qual Name
                                $sheet->writeString($row, Coordinate::columnIndexFromString('A'), $qual->getDisplayName());
                                $sheet->mergeCells($row, Coordinate::columnIndexFromString('A'), $row, Coordinate::columnIndexFromString('C'));

                                // Reset from qual to qual
                                $shortCriteriaNames = false;

                                // Column for %
                                if ($view == 'view-criteria-short') {

                                    $letter = 'E';

                                    // Columns for criteria
                                    $shortCriteriaNames = $qual->getHeaderCriteriaNamesShort();
                                    if ($shortCriteriaNames) {
                                        foreach ($shortCriteriaNames as $crit) {
                                            $oldLetter = $letter;
                                            $sheet->writeString($row, Coordinate::columnIndexFromString($letter), $crit);
                                            $letter++;

                                            // Centre align
                                            $sheet->applyFormat($row, Coordinate::columnIndexFromString($oldLetter), $formats['centre']);

                                        }
                                    }

                                    // Weighted Score
                                    $sheet->writeString($row, Coordinate::columnIndexFromString($letter), get_string('weighting', 'block_gradetracker'));

                                    // Style
                                    $sheet->applyRangeFormat(Coordinate::columnIndexFromString('A'), $row, Coordinate::columnIndexFromString($letter), $row, $formats['qual']);
                                    $sheet->applyRangeFormat(Coordinate::columnIndexFromString('A'), $courseRow, Coordinate::columnIndexFromString($letter), $courseRow, $formats['course']);

                                }

                                $row++;

                                // Get reporting data, as we are using the same data as the dashboard reporting
                                if ($view == 'view-criteria-short') {

                                    $dataQualification = new \GT\Qualification\DataQualification($qual->getID());
                                    $data = $dataQualification->getQualificationReportStudents(false, $view, false, $shortCriteriaNames, $course->id, $specificAwards);
                                    if ($data) {

                                        // Totals row instance
                                        $totalsRow = $row;
                                        $row++;
                                        // End of Totals row instance

                                        // Maximums row
                                        $maxRow = $row;
                                        $max = array();
                                        $maxWeightedScore = 0; # Score
                                        $critTotals = array();
                                        $statusArray = array(
                                            self::STATUS_BAD => 0,
                                            self::STATUS_POOR => 0,
                                            self::STATUS_GOOD => 0,
                                            self::STATUS_EXCELLENT => 0
                                        );
                                        $letter = 'E';

                                        if ($shortCriteriaNames) {
                                            foreach ($shortCriteriaNames as $crit) {

                                                // Get the field name used in the query
                                                $fieldName = 'critawardcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);

                                                // Get the highest value of this field in the results data
                                                $max[$crit] = $this->getMaxValue($fieldName, $data);
                                                $critWeighting = $this->getCriteriaNameWeighting($crit, $structureSettings[$qual->getStructureID()]['weightings']);
                                                $maxWeightedScore += ($critWeighting * $max[$crit]);

                                                // Set into worksheet
                                                $sheet->writeString($row, Coordinate::columnIndexFromString($letter), $max[$crit], ['bold' => true]);

                                                $letter++;

                                            }
                                        }

                                        // Maximum weighting, based on the maximum of each of those criteria
                                        $sheet->writeString($row, Coordinate::columnIndexFromString($letter), $maxWeightedScore);

                                        // Style the maximums row
                                        $sheet->applyRangeFormat(Coordinate::columnIndexFromString('E'), $row, Coordinate::columnIndexFromString($letter), $row, $formats['max']);
                                        // End of Maximums Row

                                        // Student row
                                        $row++;

                                        $startStudentRow = $row;

                                        foreach ($data as $student) {

                                            // Weighted variables for counting
                                            $studentWeightingMax = 0;
                                            $studentWeighting = 0;

                                            // Name
                                            $sheet->writeString($row, Coordinate::columnIndexFromString('A'), $student->firstname);
                                            $sheet->writeString($row, Coordinate::columnIndexFromString('B'), $student->lastname);
                                            $sheet->writeString($row, Coordinate::columnIndexFromString('C'), $student->username);

                                            // Criteria
                                            $letter = 'E';

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

                                                    $weighting = $this->getCriteriaNameWeighting($crit, $structureSettings[$qual->getStructureID()]['weightings']);
                                                    $studentWeightingMax += ( $critTotal * $weighting );
                                                    $studentWeighting += ( $met * $weighting );

                                                    $sheet->writeString($row, Coordinate::columnIndexFromString($letter), $met, ['bold' => true]);
                                                    $letter++;

                                                }
                                            }

                                            // Total student weighting
                                            $studentWeightings[$student->id] = $studentWeighting;
                                            $sheet->writeString($row, Coordinate::columnIndexFromString($letter), $studentWeighting);

                                            $row++;

                                        }

                                        // End of student row

                                        // Back to the Totals row
                                        // Now that we've added up the totals for all the students, get an avg
                                        $cntStudents = count($data);
                                        $totalWeighting = 0;
                                        $letter = 'E';
                                        if ($shortCriteriaNames) {
                                            foreach ($shortCriteriaNames as $crit) {
                                                $avgTotal = ceil( @($critTotals[$crit] / $cntStudents) );
                                                $weighting = $this->getCriteriaNameWeighting($crit, $structureSettings[$qual->getStructureID()]['weightings']);
                                                $totalWeighting += ($weighting * $avgTotal);

                                                $sheet->writeString($totalsRow, Coordinate::columnIndexFromString($letter), $avgTotal);
                                                $letter++;
                                            }
                                        }

                                        // Total weighting
                                        $sheet->writeString($totalsRow, Coordinate::columnIndexFromString($letter), $totalWeighting);

                                        // Style totals row
                                        $sheet->applyRangeFormat(Coordinate::columnIndexFromString('E'), $totalsRow, Coordinate::columnIndexFromString($letter), $totalsRow, $formats['totals']);
                                        // End of Totals Row

                                        // Maximum weighting percentage of total weighting (max row)
                                        $letter++;
                                        $maxWeightingTotalPercentage = (int)round( @($maxWeightedScore / $totalWeighting) * 100 );
                                        $sheet->writeString($maxRow, Coordinate::columnIndexFromString($letter), $maxWeightingTotalPercentage . '%');
                                        $sheet->applyRangeFormat(Coordinate::columnIndexFromString($letter), $maxRow, null, null, $this->getPercentageStyle($maxWeightingTotalPercentage));

                                        // Now work out each student's weighted percentage against the maxWeightedScore
                                        // Not the weighted score of everything they could have achieved, it's against the max
                                        // that has been achieved by any student on the qual
                                        $redoRow = $startStudentRow;

                                        foreach ($data as $student) {

                                            // This student's weighting
                                            $weighting = $studentWeightings[$student->id];

                                            // Calculate percentage based on the best
                                            $studentWeightingPercentage = ($maxWeightedScore > 0) ? (int)round( @($weighting / $maxWeightedScore) * 100 ) : 100;
                                            $sheet->writeString($redoRow, Coordinate::columnIndexFromString('D'), $studentWeightingPercentage . '%');
                                            $sheet->applyRangeFormat(Coordinate::columnIndexFromString('D'), $redoRow, null, null, $this->getPercentageStyle($studentWeightingPercentage));

                                            // Add to status array
                                            if ($studentWeightingPercentage < self::STATUS_BAD) {
                                                $statusArray[self::STATUS_BAD]++;
                                            } else if ($studentWeightingPercentage < self::STATUS_POOR) {
                                                $statusArray[self::STATUS_POOR]++;
                                            } else if ($studentWeightingPercentage < self::STATUS_GOOD) {
                                                $statusArray[self::STATUS_GOOD]++;
                                            } else if ($studentWeightingPercentage <= self::STATUS_EXCELLENT) {
                                                $statusArray[self::STATUS_EXCELLENT]++;
                                            }

                                            // Their percentage complete of the whole qual (total weight)
                                            $studentWeightingPercentageTotal = (int)round( @($weighting / $totalWeighting) * 100 );
                                            $sheet->writeString($redoRow, Coordinate::columnIndexFromString($letter), $studentWeightingPercentageTotal . '%');
                                            $sheet->applyRangeFormat(Coordinate::columnIndexFromString($letter), $redoRow, null, null, $this->getPercentageStyle($studentWeightingPercentageTotal));

                                            $redoRow++;

                                        }

                                        // Status columns
                                        $letter++;
                                        $percent = round( @($statusArray[self::STATUS_EXCELLENT] / $cntStudents) * 100, 1 );
                                        $sheet->writeString($maxRow, Coordinate::columnIndexFromString($letter), $percent . '%');
                                        $sheet->applyRangeFormat(Coordinate::columnIndexFromString($letter), $maxRow, null, null, $this->getPercentageStyle(self::STATUS_EXCELLENT - 1));

                                        $letter++;
                                        $percent = round( @($statusArray[self::STATUS_GOOD] / $cntStudents) * 100, 1 );
                                        $sheet->writeString($maxRow, Coordinate::columnIndexFromString($letter), $percent . '%');
                                        $sheet->applyRangeFormat(Coordinate::columnIndexFromString($letter), $maxRow, null, null, $this->getPercentageStyle(self::STATUS_GOOD - 1));

                                        $letter++;
                                        $percent = round( @($statusArray[self::STATUS_POOR] / $cntStudents) * 100, 1 );
                                        $sheet->writeString($maxRow, Coordinate::columnIndexFromString($letter), $percent . '%');
                                        $sheet->applyRangeFormat(Coordinate::columnIndexFromString($letter), $maxRow, null, null, $this->getPercentageStyle(self::STATUS_POOR - 1));

                                        $letter++;
                                        $percent = round( @($statusArray[self::STATUS_BAD] / $cntStudents) * 100, 1 );
                                        $sheet->writeString($maxRow, Coordinate::columnIndexFromString($letter), $percent . '%');
                                        $sheet->applyRangeFormat(Coordinate::columnIndexFromString($letter), $maxRow, null, null, $this->getPercentageStyle(self::STATUS_BAD - 1));

                                    }

                                }

                            }

                        }

                        // Increment number completed
                        $done++;

                        // Flush out progress
                        $progress = round(($done / $total) * 100, 1);
                        \gt_ajax_progress('pending', array(
                            'progress' => $progress,
                            'time' => time()
                        ));

                        $row++;

                    }
                }

                // Autosize columns
                $lastColumn = $sheet->getWorksheet()->getHighestColumn();
                for ($col = 'A'; $col <= $lastColumn; $col++) {
                    $sheet->getWorksheet()->getColumnDimension($col)->setAutoSize(true);
                }

            }
        }

        // End the Spreadsheet generation and save it.
        \gt_create_data_directory('reports');
        $file = \GT\GradeTracker::dataroot() . '/reports/' . $filename;
        $objPHPExcel->save($file);

        $download = \gt_create_data_path_code($file);

        // Finished
        \gt_ajax_progress(true, array(
            'file' => $download,
            'time' => time()
        ));

    }

}
