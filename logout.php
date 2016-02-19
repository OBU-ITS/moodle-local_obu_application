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
 * OBU Application - Logout page
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham (derived from '/login/index.php')
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');

$PAGE->set_url('/local/obu_application/logout.php');
$PAGE->set_context(context_system::instance());

$login = optional_param('loginpage', 0, PARAM_BOOL);

if (isloggedin()) {
	$authsequence = get_enabled_auth_plugins(); // auths, in sequence
	foreach($authsequence as $authname) {
		$authplugin = get_auth_plugin($authname);
		$authplugin->logoutpage_hook();
	}
}

require_logout();

if ($login) {
    redirect($CFG->wwwroot . '/local/obu_application/login.php');
}	

$PAGE->set_title($CFG->pageheading . ': Logout');
$CFG->custommenuitems = ''; // Clear the menu
echo $OUTPUT->header();
echo get_string('logout_message', 'local_obu_application');
echo $OUTPUT->footer();
