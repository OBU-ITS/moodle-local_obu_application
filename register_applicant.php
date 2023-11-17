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
 * @author     Joe Souch (derived from '/login/signup.php')
 * @copyright  2023, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');
require_once($CFG->dirroot . '/user/editlib.php');

$home = new moodle_url('/local/obu_application/');
$url = $home . 'register_applicant.php';
$login = $home . 'login.php';

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->add_body_class("hls-cpd");
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

// If wantsurl is empty or /login/signup.php, override wanted URL.
// We do not want to end up here again if user clicks "Login".
if (empty($SESSION->wantsurl)) {
    $SESSION->wantsurl = $CFG->wwwroot . '/';
} else {
    $wantsurl = new moodle_url($SESSION->wantsurl);
    if ($PAGE->url->compare($wantsurl, URL_MATCH_BASE)) {
        $SESSION->wantsurl = $CFG->wwwroot . '/';
    }
}

if (isloggedin() and !isguestuser()) {
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

$parameters = [
    'titles' => get_titles(),
    'show_email_notification' => true,
    'email_label' => get_string('personalemail', 'local_obu_application')
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
        $namefields = array_diff(\core_user\fields::get_name_fields(), useredit_get_required_name_fields());
        foreach ($namefields as $namefield) {
            $user->$namefield = '';
        }

        application_user_signup($user); // prints notice and link to 'local/obu_application/index.php'
        exit; //never reached
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading("Registration", 1, "mb-4");


if ($message) {
    notice($message, $login);
}
else {
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', "Fill out your details below");
    echo html_writer::tag('li', "An email will be sent to the email address provided below. Please check spam if it does not appear.");
    echo html_writer::tag('li', "Click on the web link contained in the email, and follow the instructions.");
//    echo html_writer::tag('li', get_string('passwordforgotteninstructions', 'local_obu_application'));
    echo html_writer::end_tag('ol');
    $mform->display();
}

echo $OUTPUT->footer();