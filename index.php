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
 * OBU Application - Menu page
 *
 * @package    obu_application
 * @category   local
 * @author     Joe Souch
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');

// Try to prevent searching for sites that allow sign-up.
if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .= '<meta name="robots" content="noindex" />';

require_obu_login();

$process = new moodle_url('/local/obu_application/process.php');

$PAGE->add_body_class('limitedwidth');
$PAGE->set_url($CFG->httpswwwroot . '/local/obu_application/index.php');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

echo $OUTPUT->header();

$funder = is_funder();
$lang_ext = $funder ? '_funder' : '';

echo get_string('index_welcome_heading' . $lang_ext, 'local_obu_application', array('name' => $USER->firstname));
echo get_string('index_welcome_text' . $lang_ext, 'local_obu_application');
echo get_string('index_welcome_support' . $lang_ext, 'local_obu_application');

$manager = is_manager();
$process = new moodle_url('/local/obu_application/process.php');

echo get_string('index_overview_heading' . $lang_ext, 'local_obu_application');
if($funder) {
    $approvals = get_approvals($USER->email); // get outstanding approval requests
    if ($approvals) {
        foreach ($approvals as $approval) {
            $application = read_application($approval->application_id);
            $application_title = $application->firstname . ' ' . $application->lastname . ' (Application Ref HLS/' . $application->id . ')';
            echo '<h4><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h4>';
            echo get_application_status($USER->id, $application, $manager);
        }
    } else {
        echo get_string('index_overview_empty_funder', 'local_obu_application');
    }
}
else {
    $applications = get_applications($USER->id); // get all applications for the user
    if ($applications) {
        foreach ($applications as $application) {
            $text = get_application_status($USER->id, $application, $manager);
            $button = get_application_button_text($USER->id, $application, $manager);
            $application_title = $application->course_code . ' ' . $application->course_name . ' (Application Ref HLS/' . $application->id . ')';
            if (($button != 'submit') || $manager) {
                echo '<h4><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h4>';
            } else {
                echo '<h4>' . $application_title . '</h4>';
            }
            echo $text;
        }
    } else {
        echo get_string('index_overview_empty', 'local_obu_application');
    }
}

echo $OUTPUT->footer();
