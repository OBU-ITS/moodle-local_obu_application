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
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

// Set the name for our micro-site
$SITE->shortname = get_string('plugintitle', 'local_obu_application');

// Set our login cookie suffix (too late for the session cookie)
$CFG->sessioncookie = 'email';

$PAGE->add_body_class("hls-cpd");

// Add our own CSS - mainly to hide the standard Moodle page elements
$CFG->additionalhtmlhead .= '<style>';
$CFG->additionalhtmlhead .= 'body.drawer-open-left { margin-left: 0; } #nav-drawer { left: -305px; }'; // Hide the standard navigation
$CFG->additionalhtmlhead .= '#page-header, .btn.nav-link, .navbar .nav { display: none !important; }'; // Hide other unwanted elements
$CFG->additionalhtmlhead .= 'a.navbar-brand { pointer-events: none; cursor: default; }'; // Disable the Moodle link
$CFG->additionalhtmlhead .= '.nav-link { color: #d10373 !important; text-decoration: underline; } .nav-link:hover { color: #86024a !important; text-decoration: none; }'; // Links
$CFG->additionalhtmlhead .= 'body.hls-cpd.pagelayout-login #page {background: url(' . $CFG->httpswwwroot . '/local/obu_application/moodle-hls-login-bg.jpg) no-repeat center center fixed;background-size:cover;}'; // BG Links
$CFG->additionalhtmlhead .= 'body.hls-cpd.pagelayout-login.privacy-notice .login-container {max-width:90%}'; // BG
$CFG->additionalhtmlhead .= '</style>';

// Add our own menu items for logged-in users
if (!isloggedin()) {
	$PAGE->set_context(context_system::instance());
	$CFG->custommenuitems = '';
} else {
	$PAGE->set_context(context_user::instance($USER->id));
	$CFG->custommenuitems = get_string('index_page', 'local_obu_application') . '|/local/obu_application/index.php
	#####
	' . get_string('contactdetails', 'local_obu_application') . '|/local/obu_application/contact.php
	#####
	' . get_string('profile', 'local_obu_application') . '|/local/obu_application/profile.php
	#####
	' . get_string('apply', 'local_obu_application') . '|/local/obu_application/course.php
	#####
	' . get_string('logout', 'local_obu_application') . '|/local/obu_application/logout.php?loginpage=1';

	if (strpos($USER->email, '@brookes.ac.uk') !== false) {
		$CFG->custommenuitems .= '
		#####
		Moodle|/';
	}
}

?>