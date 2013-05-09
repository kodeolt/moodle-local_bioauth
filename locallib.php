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

require_once('/local/bioauth/lib.php');

/**
 * Subtract and square two numbers (Used in euclidean_distance)
 *
 * @param array $n
 * @param array $n
 * @return (n - m)**2 
 */
function subtract_and_square($n, $m)
{
  return(pow($n - $m, 2));
}

/**
 * Compute the Euclidean distance between two vectors of arbitrary size.
 *
 * @param array $p
 * @param array $q
 * @return the Euclidean distance between $p and $q 
 */
function euclidean_distance($p, $q)
{
  $numargs = func_num_args();
  if ($numargs != 2)
  {
    die("You must supply 2, and only 2, coordinates, no more, no less.\n");
  }
  else if (sizeof($p) != sizeof($q))
  {
    die("Coordinates do not have the same number of elements.\n");
  }
  else
  {
    $c = array_map("subtract_and_square", $p, $q);
    return pow(array_sum($c), .5);
  }
}
