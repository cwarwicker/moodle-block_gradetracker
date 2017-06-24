<?php

namespace GT\Reports;

require_once 'Report.class.php';

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
    
    public function run( array $params ){
                
        global $CFG, $USER;
        
        $User = new \GT\User($USER->id);
        $params = (isset($params['params'])) ? $params['params'] : false;
        
        // Make sure qual structure has been passed through
        $qualStructure = false;
        $structureID = $this->extractParam("structureID", $params);
        if ($structureID != 'all'){
            $qualStructure = new \GT\QualificationStructure($structureID);
            if (!$qualStructure->isValid()){
                gt_ajax_progress(false, array(
                    'error' => get_string('blockbcgtdata:err:invalidqualstructure', 'block_gradetracker')
                ));
                return false;
            }
        }
        
        // Make sure at least 1 course category selected
        $cats = $this->extractParam("categories", $params);
        if (!is_array($cats) || empty($cats)){
            gt_ajax_progress(false, array(
                'error' => get_string('invalidcoursecat', 'block_gradetracker')
            ));
            return false;
        }
        
        // Are we looking for any specific criteria awards as well?
        $specificAwards = $this->extractParam('extraAwardNames', $params); 
        
        // Setup the PHPExcel object
        require_once $CFG->dirroot . '/lib/phpexcel/PHPExcel.php';
        
        // Setup Spreadsheet
        $precision = ini_get('precision');
        $objPHPExcel = new \PHPExcel();
        ini_set('precision', $precision); # PHPExcel fucks up the native round() function by changing the precision
        
        
        $objPHPExcel->getProperties()
                     ->setCreator($User->getDisplayName())
                     ->setLastModifiedBy($User->getDisplayName())
                     ->setTitle( get_string('reports:critprog', 'block_gradetracker') )
                     ->setSubject( get_string('reports:critprog', 'block_gradetracker') )
                     ->setDescription( get_string('reports:critprog', 'block_gradetracker') . " " . get_string('generatedbygt', 'block_gradetracker'))
                     ->setCustomProperty( "GT-REPORT" , $this->name, 's');


        // Remove default sheet
        $objPHPExcel->removeSheetByIndex(0);
        
        $styles = array(
            'centre' => array(
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                )
            ), 
            'qual' => array(
                'fill' => array(
                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '538DD5')
                ),
                'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => 'ffffff')
                )
            ),
            'course' => array(
                'fill' => array(
                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => 'ffff33')
                ),
                'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => '000000')
                )
            ),
            'totals' => array(
                'fill' => array(
                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => 'B1A0C7')
                ),
                'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => 'ffffff')
                )
            ),
            'max' => array(
                'fill' => array(
                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '948A54')
                ),
                'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => 'ffffff')
                )
            )
            
        );
        

        $GTEXE = \GT\Execution::getInstance();
        $GTEXE->COURSE_CAT_MIN_LOAD = true;
        $GTEXE->QUAL_MIN_LOAD = true;
        
        $structures = array();
        $structureSettings = array();
        
        $catArray = array();
        $total = 0;
        $usedFieldNames = array();
        
        // Calculate how many courses we are going to need to process, so we can do a progress bar
        if ($cats)
        {
            foreach($cats as $catID)
            {
                
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
                
        if ($catArray)
        {
            foreach($catArray as $cat)
            {
                
                // Create a sheet for this category
                $objPHPExcel->createSheet($sheetIndex);
                $objPHPExcel->setActiveSheetIndex($sheetIndex);
                $objPHPExcel->getActiveSheet()->setTitle( $this->convertStringToWorksheetName($cat->name) );
                
                $sheetIndex++;
                $row = 1;
                
                $courses = $cat->getCourses();
                if ($courses)
                {
                    foreach($courses as $course)
                    {
                                                
                        // Course name
                        $objPHPExcel->getActiveSheet()->setCellValue("A{$row}", $course->getNameWithCategory());                    
                        $objPHPExcel->getActiveSheet()->mergeCells("A{$row}:C{$row}");

                        $courseRow = $row;
                        $row++;
                        
                        // Get qualifications on course
                        $quals = $course->getCourseQualifications();
                        if ($quals)
                        {
                            
                            foreach($quals as $qual)
                            {
                                
                                // If we chose a specific qual structure, make sure this qual is that one, otherwise skip
                                if ($qualStructure && $qual->getStructureID() <> $qualStructure->getID()){
                                    continue;
                                }
                                
                                // Get settings for this qual
                                if (array_key_exists($qual->getStructureID(), $structures)){
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
                                $objPHPExcel->getActiveSheet()->setCellValue("A{$row}", $qual->getDisplayName());                    
                                $objPHPExcel->getActiveSheet()->mergeCells("A{$row}:C{$row}");
                                
                                // Reset from qual to qual
                                $shortCriteriaNames = false;
                                
                                // Column for %
                                if ($view == 'view-criteria-short')
                                {

                                    $letter = 'E';

                                    // Columns for criteria
                                    $shortCriteriaNames = $qual->getHeaderCriteriaNamesShort();
                                    if ($shortCriteriaNames)
                                    {
                                        foreach($shortCriteriaNames as $crit)
                                        {
                                            $oldLetter = $letter;
                                            $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $crit);
                                            $letter++;

                                            // Merge into 2 cells
//                                            $objPHPExcel->getActiveSheet()->mergeCells("{$oldLetter}{$row}:{$letter}{$row}");
//                                            $letter++;

                                            // Centre align
                                            $objPHPExcel->getActiveSheet()->getStyle("{$oldLetter}{$row}")->applyFromArray($styles['centre']);

                                        }
                                    }
                                    
                                    // Weighted Score
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", get_string('weighting', 'block_gradetracker'));

                                    // Style
                                    $objPHPExcel->getActiveSheet()->getStyle("A{$row}:{$letter}{$row}")->applyFromArray($styles['qual']);        
                                    $objPHPExcel->getActiveSheet()->getStyle("A{$courseRow}:{$letter}{$courseRow}")->applyFromArray($styles['course']);        
                                                                        
                                   
                                    
                                }
                                               
                                $row++;
                                                                
                                
                                // Get reporting data, as we are using the same data as the dashboard reporting
                                if ($view == 'view-criteria-short')
                                {
                                    
                                    $dataQualification = new \GT\Qualification\DataQualification($qual->getID());
                                    $data = $dataQualification->getQualificationReportStudents(false, $view, false, $shortCriteriaNames, $course->id, $specificAwards);
                                    if ($data)
                                    {
                                        
                                        
                                        
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
                                        
                                        if ($shortCriteriaNames)
                                        {
                                            foreach($shortCriteriaNames as $crit)
                                            {
                                                                                                
                                                // Get the field name used in the query
                                                $fieldName = 'critawardcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);
                                                
                                                // Get the highest value of this field in the results data
                                                $max[$crit] = $this->getMaxValue($fieldName, $data);
                                                $critWeighting = $this->getCriteriaNameWeighting($crit, $structureSettings[$qual->getStructureID()]['weightings']);
                                                $maxWeightedScore += ($critWeighting * $max[$crit]);
                                                
                                                // Set into worksheet
                                                $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $max[$crit]);  
                                                $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->getFont()->setBold(true);

                                                $letter++;
                                                
                                            }
                                        }
                                        
                                        // Maximum weighting, based on the maximum of each of those criteria
                                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $maxWeightedScore); 
                                        
                                        // Style the maximums row
                                        $objPHPExcel->getActiveSheet()->getStyle("E{$row}:{$letter}{$row}")->applyFromArray($styles['max']);        
                                        // End of Maximums Row
                                        
                                        
                                        
                                        
                                        
                                        // Student row
                                        $row++;
                                        
                                        $startStudentRow = $row;
                                        
                                        foreach($data as $student)
                                        {
                                            
                                            // Weighted variables for counting
                                            $studentWeightingMax = 0;
                                            $studentWeighting = 0;
                                            
                                            // Name
                                            $objPHPExcel->getActiveSheet()->setCellValue("A{$row}", $student->firstname);  
                                            $objPHPExcel->getActiveSheet()->setCellValue("B{$row}", $student->lastname);  
                                            $objPHPExcel->getActiveSheet()->setCellValue("C{$row}", $student->username);  
                                            
                                            // Criteria 
                                            $letter = 'E';
                                            
                                            if ($shortCriteriaNames)
                                            {
                                                foreach($shortCriteriaNames as $crit)
                                                {
                                                    
                                                    $fieldName = 'critawardcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);
                                                    $fieldNameTtl = 'critcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);
                                                    
                                                    $met = (int)$student->$fieldName;
                                                    $critTotal = (int)$student->$fieldNameTtl;
                                                    
                                                    if (!array_key_exists($crit, $critTotals)){
                                                        $critTotals[$crit] = 0;
                                                    }
                                                    
                                                    $critTotals[$crit] += $critTotal;
                                                    
                                                    $weighting = $this->getCriteriaNameWeighting($crit, $structureSettings[$qual->getStructureID()]['weightings']);
                                                    $studentWeightingMax += ( $critTotal * $weighting );
                                                    $studentWeighting += ( $met * $weighting );
                                                    
                                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $met);
                                                    $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->getFont()->setBold(true);
                                                    $letter++;
                                                                                                        
                                                }
                                            }
                                            
                                            // Total student weighting
                                            $studentWeightings[$student->id] = $studentWeighting;
                                            $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $studentWeighting);
                                                                                        
                                            $row++;

                                        }
                                        
                                        // End of student row
                                        
                                        
                                        
                                        
                                        
                                        // Back to the Totals row
                                        // Now that we've added up the totals for all the students, get an avg
                                        $cntStudents = count($data);
                                        $totalWeighting = 0;
                                        $letter = 'E';
                                        if ($shortCriteriaNames)
                                        {
                                            foreach($shortCriteriaNames as $crit)
                                            {
                                                $avgTotal = ceil( @($critTotals[$crit] / $cntStudents) );
                                                $weighting = $this->getCriteriaNameWeighting($crit, $structureSettings[$qual->getStructureID()]['weightings']);
                                                $totalWeighting += ($weighting * $avgTotal);
                                                
                                                $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$totalsRow}", $avgTotal);
                                                $letter++;
                                            }
                                        }
                                        
                                        // Total weighting
                                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$totalsRow}", $totalWeighting);

                                        // Style totals row
                                        $objPHPExcel->getActiveSheet()->getStyle("E{$totalsRow}:{$letter}{$totalsRow}")->applyFromArray($styles['totals']);        
                                        // End of Totals Row
                                        
                                        
                                        
                                        
                                        // Maximum weighting percentage of total weighting (max row)
                                        $letter++;
                                        $maxWeightingTotalPercentage = (int)round( @($maxWeightedScore / $totalWeighting) * 100 );
                                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$maxRow}", $maxWeightingTotalPercentage . '%');
                                        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$maxRow}")->applyFromArray( $this->getPercentageStyle($maxWeightingTotalPercentage) );        
                                        
                                                                                
                                        
                                        
                                        // Now work out each student's weighted percentage against the maxWeightedScore 
                                        // Not the weighted score of everything they could have achieved, it's against the max
                                        // that has been achieved by any student on the qual
                                        $redoRow = $startStudentRow;

                                        foreach($data as $student)
                                        {
                                                                                        
                                            // This student's weighting
                                            $weighting = $studentWeightings[$student->id];
                                                                                        
                                            // Calculate percentage based on the best
                                            $studentWeightingPercentage = ($maxWeightedScore > 0) ? (int)round( @($weighting / $maxWeightedScore) * 100 ) : 100;
                                            $objPHPExcel->getActiveSheet()->setCellValue("D{$redoRow}", $studentWeightingPercentage . '%');
                                            $objPHPExcel->getActiveSheet()->getStyle("D{$redoRow}")->applyFromArray( $this->getPercentageStyle($studentWeightingPercentage) );        

                                            // Add to status array
                                            if ($studentWeightingPercentage < self::STATUS_BAD){
                                                $statusArray[self::STATUS_BAD]++;
                                            } elseif ($studentWeightingPercentage < self::STATUS_POOR){
                                                $statusArray[self::STATUS_POOR]++;
                                            } elseif ($studentWeightingPercentage < self::STATUS_GOOD){
                                                $statusArray[self::STATUS_GOOD]++;
                                            } elseif ($studentWeightingPercentage <= self::STATUS_EXCELLENT){
                                                $statusArray[self::STATUS_EXCELLENT]++;
                                            }
                                            
                                            // Their percentage complete of the whole qual (total weight)
                                            $studentWeightingPercentageTotal = (int)round( @($weighting / $totalWeighting) * 100 );
                                            $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$redoRow}", $studentWeightingPercentageTotal . '%');
                                            $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$redoRow}")->applyFromArray( $this->getPercentageStyle($studentWeightingPercentageTotal) );     
                                            
                                            $redoRow++;
                                                                   
                                        }
                                        
                                        
                                        
                                        
                                        
                                        // Status columns
                                        $letter++;
                                        $percent = round( @($statusArray[self::STATUS_EXCELLENT] / $cntStudents) * 100, 1 );
                                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$maxRow}", $percent . '%');
                                        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$maxRow}")->applyFromArray( $this->getPercentageStyle(self::STATUS_EXCELLENT - 1) );     
                                        
                                        $letter++;
                                        $percent = round( @($statusArray[self::STATUS_GOOD] / $cntStudents) * 100, 1 );
                                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$maxRow}", $percent . '%');
                                        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$maxRow}")->applyFromArray( $this->getPercentageStyle(self::STATUS_GOOD - 1) );     
                                        
                                        $letter++;
                                        $percent = round( @($statusArray[self::STATUS_POOR] / $cntStudents) * 100, 1 );
                                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$maxRow}", $percent . '%');
                                        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$maxRow}")->applyFromArray( $this->getPercentageStyle(self::STATUS_POOR - 1) );     
                                        
                                        $letter++;
                                        $percent = round( @($statusArray[self::STATUS_BAD] / $cntStudents) * 100, 1 );
                                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$maxRow}", $percent . '%');
                                        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$maxRow}")->applyFromArray( $this->getPercentageStyle(self::STATUS_BAD - 1) );     
                                        
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
                $lastColumn = $objPHPExcel->setActiveSheetIndex( ($sheetIndex - 1) )->getHighestColumn();
                for ($col = 'A'; $col <= $lastColumn; $col++)
                {
                    $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
                }
                
            }
        }
        
        // End the Spreadsheet generation and save it
        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        
        \gt_create_data_directory('reports');
        
        $file = \GT\GradeTracker::dataroot() . '/reports/CriteriaProgressReport_' . $User->id . '.xlsx';
        $objWriter->save( $file );
        $download = \gt_create_data_path_code($file);
                
        // Finished
        \gt_ajax_progress(true, array(
            'file' => $download,
            'time' => time()
        ));
        
    }
    
    
    
    
    
}
