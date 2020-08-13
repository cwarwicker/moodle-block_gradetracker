<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Hooks to insert gradetracker elements into other pages/forms
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

/**
 * This adds the gradetracker box to the assignment or activity creation form
 * mod/assign/mod_form.php - line 168
 * @param type $mform
 */
function gt_mod_hook(&$mform, $cm = false) {

    global $COURSE, $PAGE, $OUTPUT;

    // Make sure the gradetracker block is installed
    if (!block_instance('gradetracker')) {
        return false;
    }

    $add = optional_param('add', false, PARAM_TEXT);
    $cmID = optional_param('update', false, PARAM_INT);
    $ModuleLink = false;
    if ($add) {
        $ModuleLink = \block_gradetracker\ModuleLink::getByModName($add);
    }

    // Bring javascript in
    $PAGE->requires->jquery();
    $PAGE->requires->js( '/blocks/gradetracker/js/mod.js', true );
    $PAGE->requires->js_init_call("gt_mod_hook_bindings", null, true);

    $GT = new \block_gradetracker\GradeTracker();
    $Course = new \block_gradetracker\Course($COURSE->id);
    $activity = $Course->getActivity($cmID);
    $quals = $Course->getCourseQualifications(true);

    // Create the box
    $mform->addElement('header', $GT->getPluginTitle(), $GT->getPluginTitle());

    $output = "";

    // If this ModuleLink has Parts, but this is a NEW activity not yet saved, then we can't link it up yet
    if ($ModuleLink && $ModuleLink->hasParts() && !$cmID) {

        $output .= \gt_info_alert_box( get_string('activitylinkaftersave', 'block_gradetracker') );

    } else {

        $output .= "<br>";

        if ($quals) {

            $unitArray = array();
            foreach ($quals as $qual) {

                // If the qual doesn't have any units, don't do anything
                if ($qual->isLevelEnabled("Units") && $qual->getUnits()) {

                    $unitsLinked = \block_gradetracker\Activity::getUnitsLinkedToCourseModule($cmID, $qual->getID());

                    $output .= "<b>{$qual->getDisplayName()}</b><br>";
                    $output .= "<select id='gt_mod_hook_{$qual->getID()}_units_select' class='gt_mod_hook_units' qualID='{$qual->getID()}'>";
                    $output .= "<option value=''></option>";
                    if ($qual->getUnits()) {
                        foreach ($qual->getUnits() as $unit) {
                            $disabled = (in_array($unit->getID(), $unitsLinked)) ? 'disabled' : '';
                            $output .= "<option value='{$unit->getID()}' {$disabled} >{$unit->getDisplayName()}</option>";
                        }
                    }
                    $output .= "</select> ";

                    $output .= "<span id='gt_mod_hook_loader_{$qual->getID()}' class='gt_hidden'><img src='".gt_image_url('i/loading_small')."' alt='loading' /></span>";
                    $output .= "<br><br>";

                    $output .= "<div id='gt_mod_hook_qual_units_{$qual->getID()}'>";

                    // Ones that are already linked
                    if ($unitsLinked) {
                        foreach ($unitsLinked as $unitID) {
                            $unit = new \block_gradetracker\Unit($unitID);
                            $criteria = $unit->sortCriteria(false, true);

                            $output .= "<div id='gt_hooked_unit_{$qual->getID()}_{$unit->getID()}' class='gt_hooked_unit'>";
                            $output .= "{$unit->getDisplayName()} <a href='#' class='gt_mod_hook_delete_unit' qualID='{$qual->getID()}' unitID='{$unit->getID()}'><img src='{gt_image_url('t/delete')}' /></a><br>";

                            $output .= "<table class='gt_c gt_hook_unit_criteria'>";
                            $output .= "<tr>";
                            if ($criteria) {
                                foreach ($criteria as $criterion) {
                                    $output .= "<th>{$criterion->getName()}</th>";
                                }
                            }
                            $output .= "</tr>";

                            $output .= "<tr>";
                            if ($criteria) {
                                foreach ($criteria as $criterion) {

                                    if ($activity->getRecordParts()) {
                                        $output .= "<td><select name='gt_criteria[{$qual->getID()}][{$unit->getID()}][{$criterion->getID()}]'>";
                                        $output .= "<option value='0'></option>";
                                        foreach ($activity->getRecordParts() as $part) {
                                            $sel = (\block_gradetracker\Activity::checkExists($cmID, $qual->getID(), $unit->getID(), $criterion->getID(), $part->id)) ? 'selected' : '';
                                            $output .= "<option value='{$part->id}' {$sel}>{$part->name}</option>";
                                        }
                                        $output .= "</select></td>";
                                    } else {
                                        $chk = (\block_gradetracker\Activity::checkExists($cmID, $qual->getID(), $unit->getID(), $criterion->getID())) ? 'checked' : '';
                                        $output .= "<td><input type='checkbox' name='gt_criteria[{$qual->getID()}][{$unit->getID()}][{$criterion->getID()}]' {$chk} /></td>";
                                    }
                                }
                            }
                            $output .= "</tr>";
                            $output .= "</table>";
                            $output .= "</div>";
                        }
                    }

                    $output .= "</div>";

                    $output .= "<br><br>";

                }

            }

            // If course module passed through, put id in hidden field
            if ($cm && $cm->id > 0) {
                $output .= "<input type='hidden' id='gt_cid' value='{$cm->course}' />";
                $output .= "<input type='hidden' id='gt_cmid' value='{$cm->id}' />";
            }

        } else {
            $output .= get_string('coursenoquals', 'block_gradetracker');
        }

    }

    $mform->addElement('html', $output);

}

/**
 * Process the GT parts of the module add/edit form
 * @param type $mod
 * @param type $course
 */
function gt_mod_hook_process($mod, $course) {

    $modID = (is_object($mod)) ? $mod->id : $mod;

    $linkedCriteria = df_optional_param_array_recursive('gt_criteria', false, PARAM_TEXT);
    $criteriaArray = array();

    // If there are criteria we want to link, process them
    if ($linkedCriteria) {

        foreach ($linkedCriteria as $qualID => $units) {

            foreach ($units as $unitID => $criteria) {

                foreach ($criteria as $critID => $value) {

                    // If the value is greater than 0, try and create a link to it
                    if ( (is_numeric($value) && $value > 0) || !is_numeric($value) ) {

                        $activity = new \block_gradetracker\Activity();
                        $activity->setCourseModuleID($modID);
                        $activity->setQualID($qualID);
                        $activity->setUnitID($unitID);
                        $activity->setCritID($critID);

                        // If the value is an int > 0, then it must be a partID
                        if (is_numeric($value) && $value > 0) {
                            $activity->setPartID($value);
                        }

                        $activity->create();
                        $criteriaArray[$qualID][] = $critID;

                    }

                }

                // ------------ Logging Info
                $Log = new \block_gradetracker\Log();
                $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
                $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_COURSE_ACTIVITY_LINKS;
                $Log->afterjson = array($modID => $criteria);
                $Log->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_QUALID, $qualID)
                    ->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_UNITID, $unitID);
                $Log->save();
                // ------------ Logging Info

            }

        }

    }

    // Now remove any that are currently linked to this course module that were not submitted in the form
    \block_gradetracker\Activity::removeNonSubmittedLinks($modID, $criteriaArray);

}