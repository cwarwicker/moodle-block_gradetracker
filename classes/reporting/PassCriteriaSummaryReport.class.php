<?php

namespace GT\Reports;

require_once 'Report.class.php';

/**
 * Description of PassCriteriaProgressReport
 *
 * @author cwarwicker
 */
class PassCriteriaSummaryReport extends \GT\Reports\Report {
    
    protected $name = 'PASS_CRIT_SUMMARY';
    
    public function run(array $params) {
        
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
                     ->setTitle( get_string('reports:passsummary', 'block_gradetracker') )
                     ->setSubject( get_string('reports:passsummary', 'block_gradetracker') )
                     ->setDescription( get_string('reports:passsummary', 'block_gradetracker') . " " . get_string('generatedbygt', 'block_gradetracker'))
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
                
                // Headers
                $objPHPExcel->getActiveSheet()->setCellValue("A{$row}", get_string('course'));                    
                $objPHPExcel->getActiveSheet()->setCellValue("B{$row}", get_string('qualification', 'block_gradetracker'));                    
                $objPHPExcel->getActiveSheet()->setCellValue("C{$row}", get_string('students'));                    
                
                // "Pass" criteria achieved based on total on qual (This is the Pass - Total column on the PassProg report)
                $objPHPExcel->getActiveSheet()->setCellValue("D{$row}", get_string('reports:passprog:header:passcritachievedtotal', 'block_gradetracker'));                    
                $objPHPExcel->getActiveSheet()->getComment("D{$row}")->getText()->createTextRun('Student "Pass" No. / Total "Pass" No.');

                // Percentage of weighted total assessed (This is the All - Best column on the PassProg report)
                $objPHPExcel->getActiveSheet()->setCellValue("E{$row}", get_string('reports:passprog:header:weightedscoreachieved', 'block_gradetracker'));                    
                $objPHPExcel->getActiveSheet()->getComment("E{$row}")->getText()->createTextRun('Student Weighting / Max Weighting');
                
                // Proportion of assessed "Pass" criteria achieved
                $objPHPExcel->getActiveSheet()->setCellValue("F{$row}", get_string('reports:passsummary:header:propassach', 'block_gradetracker'));                    
                $objPHPExcel->getActiveSheet()->mergeCells("F{$row}:I{$row}");

                // Proportion of weighted criteria
                $objPHPExcel->getActiveSheet()->setCellValue("J{$row}", get_string('reports:passsummary:header:propwtach', 'block_gradetracker'));                    
                $objPHPExcel->getActiveSheet()->mergeCells("J{$row}:M{$row}");
                
                $objPHPExcel->getActiveSheet()->getStyle("A{$row}:M{$row}")->applyFromArray($styles['qual']);        
                
                $row++;
                
                // Status headers
                $objPHPExcel->getActiveSheet()->setCellValue("F{$row}", '≥85');      
                $objPHPExcel->getActiveSheet()->getStyle("F{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT - 1) );        
                $objPHPExcel->getActiveSheet()->setCellValue("G{$row}", '≥70'); 
                $objPHPExcel->getActiveSheet()->getStyle("G{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_GOOD - 1) );        
                $objPHPExcel->getActiveSheet()->setCellValue("H{$row}", '≥50');  
                $objPHPExcel->getActiveSheet()->getStyle("H{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_POOR - 1) );        
                $objPHPExcel->getActiveSheet()->setCellValue("I{$row}", '<50');   
                $objPHPExcel->getActiveSheet()->getStyle("I{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_BAD - 1) );        

                $objPHPExcel->getActiveSheet()->setCellValue("J{$row}", '≥85');      
                $objPHPExcel->getActiveSheet()->getStyle("J{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT - 1) );        
                $objPHPExcel->getActiveSheet()->setCellValue("K{$row}", '≥70'); 
                $objPHPExcel->getActiveSheet()->getStyle("K{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_GOOD - 1) );        
                $objPHPExcel->getActiveSheet()->setCellValue("L{$row}", '≥50');  
                $objPHPExcel->getActiveSheet()->getStyle("L{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_POOR - 1) );        
                $objPHPExcel->getActiveSheet()->setCellValue("M{$row}", '<50');   
                $objPHPExcel->getActiveSheet()->getStyle("M{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_BAD - 1) );        

                $row++;
                // End of status headers
                
                
                
                
                $courses = $cat->getCourses();
                if ($courses)
                {
                    foreach($courses as $course)
                    {
                                                
                        $quals = $course->getCourseQualifications();
                        if ($quals)
                        {
                            
                            foreach($quals as $qual)
                            {
                                
                                // If we chose a specific qual structure, make sure this qual is that one, otherwise skip
                                if ($qualStructure && $qual->getStructureID() <> $qualStructure->getID()){
                                    continue;
                                }
                                
                                
                                if (array_key_exists($qual->getStructureID(), $structures)){
                                    
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
                                if ($method === false || is_null($method)){
                                    continue;
                                }
                                
                                $shortCriteriaNames = false;
                                if ($view == 'view-criteria-short')
                                {
                                    $shortCriteriaNames = $qual->getHeaderCriteriaNamesShort();
                                }
                                
                                // Invalid method
                                if ($method == 'byletter' && !$shortCriteriaNames){
                                    $method = 'all';
                                }
                                
                                $objPHPExcel->getActiveSheet()->setCellValue("A{$row}", $course->getNameWithCategory());                    
                                $objPHPExcel->getActiveSheet()->setCellValue("B{$row}", $qual->getDisplayName());                    
                                
                                $dataQualification = new \GT\Qualification\DataQualification($qual->getID());
                                $data = $dataQualification->getQualificationReportStudents(false, $view, false, $shortCriteriaNames, $course->id, $specificAwards);
                                
                                $cntStudents = count($data);
                                $objPHPExcel->getActiveSheet()->setCellValue("C{$row}", $cntStudents);                    
                                
                                if ($data)
                                {
                                    
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
                                    if ($shortCriteriaNames)
                                    {
                                        foreach($shortCriteriaNames as $crit)
                                        {

                                            $fieldName = 'critawardcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);
                                            $fieldNameTtl = 'critcnt_'.\gt_make_db_field_safe($crit, $usedFieldNames);

                                            // Get the highest value of this field in the results data
                                            $maxArray[$crit] = $this->getMaxValue($fieldName, $data);
                                            $critWeighting = $this->getCriteriaNameWeighting($crit, $nameWeightings);
                                            $maxWeightedScore += ($critWeighting * $maxArray[$crit]);

                                        }                                            

                                    }
                                    else
                                    {
                                        $maxWeightedScore = null;
                                    }
                                    
                                    
                                    
                                    // Status Array for the Proportion of Weighted Criteria Achived
                                    $statusArray = array(
                                        \GT\Reports\CriteriaProgressReport::STATUS_BAD => 0,
                                        \GT\Reports\CriteriaProgressReport::STATUS_POOR => 0,
                                        \GT\Reports\CriteriaProgressReport::STATUS_GOOD => 0,
                                        \GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT => 0
                                    );
                                    
                                    $passStatusArray = array(
                                        \GT\Reports\CriteriaProgressReport::STATUS_BAD => 0,
                                        \GT\Reports\CriteriaProgressReport::STATUS_POOR => 0,
                                        \GT\Reports\CriteriaProgressReport::STATUS_GOOD => 0,
                                        \GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT => 0
                                    );

                                    
                                    
                                    foreach($data as $student)
                                    {
                                        
                                        $max = 0;
                                        $studentWeighting = 0;
                                        $studentPass = 0;
                                        $studentPassPercentageBest = 0;
                                        
                                        // Add up total weighting, if it has short criteria names
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
                                                
                                                // Student Weightings
                                                $weighting = $this->getCriteriaNameWeighting($crit, $structureSettings[$qual->getStructureID()]['weightings']);
                                                $studentWeighting += ( $met * $weighting );

                                            }     
                                            
                                            // Total student weighting
                                            $studentWeightings[$student->id] = $studentWeighting;
                                                                                        
                                        }
                                        
                                        
                                        
                                        // First need to work out what constitutes a "Pass" criteria
                                        // By First Letter
                                        if ($method == 'byletter'){
                                                                                        
                                            $firstLetterArray = explode(",", $methodValue);
                                            if ($firstLetterArray){
                                                foreach($firstLetterArray as $firstLetter){
                                                    
                                                    // Get the field name for this student's count
                                                    $fieldName = 'critawardcnt_'.\gt_make_db_field_safe($firstLetter, $usedFieldNames);
                                                    $fieldNameTtl = 'critcnt_'.\gt_make_db_field_safe($firstLetter, $usedFieldNames);

                                                    $met = (int)$student->$fieldName;
                                                    $critTotal = (int)$student->$fieldNameTtl;
                                                                                                                                                         
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
                                            
                                        } 
                                        
                                        elseif ($method == 'all'){
                                            
                                            $studentPass += (int)$student->critawardcnt_all; # Total of all criteria the student has achieved
                                            $totalPass += (int)$student->critcnt_all; # Total of all criteria the student is attached to
                                            
                                            // Look for the max for this letter as well
                                            $maxPassLetter = $this->getMaxValue('critawardcnt_all', $data);
                                            $max += (int)$maxPassLetter;
                                            
                                            $studentPassPercentageBest = ($max > 0) ? (float)round( @($studentPass / $max) * 100, 1 ) : 100;
                                            $studentPassPercentageArray[$student->id] = $studentPassPercentageBest;

                                        }
                                        
                                        elseif ($method == 'bygradestructure'){
                                            
                                            $gradeStructureIDs = explode(",", $methodValue);
                                            if ($gradeStructureIDs){
                                                
                                                $studentMet = 0;
                                                
                                                foreach($gradeStructureIDs as $gradeStructureID){
                                                    
                                                    // Count the total criteria on this qual with this grading structure ID
                                                    if (array_key_exists($gradeStructureID, $gradingStructureCriteriaCountArray)){
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
                                                
                                                if ($studentMet > $max){
                                                    $max = $studentMet;
                                                }
                                                
                                                
                                            }
                                                                                        
                                        }
                                        
                                        if ($max > $maxPass){
                                            $maxPass = $max;
                                        }
                                        
                                    }
                                    
                                    
                                     // Now if we were doing by grade structure, need to loop through students again
                                    // As in the previous loop we worked out the best pass score, now we can divide by it
                                    if ($method == 'bygradestructure')
                                    {
                                                                                
                                        foreach($data as $student)
                                        {
                                            $studentPassPercentageBest = ($maxPass > 0) ? (float)round( @($studentPassArray[$student->id] / $maxPass) * 100, 1 ) : 100;
                                            $studentPassPercentageArray[$student->id] = $studentPassPercentageBest;
                                        }
                                        
                                    }
                                    
                                    
                                    
                                    
                                    
                                    
                                    // Pass - Total
                                    $passTotal = round( @($totalPass / $cntStudents), 1 );
                                    $passTotalPercentage = (float)round( @($maxPass / $passTotal) * 100, 1 );
                                    $objPHPExcel->getActiveSheet()->setCellValue("D{$row}", "{$passTotalPercentage}%");       
                                    $objPHPExcel->getActiveSheet()->getComment("D{$row}")->getText()->createTextRun($maxPass . '/' . $passTotal);
                                    $objPHPExcel->getActiveSheet()->getStyle("D{$row}")->applyFromArray( $this->getPercentageStyle($passTotalPercentage) );        
                                    
                                    // All - Best
                                    $totalWeighting = 0;
                                    if ($shortCriteriaNames)
                                    {
                                        foreach($shortCriteriaNames as $crit)
                                        {
                                            // It does an average because there is no "total" number of criteria on the qual
                                            // As students may be on differrent combinations of units, with different numbers of criteria
                                            $avgTotal = ceil( @($critTotals[$crit] / $cntStudents) );
                                            $weighting = $this->getCriteriaNameWeighting($crit, $nameWeightings);
                                            $totalWeighting += ($weighting * $avgTotal);
                                        }                                        
                                        
                                    }
                                    
                                    $maxWeightingTotalPercentage = (!is_null($maxWeightedScore)) ? (float)round( @($maxWeightedScore / $totalWeighting) * 100, 1 ) : get_string('na', 'block_gradetracker');
                                    $objPHPExcel->getActiveSheet()->setCellValue("E{$row}", $maxWeightingTotalPercentage . ( (!is_null($maxWeightedScore)) ? '%' : '' ));       
                                    $objPHPExcel->getActiveSheet()->getComment("E{$row}")->getText()->createTextRun($maxWeightedScore . '/' . $totalWeighting);
                                    $objPHPExcel->getActiveSheet()->getStyle("E{$row}")->applyFromArray( $this->getPercentageStyle($maxWeightingTotalPercentage) );        
                                    
                                    foreach($data as $student)
                                    {
                                        
                                        // This student's weighting
                                        $weighting = $studentWeightings[$student->id];

                                        // Calculate percentage based on the best
                                        $studentWeightingPercentage = ($maxWeightedScore > 0) ? (int)round( @($weighting / $maxWeightedScore) * 100 ) : 100;
                                                                                
                                        // Add to status array
                                        if ($studentWeightingPercentage < \GT\Reports\CriteriaProgressReport::STATUS_BAD){
                                            $statusArray[\GT\Reports\CriteriaProgressReport::STATUS_BAD]++;
                                        } elseif ($studentWeightingPercentage < \GT\Reports\CriteriaProgressReport::STATUS_POOR){
                                            $statusArray[\GT\Reports\CriteriaProgressReport::STATUS_POOR]++;
                                        } elseif ($studentWeightingPercentage < \GT\Reports\CriteriaProgressReport::STATUS_GOOD){
                                            $statusArray[\GT\Reports\CriteriaProgressReport::STATUS_GOOD]++;
                                        } elseif ($studentWeightingPercentage <= \GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT){
                                            $statusArray[\GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT]++;
                                        }
                                        
                                        // Pass Status
                                        $studentPassPercentageBest = $studentPassPercentageArray[$student->id];
                                        
                                        if ($studentPassPercentageBest < \GT\Reports\CriteriaProgressReport::STATUS_BAD){
                                            $passStatusArray[\GT\Reports\CriteriaProgressReport::STATUS_BAD]++;
                                        } elseif ($studentPassPercentageBest < \GT\Reports\CriteriaProgressReport::STATUS_POOR){
                                            $passStatusArray[\GT\Reports\CriteriaProgressReport::STATUS_POOR]++;
                                        } elseif ($studentPassPercentageBest < \GT\Reports\CriteriaProgressReport::STATUS_GOOD){
                                            $passStatusArray[\GT\Reports\CriteriaProgressReport::STATUS_GOOD]++;
                                        } elseif ($studentPassPercentageBest <= \GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT){
                                            $passStatusArray[\GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT]++;
                                        }
                                        
                                    }
                                        
                                    
                                    // Pass Status columns
                                    $letter = 'F';
                                    $percent = round( @($passStatusArray[\GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT] / $cntStudents) * 100, 1 );
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $percent . '%');
                                    $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT - 1) );     
                                    $letter++;
                                    
                                    $percent = round( @($passStatusArray[\GT\Reports\CriteriaProgressReport::STATUS_GOOD] / $cntStudents) * 100, 1 );
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $percent . '%');
                                    $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_GOOD - 1) );     
                                    $letter++;
                                    
                                    $percent = round( @($passStatusArray[\GT\Reports\CriteriaProgressReport::STATUS_POOR] / $cntStudents) * 100, 1 );
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $percent . '%');
                                    $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_POOR - 1) );     
                                    $letter++;
                                    
                                    $percent = round( @($passStatusArray[\GT\Reports\CriteriaProgressReport::STATUS_BAD] / $cntStudents) * 100, 1 );
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $percent . '%');
                                    $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_BAD - 1) );     
                                    $letter++;
                                    
                                    
                                    
                                    
                                    // Status columns
                                    $percent = round( @($statusArray[\GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT] / $cntStudents) * 100, 1 ) . '%';
                                    if (!$shortCriteriaNames){
                                        $percent = get_string('na', 'block_gradetracker');
                                    }
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $percent);
                                    if ($shortCriteriaNames){
                                        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_EXCELLENT - 1) );     
                                    }
                                    $letter++;
                                    
                                    $percent = round( @($statusArray[\GT\Reports\CriteriaProgressReport::STATUS_GOOD] / $cntStudents) * 100, 1 ) . '%';
                                    if (!$shortCriteriaNames){
                                        $percent = get_string('na', 'block_gradetracker');
                                    }
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $percent);
                                    if ($shortCriteriaNames){
                                        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_GOOD - 1) );     
                                    }
                                    $letter++;
                                    
                                    $percent = round( @($statusArray[\GT\Reports\CriteriaProgressReport::STATUS_POOR] / $cntStudents) * 100, 1 ) . '%';
                                    if (!$shortCriteriaNames){
                                        $percent = get_string('na', 'block_gradetracker');
                                    }
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $percent);
                                    if ($shortCriteriaNames){
                                        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_POOR - 1) );     
                                    }
                                    $letter++;
                                    
                                    $percent = round( @($statusArray[\GT\Reports\CriteriaProgressReport::STATUS_BAD] / $cntStudents) * 100, 1 ) . '%';
                                    if (!$shortCriteriaNames){
                                        $percent = get_string('na', 'block_gradetracker');
                                    }
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$row}", $percent);
                                    if ($shortCriteriaNames){
                                        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$row}")->applyFromArray( $this->getPercentageStyle(\GT\Reports\CriteriaProgressReport::STATUS_BAD - 1) );     
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
        
        $file = \GT\GradeTracker::dataroot() . '/reports/PassCriteriaSummaryReport_' . $User->id . '.xlsx';
        $objWriter->save( $file );
        $download = \gt_create_data_path_code($file);
                
        // Finished
        \gt_ajax_progress(true, array(
            'file' => $download,
            'time' => time()
        ));
        
    }
    
}
