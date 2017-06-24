<?php
$string['pluginname'] = 'Grade Tracker';
$string['gradetracker'] = 'Grade Tracker';

// Capabilities
$string['gradetracker:addinstance'] = 'Add a new instance of the Grade Tracker block';
$string['gradetracker:add_comments'] = 'Add comments to a grid';
$string['gradetracker:configure'] = 'Access the Configuration pages';
$string['gradetracker:configure_assessments'] = 'Access Assessment Configuration (Add/Edit/Delete/etc...)';
$string['gradetracker:configure_course'] = 'Access Course Configuration (Add Quals/Edit User Quals/Edit User Units/Edit Activity Linking/etc...)';
$string['gradetracker:configure_data'] = 'Access Data Configuration (Export and Import)';
$string['gradetracker:configure_quals'] = 'Access Qualification Configuration (Add/Edit/Delete/etc...)';
$string['gradetracker:configure_reporting'] = 'Access Reporting Configuration (Specific reporting types have additional capabilities)';
$string['gradetracker:configure_settings'] = 'Access Settings Configuration (Change system-wide settings)';
$string['gradetracker:configure_structures'] = 'Access Structures Configuration (Add/Edit/Delete/etc...) - (Qualification Structures/Qualification Builds/Grading Structures/etc...)';
$string['gradetracker:configure_tests'] = 'TODO';
$string['gradetracker:configure_units'] = 'Access Units Configuration (Add/Edit/Delete/etc...)';
$string['gradetracker:configure_users'] = 'TODO';
$string['gradetracker:crud_sql_report'] = 'Create/Update/Delete SQL Reports';
$string['gradetracker:delete_assessments'] = 'Delete assessments';
$string['gradetracker:delete_course_activity_refs'] = 'Delete activity links from a course';
$string['gradetracker:delete_restore_quals'] = 'Restore deleted qualifications';
$string['gradetracker:delete_restore_units'] = 'Restore deleted units';
$string['gradetracker:edit_activity_settings'] = 'Add/Edit/Delete activity modules to allow instances of that activity to be linked to courses';
$string['gradetracker:edit_all_courses'] = 'Permission to edit all courses, even if not enrolled on them';
$string['gradetracker:edit_all_quals'] = 'Permissions to edit all qualifications, even if not attached to them';
$string['gradetracker:edit_aspirational_grades'] = 'Edit student aspirational grades';
$string['gradetracker:edit_assessments'] = 'Edit assessments';
$string['gradetracker:edit_class_grids'] = 'Edit class grids';
$string['gradetracker:edit_course_activity_refs'] = 'Add/Edit activity links on a course';
$string['gradetracker:edit_course_quals'] = 'Edit which qualifications are attached to a course';
$string['gradetracker:edit_grids'] = 'General editing ability for grids (specific grids have their own capabilities)';
$string['gradetracker:edit_student_grids'] = 'Edit student grids';
$string['gradetracker:edit_target_grades'] = 'Edit student target grades';
$string['gradetracker:edit_unit_grids'] = 'Edit unit grids';
$string['gradetracker:export_class_grids'] = 'Export class grids to an excel data sheet';
$string['gradetracker:export_student_grids'] = 'Export student grids to an excel data sheet';
$string['gradetracker:export_unit_grids'] = 'Export unit grids to an excel data sheet';
$string['gradetracker:import_class_grids'] = 'Import class data sheets into the class grid';
$string['gradetracker:import_student_grids'] = 'Import student data sheets into the student grid';
$string['gradetracker:import_unit_grids'] = 'Import unit data sheets into the unit grid';
$string['gradetracker:run_built_report'] = 'Run any of the pre-built reports';
$string['gradetracker:run_sql_report'] = 'Run any of the custom SQL reports';
$string['gradetracker:see_aspirational_grade'] = 'See the aspirational grade on the student grid';
$string['gradetracker:see_assessment_summary_table'] = 'See the assessment summary table on the assessment grid';
$string['gradetracker:see_both_target_weighted_target_grades'] = 'See both the target grade and weighted target grade on the student grid';
$string['gradetracker:see_coefficient_table'] = 'See the coefficient table on the assessment grid';
$string['gradetracker:see_predicted_grades'] = 'See the predicted grades on the student grid';
$string['gradetracker:see_target_grade'] = 'See the target grade on the student grid';
$string['gradetracker:see_value_added'] = 'TODO';
$string['gradetracker:see_weighted_target_grade'] = 'See the weighted target grade on the student grid';
$string['gradetracker:see_weighting_percentiles'] = 'See the weighting percentiles on the grid';
$string['gradetracker:view_all_quals'] = 'Permission to view all qualifications, even if not attached to them';
$string['gradetracker:view_class_grids'] = 'View class grids';
$string['gradetracker:view_hidden_quals'] = 'View deleted qualifications';
$string['gradetracker:view_hidden_units'] = 'View deleted units';
$string['gradetracker:view_my_grids'] = 'View your own tracking grids';
$string['gradetracker:view_student_grids'] = 'View student grids';
$string['gradetracker:view_unit_grids'] = 'View unit grids';





// Errors
$string['filenotfound'] = 'File not found';
$string['invalidaccess'] = 'Invalid Access. You do not have the permissions to access this.';

$string['errors:invalidparams'] = 'Invalid parameters';
$string['errors:form'] = 'Invalid form';
$string['errors:save'] = 'Unknown error saving record';
$string['errors:save:file'] = 'Could not save uploaded file. Either the save location does not exist, or is not writable.';
$string['errors:missingparams'] = 'Missing required parameters';
$string['errors:filloutfields'] = 'Required fields have not been filled in';
$string['errors:filloutfield'] = 'Required field has not been filled in';
$string['errors:configerror'] = 'Configuration Error';
$string['errors:max_input_vars'] = 'Cannot display form as number of inputs exceeds max_input_vars setting in PHP.ini. Please speak to your server administrator to increase this setting.';

$string['errors:calcgrade:noavggcse'] = 'No Avg GCSE';
$string['errors:calcgrade:nograde'] = 'No Award';
$string['errors:calcgrade:notg'] = 'No Target Grade';


$string['errors:qualstructure:name'] = 'No name defined for the Qualification Structure';
$string['errors:qualstructure:name:duplicate'] = 'There is already a Qualification Structure with this name (%s)';
$string['errors:qualstructure:level'] = 'Invalid Level defined';
$string['errors:qualstructure:level:sub'] = 'Max Sub Criteria Levels (%d) outside of supported boundary (%d - %d)';
$string['errors:qualstructure:feature'] = 'Invalid Feature defined';

$string['errors:formelement:name'] = 'No name defined for FormElement';
$string['errors:formelement:type'] = 'Invalid FormElement type (%s)';
$string['errors:formelement:validation'] = 'Invalid FormElement validation (%s)';
$string['errors:formelement:missingoptions'] = 'No options defined for FormElement which is expecting them';
$string['errors:formelement:form'] = 'Invalid Form defined for FormElement (%s)';
$string['errors:formelement:duplicatename'] = 'Duplicate field name (%s) for form (%s)';

$string['errors:ruleset:duplicatename'] = 'Duplicate Rule Set name (%s)';

$string['errors:rule:name'] = 'Rule must have a name';
$string['errors:rule:onevent'] = 'Invalid onEvent (%s) for Rule';
$string['errors:rule:context'] = 'Invalid context (%s) for Rule';
$string['errors:rule:steps'] = 'No Rule Steps defined';
$string['errors:rule:oneventmissing'] = 'Please choose which event this rule should execute on, before adding any steps';

$string['errors:rulestep:conditions'] = 'No Conditions defined for Rule Step';
$string['errors:rulestep:actions'] = 'No Actions defined for Rule Step';

$string['errors:rulestepcondition:cmp'] = 'Invalid Comparison operator (%s) for Rule Step Comparison';
$string['errors:rulestepcondition:value'] = 'Invalid Value (%s) for Rule Step Condition';
$string['errors:rulestepaction:action'] = 'Invalid Action (%s) for Rule Step Action';

$string['errors:qualbuild:type'] = 'Invalid Qualification Structure';
$string['errors:qualbuild:level'] = 'Invalid Qualification Level';
$string['errors:qualbuild:subtype'] = 'Invalid Qualification Sub Type';
$string['errors:qualbuild:duplicate'] = 'There is already a Qualification Build with this combination';

$string['errors:qualaward'] = 'Could not save Qualification Award';
$string['errors:qualawards:buildid'] = 'Invalid Qualification Build';
$string['errors:qualawards:name'] = 'Award must have a name';
$string['errors:qualawards:name:duplicate'] = 'An Award on this Qualification Build already exists with that name (%s)';
$string['errors:qualawards:precision'] = '(%d) is larger than the maximum possible value (%d)';

$string['errors:gradestructure'] = 'Could not save Grading Structure';
$string['errors:gradestructures:name'] = 'Grading Structure must have a name';
$string['errors:gradestructures:name:duplicate'] = 'A Grading Structure already exists with this name';
$string['errors:gradestructures:qualstructure'] = 'Invalid Qualification Structure';
$string['errors:gradestructures:qualbuild'] = 'Invalid Qualification Build';
$string['errors:gradestructures:qualstructureorbuild'] = 'Invalid Qualification Structure or Qualification Build';
$string['errors:gradestructures:awards'] = 'Grading Structure must have at least 1 possible award';
$string['errors:gradestructures:awards:name'] = 'Award must have a name';
$string['errors:gradestructures:awards:shortname'] = 'Award must have a short name';
$string['errors:gradestructures:awards:points'] = 'Award must have a valid points score';
$string['errors:gradestructures:awards:pointslower'] = 'Award must have a valid lower points score';
$string['errors:gradestructures:awards:pointsupper'] = 'Award must have a valid upper points score';
$string['errors:gradestructures:awards:image'] = 'Award must have an image icon';

$string['errors:quallevel'] = 'Could not save Qualification Level';
$string['errors:quallevels:name'] = 'Level must have a name';
$string['errors:quallevels:shortname'] = 'Level must have a short name';
$string['errors:quallevels:order'] = 'Level order must be a valid number';

$string['errors:qualsubtype'] = 'Could not save Qualification SubType';
$string['errors:qualsubtype:name'] = 'Sub Type must have a name';
$string['errors:qualsubtype:shortname'] = 'Sub Type must have a short name';

$string['errors:qual:structure'] = 'Invalid Qualification Structure';
$string['errors:qual:build'] = 'Invalid Qualification Build';
$string['errors:qual:name'] = 'Qualification must have a name';
$string['errors:qual:name:duplicate'] = 'There is already a Qualification with this name and build';
$string['errors:qual:custom'] = '%s must be set';
$string['errors:qual:unit'] = 'Unit %s is not of the same type (%s) as the Qualification (%s)';

$string['errors:unit:build'] = 'Invalid Unit Type & Level combination (no Qualification Build exists for these)';
$string['errors:unit:name'] = 'Unit must have a name';
$string['errors:unit:level'] = 'Unit must have a Level';
$string['errors:unit:name:duplicate'] = 'There is already a Unit with this name, number, code, type and level';
$string['errors:unit:custom'] = '%s must be set';
$string['errors:unit:grading'] = 'Invalid Grading Structure set for Unit';

$string['errors:crit:structure'] = '(%s) Invalid Qualification Structure loaded into Criterion';
$string['errors:crit:name'] = '(%s) Criterion must have a name';
$string['errors:crit:type'] = '(%s) Invalid Criterion type';
$string['errors:crit:type:disabled'] = '(%s) This Qualification Structure does not have %s enabled';
$string['errors:crit:gradingstructure'] = '(%s) Invalid Grading Structure';
$string['errors:crit:gradingtype'] = '(%s) Invalid Grading Type';
$string['errors:crit:parent:self'] = '(%s) Criterion cannot be its own parent';
$string['errors:crit:parent:type'] = '(%s) Sub Criteria must be of the same type as their Parent';
$string['errors:crit:level'] = '(%s) Invalid Criterion Type';
$string['errors:crit:levels:max'] = '(%s) Criterion has too many levels of Sub Criteria defined (%d). Maximum allowed: %d';
$string['errors:crit:levels:min'] = '(%s) Criterion has too few levels of Sub Criteria defined (%d). Minimum allowed: %d';
$string['errors:crit:numeric:parent'] = '(%s) Top-Level Numeric Criteria cannot have Parents';
$string['errors:crit:numeric:sub'] = '(%s) Numeric Criterion must have Sub Criteria';
$string['errors:crit:duplicatenames'] = 'Criteria names must be unique. You have more than 1 criteria called (%s)';

$string['errors:exceptions:invaliduser'] = 'Invalid user has been passed into the %s object';

$string['errors:import:file'] = 'File not set or not uploaded successfully';
$string['errors:import:mimetype'] = 'Invalid file Mime Type. Should be %s - Found: %s';
$string['errors:import:open'] = 'Cannot open file';
$string['errors:import:headers'] = 'File headers do not match. Expected: %s - Found: %s';
$string['errors:import:emptyfield'] = 'Required cell is empty';
$string['errors:import:invaliduser'] = 'No such user found';
$string['errors:import:invalidqual'] = 'No such qualification found';
$string['errors:import:invalidcourse'] = 'No such course found';
$string['errors:import:invaliddata'] = 'Invalid data supplied';
$string['errors:import:studnotonqual'] = 'User %s is not on the qualification %s';
$string['errors:import:createuser'] = 'Could not create user record';
$string['errors:import:datasheettype'] = 'Invalid Datasheet Type (Please try Exporting the Datasheet again)';
$string['errors:import:datasheettypeass'] = 'Invalid Datasheet. Either you are trying to upload a non-Assessment spreadsheet to an Assessment grid, or vice versa';
$string['errors:import:xml:load'] = 'Could not load XML file. Make sure it is in correct XML format';
$string['errors:import:xml:missingnodes'] = 'XML File is missing node';
$string['errors:import:xml:structureexists'] = 'Structure already exists';

$string['errors:import:tg:invalidgrade'] = '%s is not a valid Award of this Qualification Build';
$string['errors:import:tg:update'] = 'Could not update the Target Grade for %s on qualification %s due to an unknown error';
$string['errors:import:tg:avggcseupdate'] = 'Could not update the Average GCSE score for %s due to an unknown error';
$string['errors:import:tg:calctg'] = 'Could not calculate Target Grade for %s on qualification %s, as there is no Avg GCSE score set';
$string['errors:import:tg:calctgnogrades'] = 'Could not calculate Target Grade for %s on qualification %s, as there are no Awards with QOE ranges';
$string['errors:import:tg:calcaspnotg'] = 'Could not calculate Aspirational Grade for %s on qualification %s, as there is no Target Grade set';
$string['errors:import:tg:aspupdate'] = 'Could not update Aspirational Grade for %s on qualification %s, due to an unknown error (most likely the user does not have a target grade)';
$string['errors:import:tg:wtgupdate'] = 'Could not calculate Weighted Target Grade for %s on qualification %s (most likely the user does not have a target grade)';

$string['errors:import:cg:update'] = 'Could not update the CETA Grade for %s on qualification %s due to an unknown error';

$string['errors:import:qoe:invalidsubject'] = '%s is not a valid QoE Subject';
$string['errors:import:qoe:invalidqual'] = '%s Level %s is not a valid QoE type';
$string['errors:import:qoe:invalidgrade'] = '%s is not a valid QoE grade for type %s %s Level %s';

$string['errors:import:ass:id'] = 'Please select a valid Assessment';
$string['errors:import:ass:qualnotonass'] = 'This qualification is not attached to the chosen Assessment';
$string['errors:import:ass:grade'] = 'Invalid Grade for this qualification';
$string['errors:import:ass:ceta'] = 'Invalid CETA for this qualification';
$string['errors:import:ass:update'] = 'Could not update the Assessment Grades for %s on qualification %s due to an unknown error';

$string['errors:import:qualstructure:gradingstructures'] = 'Errors encountered saving Grading Structures';
$string['errors:import:zipfile'] = 'Either could not extract file from Zip archive, or Zip archive was empty';

$string['errors:weightingcoefficient'] = 'Could not save Weighting Coefficient';

$string['errors:output:typenotset'] = 'A valid Output Type has not been set';
$string['errors:output:typenotset:recommended'] = 'PHP Code should be revised to include a valid Output Type.';

$string['errors:sql:disallowed'] = 'SQL query cannot contain any of the following words: (%s)';
$string['errors:sql:query'] = 'Query failed with message: ';

// General A-Z

// A
$string['aupdatedtobbycatd'] = '%s updated to %s ("%s"), by %s, at %s';
$string['action'] = 'Action';
$string['actions'] = 'Actions';
$string['activity'] = 'Activity';
$string['activitygrid'] = 'Activity Grid';
$string['activitylinks'] = 'Activity Links';
$string['activitylinkaftersave'] = 'As this activity module uses "Parts", you will need to save the Activity before you are able to link it to the Gradetracker.';
$string['activities'] = 'Activities';
$string['activities:overview:tick:desc'] = 'Criterion linked to an Activity';
$string['activities:overview:cross:desc'] = 'Criterion not linked to any Activity';
$string['activities:overview:warning:desc'] = 'Criterion linked to more than 1 Activity!';
$string['add'] = 'Add';
$string['addchildcrit'] = 'Add Child Criterion';
$string['addfilter'] = 'Add Filter';
$string['addobject'] = 'Add Object';
$string['addmethod'] = 'Add Method';
$string['addinput'] = 'Input Value';
$string['addnew'] = 'Add New';
$string['addnewassessment'] = 'Add New Assessment';
$string['addnewbuild'] = 'Add New Build';
$string['addneweditquery'] = 'Add New/Edit Query';
$string['addnewlevel'] = 'Add New Level';
$string['addnewquery'] = 'Add New Query';
$string['addnewrule'] = 'Add New Rule';
$string['addnewstructure'] = 'Add New Structure';
$string['addnewsubtype'] = 'Add New Sub Type';
$string['addsublink'] = 'Add Sub Link';
$string['advanced'] = 'Advanced';
$string['advancededit'] = 'Advanced Edit';
$string['advancededitgrid'] = 'Edit Grid (Advanced)';
$string['affecteduser'] = 'Affected user';
$string['after'] = 'After';
$string['all'] = 'All';
$string['allactions'] = 'All Actions';
$string['allass'] = 'All Assessments';
$string['allcourses'] = 'All Courses';
$string['allcrit'] = 'All Criteria';
$string['allquals'] = 'All Quals';
$string['allunits'] = 'All Units';
$string['areyousure'] = 'Are you sure you want to do this?';
$string['aspirationalgrade'] = 'Aspirational Grade';
$string['aspirationalgrades'] = 'Aspirational Grades';
$string['aspirationalgrade:short'] = 'Aspirational';
$string['aspirationalgrades:desc'] = 'Here you can import Aspirational Grade sheets.';
$string['aspirationalgrades:descExport'] = 'Export Aspirational Grade Data to CSV.';
$string['aspirationalgrade:help'] = 'This is the grade your teacher(s) think you should be aspiring toward getting, based on your the work you have done so far';
$string['assessment'] = 'Assessment';
$string['assessments'] = 'Assessments';
$string['assessments:acronym'] = 'FA';
$string['assessmentawards'] = 'Assessment Awards';
$string['assessmentgrades'] = 'Asssesment Grades';
$string['assessmentgradingstructures'] = 'Assessment Grading Structures';
$string['assessmentgrid'] = 'Asssessment Grid';
$string['assessment:deleted'] = 'Assessment Deleted';
$string['assessmentsaved'] = 'Assessment Saved';
$string['assessmentsettings'] = 'Assessment Settings';
$string['assessmentsettings:desc'] = 'Configuration settings for Assessments';
$string['assessmentview'] = 'Assessment View';
$string['attributes'] = 'Attributes';
$string['avggcse:help'] = 'This is an average points score for all the GCSEs you have. It is used to calculate your Target Grade';
$string['avggcsescore'] = 'Avg GCSE Score';
$string['award'] = 'Award';
$string['awards'] = 'Awards';
$string['awarddeleted'] = 'Award Deleted';
$string['awardname'] = 'Award Name';
$string['awardsaved'] = 'Award Saved';

// B
$string['back'] = 'Back';
$string['backtogrid'] = 'Back to Grid';

// Reporting elements
$string['bc_dashboard:avggcse'] = 'Avg GCSE';
$string['bc_dashboard:grade'] = 'Grade';
$string['bc_dashboard:listofquals'] = 'Quals';
$string['bc_dashboard:numberofqoe'] = 'No. QoE';
$string['bc_dashboard:award'] = 'Award';
$string['bc_dashboard:valueadded'] = 'Value Added';


$string['before'] = 'Before';
$string['blockbcgtdata'] = 'Data Transfer';
$string['blockbcgtdata:datamapping'] = 'Data Mapping';
$string['blockbcdbdata:datamapping:saved'] = 'Data mappings saved';
$string['blockbcgtdata:datamapped'] = 'Data Mapped';
$string['blockbcgtdata:datamapped:desc'] = 'Here you can see all the data which has already been mapped by transfering it into the new system (Qualifications, Units, Criteria, etc...)';
$string['blockbcgtdata:desc'] = 'Here you can transfer Qualifications, Units and Criteria from the old Gradetracker system (block_bcgt) to the new Grade Tracker';
$string['blockbcgtdata:transfer'] = 'Transfer';
$string['blockbcgtdata:transfer:desc'] = 'This lets you choose from a list of all the Qualifications in the old system (block_bcgt), and transfer only the ones you want into the new system (block_gradetracker).<br><br>It will attempt to create any Qualification Levels, SubTypes or Builds it cannot find.';
$string['blockbcgtdata:mapping:desc'] = 'Here you will need to map across the different structures from the old system to the new, so that data is inserted into the correct places.';
$string['blockbcgtdata:hidequalsnocourses'] = 'Hide Qualifications not linked to any Courses';
$string['blockbcgtdata:warning'] = 'It is recommended you take a backup of your Moodle database before running this script, as a precautionary measure.';
$string['blockbcgtdata:transferdata:desc'] = 'This lets you choose from a list of the Qualifications and Units you have transferred across from the old system (block_bcgt) into the new system (block_gradetracker) and transfer any student data associated with them into the newly mapped Qualifications/Units<br><br>Data Transfer only supports the transferring of unit/criteria data, not that of assessment data.';
$string['blockbcgtdata:qualfam'] = 'Qualification Family';
$string['blockbcgtdata:transferspecs'] = 'Transfer Specifications';
$string['blockbcgtdata:transferdata'] = 'Transfer User Data';
$string['blockbcgtdata:unitgradetype'] = 'Old Unit Grading Type';
$string['blockbcgtdata:unitgrademap'] = 'Unit Grade Mapping';
$string['blockbcgtdata:critgrademap'] = 'Criteria Grade Mapping';
$string['blockbcgtdata:linktocourses'] = 'Link new Qualification to same Courses as old Qualification';
$string['blockbcgtdata:bespokeimport'] = 'Bespoke Import';
$string['blockbcgtdata:bespokeimport:desc'] = 'Due to the complexities of transferring Bespoke qualifications to the new system, you will need to use "Export Qualification Specification" feature on the old bcgt block to generate an Excel spreadsheet, which we will then import into the new system.<br><br>You will need to go through the spreadsheet and match up the "Unit Grading Structure" and "Criteria Grading Structure" columns to valid Grading Structures in the new system, and the "Level" to match a valid level name.<br><br>You will need to create the Qualification in the new system first, then select which Qualification you want to import the specification into.';
$string['blockbcgtdata:oldid'] = 'Old ID';
$string['blockbcgtdata:newid'] = 'New ID';
$string['blockbcgtdata:transferdata:stage1'] = 'Stage 1 - Choose Qualifications';
$string['blockbcgtdata:transferdata:stage2'] = 'Stage 2 - Choose Students';
$string['blockbcgtdata:transferdata:stage3'] = 'Stage 3 - Choose Units';
$string['blockbcgtdata:transferdata:stage4'] = 'Stage 4 - Confirm Transfer';
$string['blockbcgtdata:mappedagainst'] = 'Mapped Against';


$string['blockbcgtdata:err:qualarray'] = 'No old Qualifications selected';
$string['blockbcgtdata:err:cannotmakefile'] = 'Cannot create file in Moodle Data directory. Please check directory permissions.';
$string['blockbcgtdata:err:invalidmethod'] = 'Invalid Transfer Method';
$string['blockbcgtdata:err:invalidfamilyqualstructure'] = 'Cannot find a Qualification Structure with a name matching this old Qualification Family';
$string['blockbcgtdata:err:invalidlevel'] = 'Invalid Level and attempt to create new one failed';
$string['blockbcgtdata:err:invalidsubtype'] = 'Invalid SubType and attempt to create new one failed';
$string['blockbcgtdata:err:invalidbuild'] = 'Invalid Qualification Build and attempt to create new one failed';
$string['blockbcgtdata:err:invalidqual'] = 'Failed to create new Qualification';
$string['blockbcgtdata:err:loadqual'] = 'Cannot load old Qualification';
$string['blockbcgtdata:err:loadnewqual'] = 'Cannot load new Qualification';
$string['blockbcgtdata:err:loadunit'] = 'Cannot load old Unit';
$string['blockbcgtdata:err:loadnewunit'] = 'Cannot load new Unit';

$string['blockbcgtdata:err:qualtype'] = 'Invalid Qualification Type';
$string['blockbcgtdata:err:coding:mappings'] = 'Coding Error: Required Mappings not defined for this type';
$string['blockbcgtdata:err:missingmapping'] = 'Mapping missing';
$string['blockbcgtdata:err:invalidqualstructure'] = 'Invalid Qualification Structure';
$string['blockbcgtdata:err:invalidunit'] = 'Failed to create new Unit';
$string['blockbcgtdata:err:invalidcrit'] = 'Failed to create new Criterion';
$string['blockbcgtdata:err:invalidgradingstructure'] = 'Invalid Grading Structure';
$string['blockbcgtdata:err:mapping:crit'] = 'Cannot find valid mapping for Criterion';
$string['blockbcgtdata:err:mapping:critparent'] = 'Cannot find valid mapping for Criterion\'s parent';
$string['blockbcgtdata:err:notmapped'] = 'Item is not mapped';
$string['blockbcgtdata:err:mappingerrors'] = 'Mapping Errors, cannot continue';
$string['blockbcgtdata:err:invaliduser'] = 'Invalid User';
$string['blockbcgtdata:err:notonunit'] = 'User is not on this unit';
$string['blockbcgtdata:err:nousercrit'] = 'Could not find a User Criterion record in the old system';
$string['blockbcgtdata:err:invalidoldcritval'] = 'Could not find Criterion Value record in the old system';
$string['blockbcgtdata:err:invalidnewcritval'] = 'Could not find corresponding Criterion Value in the new system';
$string['blockbcgtdata:err:invalidoldunitval'] = 'Could not find Unit Value record in the old system';
$string['blockbcgtdata:err:invalidnewunitval'] = 'Could not find corresponding Unit Value record in the new system';
$string['blockbcgtdata:err:metaward'] = 'Could not find a "MET" award on the Grading Structure, or there was more than one so did not know which one to use';
$string['blockbcgtdata:err:saveusercrit'] = 'Error saving User Criterion';
$string['blockbcgtdata:err:saveuserunit'] = 'Error saving User Unit';



$string['blockbcgtdata:process:warning:oldlevel'] = 'Warning: Could not find old Unit Level, so new Unit will need to be updated with a valid Level';
$string['blockbcgtdata:process:warning:gradingstructure'] = 'Warning: Could not work out which Grading Structure to use, so new Object will need to be updated with a valid Grading Structure';



$string['blockbcgtdata:process:success'] = 'success';
$string['blockbcgtdata:process:loaded'] = 'Loaded mappings file';
$string['blockbcgtdata:process:transferspecs'] = 'Beginning transfer of selected Specifications';
$string['blockbcgtdata:process:transferdata'] = 'Beginning transfer of User Data for selected qualifications';
$string['blockbcgtdata:process:loadedqualscnt'] = 'Loaded (%d) Qualifications';
$string['blockbcgtdata:process:loadqual'] = 'Loaded old Qualification';
$string['blockbcgtdata:process:loadunit'] = 'Loaded old Unit';
$string['blockbcgtdata:process:loadcrit'] = 'Loaded old Criterion';
$string['blockbcgtdata:process:loadnewqual'] = 'Loaded new Qualification';
$string['blockbcgtdata:process:loadnewunit'] = 'Loaded new Unit';
$string['blockbcgtdata:process:loadnewcrit'] = 'Loaded new Criterion';
$string['blockbcgtdata:process:loaduser'] = 'Loaded User';
$string['blockbcgtdata:process:loadusercrit'] = 'Loaded User Criterion record';
$string['blockbcgtdata:process:loaduserunit'] = 'Loaded User Unit record';
$string['blockbcgtdata:process:loadcritval'] = 'Loaded old Criterion value';
$string['blockbcgtdata:process:loadunitval'] = 'Loaded old Unit award';
$string['blockbcgtdata:process:loadrngob'] = 'Loaded old Range Observation';
$string['blockbcgtdata:process:foundnewcritval'] = 'Found corresponding Criterion Value';
$string['blockbcgtdata:process:foundnewunitval'] = 'Found corresponding Unit Value';
$string['blockbcgtdata:process:saveusercrit'] = 'Saved User Criterion';
$string['blockbcgtdata:process:saveuserunit'] = 'Saved User Unit';
$string['blockbcgtdata:process:nousercrit'] = 'Could not find a User Criterion record in the old system';
$string['blockbcgtdata:process:nouserunit'] = 'Could not find a User Unit record in the old system';
$string['blockbcgtdata:process:createlvl'] = 'Attempting to create new Level';
$string['blockbcgtdata:process:createsubtype'] = 'Attempting to create new SubType';
$string['blockbcgtdata:process:findbuild'] = 'Searching for a Qualification Build with Structure (%s), Level (%s) and SubType (%s)';
$string['blockbcgtdata:process:createbuild'] = 'Attempting to create new Qualification Build, with Structure (%s), Level (%s) and SubType (%s)';
$string['blockbcgtdata:process:createqual'] = 'Attempting to create new Qualification, with Build (%s) (%s) (%s) and name (%s)';
$string['blockbcgtdata:process:alreadymapped'] = 'This item is already mapped. ItemType: (%s), oldID: (%d), newID: (%d)';
$string['blockbcgtdata:process:loadqualunits'] = 'Loading Units on Qualification';
$string['blockbcgtdata:process:foundunits'] = 'Found (%d) Units';
$string['blockbcgtdata:process:createunit'] = 'Attempting to create new Unit, with name (%s) and level (%s)';
$string['blockbcgtdata:process:createcrit'] = 'Attempting to create new Criterion, with name (%s)';
$string['blockbcgtdata:process:parentlink'] = 'Linked Criterion (%s) up to its Parent Criterion (%s)';
$string['blockbcgtdata:process:linkedcourse'] = 'Linked Qualification up to Course (%s)';
$string['blockbcgtdata:process:createconvchart'] = 'Attempting to create new Conversion Chart for Range (%s) and old Grade (%s)';
$string['blockbcgtdata:process:transferredsettings'] = 'Transferred Qualification settings';
$string['blockbcgtdata:process:noerrorsplsconfirm'] = 'No errors found in Excel document. Please confirm Import of the following units: ';
$string['blockbcgtdata:process:import:success'] = 'Successfully imported %d unit specifications. <small><a href="#" onclick="$(\'#gt_import_output_cmd\').toggle();return false;">[Show/Hide details]</a></small>';
$string['blockbcgtdata:process:checkingunits'] = 'Checking Units';
$string['blockbcgtdata:process:nounitstoprocess'] = 'No units found, so no data to transfer';
$string['blockbcgtdata:process:allunitsok'] = 'All units mapped to new system, proceeding to transfer data';
$string['blockbcgtdata:process:OK'] = 'OK';
$string['blockbcgtdata:process:transferspecs:success'] = 'Specification transfer complete. <small><a href="#" onclick="$(\'#gt_transspec_cmd\').toggle();return false;">[Show/Hide details]</a></small>';
$string['blockbcgtdata:process:transferdata:success'] = 'User Data transfer complete. <small><a href="#" onclick="$(\'#gt_transdata_cmd\').toggle();return false;">[Show/Hide details]</a></small>';
$string['blockbcgtdata:process:HR'] = '<hr>';



$string['breadcrumbs:config:settings'] = 'Plugin Settings';
$string['breadcrumbs:config:settings:overview'] = 'System Overview';
$string['breadcrumbs:config:settings:general'] = 'General Settings';
$string['breadcrumbs:config:settings:grid'] = 'Grid Settings';
$string['breadcrumbs:config:settings:qual'] = 'Qualification Settings';
$string['breadcrumbs:config:settings:qual:coefficients'] = 'Qualification Weightings Coefficient';
$string['breadcrumbs:config:settings:qual:constants'] = 'Qualification Weighting Constants';
$string['breadcrumbs:config:settings:unit'] = 'Unit Settings';
$string['breadcrumbs:config:settings:criteria'] = 'Criteria Settings';
$string['breadcrumbs:config:settings:user'] = 'User Settings';
$string['breadcrumbs:config:settings:grade'] = 'Grade Settings';
$string['breadcrumbs:config:settings:reporting'] = 'Report Settings';
$string['breadcrumbs:config:search'] = 'Search';
$string['breadcrumbs:config:settings:assessments'] = 'Assessment Settings';
$string['breadcrumbs:config:structures'] = 'Structures';
$string['breadcrumbs:config:structures:qual'] = 'Qualification Structures';
$string['breadcrumbs:config:structures:qual:edit'] = 'Edit Structure';
$string['breadcrumbs:config:structures:qual:new'] = 'Create New Structure';
$string['breadcrumbs:config:structures:qual:delete'] = 'Delete Structure';
$string['breadcrumbs:config:structures:builds'] = 'Qualification Builds';
$string['breadcrumbs:config:structures:builds:new'] = 'Create New Build';
$string['breadcrumbs:config:structures:builds:edit'] = 'Edit Build';
$string['breadcrumbs:config:structures:builds:delete'] = 'Delete Build';
$string['breadcrumbs:config:structures:builds:awards'] = 'Edit Build Awards';
$string['breadcrumbs:config:structures:builds:defaults'] = 'Edit Build Defaults';
$string['breadcrumbs:config:structures:grade'] = 'Grading Structures';
$string['breadcrumbs:config:structures:grade:edit'] = 'Edit Grading Structures';
$string['breadcrumbs:config:structures:grade:edit_unit'] = 'Edit Unit Grading Structure';
$string['breadcrumbs:config:structures:grade:new_unit'] = 'Create New Unit Grading Structure';
$string['breadcrumbs:config:structures:grade:delete_unit'] = 'Delete a Unit Grading Structure';
$string['breadcrumbs:config:structures:grade:new_criteria'] = 'Create New Criteria Grading Structure';
$string['breadcrumbs:config:structures:grade:edit_criteria'] = 'Edit Criteria Grading Structure';
$string['breadcrumbs:config:structures:grade:delete_criteria'] = 'Delete a Criteria Grading Structure';
$string['breadcrumbs:config:structures:levels'] = 'Qualification Levels';
$string['breadcrumbs:config:structures:levels:new'] = 'Create New Level';
$string['breadcrumbs:config:structures:levels:edit'] = 'Edit Level';
$string['breadcrumbs:config:structures:levels:delete'] = 'Delete Level';
$string['breadcrumbs:config:structures:subtypes'] = 'Qualification Sub Types';
$string['breadcrumbs:config:structures:subtypes:new'] = 'Create New Sub Type';
$string['breadcrumbs:config:structures:subtypes:edit'] = 'Edit Sub Type';
$string['breadcrumbs:config:structures:subtypes:delete'] = 'Delete Sub Type';
$string['breadcrumbs:config:structures:qoe'] = 'Quals on Entry';
$string['breadcrumbs:config:quals'] = 'Qualifications';
$string['breadcrumbs:config:quals:overview'] = 'Qualifications Overview';
$string['breadcrumbs:config:quals:new'] = 'Add a New Qualification';
$string['breadcrumbs:config:quals:search'] = 'Search for a Qualification';
$string['breadcrumbs:config:quals:edit'] = 'Edit a Qualification';
$string['breadcrumbs:config:quals:copy'] = 'Create a Copy of a Qualification';
$string['breadcrumbs:config:units'] = 'Units';
$string['breadcrumbs:config:units:copy'] = 'Create a Copy of a Unit';
$string['breadcrumbs:config:units:overview'] = 'Units Overview';
$string['breadcrumbs:config:units:new'] = 'Add a New Unit';
$string['breadcrumbs:config:units:search'] = 'Search for a Unit';
$string['breadcrumbs:config:units:edit'] = 'Edit a Unit';
$string['breadcrumbs:config:units:delete'] = 'Delete a Unit';
$string['breadcrumbs:config:course'] = 'Course';
$string['breadcrumbs:config:course:overview'] = 'Courses';
$string['breadcrumbs:config:course:quals'] = 'Qualifications on Course';
$string['breadcrumbs:config:course:userquals'] = 'User\'s Qualifications';
$string['breadcrumbs:config:course:userunits'] = 'User\'s Units';
$string['breadcrumbs:config:course:activities'] = 'Activity Links';
$string['breadcrumbs:config:course:activities:add'] = 'Add an Activity Link';
$string['breadcrumbs:config:course:activities:delete'] = 'Delete an Activity Link';
$string['breadcrumbs:config:course:search'] = 'Search';
$string['breadcrumbs:config:course:my'] = 'My Courses';
$string['breadcrumbs:config:settings:grarude'] = 'Grade Settings';
$string['breadcrumbs:config:data'] = 'Data Settings';
$string['breadcrumbs:config:data:aspg'] = 'Import Aspirational Grades';
$string['breadcrumbs:config:data:ceta'] = 'Import CETA Grades';
$string['breadcrumbs:config:data:tg'] = 'Import Target Grades';
$string['breadcrumbs:config:data:qoe'] = 'Import Quals on Entry';
$string['breadcrumbs:config:data:overview'] = 'Overview';
$string['breadcrumbs:config:data:wcoe'] = 'Import Weighting Coefficients';
$string['breadcrumbs:config:data:block_bcgt'] = 'Old Data Extraction';
$string['breadcrumbs:config:data:avggcse'] = 'Import Avg GCSE Score';
$string['breadcrumbs:config:data:ass'] = 'Import Assessment Grades';
$string['breadcrumbs:config:data:block_bcgt:mappings'] = 'Data Mappings';
$string['breadcrumbs:config:data:block_bcgt:specs'] = 'Transfer Specifications';
$string['breadcrumbs:config:data:block_bcgt:data'] = 'Transfer User Data';
$string['breadcrumbs:config:assessments'] = 'Assessments';
$string['breadcrumbs:config:assessments:overview'] = 'Overview';
$string['breadcrumbs:config:assessments:modules'] = 'Manage Modules';
$string['breadcrumbs:config:assessments:modules:edit'] = 'Add/Edit Module Link';
$string['breadcrumbs:config:assessments:modules:delete'] = 'Delete Module Link';
$string['breadcrumbs:config:assessments:manage'] = 'Manage Assessments';
$string['breadcrumbs:config:assessments:manage:edit'] = 'Add/Edit Assessment';
$string['breadcrumbs:config:assessments:manage:delete'] = 'Delete Assessment';
$string['breadcrumbs:config:quals:delete'] = 'Delete Qualification';
$string['breadcrumbs:config:reporting'] = 'Reporting';
$string['breadcrumbs:config:reporting:overview'] = 'Overview';
$string['breadcrumbs:config:reporting:query'] = 'Queries';
$string['breadcrumbs:config:reporting:query:delete'] = 'Delete Query';
$string['breadcrumbs:config:reporting:query:edit'] = 'Edit Query';
$string['breadcrumbs:config:reporting:query:new'] = 'New Query';
$string['breadcrumbs:config:reporting:query:run'] = 'Run Query';
$string['breadcrumbs:config:reporting:reports'] = 'Pre-Built Reports';
$string['breadcrumbs:config:reporting:reports:critprog'] = 'Criteria Progress Report';
$string['breadcrumbs:config:reporting:reports:passprog'] = 'Pass Criteria Progress Report';
$string['breadcrumbs:config:reporting:reports:passsummary'] = 'Pass Criteria Summary Report';
$string['breadcrumbs:config:reporting:logs'] = 'Logs Report';
$string['breadcrumbs:config:tests'] = 'System Tests';
$string['breadcrumbs:config:tests:tg'] = 'Target Grade Tests';


$string['build'] = 'Build';
$string['builddeleted'] = 'Qualification Build Deleted';
$string['buildimported'] = 'Qualification Build Imported';
$string['buildsaved'] = 'Qualification Build Saved';
$string['byactivity'] = 'By Activity';
$string['byassignment'] = 'By Assignment';
$string['byunitscriteria'] = 'By Units/Criteria';


// C
$string['cachedef_settings'] = 'Overriden settings cache';
$string['calculate'] = 'Calculate';
$string['calculateaspirationalgrades'] = 'Calculate Aspirational Grades';
$string['calculateaveragegcsegrades'] = 'Calculate Average GCSE Score';
$string['calculatepredictedgrades'] = 'Calculate Predicted Grades';
$string['calculatetargetgrades'] = 'Calculate Target Grades';
$string['calculateweightedtargetgrades'] = 'Calculate Weighted Target Grades';
$string['calculatedtargetgrade'] = 'Calculated Target Grade to';
$string['calculatedaspgrade'] = 'Calculated Aspirational Grade to';
$string['calculatedavggcse'] = 'Calculated Avg GCSE Score to';
$string['calculatedpredictedgrade'] = 'Calculated Predicted Grade to';
$string['calculatedweightedtargetgrade'] = 'Calculated Weighted Target grade to';
$string['cancel'] = 'Cancel';
$string['cannotdisablegradingstructureassessments'] = 'You cannot disable this Grading Structure, as it is currently set to be the Grading Structure to be used by Assessments';
$string['category'] = 'Category';
$string['ceta'] = 'CETA';
$string['ceta:desc'] = 'Currently Expected To Achieve';
$string['cetagrades'] = 'CETA Grades';
$string['cetagrade'] = 'CETA Grade';
$string['cetagrade:short'] = 'CETA';
$string['cetagrades:desc'] = 'Here you can import CETA Grade sheets.';
$string['cetagrades:descExport'] = 'Export CETA Grade Data to CSV.';
$string['ceta:importbyqual'] = 'Import by Qualifiction Information';
$string['ceta:importbycourseid'] = 'Import by Course ID';
$string['ceta:importbycourseshortname'] = 'Import by Course Shortname';
$string['ceta:importbyqual:desc'] = 'Import via Qualifiction Info Provided (Default).';
$string['ceta:importbycourseid:desc'] = 'If no Qualifiction info is provided, find Course information based on Course ID.';
$string['ceta:importbycourseshortname:desc'] = 'If no Qualifiction info or Course ID is provided, find Course information based on the Shortname.';
$string['ceta:exportbycourseshortname'] = 'Export via Course Shortname';
$string['ceta:exportbycourseid'] = 'Export via Course ID';
$string['ceta:exportbycourseshortname:desc'] = 'Course info Exported will display Course Shortname (Default).';
$string['ceta:exportbycourseid:desc'] = 'Course info Exported will display Course ID.';
$string['aspirationalgrades:descExport:desc'] = 'CETA Grades';
$string['changeicon'] = 'Change Icon';
$string['chooseaunit'] = 'Choose a unit';
$string['choosefile'] = 'Choose File';
$string['choosequal'] = 'Choose a Qualification';
$string['choosespecification'] = 'Choose a Specification File';
$string['classgrid'] = 'Class Grid';
$string['classgrids'] = 'Class Grids';
$string['classgridsettings'] = 'Class Grid Settings';
$string['classgridsettings:desc'] = 'Settings relating only to the Class Grids';
$string['cleanup'] = 'Clean Up';
$string['clear'] = 'Clear';
$string['clearlogs'] = 'Clear Your Logs';
$string['comment'] = 'Comment';
$string['comments'] = 'Comments';
$string['code'] = 'Code';
$string['coding'] = 'Coding';
$string['copy'] = 'Copy';
$string['comparison'] = 'Comparison';
$string['conditions'] = 'Condition(s)';
$string['condition:input:help'] = 'If you just wish to enter some text instead of selecting a Property of a rule Object, make sure to wrap it in quotes. E.g. "Merit".';
$string['config:gridlogs'] = 'Grid Logs';
$string['config:gridlogs:desc'] = 'Enable/Disable logs on the grids to show recent Grade Tracker activity for that student/unit/etc...';
$string['config:fixedlinks'] = 'Fixed Quick Links';
$string['config:fixedlinks:desc'] = 'Enable/Disable quick links to be fixed to the top left of the grid screens';
$string['config:jquery'] = 'Include jQuery UI';
$string['config:jquery:desc'] = 'If your Moodle theme already includes jQuery UI you can disable it from being included with the Grade Tracker, otherwise the version shipped with the Grade Tracker will be loaded into the pages.';
$string['config:layout'] = 'Page Layout';
$string['config:layout:desc'] = 'The Grade Tracker looks and works best in a theme layout which allows it to use the full width of the page, with no blocks down either side, such as "login" or "base".';
$string['config:printlogo'] = 'Institution Logo';
$string['config:printlogo:desc'] = 'Some grids/reports can be printed. This logo for your institution will appear at the top of each printable page.';
$string['config:templating'] = 'Custom Templating';
$string['config:templating:desc'] = 'Ability to override the page templates used in the Grade Tracker and re-design the forms/pages how you would like them to look.';
$string['config:title'] = 'Plugin Title';
$string['config:title:desc'] = 'If you would rather the Grade Tracker have a different name, you can change that here.';
$string['config:unitgridpaging'] = 'Unit Grid Paging';
$string['config:unitgridpaging:desc'] = 'How many records to show per page on the Unit Grids. (Use 0 to disable paging).';
$string['config:criteria:numeric:maxpoints'] = 'Maximum Points';
$string['config:criteria:numeric:maxpoints:desc'] = 'What is the maximum points score that can be set for a Numeric criteria';
$string['config:coursenameformat'] = 'Course Name Format';
$string['config:coursename'] = 'Course Name';
$string['config:coursesnamesearch'] = 'Search for and Load Courses into the Gradetracker';
$string['config:coursesearch'] = 'Course Search';
$string['config:coursenameformat:desc'] = 'In places where a course name is printed, what format do you want it to be in?<br><u>Variables:</u> %id% - ID, %fn% - FullName, %sn% - ShortName, %idnum% - ID Number';
$string['config:studentroles'] = 'Student Roles';
$string['config:studentroles:desc'] = 'These are the shortnames of any roles you want to use to link Students to the Gradetracker';
$string['config:staffroles'] = 'Staff Roles';
$string['config:staffroles:desc'] = 'These are the shortnames of any roles you want to use to link Staff members to the Gradetracker';
$string['config:grid:navlinks'] = 'Navigation Links';
$string['config:grid:navlinks:desc'] = 'These are the links which appear at the top of the Grid';
$string['config:studcols'] = 'Student Columns';
$string['config:studcols:desc'] = 'The student fields to show when showing rows of students (e.g. unit grids, reports, etc...)';
$string['config:grade:avggrademinunits'] = 'Predicted Award - Minimum no. unit awards';
$string['config:grade:avggrademinunits:desc'] = 'The minimum number of unit awards a student must have before a Predicted Avg Grade is calculated';
$string['config:grade:aspdiff'] = 'Target / Aspirational Grade Difference';
$string['config:grade:aspdiff:desc'] = 'If you use automated Aspirational Grade calculating, you can use this setting to define the difference between the Target and the Aspirational grade. Values can be positive or negative, and whole numbers or fractions. For example (A Level): B -> A is 1 grade difference. C -> D/C is -0.6 grade difference and A -> C/D is -2.3 grade difference. These values are the "rank" of the Qual Build Awards';
$string['config:grade:weightedmethod'] = 'Weighted Target Grade Calculation Method';
$string['config:grade:weightedmethod:desc'] = 'The method of calculation to use when calcuating a Weighted Target Grade. <b>Method 1</b> - Multiplies the Avg GCSE Score by the Qualification Coefficient. <b>Method 2</b> - Multiplies the Target Grade UCAS points by the Qualification Coefficient';
$string['config:grade:weighteddirection'] = 'Weighting Direction';
$string['config:grade:weighteddirection:desc'] = 'If the new UCAS points from the weighted calculation does not find an exact match, do you want to find the nearest grade above it (UP) or below it (DOWN)?';
$string['config:classgridpaging'] = 'Class Grid Paging';
$string['config:classgridpaging:desc'] = 'How many records to show per page on the Class Grids. (Use 0 to disable paging).';
$string['config:assessment:showqualsonepage'] = 'Assessment Grid One Page';
$string['config:assessment:showqualsonepage:desc'] = 'On the assessment student grid, for qualifications with assessments attached, show all their qualifications on the page, instead of just the one selected';
$string['config:assessments:comments'] = 'Enable/Disable Comments';
$string['config:assessments:comments:desc'] = 'Enable or Disable the ability to add comments to a student\'s Assessment';
$string['config:assessments:fields'] = 'Assessment Columns';
$string['config:assessments:fields:desc'] = 'If there are any other form fields you would like to appear to be filled out on the Assessment Grid, to go with the Grade/CETA/Comments columns, you can create them here';
$string['config:data:studentsadded'] = 'Calculated grades for %s Students, on %s Qualifications  <a href="#" onclick="$(\'#gt_calc_output\').toggle();return false;"><small>[View Output]</small></a>';
$string['config:qual:coefficient:percentiles'] = 'Weighting Percentiles';
$string['config:qual:coefficient:percentiles:desc'] = 'The number of percentile columns to use';
$string['config:qual:coefficient:percentilecolours'] = 'Percentile Colours';
$string['config:qual:coefficient:percentilecolours:desc'] = 'What colours to use for each percentile column';
$string['config:qual:coefficient:percentilepercents'] = 'Percentile Percentages';
$string['config:qual:coefficient:percentilepercents:desc'] = 'What % does each column represent? And which should be the percentile we want to use in the weighted target grades calculation?';
$string['config:qual:constant:enabled:desc'] = 'Are Weighting Constants enabled or disabled?';
$string['config:autoenrol'] = 'Automatic Enrolment';
$string['config:autoenrol:desc'] = 'Students and Staff are automatically enrolled to:';
$string['config:autounenrol'] = 'Automatic Unenrolment';
$string['config:autounenrol:desc'] = 'Students and Staff are automatically unenrolled from:';
$string['config:customcss'] = 'Custom CSS';
$string['config:customcss:desc'] = 'If you want to apply any custom CSS to override our CSS, you can do so here';
$string['config:reporting:categories'] = 'Reporting Course Categories';
$string['config:reporting:categories:desc'] = 'Choose which Course Categories you want to be able to run reports against';
$string['config:reporting:critscores'] = 'Short Criteria Weighted Scores';
$string['config:reporting:critscores:desc'] = 'The Criteria Progress report totals up all the criteria based on the first letter of its name, for example in BTEC qualifications this will mean separate columns for "P", "M" and "D". The report can also apply a weighted score to these criteria columns to calculate the weighted percentage of how much the student has achieved so far. For example in BTEC these could be: P - 1, M - 2, D - 3.';
$string['config:reporting:passprog:desc'] = 'The Pass Progress report totals up all the "Pass" criteria based on how you have defined what a "Pass" criteria is for each qualification structure (e.g. by first letter (e.g. "P"), by exact name or by grading structure). The report can also bring through the overall weighted percentage score from the Criteria Progress report, if you have set up the weightings for that report.<br><br>Using the forms below, please define what you want to be considered a "Pass" criteria for each structure that you wish to run the report for.';
$string['config:keeplogsfor'] = 'Keep Logs for';
$string['config:keeplogsfor:desc'] = 'How many days should you keep logs in the database for. The log table can get very large, so if you are experiencing issues, you may want to set this to a shorter value. (Set as "0" to never delete logs)';


$string['config'] = 'Configuration';
$string['config:short'] = 'Config';
$string['confirm'] = 'Confirm';
$string['console'] = 'Debugging Console';
$string['constant'] = 'Constant';
$string['context'] = 'Context';
$string['conversionchart'] = 'Conversion Chart';
$string['core'] = 'Core';
$string['course'] = 'Course';
$string['courses'] = 'Courses';
$string['coursecats'] = 'Course Categories';
$string['coursenoquals'] = 'This Course has no Qualifications';
$string['courseschoose'] = 'Courses to Choose From';
$string['courseshortname'] = 'Course Short Name';
$string['coursesonqual'] = 'Courses on Qualification';
$string['coursequalssaved'] = 'Course Qualifications Saved';
$string['createfileorchangepath'] = 'Moodle Developer should either create that file, or alter the path to point to the correct file.';
$string['createddate'] = 'Created Date';
$string['createnew'] = 'Create New';
$string['createlevelsubtype'] = 'Create Level/SubType if they don\'t exist';
$string['createrule'] = 'Create Rule';
$string['createrulestep'] = 'Create Rule Step';
$string['credits'] = 'Credits';
$string['criteria'] = 'Criteria';
$string['criteriaawards'] = 'Criteria Awards';
$string['critgradingstructuresaved'] = 'Criteria Grading Structure Saved';
$string['criteriasettings'] = 'Criteria Settings';
$string['criteriasettings:desc'] = 'Configuration settings for Criteria';
$string['criterion'] = 'Criterion';
$string['criteriagrading'] = 'Criteria Grading';
$string['criteriagradingstructures'] = 'Criteria Grading Structures';
$string['csvexample'] = 'CSV Example';
$string['csvtemplate'] = 'CSV Template';
$string['current'] = 'Current';
$string['customformfields'] = 'Custom Form Fields';
$string['customvalue'] = 'Custom Value';

// D
$string['dashboard'] = 'Dashboard';
$string['dashboard:info'] = 'Click on `Reporting` to access simple overview reports on the Qualifications, Students and Units.';
$string['dashboard:trackingsheets'] = 'Admin/Staff Tracking Sheets';
$string['dashboard:studentgrids'] = 'Student Grids';
$string['dashboard:studentgrids:info'] = 'Tracking for each of your Student enrolled Qualifications';
$string['dashboard:griddisplay'] = 'Dashboard Grid Display';
$string['dashboard:display:simple'] = 'Simple Display (No Criteria)';
$string['dashboard:display:short'] = 'Short Criteria Display';
$string['dashboard:display:full'] = 'Full Criteria Display';
$string['data'] = 'Data';
$string['dataexport'] = 'Data Export';
$string['dataimport'] = 'Data Import';
$string['datamapping'] = 'Data Mapping';
$string['date'] = 'Date';
$string['dateachieved'] = 'Date Achieved';
$string['debug'] = 'Debug';
$string['debuggingrunning'] = 'Grade Tracker Debugging is currently running. Remember to turn it off when you have finished using it, or the log files can become large quite quickly.';
$string['debuginfo'] = 'Debug Info';
$string['default'] = 'Default';
$string['defaultcredits'] = 'Default Credits';
$string['defaultcredits:desc'] = 'This is the number of credits expected on Qualifications of this Build. If it does not use credits you can leave this blank.';
$string['defaultrules:desc'] = 'This is the set of Rules which will be used by Qualifications of this Build.';
$string['defaults'] = 'Defaults';
$string['defaultssaved'] = 'Default Values Saved';

$string['default:displayname'] = '%sdn% %ln% %sbn% - %n%';

$string['delete'] = 'Delete';
$string['deleted'] = 'Deleted';
$string['deletelinks'] = 'Delete Links';

$string['delete:qualbuild:sure'] = 'Are you sure you want to delete this Qualification Build?<br><br>Deleting this Build will also delete:<br><br><b>%d</b> Qualifications';
$string['delete:qualstructure:sure'] = 'Are you sure you want to delete this Qualification Structure?<br><br>Deleting this Structure will also delete:<br><br><b>%d</b> Qualification Builds<br><b>%d</b> Qualifications<br><b>%d</b> Units';
$string['delete:level:sure'] = 'Are you sure you want to delete this Qualification Level?<br><br>Deleting this Level will also delete:<br><br><b>%d</b> Qualifications<br><b>%d</b> Qualification Builds';
$string['delete:subtype:sure'] = 'Are you sure you want to delete this Qualification Sub Type?<br><br>Deleting this Sub Type will also delete:<br><br><b>%d</b> Qualifications<br><b>%d</b> Qualification Builds';
$string['delete:unitgradingstructure:sure'] = 'Are you sure you want to delete this Unit Grading Structure?<br><br>Deleting this structure will reset the unit awards of <b>%d</b> Units';
$string['delete:critgradingstructure:sure'] = 'Are you sure you want to delete this Criteria Grading Structure?<br><br>Deleting this structure will reset the criteria awards of <b>%d</b> Criteria';
$string['delete:modlink:sure'] = 'Are you sure you want to delete this Mod Link?<br><br>Deleting this link will permanently delete <b>%d</b> GradeTracker activity links';
$string['delete:assessment:sure'] = 'Are you sure you want to delete this Assessment?<br><br>This assessment is linked to <b>%d</b> Qualifications';
$string['delete:query:sure'] = 'Are you sure you want to delete this Query?';

$string['description'] = 'Description';
$string['detail'] = 'Detail';
$string['detailcriteria'] = 'Detail Criteria';
$string['details'] = 'Details';
$string['direct'] = 'Direct';
$string['disable'] = 'Disable';
$string['disabled'] = 'Disabled';
$string['display'] = 'Display';
$string['displayname'] = 'Display Name';
$string['down'] = 'Down';
$string['duedate'] = 'Due Date';
$string['duplicate'] = 'Duplicate';

// E
$string['edit'] = 'Edit';
$string['editaction'] = 'Edit Action';
$string['editcetagrades'] = 'Edit CETA Grades';
$string['editcondition'] = 'Edit Condition';
$string['editgrid'] = 'Edit Grid';
$string['editqualification'] = 'Edit Qualification';
$string['editaspgrades'] = 'Edit Aspirational Grades';
$string['edittargetgrades'] = 'Edit Target Grades';
$string['editunit'] = 'Edit Unit';
$string['effect:flashinglights'] = 'This effect has a small amount of flashing lights. Would you like to continue?';
$string['element:text'] = 'Text';
$string['element:textbox'] = 'Text Box';
$string['element:number'] = 'Number';
$string['element:select'] = 'Select Menu';
$string['element:checkbox'] = 'Checkbox';
$string['enggcse'] = 'English GCSE';
$string['enable'] = 'Enable';
$string['enabled'] = 'Enabled';
$string['enabledisable'] = 'Enable/Disable';
$string['enrolment'] = 'Enrolment';
$string['error'] = 'Error';
$string['errors'] = 'Errors';
$string['errorsfound'] = 'Errors found';
$string['exact'] = 'Exact';
$string['exception'] = 'Exception';
$string['expected'] = 'Expected';
$string['export'] = 'Export';
$string['exportdatasheet'] = 'Export Data Sheet';

$string['eventtest'] = 'test event';

// F
$string['features'] = 'Features';

$string['features:targetgrades'] = 'Target Grades';
$string['features:targetgrades:help'] = 'A grade that the student should be aiming toward.';
$string['features:predictedminmaxgrades'] = 'Min/Max Predicted Grades';
$string['features:predictedminmaxgrades:help'] = 'The minimum and maximum grade the student can get, based on current unit awards';
$string['features:targetgradesauto'] = 'Auto-Calculated Target Grades';
$string['features:targetgradesauto:help'] = 'Target Grades calculated from the students average GCSE score';
$string['features:aspirationalgrades'] = 'Aspirational Grades';
$string['features:aspirationalgrades:help'] = 'A manually set grade that the student should be aspiring toward.';
$string['features:predictedgrades'] = 'Predicted Grades';
$string['features:predictedgrades:help'] = 'An estimated grade based on current unit awards';
$string['features:datasheets'] = 'Data Sheets';
$string['features:datasheets:help'] = 'Export & Import data sheets for offline marking';
$string['features:percentagecomp'] = 'Percentage Completion';
$string['features:percentagecomp:help'] = 'A percentage bar per unit, showing how much has been completed';
$string['features:weightedtargetgrades'] = 'Weighted Target Grades';
$string['features:weightedtargetgrades:help'] = 'A Target Grade adjusted based on the weighting of the Qualification';
$string['features:cetagrades'] = 'CETA Grades';
$string['features:cetagrades:help'] = 'The grade the student is Currently Expected To Achieve';


$string['filter'] = 'Filter';
$string['final'] = 'Final';
$string['forcepopup:q'] = 'Force Popup?';
$string['form'] = 'Form';
$string['fullgridaccess'] = 'Full Grid Access';
$string['function'] = 'Function';

// G
$string['gcsescore'] = 'GCSE Score';
$string['general'] = 'General';
$string['generalsettings'] = 'General Settings';
$string['generalsettings:desc'] = 'General configuration settings for the plugin';
$string['generatedbygt'] = 'Generated by Moodle Grade Tracker';
$string['gotocoursepage'] = 'Course Page';
$string['grade'] = 'Grade';
$string['grades'] = 'Grades';
$string['gradessaved'] = 'Grades Saved';
$string['gradesettings'] = 'Grade Settings';
$string['gradesettings:desc'] = 'Configuration settings for Grades';
$string['gradestructure'] = 'Grading Structure';
$string['gradestructures'] = 'Grading Structures';
$string['gradingmethod'] = 'Grading Method';
$string['gradingstructuredeleted'] = 'Grading Structure Deleted';
$string['gradingstructurelocked'] = '%s has locked this Grading Structure, so you cannot create new ones unless it is unlocked';
$string['gradingstructurelockedcannotenable'] = 'You cannot enable/disable Grading Structures while the Grading Structure is locked down';
$string['gradingstructuresaved'] = 'Grading Structure Saved';
$string['gradingtype'] = 'Grading Type';
$string['grid'] = 'Grid';
$string['grids'] = 'Grids';
$string['gridkey'] = 'Grid Key';
$string['gridfilters'] = 'Grid Filters';
$string['gridsettings'] = 'Grid Settings';
$string['gridsettings:desc'] = 'Configuration settings for the various different grids';
$string['gtexception'] = 'GTException';

// H
$string['hexcode'] = 'HEX Code';

// I
$string['in'] = 'in';
$string['info'] = 'Info';
$string['img'] = 'Img';
$string['import'] = 'Import';

$string['import:tg:aspirationalgrades:desc'] = 'Calculate the Aspirational Grade based on the Target Grade set and any &plusmn; setting you have defined';
$string['import:tg:targetgrades:desc'] = 'Calculate the Target Grade for any row where you only upload an Avg GCSE Score, and no grades';
$string['import:tg:weightedtargetgrades:desc'] = 'Calculate the Weighted Target Grade based on the Target Grade, using whichever calculation method you have chosen in your Grade Settings';
$string['import:tg:updated'] = 'Target Grade for %s on qualification %s updated to %s';
$string['import:tg:avggcseupdated'] = 'Average GCSE score for %s updated to %g';
$string['import:tg:processed'] = 'CSV successfully processed and imported with %d errors. <a href="#" onclick="$(\'#gt_import_output\').toggle();return false;"><small>[View Output]</small></a>';
$string['import:cg:updated'] = 'CETA Grade for %s on %s updated to %s';
$string['import:qoe:insertgrades:desc'] = 'If selected, this will create any QoE Grades from the CSV that do not currently exist in Moodle';
$string['import:qoe:insertquals:desc'] = 'If selected, this will create any Quals on Entry from the CSV that do not currently exist in Moodle';
$string['import:qoe:insertsubjects:desc'] = 'If selected, this will create any QoE Subjects from the CSV that do not currently exist in Moodle';
$string['import:qoe:insertusers:desc'] = 'If selected, this will create any Users from the CSV that do not currently exist in Moodle';
$string['import:qoe:processed'] = 'CSV successfully processed and imported with %d errors. <a href="#" onclick="$(\'#gt_import_output\').toggle();return false;"><small>[View Output]</small></a>';
$string['import:qoe:createdsubject'] = 'Created QoE subject %s';
$string['import:qoe:createdqual'] = 'Created QoE type %s Level %s';
$string['import:qoe:createdgrade'] = 'Created QoE grade %s for %s Level %s';
$string['import:qoe:update'] = 'Updated QoE record for %s, %s Level %s (%s) with grade %s';
$string['import:qoe:calcavggcse'] = 'Calculated Average GCSE Score for %s to %g';
$string['import:qoe:calculatetargetgrades:desc'] = 'If selected, this will calculate the Target Grade for the student\'s qualifications, based on their Avg GCSE Score';
$string['import:qoe:calculateaspirationalgrades:desc'] = 'If selected, this will calculate the Aspirational Grade for the student\'s qualifications, based on their Target Grade and any &plusmn; setting you have defined';
$string['import:qoe:settg'] = 'Calculated Target grade for %s on %s, to be (%s)';
$string['import:qoe:setwtg'] = 'Calculated Weighted Target grade for %s on %s, to be (%s)';
$string['import:qoe:setasp'] = 'Calculated Aspirational grade for %s on %s, to be (%s)';
$string['import:qoe:wipeuserdata:desc'] = 'If selected, this will wipe the data for each user it comes across, before importing the new data.';
$string['import:qoe:wipeduserdata'] = 'Wiped all the QoE data for (%s)';
$string['import:assgrades:desc'] = 'Import the User Grades and User CETA Grades for an Assessment (useful if you are recording this in another system and just displaying in the Grade Tracker)';
$string['import:assgrades:updated'] = 'Assessment [%s] for %s on qualification %s updated to Grade (%s), CETA (%s)';
$string['import:datasheet:student:desc'] = 'If you have downloaded a Data Sheet for this student, you can import it back into the system here';
$string['import:datasheet:unit:desc'] = 'If you have downloaded a Data Sheet for this unit, you can import it back into the system here';
$string['import:datasheet:class:desc'] = 'If you have downloaded a Data Sheet for this class, you can import it back into the system here';
$string['import:datasheet:key:updatedsince'] = '<b>The criterion has been updated in the Gradetracker since you downloaded the spreadsheet, but the value appears to be the same.</b><br>(Most likely something else has changed, such as the Date Updated)';
$string['import:datasheet:key:updatedinsheet'] = '<b>The criterion value in your spreadsheet is different to the one in Gradetracker.</b><br>(You have updated it in the spreadsheet)';
$string['import:datasheet:key:updatedinboth'] = '<b>The criterion has been updated in the Gradetracker since you downloaded the spreadsheets, and the values do not match.</b><br>(Most likely the value has been changed on the grid)';
$string['import:datasheet:process:file'] = 'Loaded file %s ...';
$string['import:datasheet:process:worksheet'] = 'Loaded worksheet %s ...';
$string['import:datasheet:process:unit'] = 'Loaded unit %s ...';
$string['import:datasheet:process:qual'] = 'Loaded qualification %s ...';
$string['import:datasheet:process:success'] = 'Successfully updated user\'s Criterion %s to %s ...';
$string['import:datasheet:process:success:ass'] = 'Successfully updated user\'s Assessment %s %s to %s ...';
$string['import:datasheet:process:success:misc'] = 'Successfully updated user\'s %s to %s ...';
$string['import:datasheet:process:end'] = 'Process ended.';
$string['import:datasheet:process:error:qual'] = 'Error: Could not load qualification %s';
$string['import:datasheet:process:error:stud'] = 'Error: Could not load student %s';
$string['import:datasheet:process:error:unit'] = 'Error: Could not load unit %s';
$string['import:datasheet:process:error:criterion'] = 'Error: Could not load criterion %s';
$string['import:datasheet:process:error:value'] = 'Error: Invalid criterion value (%s)';
$string['import:datasheet:process:error:ass'] = 'Error: Could not find Assessment from Worksheet';
$string['import:datasheet:process:error:studass'] = 'Error: Could not load assessment (%s)';
$string['import:datasheet:process:summary'] = 'Successfully updated %d records. <small><a href="#" onclick="$(\'#gt_import_output_cmd\').toggle();return false;">[Show/Hide details]</a></small>';
$string['import:datasheet:process:deletedfile'] = 'Deleted temporary file %s ...';
$string['import:datasheet:process:autocalcunit'] = 'Recalculated unit award for %s to %s ...';
$string['import:datasheet:process:autocalcstudunit'] = 'Recalculated %s\'s unit award for %s to %s ...';
$string['import:datasheet:process:student'] = 'Loaded student %s ...';
$string['import:createduser'] = 'Created user record. Username "%s" password "%s"';
$string['import:zipfile'] = 'Attempting to extract files from Zip archive...';


$string['importavggcse:desc'] = 'Here you can import the Avg GCSE scores for students, if you do not want the Grade Tracker to calculate them for you based on their Quals on Entry records';
$string['importcomplete'] = 'Import Complete';
$string['importdatasheet'] = 'Import Data Sheet';
$string['importnewstructure'] = 'Import Structure XML';
$string['importqoe'] = 'Import Quals on Entry';
$string['importqoe:desc'] = 'Here you can import any qualifications the students have on entry. For GCSEs the "Qual" should be one of "GCSE", "GCSE Short Course" or "GCSE Double Award". Only GCSEs will be used in the calculations of Avg GCSE Score and then Target Grades, where applicable.';
$string['importtargetgrades'] = 'Import Target Grades';
$string['importtargetgrades:desc'] = 'Here you can import Target Grades and the Average GCSE Scores into Moodle. There are several options as well, which let you choose to run calculations as well.';
$string['importwarning'] = 'Some of the criteria values in the system have been updated since you downloaded the spreadsheet. Please make sure you defintely want to over-write these before continuing';
$string['includecoursecode'] = 'Include Course';
$string['includenamecols'] = 'Include Name Columns';
$string['info:block_bcgt'] = 'Found old "block_bcgt" installation (%s).<br><img src="%s" alt="data transfer" class="gt_16" /> <a href="config.php?view=data&section=block_bcgt">Transfer data from old block_bcgt to new block_gradetracker</a>';
$string['insertgrades'] = 'Insert Grades';
$string['insertquals'] = 'Insert Qualifications';
$string['insertsubjects'] = 'Insert Subjects';
$string['insertusers'] = 'Insert Users';
$string['invalidassessment'] = 'Invalid Assessment';
$string['invalidaward'] = '!!Invalid Award!!';
$string['invalidcourse'] = 'Invalid Course';
$string['invalidcoursecat'] = 'Invalid Course Category';
$string['invalidgradingstructure'] = 'Invalid Grading Structure';
$string['invalidgridtype'] = 'Invalid Grid Type';
$string['invalidlevel'] = 'Invalid Level';
$string['invalidqual'] = 'Invalid Qualification';
$string['invalidrecord'] = 'Invalid Record';
$string['invalidunit'] = 'Invalid Unit';
$string['invaliduser'] = 'Invalid User';
$string['item'] = 'Item';
$string['iv'] = 'IV';
$string['iv:desc'] = 'Internal Verification';

// K
$string['key'] = 'Key';

// L
$string['lastedited'] = 'Last Edited';
$string['lastran'] = 'Last Ran';
$string['lastupdatedby'] = 'Last Updated By';
$string['latest'] = 'Latest';
$string['letter'] = 'Letter';
$string['level'] = 'Level';
$string['levels'] = 'Levels';
$string['leveldeleted'] = 'Level Deleted';
$string['levelsaved'] = 'Level Saved';
$string['limit'] = 'Limit';
$string['loading'] = 'Loading';
$string['load'] = 'Load';
$string['lowerpoints'] = 'Lower Points';

$string['logs'] = 'Logs';
$string['log:att:qualID'] = 'Qualification';
$string['log:att:unitID'] = 'Unit';
$string['log:att:assID'] = 'Assessment';
$string['log:att:critID'] = 'Criterion';
$string['log:att:rangeID'] = 'Range';
$string['log:att:studentID'] = 'Student';
$string['log:att:courseID'] = 'Course';
$string['logdetails'] = 'Log Details';
$string['logattributes'] = 'Log-Specific Attributes';
$string['logattributes:desc'] = 'These are attributes which are saved along with the log and change depending on what type of log it is, for example, setting a user grade (e.g. target grade) will save attributes for the qualID & studentID; updating a user criterion will save attributes for the qualID, unitID, critID and studentID; updating a unit\'s information will save attributes for just the unitID, etc...';


// M
$string['mainmenu'] = 'Main Menu';
$string['manageactivityrefs'] = 'Manage Activity Links';
$string['manageassessments'] = 'Manage Assessments';
$string['managemodules'] = 'Manage Modules';
$string['managemodulelinking'] = 'Manage Module Linking';
$string['manageqoe'] = 'Manage Quals on Entry';
$string['mapping'] = 'Mapping';
$string['massupdate'] = 'Mass Update';
$string['mathsgcse'] = 'Mathematics GCSE';
$string['max'] = 'Max';
$string['maxsubcritlevels'] = 'Max Sub Criteria Levels';
$string['merge'] = 'Merge';
$string['met'] = 'Is Met';
$string['method'] = 'Method';
$string['min'] = 'Min';
$string['misc'] = 'Miscellaneous';
$string['modlinking:mod'] = 'Mod';
$string['modlinking:modtable'] = 'Module Table';
$string['modlinking:coursedbcolumn'] = 'Module `course` DB column';
$string['modlinking:startdatedbcolumn'] = 'Module `startdate` DB column';
$string['modlinking:duedatedbcolumn'] = 'Module `duedate` DB column';
$string['modlinking:instancetitledbcolumn'] = 'Module instance `title` DB column';
$string['modlinking:parttable'] = 'Module Parts Table';
$string['modlinking:partmoddbcolumn'] = 'Part `moduleinstance` DB column';
$string['modlinking:parttitledbcolumn'] = 'Part `title` DB column';
$string['modlinking:submissiontable'] = 'Submission table';
$string['modlinking:submissionuserdbcolumn'] = 'Submission `user` DB column';
$string['modlinking:submissiondatedbcolumn'] = 'Submission `date` DB column';
$string['modlinking:submissionmodinstancedbcolumn'] = 'Submission `moduleinstance` DB column';
$string['modlinking:submissionpartdbcolumn'] = 'Submission `part` DB column';
$string['modlinking:submissionstatusdbcolumn'] = 'Submission `status` DB column';
$string['modlinking:submissionstatusexpectedvalue'] = 'Submission `status` expected value';
$string['modlinking:auto'] = 'Automatic grid updates (WS, WNS, LATE)';
$string['modlinking:deleted'] = 'Deleted Mod Link';
$string['modlinking:new'] = 'Link New Module';
$string['modlinking:saved'] = 'Saved Mod Links';
$string['modlinking:error:mod'] = 'This mod is already in use by another Mod Link';
$string['multiplier'] = 'Multiplier';
$string['mycourses'] = 'My Courses';
$string['mydashboard'] = 'My Dashboard';
$string['myquals'] = 'My Quals';
$string['mytrackers'] = 'My Trackers';

// N
$string['na'] = 'N/A';
$string['name'] = 'Name';
$string['nameandusername'] = 'Name & Username';
$string['next'] = 'Next';
$string['new'] = 'New';
$string['newlevel'] = 'New Level';
$string['newqualification'] = 'New Qualification';
$string['newqualbuild'] = 'New Qualification Build';
$string['newrule'] = 'New Rule';
$string['newsubtype'] = 'New Sub Type';
$string['newunit'] = 'New Unit';
$string['newversionavailable'] = 'New version available';
$string['no'] = 'No';
$string['nogradestructuresdefined'] = 'No Grading Structures have been defined';
$string['nodata'] = 'No data could be found';
$string['nomodlinks'] = 'No Module Links have been set up in the '.$string['pluginname'].' configuration';
$string['none'] = 'None';
$string['nopercentiles'] = 'Weighting Percentiles needs to be greater than 0 to use this';
$string['norecord'] = 'No such record exists';
$string['noresults'] = 'No results could be found';
$string['normalgrid'] = 'Normal Grid';
$string['nostudentsonqual'] = 'No students are attached to this Qualification';
$string['notattempted'] = 'Not Attempted';
$string['notwriteable'] = 'Not Writable';
$string['numeric'] = 'Numeric';
$string['numericcriteria'] = 'Numeric Criteria';
$string['numobservations'] = 'No. Observations';
$string['numsteps'] = 'No. Steps';
$string['numberofunitsawarded'] = 'Units Awarded';
$string['numberofcreditsawarded'] = 'Credits Awarded';

// O
$string['object'] = 'Object';
$string['observations'] = 'Observations';
$string['ok'] = 'OK';
$string['old'] = 'Old';


$string['onevent'] = 'On Event';
$string['open'] = 'Open';
$string['openrules'] = 'Open Rules';
$string['openstudentgrid'] = 'Open Student Grid';
$string['optional'] = 'Optional';
$string['options'] = 'Options';
$string['order'] = 'Order';
$string['order:placeholder'] = 'Leave blank to use default ordering';
$string['ordering'] = 'Ordering';
$string['other'] = 'Other';
$string['overall'] = 'Overall';
$string['overview'] = 'Overview';
$string['overwrite'] = 'Overwrite';

$string['overview:quals:activequals'] = 'No. Active Qualifications';
$string['overview:quals:inactivequals'] = 'No. Inactive Qualifications';
$string['overview:quals:correctcredits'] = 'No. Qualifications with Correct Credits';
$string['overview:quals:incorrectcredits'] = 'No. Qualifications with Incorrect Credits';
$string['overview:quals:activeunits'] = 'No. Active Units';
$string['overview:quals:inactiveunits'] = 'No. Inactive Units';

// P
$string['page:settings'] = 'Please choose which settings you wish to configure.';
$string['page:structures'] = 'Please choose which structures you wish to configure.';
$string['parent'] = 'Parent';
$string['passcriteria'] = 'Pass Criteria';
$string['percentage'] = 'Percentage';
$string['pleaseselect'] = 'Please select one...';
$string['plschoosegrading'] = 'Please choose a Grading Structure';
$string['pleasechoosetracker'] = 'Please choose a Tracker';
$string['pluginsettings'] = 'Plugin Settings';
$string['points'] = 'Points';
$string['pointspercredit'] = 'Points per Credit';
$string['pointspercredit:desc'] = 'In some qualification calculations (for example BTECs) you may want to define that each credit should be worth more than the default 1 point in the Qualification Award calculations. If you are unsure you can leave this blank to use the default of 1.';
$string['pointslower'] = 'Points (Lower)';
$string['pointsupper'] = 'Points (Upper)';
$string['prebuiltreports'] = 'Pre Built Reports';
$string['predicted'] = 'Predicted';
$string['predictedfinalgrade'] = 'Predicted Final Award';
$string['predictedfinalgrade:desc'] = 'This is your final grade based on all the unit awards you have. However, be aware that occasionally awarding bodies do disagree with marks teachers have given, so it is possible your actual final award from the awarding body may differ from this.';
$string['predictedgrade'] = 'Predicted Average Award';
$string['predictedgrade:desc'] = 'The Predicted Average Award is based on the average unit awards you currently have, calculated on the assumption that you will get that average across all the rest of your unawarded units as well. It will be calculated once you have at least <b>%d</b> units awarded.';
$string['predictedgradesexplained'] = 'Predicted Grades - Explained';
$string['predictedmingrade'] = 'Predicted Min Award';
$string['predictedmingrade:desc'] = 'The Predicted Minimum Award is based on the average unit awards you currently have, calculated on the assumption that you will get the lowest possible grade for the rest of your unawarded units. It will be calculated once you have at least <b>%d</b> units awarded.';
$string['predictedmaxgrade'] = 'Predicted Max Award';
$string['predictedmaxgrade:desc'] = 'The Predicted Maximum Award is based on the average unit awards you currently have, calculated on the assumption that you will get the highest possible grade for the rest of your unawarded units. It will be calculated once you have at least <b>%d</b> units awarded.';
$string['print'] = 'Print';
$string['printgrid'] = 'Print Grid';
$string['priorquals'] = 'Prior Quals';
$string['progress'] = 'Progress';
$string['property'] = 'Property';


// Q
$string['qoe'] = 'Quals on Entry';
$string['qoescorelower'] = 'QOE Score (Lower)';
$string['qoescoreupper'] = 'QOE Score (Upper)';
$string['qoegradessaved'] = 'QOE Grades Saved';
$string['qoesubjectssaved'] = 'QOE Subjects Saved';
$string['qoetypessaved'] = 'QOE Types Saved';
$string['qualaward'] = 'Qual Award';
$string['qualawards'] = 'Qual Awards';
$string['qualdeleted'] = 'Qualification Deleted';
$string['qualrestored'] = 'Qualification Restored';
$string['qualification'] = 'Qualification';
$string['qualifications'] = 'Qualifications';
$string['qualificationselected'] = 'Qualifications Selected';
$string['qualbuild'] = 'Qualification Build';
$string['qualbuilds'] = 'Qualification Builds';
$string['qualbuildimported'] = 'Qualification Build(s) Imported';
$string['qualdefaults'] = 'Qualification Default Values';
$string['qualnounits'] = 'This Qualification has no Units';
$string['qualnostuds'] = 'This Qualification has no Students';
$string['quals'] = 'Quals';
$string['qualsaved'] = 'Qualification Saved';
$string['qualschoose'] = 'Qualifications to Choose From';
$string['qualsettings'] = 'Qualification Settings';
$string['qualsettings:desc'] = 'Configuration settings for Qualifications';
$string['qualsoncourse'] = 'Qualifications on Course';
$string['qualsoncourse:short'] = 'Quals on Course';
$string['qualstructure'] = 'Qualification Structure';
$string['qualstructures'] = 'Qualification Structures';
$string['qualstructurehasnoassessmentgradingstructure'] = 'Qualification Structure (%s) has not got a Criteria Grading Structure set to be used In Assessments';
$string['qualtype'] = 'Qualification Type';
$string['query'] = 'Query';
$string['querysaved'] = 'Query Saved';
$string['queries'] = 'Queries';

// R
$string['range'] = 'Range';
$string['ranged'] = 'Ranged';
$string['rangedcriteria'] = 'Ranged Criteria';
$string['ranges'] = 'Ranges';
$string['rank'] = 'Rank';
$string['ranking'] = 'Ranking';
$string['readless'] = 'Read Less';
$string['readmore'] = 'Read More';
$string['readonly'] = 'Readonly';
$string['readonly:q'] = 'Readonly?';
$string['recentactivity'] = 'Recent Activity';
$string['recommended'] = 'Recommended';
$string['recordexists'] = 'Record already exists';
$string['recordisdeleted'] = 'This record is deleted. You will need to recover it from the database if you want to use it again';
$string['redraw'] = 'Redraw';
$string['refreshgcsescore'] = 'Refresh Avg GCSE';
$string['refreshpredictedgrades'] = 'Refresh Predicted Grades';
$string['refreshtargetgrade'] = 'Refresh Target Grade';
$string['refreshweightedtargetgrade'] = 'Refresh Weighted Target Grade';
$string['register'] = 'Register';
$string['registergt'] = 'Register Site';
$string['registersite'] = 'Register your site';
$string['registersiteok'] = 'Site registered successfully';

$string['registration:sitename'] = 'Site Name';
$string['registration:url'] = 'Site URL';
$string['registration:privacy'] = 'Privacy';
$string['registration:privacy:allgood'] = '0. All information can be published';
$string['registration:privacy:donttalktome'] = '1. All information except your contact details can be published';
$string['registration:privacy:nameonly'] = '2. Only site name can be published';
$string['registration:privacy:hidemyass'] = '3. Do not publish anything';
$string['registration:admin'] = 'Administrator';
$string['registration:adminemail'] = 'Email Address';



$string['reloadcsvs'] = 'Reload CSV Files';
$string['remove'] = 'Remove';
$string['report'] = 'Report';
$string['reports'] = 'Reports';
$string['reportsettings'] = 'Report Settings';
$string['reportsettings:desc'] = 'Configuration settings related to the Reporting feature';

$string['reports:critprog'] = 'Criteria Progress Report';
$string['reports:critprog:desc'] = 'This report can be run against any Qualification Structure using the "Short Criteria Display" configuration setting. It will generate an excel spreadsheet with the following information:<br><ul><li>One worksheet for each course category</li><li>A list of each of the courses in that category with active qualification links</li><li>For each qualification - a list of the students with colour-coded summary data: <ul><li>Number of each criteria type met</li><li>Weighting scores</li><li>Percentage of possible criteria awarded</li></ul></li></ul><br><b>Note:</b> Only Qualification Structures with the setting <i>Dashboard Grid Display</i> set to "Short Criteria Display" will be reported on.<br><b>Note:</b> If you wish to report on the Weighted Score of the criteria, you will need to set the criteria letters and scores in Config >> Plugin Settings >> Report Settings';
$string['reports:critprog:screenshot'] = 'Example Screenshot of a Criteria Progress report';
$string['reports:passprog'] = 'Pass Criteria Progress Report';
$string['reports:passprog:desc'] = 'This report can be run against any Qualification Structure to generate statistics on "Pass" criteria (what constitutes a "Pass" criterion can be defined in Config >> Plugin Settings >> Report Settings). It will generate an excel spreadsheet with the following information:<br><ul><li>One worksheet for each course category</li><li>A list of each of the courses in that category with active qualification links</li><li>For each qualification - a list of the students with the following information: <ul><li>Overall Weighted Criteria Progress % (If you have setup Weighted scores for the "Criteria Progress" report)</li><li>Pass Criteria Achieved % (based on the most achieved by anyone on the qualification)</li><li>Maximum Pass Criteria Achieved % (based on how many there are on the qualification in total)</li></ul></li></ul><br><b>Note:</b> If you wish to report on the Weighted Score of the criteria, you will need to set the criteria letters and scores in Config >> Plugin Settings >> Report Settings';
$string['reports:passprog:byletter'] = 'By first letter <small>(separate with commas)</small>';
$string['reports:passprog:bygradestructure'] = 'By grading structure';
$string['reports:passprog:all'] = 'All';
$string['reports:passprog:header:weightedscoreachieved'] = 'All - Best';
$string['reports:passprog:header:passcritachieved'] = 'Pass - Best';
$string['reports:passprog:header:passcritachievedtotal'] = 'Pass - Total';
$string['reports:passprog:screenshot'] = 'Example Screenshot of a "Pass" Criteria Progress report';
$string['reports:passsummary'] = 'Pass Criteria Summary Report';
$string['reports:passsummary:desc'] = 'This report can be run against any Qualification Structure to generate statistics on "Pass" criteria (what constitutes a "Pass" criterion can be defined in Config >> Plugin Settings >> Report Settings). It will generate an excel spreadsheet with the following information:<br><ul><li>One worksheet for each course category</li><li>A list of each of the courses in that category with active qualification links</li><li>For each qualification - a summary of student information: <ul><li>Overall Weighted Criteria Progress % (If you have setup Weighted scores for the "Criteria Progress" report)</li><li>Pass Criteria Achieved % (based on the most achieved by anyone on the qualification)</li><li>Proportion of students in each status boundary for "Pass" Criteria achieved</li><li>Proportion of students in each status boundary for Weighted Criteria achieved</li></ul></li></ul><br><b>Note:</b> If you wish to report on the Weighted Score of the criteria, you will need to set the criteria letters and scores in Config >> Plugin Settings >> Report Settings';
$string['reports:passsummary:screenshot'] = 'Example Screenshot of a "Pass" Criteria Summary report';
$string['reports:passsummary:header:propassach'] = 'Proportion of Assessed "Pass" Criteria Achieved';
$string['reports:passsummary:header:propwtach'] = 'Proportion of Assessed Weighted Criteria Achieved';
$string['reports:passsummary:options:values'] = 'By default this report will count up the criteria which are "MET". If you want to also include any criteria with specific values, for example Partially Achieved, Referred, etc... in the count, you can select them from the list below:';

$string['reportoption:type'] = 'Type';

$string['reporting'] = 'Reporting';
$string['req'] = 'Req';
$string['reqallscored'] = 'All criteria must be scored?';
$string['reset'] = 'Reset';
$string['restore'] = 'Restore';
$string['result'] = 'Result';
$string['results'] = 'Results';
$string['rule'] = 'Rule';

$string['rulename'] = 'Rule Name';
$string['ruleexecuteon'] = 'Executes On';
$string['rule:setting:details'] = '1. Details';
$string['rule:setting:event'] = '2. Execute On Event';
$string['rule:setting:steps'] = '3. Rule Steps';
$string['rule:setting:steps:ifcond'] = 'If these conditions are met...';
$string['rule:setting:steps:thenact'] = 'Then perform these actions...';
$string['rule:event:onCriterionAwardUpdate'] = 'When a criterion award is updated';
$string['rule:event:onUnitAwardUpdate'] = 'When a unit award is updated';

$string['ruleinfo'] = 'Rule Info';
$string['rules'] = 'Rules';
$string['rulesets'] = 'Rule Sets';
$string['rulesteps'] = 'Rule Steps';
$string['run'] = 'Run';
$string['running'] = 'Running';
$string['runquery'] = 'Run Query';

// S
$string['save'] = 'Save';
$string['saved'] = 'Saved';
$string['score'] = 'Score';
$string['search'] = 'Search';
$string['searchcourse'] = 'Search for Course';
$string['searchforunit'] = 'Search for Unit';
$string['searchqual'] = 'Search for Qualification';
$string['selectdeselectall'] = 'Select/Deselect All';
$string['selectedquals'] = 'Selected Qualifications';
$string['selectedstuds'] = 'Selected Students';
$string['selectedunits'] = 'Selected Units';
$string['selectgrid'] = 'Select a Grid';
$string['setby'] = 'Set By';
$string['settime'] = 'Set Time';
$string['setting'] = 'Setting';
$string['settings'] = 'Settings';

$string['setting:criteriaordering'] = 'Criteria Ordering';
$string['setting:unitordering'] = 'Unit Ordering';
$string['setting:targetgrades'] = 'Target Grades';
$string['setting:targetgrades:both'] = 'Allow student to see Target and Weighted Target';
$string['setting:targetgrades:tg'] = 'Student can only see Target';
$string['setting:targetgrades:wtg'] = 'Student can only see Weighted Target';
$string['setting:ivcolumn'] = 'IV Column';
$string['setting:ivcolumn:desc'] = 'Have a column on the grid to record who the IV for the unit was and when the IV was done';
$string['setting:forcesinglepage'] = 'Force Single Page';
$string['setting:critletters'] = 'Criteria Letters';

$string['settingsupdated'] = 'Settings updated';
$string['sexleft'] = 'secs left';
$string['shortgrade'] = 'Short Grade';
$string['shortname'] = 'Short Name';
$string['showhide'] = 'Show/Hide';
$string['showhidecomments'] = 'Show/Hide Comments';
$string['skip'] = 'Skip';
$string['specialval'] = 'Special Value';
$string['sqlreports'] = 'SQL Reports';
$string['staff'] = 'Staff';
$string['standard'] = 'Standard';
$string['standardcriteria'] = 'Standard Criteria';
$string['startdebugging'] = 'Start Debugging Script';
$string['stats'] = 'Stats';
$string['step'] = 'Step';
$string['stopdebugging'] = 'Stop Debugging Script';
$string['structure'] = 'Structure';
$string['structuredeleted'] = 'Structure Deleted';
$string['structureimported'] = 'Structure(s) Imported';
$string['structureisdisabled'] = 'This Structure is currently DISABLED';
$string['structures'] = 'Structures';
$string['structuresaved'] = 'Structure Saved';
$string['student'] = 'Student';
$string['students'] = 'Students';
$string['studentgrid'] = 'Student Grid';
$string['studentgrids'] = 'Student Grids';
$string['studentgridsettings'] = 'Student Grid Settings';
$string['studentgridsettings:desc'] = 'Settings relating only to the Student Grids';
$string['studenresultsfilter'] = 'Students Results Filter';
$string['studenresultsfilter:all'] = 'All Results';
$string['studenresultsfilter:allmarked'] = 'Students with all units marked';
$string['studenresultsfilter:someoutstanding'] = 'Students with units outstanding';
$string['studenresultsfilter:alloutstanding'] = 'Students with all units outstanding';
$string['subcategory'] = 'Sub Category';
$string['subcriteria'] = 'Sub Criteria';
$string['subject'] = 'Subject';
$string['subjects'] = 'Subjects';
$string['subtype'] = 'Sub Type';
$string['subtypes'] = 'Sub Types';
$string['subtypedeleted'] = 'Sub Type Deleted';
$string['subtypesaved'] = 'Sub Type Saved';
$string['success'] = 'Success';
$string['switchcourse'] = 'Switch Course';
$string['switchgroup'] = 'Switch Group';
$string['switchqual'] = 'Switch Qualification';
$string['switchunit'] = 'Switch Unit';
$string['switchuser'] = 'Switch User';
$string['systemoverview'] = 'System Overview';
$string['systeminfo'] = 'System Information';
$string['systemtests'] = 'System Tests';

$string['system:moodleversion'] = 'Moodle Version';
$string['system:moodleversion:info'] = 'Your Moodle version will not be published';
$string['system:gtversion'] = 'Grade Tracker Version';
$string['system:updatesavailable'] = 'Updates';
$string['system:dataroot'] = 'Grade Tracker Data Directory';
$string['system:registered'] = 'Site Registration';
$string['system:registered:no'] = 'Your site is not registered';
$string['system:registered:yes'] = '<b>%s</b><br>Last updated %s (%s)';
$string['system:registered:task'] = 'Next update scheduled for: %s';
$string['system:count:quals'] = 'No. Qualifications';
$string['system:count:units'] = 'No. Units';
$string['system:count:criteria'] = 'No. Criteria';
$string['system:count:structures'] = 'No. Structures';
$string['system:gtinfo'] = 'Grade Tracker Stats';
$string['system:gtinfo:info'] = 'These stats will not be published';


// T
$string['task:updategridsfromactivities'] = 'Update Grids from Activities';
$string['task:refresh_site_registration'] = 'Refresh Site Registration Info';
$string['task:clean_up'] = 'Clean Up';

$string['target'] = 'Target';
$string['target:acronym'] = 'T';
$string['targetgrade'] = 'Target Grade';
$string['targetgrades'] = 'Target Grades';
$string['targetgrade:short'] = 'Target';
$string['targetgrades:descExport'] = 'Export Target Grade Data to CSV.';
$string['targetgrade:help'] = 'This is calculated from your Average GCSE score and (based on those) is the minimum grade you should be aiming to get';
$string['template'] = 'Template';
$string['test'] = 'Test';
$string['tests'] = 'Tests';
$string['textinput'] = 'Text Input';
$string['tickall'] = 'Tick All';
$string['time'] = 'Time';
$string['toomanytoshow'] = 'Too many results to show them all...';
$string['totalcredits'] = 'Total Credits';
$string['totalpoints'] = 'Total Points';
$string['totalweightedscores'] = 'Total Weighted Scores';
$string['trackers'] = 'Trackers';
$string['transfer'] = 'Transfer';
$string['type'] = 'Type';
$string['types'] = 'Types';

// U
$string['ucaspoints'] = 'UCAS Points';
$string['unit'] = 'Unit';
$string['unitdeleted'] = 'Unit Deleted';
$string['unitrestored'] = 'Unit Restored';
$string['units'] = 'Units';
$string['unitname'] = 'Unit Name';
$string['unitgradingstructuresaved'] = 'Unit Grading Structure Saved';
$string['unitschoose'] = 'Units to Choose From';
$string['unitsettings'] = 'Unit Settings';
$string['unitsettings:desc'] = 'Configuration settings for Units';
$string['unitsonqual'] = 'Units on Qualification';
$string['unitgrading'] = 'Unit Grading';
$string['unitgradingstructures'] = 'Unit Grading Structures';
$string['unknown'] = 'UNKNOWN';
$string['up'] = 'Up';
$string['update'] = 'Update';
$string['updatebuildimport'] = 'If the Qualification Build already exists';
$string['updateregistration'] = 'Update Site';
$string['updatestructureimport'] = 'If the Qualification Structure already exists';
$string['updatetime'] = 'Update Time';
$string['upload'] = 'Upload';
$string['uploads:cantopenfile'] = 'Cannot open file';
$string['uploads:dirnoexist'] = 'Upload directory does not exist or is not writeable';
$string['uploads:filenotset'] = 'File Not Set';
$string['uploads:filetoolarge'] = 'File exceeds maximum allowed size';
$string['uploads:invalidmimetype'] = 'Invalid file format';
$string['uploads:mimetypesnotset'] = 'Mime types not set';
$string['uploads:notmpdir'] = 'No tmp directory';
$string['uploads:onlypartial'] = 'File was only partially uploaded';
$string['uploads:phpextension'] = 'A PHP extension stopped the file upload. Check list of loaded extensions to determine what and why';
$string['uploads:postexceeded'] = 'No POST or FILES data could be found. This is most likely because the file was too large and has exceeded the server\'s max post size.';
$string['uploads:sidnotset'] = 'Student ID not set';
$string['uploads:titlenotset'] = 'File title not set';
$string['uploads:uploaddirnotset'] = 'Upload directory not set';
$string['uploads:unknownerror'] = 'Unknown error';
$string['uploads:movingfiles'] = 'Cannot move files to new location';
$string['uptodate'] = 'No new updates available';
$string['upperpoints'] = 'Upper Points';
$string['unabletocheckforupdates'] = 'Unable to check for updates';
$string['uniquecode'] = 'Unique Code';
$string['unitaward'] = 'Unit Award';
$string['unitgrid'] = 'Unit Grid';
$string['unitname'] = 'Unit Name';
$string['unitgridsettings'] = 'Unit Grid Settings';
$string['unitgridsettings:desc'] = 'Settings relating only to the Unit Grids';
$string['unitgrids'] = 'Unit Grids';
$string['unitnumber'] = 'Unit Number';
$string['unitpoints'] = 'Unit Points';
$string['unitpoints:desc'] = 'In some Qualification types (such as BTEC) unit points are set for different levels, to take into account in Qualification Award calculations. If this Qualification Structure doesn\'t use them, just leave this blank.';
$string['unitpointslower'] = 'Unit Points (Lower)';
$string['unitpointsupper'] = 'Unit Points (Upper)';
$string['unitsaved'] = 'Unit Saved';
$string['unitset'] = 'Unit Set';
$string['unitsfeaturenotenabled'] = '%s does not have the Units feature enabled';
$string['unittype'] = 'Unit Type';
$string['unsaved'] = 'Unsaved..';
$string['url'] = 'URL';
$string['useforassessments'] = 'Use in Assessments';
$string['useforsummary'] = 'Use in Summary';
$string['user'] = 'User';
$string['usernotonunit'] = 'User is not attached to either this unit or this qualification';
$string['users'] = 'Users';
$string['username'] = 'Username';
$string['userprofile'] = 'User Profile';
$string['usersettings'] = 'User Settings';
$string['usersettings:desc'] = 'User-related settings';
$string['userquals'] = 'User Qualifications';
$string['userqualssaved'] = 'User Qualifications Saved';
$string['userunits'] = 'User Units';
$string['userunitssaved'] = 'User Units Saved';


// V
$string['value'] = 'Value';
$string['valueadded'] = 'Value Added';
$string['valueadded:acronym'] = 'VA';
$string['valueadded:gradecmp'] = 'Compare this Award/Grade';
$string['valueadded:targetcmp'] = 'Against this Target/Grade';
$string['verifier'] = 'Verifier';
$string['verify'] = 'Verify';
$string['verifyandadd'] = 'Verify & Add';
$string['view'] = 'View';
$string['viewbyclass'] = 'View by Class';
$string['viewbystudent'] = 'View by Student';
$string['viewbyunit'] = 'View by Unit';
$string['viewdata'] = 'View Data';
$string['viewgrid'] = 'View Grid';
$string['viewlogs'] = 'View Debugging Logs';

$string['versionupdatetype_general'] = 'General Update';
$string['versionupdatetype_performance'] = 'Performance Update';
$string['versionupdatetype_security'] = 'Security Update';
$string['versionupdatetype_securitycrit'] = 'Critical Security Update';

// W
$string['warning'] = 'Warning';
$string['wcoe'] = 'Weighting Coefficients';
$string['wcoe:desc'] = 'Here you can import Weighting Coefficients sheets.';
$string['wcoe:descExport'] = 'Export Weighting Coefficients Data to CSV.';
$string['weight'] = 'Weight';
$string['weighted'] = 'Weighted';
$string['weighted:method1'] = 'Method 1 (GCSE)';
$string['weighted:method2'] = 'Method 2 (UCAS)';
$string['weightedtarget'] = 'Weighted Target';
$string['weightedtarget:acronym'] = 'WT';
$string['weightedtargetgrade'] = 'Weighted Target Grade';
$string['weightedtargetgrades'] = 'Weighted Target Grades';
$string['weightedtargetgrade:short'] = 'Weighted Target';

$string['weighting'] = 'Weighting';
$string['weightingcoefficients'] = 'Weighting Coefficients';
$string['weightingcoefficientsaved'] = 'Weighting Coefficient (%s) %s saved';
$string['weightingcoefficients:desc'] = 'The coefficients define the difficulty of the qualification. For example Physics is more difficult than Photography, so a smaller coefficient should be set for Physics, to ensure the Weighted Target Grade for Physics is lower than for Photography.';
$string['weightingconstants'] = 'Weighting Constants';
$string['weightingconstants:desc'] = 'Some coefficients are close to 1. When this occurs the grades may not change. In these cases a ucas points constant (e.g. half a grade, a grade) can be applied to their target grade before being multiplied. This then makes future alps checks more accurate. And makes it more likely a student will have a grade change.';
$string['whoops'] = 'Whoops';
$string['wipeuserdata'] = 'Wipe User Data';
$string['withunitawarddoingunit'] = 'No. Studs With Unit Award / Doing Unit';
$string['writeable'] = 'Writable';


// X


// Y
$string['year'] = 'Year';
$string['yes'] = 'Yes';




// Report elements
$string['reports:gradetracker:quals'] = 'Quals';
$string['reports:gradetracker:tg'] = 'Target Grade';
$string['reports:gradetracker:aspg'] = 'Aspirational Grade';
$string['reports:gradetracker:avggcse'] = 'Avg GCSE';



// Overriding old elements
$string['mintargetgrade'] = $string['targetgrade'];
