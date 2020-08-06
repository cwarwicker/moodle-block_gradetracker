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
 * The class is used to generate and read excel spreadsheets
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */
namespace GT;

use core_useragent;
use MoodleExcelFormat;
use MoodleExcelWorkbook;
use MoodleExcelWorksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot . '/lib/excellib.class.php');

class Excel extends MoodleExcelWorkbook {

    /**
     * Get the PHPSpreadsheet object from the Moodle class
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet|\PhpSpreadsheet
     */
    public function getSpreadsheet() {
        return $this->objspreadsheet;
    }

    /**
     * Overwrite the parent add_worksheet method to add an ExcelSheet instead of MoodleExcelWorksheet
     * @param string $name
     * @return ExcelSheet|MoodleExcelWorksheet
     */
    public function addWorksheet($name = '') {
        return new ExcelSheet($name, $this->objspreadsheet);
    }

    /**
     * Save the spreadsheet into a file instead of just displaying it for download
     * @param $file
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function save($file) {

        foreach ($this->objspreadsheet->getAllSheets() as $sheet) {
            $sheet->setSelectedCells('A1');
        }

        $this->objspreadsheet->setActiveSheetIndex(0);

        $objwriter = IOFactory::createWriter($this->objspreadsheet, $this->type);
        $objwriter->save($file);

    }

    /**
     * Serve the generated file to the web browser.
     * @return void
     */
    public function serve() {

        $mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $filename = preg_replace('/\.xlsx?$/i', '', $this->filename);
        $filename = $filename.'.xlsx';

        if (core_useragent::is_ie() || core_useragent::is_edge()) {
            $filename = rawurlencode($filename);
        } else {
            $filename = s($filename);
        }

        header('Content-Type: '.$mimetype);
        header('Content-Disposition: attachment;filename="'.$filename.'"');

    }

}

class ExcelSheet extends MoodleExcelWorksheet {

    /**
     * get the PHPSpreadsheet Worksheet object from the Moodle class
     * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    public function getWorksheet() {
        return $this->worksheet;
    }

    /**
     * The MoodleExcelWorksheet increments the specified column number by 1 unnecessarily, so we cannot use that method as is.
     * @param integer $row    Zero indexed row
     * @param integer $col    Zero indexed column
     * @param string  $str    The string to write
     * @param mixed   $format The XF format for the cell
     */
    public function writeString($row, $col, $str, $format = null) {

        // Because the Moodle version is going to increment the column numbers (for some reason...) we have to decrement them first.
        $col -= 1;
        parent::write_string($row, $col, $str, $format);

    }

    /**
     * The MoodleExcelWorksheet increments the specified column number by 1 unnecessarily, so we cannot use that method as is.
     * @param int $firstrow
     * @param int $firstcol
     * @param int $lastrow
     * @param int $lastcol
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function mergeCells($firstrow, $firstcol, $lastrow, $lastcol) {

        // Because the Moodle version is going to increment the column numbers (for some reason...) we have to decrement them first.
        $firstcol -= 1;
        $lastcol -= 1;
        parent::merge_cells($firstrow, $firstcol, $lastrow, $lastcol);

    }

    /**
     * We can't call apply_format from a script, as it is protected. So this method lets you call it from a public context.
     * @param $row
     * @param $col
     * @param $format
     */
    public function applyFormat($row, $col, $format) {
        parent::apply_format($row, $col, $format);
    }

    /**
     * Apply format to a range of cells, e.g. A6:B9
     * @param $firstcol
     * @param $firstrow
     * @param $lastcol
     * @param $lastrow
     * @param null $format
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function applyRangeFormat($firstcol, $firstrow, $lastcol = null, $lastrow = null, $format = null) {

        if (!$format) {
            $format = new MoodleExcelFormat();
        } else if (is_array($format)) {
            $format = new MoodleExcelFormat($format);
        }

        $firstrow += 1;

        if (!is_null($lastrow)) {
            $lastrow += 1;
        }

        $this->worksheet->getStyleByColumnAndRow($firstcol, $firstrow, $lastcol, $lastrow)->applyFromArray($format->get_format_array());

    }

}