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
 * OBU Application - Hide Moodle
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
// Set our login cookie suffix (too late for the session cookie)
$CFG->sessioncookie = 'email';

// Add our own CSS - mainly to hide the standard Moodle page elements
$CFG->additionalhtmlhead .= '<style>.langmenu, .usermenu, .logininfo, .homelink, .breadcrumb, .helplink { display: none; } .nav { color: white; } a.brand { pointer-events: none; }</style>';

// Add our own menu items for logged-in users
if (!isloggedin()) {
	$PAGE->set_context(context_system::instance());
} else {
	$PAGE->set_context(context_user::instance($USER->id));
	$CFG->custommenuitems = fullname($USER, true) . '
	#####
	' . get_string('index_page', 'local_obu_application') . '|/local/obu_application/index.php
	#####
	' . get_string('contactdetails', 'local_obu_application') . '|/local/obu_application/contact.php
	#####
	' . get_string('profile', 'local_obu_application') . '|/local/obu_application/profile.php
	#####
	' . get_string('apply', 'local_obu_application') . '|/local/obu_application/course.php
	#####
	' . get_string('logout', 'local_obu_application') . '|/local/obu_application/logout.php?loginpage=1';
}

// Set our own page heading (non-standard $CFG variable)
$CFG->pageheading = get_string('plugintitle', 'local_obu_application');
$PAGE->set_headingmenu('<h1>' . $CFG->pageheading . '</h1>');
$PAGE->set_heading($CFG->pageheading);

// Add our own footer elements
$CFG->additionalhtmlfooter .= '<center>©' . date('Y') . ', Oxford Brookes University</center>';

?>