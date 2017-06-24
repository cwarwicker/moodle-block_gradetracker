<?php
/**
 * Set of global functions to be used across the plugin
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


require_once $CFG->dirroot . '/blocks/gradetracker/GradeTracker.class.php';

// Find all classes in /classes and include them
gt_require_classes('classes');


/**
 * Require all the php classes in given directory & sub directories
 * @global type $CFG
 * @param type $dir
 */
function gt_require_classes($dir){
    
    global $CFG;
        
    // Find sub folders
    if ($open = opendir($CFG->dirroot . '/blocks/gradetracker/' . $dir))
    {
        while ( ($file = readdir($open)) !== false )
        {
            
            if ($file == '.' || $file == '..') continue;
            
            if (strpos($file, '.php') === false)
            {
                gt_require_classes($dir . '/' . $file);
            }
            
        }
    }
    
    // Now just load any files in this folder
    foreach( glob("{$CFG->dirroot}/blocks/gradetracker/{$dir}/*.class.php") as $file ){
        require_once $file;    
    }
    
}

/**
 * Print something to a test file. Useful for debugging AJAX requests.
 * @global type $CFG
 * @param type $value
 */
function gt_pn($value)
{
    global $CFG;
    $file = fopen($CFG->dirroot . '/blocks/gradetracker/tmp.txt', 'a');
    if ($file){
        fwrite($file, print_r($value, true));
        fwrite($file, "\n");
        fclose($file);
    }
}

/**
 * Write a trace to any given file in the data directory
 * @param type $file
 * @param type $text
 */
function gt_trace($file, $text)
{
    
    $file = fopen($file, 'a');
    if ($file)
    {
        fwrite($file, "[".date('d-m-Y H:i:s')."] " . $text . "\n");
        fclose($file);
    }
    
}

function gt_enable_debugging(){
    $_SESSION['gt_debug'] = true;
}

function gt_disable_debugging(){
    unset($_SESSION['gt_debug']);
}


/**
 * Is GT debugging turned on?
 * @return type
 */
function gt_is_debugging(){
    return (isset($_SESSION['gt_debug']));
}

/**
 * Add something to debug log
 * @global type $USER
 * @param type $info
 */
function gt_debug($info){
    
    global $USER;
    
    if (!gt_is_debugging()) return false;
    
    // Create directory if it doersn't exist
    \gt_create_data_directory("debug");
    
    $file = fopen( \GT\GradeTracker::dataroot() . "/debug/{$USER->id}.txt" , 'a');
    if ($file){
        fwrite($file, "[".date('d-m-Y H:i:s')."] " . $info . "\n");
        fclose($file);
    }
    
    return true;
    
}

/**
 * Exit a script, optionally logging a debug message
 * @param type $debug
 */
function gt_stop($debug){
    
    if (strlen($debug) > 0){
        \gt_debug($debug);
    }
    
    exit;
    
}

/**
 * Loop through all the user's contexts and see if they have the given capability on any of them
 * This saves passing a course id through every form/page.
 * @param type $cap
 * @param type $access If null, load the contexts
 * @param type $thisContext If we want to check a specific course context
 */
function gt_has_capability($cap, $access = false, $thisContext = false, $user = false)
{
    
    $GT = new \GT\GradeTracker();
    
    // if we want to check a specific course context then use that
    if ($thisContext)
    {
        return has_capability($cap, $thisContext);
    }
    
    
    // Otherwise check for capabilities across all our contexts
    // (If we want to check against the contexts of a different user, use gt_has_user_capability())
    if (!$access){
        
        // If user is set with contexts already loaded, use those
        if ($user && $user->getContextArray()){
            $access = $user->getContextArray();
        }
        
        // Otherwise, if we have a user, but the contexts haven't been loaded yet
        else {
            $access = $GT->getUserContexts();
            if ($user){
                $user->setContextArray($access);
            }
        }        
        
    }
    
    // Now use the access array of contexts
    if ($access)
    {
        foreach($access as $context)
        {
            if (has_capability($cap, $context))
            {
                return true;
            }
        }
    }
    
    return false;
    
}

/**
 * Do we have a particular capability in any of the contexts of the given user?
 * 
 * For example, say we want to look at the student grid for student ID 1732
 * For this we need capability view_student_grids, however we might have that capability for Course 1, and
 * the student might be on Course 2 and Course 3, so whilst we have the capability we shouldn't be able to 
 * view them.
 * 
 * Similarly, since they can be on multiple courses and so can we, we don't have a specific course id passed in
 * 
 * So to find out if we have the capability for this user, we want to find all the courses the user is on.
 * So in this case that will be Course 2 and Course 3 and get the contexts of those.
 * 
 * Then we loop through those contexts and check to see if we have the view_stud_grid capability on any of those.
 * We will only have the capability on that context if we are also on that course.
 * 
 * @param type $cap
 * @param type $userID
 */
function gt_has_user_capability($cap, $userID)
{
    global $GT;
    if (!isset($GT)){
        $GT = new \GT\GradeTracker();
    }
    $contexts = $GT->getUserContexts($userID);    
    return gt_has_capability($cap, $contexts);
    
}


/**
 * Get a success alert box
 * @param type $text
 * @param type $title
 * @return string
 */
function gt_success_alert_box($text)
{
    
    $output = "";
    $output .= "<div class='gt_alert_good fade in'>";
    
        if (is_array($text))
        {
            $output .= "<strong>".get_string('success', 'block_gradetracker')."</strong><br>";
            foreach($text as $t)
            {
                $output .= "<span>{$t}</span><br>";
            }
        }
        else
        {
            $output .= "<strong>".get_string('success', 'block_gradetracker')."</strong> ";
            $output .= "<span>{$text}</span>";
        }
        
    $output .= "</div>";
    
    return $output;
    
}

/**
 * Get an error alert box
 * @param type $text
 * @param type $title
 * @return string
 */
function gt_error_alert_box($text)
{
    
    $output = "";
    $output .= "<div class='gt_alert_bad fade in'>";
        
        if (is_array($text))
        {
            
            $output .= "<strong>".get_string('errors', 'block_gradetracker')."</strong><br>";
            foreach($text as $t)
            {
                if (is_array($t))
                {
                    foreach($t as $t2)
                    {
                        $output .= "<span>{$t2}</span><br>";
                    }
                }
                else
                {
                    $output .= "<span>{$t}</span><br>";
                }
            }
        }
        else
        {
            $output .= "<strong>".get_string('error', 'block_gradetracker')."</strong> ";
            $output .= "<span>{$text}</span>";
        }
        
    $output .= "</div>";
    
    return $output;
    
}


/**
 * Get a success alert box
 * @param type $text
 * @param type $title
 * @return string
 */
function gt_info_alert_box($text)
{
    
    $output = "";
    $output .= "<div class='gt_alert_info fade in'>";
    
        if (is_array($text))
        {
            $output .= "<strong>".get_string('info', 'block_gradetracker')."</strong><br>";
            foreach($text as $t)
            {
                $output .= "<span>{$t}</span><br>";
            }
        }
        else
        {
            $output .= "<strong>".get_string('info', 'block_gradetracker')."</strong> ";
            $output .= "<span>{$text}</span>";
        }
        
    $output .= "</div>";
    
    return $output;
    
}



/**
 * Get a success alert box
 * @param type $text
 * @param type $title
 * @return string
 */
function gt_warning_alert_box($text)
{
    
    $output = "";
    $output .= "<div class='gt_alert_warning fade in'>";
    
        if (is_array($text))
        {
            $output .= "<strong>".get_string('warning', 'block_gradetracker')."</strong><br>";
            foreach($text as $t)
            {
                $output .= "<span>{$t}</span><br>";
            }
        }
        else
        {
            $output .= "<strong>".get_string('warning', 'block_gradetracker')."</strong> ";
            $output .= "<span>{$text}</span>";
        }
        
    $output .= "</div>";
    
    return $output;
    
}

/**
 * Create a random string
 * @param type $length
 * @return string
 */
function gt_rand_str($length)
{

    $str = "987654321AaBbCcDdEeFfGgHhJjKkMmNnPpQqRrSsTtUuVvWwXxYyZz123456789";

    $count = strlen($str) - 1;

    $output = "";

    for($i = 0; $i < $length; $i++)
    {
        $output .= $str[mt_rand(0, $count)];
    }

    return $output;

}


/** 
 * Create directory in Moodledata to store files
 * Will create the directory: /moodledata/gradetracker/$dir
 * Will attempt to create the parent directories if they don't exist yet
 * Uses chmod of 0764:
 *      Owner: rwx, 
 *      Group: rw, 
 *      Public: r
 *  @param type $dir
 */
function gt_create_data_directory($dir)
{

    global $CFG;

    // Check for main plugin directory
    if (!is_dir( \GT\GradeTracker::dataroot() )){
        if (is_writeable($CFG->dataroot)){
            if (!mkdir(\GT\GradeTracker::dataroot(), 0755, true)){
                return false;
            }
        } else {
            return false;
        }
    }


    // Now try and make the actual dir we want
    if (!is_dir( \GT\GradeTracker::dataroot() . '/' . $dir )){
        if (is_writeable(\GT\GradeTracker::dataroot())){
            if (!mkdir(\GT\GradeTracker::dataroot() . '/' . $dir, 0755, true)){
                return false;
            }
        } else {
            return false;
        }
    }

    // If we got this far must be ok
    return true;


}



/**
 * For a given file path create a code we can use to download that file
 * @global type $DB
 * @param type $path
 * @return type
 */
function gt_create_data_path_code($path){
    
    global $DB;
    
    // See if one already exists for this path
    $record = $DB->get_record("bcgt_file_codes", array("path" => $path));
    if ($record){
        return $record->code;
    }

    // Create one
    $code = gt_rand_str(10);

    // Unlikely, but check if code has already been used
    $cnt = $DB->count_records("bcgt_file_codes", array("code" => $code));
    while ($cnt > 0)
    {
        $code = gt_rand_str(10);
        $cnt = $DB->count_records("bcgt_file_codes", array("code" => $code));
    }
    

    $ins = new \stdClass();
    $ins->path = $path;
    $ins->code = $code;

    $DB->insert_record("bcgt_file_codes", $ins);
    return $code;
    
}

/**
 * Get the code for a data path
 * @global type $DB
 * @param type $path
 * @return boolean
 */
function gt_get_data_path_code($path)
{
    global $DB;
    
    // See if one already exists for this path
    $record = $DB->get_record("bcgt_file_codes", array("path" => $path));
    if ($record){
        return $record->code;
    }
    
    return false;
    
}

/**
 * Save a string to a file
 * @param type $data
 * @param type $file
 */
function gt_save_file_contents($data, $file){
    
    // Create directory if it doersn't exist
    \gt_create_data_directory("tmp/data");
    
    $path = \GT\GradeTracker::dataroot() . "/tmp/data/{$file}";
    
    $file = fopen( $path , 'a');
    if ($file){
        fwrite($file, $data);
        fclose($file);
        return $path;
    } else {
        return false;
    }
        
}

/**
 * Save a file to a given path within the gradetracker data directory
 * @param type $file
 * @param type $path
 * @param type $name
 * @param type $new Is it a new file we've uploaded or an existing one we are just moving?
 */
function gt_save_file($file, $path, $name, $new = true)
{
    
    global $CFG;
    
    // Remove any double slashes or backslashes
    $path = preg_replace("/\\\\/", "/", $path);
    $path = preg_replace("/\/{2,}/", "/", $path);
        
    // Split into array and make sure each directory exists, creating it if it doesn't
    $explode = explode("/", $path);
    $cnt = count($explode);
    
    if ($explode)
    {
        for ($i = 0; $i < $cnt; $i++)
        {
            
            $diff = $i - 1;
            $checkPath = "";

            for ($j = 0; $j <= $diff; $j++)
            {
                $checkPath .= $explode[$j] . "/";
            }
            
            $checkPath .= $explode[$i];

            if (!\gt_create_data_directory($checkPath)){
                return false;
            }
                
        }
    }
                
    if ($new){
        return move_uploaded_file($file, \GT\GradeTracker::dataroot() . '/' . $path . '/' . $name);
    } else {
        return rename($file, \GT\GradeTracker::dataroot() . '/' . $path . '/' . $name);
    }
    
}

/**
 * Get the mime type of a file
 * @param type $file
 */
function gt_get_file_mime_type($file)
{
    $fInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($fInfo, $file);
    finfo_close($fInfo);
    return $mime;
}

/**
 * Check if a given mime type is an image
 * @param type $mime
 * @return type
 */
function gt_mime_type_is_image($mime)
{
    return (in_array($mime, array('image/bmp', 'image/gif', 'image/jpeg', 'image/png', 'image/tiff', 'image/pjpeg')));
}

/**
 * Convert to html entities
 * @param type $txt
 * @return type
 */
function gt_html($txt, $nl2br = false)
{
    return ($nl2br) ? nl2br(  htmlspecialchars($txt, ENT_QUOTES) ) : htmlspecialchars($txt, ENT_QUOTES);
}

function gt_html_undo($txt){
    return html_entity_decode($txt, ENT_QUOTES);
}


/**
 * Get the file extension from a file name
 * @param type $filename
 * @return type
 */
function gt_get_file_extension($filename)
{
    $filename = strtolower($filename);
    $exts = explode(".", $filename);
    $n = count($exts) - 1;
    $ext = $exts[$n];
    return $ext;
}


/**
 * Convert a max_filesize value to an int of bytes
 * I'll be honest with you, I can't remember how this works, and looking at it I have no idea... But it doess
 * @param type $val e.g. 128M
 * @return int e.g. ..
 */
function gt_get_bytes_from_upload_max_filesize($val)
{
    
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
    
}

/**
 * Convert a number of bytes into a human readable string
 * @param type $bytes
 * @param type $precision
 * @return type
 */
function gt_convert_bytes_to_hr($bytes, $precision = 2)
{	
	$kilobyte = 1024;
	$megabyte = $kilobyte * 1024;
	$gigabyte = $megabyte * 1024;
	$terabyte = $gigabyte * 1024;
	
	if (($bytes >= 0) && ($bytes < $kilobyte)) {
		return $bytes . ' B';

	} elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
		return round($bytes / $kilobyte, $precision) . ' KB';

	} elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
		return round($bytes / $megabyte, $precision) . ' MB';

	} elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
		return round($bytes / $gigabyte, $precision) . ' GB';

	} elseif ($bytes >= $terabyte) {
		return round($bytes / $terabyte, $precision) . ' TB';
	} else {
		return $bytes . ' B';
	}
}

/**
 * From an array of _FILES submitted, get a specific one
 * @param type $file
 * @param type $num
 * @return type
 */
function gt_get_multidimensional_file($file, $num)
{
    
    $return = array();
    
    foreach($file as $key => $array)
    {
        $return[$key] = $array[$num];
    }
    
    return $return;
        
}

/**
 * Strip anything that isn't alphanumeric, space, hyphen or fullstop
 * @param type $txt
 * @return type
 */
function gt_strip_chars($txt){
    
    $txt = preg_replace("/[^a-z0-9\.\- ]/i", "", $txt);
    return trim($txt);
    
}

/**
 * Strip everything not alphanumeric
 * @param type $txt
 * @return type
 */
function gt_strip_chars_non_alpha($txt){
    $txt = preg_replace("/[^a-z0-9]/i", "", $txt);
    return trim($txt);
}

/**
 * Make a string safe to use in a database query as a field
 * @param type $str
 * @return type
 */
function gt_make_db_field_safe($str, &$usedArray){
    
    if (array_key_exists($str, $usedArray)){
        return $usedArray[$str];
    }
    
    $txt = \gt_strip_chars_non_alpha($str);
    $txt = strtolower($txt);
    
    while (in_array($txt, $usedArray)){
        $txt .= '_';
    }
    
    $usedArray[$str] = $txt;
    return $usedArray[$str];
    
}

/**
 * Check a string is empty or not, taking into account that empty() returns "0" as empty
 * @param type $txt
 * @return type
 */
function gt_is_empty($txt){
    
    $txt = (string)$txt;
    $txt = trim($txt);
    return ($txt == "");
    
}

/**
 * Get a role record by its shortname
 * @global type $DB
 * @param type $shortname
 * @return type
 */
function gt_get_role_by_shortname($shortname){
    
    global $DB;
    return $DB->get_record("role", array("shortname" => $shortname));
    
}

/**
 * Create a string of question mark placeholders for SQL queries from an array of parameters
 * Mostly used for when binding to an IN(?,?,?,?,?,etc...)
 * @param type $params
 * @return type
 */
function gt_create_sql_placeholders($params){
    
    return implode(',', array_fill(0, count($params), '?'));
    
}


/**
 * Convert an array of select, join, where, group, into an sql string
 * @param type $sqlArray
 * @return string
 */
function gt_convert_to_sql($sqlArray){

    // Remove empty elements
    $sqlArray['select'] = array_filter($sqlArray['select']);
    $sqlArray['join'] = array_filter($sqlArray['join']);
    $sqlArray['where'] = array_filter($sqlArray['where']);

    $sql = "";

    // Select
    $sql .= "SELECT " . implode(", ", $sqlArray['select']) . " ";

    // From
    $sql .= "FROM " . $sqlArray['from'] . " ";

    // Joins
    foreach($sqlArray['join'] as $join)
    {
        $sql .= $join . " ";
    }

    // Where
    $sql .= "WHERE " . implode(" AND ", $sqlArray['where']) . " ";

    // Group
    if (isset($sqlArray['group'])){
        $sql .= "GROUP BY " . $sqlArray['group'] . " ";
    }

    // Order
    if (isset($sqlArray['order'])){
        $sql .= "ORDER BY " . $sqlArray['order'] . " ";
    }

    return $sql;

}


/**
 * Get the depth of a multi-domensional array
 * http://stackoverflow.com/questions/262891/is-there-a-way-to-find-out-how-deep-a-php-array-is
 * @param array $array
 * @return type
 */
function gt_get_array_depth(array $array) {
    
    $max_depth = 1;

    foreach ($array as $value) {
        if (is_array($value)) {
            $depth = gt_get_array_depth($value) + 1;

            if ($depth > $max_depth) {
                $max_depth = $depth;
            }
        }
    }

    return $max_depth;
    
}

/**
 * Convert a multi-dimensional array to a single array
 * http://stackoverflow.com/questions/6785355/convert-multidimensional-array-into-single-array
 * @param type $array
 * @return boolean
 */
function gt_flatten_array($array) { 
    
    if (!is_array($array)) return false;
    
    $result = array(); 
    
    foreach ($array as $key => $value) { 
        
        if (is_array($value)) { 
            // I have changed the array_merge because with an empty $result it overrides first key to 0
//            $result = array_merge($result, gt_flatten_array($value)); 
            $result = $result + \gt_flatten_array($value);
        } 
        else { 
            $result[$key] = $value; 
        } 
      
    } 
    
    return $result; 
    
} 

/**
 * Strip slashes from a string or an array of strings
 * http://php.net/manual/en/function.stripslashes.php Example #2
 * @param type $value
 * @return type
 */
function gt_strip_slashes($value)
{
    $value = is_array($value) ?
                array_map('gt_strip_slashes', $value) :
                stripslashes($value);

    return $value;
}

/**
 * Check if a string starts with specific characters
 * @param type $haystack
 * @param type $needle
 * @return type
 */
function gt_str_starts($haystack, $needle)
{
    
    if (is_array($needle))
    {
        $result = false;
        foreach($needle as $str)
        {
            $length = strlen($str);
            $strResult = ( strcasecmp(substr($haystack, 0, $length), $str) == 0 );
            $result = ($result || $strResult);
        }
        return $result;
    }
    else
    {
        $length = strlen($needle);
        return ( strcasecmp(substr($haystack, 0, $length), $needle) == 0 );
    }
    
}

/**
 * Check if a string ends with specific characters
 * @param type $haystack
 * @param type $needle
 * @return type
 */
function gt_str_ends($haystack, $needle)
{
    if (is_array($needle))
    {
        $result = false;
        foreach($needle as $str)
        {
            $strResult = (strcasecmp(substr($haystack, -strlen($str)),$str) == 0);
            $result = ($result || $strResult);
        }
        return $result;
    }
    else
    {
        return (strcasecmp(substr($haystack, -strlen($needle)),$needle) == 0);
    }
}

/**
 * Check if a string contains another string
 * @param type $haystack
 * @param type $needle
 * @return type
 */
function gt_str_contains($haystack, $needle)
{
    if (is_array($needle))
    {
        $result = false;
        foreach($needle as $str)
        {
            $strResult = (stripos($haystack, $str) !== false);
            $result = ($result || $strResult);
        }
        return $result;
    }
    else
    {
        return (stripos($haystack, $needle) !== false);
    }
}


/**
 * Display the debugging section
 * @global type $USER
 */
function gt_display_debug_section(){
    
    global $CFG, $USER;
    
    if (is_siteadmin())
    {
     
        echo "<div id='gt_grid_debug'>";
            echo "<table>";
                echo "<tr><th colspan='4'>".get_string('console', 'block_gradetracker')."</th></tr>";
                echo "<tr>";
                    echo "<td>";
                        echo "<a href='#' onclick='start_script_debugging();return false;' title='".get_string('startdebugging', 'block_gradetracker')."'>";
                            echo "<img class='".((\gt_is_debugging()) ? 'gt_img_disable' : '')." gt_debug_start' src='{$CFG->wwwroot}/blocks/gradetracker/pix/start.png' alt='".get_string('startdebugging', 'block_gradetracker')."' />";
                        echo "</a>";
                    echo "</td>";
                    echo "<td>";
                        echo "<a href='#' onclick='stop_script_debugging();return false;' title='".get_string('stopdebugging', 'block_gradetracker')."'>";
                            echo "<img class='".((!\gt_is_debugging()) ? 'gt_img_disable' : '')." gt_debug_stop' src='{$CFG->wwwroot}/blocks/gradetracker/pix/end.png' alt='".get_string('stopdebugging', 'block_gradetracker')."' />";
                        echo "</td>";
                    echo "</a>";
                    echo "<td>";
                        echo "<a href='{$CFG->wwwroot}/blocks/gradetracker/download.php?f=".\gt_create_data_path_code( \GT\GradeTracker::dataroot() . '/debug/'.$USER->id.'.txt' )."&t=".time()."' onclick='gtRefreshUrlTimeParam(this);' target='_blank' title='".get_string('viewlogs', 'block_gradetracker')."'>";
                            echo "<img src='{$CFG->wwwroot}/blocks/gradetracker/pix/page_white_text.png' alt='".get_string('viewlogs', 'block_gradetracker')."' />";
                        echo "</a>";
                    echo "</td>";
                    echo "<td>";
                        echo "<a href='#' onclick='clear_debugging_logs();return false;'>";
                            echo "<img src='{$CFG->wwwroot}/blocks/gradetracker/pix/page_white_delete.png' alt='".get_string('clearlogs', 'block_gradetracker')."' title='".get_string('clearlogs', 'block_gradetracker')."' />";
                        echo "</a>";
                    echo "</td>";
                echo "</tr>";
            echo "</table>";
        echo "</div>";
        
        if (\gt_is_debugging()){
            echo "<script> isDebugging = true; </script>";
        }
        
    
    }
    
}

/**
 * Get overriden settings from json file
 * @return boolean
 */
function gt_get_overriden_settings(){
    
    $file = \GT\GradeTracker::dataroot() . '/settings.json';
    if (file_exists($file))
    {
        $content = file_get_contents($file);
        return serialize( json_decode($content) );
    }
    
    return false;
    
}

/**
 * Cut a string off at a certain number of characters with some dots at the end
 * @param type $string
 * @param type $length
 * @return type
 */
function gt_cut_string($string, $length = 100){
    
    return (strlen($string) > $length) ? substr($string, 0, ($length - 3) ) . '...' : $string;
       
}

/**
 * Move an array element to the top, based on its key
 * http://stackoverflow.com/questions/5312879/moving-array-element-to-top-in-php
 * @param type $array
 * @param type $key
 */
function gt_array_element_to_top(&$array, $key) {
    $temp = array($key => $array[$key]);
    unset($array[$key]);
    $array = $temp + $array;
}

/**
 * Move an array element to the bottom, based on its key
 * http://stackoverflow.com/questions/5312879/moving-array-element-to-top-in-php
 * @param array $array
 * @param type $key
 */
function gt_array_element_to_bot(&$array, $key) {
    $value = $array[$key];
    unset($array[$key]);
    $array[$key] = $value;
}

/**
 * Convert an image to a data string, encoded in base64
 * @param type $path
 * @return type
 */
function gt_img_to_data($path){
    
    if (file_exists($path) && !is_dir($path)){
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    
    return null;
    
}

/**
 * Get the image extension (e.g. jpg, gif, etc...) from the base64 encoded string of an image
 * @param type $data
 * @return type
 */
function gt_get_image_ext_from_base64($data){

    preg_match("/data:image\/(.*?);/", $data, $match);
    return (isset($match[1])) ? $match[1] : false;

}

/**
 * Create an image file and save it, from the base64 encoded string of an image
 * @param type $data
 * @param type $path
 * @return boolean
 */
function gt_save_base64_image($data, $path){
    
    $pos = strpos($data, ',');
    $start = $pos - strlen($data) + 1;
    $data = substr($data, $start);
    
    $data = base64_decode($data);
    
    $source = imagecreatefromstring($data);
    if ($source){
        
        // Transparency
        imagealphablending($source, false);
        imagesavealpha($source, true);
        
        imagepng($source, $path);
        imagedestroy($source);
        return true;
    } else {
        return false;
    }
    
    
}

// Not used anymore i think, uses the observer method
function gt_auto_enrol($data){
    
    global $DB;
    
    $GT = new \GT\GradeTracker();
    
    if ($GT->getSetting('use_auto_enrol_quals') == 1){
        
        $context = $DB->get_record("context", array("contextlevel" => CONTEXT_COURSE, "instanceid" => $data->courseid));
        if (!$context) return true;
        
        $role = $DB->get_record("role_assignments", array("userid" => $data->userid, "contextid" => $context->id));
        if (!$role) return true;
        
        $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data->courseid));
        if (!$quals) return true;
        
        $GT_User = new \GT\User($data->userid);
        
        foreach ($quals as $qual){
            $user_role = ($role->roleid < 5 ? "STAFF" : "STUDENT");
            $GT_User->addToQual($qual->qualid, $user_role);
        }
        
    }
    
    if ($GT->getSetting('use_auto_enrol_units') == 1){
        
        if (!$context) $context = $DB->get_record("context", array("contextlevel" => CONTEXT_COURSE, "instanceid" => $data->courseid));
        if (!$context) return true;
        
        if (!$role) $role = $DB->get_record("role_assignments", array("userid" => $data->userid, "contextid" => $context->id));
        if (!$role) return true;
        
        if (!$quals) $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data->courseid));;
        if (!$quals) return true;
        
        if (!$GT_User) $GT_User = new \GT\User($data->userid);
        
        foreach ($quals as $qual){
            
            $Qual = new \GT\Qualification($qual->qualid);
            $units = $Qual->getUnits();
            
            $user_role = ($role->roleid < 5 ? "STAFF" : "STUDENT");
            
            foreach ($units as $unit){
                $GT_User->addToQualUnit($qual->qualid, $unit->getID(), $user_role);
            }
            
        }
    }
    return true;
}

// Not used anymore i think, uses the observer method
function gt_auto_unenrol($data){
    
    global $DB;
    
    $GT = new \GT\GradeTracker();
    
    if ($GT->getSetting('use_auto_unenrol_quals') == 1){
        
        $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data->courseid));
        if (!$quals) return true;
        
        $GT_User = new \GT\User($data->userid);
        
        foreach ($quals as $qual){
            $GT_User->removeFromQual($qual->qualid);
        }
    }
    
    if ($GT->getSetting('use_auto_unenrol_units') == 1){
        
        if (!$quals) $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data->courseid));;
        if (!$quals) return true;
        
        if (!$GT_User) $GT_User = new \GT\User($data->userid);
        
        foreach ($quals as $qual){
            
            $Qual = new \GT\Qualification($qual->qualid);
            $units = $Qual->getUnits();
           
            foreach ($units as $unit){
                $GT_User->removeFromQualUnit($qual->qualid, $unit->getID());
            }
            
        }
    }
    
    return true;
}

/**
 * Split a long single dimensional array into a multideimmensional array with $length number of elements in each element
 * E.g. array('one', 'two', 'three', 'four', 'five' ,'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve');
 * Split into array lengths of 5 would result in:
 * 
 * array (size=3)
  0 => 
    array (size=5)
      0 => string 'one' (length=3)
      1 => string 'two' (length=3)
      2 => string 'three' (length=5)
      3 => string 'four' (length=4)
      4 => string 'five' (length=4)
  1 => 
    array (size=5)
      0 => string 'six' (length=3)
      1 => string 'seven' (length=5)
      2 => string 'eight' (length=5)
      3 => string 'nine' (length=4)
      4 => string 'ten' (length=3)
  2 => 
    array (size=2)
      0 => string 'eleven' (length=6)
      1 => string 'twelve' (length=6)
 * 
 * @param type $array
 * @param type $length
 * @param type $result
 * @return type
 */
function gt_split_array($array, $length, &$result = false){
  
  if (!$result){
      $result = array();
  }
  
  $thisArray = array();
  
  for ($i = 0; $i < $length; $i++)
  {
      if ($array){
         $thisArray[] = array_shift($array);
      }
  }
  
  $result[] = $thisArray;
    
  if ($array){
     \gt_split_array($array, $length, $result);
  } 
    
  return $result;
  
}

/**
 * Get the Greatest Common Denominator of 2 numbers
 * @param type $a
 * @param type $b
 * @return type
 */
function gt_gcd($a, $b)
{
 
  return $b ? gt_gcd( $b, ($a % $b) ) : $a;
  
}

/**
 * Get the Lowest Common Multiple of 2 numbers
 * @param type $a
 * @param type $b
 * @return type
 */
function gt_lcm($a, $b)
{
 
  return ($a * $b) / gt_gcd($a, $b);
  
}

/**
 * Decrement an excel column-style letter, e.g. B, AB, ACZ, etc...
 * http://stackoverflow.com/questions/10762579/decrementing-alphabetical-values
 * @param type $char
 * @return type
 */
function gt_decrement_letter_excel($char, $times = 1) {
    
    // If it's not a string just return null
    if (!is_string($char)) return null;
  
    for ($i = 0; $i < $times; $i++)
    {
    
        $len = strlen($char);

        // last character is A or a
        if(ord($char[$len - 1]) === 65 || ord($char[$len - 1]) === 97){ 

              if($len === 1){ // one character left
                      return null;
              } else { // 'ABA'--;  => 'AAZ'; recursive call
                      $char = gt_decrement_letter_excel(substr($char, 0, -1)).'Z';
              }

        } else {
              $char[$len - 1] = chr(ord($char[$len - 1]) - 1);
        }
          
    }
    
    return $char;
     
}

/**
 * Convert the number you get from Excel when you input a date, into a unix timestamp so we can get a date again
 * @param type $excelDate
 * @return type
 */
function gt_convert_excel_date_unix($excelDate){
    return ($excelDate - 25569) * 86400;
}

/**
 * Get just the file and line from a debug backtrace array so we can dump that without all the other crap
 * @param type $backtrace
 * @return string
 */
function gt_debug_file_line($backtrace)
{
    
    $r = array();
    
    foreach($backtrace as $row)
    {
        $r[] = $row['file'] . ':' . $row['line'];
    }
    
    return $r;
    
}

/**
 * Get a user access a particular activity? (Restricted access conditions)
 * @global type $DB
 * @param type $cmID
 * @param type $userID
 * @return type
 */
function gt_can_user_access_activity($cmID, $userID){
    
    global $DB;
    
    // Get the course module record
    $cm = \GT\ModuleLink::getCourseModule($cmID);    
    
    // Get the modinfo for the course this course module is on
    $modinfo = get_fast_modinfo($cm->course);
    
    // Get the course module information, with all the extra modinfo stuff loaded in
    $cm = $modinfo->get_cm($cmID);
    
    // Get the availability information about this course module
    $info = new \core_availability\info_module($cm);
    
    // Get the record for the userID we are looking up
    $user = $DB->get_record("user", array("id" => $userID));
    
    // Only way I can see to do this is to filter an array of users, so send in an array with just the one user
    // and filter that
    $filteredUsers = $info->filter_user_list( array( $user->id => $user ) );
        
    // If the user still exists in the filtered list, they can access it, if not, they can't
    return (array_key_exists($userID, $filteredUsers));  
    
}


function gt_count_elements_and_sub_elements($array, $field){
  
    $cnt = 0;
    $cnt += count($array);

    if ($array){
        foreach($array as $el){
            if ($el[$field]){
                $cnt += count($el[$field]);
            }
        }
    }

    return $cnt;
  
}

/**
 * Check if plugin is installed
 * @global type $DB
 * @param type $plugin
 * @return type
 */
function gt_is_plugin_installed($plugin){
    
   global $DB;
   return $DB->get_record("config_plugins", array("plugin" => $plugin, "name" => "version"));
    
}

/**
 * Find element in multidimensional associative array by value
 * 
 * Will only work with 1 level, e.g.
 * 
 *  Array
    (
        [0] => Array
            (
                [item] => qual
                [oldID] => 1
                [newID] => 100
            )

        [1] => Array
            (
                [item] => qual
                [oldID] => 2
                [newID] => 200
            )

        [3] => Array
            (
                [item] => qual
                [oldID] => 4
                [newID] => 400
            )

    )
 * 
 * @param type $key
 * @param type $val
 * @param type $array
 * @return type
 */

function gt_array_find($array, $key, $val = false){
     
    if (is_null($array)) return false;
        
    $return = array();
      
    if (is_array($key))
    {

        $search = $key; // Rename to make more sense
        $v = reset($search);
        $k = key($search);
        unset($search[$k]);

        $find = \gt_array_find($array, $k, $v);
        
        // If any elements left, do it again
        if ($search){
            return \gt_array_find($find, $search);
        } else {
            if (count($find) == 1){
                return reset($find);
            } else {
                return $find;
            }
        }

    }
    else
    {
        
        foreach($array as $el)
        {

            if ($el[$key] == $val)
            {
                $return[] = $el;
            }

        }
        
        return $return;

    }
    
}

function gt_split_unit_name_number($name){
    
    $name = trim($name);
    preg_match("/^(Unit)?\s?(\d+)\s?:(.+)/", $name, $matches);

    $return = array( "number" => null, "name" => $name );
    if ($matches)
    {
        $return['number'] = (ctype_digit($matches[2])) ? $matches[2] : null;
        $return['name'] = trim($matches[3]);
    }

    return $return;
    
}

function gt_get_course($courseID){
    
    global $DB;
    return $DB->get_record("course", array("id" => $courseID));
    
}

/**
 * Validate an external session
 * @param type $ssn
 * @return boolean
 */
function gt_validate_external_session($ssn, $studentID){
    
    global $CFG;
    
    $split = explode(":", $ssn);
    $from = $split[0];
    $ssn = @$split[1];
            
    switch($from)
    {
        case 'portal':
            
            if (!defined('PARENT_PORTAL')){
                define('PARENT_PORTAL', true);
            }
            
            require_once $CFG->dirroot . '/portal/lib.php';

            $session = ( isset($_SESSION['pp_user']) && isset($_SESSION['pp_ssn']) && $_SESSION['pp_ssn'] == $ssn );
            if ($session)
            {
                return (\PP\Portal::canUserAccessStudent($_SESSION['pp_user']->id, $studentID));
            }
            
        break;
        
        case 'block_elbp':
            return true;
        break;
        
    }
    
    return false;
    
}

function gt_get_external_gt_user_id($ssn){
    
    global $USER;
    
    $split = explode(":", $ssn);
    $from = $split[0];
    $ssn = @$split[1];
    
    switch($from)
    {
        case 'block_elbp':
            return $USER->id;
        break;
        default:
            return false;
        break;
    }
    
    return false;
    
}


function gt_ajax_progress($status, $params = false){
    
    @ob_implicit_flush(true);
    @ob_end_flush();
    
    $arr = array(
        'result' => $status
    );
    
    if ($params){
        $arr = $arr + $params;
    }
    
    echo json_encode($arr);
    
}


function gt_ajax_shutdown(){
    
    $e = error_get_last();   
    if ($e && $e['type'] == E_ERROR){
        
        \gt_ajax_progress(false, array(
            'error' => $e['message'] . '<br>' . $e['file'] . ':' . $e['line']
        ));
        
    }

    return false;

}

function gt_get_field_total_from_objects($array, $field){
    
    $total = 0;
    if ($array){
        foreach($array as $row){
            if (isset($row->$field) && is_numeric($row->$field)){
                $total += $row->$field;
            }
        }
    }
    
    return $total;
    
}

function gt_add_field_to_objects(&$array, $field, $value){
    if ($array){
        foreach($array as $row){
            $row->$field = $value;
        }
    }
}

function gt_implode_objects($delim, $objects, $method){
    
    $array = array();
    if ($objects)
    {
        foreach($objects as $object)
        {
            if (method_exists($object, $method))
            {
                $array[] = $object->$method();
            }
        }
    }
        
    return implode($delim, $array);
    
}

/**
 * Convert a string to utf8
 * @param type $str
 * @return type
 */
function gt_convert_to_utf8($str){
    mb_detect_order('ASCII, UTF-8');
    $enc = mb_detect_encoding($str, mb_detect_order(), true);
    return ($enc != 'UTF-8') ? iconv($enc, 'UTF-8', $str) : $str;
}

/**
 * Get how long ago a timestamp was
 * http://phppot.com/php/php-time-ago-function/
 * @param type $timestamp
 * @return type
 */
function gt_time_ago($timestamp) {
    
   $strTime = array("second", "minute", "hour", "day", "month", "year");
   $length = array("60","60","24","30","12","10");

   $currentTime = time();
   if($currentTime >= $timestamp) {
        $diff = time() - $timestamp;
        for($i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++) {
            $diff = $diff / $length[$i];
        }
        $diff = round($diff);
        return $diff . " " . $strTime[$i] . "(s) ago ";
   }
       
}

/**
 * Decode each value in an array
 * @param type $array
 */
function gt_json_decode_array($array){
    
    foreach($array as &$el){
        $d = json_decode($el);
        $el = (!is_null($d)) ? $d : $el;
    }
    
    return $array;
    
}

/**
 * Find an element in a multidimensional array by one of its keys
 * @param type $array
 * @param type $key
 * @param type $value
 * @return boolean
 */
function gt_find_element_in_array($array, $key, $value){
        
    foreach($array as $el){
        if ($el[$key] == $value){
            return $el;
        }
    }

    return false;
    
}
        


function gt_get_core_jquery(){
    
    global $CFG;    
     
    require $CFG->dirroot . '/lib/jquery/plugins.php';
    $jquery = array(
        'jquery' => (isset($plugins['jquery']['files'])) ? $CFG->wwwroot . '/theme/jquery.php/core/' . reset($plugins['jquery']['files']) : false,
        'ui' => (isset($plugins['ui']['files'])) ? $CFG->wwwroot . '/theme/jquery.php/core/' . reset($plugins['ui']['files']) : false
    );

    return $jquery;
    
}

function gt_get_value_added_class($awardRank, $targetRank){
    
    $diff = $awardRank - $targetRank;
    
    if ($diff >= 3){
        return "gt_value_added_higher";
    } elseif ($diff > 0){
        return "gt_value_added_high";
    } elseif ($diff <= -3){
        return "gt_value_added_lower";
    } elseif ($diff < 0){
        return "gt_value_added_low";
    } else {
        return "gt_value_added_same";
    }
    
}
