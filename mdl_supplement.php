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
 * OBU Application - Add or amend a supplementary supplement template [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./db_update.php');
require_once('./mdl_supplement_form.php');

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$applications_course = get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;
if (!is_administrator()) {
	redirect($back);
}

if (!has_capability('local/obu_application:update', context_system::instance())) {
	redirect($back);
}

$url = $home . 'local/obu_application/mdl_supplement.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('supplements', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$ref = '';
$version = '';
$versions = array();
$record = null;
$is_published = 0;

if (isset($_REQUEST['ref'])) {
	$ref = strtoupper($_REQUEST['ref']);
	if (isset($_REQUEST['version'])) {
		$version = strtoupper($_REQUEST['version']);
        $record = read_supplement_form($ref, $version);
	} else {
		if (!isset($_REQUEST['versions']) || (isset($_REQUEST['versions']) && $_REQUEST['versions'] != 0)) {
			$supplements = read_supplement_forms($ref);
			if ($supplements) {
				$versions[0] = get_string('new_version', 'local_obu_application'); // The 'New Version' option
				foreach ($supplements as $supplement) {
					$versions[] = $supplement->version;
				}
				if (isset($_REQUEST['versions'])) {
					$version = $versions[$_REQUEST['versions']];
					$record = read_supplement_form($ref, $version);
				}
			}
		}
	}
}

$parameters = [
	'ref' => $ref,
	'version' => $version,
	'versions' => $versions,
	'record' => $record
];

$msupplement = new mdl_supplement_form(null, $parameters);

if ($msupplement->is_cancelled()) {
	if ($ref == '') {
		redirect($back);
	} else {
		redirect($url);
	}
} 
else if ($msupplement_data = $msupplement->get_data()) {
	if ($msupplement_data->submitbutton == get_string('save', 'local_obu_application')) {
		if (!$msupplement_data->already_published || is_siteadmin()) {
			write_supplement_form($USER->id, $msupplement_data);
		}
		redirect($url);
    } else if ($msupplement_data->submitbutton == get_string('reinstate', 'local_obu_application')) {
        if ($msupplement_data->already_published || is_siteadmin()) {
            redirect(reinstate_supplement_form($USER->id, $msupplement_data));
        }
    }
}	

echo $OUTPUT->header();

if ($message) {
    notice($message, $url);    
}
else {
    $msupplement->display();
}

echo $OUTPUT->footer();
