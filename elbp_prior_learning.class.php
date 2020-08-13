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
 * Prior Learning ELBP plugin class
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace ELBP\Plugins;

defined('MOODLE_INTERNAL') or die();

require_once('lib.php');

class elbp_prior_learning extends Plugin {

    /**
     * Construct the plugin object
     * @param bool $install If true, we want to send the default info to the parent constructor, to install the record into the DB
     */
    public function __construct($install = false) {

        if ($install) {
            parent::__construct( array(
                "name" => strip_namespace(get_class($this)),
                "title" => "Prior Learning",
                "path" => '/blocks/gradetracker/',
                "version" => \ELBP\ELBP::getBlockVersionStatic()
            ) );
        } else {
            parent::__construct( strip_namespace(get_class($this)) );
        }

    }


    public function ajax($action, $params, $ELBP) {

    }

    public function getSummaryBox() {

        $TPL = new \ELBP\Template();

        $TPL->set("obj", $this);

        $user = new \block_gradetracker\User($this->student->id);

        $TPL->set("prior", $user->getQualsOnEntry());
        $TPL->set("avgScore", $user->getAverageGCSEScore());

        try {
            return $TPL->load($this->CFG->dirroot . $this->path . 'tpl/elbp_prior_learning/summary.html');
        } catch (\ELBP\ELBPException $e) {
            return $e->getException();
        }

    }


    public function getDisplay($params = array()) {

        $output = "";

        $TPL = new \ELBP\Template();

        $user = new \block_gradetracker\User($this->student->id);

        $TPL->set("prior", $user->getQualsOnEntry());
        $TPL->set("avgScore", $user->getAverageGCSEScore());
        $TPL->set("obj", $this);
        $TPL->set("access", $this->access);

        try {
            $output .= $TPL->load($this->CFG->dirroot . $this->path . 'tpl/elbp_prior_learning/expanded.html');
        } catch (\ELBP\ELBPException $e) {
            $output .= $e->getException();
        }

        return $output;

    }


    public function getConfigPath() {
        $path = $this->getPath() . 'config_'.$this->getName().'.php';
        return $path;
    }


    public function install() {

        global $DB;

        $return = true;
        $this->id = $this->createPlugin();

        // Hooks
        $DB->insert_record("lbp_hooks", array("pluginid" => $this->id, "name" => "English GCSE"));
        $DB->insert_record("lbp_hooks", array("pluginid" => $this->id, "name" => "Maths GCSE"));

        return $return;
    }

    public function upgrade() {

        global $DB;

        $return = true;

        return $return;

    }


    public function _callHook_English_GCSE($obj, $params) {

        if (!$this->isEnabled()) {
            return false;
        }
        if (!isset($obj->student->id)) {
            return false;
        }

        // Load student
        $this->loadStudent($obj->student->id);

        $user = new \block_gradetracker\User($this->student->id);

        $prior = $user->getQualsOnEntry();

        if ($prior) {
            foreach ($prior as $qual) {
                if ($qual->getType()->name == 'GCSE' && ($qual->getSubjectName($qual->getSubjectID()) == 'English' || $qual->getSubjectName($qual->getSubjectID()) == 'English Language')) {
                    return $qual->getGradeObject()->grade;
                }

            }
        }

        return get_string('na', 'block_gradetracker');

    }

    public function _callHook_Maths_GCSE($obj, $params) {
        if (!$this->isEnabled()) {
            return false;
        }
        if (!isset($obj->student->id)) {
            return false;
        }

        // Load student
        $this->loadStudent($obj->student->id);

        $user = new \block_gradetracker\User($this->student->id);

        $prior = $user->getQualsOnEntry();

        if ($prior) {
            foreach ($prior as $qual) {

                if ($qual->getType()->name == 'GCSE' && ($qual->getSubjectName($qual->getSubjectID()) == 'Mathematics' || $qual->getSubjectName($qual->getSubjectID()) == 'Maths')) {
                    return $qual->getGradeObject()->grade;
                }

            }
        }

        return get_string('na', 'block_gradetracker');

    }
}

