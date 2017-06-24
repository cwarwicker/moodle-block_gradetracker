<?php
define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once $CFG->libdir.'/clilib.php';      // cli only functions
require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';


function recurse_copy($src, $dst) {
    
    global $CFG;
    
    $dir = opendir($src);
    
    if (!is_dir($dst) && !mkdir($dst, $CFG->directorypermissions)){
        mtrace("Error: Cannot create tmp directory: {$dst}");
        exit;
    }
    
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                if (!copy($src . '/' . $file, $dst . '/' . $file)){
                    mtrace("Error: Failed to copy file: {$src}/{$file}");
                    exit;
                }
                
                mtrace($src . '/' . $file . '........' . $dst . '/' . $file);
                
            }
        }
    }
    
    closedir($dir);
    
}

function recursive_delete($dir){
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }

    rmdir($dir);
    
}


/**
 * Create directories with names defined in array, in the specified directory inside the gradetracker dataroot directory
 * @param string $dir
 * @param array $directories
 */
function create_directories($dir, array $directories){
    
    global $CFG;
    
    foreach($directories as $sub)
    {
        $dir .= '/' . $sub;
        if (!mkdir($dir, $CFG->directorypermissions) && !file_exists($dir)){
            mtrace("Error: Cannot create plugin directory: {$dir}");
            exit;
        }
    }
    
}




// Build the downloadable zip

$GT = new \GT\GradeTracker();
$version = $GT->getPluginVersion();

mtrace('Creating tmp directory...');

// Create a temporary directory in the moodledata to house our files
$tmp = 'build_' . hash('sha256', time() . gethostname());
$dir = \GT\GradeTracker::dataroot() . '/' . $tmp;
if (!mkdir($dir, $CFG->directorypermissions)){
    mtrace("Error: Cannot create tmp directory: {$dir}");
    exit;
}

mtrace("Copying plugins to tmp directory...");



/***** MOD/ASSIGN/FEEDBACK/GRADETRACKER *****/

// create the directories for the assignment feedback plugin
$dir = \GT\GradeTracker::dataroot() . '/' . $tmp;
create_directories($dir, array('mod', 'assign', 'feedback', 'gradetracker'));
recurse_copy($CFG->dirroot . '/mod/assign/feedback/gradetracker', $dir . '/mod/assign/feedback/gradetracker');


/***** BLOCKS/GRADETRACKER *****/

// create the directories for the gradetracker block
$dir = \GT\GradeTracker::dataroot() . '/' . $tmp;
create_directories($dir, array('blocks', 'gradetracker'));
recurse_copy($CFG->dirroot . '/blocks/gradetracker', $dir . '/blocks/gradetracker');


/***** BLOCKS/BC_DASHBOARD *****/

// create the directories for bc_dashboard block
$dir = \GT\GradeTracker::dataroot() . '/' . $tmp;
create_directories($dir, array('blocks', 'bc_dashboard'));
recurse_copy($CFG->dirroot . '/blocks/bc_dashboard', $dir . '/blocks/bc_dashboard');






// Now let's move that tmp folder into a zip archive
mtrace('Creating Zip archive...');


$dir = \GT\GradeTracker::dataroot() . '/' . $tmp;
$zipFile = \GT\GradeTracker::dataroot() . '/Grade_Tracker_v'.str_replace(".", "_", $version).'.zip';

$zip = new ZipArchive;
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    
    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file)
    {
        // Skip directories (they would be added automatically)
        if (!$file->isDir())
        {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($dir) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    $zip->close();
    
} else {
    mtrace('Error: Failed to create zip archive');
    exit;
}



/***** DELETE THE TMP DIRECTORY *****/
mtrace('Deleting tmp directory...');
$dir = \GT\GradeTracker::dataroot() . '/' . $tmp;
recursive_delete($dir);

mtrace("");
mtrace("Build file created: {$zipFile}");

exit;