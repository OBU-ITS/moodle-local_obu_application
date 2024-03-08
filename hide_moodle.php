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

require_once('./locallib.php');

// Set the name for our micro-site
$SITE->shortname = get_string('plugintitle', 'local_obu_application');

// Set our login cookie suffix (too late for the session cookie)
$CFG->sessioncookie = 'email';

$PAGE->add_body_class("hls-cpd");

$nav_offset = is_siteadmin() ? 4 : 3;

// Add our own CSS - mainly to hide the standard Moodle page elements
$CFG->additionalhtmlhead .= '<style>';
$CFG->additionalhtmlhead .= 'body.drawer-open-left { margin-left: 0; } #nav-drawer { left: -305px; }'; // Hide the standard navigation
$CFG->additionalhtmlhead .= '#page-header, .btn.nav-link, .navbar .nav { display: none !important; }'; // Hide other unwanted elements
$CFG->additionalhtmlhead .= 'a.navbar-brand { pointer-events: none; cursor: default; }'; // Disable the Moodle link
$CFG->additionalhtmlhead .= '.nav-link { color: #d10373 !important; text-decoration: underline; } .nav-link:hover { color: #86024a !important; text-decoration: none; }'; // Links
$CFG->additionalhtmlhead .= 'body.hls-cpd.pagelayout-login #page {background: none;} body.hls-cpd.pagelayout-login #page:before  { content: ""; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: url(' . $CFG->httpswwwroot . '/local/obu_application/moodle-hls-login-bg.jpg) no-repeat center center; background-size: cover; will-change: transform; z-index: -1; }'; // BG Links
$CFG->additionalhtmlhead .= '.hls-cpd.path-local-obu_application .navbar .nav { display: flex !important; }';
$CFG->additionalhtmlhead .= '.hls-cpd.path-local-obu_application .primary-navigation .navigation .nav-link { color: #fff !important; }';
$CFG->additionalhtmlhead .= '.hls-cpd.path-local-obu_application .primary-navigation .navigation ul.nav li:nth-child(-n + ' . $nav_offset . ') { display: none !important; }';
$CFG->additionalhtmlhead .= '.hls-cpd.path-local-obu_application .drawer-primary .list-group a:nth-child(-n + ' . $nav_offset . ') { display: none !important; }';
$CFG->additionalhtmlhead .= '</style>';

$googleAnalytics = get_config('local_obu_application', 'google_analytics');
$hasGoogleAnalytics = $googleAnalytics != ''
    && $googleAnalytics != 'G-XXXXXXXXXX'
    && ( substr( $googleAnalytics, 0, 2 ) === "G-" || substr( $googleAnalytics, 0, 2 ) === "g-");
if($hasGoogleAnalytics) {
    $CFG->additionalhtmltopofbody = '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $googleAnalytics . '"></script>
';
    $CFG->additionalhtmltopofbody .= "<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    
    gtag('config', '" . $googleAnalytics . "')
</script>";

}
else {
    $CFG->additionalhtmltopofbody = '';
}

$CFG->additionalhtmlbottomofbody = '';
$CFG->additionalhtmlfooter = '';

// Add our own menu items for logged-in users
if (!isloggedin()) {
	$PAGE->set_context(context_system::instance());
	$CFG->custommenuitems = '';
} else {
	$PAGE->set_context(context_user::instance($USER->id));
	$CFG->custommenuitems = get_string('index_page', 'local_obu_application') . '|/local/obu_application/index.php';

    if(!is_funder()) {
        $CFG->custommenuitems .= '
        ' . get_string('application', 'local_obu_application') . '|/local/obu_application/application.php';
    }

	$CFG->custommenuitems .=  '
        ' . get_string('logout', 'local_obu_application') . '|/local/obu_application/logout.php?loginpage=1';

	if (strpos($USER->email, '@brookes.ac.uk') !== false) {
		$CFG->custommenuitems .= '
		Moodle|/';
	}
}

?>