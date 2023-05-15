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
 * OBU Application - Amend the Supplementary document in an application [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Emir Kamel
 * @copyright  2023, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./supplement_form.php');
require_once($CFG->libdir . '/moodlelib.php');

require_login();

$home = new moodle_url('/');
$context = context_user::instance($USER->id);

if (!is_manager()) {
    redirect($home);
}

$applications_course = get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;

if (!has_capability('local/obu_application:update', context_system::instance())) {
    redirect($back);
}

// We only allow access to an existing application (id given)
if (!isset($_REQUEST['id'])) {
    redirect($back);
}

$application = read_application($_REQUEST['id']);
if ($application === false) {
    redirect($back);
}

$url = $home . 'local/obu_application/mdl_amend_supplement.php?id=' . $application->id;
$process = $home . 'local/obu_application/mdl_process.php?id=' . $application->id;

if ((($application->approval_level != 1) && ($application->approval_level != 3)) || ($application->approval_state != 0)) { // Must be awaiting approval/rejection by HLS staff
    redirect($process);
}

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('application', 'local_obu_application', $application->id);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($url);
$PAGE->navbar->add($heading);

$message = '';

if ($message == '') {
    unpack_supplement_data($application->supplement_data, $fields);
    $supplement = get_supplement_form_by_version($fields['supplement'], $fields['version']);
    if (!$supplement) {
        $message = get_string('invalid_data', 'local_obu_application');
    }
}

$parameters = [
    'supplement' => $supplement,
    'fields' => $fields
];

$mform = new supplement_form(null, $parameters, $application->id);

if ($mform->is_cancelled()) {
    redirect($process);
}

if ($mform_data = (array)$mform->get_data()) {
    $files = get_file_elements($supplement->template); // Get the list of the 'file' elements from the supplementary form's template
    $data_fields = array();
    foreach ($mform_data as $key => $value) {
        if ($key != 'submitbutton') { // Ignore the standard field
            if (in_array($key, $files)) { // Is this element a 'file' one?
                $file = $mform->save_stored_file($key, $context->id, 'local_obu_application', 'file', $value, '/', null, true, null); // Save it to the Moodle pool
                if ($file !== false) {
                    $data_fields[$key] = $file->get_pathnamehash(); // Store the file's pathname hash (it's unique identifier)
                }
            } else {
                $data_fields[$key] = $value;
            }
        }
    }
    write_supplement_data($USER->id, pack_supplement_data($data_fields));
    redirect($process);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course_supplement', 'local_obu_application'));

if ($message) {
    notice($message, $home);
}
else {
    $mform->display();
}

echo $OUTPUT->footer();