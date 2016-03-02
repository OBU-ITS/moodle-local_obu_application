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
 * OBU Application - Confirm self registered user
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham (derived from '/login/confirm.php')
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');

$data = optional_param('data', '', PARAM_RAW);  // Formatted as secret/username

$PAGE->set_url('/local/obu_application/confirm.php');

if (!isset($data) || empty($data)) {
	display_message(get_string('error'), get_string('errorwhenconfirming'));
}

$dataelements = explode('/', $data, 2); // Stop after 1st slash. Rest is username. MDL-7647
$usersecret = $dataelements[0];
$username = $dataelements[1];

$confirmed = application_user_confirm($username, $usersecret);

if ($confirmed == AUTH_CONFIRM_ALREADY) {
	if (!$user = get_complete_user_data('username', $username)) {
		display_message(get_string('error'), get_string('cannotfinduser') . ' ' . $username);
	}
	display_message(get_string('thanks') . ', ' . fullname($user), get_string('alreadyconfirmed'));
} else if ($confirmed == AUTH_CONFIRM_OK) { // The user has confirmed successfully, let's log them in
	if (!$user = get_complete_user_data('username', $username)) {
		display_message(get_string('error'), get_string('cannotfinduser') . ' ' . $username);
	}
	complete_user_login($user);

	if (!empty($SESSION->wantsurl)) { // Send them where they were going
		$goto = $SESSION->wantsurl;
		unset($SESSION->wantsurl);
		redirect($goto);
	}

	display_message(get_string('thanks') . ', ' . fullname($user), get_string('confirmed'));
} else {
	display_message(get_string('error'), get_string('invalidconfirmdata'));
}

redirect($CFG->wwwroot . '/local/obu_application/');
