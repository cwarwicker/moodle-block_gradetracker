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
 * GT Exception class
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

class GTException extends \Exception {

    protected $context; # This is not a Moodle context, it's an english word/phrase to define the context in which the error occured, e.g "Plugin"
    protected $expected; # This is used to print out what was expected to occur/be passed in, which would not have led to the exception
    protected $recommended; # The recommended response to seeing this exception. E.g. "Programming error - contact site developer." Or something along those lines.

    /**
     *
     * @param type $context
     * @param type $message
     * @param type $expected
     * @param type $recommended
     */
    public function __construct($context, $message, $expected = null, $recommended = null) {
        $this->context = $context;
        $this->expected = $expected;
        $this->recommended = $recommended;
        parent::__construct($message);
    }

    public function getContext() {
        return $this->context;
    }

    public function getExpected() {
        return $this->expected;
    }

    public function getRecommended() {
        return $this->recommended;
    }


    /**
     * Get the full exception message in the format we want
     * @return string
     */
    public function getException() {

        global $CFG;

        $output = "";
        $output .= "<div class='gt_err_box'>";
        $output .= "<h1>" . get_string('gtexception', 'block_gradetracker') . "</h1>";
        $output .= "<h2>" . $this->getContext() . "</h2><br>";
        $output .= "<em>".$this->getMessage()."</em><br>";

        if (!is_null($this->getExpected())) {
            $output .= "<br>";
            $output .= "<strong>".get_string('expected', 'block_gradetracker') . "</strong> - " . $this->getExpected();
        }

        if (!is_null($this->getRecommended())) {
            $output .= "<br>";
            $output .= "<strong>".get_string('recommended', 'block_gradetracker')."</strong> - " . $this->getRecommended();
        }

        $output .= "<br><br>";

        // If in max debug mode, show backtrace
        if ($CFG->debug >= 32767) {
            $debugtrace = debug_backtrace();
            if ($debugtrace) {
                foreach ($debugtrace as $trace) {
                    $file = (isset($trace['file'])) ? $trace['file'] : '?';
                    $line = (isset($trace['line'])) ? $trace['line'] : '?';
                    $output .= "<div class='notifytiny' style='text-align:center !important;'>{$file}:{$line}</div>";
                }
            }
        }

        $output .= "</div>";
        return $output;
    }

}