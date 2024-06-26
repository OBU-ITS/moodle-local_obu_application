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
 * OBU Application - Reject application
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
require_once('./mdl_reject_form.php');
require_once($CFG->libdir . '/moodlelib.php');

$home = new moodle_url('/');
if (!local_obu_application_is_manager()) {
    redirect($home);
}

$applications_course = local_obu_application_get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;
if (!local_obu_application_is_manager()) {
    redirect($back);
}

$url = $home . 'local/obu_application/mdl_reject.php?id=' . $applications_course->id;

$source = $_REQUEST['source'] ?? null;
if ($source) {
    $back = urldecode($source);
}

$id = $_REQUEST['id'] ?? null;
$application = $id ? local_obu_application_read_application($_REQUEST['id']) : null;
if(!$application) {
    redirect($back);
}

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('application_ref', 'local_obu_application', $application->id);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);
$message = '';

$parameters = [
    'source' => $source,
    'record' => $application
];

$mform = new mdl_reject_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
}

if ($mform_data = $mform->get_data()) {
    local_obu_application_update_workflow($application, false, $mform_data); // Rejected

    redirect($back);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $home);
}
else {
    $mform->display();
}

echo $OUTPUT->footer();