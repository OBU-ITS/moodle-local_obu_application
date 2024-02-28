<?php

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
 * OBU Application - Export individual statement from application
 *
 * @package    obu_application
 * @category   local
 * @author     Emir Kamel
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once($CFG->libdir . '/moodlelib.php');

$home = new moodle_url('/');

$url = $home . 'local/obu_application/mdl_statement.php?id=' . $application->id;

$source = '';
if (isset($_REQUEST['source'])) {
    $source = $_REQUEST['source'];
}
if ($source) {
    $back = urldecode($source);
}

$id = '';
if (isset($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
}
if ($id){
    $application = read_application($_REQUEST['id']);
}

header('Content-Type: text/plain');
header('Content-Disposition: attachment;filename=' . get_string('statement_file', 'local_obu_application') . '_' . 'HLS/' . $application->id . '_' . date("Ymd", $mform_data->application_date) . '.txt');
$fp = fopen('php://output', 'w');
file_put_contents($fp, $application->statement);
fclose($fp);

var_dump($application->statement);
die();

redirect($back);