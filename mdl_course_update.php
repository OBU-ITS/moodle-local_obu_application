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
 * OBU Application - Course Update [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('./mdl_course_update_form.php');

require_login();

$home = new moodle_url('/');
if (!is_siteadmin()) {
	redirect($home);
}

$url = new moodle_url('/local/obu_application/mdl_course_update.php');
$heading = get_string('course_update', 'local_obu_application');

$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_heading($heading);
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

$mform = new mdl_course_update_form();

if ($mform->is_cancelled()) {
    redirect($home);
}

echo $OUTPUT->header();

if ($form_data = $mform->get_data()) {
	$importid = csv_import_reader::get_new_iid('course_update_fields');
	$cir = new csv_import_reader($importid, 'course_update_fields');
	$content = $mform->get_file_content('course_update_file');
	$readcount = $cir->load_csv_content($content, 'UTF-8', 'comma');
	unset($content);
	if ($readcount === false) {
		print_error('csvfileerror', 'local_obu_application', $url, $cir->get_error());
	} else if ($readcount == 0) {
		print_error('csvemptyfile', 'error', $url, $cir->get_error());
	}

	// Loop over the CSV lines
	$columns = $cir->get_columns();
	$cir->init();
	while ($fields = $cir->next()) {
		$course = $DB->get_record('local_obu_course', array('code' => $fields[0]));
		if ($course !== false) {
			for ($column = 1; $column < count($columns); $column++) {
				$course->{$columns[$column]} = $fields[$column];
			}
			$DB->update_record('local_obu_course', $course);
		}
	}

    $cir->cleanup(true);
    redirect($url);
} else {
	$mform->display();
}

echo $OUTPUT->footer();
