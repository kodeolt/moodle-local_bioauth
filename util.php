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
 * Library of functions used by the bioauth module.
 *
 * This contains functions that are called from within the bioauth module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Load a csv file with the first element in each row as the key, skipping
 * empty lines.
 *
 * @param string $filename the name of the csv file to load
 * @return array a 2-dimensional array keyed by the first element in each row
 *
 */
function load_csv($filename) {
    $data = array();
    if (($handle = fopen($filename, "r")) !== false) {
        while (($csvdata = fgetcsv($handle, 1000, ",")) !== false) {
            if (count($csvdata[0]) > 0) {
                $data[$csvdata[0]] = array_slice($csvdata, 1);
            }
        }
        fclose($handle);
    }

    return $data;
}

/**
 * DefaultArray provides functionality similar to Python's defaultdict.
 * A default value is initialized and inserted into the array for any key that
 * does not already exist.
 *
 */
class DefaultArray extends ArrayObject {

    /**
     * @var the default value in the array
     */
    protected $_default_value;

    public function __construct($value = null) {
        $this->_default_value = $value;
    }

    public function offsetExists($index) {
        return true;
    }

    public function offsetGet($index) {
        if (!parent::offsetExists($index)) {
            if (is_object($this->_default_value)) {
                parent::offsetSet($index, clone $this->_default_value);
            } else {
                parent::offsetSet($index, $this->_default_value);
            }
        }

        return parent::offsetGet($index);
    }

}
