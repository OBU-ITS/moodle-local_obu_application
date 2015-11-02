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
 * OBU Application - Profile page
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require('../../config.php');
require_once('./locallib.php');

require_obu_login();

$PAGE->set_title($CFG->pageheading . ': ' . get_string('profile', 'local_obu_application');

// HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$PAGE->set_url('/local/obu_application/profile.php');
$PAGE->set_context(context_system::instance());

$parameters = [
	'formref' => $formref,
	'record' => $record
];

include('./profile_form.php');
$mform = new profile_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect('/local/obu_application/');
} else if ($profile = $mform->get_data()) {
    $user->confirmed = 0;
    $user->lang = current_language();
    $user->firstaccess = time();
    $user->timecreated = time();
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->secret = random_string(15);
    $user->auth = 'email';
	
    application_user_signup($user); // prints notice and link to 'local/obu_application/index.php'
    exit; //never reached
}

echo $OUTPUT->header();
inject_css();
$mform->display();
echo $OUTPUT->footer();
