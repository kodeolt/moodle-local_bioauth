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
 * 
 * Definitions of constants for bioauth
 * 
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Keystroke actions

define('BIOAUTH_ACTION_PRESS', 0);
define('BIOAUTH_ACTION_RELEASE', 1);

// Keystroke feature measures

define('BIOAUTH_MEASURE_MEAN', 0);
define('BIOAUTH_MEASURE_STDDEV', 1);

// Keystroke feature types

define('BIOAUTH_FEATURE_DURATION', 0);
define('BIOAUTH_FEATURE_T1', 1);
define('BIOAUTH_FEATURE_T2', 2);
define('BIOAUTH_FEATURE_T3', 3);
define('BIOAUTH_FEATURE_T4', 4);