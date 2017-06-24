<?php
$tasks = array(
    array(
        'classname' => 'block_gradetracker\task\update_grids_from_activities',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'block_gradetracker\task\refresh_site_registration',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '3',
        'day' => '*',
        'dayofweek' => '*/7',
        'month' => '*'
    ),
    array(
        'classname' => 'block_gradetracker\task\clean_up',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);