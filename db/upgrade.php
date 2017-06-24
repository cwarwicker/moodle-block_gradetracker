<?php
/**
 * Standard moodle block upgrade script
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

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

function xmldb_block_gradetracker_upgrade($oldversion = 0)
{
    
    global $CFG, $DB;
    
    $dbman = $DB->get_manager();
    $result = true;
    
    
    // test 4 me
    if ($oldversion < 2015071300)
    {
        
        // ======================== Install data ======================== //

        // Qualification Structure Features
        $features = \GT\QualificationStructure::_features();
        if ($features){
            foreach($features as $feature){

                $check = $DB->get_record("bcgt_qual_structure_features", array("name" => $feature));
                if (!$check){

                    $obj = new stdClass();
                    $obj->name = $feature;
                    $result = $result && $DB->insert_record("bcgt_qual_structure_features", $obj);

                }

            }
        }


        // Qualification Structure Levels
        $levels = \GT\QualificationStructure::_levels();
        if ($levels){
            foreach($levels as $level => $minMax){

                $check = $DB->get_record("bcgt_qual_structure_levels", array("name" => $level));
                if ($check){
                    $check->minsublevels = $minMax[0];
                    $check->maxsublevels = $minMax[1];
                    $result = $result && $DB->update_record("bcgt_qual_structure_levels", $check);
                } else {
                    $obj = new stdClass();
                    $obj->name = $level;
                    $obj->minsublevels = $minMax[0];
                    $obj->maxsublevels = $minMax[1];
                    $result = $result && $DB->insert_record("bcgt_qual_structure_levels", $obj);
                }

            }
        }

        
    }
    
    // Add new fields onto the user_assessments table
    if ($oldversion < 2015121700)
    {
        
        // Define field lastupdate to be added to bcgt_user_assessments.
        $table = new xmldb_table('bcgt_user_assessments');
        
        $field = new xmldb_field('lastupdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'comments');

        // Conditionally launch add field lastupdate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $field = new xmldb_field('lastupdateby', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'lastupdate');

        // Conditionally launch add field lastupdateby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $key = new xmldb_key('lu_fk', XMLDB_KEY_FOREIGN, array('lastupdateby'), 'user', array('id'));

        // Launch add key lu_fk.
        $dbman->add_key($table, $key);
        
        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2015121700, 'gradetracker');
        
    }
    
    // Add "buildid" column to bcgt_crit_award_structures
    if ($oldversion < 2016020402) {

        // Define field buildid to be added to bcgt_crit_award_structures.
        $table = new xmldb_table('bcgt_crit_award_structures');
        $field = new xmldb_field('buildid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'qualstructureid');

        // Conditionally launch add field buildid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        
        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016020402, 'gradetracker');
        
    }
    
    // Change geadingstructureid field on criteria table to allow NULL for readonly criteria
    if ($oldversion < 2016030100)
    {
        
        $table = new xmldb_table('bcgt_criteria');

        // Drop key
        $key = new xmldb_key('gsid_fk', XMLDB_KEY_FOREIGN, array('gradingstructureid'), 'bcgt_crit_award_structures', array('id'));
        $dbman->drop_key($table, $key);
        
        
        // Changing nullability of field gradingstructureid on table bcgt_criteria to null.
        $field = new xmldb_field('gradingstructureid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'subcrittype');

        // Launch change of nullability for field gradingstructureid.
        $dbman->change_field_notnull($table, $field);
        
        
        // Add key again
        $key = new xmldb_key('gsid_fk', XMLDB_KEY_FOREIGN, array('gradingstructureid'), 'bcgt_crit_award_structures', array('id'));
        $dbman->add_key($table, $key);

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016030100, 'gradetracker');
        
    }
    
    // Add score field to user_assessments table to store grade if we use numeric grading
    if ($oldversion < 2016041400) {

        // Define field score to be added to bcgt_user_assessments.
        $table = new xmldb_table('bcgt_user_assessments');
        $field = new xmldb_field('score', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'lastupdateby');

        // Conditionally launch add field score.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016041400, 'gradetracker');
        
    }

    
    if ($oldversion < 2016042600) {

        // Define table bcgt_assessment_attributes to be created.
        $table = new xmldb_table('bcgt_assessment_attributes');

        // Adding fields to table bcgt_assessment_attributes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('assessmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('qualid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attribute', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table bcgt_assessment_attributes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('aid_fk', XMLDB_KEY_FOREIGN, array('assessmentid'), 'bcgt_assessments', array('id'));
        $table->add_key('qid_fk', XMLDB_KEY_FOREIGN, array('qualid'), 'bcgt_qualifications', array('id'));
        $table->add_key('uid_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Conditionally launch create table for bcgt_assessment_attributes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016042600, 'gradetracker');
        
    }
    
    if ($oldversion < 2016060100) {

        // Define table bcgt_query_reporting to be created.
        $table = new xmldb_table('bcgt_query_reporting');

        // Adding fields to table bcgt_query_reporting.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('query', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('created_date', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('last_edited', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('last_ran', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table bcgt_query_reporting.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for bcgt_query_reporting.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016060100, 'gradetracker');
        
    }
    
    
    if ($oldversion < 2016060101)
    {
        
        // Define field lastupdate to be added to bcgt_unit_attributes.
        $table = new xmldb_table('bcgt_unit_attributes');
        $field = new xmldb_field('lastupdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'value');

        // Conditionally launch add field lastupdate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        
        
        // Define field lastupdate to be added to bcgt_qual_attributes.
        $table = new xmldb_table('bcgt_qual_attributes');
        $field = new xmldb_field('lastupdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'value');

        // Conditionally launch add field lastupdate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        
        
        // Define field lastupdate to be added to bcgt_criteria_attributes.
        $table = new xmldb_table('bcgt_criteria_attributes');
        $field = new xmldb_field('lastupdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'value');

        // Conditionally launch add field lastupdate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        
        
        // Define field lastupdate to be added to bcgt_assessment_attributes.
        $table = new xmldb_table('bcgt_assessment_attributes');
        $field = new xmldb_field('lastupdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'value');

        // Conditionally launch add field lastupdate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

                
        
        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016060101, 'gradetracker');
        
    }
    
    
    if ($oldversion < 2016071900)
    {
        
        
        $table = new xmldb_table('bcgt_crit_award_structures');
        
        
        // Drop the qualstructureid key
        $key = new xmldb_key('qsid_fk', XMLDB_KEY_FOREIGN, array('qualstructureid'));
        $dbman->drop_key($table, $key);
        
                
        // Changing nullability of field qualstructureid on table bcgt_crit_award_structures to null.
        $field = new xmldb_field('qualstructureid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');
        $dbman->change_field_notnull($table, $field);

        
        // Put the key back
        $key = new xmldb_key('qsid_fk', XMLDB_KEY_FOREIGN, array('qualstructureid'), 'bcgt_qual_structures', array('id'));
        $dbman->add_key($table, $key);
        
        
        // Define key bid_fk (foreign) to be added to bcgt_crit_award_structures.
        $key = new xmldb_key('bid_fk', XMLDB_KEY_FOREIGN, array('buildid'), 'bcgt_builds', array('id'));
        $dbman->add_key($table, $key);
            
        
        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016071900, 'gradetracker');
        
    }
    
    
    if ($oldversion < 2016072500)
    {
        
        // Define table bcgt_data_mapping to be created.
        $table = new xmldb_table('bcgt_data_mapping');

        // Adding fields to table bcgt_data_mapping.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('context', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('item', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('oldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('newid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table bcgt_data_mapping.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for bcgt_data_mapping.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016072500, 'gradetracker');
        
    }
    
    if ($oldversion < 2016072501) {

        // Changing type of field ordernum on table bcgt_qual_levels to number.
        $table = new xmldb_table('bcgt_qual_levels');
        $field = new xmldb_field('ordernum', XMLDB_TYPE_NUMBER, '4, 1', null, XMLDB_NOTNULL, null, '0', 'shortname');

        // Launch change of type for field ordernum.
        $dbman->change_field_type($table, $field);

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016072501, 'gradetracker');
        
    }
    
    if ($oldversion < 2016080516)
    {
        
         // Changing type of field oldid on table bcgt_data_mapping to text.
        $table = new xmldb_table('bcgt_data_mapping');
        
        // Launch change of type for field oldid.
        $field = new xmldb_field('oldid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'item');
        $dbman->change_field_type($table, $field);

        // Launch change of type for field newid.
        $field = new xmldb_field('newid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'oldid');
        $dbman->change_field_type($table, $field);
        
        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016080516, 'gradetracker');
        
    }

    if ($oldversion < 2016081600) {
        
        // Define table bcgt_logs to be dropped.
        $table = new xmldb_table('bcgt_logs');

        // Conditionally launch drop table for bcgt_logs.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        
        // Define table bcgt_logs to be created.
        $table = new xmldb_table('bcgt_logs');

        // Adding fields to table bcgt_logs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userip', XMLDB_TYPE_CHAR, '40', null, null, null, null);
        $table->add_field('backtrace', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('beforejson', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('afterjson', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('details', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('context', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table bcgt_logs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('usrid_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Conditionally launch create table for bcgt_logs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        
        
        
        
        // Define table bcgt_log_attributes to be created.
        $table = new xmldb_table('bcgt_log_attributes');

        // Adding fields to table bcgt_log_attributes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('logid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attributename', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('attributevalue', XMLDB_TYPE_INTEGER, '10', null, null, null, null);


        // Adding keys to table bcgt_log_attributes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('lid_fk', XMLDB_KEY_FOREIGN, array('logid'), 'bcgt_logs', array('id'));

        // Conditionally launch create table for bcgt_log_attributes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016081600, 'gradetracker');
        
    }

    
    
    if ($oldversion < 2016081700) {

        // Changing type of field attributename on table bcgt_log_attributes to text.
        $table = new xmldb_table('bcgt_log_attributes');
        $field = new xmldb_field('attributename', XMLDB_TYPE_TEXT, null, null, null, null, null, 'logid');

        // Launch change of type for field attributename.
        $dbman->change_field_type($table, $field);

        // Changing type of field attributevalue on table bcgt_log_attributes to text.
        $table = new xmldb_table('bcgt_log_attributes');
        $field = new xmldb_field('attributevalue', XMLDB_TYPE_TEXT, null, null, null, null, null, 'attributename');

        // Launch change of type for field attributevalue.
        $dbman->change_field_type($table, $field);
        
        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016081700, 'gradetracker');
        
    }
    
    
    if ($oldversion < 2016081900) {

        // Define table bcgt_qual_structure_rule_set to be created.
        $table = new xmldb_table('bcgt_qual_structure_rule_set');

        // Adding fields to table bcgt_qual_structure_rule_set.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('qualstructureid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table bcgt_qual_structure_rule_set.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('qsid_fk', XMLDB_KEY_FOREIGN, array('qualstructureid'), 'bcgt_qual_structures', array('id'));

        // Conditionally launch create table for bcgt_qual_structure_rule_set.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        
        
        // Alter rules table to apply rule sets
        
        // FIrst need to wipe the table, as can't add NOT NULL field to existing records without default value
        $DB->delete_records("bcgt_qual_structure_rules");
        
        $table = new xmldb_table('bcgt_qual_structure_rules');
        
        
        // Define key qsid_fk (foreign) to be dropped form bcgt_qual_structure_rules.
        $key = new xmldb_key('qsid_fk', XMLDB_KEY_FOREIGN, array('qualstructureid'), 'bcgt_qual_structures', array('id'));
        $dbman->drop_key($table, $key);
        
        
        
        // Conditionally launch drop field qualstructureid.
        $field = new xmldb_field('qualstructureid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        
        
        // Conditionally launch add field setid.
        $field = new xmldb_field('setid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        
        
        // Launch add key sid_fk.
        $key = new xmldb_key('sid_fk', XMLDB_KEY_FOREIGN, array('setid'), 'bcgt_qual_structure_rule_set', array('id'));
        $dbman->add_key($table, $key);
        
        

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016081900, 'gradetracker');
        
    }

    
    
    if ($oldversion < 2016082300) {

        // Define field enabled to be added to bcgt_qual_structure_rule_set.
        $table = new xmldb_table('bcgt_qual_structure_rule_set');
        $field = new xmldb_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'name');

        // Conditionally launch add field enabled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016082300, 'gradetracker');
        
    }
    
    
    if ($oldversion < 2016082600) {

        // Define field isdefault to be added to bcgt_qual_structure_rule_set.
        $table = new xmldb_table('bcgt_qual_structure_rule_set');
        $field = new xmldb_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'name');

        // Conditionally launch add field isdefault.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016082600, 'gradetracker');
    }
    
    
    if ($oldversion < 2016092200) {

        // Define index indx (unique) to be added to bcgt_qual_structure_levels.
        $table = new xmldb_table('bcgt_qual_structure_levels');
        $index = new xmldb_index('indx', XMLDB_INDEX_UNIQUE, array('id', 'name'));

        // Conditionally launch add index indx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016092200, 'gradetracker');
    }

    
    if ($oldversion < 2016092900) {

        // Define index uu_indx (not unique) to be added to bcgt_user_qual_units.
        $table = new xmldb_table('bcgt_user_qual_units');
        
        $index = new xmldb_index('uu_indx', XMLDB_INDEX_NOTUNIQUE, array('userid', 'unitid'));

        // Conditionally launch add index uu_indx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('uur_indx', XMLDB_INDEX_NOTUNIQUE, array('userid', 'unitid', 'role'));

        // Conditionally launch add index uur_indx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        $index = new xmldb_index('uuq_indx', XMLDB_INDEX_NOTUNIQUE, array('userid', 'unitid', 'qualid'));

        // Conditionally launch add index uuq_indx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        $index = new xmldb_index('uuqr_indx', XMLDB_INDEX_NOTUNIQUE, array('userid', 'unitid', 'qualid', 'role'));

        // Conditionally launch add index uuqr_indx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        
        
        // Define index uq_indx (not unique) to be added to bcgt_user_quals.
        $table = new xmldb_table('bcgt_user_quals');
        $index = new xmldb_index('uq_indx', XMLDB_INDEX_NOTUNIQUE, array('userid', 'qualid'));

        // Conditionally launch add index uq_indx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        $index = new xmldb_index('uqr_indx', XMLDB_INDEX_NOTUNIQUE, array('userid', 'qualid', 'role'));

        // Conditionally launch add index uqr_indx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        
        
        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016092900, 'gradetracker');
    }

    
    if ($oldversion < 2016101100) {

        // Changing type of field unitnumber on table bcgt_units to char.
        $table = new xmldb_table('bcgt_units');
        $field = new xmldb_field('unitnumber', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'gradingstructureid');

        // Launch change of type for field unitnumber.
        $dbman->change_field_type($table, $field);

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016101100, 'gradetracker');
        
    }

    // Update bcgt_unit_award_points table to allow us to store the points against a qual build
    if ($oldversion < 2016120100)
    {
        
        // Define key lid_fk (foreign) to be dropped from bcgt_unit_award_points.
        $table = new xmldb_table('bcgt_unit_award_points');
        $key = new xmldb_key('lid_fk', XMLDB_KEY_FOREIGN, array('levelid'), 'bcgt_qual_levels', array('id'));

        // Launch drop key lid_fk.
        $dbman->drop_key($table, $key);

        
        
        // Changing nullability of field levelid on table bcgt_unit_award_points to null.
        $table = new xmldb_table('bcgt_unit_award_points');
        $field = new xmldb_field('levelid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'qualstructureid');

        // Launch change of nullability for field levelid.
        $dbman->change_field_notnull($table, $field);
        
        
        
        
        // Define key lid_fk (foreign) to be added to bcgt_unit_award_points.
        $table = new xmldb_table('bcgt_unit_award_points');
        $key = new xmldb_key('lid_fk', XMLDB_KEY_FOREIGN, array('levelid'), 'bcgt_qual_levels', array('id'));

        // Launch add key lid_fk.
        $dbman->add_key($table, $key);
        
        
        
        
        // Define field qualbuildid to be added to bcgt_unit_award_points.
        $table = new xmldb_table('bcgt_unit_award_points');
        $field = new xmldb_field('qualbuildid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'qualstructureid');

        // Conditionally launch add field qualbuildid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        
        
        // Define key bid_fk (foreign) to be added to bcgt_unit_award_points.
        $table = new xmldb_table('bcgt_unit_award_points');
        $key = new xmldb_key('bid_fk', XMLDB_KEY_FOREIGN, array('qualbuildid'), 'bcgt_qual_builds', array('id'));

        // Launch add key bid_fk.
        $dbman->add_key($table, $key);
        
        
        
        
        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016120100, 'gradetracker');
        
    }
    
    if ($oldversion < 2016120200)
    {
        
        // Changing precision of field points on table bcgt_unit_award_points to (4, 1).
        $table = new xmldb_table('bcgt_unit_award_points');
        $field = new xmldb_field('points', XMLDB_TYPE_NUMBER, '4, 1', null, XMLDB_NOTNULL, null, null, 'awardid');

        // Launch change of precision for field points.
        $dbman->change_field_precision($table, $field);

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2016120200, 'gradetracker');
        
    }
    
    
    if ($oldversion < 2017011800)
    {
        
        // Define field description to be added to bcgt_qual_structure_rules.
        $table = new xmldb_table('bcgt_qual_structure_rules');
        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');

        // Conditionally launch add field description.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gradetracker savepoint reached.
        upgrade_block_savepoint(true, 2017011800, 'gradetracker');
        
    }
    

    return $result;
    
}