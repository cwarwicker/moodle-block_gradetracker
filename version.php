<?php
// Moodle versions - This need to be updated when we want to force it to run the db/update.php script

$plugin->version = 2019032801;  // YYYYMMDDHH (year, month, day, 24-hr time)
$plugin->requires = 2014111006; // Moodle 2.8
$plugin->cron = 3600;
$plugin->component = 'block_gradetracker';
$plugin->dependencies = array(
    'block_bc_dashboard' => 2017110800,
    'local_df_hub' => 2019071900
);