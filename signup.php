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
 * OBU Application - User registration (signup) page
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham (derived from '/login/signup.php')
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');
require_once($CFG->dirroot . '/user/editlib.php');

// Try to prevent searching for sites that allow sign-up.
if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .= '<meta name="robots" content="noindex" />';

$home = new moodle_url('/local/obu_application/');
$url = $home . 'signup.php';
$login = $home . 'login.php';

$PAGE->set_url($url);

// Override wanted URL, we do not want to end up here again if user clicks "Login"
$SESSION->wantsurl = $home;

// Prevent registering when already logged in
if (isloggedin()) {
    echo $OUTPUT->header();
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url($CFG->httpswwwroot . '/local/obu_application/logout.php',
        array('sesskey' => sesskey(), 'loginpage' => 1)), get_string('logout'), 'post');
    $continue = new single_button($home, get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('cannotsignup', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

include('./signup_form.php');
$counties = get_counties();
$parameters = [
	'counties' => $counties
];

$mform = new registration_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($login);
} else if ($user = $mform->get_data()) {
	if (strpos($user->email, '@brookes.ac.uk') !== false) {
		$message = get_string('preregistered', 'local_obu_application');
	} else {
		$message = '';
		$user->confirmed = 0;
		$user->lang = current_language();
		$user->firstaccess = time();
		$user->timecreated = time();
		$user->mnethostid = $CFG->mnet_localhost_id;
		$user->secret = random_string(15);
		$user->auth = 'email';
	
		// Initialize alternate name fields to empty strings.
		$namefields = array_diff(get_all_user_name_fields(), useredit_get_required_name_fields());
		foreach ($namefields as $namefield) {
			$user->$namefield = '';
		}
	
		application_user_signup($user); // prints notice and link to 'local/obu_application/index.php'
		exit; //never reached
	}
}

$PAGE->set_title($CFG->pageheading . ': ' . get_string('registration', 'local_obu_application'));

echo $OUTPUT->header();

if ($message) {
    notice($message, $login);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
