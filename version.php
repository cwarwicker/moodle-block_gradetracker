<?php
// Moodle versions - This need to be updated when we want to force it to run the db/update.php script

$plugin->version = 2020031900;  // YYYYMMDDHH (year, month, day, 24-hr time)
$plugin->requires = 2017111300; // Moodle 3.4
$plugin->cron = 3600;
$plugin->component = 'block_gradetracker';
$plugin->dependencies = array(
    'block_bc_dashboard' => 2020031900,
    'local_df_hub' => 2020031900
);