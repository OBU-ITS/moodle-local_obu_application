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
 * OBU Application - Login page
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham (derived from '/login/index.php')
 * @copyright  2016 Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');

$testsession = optional_param('testsession', 0, PARAM_INT); // test session works properly
$cancel = optional_param('cancel', 0, PARAM_BOOL); // redirect to index page, needed for loginhttps

if ($cancel) {
    redirect(new moodle_url('/local/obu_application/'));
}

// HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$context = context_system::instance();
$PAGE->set_url($CFG->httpswwwroot . '/local/obu_application/login.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

// Initialize variables
$errormsg = '';
$errorcode = 0;

// Login page requested session test
if ($testsession) {
    if ($testsession == $USER->id) {
        if (isset($SESSION->wantsurl)) {
            $urltogo = $SESSION->wantsurl;
        } else {
            $urltogo = $CFG->wwwroot . '/local/obu_application/';
        }
        unset($SESSION->wantsurl);
        redirect($urltogo);
    } else {
        $errormsg = get_string('cookiesnotenabled');
        $errorcode = 1;
    }
}

// Check for timed out sessions
if (!empty($SESSION->has_timed_out)) {
    $session_has_timed_out = true;
    unset($SESSION->has_timed_out);
} else {
    $session_has_timed_out = false;
}

$frm  = false;
$user = false;

$frm = data_submitted();

// Check if the user has actually submitted login data to us

if ($frm and isset($frm->username)) { // Login WITH cookies

    $frm->username = trim(core_text::strtolower($frm->username));

    if (empty($errormsg)) {
            $user = authenticate_application_user($frm->username, $frm->password, false, $errorcode);
    }

    // Intercept 'restored' users to provide them with info & reset password
    if (!$user and $frm and is_restored_user($frm->username)) {
		$PAGE->set_title($CFG->pageheading . ': ' . get_string('restoredaccount'));
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('restoredaccount'));
        echo $OUTPUT->box(get_string('restoredaccountinfo'), 'generalbox boxaligncenter');
        require_once('restored_password_form.php');
        $form = new login_forgot_password_form('forgot_password.php', array('username' => $frm->username));
        $form->display();
        echo $OUTPUT->footer();
        die;
    }
	
	// Language setup
	if (!empty($user->lang)) { // Unset previous session language - use user preference instead
		unset($SESSION->lang);
	}

    if ($user) {
        if (empty($user->confirmed)) { // This account was never confirmed
			$PAGE->set_title($CFG->pageheading . ': ' . get_string('mustconfirm'));
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('mustconfirm'));
            echo $OUTPUT->box(get_string('emailconfirmsent', '', $user->email), 'generalbox boxaligncenter');
            echo $OUTPUT->footer();
            die;
        }

		// Let's get them all set up.
        complete_user_login($user);

        // Set the username cookie
        if (empty($CFG->nolastloggedin)) { // Store last logged in user in cookie
			if (empty($CFG->rememberusername) or ($CFG->rememberusername == 2 and empty($frm->rememberusername))) { // No permanent cookies, delete old one if exists
				set_moodle_cookie('');
			} else {
				set_moodle_cookie($USER->email);
			}
        }

        $urltogo = get_return_url();

        // Discard any errors before the last redirect.
        unset($SESSION->loginerrormsg);
		
        // test the session actually works by redirecting to self
        $SESSION->wantsurl = $urltogo;
        redirect(new moodle_url('/local/obu_application/login.php', array('testsession' => $USER->id)));
		
    } else {
        if (empty($errormsg)) {
            if ($errorcode == AUTH_LOGIN_UNAUTHORISED) {
                $errormsg = get_string('unauthorisedlogin', '', $frm->username);
            } else {
                $errormsg = get_string('invalidlogin');
                $errorcode = 3;
            }
        }
    }
}

// Detect problems with timedout sessions
if ($session_has_timed_out and !data_submitted()) {
    $errormsg = get_string('sessionerroruser', 'error');
    $errorcode = 4;
}

// First, let's remember where the user was trying to get to before they got here

if (empty($SESSION->wantsurl)) {
    $SESSION->wantsurl = (array_key_exists('HTTP_REFERER', $_SERVER)
		&& $_SERVER['HTTP_REFERER'] != $CFG->wwwroot
		&& $_SERVER['HTTP_REFERER'] != $CFG->wwwroot . '/'
		&& $_SERVER['HTTP_REFERER'] != $CFG->httpswwwroot . '/local/obu_application/'
		&& strpos($_SERVER['HTTP_REFERER'], $CFG->httpswwwroot . '/local/obu_application/?') !== 0
		&& strpos($_SERVER['HTTP_REFERER'], $CFG->httpswwwroot . '/local/obu_application/login.php') !== 0) // There might be some extra params such as ?lang=.
			? $_SERVER['HTTP_REFERER'] : NULL;
}

// Make sure we really are on the https page when https login required
$PAGE->verify_https_required();

// Generate the login page with forms

if (!isset($frm) or !is_object($frm)) {
    $frm = new stdClass();
}

if (empty($frm->username)) {
    if (!empty($_GET['username'])) {
        $frm->username = clean_param($_GET['username'], PARAM_RAW); // We do not want data from _POST here
    } else {
        $frm->username = get_moodle_cookie();
    }

    $frm->password = "";
}

if (!empty($frm->username)) {
    $focus = 'password';
} else {
    $focus = 'username';
}

if (!empty($SESSION->loginerrormsg)) { // We had some errors before redirect, show them now
    $errormsg = $SESSION->loginerrormsg;
    unset($SESSION->loginerrormsg);
} else if ($testsession) { // No need to redirect here
    unset($SESSION->loginerrormsg);
} else if ($errormsg or !empty($frm->password)) { // We must redirect after every password submission
    if ($errormsg) {
        $SESSION->loginerrormsg = $errormsg;
    }
    redirect(new moodle_url('/local/obu_application/login.php'));
}

$PAGE->set_title($CFG->pageheading . ': ' . get_string('login'));

echo $OUTPUT->header();

if (isloggedin()) { // Prevent login when already logged in, we do not want them to relogin by accident because sesskey would be changed
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url($CFG->httpswwwroot.'/local/obu_application/logout.php', array('sesskey' => sesskey(),'loginpage' => 1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url($CFG->httpswwwroot.'/local/obu_application/index.php', array('cancel' => 1)), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('alreadyloggedin', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
} else {
    include("login_form.php");
    if ($errormsg) {
        $PAGE->requires->js_init_call('M.util.focus_login_error', null, true);
    } else if (!empty($CFG->loginpageautofocus)) {
        //focus email or password
        $PAGE->requires->js_init_call('M.util.focus_login_form', null, true);
    }
}

echo $OUTPUT->footer();
