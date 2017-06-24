<?php

namespace GT;

/**
 * Description of DataImport
 *
 * @author cwarwicker
 */
class DataImport {
   
    private $qualID;
    private $unitID;
    private $studentID;
    
    private $file = false;
    private $errors = array();
    public $errCnt = 0;
    private $output = '';
    
    public function __construct($file) {
        
        $this->file = $file;
        
    }
    
    public function getQualID(){
        return $this->qualID;
    }
    
    public function setQualID($id){
        $this->qualID = $id;
        return $this;
    }
    
    public function getUnitID(){
        return $this->unitID;
    }
    
    public function setUnitID($id){
        $this->unitID = $id;
        return $this;
    }
    
    public function getStudentID(){
        return $this->studentID;
    }
    
    public function setStudentID($id){
        $this->studentID = $id;
        return $this;
    }
    
    public function getErrors(){
        return $this->errors;
    }
    
    public function getOutput(){
        return $this->output;
    }
    
    public function runImportQualsOnEntry()
    {
        
        $options = (isset($_POST['options'])) ? $_POST['options'] : false;
        
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fInfo, $this->file['tmp_name']);
        finfo_close($fInfo);
                
        // Has to be csv file, otherwise error and return
        if ($mime != 'text/csv' && $mime != 'text/plain'){
            $this->errors[] = sprintf( get_string('errors:import:mimetype', 'block_gradetracker'), 'text/csv or text/plain', $mime );
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
        
        // Compare headers
        $headerRow = fgetcsv($fh);
        $headers = \GT\CSV\Template::$headersQOE;
        
        if ($headerRow !== $headers){
            $this->errors[] = sprintf( get_string('errors:import:headers', 'block_gradetracker'), implode(', ', $headers), implode(', ', $headerRow) );
            return false;
        }
        
        $userArray = array();
        $wipedUsersArray = array();
        $i = 0;
        $err = 0;
        
        while( ($row = fgetcsv($fh)) !== false )
        {
            
            $i++;
            
            $row = array_map('trim', $row);
            
            $username = $row[0];
            $subject = $row[1];
            $qual = $row[2];
            $level = $row[3];
            $grade = $row[4];
            $year = $row[5];
            
            // Check fields are not empty
            if ( empty($username) || empty($subject) ){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:emptyfield', 'block_gradetracker') . " - " . implode(', ', $row) . "<br>";
                $err++;
                continue;
            } 
            
            // Strip GCSE out of name
            $this->stripQoENames($subject);            
            
            // Check valid user
            $user = \GT\User::byUsername($username);
            if (!$user || !$user->isValid()){
                
                // If we want to insert them
                if (isset($options['ins_users'])){
                    
                    if ($obj = \create_user_record($username, 'password')){
                        $user = new \GT\User($obj->id);
                        $this->output .= "[{$i}] " . sprintf( get_string('import:createduser', 'block_gradetracker'), $username, 'password' ) . "<br>";
                    } else {
                        $this->output .= "[{$i}] ERR: " . get_string('errors:import:createuser', 'block_gradetracker') . " - {$username}<br>";
                        $err++;
                        continue;
                    }
                    
                } else {
                    $this->output .= "[{$i}] ERR: " . get_string('errors:import:invaliduser', 'block_gradetracker') . " - {$username}<br>";
                    $err++;
                    continue;
                }
                
            }
            
            $userArray[$user->id] = $user;
            
            // Should we wipe their data?
            if (isset($options['wipe_user_data']) && !in_array($user->id, $wipedUsersArray)){
                
                // Wipe their data
                \GT\QualOnEntry::deleteUsersData($user->id);
                
                // Output message
                $this->output .= "[{$i}] " . sprintf( get_string('import:qoe:wipeduserdata', 'block_gradetracker'), $username ) . "<br>";
                
                // Add to array to stop it happening again during this import
                $wipedUsersArray[] = $user->id;
                
            }
            
            
            
            // Subject
            $subjectID = \GT\QualOnEntry::getSubject($subject);
            if (!$subjectID){
                
                if (isset($options['ins_subjects'])){
                    $subjectID = \GT\QualOnEntry::createSubject($subject);
                    $this->output .= "[{$i}] " . sprintf( get_string('import:qoe:createdsubject', 'block_gradetracker'), $subject ) . "<br>";
                } else {
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:qoe:invalidsubject', 'block_gradetracker'), $subject) . "<br>";
                    $err++;
                    continue;
                }
                
            }
            
            
            // Qual type
            $qualID = \GT\QualOnEntry::getQual($qual, $level);
            if (!$qualID){
                
                if (isset($options['ins_quals'])){
                    $qualID = \GT\QualOnEntry::createQual($qual, $level);
                    $this->output .= "[{$i}] " . sprintf( get_string('import:qoe:createdqual', 'block_gradetracker'), $qual, $level ) . "<br>";
                } else {
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:qoe:invalidqual', 'block_gradetracker'), $qual, $level) . "<br>";
                    $err++;
                    continue;
                }
                
            }
            
            
            
            // Grade
            $gradeID = \GT\QualOnEntry::getGrade($qualID, $grade);
            if (!$gradeID){
                
                if (isset($options['ins_grades'])){
                    $gradeID = \GT\QualOnEntry::createGrade($qualID, $grade);
                    $this->output .= "[{$i}] " . sprintf( get_string('import:qoe:createdgrade', 'block_gradetracker'), $grade, $qual, $level) . "<br>";
                } else {
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:qoe:invalidgrade', 'block_gradetracker'), $grade, $qual, $level) . "<br>";
                    $err++;
                    continue;
                }
                
            }
            
            
            
            // Now the student's record
            $record = \GT\QualOnEntry::getRecord($user->id, $subjectID);
            if ($record)
            {
                $record->setGradeID($gradeID);
                $record->setYear($year);
                $record->save();
            }
            else
            {
                $record = new \GT\QualOnEntry();
                $record->setUserID($user->id);
                $record->setGradeID($gradeID);
                $record->setSubjectID($subjectID);
                $record->setYear($year);
                $record->save();
            }
            
            $this->output .= "[{$i}] OK: " . sprintf( get_string('import:qoe:update', 'block_gradetracker'), $username, $qual, $level, $subject, $grade) . "<br>";
            
        }
        
        $this->output .= "<br>";       
        
        // Calculate Avg GCSE Scores
        if ($userArray)
        {
            foreach($userArray as $user)
            {
                
                $avg = $user->calculateAverageGCSEScore();
                $this->output .= sprintf( get_string('import:qoe:calcavggcse', 'block_gradetracker'), $user->getDisplayName(), $avg) . "<br>";
                
                // Calculate target grades as well?
                if (isset($options['calc_tg'])){
                    
                    $quals = $user->getQualifications("STUDENT");
                    if ($quals)
                    {
                        foreach($quals as $qual)
                        {
                            if ($qual->isFeatureEnabledByName('targetgradesauto'))
                            {
                                $award = $user->calculateTargetGrade($qual->getID());
                                if ($award){
                                    $this->output .= sprintf( get_string('import:qoe:settg', 'block_gradetracker'), $user->getDisplayName(), $qual->getDisplayName(), $award->getName()) . "<br>";
                                }
                            }
                        }
                    }
                    
                }
                
                
                // Weighted target grades
                if (isset($options['calc_wtg'])){
                    
                    $quals = $user->getQualifications("STUDENT");
                    if ($quals)
                    {
                        foreach($quals as $qual)
                        {
                            
                            // Only if this qual structure has this enabled
                            if ($qual->isFeatureEnabledByName('weightedtargetgrades'))
                            {
                                $weighted = $user->calculateWeightedTargetGrade($qual->getID());
                                if ($weighted) {
                                    $this->output .= sprintf( get_string('import:qoe:setwtg', 'block_gradetracker'), $user->getDisplayName(), $qual->getDisplayName(), $weighted->getName() ) . "<br>";
                                }                                
                            }
                        }
                    }
                }
                
                
                // Aspirational grades
                if (isset($options['calc_asp'])){
                    
                    $quals = $user->getQualifications("STUDENT");
                    if ($quals)
                    {
                        foreach($quals as $qual)
                        {
                            
                            // Only if this qual structure has this enabled
                            if ($qual->isFeatureEnabledByName('aspirationalgrades'))
                            {
                            
                                $aspirationalGrade = $user->calculateAspirationalGrade($qual->getID());
                                if ($aspirationalGrade){
                                    $this->output .= sprintf( get_string('import:qoe:setasp', 'block_gradetracker'), $user->getDisplayName(), $qual->getDisplayName(), $aspirationalGrade->getName() ) . "<br>";
                                }
                                
                            }
                        }
                    }
                }
            }
        }
        
        $this->errCnt = $err;
        
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_IMPORTED_QOE;
        $Log->afterjson = array(
            'data' => file_get_contents($this->file['tmp_name']),
            'post' => $_POST
        );
        $Log->save();
        // ------------ Logging Info
        
        fclose($fh);
        
        
    }
    
    public function runImportAvgGCSE()
    {
        
        $options = (isset($_POST['options'])) ? $_POST['options'] : false;
        
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fInfo, $this->file['tmp_name']);
        finfo_close($fInfo);
                
        // Has to be csv file, otherwise error and return
        if ($mime != 'text/csv' && $mime != 'text/plain'){
            $this->errors[] = sprintf( get_string('errors:import:mimetype', 'block_gradetracker'), 'text/csv or text/plain', $mime );
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
        
        // Compare headers
        $headerRow = fgetcsv($fh);
        $headers = \GT\CSV\Template::$headersAvgGCSE;
        
        if ($headerRow !== $headers){
            $this->errors[] = sprintf( get_string('errors:import:headers', 'block_gradetracker'), implode(', ', $headers), implode(', ', $headerRow) );
            return false;
        }
        
        
        
        $i = 0;
        $err = 0;
        
        while( ($row = fgetcsv($fh)) !== false )
        {
            
            $i++;
            
            $row = array_map('trim', $row);
            
            // Check fields are not empty
            if (\gt_is_empty($row[0]) || \gt_is_empty($row[1])){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:emptyfield', 'block_gradetracker') . " - " . implode(', ', $row) . "<br>";
                $err++;
                continue;
            } 
            
            $username = $row[0];
            $avgGcse = $row[1];
            
            // Check valid user
            $user = \GT\User::byUsername($username);
            if (!$user || !$user->isValid()){
                
                // If we want to insert them
                if (isset($options['ins_users'])){
                    
                    if ($obj = \create_user_record($username, 'password')){
                        $user = new \GT\User($obj->id);
                        $this->output .= "[{$i}] " . sprintf( get_string('import:createduser', 'block_gradetracker'), $username, 'password' ) . "<br>";
                    } else {
                        $this->output .= "[{$i}] ERR: " . get_string('errors:import:createuser', 'block_gradetracker') . " - {$username}<br>";
                        $err++;
                        continue;
                    }
                    
                } else {
                    $this->output .= "[{$i}] ERR: " . get_string('errors:import:invaliduser', 'block_gradetracker') . " - {$username}<br>";
                    $err++;
                    continue;
                }
                
            }
            
                                    
            
            
            // Should we wipe their data?
            if (isset($options['wipe_user_data'])){
                
                // Wipe their data
                \GT\QualOnEntry::deleteUsersData($user->id);
                
                // Output message
                $this->output .= "[{$i}] " . sprintf( get_string('import:qoe:wipeduserdata', 'block_gradetracker'), $username ) . "<br>";
                                
            }
            
                        
            // Average GCSE score
            if ($avgGcse != '' && is_numeric($avgGcse)){
                
                if ($user->setAverageGCSEScore($avgGcse)){
                    $this->output .= "[{$i}] OK: " . sprintf( get_string('import:tg:avggcseupdated', 'block_gradetracker'), $username, $avgGcse ) . "<br>";
                } else {
                    $err++;
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:avggcseupdate', 'block_gradetracker'), $username ) . "<br>";
                    continue;
                }
                
            }
            
            
            // Get user's quals
            $quals = $user->getQualifications("STUDENT");
            
            
            // Target Grades for this user
            if (isset($options['calc_tg']))
            {
                if ($quals)
                {
                    foreach($quals as $qual)
                    {
                        if ($qual->isFeatureEnabledByName('targetgradesauto'))
                        {
                            $targetGrade = $user->calculateTargetGrade($qual->getID());
                            if ($targetGrade){
                                $this->output .= "[{$i}] OK: " . sprintf( get_string('import:qoe:settg', 'block_gradetracker'), $username, $qual->getDisplayName(), $targetGrade->getName() ) . "<br>";
                            } else {
                                $err++;
                                $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:update', 'block_gradetracker'), $username, $qual->getDisplayName() ) . "<br>";
                                continue;
                            }
                        }
                    }
                }
            }
            
            
            // Calculate Weighted TG
            if (isset($options['calc_wtg'])){
                
                if ($quals)
                {
                    foreach($quals as $qual)
                    {
                        if ($qual->isFeatureEnabledByName('weightedtargetgrades'))
                        {
                            $weighted = $user->calculateWeightedTargetGrade($qual->getID());
                            if ($weighted)
                            {
                                $this->output .= "[{$i}] OK: " . sprintf( get_string('import:qoe:setwtg', 'block_gradetracker'), $username, $qual->getDisplayName(), $weighted->getName() ) . "<br>";
                            }
                            else
                            {
                                $err++;
                                $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:wtgupdate', 'block_gradetracker'), $username, $qual->getDisplayName() ) . "<br>";
                                continue;
                            }
                        }
                    }
                }                
            }
            
            
            // Calculate aspirational
            if (isset($options['calc_asp'])){
                
                if ($quals)
                {
                    foreach($quals as $qual)
                    {
                        if ($qual->isFeatureEnabledByName('aspirationalgrades'))
                        {
                            $aspirationalGrade = $user->calculateAspirationalGrade($qual->getID());
                            if ($aspirationalGrade){
                                $this->output .= "[{$i}] OK: " . sprintf( get_string('import:qoe:setasp', 'block_gradetracker'), $username, $qual->getDisplayName(), $aspirationalGrade->getName() ) . "<br>";
                            } else {
                                $err++;
                                $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:aspupdate', 'block_gradetracker'), $username, $qual->getDisplayName() ) . "<br>";
                                continue;
                            }
                        }
                    }
                } 
                                                            
            }
                        
        }     
                
        $this->errCnt = $err;
        
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_IMPORTED_AVGGCSE;
        $Log->afterjson = array(
            'data' => file_get_contents($this->file['tmp_name']),
            'post' => $_POST
        );
        $Log->save();
        // ------------ Logging Info
        
        fclose($fh);
        
    }
    
    
    /**
     * Import the Target Grades from CSV
     * @return boolean
     */
    public function runImportTargetGrades()
    {
                                
        $options = (isset($_POST['options'])) ? $_POST['options'] : false;
        
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fInfo, $this->file['tmp_name']);
        finfo_close($fInfo);
                
        // Has to be csv file, otherwise error and return
        if ($mime != 'text/csv' && $mime != 'text/plain'){
            $this->errors[] = sprintf( get_string('errors:import:mimetype', 'block_gradetracker'), 'text/csv or text/plain', $mime );
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
        
        // Compare headers
        $headerRow = fgetcsv($fh);
        $headers = \GT\CSV\Template::$headersTargetGrades;
        
        if ($headerRow !== $headers){
            $this->errors[] = sprintf( get_string('errors:import:headers', 'block_gradetracker'), implode(', ', $headers), implode(', ', $headerRow) );
            return false;
        }
        
        
        
        $i = 0;
        $err = 0;
        
        while( ($row = fgetcsv($fh)) !== false )
        {
            
            $i++;
            
            $row = array_map('trim', $row);
            
            // Check fields are not empty
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:emptyfield', 'block_gradetracker') . " - " . implode(', ', $row) . "<br>";
                $err++;
                continue;
            } 
            
            $qualType = $row[0];
            $qualLevel = $row[1];
            $qualSubType = $row[2];
            $qualName = $row[3];
            $username = $row[4];
            $grade = $row[5];
            $avgGcse = $row[6];
            
            // Check valid user
            $user = \GT\User::byUsername($username);
            if (!$user || !$user->isValid()){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:invaliduser', 'block_gradetracker') . " - {$username}<br>";
                $err++;
                continue;
            }
            
            
            // Check valid qual
            $qual = \GT\Qualification::retrieve($qualType, $qualLevel, $qualSubType, $qualName);
            if (!$qual || !$qual->isValid()){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:invalidqual', 'block_gradetracker') . " - {$qualType} | {$qualLevel} | {$qualSubType} | {$qualName}<br>";
                $err++;
                continue;
            }
            
            
            // Check user is on qual
            if (!$user->isOnQual($qual->getID(), "STUDENT")){
                $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:studnotonqual', 'block_gradetracker'), $username, $qualName ) . "<br>";
                $err++;
                continue;
            }
            
            
            
            // Average GCSE score
            if ($avgGcse != '' && is_numeric($avgGcse)){
                
                if ($user->setAverageGCSEScore($avgGcse)){
                    $this->output .= "[{$i}] OK: " . sprintf( get_string('import:tg:avggcseupdated', 'block_gradetracker'), $username, $avgGcse ) . "<br>";
                } else {
                    $err++;
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:avggcseupdate', 'block_gradetracker'), $username ) . "<br>";
                    continue;
                }
                
            }
            
            
            // Target Grade for this qualification
                        
            // Check grade is valid, if it's not empty as this is optional, might just do an avg gcse score
            if (!empty($grade)){
                
                $award = $qual->getBuild()->getAwardByName($grade);
                if (!$award || !$award->isValid()){
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:invalidgrade', 'block_gradetracker'), $grade ) . "<br>";
                    $err++;
                    continue;
                }
                
                // At this point everything should be ok. The user is on the qual, the grade is valid.
                // So let's update the grade
                if ($user->setUserGrade('target', $award->getID(), array('qualID' => $qual->getID()))){
                    $this->output .= "[{$i}] OK: " . sprintf( get_string('import:tg:updated', 'block_gradetracker'), $username, $qualName, $grade ) . "<br>";
                } else {
                    $err++;
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:update', 'block_gradetracker'), $username, $qualName ) . "<br>";
                    continue;
                }
                
            } elseif (isset($options['calc_tg']) && $qual->isFeatureEnabledByName('targetgradesauto')) {
                
                // If we didn't specificy a grade but we did choose to calculate target grade
                // Try and calculate from avg GCSE score
                $targetGrade = $user->calculateTargetGrade($qual->getID());
                if ($targetGrade){
                    $this->output .= "[{$i}] OK: " . sprintf( get_string('import:qoe:settg', 'block_gradetracker'), $username, $qualName, $targetGrade->getName() ) . "<br>";
                } else {
                    $err++;
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:update', 'block_gradetracker'), $username, $qualName ) . "<br>";
                    continue;
                }
                
            }
            
            
            
            // Calculate Weighted TG
            if (isset($options['calc_wtg']) && $qual->isFeatureEnabledByName('weightedtargetgrades')){
                
                $weighted = $user->calculateWeightedTargetGrade($qual->getID());
                if ($weighted)
                {
                    $this->output .= "[{$i}] OK: " . sprintf( get_string('import:qoe:setwtg', 'block_gradetracker'), $username, $qualName, $weighted->getName() ) . "<br>";
                }
                else
                {
                    $err++;
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:wtgupdate', 'block_gradetracker'), $username, $qualName ) . "<br>";
                    continue;
                }
                
            }
            
            
            
            // Calculate aspirational
            if (isset($options['calc_asp']) && $qual->isFeatureEnabledByName('aspirationalgrades')){
                
                $aspirationalGrade = $user->calculateAspirationalGrade($qual->getID());
                if ($aspirationalGrade){
                    $this->output .= "[{$i}] OK: " . sprintf( get_string('import:qoe:setasp', 'block_gradetracker'), $username, $qualName, $aspirationalGrade->getName() ) . "<br>";
                } else {
                    $err++;
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:aspupdate', 'block_gradetracker'), $username, $qualName ) . "<br>";
                    continue;
                }
                                            
            }
                        
        }     
        
                
        $this->errCnt = $err;
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_IMPORTED_TARGET_GRADES;
        $Log->afterjson = array(
            'data' => file_get_contents($this->file['tmp_name']),
            'post' => $_POST
        );
        $Log->save();
        // ------------ Logging Info
        
        fclose($fh);
        
        
        
    }
    
    /**
     * Import the Aspirational Grades from CSV
     * @return boolean
     */
    public function runImportAspirationalGrades()
    {
                        
        $options = (isset($_POST['options'])) ? $_POST['options'] : false;
        
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fInfo, $this->file['tmp_name']);
        finfo_close($fInfo);
                
        // Has to be csv file, otherwise error and return
        if ($mime != 'text/csv' && $mime != 'text/plain'){
            $this->errors[] = sprintf( get_string('errors:import:mimetype', 'block_gradetracker'), 'text/csv or text/plain', $mime );
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
        
        // Compare headers
        $headerRow = fgetcsv($fh);
        $headers = \GT\CSV\Template::$headersAspirationalGrades;
        
        if ($headerRow !== $headers){
            $this->errors[] = sprintf( get_string('errors:import:headers', 'block_gradetracker'), implode(', ', $headers), implode(', ', $headerRow) );
            return false;
        }
        
        
        
        $i = 0;
        $err = 0;
        
        while( ($row = fgetcsv($fh)) !== false )
        {
            
            $i++;
            
            $row = array_map('trim', $row);
            
            // Check fields are not empty
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:emptyfield', 'block_gradetracker') . " - " . implode(', ', $row) . "<br>";
                $err++;
                continue;
            } 
            
            $qualType = $row[0];
            $qualLevel = $row[1];
            $qualSubType = $row[2];
            $qualName = $row[3];
            $username = $row[4];
            $aspGrade = $row[5];

            // Check valid user
            $user = \GT\User::byUsername($username);
            if (!$user || !$user->isValid()){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:invaliduser', 'block_gradetracker') . " - {$username}<br>";
                $err++;
                continue;
            }
            
            
            // Check valid qual
            $qual = \GT\Qualification::retrieve($qualType, $qualLevel, $qualSubType, $qualName);
            if (!$qual || !$qual->isValid()){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:invalidqual', 'block_gradetracker') . " - {$qualType} | {$qualLevel} | {$qualSubType} | {$qualName}<br>";
                $err++;
                continue;
            }
            
            
            // Check user is on qual
            if (!$user->isOnQual($qual->getID(), "STUDENT")){
                $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:studnotonqual', 'block_gradetracker'), $username, $qualName ) . "<br>";
                $err++;
                continue;
            }
            
            if (!empty($aspGrade)){
                
                $award = $qual->getBuild()->getAwardByName($aspGrade);
                if (!$award || !$award->isValid()){
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:invalidgrade', 'block_gradetracker'), $aspGrade ) . "<br>";
                    $err++;
                    continue;
                }
                
                // At this point everything should be ok. The user is on the qual, the grade is valid.
                // So let's update the grade
                if ($user->setUserGrade('aspirational', $award->getID(), array('qualID' => $qual->getID()))){
                    $this->output .= "[{$i}] OK: " . sprintf( get_string('import:tg:updated', 'block_gradetracker'), $username, $qualName, $aspGrade ) . "<br>";
                } else {
                    $err++;
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:update', 'block_gradetracker'), $username, $qualName ) . "<br>";
                    continue;
                }
                
            }
            
        }     
        
        $this->errCnt = $err;
        
         // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_IMPORTED_ASP_GRADES;
        $Log->afterjson = array(
            'data' => file_get_contents($this->file['tmp_name']),
            'post' => $_POST
        );
        $Log->save();
        // ------------ Logging Info
        
        fclose($fh);

    }
    
    /**
     * Import the Ceta Grades from CSV
     * @return boolean
     */
    public function runImportCETAGrades()
    {
        
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fInfo, $this->file['tmp_name']);
        finfo_close($fInfo);
                
        // Has to be csv file, otherwise error and return
        if ($mime != 'text/csv' && $mime != 'text/plain'){
            $this->errors[] = sprintf( get_string('errors:import:mimetype', 'block_gradetracker'), 'text/csv or text/plain', $mime );
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
        
        // Compare headers
        $headerRow = fgetcsv($fh);
        $headers = \GT\CSV\Template::$headersCetaGrades;
        
        if ($headerRow !== $headers){
            $this->errors[] = sprintf( get_string('errors:import:headers', 'block_gradetracker'), implode(', ', $headers), implode(', ', $headerRow) );
            return false;
        }

        $i = 0;
        $err = 0;
        
        while( ($row = fgetcsv($fh)) !== false )
        {
            
            $i++;
            $row = array_map('trim', $row);
            
            $params = array();
            $params['qualID'] = null;
            $params['courseID'] = null; 

            $name = '';
            $qualFamily = $row[0];
            $qualLevel = $row[1];
            $qualSubType = $row[2];
            $qualName = $row[3];
            $username = $row[4];
            $ceta = $row[5];
            $course = $row[6];

            // Check valid user
            $user = \GT\User::byUsername($username);
            if (!$user || !$user->isValid()){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:invaliduser', 'block_gradetracker') . " - {$username}<br>";
                $err++;
                continue;
            }
                
            // if the qual info submitted is not empty.
            if (!empty($qualFamily) && !empty($qualLevel) && !empty($qualSubType) && !empty($qualName) && !empty($username))
            {
                // Check valid qual
                $qual = \GT\Qualification::retrieve($qualFamily, $qualLevel, $qualSubType, $qualName);
                if (!$qual || !$qual->isValid()){
                    $this->output .= "[{$i}] ERR: " . get_string('errors:import:invalidqual', 'block_gradetracker') . " - {$qualFamily} | {$qualLevel} | {$qualSubType} | {$qualName}<br>";
                    $err++;
                    continue;
                }

                // Check user is on qual
                if (!$user->isOnQual($qual->getID(), "STUDENT")){
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:studnotonqual', 'block_gradetracker'), $username, $qualName ) . "<br>";
                    $err++;
                    continue;
                }

                if (!empty($ceta)){
                    $award = $qual->getBuild()->getAwardByName($ceta);
                    if ($award && $award->isValid())
                    {
                        $awardID = $award->getID();
                    }
                    else
                    {
                        $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:tg:invalidgrade', 'block_gradetracker'), $ceta ) . "<br>";
                        $err++;
                        continue;
                    }
                }
                
                $name = $qual->getShortDisplayName();
                $params['qualID'] = $qual->getID();

            }
            elseif (!empty($username) && !empty($course))
            {

                $byCourseMethod = $_POST['importoptions'];
                if ($byCourseMethod == 'importcourseshortname')
                {
                    $coursecheck = \GT\Course::retrieve('shortname', $course);
                }
                elseif ($byCourseMethod == 'importcourseid')
                {
                    $coursecheck = \GT\Course::retrieve('id', $course);
                }

                if($coursecheck && $coursecheck->isValid())
                {
                    $params['courseID'] = $coursecheck->id;
                }     
                else
                {
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:invalidcourse', 'block_gradetracker'), $course ) . "<br>";
                    $err++;
                    continue;                
                }
                
                $name = $coursecheck->getName();
                $awardID = $ceta;

            }
            
            else
            {
                $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:invaliddata', 'block_gradetracker'), $course ) . "<br>";
                $err++;
                continue;  
            }

            // At this point everything should be ok. The user is on the qual, the grade is valid.
            // So let's update the grade
            if (!is_null($params['qualID']) || !is_null($params['courseID']))
            {
                if ($user->setUserGrade('ceta', $awardID, $params))
                {
                    $this->output .= "[{$i}] OK: " . sprintf( get_string('import:cg:updated', 'block_gradetracker'), $username, $name, $ceta ) . "<br>";
                } 
                else 
                {
                    $err++;
                    $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:cg:update', 'block_gradetracker'), $username, $name ) . "<br>";
                    continue;
                }
            }
        }       
        
        $this->errCnt = $err;     
        
         // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_IMPORTED_CETA_GRADES;
        $Log->afterjson = array(
            'data' => file_get_contents($this->file['tmp_name']),
            'post' => $_POST
        );
        $Log->save();
        // ------------ Logging Info
        
        fclose($fh);
        
        
    }
    
    public function runImportWCoe()
    {
        global $DB;
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fInfo, $this->file['tmp_name']);
        finfo_close($fInfo);
                
        // Has to be csv file, otherwise error and return
        if ($mime != 'text/csv' && $mime != 'text/plain'){
            $this->errors[] = sprintf( get_string('errors:import:mimetype', 'block_gradetracker'), $mime );
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
        
        // Compare headers
        $headerRow = fgetcsv($fh);
        $headers = \GT\CSV\Template::$headersWCoe;
        
        if ($headerRow !== $headers){
            $this->errors[] = sprintf( get_string('errors:import:headers', 'block_gradetracker'), implode(', ', $headers), implode(', ', $headerRow) );
            return false;
        }

        $i = 0;
        $err = 0;
        
        while( ($row = fgetcsv($fh)) !== false )
        {
            $i++;
            $row = array_map('trim', $row);
            
          
            // Check fields are not empty
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5])){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:emptyfield', 'block_gradetracker') . " - " . implode(', ', $row) . "<br>";
                $err++;
                continue;
            } 
            
            $qualType = $row[0];
            $qualLevel = $row[1];
            $qualSubType = $row[2];
            $qualName = $row[3];
            $percentileNumber = $row[4];
            $value = $row[5];

            
            // Check valid qual
            $qual = \GT\Qualification::retrieve($qualType, $qualLevel, $qualSubType, $qualName);
            if (!$qual || !$qual->isValid()){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:invalidqual', 'block_gradetracker') . " - {$qualType} | {$qualLevel} | {$qualSubType} | {$qualName}<br>";
                $err++;
                continue;
            }
            
            if ($qual) {
                $DB->delete_records("bcgt_qual_attributes", array("qualid" => $qual->getID()));
            }
        }
        
        rewind($fh);
        
        while( ($row = fgetcsv($fh)) !== false )
        {
            
            $i++;
            $row = array_map('trim', $row);
            
          
            // Check fields are not empty
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5])){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:emptyfield', 'block_gradetracker') . " - " . implode(', ', $row) . "<br>";
                $err++;
                continue;
            } 
            
            $qualType = $row[0];
            $qualLevel = $row[1];
            $qualSubType = $row[2];
            $qualName = $row[3];
            $percentileNumber = $row[4];
            $value = $row[5];

            
            // Check valid qual
            $qual = \GT\Qualification::retrieve($qualType, $qualLevel, $qualSubType, $qualName);
            if (!$qual || !$qual->isValid()){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:invalidqual', 'block_gradetracker') . " - {$qualType} | {$qualLevel} | {$qualSubType} | {$qualName}<br>";
                $err++;
                continue;
            }
            
            if ($qual) {
                $record = new \stdClass();
                $record->qualid = $qual->getID();
                $record->userid = null;
                $record->attribute = $percentileNumber;
                $record->value = $value;
                $DB->insert_record("bcgt_qual_attributes", $record);
            }
        }
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_IMPORTED_COEFFICIENTS;
        $Log->afterjson = array(
            'data' => file_get_contents($this->file['tmp_name']),
            'post' => $_POST
        );
        $Log->save();
        // ------------ Logging Info
        
        fclose($fh);
        
        
        
    }
    
    public function runImportAssessmentGrades($assessmentID){
        
        global $DB;
        
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fInfo, $this->file['tmp_name']);
        finfo_close($fInfo);
                
        // Has to be csv file, otherwise error and return
        if ($mime != 'text/csv' && $mime != 'text/plain'){
            $this->errors[] = sprintf( get_string('errors:import:mimetype', 'block_gradetracker'), $mime );
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
        
        // Compare headers
        
        // Get the header from the file
        $headerRow = fgetcsv($fh);
        
        // Get the default header to compare against
        $headers = \GT\CSV\Template::$headersAssGrades;
        
        // Duplicate that into another variable for some reason
        $headersWithCommentCol = $headers;
        
        // Add the header "Comments" to this duplicate
        array_push($headersWithCommentCol, 'Comments');
        
        // Check to see if the header row supplied matches either the default, or the default + the Comments header
        if ($headerRow !== $headers && $headerRow !== $headersWithCommentCol){
            $this->errors[] = sprintf( get_string('errors:import:headers', 'block_gradetracker'), implode(', ', $headers), implode(', ', $headerRow) );
            return false;
        }
        
        $isUsingCommentsCol = (in_array('Comments', $headerRow));
        
        // Check assessment is valid
        $Assessment = new \GT\Assessment($assessmentID);
        if (!$Assessment->isValid()){
            $this->errors[] = get_string('invalidassessment', 'block_gradetracker');
            return false;
        }

        $i = 0;
        $err = 0;
        
        while( ($row = fgetcsv($fh)) !== false )
        {
            
            $i++;
            $row = array_map('trim', $row);
                      
            // Check fields are not empty
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5])){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:emptyfield', 'block_gradetracker') . " - " . implode(', ', $row) . "<br>";
                $err++;
                continue;
            } 
            
            $username = $row[0];
            $course = $row[1];
            $qualType = $row[2];
            $qualLevel = $row[3];
            $qualSubType = $row[4];
            $qualName = $row[5];
            $grade = $row[6];
            $ceta = $row[7];
            $comments = ($isUsingCommentsCol) ? $row[8] : null;
            
            // Check valid user
            $user = \GT\User::byUsername($username);
            if (!$user || !$user->isValid()){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:invaliduser', 'block_gradetracker') . " - {$username}<br>";
                $err++;
                continue;
            }
            
            // Check valid qual
            $qual = \GT\Qualification::retrieve($qualType, $qualLevel, $qualSubType, $qualName);
            if (!$qual || !$qual->isValid()){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:invalidqual', 'block_gradetracker') . " - {$qualType} | {$qualLevel} | {$qualSubType} | {$qualName}<br>";
                $err++;
                continue;
            }
            
            // Check user is on qual
            if (!$user->isOnQual($qual->getID(), "STUDENT")){
                $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:studnotonqual', 'block_gradetracker'), $username, $qualName ) . "<br>";
                $err++;
                continue;
            }            
                        
            // Check this qualification is attached to the assessment specified
            $qualAssessment = $qual->getAssessment($Assessment->getID());
            if (!$qualAssessment){
                $this->output .= "[{$i}] ERR: " . get_string('errors:import:ass:qualnotonass', 'block_gradetracker') . " - {$qualType} | {$qualLevel} | {$qualSubType} | {$qualName}<br>";
                $err++;
                continue;
            }
            
            // Check grade is valid (if set)
            $gradeObj = new \GT\CriteriaAward();
            if ($grade){
                $GradingStructure = $qualAssessment->getQualificationAssessmentGradingStructure();
                $gradeObj = ($GradingStructure && $GradingStructure->isValid()) ? $GradingStructure->getAwardByShortName($grade) : false;
                if (!$gradeObj || !$gradeObj->isValid()){
                    $this->output .= "[{$i}] ERR: " . get_string('errors:import:ass:grade', 'block_gradetracker') . " - {$qualType} | {$qualLevel} | {$qualSubType} | {$qualName} - ({$grade})<br>";
                    $err++;
                    continue;
                }
            }
            
            // Check CETA is valid (if set)
            $cetaObj = new \GT\QualificationAward();
            if ($ceta){
                $QualBuild = $qual->getBuild();
                $cetaObj = $QualBuild->getAwardByName($ceta);
                if (!$cetaObj || !$cetaObj->isValid()){
                    $this->output .= "[{$i}] ERR: " . get_string('errors:import:ass:ceta', 'block_gradetracker') . " - {$qualType} | {$qualLevel} | {$qualSubType} | {$qualName} - ({$ceta})<br>";
                    $err++;
                    continue;
                }
            }
            
            // Okay, if we've got this far then everything should be ok with this row
            $qualAssessment->loadStudent( $user );
            
            $qualAssessment->setUserGrade( $gradeObj );
            $qualAssessment->setUserCeta( $cetaObj );
            
            if (!is_null($comments)){
                $qualAssessment->setUserComments( $comments );
            }
                        
            // Save the user's assessment
            if ( $qualAssessment->saveUser() ){
                $this->output .= "[{$i}] OK: " . sprintf( get_string('import:assgrades:updated', 'block_gradetracker'), $qualAssessment->getName(), $username, $qual->getShortDisplayName(), $grade, $ceta ) . "<br>";
            } else {
                $err++;
                $this->output .= "[{$i}] ERR: " . sprintf( get_string('errors:import:ass:update', 'block_gradetracker'), $username, $qual->getShortDisplayName() ) . "<br>";
                continue;
            }
                                   
        }
        
        $this->errCnt = $err;
        
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
        $Log->details = \GT\Log::GT_LOG_DETAILS_IMPORTED_ASSESSMENT_GRADES;
        $Log->afterjson = array(
            'data' => file_get_contents($this->file['tmp_name']),
            'post' => $_POST
        );
        $Log->addAttribute(\GT\Log::GT_LOG_ATT_ASSID, $Assessment->getID());
        $Log->save();
        // ------------ Logging Info
        
        fclose($fh);
        
    }
    
    public function runImportOldQualSpec($qualID){
        
        global $CFG, $DB;
        
        $file = \GT\GradeTracker::dataroot() . '/tmp/' . $this->file;
        
        $Qualification = new \GT\Qualification($qualID);
        if (!$Qualification->isValid()){
            $this->errors[] = get_string('invalidqual', 'block_gradetracker');
            return false;
        }
        
        $QualStructure = $Qualification->getStructure();
        if (!$QualStructure || !$QualStructure->isValid() || $QualStructure->isDeleted()){
            $this->errors[] = get_string('invalidstructure', 'block_gradetracker');
            return false;
        }
        
        // Try to open file
        // Require PHPExcel library
        require_once $CFG->dirroot . '/lib/phpexcel/PHPExcel.php';
        
        // Open with PHPExcel reader
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($file);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($file);
        } catch(Exception $e){
            print_error($e->getMessage());
            return false;
        }
        
        // Start output
        $output = "";
        $cnt = 0;
        
        // Can re-use some of these strings
        $output .= sprintf( get_string('import:datasheet:process:file', 'block_gradetracker'), $file ) . '<br>';
        
        
        // Get stuff from worksheets
        $objPHPExcel->setActiveSheetIndex(0);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $cntSheets = $objPHPExcel->getSheetCount();

        // Loop through the worksheets (each unit has its own worksheet)
        for($sheetNum = 0; $sheetNum < $cntSheets; $sheetNum++)
        {

            $objPHPExcel->setActiveSheetIndex($sheetNum);
            $objWorksheet = $objPHPExcel->getActiveSheet();
            $output .= sprintf( get_string('import:datasheet:process:worksheet', 'block_gradetracker'), $objWorksheet->getTitle() ) . '<br>';
            
            $lastCol = $objWorksheet->getHighestColumn();
            $lastCol++;
            $lastRow = $objWorksheet->getHighestRow();
            
            $unit = array();
            $unit['name'] = (string)$objWorksheet->getCell("B3")->getCalculatedValue();
            $unit['code'] = (string)$objWorksheet->getCell("B4")->getCalculatedValue();
            $unit['details'] = (string)$objWorksheet->getCell("B5")->getCalculatedValue();
            $unit['level'] = (string)$objWorksheet->getCell("B6")->getCalculatedValue();
            $unit['credits'] = (string)$objWorksheet->getCell("B7")->getCalculatedValue();
            $unit['weighting'] = (string)$objWorksheet->getCell("B8")->getCalculatedValue();
            $unit['grading'] = (string)$objWorksheet->getCell("B9")->getCalculatedValue();
            $unit['criteria'] = array();
            
            $output .= sprintf( get_string('import:datasheet:process:unit', 'block_gradetracker'), $unit['name'] ) . '<br>';
            
            // Check for unit grading structure with this name
            $unitGradingStructure = $QualStructure->getUnitGradingStructureByName($unit['grading']);
            if (!$unitGradingStructure || !$unitGradingStructure->isValid()){
                $this->errors[] = get_string('unit', 'block_gradetracker') . " ({$unit['name']}) " . get_string('invalidgradingstructure', 'block_gradetracker') . " - " . $unit['grading'];
            }
            
            // Check level exists
            $Level = \GT\Level::findByName($unit['level']);
            if (!$Level){
                $this->errors[] = get_string('unit', 'block_gradetracker') . " ({$unit['name']}) " . get_string('invalidlevel', 'block_gradetracker') . " - " . $unit['level'];
            }
            
            // See if a unit with this name already exists
            $split = \gt_split_unit_name_number($unit['name']);
            $check = \GT\Unit::search( array(
                'structureID' => $QualStructure->getID(),
                'levelID' => $Level->getID(),
                'unitNumber' => $split['number'],
                'name' => $split['name']
            ) );
            
            // If this unit already exists, skip it
            if ($check){
                $output .= get_string('unit', 'block_gradetracker') . " ({$unit['name']}) " . get_string('recordexists', 'block_gradetracker') . '<br>';
                continue;
            }
            
            if (!$this->errors)
            {
            
                $output .= sprintf( get_string('blockbcgtdata:process:createunit', 'block_gradetracker'), $unit['name'], $Level->getName() ) . '<br>';
                
                // Create the new unit
                $Unit = new \GT\Unit();
                $Unit->setCode($unit['code']);
                $Unit->setCredits($unit['credits']);
                $Unit->setDescription($unit['details']);
                $Unit->setGradingStructureID($unitGradingStructure->getID());
                $Unit->setLevelID($Level->getID());
                $Unit->setName($split['name']);
                $Unit->setStructureID($QualStructure->getID());
                $Unit->setUnitNumber($split['number']);
                
                $Unit->save();
                
                if ($Unit->isValid()){
                    
                    $cnt++;
                    $critParentsArray = array();
                    $critArray = array();
                    
                    $output .= get_string('blockbcgtdata:process:success', 'block_gradetracker') . '<br>';
                    
                    // Now do the criteria
                    for ($i = 11; $i <= $lastRow; $i++)
                    {
                        
                        $crit = array(
                            'name' => $objWorksheet->getCell("A{$i}")->getCalculatedValue(),
                            'details' => $objWorksheet->getCell("B{$i}")->getCalculatedValue(),
                            'weighting' => $objWorksheet->getCell("C{$i}")->getCalculatedValue(),
                            'grading' => $objWorksheet->getCell("D{$i}")->getCalculatedValue(),
                            'parent' => $objWorksheet->getCell("E{$i}")->getCalculatedValue(),
                        );
                            
                        $output .= sprintf( get_string('blockbcgtdata:process:createcrit', 'block_gradetracker'), $crit['name'] ) . '<br>';

                        // Check for crit grading structure with this name
                        $critGradingStructure = $QualStructure->getCriteriaGradingStructureByName($crit['grading']);
                        if (!$critGradingStructure || !$critGradingStructure->isValid()){
                            $this->errors[] = get_string('unit', 'block_gradetracker') . ' ' . $unit['name'] . ' - ' . get_string('criterion', 'block_gradetracker') . " ({$crit['name']}) " . get_string('invalidgradingstructure', 'block_gradetracker') . " - " . $crit['grading'];
                        }
                        
                        $Criterion = new \GT\Criteria\StandardCriterion();
                        $Criterion->setDescription($crit['details']);
                        $Criterion->setGradingStructureID($critGradingStructure->getID());
                        $Criterion->setName($crit['name']);
                        $Criterion->setUnitID($Unit->getID());

                        $Criterion->save();
                        
                        if ($Criterion->isValid()){
                            $output .= get_string('blockbcgtdata:process:success', 'block_gradetracker') . '<br>';
                            $critArray[$Criterion->getName()] = $Criterion->getID();
                            if ($crit['parent']){
                                $critParentsArray[$Criterion->getID()] = $crit['parent'];
                            }
                        } else {
                            $this->errors[] = get_string('blockbcgtdata:err:invalidcritt', 'block_gradetracker');
                        }                        

                    }
                    
                    // Parents
                    if ($critParentsArray)
                    {
                        foreach($critParentsArray as $id => $parentName)
                        {
                            if (array_key_exists($parentName, $critArray))
                            {
                                $parentID = $critArray[$parentName];
                                $Criterion = new \GT\Criteria\StandardCriterion($id);
                                if ($Criterion->isValid())
                                {
                                    $Criterion->setParentID($parentID);
                                    $Criterion->save();
                                }
                            }
                        }
                    }
                    
                    // Add the unit to the Qual
                    $Qualification->addUnit($Unit);
                    
                } else {
                    $this->errors[] = get_string('blockbcgtdata:err:invalidunit', 'block_gradetracker');
                }
            
            }
            
            $Qualification->saveQualUnits();
            
        }
        
        
                
        
        $this->output['result'] = (!$this->errors);
        $this->output['output'] = $output;
        $this->output['cnt'] = $cnt;
        $this->output['cntSheets'] = $cntSheets;
        
    }
    
    
    
    public function checkFileOldQualSpec($qualID)
    {
        
        global $CFG, $DB, $MSGS;
        
        $Qualification = new \GT\Qualification($qualID);
        if (!$Qualification->isValid()){
            $this->errors[] = get_string('invalidqual', 'block_gradetracker');
            return false;
        }
        
        $QualStructure = $Qualification->getStructure();
        if (!$QualStructure || !$QualStructure->isValid() || $QualStructure->isDeleted()){
            $this->errors[] = get_string('invalidstructure', 'block_gradetracker');
            return false;
        }
                
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = \finfo_open(FILEINFO_MIME_TYPE);
            $mime = \finfo_file($fInfo, $this->file['tmp_name']);
        \finfo_close($fInfo);
        
        $ext = pathinfo($this->file['name'], PATHINFO_EXTENSION);
            
        // On linux PHP says the mime type of an xlsx is application/zip, which is handy...
        if ( ($mime != 'application/vnd.ms-excel' && $mime != 'application/zip' && $mime != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') || $ext != 'xlsx'){
            $this->errors[] = 'Invalid file format. Expected: application/vnd.ms-excel or application/vnd.openxmlformats-officedocument.spreadsheetml.sheet (.xlsx) Found: ' . $mime . ' ('.$ext.')';
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
        
        // Open with PHPExcel
        require_once $CFG->dirroot . '/lib/phpexcel/PHPExcel.php';
        
        // Open with PHPExcel reader
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($this->file['tmp_name']);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($this->file['tmp_name']);
        } catch(Exception $e){
            $this->errors[] = $e->getMessage();
            return false;
        }
        
        $now = time();
        $this->tmpFile = 'old_spec_' . $qualID . '_' . $now . '.xlsx';
        
        // Save the tmp file to Moodledata so we can still use it when we click confirm
        $saveFile = \gt_save_file($this->file['tmp_name'], 'tmp', $this->tmpFile);
        if (!$saveFile){
            $this->errors[] = get_string('errors:save:file', 'block_gradetracker');
            return false;
        }    
        
        
        
        // Get stuff from worksheets
        $objPHPExcel->setActiveSheetIndex(0);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $cntSheets = $objPHPExcel->getSheetCount();

        $units = array();

        // Loop through the worksheets (each unit has its own worksheet)
        for($sheetNum = 0; $sheetNum < $cntSheets; $sheetNum++)
        {

            $objPHPExcel->setActiveSheetIndex($sheetNum);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            $lastCol = $objWorksheet->getHighestColumn();
            $lastCol++;
            $lastRow = $objWorksheet->getHighestRow();
            
            $unit = array();
            $unit['name'] = $objWorksheet->getCell("B3")->getCalculatedValue();
            $unit['code'] = $objWorksheet->getCell("B4")->getCalculatedValue();
            $unit['details'] = $objWorksheet->getCell("B5")->getCalculatedValue();
            $unit['level'] = $objWorksheet->getCell("B6")->getCalculatedValue();
            $unit['credits'] = $objWorksheet->getCell("B7")->getCalculatedValue();
            $unit['weighting'] = $objWorksheet->getCell("B8")->getCalculatedValue();
            $unit['grading'] = $objWorksheet->getCell("B9")->getCalculatedValue();
            $unit['criteria'] = array();
            
            // Check for unit grading structure with this name
            $unitGradingStructure = $QualStructure->getUnitGradingStructureByName($unit['grading']);
            if (!$unitGradingStructure || !$unitGradingStructure->isValid()){
                $this->errors[] = get_string('unit', 'block_gradetracker') . " ({$unit['name']}) " . get_string('invalidgradingstructure', 'block_gradetracker') . " - " . $unit['grading'];
            }
            
            // Check level exists
            $Level = \GT\Level::findByName($unit['level']);
            if (!$Level){
                $this->errors[] = get_string('unit', 'block_gradetracker') . " ({$unit['name']}) " . get_string('invalidlevel', 'block_gradetracker') . " - " . $unit['level'];
            }

            // Criteria
            for ($i = 11; $i <= $lastRow; $i++)
            {
                $crit = array(
                    'name' => $objWorksheet->getCell("A{$i}")->getCalculatedValue(),
                    'details' => $objWorksheet->getCell("B{$i}")->getCalculatedValue(),
                    'weighting' => $objWorksheet->getCell("C{$i}")->getCalculatedValue(),
                    'grading' => $objWorksheet->getCell("D{$i}")->getCalculatedValue(),
                    'parent' => $objWorksheet->getCell("E{$i}")->getCalculatedValue(),
                );
                    
                // Check for crit grading structure with this name
                $critGradingStructure = $QualStructure->getCriteriaGradingStructureByName($crit['grading']);
                if (!$critGradingStructure || !$critGradingStructure->isValid()){
                    $this->errors[] = "{$unit['name']} - [A{$i}] - " . get_string('criterion', 'block_gradetracker') . " ({$crit['name']}) " . get_string('invalidgradingstructure', 'block_gradetracker') . " - " . $crit['grading'];
                }
                    
                $unit['criteria'][] = $crit;
                    
            }
            
            
            $units[] = $unit;
        
        }
        
        if (!$this->errors){
            $this->output['qual'] = $Qualification;
            $this->output['units'] = $units;
            $this->output['file'] = $this->tmpFile;
        }
        
    }
    
    /**
     * Check the student data sheet to make sure we can import it
     * @global \GT\type $CFG
     * @global \GT\type $DB
     * @global array $MSGS
     * @return boolean
     */
    public function checkFileStudentDataSheet()
    {
        
        global $CFG, $DB, $MSGS;
        
        $assessmentView = optional_param('ass', false, PARAM_INT);
        
        if (!$this->getStudentID() || !$this->getQualID()){
            $this->errors[] = get_string('invalidrecord', 'block_gradetracker');
            return false;
        }
        
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = \finfo_open(FILEINFO_MIME_TYPE);
            $mime = \finfo_file($fInfo, $this->file['tmp_name']);
        \finfo_close($fInfo);
        
        $ext = pathinfo($this->file['name'], PATHINFO_EXTENSION);
            
        // On linux PHP says the mime type of an xlsx is application/zip, which is handy...
        if ( ($mime != 'application/vnd.ms-excel' && $mime != 'application/zip' && $mime != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') || $ext != 'xlsx'){
            $this->errors[] = 'Invalid file format. Expected: application/vnd.ms-excel or application/vnd.openxmlformats-officedocument.spreadsheetml.sheet (.xlsx) Found: ' . $mime . ' ('.$ext.')';
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
                
        // Generate an overview of the spreadsheet so we can see what has changed
        $output = "";
        $now = time();
        $student = new \GT\User($this->getStudentID());
        
        $qualification = new \GT\Qualification\UserQualification($this->getQualID());
        if (!$qualification->isValid())
        {
            $this->errors[] = get_string('invalidqual', 'block_gradetracker');
            return false;
        }
        
        $qualification->loadStudent( $this->getStudentID() );
        
        // Require PHPExcel library
        require_once $CFG->dirroot . '/lib/phpexcel/PHPExcel.php';
        
        // Open with PHPExcel reader
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($this->file['tmp_name']);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($this->file['tmp_name']);
        } catch(Exception $e){
            $this->errors[] = $e->getMessage();
            return false;
        }
        
        // Check it's a valid student datasheet
        $customProperties = $this->getFileCustomProperties($objPHPExcel);
        
        if ($customProperties['GT-DATASHEET-TYPE'] !== 'STUDENT'){
            $this->errors[] = get_string('errors:import:datasheettype', 'block_gradetracker');
            return false;
        }
                
        // Is it an assessment grid?
        if ($assessmentView && $customProperties['GT-DATASHEET-ASSESSMENT-VIEW'] != 1 ){
            $this->errors[] = get_string('errors:import:datasheettypeass', 'block_gradetracker');
            return false;
        }
        
        // If it not an assessment grid, but we uploaded an assessment spreadsheet?
        if (!$assessmentView && $customProperties['GT-DATASHEET-ASSESSMENT-VIEW'] == 1){
            $this->errors[] = get_string('errors:import:datasheettypeass', 'block_gradetracker');
            return false;
        }
        
        // Save the tmp file to Moodledata so we can still use it when we click confirm
        $saveFile = \gt_save_file($this->file['tmp_name'], 'tmp', $this->getQualID() . '_' . $this->getStudentID() . '_' . $now . '.xlsx');
        if (!$saveFile){
            $this->errors[] = get_string('errors:save:file', 'block_gradetracker');
            return false;
        }    
        
        $this->tmpFile = $saveFile;
        
        
        // Get stuff from worksheets
        $unix = $objPHPExcel->getProperties()->getCreated();
                        
        $objPHPExcel->setActiveSheetIndex(0);
        $objWorksheet = $objPHPExcel->getActiveSheet();

        $lastCol = $objWorksheet->getHighestColumn();
        $lastCol++;
        
        $lastRow = $objWorksheet->getHighestRow();
        
        $commentWorkSheet = $objPHPExcel->getSheet(1);
        
        
        
        // Key here
        $output .= "<h3>".get_string('key', 'block_gradetracker')."</h3>";
        $output .= "<table class='gt_import_key'>";
            $output .= "<tr>";
                $output .= "<td class='updatedsince crit'>&nbsp;</td>";
                $output .= "<td>".get_string('import:datasheet:key:updatedsince', 'block_gradetracker')."</td>";
            $output .= "</tr>";

            $output .= "<tr>";
                $output .= "<td class='updatedinsheet crit'>&nbsp;</td>";
                $output .= "<td>".get_string('import:datasheet:key:updatedinsheet', 'block_gradetracker')."</td>";
            $output .= "</tr>";

            $output .= "<tr>";
                $output .= "<td class='updatedinsheet updatedsince crit'>&nbsp;</td>";
                $output .= "<td>".get_string('import:datasheet:key:updatedinboth', 'block_gradetracker')."</td>";
            $output .= "</tr>";

        $output .= "</table>";

        $output .= "<br>";

        
        
        // Assessment Grid/Spreadsheet
        if ($assessmentView)
        {
            
            // Get all the qualIDs as some assessment grids include other qualifications
            $qualIDArray = array();
            for ($row = 3; $row <= $lastRow; $row++)
            {
                $qualIDArray[] = $objWorksheet->getCell("A{$row}")->getCalculatedValue();
            }
            
            $placeholders = \gt_create_sql_placeholders($qualIDArray);
            $params = array($this->getStudentID(), $unix);
            $params = array_merge($params, $qualIDArray);
                        
            // See if anything has been updated in the DB since we downloaded the file
            $updates = $DB->get_records_sql("SELECT ua.*
                                            FROM {bcgt_user_assessments} ua
                                            WHERE ua.userid = ?
                                            AND ua.lastupdate > ?
                                            AND ua.qualid IN ({$placeholders})", $params);

            if ($updates)
            {

                $output .= "<div class='gt_import_warning'>";
                    $output .= "<b>".get_string('warning').":</b><br><br>";
                    $output .= "<p>".get_string('importwarning', 'block_gradetracker')."</p>";
                    
                    foreach($updates as $update)
                    {

                        $qual = new \GT\Qualification($update->qualid);
                        $assessment = new \GT\Assessment($update->assessmentid);
                        $updateBy = new \GT\User($update->lastupdateby);
                        
                        // Grade
                        $grade = new \GT\CriteriaAward($update->grade);
                        
                        // Ceta
                        $ceta = new \GT\QualificationAward($update->ceta);
                        
                        $gradeValue = $grade->getShortName();
                        if ($assessment->getSetting('grading_method') == 'numeric'){
                            $gradeValue = $update->score;
                        }
                                                
                        // Grade
                        $output .= sprintf( get_string('aupdatedtobbycatd', 'block_gradetracker'), $qual->getDisplayName() . " " . $assessment->getName(), "GRADE ({$gradeValue}), CETA ({$ceta->getName()})", \gt_html($update->comments), $updateBy->getDisplayName(), date('d-m-Y, H:i', $update->lastupdate)) . "<br>";

                    }

                $output .= "</div>";
                $output .= "<br><br>";

            }
            
                        
            
            $output .= "<h2 class='gt_c'>".$student->getDisplayName()."</h2>";
            $output .= "<br>";
            
            
            $output .= "<div class='gt_import_grid_div'>";

                $output .= "<form action='' method='post' class='c'>";

                $output .= "<p class='gt_c'><a href='#' onclick='gtToggleImportGridTables(\"grades\");return false;'>".get_string('grades', 'block_gradetracker')."</a> | <a href='#' onclick='gtToggleImportGridTables(\"comments\");return false;'>".get_string('comments', 'block_gradetracker')."</a></p>";

                
                
                
                
                // Grades sheet
                $output .= "<table id='gt_import_grid_table_grades' class='gt_import_grid_table'>";

                    $output .= "<tr>";

                        $output .= "<th><input type='checkbox' onclick='gtImportToggleCheckBoxes(this, \"gt_import_unit_checkbox\");' checked /></th>";
                        $output .= "<th>".get_string('qualification', 'block_gradetracker')."</th>";

                        $assessmentsArray = array();
                        
                        for ($col = 'C'; $col != $lastCol; $col++){

                            $cellValue = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                            
                            // If the cell is not empty
                            if ($cellValue != ''){
                                
                                preg_match("/^\[([0-9]+)\]/", $cellValue, $matches);
                                $id = (isset($matches[1])) ? $matches[1] : false;
                                
                                // If format of column is valid and we got an ID out of it
                                if ($id){
                                    $assessmentsArray[$id] = array('id' => $id, 'name' => $cellValue, 'colspan' => 1, 'startingCell' => $col);
                                }
                                
                            } elseif ($assessmentsArray) {
                                
                                // Else if it's blank, it must be merged with a previous cell, so increment colspan
                                end($assessmentsArray);
                                $key = key($assessmentsArray);
                                $assessmentsArray[$key]['colspan']++;
                                
                            }

                        }

                        // Now loop through the assessmentArray, since we know the colspans to use
                        if ($assessmentsArray)
                        {
                            foreach($assessmentsArray as $ass)
                            {
                                $output .= "<th colspan='{$ass['colspan']}'>{$ass['name']}</th>";
                            }
                        }
                        
                    $output .= "</tr>";
                    
                    // Now loop through the second row, which shows the column, e.g. Grade, CETA or a custom field
                    $output .= "<tr>";
                    
                        $output .= "<th></th>";
                        $output .= "<th></th>";
                        
                        for ($col = 'C'; $col != $lastCol; $col++){

                            $cellValue = $objWorksheet->getCell($col . "2")->getCalculatedValue();
                            $output .= "<th>{$cellValue}</th>";

                        }
                    
                    $output .= "</tr>";
                    
                    
                    // Loop through qualifications
                    for ($row = 3; $row <= $lastRow; $row++)
                    {
                        
                        $studentQualification = false;
                        $rowClass = ( ($row % 2) == 0 ) ? 'even' : 'odd';
                        
                        $output .= "<tr class='{$rowClass}'>";      
                            
                            for ($col = 'A'; $col != $lastCol; $col++){
                                
                                $cellClass = '';
                                $currentValue = get_string('na', 'block_gradetracker');                                        
                                $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                                // If first column, get the ID of the unit but don't print it out
                                if ($col == 'A'){
                                    
                                    $qualID = (int)$cellValue;                                    
                                    $studentQual = new \GT\Qualification\UserQualification($qualID);
                                    
                                    if (!$studentQual->isValid() || !$studentQual->loadStudent($this->getStudentID())){
                                        $this->errors[] = sprintf( get_string('import:datasheet:process:error:qual', 'block_gradetracker'), $objWorksheet->getCell("B".$row)->getCalculatedValue() );
                                        break;
                                    }
                                    
                                    continue; // Don't want to print the id out
                                    
                                }
                                
                                // Assessment we want to check for changes
                                elseif ($col != 'A' && $col != 'B'){

                                    // Work out the merged cell that has the assessment ID in, based on
                                    // which cell we are in now and the colspan of the parent
                                    $assessment = self::findAssessmentParentColumn($assessmentsArray, $col);
                                    if (!$assessment){
                                        $this->errors[] = get_string('import:datasheet:process:error:ass', 'block_gradetracker' );
                                        $output .= "<td>-</td>";
                                        continue;
                                    }
                                    
                                    
                                    // Get the cell value of the column this is in, so we can see if it's
                                    // a Grade column, a CETA column or a Custom Field
                                    $column = $objWorksheet->getCell($col . 2)->getCalculatedValue();
                                    $column = strtolower($column);
                                                                        
                                    // Student Assessment
                                    $studentAssessment = $studentQual->getUserAssessment($assessment['id']);
                                    
                                    // If can't load it on this qual, must not be attached to this qual
                                    if (!$studentAssessment){
                                        $output .= "<td>-</td>";
                                        continue;
                                    }

                                                                      
                                    // Grade cell
                                    if ($column == 'grade')
                                    {
                                        
                                        $gradingMethod = $studentAssessment->getSetting('grading_method');
                                        if ($gradingMethod == 'numeric')
                                        {
                                            
                                            // Check the current score of this assessment
                                            $currentValue = $studentAssessment->getUserScore();
                                            
                                        }
                                        else
                                        {
                                        
                                            // Check the current grade of this assessment
                                            $currentGrade = $studentAssessment->getUserGrade();
                                            $currentValue = ($currentGrade) ? $currentGrade->getShortName() : '';
                                            $dateAssessmentUpdated = $studentAssessment->getUserLastUpdate();
                                        
                                        }
                                        
                                        // The default name of the CriteriaAward if none is set is N/A,
                                        // so if the cell is blank, a comparison of blank and "N/A" won't match
                                        // and it will think something has changed even though it hasn't
                                        // So change the cell value to N/A in order for the comparison to work
                                        if ($cellValue == ''){
                                            $cellValue = get_string('na', 'block_gradetracker');
                                        }
                                      
                                        // If value in DB and sheet don't match
                                        if ($currentValue != $cellValue){
                                            $cellClass .= 'updatedinsheet ';
                                        }

                                        // If the assessment's last update date is later than when we downloaded the datasheet
                                        if ($dateAssessmentUpdated > $unix){
                                            $cellClass .= 'updatedsince ';
                                        }
                                        
                                    }

                                    // CETA cell
                                    elseif ($column == 'ceta')
                                    {
                                        
                                        // Check the current CETA of this assessment
                                        $currentCeta = $studentAssessment->getUserCeta();
                                        $currentValue = ($currentCeta) ? $currentCeta->getName() : '';
                                        $dateAssessmentUpdated = $studentAssessment->getUserLastUpdate();
                                        
                                        // If value in DB and sheet don't match
                                        if ($currentValue != $cellValue){
                                            $cellClass .= 'updatedinsheet ';
                                        }

                                        // If the assessment's last update date is later than when we downloaded the datasheet
                                        if ($dateAssessmentUpdated > $unix){
                                            $cellClass .= 'updatedsince ';
                                        }
                                        
                                        // For display purposes, change blank cell into N/A as it looks better
                                        if ($cellValue == ''){
                                            $cellValue = get_string('na', 'block_gradetracker');
                                        }
                                        
                                    }

                                    // Custom Form Field
                                    elseif (preg_match("/^\[([0-9]+)\]/", $column, $matches))
                                    {
                                        
                                        $fieldID = (isset($matches[1])) ? $matches[1] : false;
                                        $field = new \GT\FormElement($fieldID);
                                        
                                        // Check the current value of this custom field
                                        $currentCustomField = $studentAssessment->getCustomFieldValue($field, 'v', false);
                                        $currentValue = ($currentCustomField) ? $currentCustomField : '';
                                        $dateAssessmentUpdated = $studentAssessment->getUserLastUpdate();

                                        // If value in DB and sheet don't match
                                        if ($currentValue != $cellValue){
                                            $cellClass .= 'updatedinsheet ';
                                        }
                                        
                                        // If the assessment's last update date is later than when we downloaded the datasheet
                                        if ($dateAssessmentUpdated > $unix){
                                            $cellClass .= 'updatedsince ';
                                        }
                                        
                                        // For display purposes, change blank cell into N/A as it looks better
                                        if ($cellValue == ''){
                                            $cellValue = get_string('na', 'block_gradetracker');
                                        }
                                        
                                    }
                                    
                                    // Display the cell
                                    $output .= "<td class='{$cellClass}'>";
                                        $output .= $cellValue;
                                    $output .= "</td>";
                                    

                                } else {
                                    // Otherwise for qual name just print it out
                                    $output .= "<td><input type='checkbox' name='quals[]' value='{$qualID}' class='gt_import_unit_checkbox' checked /></td>";
                                    $output .= "<td>{$cellValue}</td>";
                                }
                                
                            }
                        
                        $output .= "</tr>";
                        
                    }


                // End of grades sheet
                $output .= "</table>";
                
                
                
                
                
                // Comments sheet
                $lastCol = $commentWorkSheet->getHighestColumn();
                $lastCol++;
                
                $output .= "<table id='gt_import_grid_table_comments' class='gt_import_grid_table' style='display:none;'>";

                    $output .= "<tr>";

                        $output .= "<th></th>";
                        $output .= "<th>".get_string('qualification', 'block_gradetracker')."</th>";
                
                        // Now loop through the assessmentArray, since we know the colspans to use
                        if ($assessmentsArray)
                        {
                            foreach($assessmentsArray as $ass)
                            {
                                $output .= "<th>{$ass['name']}</th>";
                            }
                        }
                        
                    $output .= "</tr>";
                    
                    // Loop through qualifications
                    for ($row = 3; $row <= $lastRow; $row++)
                    {
                        
                        $studentQualification = false;
                        $rowClass = ( ($row % 2) == 0 ) ? 'even' : 'odd';
                        
                        $output .= "<tr class='{$rowClass}'>";      
                            
                            for ($col = 'A'; $col != $lastCol; $col++){
                                
                                $cellClass = '';
                                $currentValue = get_string('na', 'block_gradetracker');                                        
                                $cellValue = $commentWorkSheet->getCell($col . $row)->getCalculatedValue();

                                // If first column, get the ID of the unit but don't print it out
                                if ($col == 'A'){
                                    
                                    $qualID = (int)$cellValue;                                    
                                    $studentQual = new \GT\Qualification\UserQualification($qualID);
                                    
                                    if (!$studentQual->isValid() || !$studentQual->loadStudent($this->getStudentID())){
                                        $this->errors[] = sprintf( get_string('import:datasheet:process:error:qual', 'block_gradetracker'), $objWorksheet->getCell("B".$row)->getCalculatedValue() );
                                        break;
                                    }
                                    
                                    continue; // Don't want to print the id out
                                    
                                }
                                
                                // Assessment we want to check for changes
                                elseif ($col != 'A' && $col != 'B'){

                                    $parentColumn = $commentWorkSheet->getCell($col . 1)->getCalculatedValue();
                                    
                                    // Get the assessment ID from the name
                                    preg_match("/^\[([0-9]+)\]/", $parentColumn, $matches);
                                    if (!isset($matches[1])){
                                        $this->errors[] = get_string('import:datasheet:process:error:ass', 'block_gradetracker');
                                        $output .= "<td>-</td>";
                                        continue;
                                    }
                                    
                                    $assessmentID = $matches[1];
                                    
                                    // Get the cell value of the column this is in, so we can see if it's
                                    // a Grade column, a CETA column or a Custom Field
                                    $column = $commentWorkSheet->getCell($col . 2)->getCalculatedValue();
                                    $column = strtolower($column);
                                                                        
                                    // Student Assessment
                                    $studentAssessment = $studentQual->getUserAssessment($assessmentID);
                                    
                                    // If can't load it on this qual, must not be attached to this qual
                                    if (!$studentAssessment){
                                        $output .= "<td>-</td>";
                                        continue;
                                    }

                                    // Get current comments                                 
                                    $userComments = $studentAssessment->getUserComments();
                                    $dateAssessmentUpdated = $studentAssessment->getUserLastUpdate();
                                    
                                    if (is_null($cellValue)){
                                        $cellValue = '';
                                    }
                                   
                                    
                                    // If value in DB and sheet don't match
                                    if ($userComments != $cellValue){
                                        $cellClass .= 'updatedinsheet ';
                                    }
                                        
                                    // If the assessment's last update date is later than when we downloaded the datasheet
                                    if ($dateAssessmentUpdated > $unix){
                                        $cellClass .= 'updatedsince ';
                                    }
                                    
                                    // Display the cell
                                    $output .= "<td class='{$cellClass}'>";
                                        $output .= $cellValue;
                                    $output .= "</td>";
                                    

                                } else {
                                    // Otherwise for qual name just print it out
                                    $output .= "<td></td>";
                                    $output .= "<td>{$cellValue}</td>";
                                }
                                
                            }
                        
                        $output .= "</tr>";
                        
                    }
                
                
                // End of comments sheet
                $output .= "</table>";
                
                $output .= "<br>";

                $output .= "<input type='hidden' name='qualID' value='{$this->getQualID()}' />";
                $output .= "<input type='hidden' name='studentID' value='{$this->getStudentID()}' />";
                $output .= "<input type='hidden' name='now' value='{$now}' />";
                $output .= "<input type='submit' class='gt_btn gt_green gt_btn_small' name='confirm' value='".get_string('confirm')."' />";
                $output .= str_repeat("&nbsp;", 8);
                $output .= "<input type='button' class='gt_btn gt_red gt_btn_small' onclick='window.location.href=\"{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$this->getStudentID()}&qualID={$this->getQualID()}\";' value='".get_string('cancel')."' />";
                
                $output .= "</form>";
                
            
        }
        else
        {
        
        
            // See if anything has been updated in the DB since we downloaded the file
            $updates = $DB->get_records_sql("SELECT uc.*, c.unitid
                                            FROM {bcgt_user_criteria} uc
                                            INNER JOIN {bcgt_criteria} c ON c.id = uc.critid
                                            WHERE critid IN 
                                            ( 
                                                SELECT c.id
                                                FROM {bcgt_criteria} c
                                                INNER JOIN {bcgt_units} u ON c.unitid = u.id
                                                INNER JOIN {bcgt_qual_units} qu ON qu.unitid = u.id
                                                WHERE qu.qualid = ? 
                                            )
                                            AND userid = ?
                                            AND lastupdate > ?", array($this->getQualID(), $this->getStudentID(), $unix));

            if ($updates)
            {

                $output .= "<div class='gt_import_warning'>";
                    $output .= "<b>".get_string('warning').":</b><br><br>";
                    $output .= "<p>".get_string('importwarning', 'block_gradetracker')."</p>";
                    foreach($updates as $update)
                    {

                        $unit = $qualification->getUnit($update->unitid);
                        if (!$unit) continue;

                        $criterion = $unit->getCriterion($update->critid);
                        if (!$criterion) continue;

                        $updateBy = new \GT\User($update->lastupdateby);

                        $value = new \GT\CriteriaAward($update->awardid);
                        $output .= sprintf( get_string('aupdatedtobbycatd', 'block_gradetracker'), "{$unit->getDisplayName()} ({$criterion->getName()})", $value->getName(), \gt_html($update->comments), $updateBy->getDisplayName(), date('d-m-Y, H:i', $update->lastupdate)) . "<br>";

                    }

                $output .= "</div>";
                $output .= "<br><br>";

            }

            $output .= "<h2 class='gt_c'>".$student->getDisplayName()."</h2>";
            $output .= "<br>";


            $output .= "<div class='gt_import_grid_div'>";

                $output .= "<form action='' method='post' class='c'>";

                $output .= "<p class='gt_c'><a href='#' onclick='gtToggleImportGridTables(\"grades\");return false;'>".get_string('grades', 'block_gradetracker')."</a> | <a href='#' onclick='gtToggleImportGridTables(\"comments\");return false;'>".get_string('comments', 'block_gradetracker')."</a></p>";

                $output .= "<table id='gt_import_grid_table_grades' class='gt_import_grid_table'>";

                    $output .= "<tr>";

                        $output .= "<th><input type='checkbox' onclick='gtImportToggleCheckBoxes(this, \"gt_import_unit_checkbox\");' checked /></th>";
                        $output .= "<th>".get_string('unit', 'block_gradetracker')."</th>";

                        for ($col = 'C'; $col != $lastCol; $col++){

                            $cellValue = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                            $output .= "<th>{$cellValue}</th>";

                        }

                    $output .= "</tr>";


                    // Loop through rows to get students
                    for ($row = 2; $row <= $lastRow; $row++)
                    {

                        $studentUnit = false;

                        // Loop columns
                        $rowClass = ( ($row % 2) == 0 ) ? 'even' : 'odd';

                        $output .= "<tr class='{$rowClass}'>";

                            for ($col = 'A'; $col != $lastCol; $col++){

                                $critClass = '';
                                $currentValue = get_string('na', 'block_gradetracker');                                       
                                $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                                // If first column, get the ID of the unit but don't print it out
                                if ($col == 'A'){
                                    $unitID = (int)$cellValue;
                                    $studentUnit = $qualification->getUnit($unitID);
                                    if (!$studentUnit){
                                        $this->errors[] = sprintf( get_string('import:datasheet:process:error:unit', 'block_gradetracker'), $objWorksheet->getCell("B".$row)->getCalculatedValue() );
                                        break;
                                    }
                                    continue; // Don't want to print the id out
                                }

                                // Criteria we want to check for changes
                                if ($col != 'A' && $col != 'B'){


                                    $value = $cellValue;

                                    $critClass .= 'crit ';

                                    // Get studentCriteria to see if it has been updated since we downloaded the sheet
                                    $criteriaName = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                                    $studentCriterion = $studentUnit->getCriterionByName($criteriaName);

                                    if ($studentCriterion)
                                    {

                                        $critDateUpdated = $studentCriterion->getUserLastUpdate();
                                        $valueObj = $studentCriterion->getUserAward();
                                        $userComments = $studentCriterion->getUserComments();

                                        if ($valueObj)
                                        {
                                            $currentValueID = $valueObj->getID();
                                            $currentValue = $valueObj->getShortName();
                                        }


                                        // If value in DB and sheet don't match
                                        if ($currentValue != $value){
                                            $critClass .= 'updatedinsheet ';
                                        }

                                        // If the criteria's last update date is later than when we downloaded the datasheet
                                        if ($critDateUpdated > $unix)
                                        {
                                            $critClass .= 'updatedsince ';
                                        }


                                        $output .= "<td class='{$critClass}' currentValue='{$currentValue}'><small>{$cellValue}</small></td>";

                                    } 
                                    else
                                    {
                                        
                                        // Was it an IV column?
                                        if ($qualification->getStructure() && $qualification->getStructure()->getSetting('iv_column') == 1)
                                        {
                                            
                                            $attribute = false;
                                            $ivDateString = get_string('iv', 'block_gradetracker') . ' - ' . get_string('date');
                                            $ivWhoString = get_string('iv', 'block_gradetracker') . ' - ' . get_string('verifier', 'block_gradetracker');

                                            // If it's a Date in the Excel file, it will return a number here
                                            // Otherwise treat it as text
                                            // So check if we are in the date column and if value is float
                                            if ($criteriaName == $ivDateString && is_float($value) && $value > 0)
                                            {
                                                $value = \gt_convert_excel_date_unix($value);
                                                $value = date('d-m-Y', $value);
                                            }
                                                                                        
                                            // Get the name of the attribute
                                            if ($criteriaName == $ivDateString)
                                            {
                                                $attribute = 'IV_date';
                                            }
                                            elseif ($criteriaName == $ivWhoString)
                                            {
                                                $attribute = 'IV_who';
                                            }
                                            
                                                                                        
                                            // If valid attribute
                                            if ($attribute)
                                            {
                                             
                                                $check = $DB->get_record("bcgt_unit_attributes", array("unitid" => $studentUnit->getID(), "userid" => $this->getStudentID(), "attribute" => $attribute));
                                                
                                                // If value in DB and sheet don't match
                                                if ( ($check && $check->value != $value) || (!$check && !is_null($value)) ){
                                                    $critClass .= 'updatedinsheet ';
                                                }
                                                
                                                // If the attribute's last update date is later than when we downloaded the datasheet
                                                if ($check && $check->lastupdate > $unix)
                                                {
                                                    $critClass .= 'updatedsince ';
                                                }
                                                
                                            }
                                            
                                            $output .= "<td class='{$critClass}'>{$value}</td>";
                                            
                                        }
                                        else
                                        {                                        
                                            $output .= "<td></td>";
                                        }
                                    }


                                } else {
                                    // Otherwise for unit name just print it out
                                    $output .= "<td><input type='checkbox' name='units[]' value='{$unitID}' class='gt_import_unit_checkbox' checked /></td>";
                                    $output .= "<td>{$cellValue}</td>";
                                }


                            }


                        $output .= "</tr>";

                    }


                $output .= "</table>";




                // Comments table
                $output .= "<table id='gt_import_grid_table_comments' class='gt_import_grid_table' style='display:none;'>";

                    $output .= "<tr>";

                        $output .= "<th></th>";
                        $output .= "<th>".get_string('unit', 'block_gradetracker')."</th>";

                        for ($col = 'C'; $col != $lastCol; $col++){

                            $cellValue = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                            $output .= "<th>{$cellValue}</th>";

                        }

                    $output .= "</tr>";


                    // Loop through rows to get students
                    for ($row = 2; $row <= $lastRow; $row++)
                    {

                        $studentUnit = false;

                        // Loop columns
                        $rowClass = ( ($row % 2) == 0 ) ? 'even' : 'odd';

                        $output .= "<tr class='{$rowClass}'>";

                            for ($col = 'A'; $col != $lastCol; $col++){

                                $critClass = '';
                                $currentValue = get_string('na', 'block_gradetracker');                                       
                                $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                                // If first column, get the ID of the unit but don't print it out
                                if ($col == 'A'){
                                    $unitID = (int)$cellValue;
                                    $studentUnit = $qualification->getUnit($unitID);
                                    if (!$studentUnit){
                                        $this->errors[] = sprintf( get_string('import:datasheet:process:error:unit', 'block_gradetracker'), $objWorksheet->getCell("B".$row)->getCalculatedValue() );
                                        break;
                                    }
                                    continue; // Don't want to print the id out
                                }

                                // Criteria we want to check for changes
                                if ($col != 'A' && $col != 'B'){


                                    $value = $cellValue;

                                    $critClass .= 'crit ';

                                    // Get studentCriteria to see if it has been updated since we downloaded the sheet
                                    $criteriaName = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                                    $studentCriterion = $studentUnit->getCriterionByName($criteriaName);

                                    if ($studentCriterion)
                                    {

                                        $critDateUpdated = $studentCriterion->getUserLastUpdate();
                                        $valueObj = $studentCriterion->getUserAward();
                                        $userComments = $studentCriterion->getUserComments();

                                        if ($valueObj)
                                        {
                                            $currentValueID = $valueObj->getID();
                                            $currentValue = $valueObj->getShortName();
                                        }

                                        // Comments
                                        $comment = $commentWorkSheet->getCell($col . $row)->getCalculatedValue();

                                        // If value in DB and sheet don't match
                                        if ($comment != $userComments){
                                            $critClass .= 'updatedinsheet ';
                                        }

                                        // If the criteria's last update date is later than when we downloaded the datasheet
                                        if ($critDateUpdated > $unix)
                                        {
                                            $critClass .= 'updatedsince ';
                                        }


                                        $output .= "<td class='{$critClass}' currentValue='{$currentValue}'><small>{$comment}</small></td>";

                                    } 
                                    else
                                    {
                                        $output .= "<td></td>";
                                    }


                                } else {
                                    // Otherwise for unit name just print it out
                                    $output .= "<td></td>";
                                    $output .= "<td>{$cellValue}</td>";
                                }


                            }


                        $output .= "</tr>";

                    }


                $output .= "</table>";
            
                       
            
                $output .= "<br>";

                $output .= "<input type='hidden' name='qualID' value='{$this->getQualID()}' />";
                $output .= "<input type='hidden' name='studentID' value='{$this->getStudentID()}' />";
                $output .= "<input type='hidden' name='now' value='{$now}' />";
                $output .= "<input type='submit' class='gt_btn gt_green gt_btn_small' name='confirm' value='".get_string('confirm')."' />";
                $output .= str_repeat("&nbsp;", 8);
                $output .= "<input type='button' class='gt_btn gt_red gt_btn_small' onclick='window.location.href=\"{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$this->getStudentID()}&qualID={$this->getQualID()}\";' value='".get_string('cancel')."' />";

                $output .= "</form>";

            $output .= "</div>";

        }

            
        
        
        $MSGS['output'] = $output;
                
    }
    
    /**
     * Find
     * @param type $assessmentsArray
     * @param type $col
     * @return boolean
     */
    public static function findAssessmentParentColumn($assessmentsArray, $col)
    {
        
        // Look for an assessment with this column as the starting cell
        if ($assessmentsArray)
        {
            foreach($assessmentsArray as $key => $ass)
            {
                if ($ass['startingCell'] === $col)
                {
                    return $ass;
                }
            }
        }
        
        // If not, decrement the column and try again
        $col = \gt_decrement_letter_excel($col);
        
        // If it's not a valid letter any more, just give up
        if (is_null($col))
        {
            return false;
        }
        
        // Try again with new letter
        return self::findAssessmentParentColumn($assessmentsArray, $col);
        
    }
    
    
    
    
    /**
     * Check the unit data sheet to make sure we can import it
     * @global type $CFG
     * @global type $DB
     * @global array $MSGS
     * @return boolean
     */
    public function checkFileUnitDataSheet()
    {
        
        global $CFG, $DB, $MSGS;
        
        if (!$this->getUnitID() || !$this->getQualID()){
            $this->errors[] = get_string('invalidrecord', 'block_gradetracker');
            return false;
        }
        
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = \finfo_open(FILEINFO_MIME_TYPE);
            $mime = \finfo_file($fInfo, $this->file['tmp_name']);
        \finfo_close($fInfo);
        
        $ext = pathinfo($this->file['name'], PATHINFO_EXTENSION);
            
        // On linux PHP says the mime type of an xlsx is application/zip, which is handy...
        if ( ($mime != 'application/vnd.ms-excel' && $mime != 'application/zip' && $mime != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') || $ext != 'xlsx'){
            $this->errors[] = 'Invalid file format. Expected: application/vnd.ms-excel or application/vnd.openxmlformats-officedocument.spreadsheetml.sheet (.xlsx) Found: ' . $mime . ' ('.$ext.')';
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
                
        
        
        // Generate an overview of the spreadsheet so we can see what has changed
        $now = time();
        $output = "";       
        
        $qualification = new \GT\Qualification\UserQualification($this->getQualID());
        if (!$qualification->isValid())
        {
            $this->errors[] = get_string('invalidqual', 'block_gradetracker');
            return false;
        }
        
        $unit = $qualification->getUnit($this->getUnitID());
        if (!$unit || !$unit->isValid())
        {
            $this->errors[] = get_string('invalidunit', 'block_gradetracker');
            return false;
        }
        
        // Require PHPExcel library
        require_once $CFG->dirroot . '/lib/phpexcel/PHPExcel.php';
        
        // Open with PHPExcel reader
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($this->file['tmp_name']);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($this->file['tmp_name']);
        } catch(Exception $e){
            $this->errors[] = $e->getMessage();
            return false;
        }
                
        
        // Check it's a valid student datasheet
        $customProperties = $this->getFileCustomProperties($objPHPExcel);
        
        if ($customProperties['GT-DATASHEET-TYPE'] !== 'UNIT'){
            $this->errors[] = get_string('errors:import:datasheettype', 'block_gradetracker');
            return false;
        }
        
        // Save the tmp file to Moodledata so we can still use it when we click confirm
        $saveFile = \gt_save_file($this->file['tmp_name'], 'tmp', 'U_' . $this->getUnitID() . '_' . $this->getQualID() . '_' . $now . '.xlsx');
        if (!$saveFile){
            $this->errors[] = get_string('errors:save:file', 'block_gradetracker');
            return false;
        }    
        
        $this->tmpFile = $saveFile;
        
        
        // Get stuff from worksheets
        $unix = $objPHPExcel->getProperties()->getCreated();
                        
        $objPHPExcel->setActiveSheetIndex(0);
        $objWorksheet = $objPHPExcel->getActiveSheet();

        $lastCol = $objWorksheet->getHighestColumn();
        $lastCol++;
        $lastRow = $objWorksheet->getHighestRow();
        
        $commentWorkSheet = $objPHPExcel->getSheet(1);
        
        
        // See if anything has been updated in the DB since we downloaded the file
        $updates = $DB->get_records_sql("SELECT DISTINCT uc.*
                                        FROM {bcgt_user_criteria} uc
                                        INNER JOIN {user} u ON u.id = uc.userid
                                        INNER JOIN {bcgt_user_qual_units} uqu ON uqu.userid = u.id
                                        WHERE uqu.qualid = ? AND uqu.unitid = ? AND lastupdate > ?", array($this->getQualID(), $this->getUnitID(), $unix));
        
        if ($updates)
        {

            $output .= "<div class='gt_import_warning'>";
                $output .= "<b>".get_string('warning').":</b><br><br>";
                $output .= "<p>".get_string('importwarning', 'block_gradetracker')."</p>";
                
                foreach($updates as $update)
                {
                                                            
                    $criterion = $unit->getCriterion($update->critid);
                    if (!$criterion) continue;
                    
                    $updateBy = new \GT\User($update->lastupdateby);
                    $student = new \GT\User($update->userid);
                    
                    $value = new \GT\CriteriaAward($update->awardid);
                    $output .= sprintf( get_string('aupdatedtobbycatd', 'block_gradetracker'), "{$student->getDisplayName()} ({$criterion->getName()})", $value->getName(), $value->getShortName(), $updateBy->getDisplayName(), date('d-m-Y, H:i', $update->lastupdate)) . "<br>";

                }

            $output .= "</div>";
            $output .= "<br><br>";

        }
        
        // Key
        $output .= "<h3>".get_string('key', 'block_gradetracker')."</h3>";
        $output .= "<table class='gt_import_key'>";
            $output .= "<tr>";
                $output .= "<td class='updatedsince crit'>&nbsp;</td>";
                $output .= "<td>".get_string('import:datasheet:key:updatedsince', 'block_gradetracker')."</td>";
            $output .= "</tr>";

            $output .= "<tr>";
                $output .= "<td class='updatedinsheet crit'>&nbsp;</td>";
                $output .= "<td>".get_string('import:datasheet:key:updatedinsheet', 'block_gradetracker')."</td>";
            $output .= "</tr>";

            $output .= "<tr>";
                $output .= "<td class='updatedinsheet updatedsince crit'>&nbsp;</td>";
                $output .= "<td>".get_string('import:datasheet:key:updatedinboth', 'block_gradetracker')."</td>";
            $output .= "</tr>";

        $output .= "</table>";
        
        $output .= "<br>";
        $output .= "<h2 class='gt_c'>".$unit->getDisplayName()."</h2>";
        $output .= "<br>";
        
        $output .= "<p class='gt_c'><a href='#' onclick='gtToggleImportGridTables(\"grades\");return false;'>".get_string('grades', 'block_gradetracker')."</a> | <a href='#' onclick='gtToggleImportGridTables(\"comments\");return false;'>".get_string('comments', 'block_gradetracker')."</a></p>";
        
            $output .= "<form action='' method='post' class='c'>";
                    
                $output .= "<div class='gt_import_grid_div'>";
            
                // Grades table
                $output .= "<table id='gt_import_grid_table_grades' class='gt_import_grid_table'>";

                    $output .= "<tr>";

                        $output .= "<th><input type='checkbox' onclick='gtImportToggleCheckBoxes(this, \"gt_import_stud_checkbox\");' checked /></th>";

                        for ($col = 'B'; $col != $lastCol; $col++){

                            $cellValue = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                            $output .= "<th>{$cellValue}</th>";

                        }

                    $output .= "</tr>";


                    // Loop through rows to get students
                    for ($row = 2; $row <= $lastRow; $row++)
                    {

                        $student = false;

                        // Loop columns
                        $rowClass = ( ($row % 2) == 0 ) ? 'even' : 'odd';

                        $output .= "<tr class='{$rowClass}'>";

                            for ($col = 'A'; $col != $lastCol; $col++){

                                $critClass = '';
                                $currentValue = get_string('na', 'block_gradetracker');                                 
                                $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                                // If first column, get the ID of the unit but don't print it out
                                if ($col == 'A'){

                                    $studentID = (int)$cellValue;

                                    // If no studentID at all, skip this row
                                    if (!$studentID){
                                        break;
                                    }

                                    // Check to make sure the studentID for this row matches the Grades and Comments sheets
                                    $commentsSheetStudentID = $commentWorkSheet->getCell($col . $row)->getCalculatedValue();
                                    if ($studentID != $commentsSheetStudentID)
                                    {
                                        $this->errors[] = get_string('invaliduser', 'block_gradetracker') . ' :: ' . "[{$row}] ({$studentID}) ({$commentsSheetStudentID})";
                                        break;
                                    }
                                    
                                    $student = new \GT\User($studentID);
                                    if (!$student->isValid()){
                                        $this->errors[] = get_string('invaliduser', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().")";
                                        break;
                                    }
                                    
                                    // Make sure student is actually on this qual and unit
                                    if (!$student->isOnQualUnit($qualification->getID(), $unit->getID(), "STUDENT")){
                                        $this->errors[] = get_string('usernotonunit', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().") - " . $unit->getDisplayName();
                                        break;
                                    }

                                    
                                    $unit->loadStudent($student);
                                    $output .= "<td><input type='checkbox' name='students[]' value='{$studentID}' class='gt_import_stud_checkbox' checked /></td>";
                                    continue; // Don't want to print the id out
                                }

                                elseif ($col == 'B' || $col == 'C' || $col == 'D')
                                {
                                    $output .= "<td>{$cellValue}</td>";
                                }

                                // Criteria we want to check for changes
                                else 
                                {

                                    $value = $cellValue;

                                    $critClass .= 'crit ';

                                    // Get studentCriteria to see if it has been updated since we downloaded the sheet
                                    $criteriaName = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                                    $studentCriterion = $unit->getCriterionByName($criteriaName);

                                    if ($studentCriterion)
                                    {

                                        $critDateUpdated = $studentCriterion->getUserLastUpdate();
                                        $valueObj = $studentCriterion->getUserAward();
                                        if ($valueObj)
                                        {
                                            $currentValueID = $valueObj->getID();
                                            $currentValue = $valueObj->getShortName();
                                        }

                                        if ($currentValue != $value){
                                            $critClass .= 'updatedinsheet ';
                                        }

                                        if ($critDateUpdated > $unix)
                                        {
                                            $critClass .= 'updatedsince ';
                                        }

                                        $output .= "<td class='{$critClass}' currentValue='{$currentValue}'><small>{$cellValue}</small></td>";

                                    } 
                                    else
                                    {

                                        // Was it an IV column?
                                        if ($qualification->getStructure() && $qualification->getStructure()->getSetting('iv_column') == 1)
                                        {

                                            $attribute = false;
                                            $ivDateString = get_string('iv', 'block_gradetracker') . ' - ' . get_string('date');
                                            $ivWhoString = get_string('iv', 'block_gradetracker') . ' - ' . get_string('verifier', 'block_gradetracker');

                                            // If it's a Date in the Excel file, it will return a number here
                                            // Otherwise treat it as text
                                            // So check if we are in the date column and if value is float
                                            if ($criteriaName == $ivDateString && is_float($value) && $value > 0)
                                            {
                                                $value = \gt_convert_excel_date_unix($value);
                                                $value = date('d-m-Y', $value);
                                            }

                                            // Get the name of the attribute
                                            if ($criteriaName == $ivDateString)
                                            {
                                                $attribute = 'IV_date';
                                            }
                                            elseif ($criteriaName == $ivWhoString)
                                            {
                                                $attribute = 'IV_who';
                                            }


                                            // If valid attribute
                                            if ($attribute)
                                            {

                                                $check = $DB->get_record("bcgt_unit_attributes", array("unitid" => $unit->getID(), "userid" => $studentID, "attribute" => $attribute));

                                                // If value in DB and sheet don't match
                                                if ( ($check && $check->value != $value) || (!$check && !is_null($value)) ){
                                                    $critClass .= 'updatedinsheet ';
                                                }

                                                // If the attribute's last update date is later than when we downloaded the datasheet
                                                if ($check && $check->lastupdate > $unix)
                                                {
                                                    $critClass .= 'updatedsince ';
                                                }

                                            }

                                            $output .= "<td class='{$critClass}'>{$value}</td>";

                                        }
                                        else
                                        {                                        
                                            $output .= "<td></td>";
                                        }
                                    }


                                } 


                            }


                        $output .= "</tr>";

                    }


                $output .= "</table>";



                // Comments table
                $output .= "<table id='gt_import_grid_table_comments' class='gt_import_grid_table' style='display:none;'>";

                    $output .= "<tr>";

                        $output .= "<th></th>";

                        for ($col = 'B'; $col != $lastCol; $col++){

                            $cellValue = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                            $output .= "<th>{$cellValue}</th>";

                        }

                    $output .= "</tr>";


                    // Loop through rows to get students
                    for ($row = 2; $row <= $lastRow; $row++)
                    {

                        $student = false;

                        // Loop columns
                        $rowClass = ( ($row % 2) == 0 ) ? 'even' : 'odd';

                        $output .= "<tr class='{$rowClass}'>";

                            for ($col = 'A'; $col != $lastCol; $col++){

                                $critClass = '';
                                $currentValue = get_string('na', 'block_gradetracker');                                      
                                $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                                // If first column, get the ID of the unit but don't print it out
                                if ($col == 'A'){

                                    $studentID = (int)$cellValue;

                                    // If no studentID at all, skip this row
                                    if (!$studentID){
                                        break;
                                    }

                                    // Check to make sure the studentID for this row matches the Grades and Comments sheets
                                    $commentsSheetStudentID = $commentWorkSheet->getCell($col . $row)->getCalculatedValue();
                                    if ($studentID != $commentsSheetStudentID)
                                    {
                                        break;
                                    }

                                    $student = new \GT\User($studentID);
                                    if (!$student->isValid()){
                                        $this->errors[] = get_string('invaliduser', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().")";
                                        break;
                                    }

                                    $unit->loadStudent($student);
                                    $output .= "<td></td>";
                                    continue; // Don't want to print the id out

                                }

                                elseif ($col == 'B' || $col == 'C' || $col == 'D')
                                {
                                    $output .= "<td>{$cellValue}</td>";
                                }

                                // Criteria we want to check for changes
                                else 
                                {

                                    $value = $cellValue;

                                    $critClass .= 'crit ';

                                    // Get studentCriteria to see if it has been updated since we downloaded the sheet
                                    $criteriaName = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                                    $studentCriterion = $unit->getCriterionByName($criteriaName);

                                    if ($studentCriterion)
                                    {

                                        $critDateUpdated = $studentCriterion->getUserLastUpdate();
                                        $valueObj = $studentCriterion->getUserAward();
                                        $userComments = $studentCriterion->getUserComments();

                                        if ($valueObj)
                                        {
                                            $currentValueID = $valueObj->getID();
                                            $currentValue = $valueObj->getShortName();
                                        }

                                        // Comments
                                        $comment = $commentWorkSheet->getCell($col . $row)->getCalculatedValue();

                                        // If value in DB and sheet don't match
                                        if ($comment != $userComments){
                                            $critClass .= 'updatedinsheet ';
                                        }

                                        // If the criteria's last update date is later than when we downloaded the datasheet
                                        if ($critDateUpdated > $unix)
                                        {
                                            $critClass .= 'updatedsince ';
                                        }


                                        $output .= "<td class='{$critClass}' currentValue='{$currentValue}'><small>{$comment}</small></td>";

                                    } 
                                    else
                                    {
                                        $output .= "<td></td>";
                                    }


                                } 


                            }


                        $output .= "</tr>";

                    }


                $output .= "</table>";
                $output .= "<br>";
                        
            $output .= "</div>";
            
            $output .= "<input type='hidden' name='qualID' value='{$this->getQualID()}' />";
            $output .= "<input type='hidden' name='unitID' value='{$this->getUnitID()}' />";
            $output .= "<input type='hidden' name='now' value='{$now}' />";
            $output .= "<input type='submit' class='gt_btn gt_green gt_btn_small' name='confirm' value='".get_string('confirm')."' />";
            $output .= str_repeat("&nbsp;", 8);
            $output .= "<input type='button' class='gt_btn gt_red gt_btn_small' onclick='window.location.href=\"{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$this->getStudentID()}&qualID={$this->getQualID()}\";' value='".get_string('cancel')."' />";

        $output .= "</form>";
            
    
        $MSGS['output'] = $output;
        
        
        
    }
    
    
    
    
    
    /**
     * Check the student data sheet to make sure we can import it
     * @global \GT\type $CFG
     * @global \GT\type $DB
     * @global array $MSGS
     * @return boolean
     */
    public function checkFileClassDataSheet()
    {
        
        global $CFG, $DB, $MSGS;
        
        $assessmentView = optional_param('ass', false, PARAM_INT);
        
        if (!$this->getQualID()){
            $this->errors[] = get_string('invalidrecord', 'block_gradetracker');
            return false;
        }
        
        // Check file exists and no errors from upload
        if (!$this->file || $this->file['error'] > 0){
            $this->errors[] = get_string('errors:import:file', 'block_gradetracker');
            return false;
        }
        
        // Check tmp uploaded file exists
        if (!file_exists($this->file['tmp_name'])){
            $this->errors[] = get_string('filenotfound', 'block_gradetracker');
            return false;
        }
        
        // Check mime type of file to make sure it is csv
        $fInfo = \finfo_open(FILEINFO_MIME_TYPE);
            $mime = \finfo_file($fInfo, $this->file['tmp_name']);
        \finfo_close($fInfo);
        
        $ext = pathinfo($this->file['name'], PATHINFO_EXTENSION);
            
        // On linux PHP says the mime type of an xlsx is application/zip, which is handy...
        if ( ($mime != 'application/vnd.ms-excel' && $mime != 'application/zip' && $mime != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') || $ext != 'xlsx'){
            $this->errors[] = 'Invalid file format. Expected: application/vnd.ms-excel or application/vnd.openxmlformats-officedocument.spreadsheetml.sheet (.xlsx) Found: ' . $mime . ' ('.$ext.')';
            return false;
        }
        
        // Open file
        $fh = fopen($this->file['tmp_name'], 'r');
        if (!$fh){
            $this->errors[] = get_string('errors:import:open', 'block_gradetracker');
            return false;
        }
                
        
        
        // Generate an overview of the spreadsheet so we can see what has changed
        $output = "";
        $now = time();
        
        $qualification = new \GT\Qualification\UserQualification($this->getQualID());
        if (!$qualification->isValid())
        {
            $this->errors[] = get_string('invalidqual', 'block_gradetracker');
            return false;
        }
                
        
        // Require PHPExcel library
        require_once $CFG->dirroot . '/lib/phpexcel/PHPExcel.php';
        
        // Open with PHPExcel reader
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($this->file['tmp_name']);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($this->file['tmp_name']);
        } catch(Exception $e){
            $this->errors[] = $e->getMessage();
            return false;
        }
                
        // Check it's a valid student datasheet
        $customProperties = $this->getFileCustomProperties($objPHPExcel);
        
        if ($customProperties['GT-DATASHEET-TYPE'] !== 'CLASS'){
            $this->errors[] = get_string('errors:import:datasheettype', 'block_gradetracker');
            return false;
        }
                
        // Is it an assessment grid?
        if ($assessmentView && $customProperties['GT-DATASHEET-ASSESSMENT-VIEW'] != 1 ){
            $this->errors[] = get_string('errors:import:datasheettypeass', 'block_gradetracker');
            return false;
        }
        
        // If it not an assessment grid, but we uploaded an assessment spreadsheet?
        if (!$assessmentView && $customProperties['GT-DATASHEET-ASSESSMENT-VIEW'] == 1){
            $this->errors[] = get_string('errors:import:datasheettypeass', 'block_gradetracker');
            return false;
        }
        
        
        // Save the tmp file to Moodledata so we can still use it when we click confirm
        $saveFile = \gt_save_file($this->file['tmp_name'], 'tmp', 'C_' . $this->getQualID() . '_' . $now . '.xlsx');
        if (!$saveFile){
            $this->errors[] = get_string('errors:save:file', 'block_gradetracker');
            return false;
        }    
        
        $this->tmpFile = $saveFile;
        
        
        // Get stuff from worksheets
        $unix = $objPHPExcel->getProperties()->getCreated();
                        
        $objPHPExcel->setActiveSheetIndex(0);
        $objWorksheet = $objPHPExcel->getActiveSheet();

        $lastCol = $objWorksheet->getHighestColumn();
        $lastCol++;
        
        $lastRow = $objWorksheet->getHighestRow();
        
        $commentWorkSheet = $objPHPExcel->getSheet(1);
        
        
        // Key
        $output .= "<h3>".get_string('key', 'block_gradetracker')."</h3>";
        $output .= "<table class='gt_import_key'>";
            $output .= "<tr>";
                $output .= "<td class='updatedsince crit'>&nbsp;</td>";
                $output .= "<td>".get_string('import:datasheet:key:updatedsince', 'block_gradetracker')."</td>";
            $output .= "</tr>";

            $output .= "<tr>";
                $output .= "<td class='updatedinsheet crit'>&nbsp;</td>";
                $output .= "<td>".get_string('import:datasheet:key:updatedinsheet', 'block_gradetracker')."</td>";
            $output .= "</tr>";

            $output .= "<tr>";
                $output .= "<td class='updatedinsheet updatedsince crit'>&nbsp;</td>";
                $output .= "<td>".get_string('import:datasheet:key:updatedinboth', 'block_gradetracker')."</td>";
            $output .= "</tr>";

        $output .= "</table>";
        
        $output .= "<br>";
        
        $output .= "<form action='' method='post'>";
        
        
        // Assessment Grid
        if ($assessmentView)
        {
            
            // Get all the qualIDs as some assessment grids include other qualifications
            $studentIDArray = array();
            for ($row = 3; $row <= $lastRow; $row++)
            {
                $studentIDArray[] = $objWorksheet->getCell("A{$row}")->getCalculatedValue();
            }
            
            $placeholders = \gt_create_sql_placeholders($studentIDArray);
            $params = array($qualification->getID(), $unix);
            $params = array_merge($params, $studentIDArray);
                        
            // See if anything has been updated in the DB since we downloaded the file
            $updates = $DB->get_records_sql("SELECT ua.*
                                            FROM {bcgt_user_assessments} ua
                                            WHERE ua.qualid = ?
                                            AND ua.lastupdate > ?
                                            AND ua.userid IN ({$placeholders})", $params);
                                            
            if ($updates)
            {

                $output .= "<div class='gt_import_warning'>";
                    $output .= "<b>".get_string('warning').":</b><br><br>";
                    $output .= "<p>".get_string('importwarning', 'block_gradetracker')."</p>";
                    
                    foreach($updates as $update)
                    {

                        $student = new \GT\User($update->userid);
                        $assessment = new \GT\Assessment($update->assessmentid);
                        $updateBy = new \GT\User($update->lastupdateby);
                        
                        // Grade
                        $grade = new \GT\CriteriaAward($update->grade);
                        
                        // Ceta
                        $ceta = new \GT\QualificationAward($update->ceta);
                        
                        $gradeValue = $grade->getShortName();
                        if ($assessment->getSetting('grading_method') == 'numeric'){
                            $gradeValue = $update->score;
                        }
                                                
                        // Grade
                        $output .= sprintf( get_string('aupdatedtobbycatd', 'block_gradetracker'), $student->getDisplayName() . " " . $assessment->getName(), "GRADE ({$gradeValue}), CETA ({$ceta->getName()})", \gt_html($update->comments), $updateBy->getDisplayName(), date('d-m-Y, H:i', $update->lastupdate)) . "<br>";

                    }

                $output .= "</div>";
                $output .= "<br><br>";

            }
                      
            $output .= "<h2 class='gt_c'>".$qualification->getDisplayName()."</h2>";
            $output .= "<br>";
            
            $output .= "<div class='gt_import_grid_div'>";

                $output .= "<p class='gt_c'><a href='#' onclick='gtToggleImportGridTables(\"grades\");return false;'>".get_string('grades', 'block_gradetracker')."</a> | <a href='#' onclick='gtToggleImportGridTables(\"comments\");return false;'>".get_string('comments', 'block_gradetracker')."</a></p>";

                // Grades sheet
                $output .= "<table id='gt_import_grid_table_grades' class='gt_import_grid_table'>";

                    $output .= "<tr>";

                        $output .= "<th><input type='checkbox' onclick='gtImportToggleCheckBoxes(this, \"gt_import_unit_checkbox\");' checked /></th>";
                        $output .= "<th>".get_string('firstname')."</th>";
                        $output .= "<th>".get_string('lastname')."</th>";
                        $output .= "<th>".get_string('username')."</th>";
                        
                        $assessmentsArray = array();
                        
                        for ($col = 'E'; $col != $lastCol; $col++){

                            $cellValue = $objWorksheet->getCell($col . "1")->getCalculatedValue();

                            // If the cell is not empty
                            if ($cellValue != ''){
                                
                                preg_match("/^\[([0-9]+)\]/", $cellValue, $matches);
                                $id = (isset($matches[1])) ? $matches[1] : false;
                                
                                // If format of column is valid and we got an ID out of it
                                if ($id){
                                    $assessmentsArray[$id] = array('id' => $id, 'name' => $cellValue, 'colspan' => 1, 'startingCell' => $col);
                                }
                                
                            } elseif ($assessmentsArray) {
                                
                                // Else if it's blank, it must be merged with a previous cell, so increment colspan
                                end($assessmentsArray);
                                $key = key($assessmentsArray);
                                $assessmentsArray[$key]['colspan']++;
                                
                            }

                        }
                        

                        // Now loop through the assessmentArray, since we know the colspans to use
                        if ($assessmentsArray)
                        {
                            foreach($assessmentsArray as $ass)
                            {
                                $output .= "<th colspan='{$ass['colspan']}'>{$ass['name']}</th>";
                            }
                        }
                        
                    $output .= "</tr>";
                    
                    // Now loop through the second row, which shows the column, e.g. Grade, CETA or a custom field
                    $output .= "<tr>";
                    
                        $output .= "<th></th>";
                        $output .= "<th></th>";
                        $output .= "<th></th>";
                        $output .= "<th></th>";
                        
                        for ($col = 'E'; $col != $lastCol; $col++){

                            $cellValue = $objWorksheet->getCell($col . "2")->getCalculatedValue();
                            $output .= "<th>{$cellValue}</th>";

                        }
                    
                    $output .= "</tr>";
                    
                    
                    // Loop through qualifications
                    for ($row = 3; $row <= $lastRow; $row++)
                    {
                        
                        $rowClass = ( ($row % 2) == 0 ) ? 'even' : 'odd';
                        
                        $output .= "<tr class='{$rowClass}'>";      
                            
                            for ($col = 'A'; $col != $lastCol; $col++){
                                
                                $cellClass = '';
                                $currentValue = get_string('na', 'block_gradetracker');                                        
                                $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                                // If first column, get the ID of the unit but don't print it out
                                if ($col == 'A'){
                                    
                                    $studentID = (int)$cellValue;    
                                    
                                    // If no studentID at all, skip this row
                                    if (!$studentID){
                                        break;
                                    }

                                    $student = new \GT\User($studentID);
                                    if (!$student->isValid()){
                                        $this->errors[] = get_string('invaliduser', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().")";
                                        break;
                                    }
                                    
                                    if (!$qualification->loadStudent($studentID)){
                                        $this->errors[] = sprintf( get_string('import:datasheet:process:error:stud', 'block_gradetracker'), $objWorksheet->getCell("B".$row)->getCalculatedValue() );
                                        break;
                                    }
                                    
                                    $output .= "<td><input type='checkbox' name='studs[]' value='{$studentID}' class='gt_import_unit_checkbox' checked /></td>";
                                                                        
                                }
                                
                                elseif ($col == 'B' || $col == 'C' || $col == 'D')
                                {
                                    $output .= "<td>{$cellValue}</td>";
                                }
                                
                                // Assessment we want to check for changes
                                elseif ($col != 'A' && $col != 'B' && $col != 'C' && $col != 'D'){

                                    // Work out the merged cell that has the assessment ID in, based on
                                    // which cell we are in now and the colspan of the parent
                                    $assessment = self::findAssessmentParentColumn($assessmentsArray, $col);
                                    if (!$assessment){
                                        $this->errors[] = get_string('import:datasheet:process:error:ass', 'block_gradetracker' ) . ": {$col}";
                                        $output .= "<td>-</td>";
                                        continue;
                                    }
                                    
                                    
                                    // Get the cell value of the column this is in, so we can see if it's
                                    // a Grade column, a CETA column or a Custom Field
                                    $column = $objWorksheet->getCell($col . 2)->getCalculatedValue();
                                    $column = strtolower($column);
                                                                        
                                    // Student Assessment
                                    $studentAssessment = $qualification->getUserAssessment($assessment['id']);
                                    
                                    // If can't load it on this qual, must not be attached to this qual
                                    if (!$studentAssessment){
                                        $output .= "<td>-</td>";
                                        continue;
                                    }

                                                                      
                                    // Grade cell
                                    if ($column == 'grade')
                                    {
                                        
                                        $gradingMethod = $studentAssessment->getSetting('grading_method');
                                        if ($gradingMethod == 'numeric')
                                        {
                                            
                                            // Check the current score of this assessment
                                            $currentValue = $studentAssessment->getUserScore();
                                            
                                        }
                                        else
                                        {
                                        
                                            // Check the current grade of this assessment
                                            $currentGrade = $studentAssessment->getUserGrade();
                                            $currentValue = ($currentGrade) ? $currentGrade->getShortName() : '';
                                            $dateAssessmentUpdated = $studentAssessment->getUserLastUpdate();
                                        
                                        }
                                        
                                        // The default name of the CriteriaAward if none is set is N/A,
                                        // so if the cell is blank, a comparison of blank and "N/A" won't match
                                        // and it will think something has changed even though it hasn't
                                        // So change the cell value to N/A in order for the comparison to work
                                        if ($cellValue == ''){
                                            $cellValue = get_string('na', 'block_gradetracker');
                                        }
                                      
                                        // If value in DB and sheet don't match
                                        if ($currentValue != $cellValue){
                                            $cellClass .= 'updatedinsheet ';
                                        }

                                        // If the assessment's last update date is later than when we downloaded the datasheet
                                        if ($dateAssessmentUpdated > $unix){
                                            $cellClass .= 'updatedsince ';
                                        }
                                        
                                    }

                                    // CETA cell
                                    elseif ($column == 'ceta')
                                    {
                                        
                                        // Check the current CETA of this assessment
                                        $currentCeta = $studentAssessment->getUserCeta();
                                        $currentValue = ($currentCeta) ? $currentCeta->getName() : '';
                                        $dateAssessmentUpdated = $studentAssessment->getUserLastUpdate();
                                        
                                        // If value in DB and sheet don't match
                                        if ($currentValue != $cellValue){
                                            $cellClass .= 'updatedinsheet ';
                                        }

                                        // If the assessment's last update date is later than when we downloaded the datasheet
                                        if ($dateAssessmentUpdated > $unix){
                                            $cellClass .= 'updatedsince ';
                                        }
                                        
                                        // For display purposes, change blank cell into N/A as it looks better
                                        if ($cellValue == ''){
                                            $cellValue = get_string('na', 'block_gradetracker');
                                        }
                                        
                                    }

                                    // Custom Form Field
                                    elseif (preg_match("/^\[([0-9]+)\]/", $column, $matches))
                                    {
                                        
                                        $fieldID = (isset($matches[1])) ? $matches[1] : false;
                                        $field = new \GT\FormElement($fieldID);
                                        
                                        // Check the current value of this custom field
                                        $currentCustomField = $studentAssessment->getCustomFieldValue($field, 'v', false);
                                        $currentValue = ($currentCustomField) ? $currentCustomField : '';
                                        $dateAssessmentUpdated = $studentAssessment->getUserLastUpdate();

                                        // If value in DB and sheet don't match
                                        if ($currentValue != $cellValue){
                                            $cellClass .= 'updatedinsheet ';
                                        }
                                        
                                        // If the assessment's last update date is later than when we downloaded the datasheet
                                        if ($dateAssessmentUpdated > $unix){
                                            $cellClass .= 'updatedsince ';
                                        }
                                        
                                        // For display purposes, change blank cell into N/A as it looks better
                                        if ($cellValue == ''){
                                            $cellValue = get_string('na', 'block_gradetracker');
                                        }
                                        
                                    }
                                    
                                    // Display the cell
                                    $output .= "<td class='{$cellClass}'>";
                                        $output .= $cellValue;
                                    $output .= "</td>";
                                    

                                } 
                                
                            }
                        
                        $output .= "</tr>";
                        
                    }


                // End of grades sheet
                $output .= "</table>";
                
                
                
                
                
                // Comments sheet
                $lastCol = $commentWorkSheet->getHighestColumn();
                $lastCol++;
                
                $output .= "<table id='gt_import_grid_table_comments' class='gt_import_grid_table' style='display:none;'>";

                    $output .= "<tr>";

                        $output .= "<th></th>";
                        $output .= "<th>".get_string('firstname')."</th>";
                        $output .= "<th>".get_string('lastname')."</th>";
                        $output .= "<th>".get_string('username')."</th>";
                        
                        // Now loop through the assessmentArray, since we know the colspans to use
                        if ($assessmentsArray)
                        {
                            foreach($assessmentsArray as $ass)
                            {
                                $output .= "<th>{$ass['name']}</th>";
                            }
                        }
                        
                    $output .= "</tr>";
                    
                                        
                    // Loop through qualifications
                    for ($row = 3; $row <= $lastRow; $row++)
                    {
                        
                        $rowClass = ( ($row % 2) == 0 ) ? 'even' : 'odd';
                        
                        $output .= "<tr class='{$rowClass}'>";      
                            
                            for ($col = 'A'; $col != $lastCol; $col++){
                                
                                $cellClass = '';
                                $currentValue = get_string('na', 'block_gradetracker');                                        
                                $cellValue = $commentWorkSheet->getCell($col . $row)->getCalculatedValue();

                                // If first column, get the ID of the unit but don't print it out
                                if ($col == 'A'){
                                    
                                    $studentID = (int)$cellValue;    
                                    
                                    // If no studentID at all, skip this row
                                    if (!$studentID){
                                        break;
                                    }

                                    $student = new \GT\User($studentID);
                                    if (!$student->isValid()){
                                        $this->errors[] = get_string('invaliduser', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().")";
                                        break;
                                    }
                                    
                                    if (!$qualification->loadStudent($studentID)){
                                        $this->errors[] = sprintf( get_string('import:datasheet:process:error:stud', 'block_gradetracker'), $objWorksheet->getCell("B".$row)->getCalculatedValue() );
                                        break;
                                    }
                                    
                                    $output .= "<td></td>";
                                                                        
                                }
                                
                                elseif ($col == 'B' || $col == 'C' || $col == 'D')
                                {
                                    $output .= "<td>{$cellValue}</td>";
                                }
                                
                                // Assessment we want to check for changes
                                elseif ($col != 'A' && $col != 'B' && $col != 'C' && $col != 'D'){

                                    $parentColumn = $commentWorkSheet->getCell($col . 1)->getCalculatedValue();
                                    
                                    // Get the assessment ID from the name
                                    preg_match("/^\[([0-9]+)\]/", $parentColumn, $matches);
                                    if (!isset($matches[1])){
                                        $this->errors[] = get_string('import:datasheet:process:error:ass', 'block_gradetracker');
                                        $output .= "<td>-</td>";
                                        continue;
                                    }
                                    
                                    $assessmentID = $matches[1];
                                    
                                    
                                    // Student Assessment
                                    $studentAssessment = $qualification->getUserAssessment($assessmentID);
                                    
                                    // If can't load it on this qual, must not be attached to this qual
                                    if (!$studentAssessment){
                                        $output .= "<td>-</td>";
                                        continue;
                                    }
                                    
                                    
                                    // Get current comments                                 
                                    $userComments = $studentAssessment->getUserComments();
                                    $dateAssessmentUpdated = $studentAssessment->getUserLastUpdate();
                                    
                                    if (is_null($cellValue)){
                                        $cellValue = '';
                                    }
                                   
                                    
                                    // If value in DB and sheet don't match
                                    if ($userComments != $cellValue){
                                        $cellClass .= 'updatedinsheet ';
                                    }
                                        
                                    // If the assessment's last update date is later than when we downloaded the datasheet
                                    if ($dateAssessmentUpdated > $unix){
                                        $cellClass .= 'updatedsince ';
                                    }

                                    
                                    // Display the cell
                                    $output .= "<td class='{$cellClass}'>";
                                        $output .= $cellValue;
                                    $output .= "</td>";
                                    

                                } 
                                
                            }
                        
                        $output .= "</tr>";
                        
                    }


                // End of comments sheet
                $output .= "</table>";
                
                
                
                
            $output .= "</div>";
            
            
        }
        else
        {
        
        
            // Get stuff from worksheets
            $unix = $objPHPExcel->getProperties()->getCreated();
            $cntSheets = $objPHPExcel->getSheetCount();

            $uOutput = "";

            // Loop through the worksheets (each unit has its own worksheet)
            for($sheetNum = 0; $sheetNum < $cntSheets; $sheetNum++)
            {

                $objPHPExcel->setActiveSheetIndex($sheetNum);
                $objWorksheet = $objPHPExcel->getActiveSheet();

                $sheetName = $objWorksheet->getTitle();
                preg_match("/^\((\d+)\)/", $sheetName, $matches);
                if (!isset($matches[1])){
                    $this->errors[] = get_string('invalidunit', 'block_gradetracker') . ' - ' . $sheetName;
                    continue;
                }

                $unitID = $matches[1];
                $unit = $qualification->getUnit($unitID);
                if (!$unit){
                    $this->errors[] = get_string('invalidunit', 'block_gradetracker') . ' - ' . $sheetName;
                    continue;
                }


                $lastCol = $objWorksheet->getHighestColumn();
                $lastCol++;
                $lastRow = $objWorksheet->getHighestRow();

                $output .= "<a href='#unit{$unitID}' title='".$unit->getDisplayName()."'>".\gt_cut_string( $unit->getDisplayName(), 20 )."</a> &nbsp;&nbsp; ";

                $uOutput .= "<div id='gt_class_import_unit_{$unitID}' class='gt_class_import_unit_div'>";

                $uOutput .= "<br>";
                $uOutput .= "<a name='unit{$unitID}'></a>";
                $uOutput .= "<h3>{$unit->getDisplayName()}</h3><br>";

                // See if anything has been updated in the DB since we downloaded the file
                $updates = $DB->get_records_sql("SELECT DISTINCT uc.*, usr.firstname, usr.lastname
                                                FROM {bcgt_user_criteria} uc
                                                INNER JOIN {bcgt_criteria} c ON c.id = uc.critid
                                                INNER JOIN {bcgt_units} u ON u.id = c.unitid
                                                INNER JOIN {user} usr ON usr.id = uc.userid
                                                INNER JOIN {bcgt_qual_units} qu ON qu.unitid = u.id
                                                INNER JOIN {bcgt_user_qual_units} uqu ON ( uqu.unitid = u.id AND uqu.userid = usr.id AND uqu.qualid = qu.qualid )
                                                WHERE uqu.qualid = ? AND uqu.unitid = ? AND uc.lastupdate > ?
                                                ORDER BY usr.lastname, usr.firstname, uc.lastupdate", 
                                                array($this->getQualID(), $unit->getID(), $unix));


                if ($updates)
                {

                    $uOutput .= "<div class='gt_import_warning'>";
                        $uOutput .= "<b>".get_string('warning').":</b><br><br>";
                        $uOutput .= "<p>".get_string('importwarning', 'block_gradetracker')."</p>";

                        foreach($updates as $update)
                        {

                            $criterion = $unit->getCriterion($update->critid);
                            if (!$criterion) continue;

                            $stud = new \GT\User($update->userid);
                            $updateBy = new \GT\User($update->lastupdateby);

                            $value = new \GT\CriteriaAward($update->awardid);
                            $uOutput .= sprintf( get_string('aupdatedtobbycatd', 'block_gradetracker'), "{$stud->getDisplayName()} ({$criterion->getName()})", $value->getName(), \gt_html($update->comments), $updateBy->getDisplayName(), date('d-m-Y, H:i', $update->lastupdate)) . "<br>";

                        }

                    $uOutput .= "</div>";
                    $uOutput .= "<br><br>";

                }


                $uOutput .= "<div class='gt_import_grid_div'>";

                    $uOutput .= "<table class='gt_import_grid_table'>";

                        $uOutput .= "<tr>";

                            $uOutput .= "<th><input type='checkbox' onclick='gtImportToggleCheckBoxes(this, \"gt_import_stud_checkbox_{$unitID}\");' checked /></th>";

                            for ($col = 'B'; $col != $lastCol; $col++){

                                $cellValue = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                                $uOutput .= "<th>{$cellValue}</th>";

                            }

                        $uOutput .= "</tr>";


                        // Loop through rows to get students
                        for ($row = 2; $row <= $lastRow; $row++)
                        {

                            $student = false;

                            // Loop columns
                            $rowClass = ( ($row % 2) == 0 ) ? 'even' : 'odd';

                            $uOutput .= "<tr class='{$rowClass}'>";

                                for ($col = 'A'; $col != $lastCol; $col++){

                                    $critClass = '';
                                    $currentValue = get_string('na', 'block_gradetracker');                                    
                                    $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                                    // If first column, get the ID of the unit but don't print it out
                                    if ($col == 'A'){

                                        $studentID = (int)$cellValue;

                                        // If no studentID at all, skip this row
                                        if (!$studentID){
                                            break;
                                        }

                                        $student = new \GT\User($studentID);
                                        if (!$student->isValid()){
                                            $this->errors[] = get_string('invaliduser', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().")";
                                            break;
                                        }
                                        
                                        // Make sure student is actually on this qual and unit
                                        if (!$student->isOnQualUnit($qualification->getID(), $unit->getID(), "STUDENT")){
                                            $this->errors[] = get_string('usernotonunit', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().") - " . $unit->getDisplayName();
                                            break;
                                        }
                                        

                                        $unit->loadStudent($student);
                                        $uOutput .= "<td><input type='checkbox' name='unit_students[{$unitID}][]' value='{$studentID}' class='gt_import_stud_checkbox_{$unitID}' checked /></td>";
                                        continue; // Don't want to print the id out

                                    }

                                    elseif ($col == 'B' || $col == 'C' || $col == 'D')
                                    {
                                        $uOutput .= "<td>{$cellValue}</td>";
                                    }

                                    // Criteria we want to check for changes
                                    else 
                                    {

                                        $value = $cellValue;

                                        $critClass .= 'crit ';

                                        // Get studentCriteria to see if it has been updated since we downloaded the sheet
                                        $criteriaName = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                                        $studentCriterion = $unit->getCriterionByName($criteriaName);

                                        if ($studentCriterion)
                                        {

                                            $critDateUpdated = $studentCriterion->getUserLastUpdate();
                                            $valueObj = $studentCriterion->getUserAward();
                                            if ($valueObj)
                                            {
                                                $currentValue = $valueObj->getShortName();
                                            }

                                            if ($currentValue != $value){
                                                $critClass .= 'updatedinsheet ';
                                            }

                                            if ($critDateUpdated > $unix)
                                            {
                                                $critClass .= 'updatedsince ';
                                            }

                                            $uOutput .= "<td class='{$critClass}' currentValue='{$currentValue}'><small>{$cellValue}</small></td>";

                                        } 
                                        else
                                        {
                                            $uOutput .= "<td></td>";
                                        }


                                    } 


                                }


                            $uOutput .= "</tr>";

                        }


                    $uOutput .= "</table>";

                $uOutput .= "</div>";

                $uOutput .= "</div>";

            }


            $output .= $uOutput;
        
        }

        
        $output .= "<input type='hidden' name='qualID' value='{$this->getQualID()}' />";
        $output .= "<input type='hidden' name='now' value='{$now}' />";
        $output .= "<div class='gt_c'>";
        $output .= "<input type='submit' class='gt_btn gt_green gt_btn_small' name='confirm' value='".get_string('confirm')."' />";
        $output .= str_repeat("&nbsp;", 8);
        $output .= "<input type='button' class='gt_btn gt_red gt_btn_small' onclick='window.location.href=\"{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$this->getStudentID()}&qualID={$this->getQualID()}\";' value='".get_string('cancel')."' />";
        $output .= "</div>";
        
        $output .= "</form>";
        
        
        $MSGS['output'] = $output;
                
    }
    
    /**
     * Get the custom properties off the file object
     * @param type $objPHPExcel
     * @return type
     */
    protected function getFileCustomProperties($objPHPExcel){
        
        // Check it's a valid student datasheet
        return array(
            'GT-DATASHEET-TYPE' => $objPHPExcel->getProperties()->getCustomPropertyValue("GT-DATASHEET-TYPE"),
            'GT-DATASHEET-DOWNLOADED' => $objPHPExcel->getProperties()->getCustomPropertyValue("GT-DATASHEET-DOWNLOADED"),
            'GT-DATASHEET-ASSESSMENT-VIEW' => $objPHPExcel->getProperties()->getCustomPropertyValue("GT-DATASHEET-ASSESSMENT-VIEW")
        );
        
    }
    
    /**
     * Strip out the "GCSE" in the name of the QoE qualification, so we can more accurately get rid of duplicates
     * e.g. some students have records for "GCSE English Literature" and "GCSE English in Literature"
     * @param type $name
     */
    protected function stripQoENames(&$name){
        
        $name = preg_replace("/^(.*?)GCSE in /i", "", $name);
        $name = preg_replace("/^(.*?)GCSE /i", "", $name);
        $name = preg_replace("/^(.*?)Short Course GCSE /i", "", $name);
        $name = preg_replace("/^(.*?)\(Short Course\) in /i", "", $name);
        $name = trim($name);
        
    }
    
    
}
