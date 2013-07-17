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
 * This file renders the quiz overview graph.
 *
 * @package   quiz_overview
 * @copyright 2008 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/bioauth/locallib.php');
require_once($CFG->dirroot . '/local/bioauth/HighRoller/HighRoller.php');
require_once($CFG->dirroot . '/local/bioauth/HighRoller/HighRollerSeriesData.php');
require_once($CFG->dirroot . '/local/bioauth/HighRoller/HighRollerLineChart.php');


$id = required_param('id', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}
$coursecontext = context_course::instance($id);
require_login($course);

//$validation = get_validation_from_instance('bioauth_quiz_validations', $course);

// Print the header.
$strbioauth = get_string('pluginname', 'local_bioauth');

$chartData = array(5324, 7534, 6234, 7234, 8251, 10324);

$linechart = new HighRollerLineChart();
$linechart->chart->renderTo = 'linechart';
$linechart->title->text = 'Line Chart';
$linechart->chart->width = 300;
$linechart->chart->height = 300;

$series1 = new HighRollerSeriesData();
$series1->addName('myData')->addData($chartData);

$linechart->addSeries($series1);

echo '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>';
echo "<script type='text/javascript' src='highcharts/highcharts.js'></script>";
echo '<div id="linechart"></div><script type="text/javascript">'.$linechart->renderChart().'</script>';

