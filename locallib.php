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
 * OBU Application - Library functions
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require_once($CFG->libdir . '/password_compat/lib/password.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/local/obu_application/db_update.php');

// Check if the user is an applications manager
function is_manager() {
	global $USER;
	
	if (is_siteadmin()) {
		return true;
	}
	
	return has_applications_role($USER->id, 4, 5);
}

// Check if the user is an applications administrator
function is_administrator() {
	global $USER;
	
	if (is_siteadmin()) {
		return true;
	}
	
	return has_applications_role($USER->id, 4);
}

/**  Determine where a user should be redirected after they have been logged in.
 * @return string url the user should be redirected to.
 */
function get_return_url() {
    global $CFG, $SESSION, $USER;
	
	if (isset($SESSION->wantsurl) and ((strpos($SESSION->wantsurl, $CFG->wwwroot) === 0) or (strpos($SESSION->wantsurl, str_replace('http://', 'https://', $CFG->wwwroot)) === 0))) {
        $urltogo = $SESSION->wantsurl;    // Because it's an address in this site.
        unset($SESSION->wantsurl);
    } else {
        // No wantsurl stored or external - go to homepage.
        $urltogo = $CFG->wwwroot . '/local/obu_application/';
        unset($SESSION->wantsurl);
    }

    return $urltogo;
}

function application_user_signup($user) { // Derived from email->user_signup
	global $CFG, $PAGE, $OUTPUT;

	$user->password = hash_internal_user_password($user->password);
	if (empty($user->calendartype)) {
		$user->calendartype = $CFG->calendartype;
	}
	$user->id = user_create_user($user, false);
	
	// Save any custom profile field information
	profile_save_data($user);
	
	// Save contact information
	write_contact_details($user->id, $user);
	
	if (!send_application_confirmation_email($user)) {
		print_error('auth_emailnoemail', 'auth_email');
	}
	
	$PAGE->set_title($CFG->pageheading . ': ' . get_string('emailconfirm'));
	echo $OUTPUT->header();
	notice(get_string('emailconfirmsent', '', $user->email), $CFG->wwwroot . '/local/obu_application/login.php');
}

function application_user_confirm($username, $confirmsecret) { // Derived from email->user_confirm
	global $DB;
	
	$user = get_complete_user_data('username', $username);

	if (!empty($user)) {
		if (($user->secret == $confirmsecret) && $user->confirmed) {
			return AUTH_CONFIRM_ALREADY;
		} else if ($user->secret == $confirmsecret) {   // They have provided the secret key to get in
			$DB->set_field('user', 'confirmed', 1, array('id' => $user->id));
			if ($user->firstaccess == 0) {
				$DB->set_field('user', 'firstaccess', time(), array('id' => $user->id));
			}
			return AUTH_CONFIRM_OK;
		}
	} else {
		return AUTH_CONFIRM_ERROR;
	}
}

function send_application_confirmation_email($user) {
	global $CFG;

	$data = new stdClass();
	$data->firstname = fullname($user);
	$data->sitename = format_string($CFG->pageheading);
	$data->admin = generate_email_signoff();

	$subject = get_string('emailconfirmationsubject', '', $data->sitename);
	$username = urlencode($user->username);
	$username = str_replace('.', '%2E', $username); // Prevent problems with trailing dots
	$link = $CFG->wwwroot . '/local/obu_application/confirm.php?data=' . $user->secret . '/' . $username;
	$data->link = '<a href="' . $link . '">' . $link . '</a>';
	$message = get_string('emailconfirmation', '', $data);
	$messagehtml = text_to_html($message, false, false, true);

	$user->mailformat = 1;  // Always send HTML version as well.

	// Send from HLS
	$hls = get_complete_user_data('username', 'hls');
    $hls->customheaders = array ( // Headers to help prevent auto-responders
		'Precedence: Bulk',
		'X-Auto-Response-Suppress: All',
		'Auto-Submitted: auto-generated'
	);
	return email_to_user($user, $hls, $subject, $message, $messagehtml);
}

function authenticate_application_user($username, $password, $ignorelockout = false, &$failurereason = null) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/authlib.php');

    if ($user = get_complete_user_data('username', $username, $CFG->mnet_localhost_id)) { // We have found the user
    } else if ($email = clean_param($username, PARAM_EMAIL)) {
		$select = "mnethostid = :mnethostid AND LOWER(email) = LOWER(:email) AND deleted = 0";
		$params = array('mnethostid' => $CFG->mnet_localhost_id, 'email' => strtolower($email));
		$users = $DB->get_records_select('user', $select, $params, 'id', 'id', 0, 2);
		if (count($users) === 1) { // Use email for login only if unique
			$user = reset($users);
			$user = get_complete_user_data('id', $user->id);
			$username = $user->username;
		}
		unset($users);
    }

    $authsenabled = get_enabled_auth_plugins();

    if ($user) {
		// Use manual if auth not set (or is set to 'email' - we need to accept those even if normally excluded)
        $auth = (empty($user->auth) || ($user->auth == 'email')) ? 'manual' : $user->auth;
        if (!empty($user->suspended)) {
            $failurereason = AUTH_LOGIN_SUSPENDED;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,  'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client ' . getremoteaddr() . "]  $CFG->wwwroot  Suspended Login:  $username  " . $_SERVER['HTTP_USER_AGENT']);
            return false;
        }
        if ($auth == 'nologin' or !is_enabled_auth($auth)) {
            // Legacy way to suspend user.
            $failurereason = AUTH_LOGIN_SUSPENDED;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id, 'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client ' . getremoteaddr() . "]  $CFG->wwwroot  Disabled Login:  $username  " . $_SERVER['HTTP_USER_AGENT']);
            return false;
        }
        $auths = array($auth);

    } else {
        // Check if there's a deleted record (cheaply), this should not happen because we mangle usernames in delete_user().
        if ($DB->get_field('user', 'id', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id,  'deleted' => 1))) {
            $failurereason = AUTH_LOGIN_NOUSER;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client ' . getremoteaddr() . "]  $CFG->wwwroot  Deleted Login:  $username  " . $_SERVER['HTTP_USER_AGENT']);
            return false;
        }

        // User does not exist.
        $auths = $authsenabled;
        $user = new stdClass();
        $user->id = 0;
    }

    if ($ignorelockout) {
        // Some other mechanism protects against brute force password guessing, for example login form might include reCAPTCHA
        // or this function is called from a SSO script.
    } else if ($user->id) {
        // Verify login lockout after other ways that may prevent user login.
        if (login_is_lockedout($user)) {
            $failurereason = AUTH_LOGIN_LOCKOUT;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();

            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Login lockout:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            return false;
        }
    } else {
        // We can not lockout non-existing accounts.
    }

    foreach ($auths as $auth) {
        $authplugin = get_auth_plugin($auth);

        // On auth fail fall through to the next plugin.
        if (!$authplugin->user_login($username, $password)) {
            continue;
        }

        // Successful authentication.
        if ($user->id) {
            // User already exists in database.
            if (empty($user->auth)) {
                // For some reason auth isn't set yet.
                $DB->set_field('user', 'auth', $auth, array('id' => $user->id));
                $user->auth = $auth;
            }

            // If the existing hash is using an out-of-date algorithm (or the legacy md5 algorithm), then we should update to
            // the current hash algorithm while we have access to the user's password.
            update_internal_user_password($user, $password);

            if ($authplugin->is_synchronised_with_external()) {
                // Update user record from external DB.
                $user = update_user_record_by_id($user->id);
            }
        } else {
            // The user is authenticated but user creation may be disabled.
            if (!empty($CFG->authpreventaccountcreation)) {
                $failurereason = AUTH_LOGIN_UNAUTHORISED;

                // Trigger login failed event.
                $event = \core\event\user_login_failed::create(array('other' => array('username' => $username, 'reason' => $failurereason)));
                $event->trigger();

                error_log('[client ' . getremoteaddr() . "]  $CFG->wwwroot  Unknown user, can not create new accounts:  $username  " . $_SERVER['HTTP_USER_AGENT']);
                return false;
            } else {
                $user = create_user_record($username, $password, $auth);
            }
        }

        $authplugin->sync_roles($user);

        foreach ($authsenabled as $hau) {
            $hauth = get_auth_plugin($hau);
            $hauth->user_authenticated_hook($user, $username, $password);
        }

        if (empty($user->id)) {
            $failurereason = AUTH_LOGIN_NOUSER;
            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                    'reason' => $failurereason)));
            $event->trigger();
            return false;
        }

        if (!empty($user->suspended)) {
            // Just in case some auth plugin suspended account.
            $failurereason = AUTH_LOGIN_SUSPENDED;
            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Suspended Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            return false;
        }

        login_attempt_valid($user);
        $failurereason = AUTH_LOGIN_OK;
        return $user;
    }

    // Failed if all the plugins have failed.
    if (debugging('', DEBUG_ALL)) {
        error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Failed Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
    }

    if ($user->id) {
        login_attempt_failed($user);
        $failurereason = AUTH_LOGIN_FAILED;
        // Trigger login failed event.
        $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                'other' => array('username' => $username, 'reason' => $failurereason)));
        $event->trigger();
    } else {
        $failurereason = AUTH_LOGIN_NOUSER;
        // Trigger login failed event.
        $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                'reason' => $failurereason)));
        $event->trigger();
    }

    return false;
}

function display_message($header, $message) {
	global $CFG, $PAGE, $OUTPUT;
	
	$PAGE->set_title($CFG->pageheading . ': Message');
	echo $OUTPUT->header();
	echo $OUTPUT->box_start('generalbox centerpara boxwidthnormal boxaligncenter');
	echo '<h3>' . $header . '</h3>';
	echo '<p>' . $message . '</p>';
	echo $OUTPUT->single_button($CFG->wwwroot . '/local/obu_application/', get_string('continue'));
	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
	
	exit;
}

function require_obu_login() {
	global $CFG, $SESSION, $USER, $PAGE, $SITE, $DB, $OUTPUT;

	$login_url = '/local/obu_application/login.php';

	// Must not redirect when byteserving already started.
	if (!empty($_SERVER['HTTP_RANGE'])) {
		$preventredirect = true;
	} else {
		$preventredirect = false;
	}

	// Redirect to the login page if session has expired, only with dbsessions enabled (MDL-35029) to maintain current behaviour.
	if ((!isloggedin() or isguestuser()) && !empty($SESSION->has_timed_out) && !$preventredirect && !empty($CFG->dbsessions)) {
		$SESSION->wantsurl = qualified_me();
		redirect($login_url);
	}

    // If the user is not even logged in yet then make sure they are.
	if (!isloggedin()) {
		if ($preventredirect) {
			throw new require_login_exception('You are not logged in');
		}

		$SESSION->wantsurl = qualified_me();
		if (!empty($_SERVER['HTTP_REFERER'])) {
			$SESSION->fromurl = $_SERVER['HTTP_REFERER'];
		}
		redirect($login_url);
	}

	// Make sure the USER has a sesskey set up. Used for CSRF protection.
	sesskey();

	// Fetch the system context.
	$context = context_system::instance();

	// If the site is currently under maintenance, then print a message.
	if ((!is_siteadmin() && !empty($CFG->maintenance_enabled) && !has_capability('moodle/site:config', $context))
		|| !has_capability('local/obu_application:update', $context) || !has_capability('local/obu_application:apply', $context)) {
		if ($preventredirect) {
			throw new require_login_exception('Maintenance in progress');
		}
		print_maintenance_message();
	}

	// Finally access granted, update lastaccess times.
	user_accesstime_log();
}

function get_counties() {
/*	
	$records = file_get_contents('https://kmis.brookes.ac.uk/csms/lookup_api.domicile');
	$records = json_decode($records, true);
	$counties = array();
	foreach ($records as $record) {
		if (($record['AREA'] == 'UK') && ($record['CODE'] != '') && ($record['DESCRIPTION'] != '')) {
			$counties[$record['CODE']] = $record['DESCRIPTION'];
		}
	}
	asort($counties);
*/	
	$counties = array (
		'2110' => 'Aberdeenshire',
		'2592' => 'Alderney',
		'2120' => 'Angus',
		'2003' => 'Antrim',
		'2130' => 'Argyll and Bute',
		'2005' => 'Armagh',
		'2302' => 'Barnet',
		'2800' => 'Bath & North East Somerset',
		'2820' => 'Bedfordshire',
		'2303' => 'Bexley',
		'2677' => 'Blaenau Gwent',
		'2867' => 'Bracknell Forest',
		'2304' => 'Brent',
		'2672' => 'Bridgend',
		'2846' => 'Brighton & Hove',
		'2305' => 'Bromley',
		'2823' => 'Buckinghamshire',
		'2676' => 'Caerphilly',
		'2381' => 'Calderdale',
		'2873' => 'Cambridgeshire',
		'2681' => 'Cardiff',
		'2669' => 'Carmarthenshire',
		'2667' => 'Ceredigion',
		'2591' => 'Channel Isles',
		'2875' => 'Cheshire',
		'2201' => 'City of London',
		'2150' => 'Clackmannashire',
		'2662' => 'Conwy',
		'2908' => 'Cornwall',
		'2331' => 'Coventry',
		'2306' => 'Croydon',
		'2909' => 'Cumbria',
		'2841' => 'Darlington',
		'2663' => 'Denbighshire',
		'2910' => 'Derbyshire',
		'2878' => 'Devon',
		'2371' => 'Doncaster',
		'2835' => 'Dorset',
		'2004' => 'Down',
		'2332' => 'Dudley',
		'2258' => 'Dumfries and Galloway',
		'2840' => 'Durham',
		'2170' => 'East Ayrshire',
		'2200' => 'East Dunbartonshire',
		'2172' => 'East Lothian',
		'2220' => 'East Renfrewshire',
		'2811' => 'East Riding of Yorkshire',
		'2845' => 'East Sussex',
		'2308' => 'Enfield',
		'2881' => 'Essex',
		'2240' => 'Falkirk',
		'2008' => 'Fermanagh',
		'2250' => 'Fife',
		'2664' => 'Flintshire',
		'2390' => 'Gateshead',
		'2916' => 'Gloucestershire',
		'2203' => 'Greenwich',
		'2593' => 'Guernsey',
		'2572' => 'Gwent',
		'2661' => 'Gwynedd',
		'2204' => 'Hackney',
		'2876' => 'Halton',
		'2205' => 'Hammersmith and Fulham',
		'2850' => 'Hampshire',
		'2309' => 'Haringey',
		'2310' => 'Harrow',
		'2805' => 'Hartlepool',
		'2311' => 'Havering',
		'2884' => 'Herefordshire',
		'2919' => 'Hertfordshire',
		'2270' => 'Highland',
		'2251' => 'Highlands',
		'2312' => 'Hillingdon',
		'2313' => 'Hounslow',
		'2280' => 'Inverclyde',
		'2660' => 'Isle of Anglesey',
		'2595' => 'Isle of Man',
		'2921' => 'Isle of Wight',
		'2420' => 'Isles of Scilly',
		'2206' => 'Islington',
		'2594' => 'Jersey',
		'2207' => 'Kensington and Chelsea',
		'2886' => 'Kent',
		'2314' => 'Kingston-Upon-Thames',
		'2382' => 'Kirklees',
		'2340' => 'Knowsley',
		'2208' => 'Lambeth',
		'2888' => 'Lancashire',
		'2855' => 'Leicestershire',
		'2209' => 'Lewisham',
		'2925' => 'Lincolnshire',
		'2341' => 'Liverpool',
		'2006' => 'Londonderry',
		'2821' => 'Luton',
		'2352' => 'Manchester',
		'2887' => 'Medway',
		'2675' => 'Merthyr Tydfil',
		'2315' => 'Merton',
		'2290' => 'Mid Lothian',
		'2806' => 'Middlesbrough',
		'2826' => 'Milton Keynes',
		'2679' => 'Monmouthshire',
		'2300' => 'Moray',
		'2671' => 'Neath Port Talbot',
		'2391' => 'Newcastle Upon Tyne',
		'2316' => 'Newham',
		'2680' => 'Newport',
		'2926' => 'Norfolk',
		'2182' => 'North Ayrshire',
		'2812' => 'North East Lincolnshire',
		'2183' => 'North Lanarkshire',
		'2813' => 'North Lincolnshire',
		'2802' => 'North Somerset',
		'2392' => 'North Tyneside',
		'2815' => 'North Yorkshire',
		'2928' => 'Northamptonshire',
		'2099' => 'Northern Ireland General',
		'2929' => 'Northumberland',
		'2891' => 'Nottinghamshire',
		'2353' => 'Oldham',
		'2260' => 'Orkney',
		'2184' => 'Orkney Islands',
		'2931' => 'Oxfordshire',
		'2668' => 'Pembrokeshire',
		'2185' => 'Perthshire & Kinross',
		'2666' => 'Powys',
		'2317' => 'Redbridge',
		'2807' => 'Redcar and Cleveland',
		'2186' => 'Renfrewshire',
		'2674' => 'Rhondda Cynon Taff',
		'2318' => 'Richmond-Upon-Thames',
		'2354' => 'Rochdale',
		'2372' => 'Rotherham',
		'2857' => 'Rutland',
		'2355' => 'Salford',
		'2333' => 'Sandwell',
		'2140' => 'Scottish Borders',
		'2343' => 'Sefton',
		'2373' => 'Sheffield',
		'2360' => 'Shetland',
		'2893' => 'Shropshire',
		'2871' => 'Slough',
		'2334' => 'Solihull',
		'2933' => 'Somerset',
		'2188' => 'South Ayrshire',
		'2803' => 'South Gloucestershire',
		'2189' => 'South Lanarkshire',
		'2393' => 'South Tyneside',
		'2852' => 'Southampton',
		'2882' => 'Southend',
		'2210' => 'Southwark',
		'2342' => 'St Helens',
		'2860' => 'Staffordshire',
		'2190' => 'Stirling',
		'2356' => 'Stockport',
		'2808' => 'Stockton-On-Tees',
		'2861' => 'Stoke-On-Trent',
		'2935' => 'Suffolk',
		'2394' => 'Sunderland',
		'2936' => 'Surrey',
		'2319' => 'Sutton',
		'2670' => 'Swansea',
		'2357' => 'Tameside',
		'2894' => 'The Wrekin',
		'2883' => 'Thurrock',
		'2880' => 'Torbay',
		'2678' => 'Torfaen',
		'2211' => 'Tower Hamlets',
		'2358' => 'Trafford',
		'2007' => 'Tyrone',
		'2673' => 'Vale of Glamorgan',
		'2384' => 'Wakefield',
		'2335' => 'Walsall',
		'2320' => 'Waltham Forest',
		'2212' => 'Wandsworth',
		'2877' => 'Warrington',
		'2937' => 'Warwickshire',
		'2869' => 'West Berkshire',
		'2160' => 'West Dunbartonshire',
		'2400' => 'West Lothian',
		'2191' => 'West Lothian',
		'2938' => 'West Sussex',
		'2410' => 'Western Isles',
		'2213' => 'Westminster',
		'2359' => 'Wigan',
		'2865' => 'Wiltshire',
		'2868' => 'Windsor and Maidenhead',
		'2344' => 'Wirral',
		'2872' => 'Wokingham',
		'2336' => 'Wolverhampton',
		'2885' => 'Worcestershire',
		'2665' => 'Wrexham'
	);
	
	return $counties;	
}

function get_countries() {
/*	
	$records = file_get_contents('https://kmis.brookes.ac.uk/csms/lookup_api.domicile');
	$records = json_decode($records, true);
	$countries = array();
	foreach ($records as $record) {
		if (($record['AREA'] == 'Overseas') && ($record['CODE'] != '') && ($record['DESCRIPTION'] != '') && ($record['DESCRIPTION'] != 'Uk')) {
			$countries[$record['CODE']] = $record['DESCRIPTION'];
		}
	}
	asort($countries);
*/	
	$countries = array (
		'1602' => 'Afghanistan',
		'1603' => 'Albania',
		'1604' => 'Algeria',
		'1854' => 'American Samoa',
		'1605' => 'Andorra',
		'1606' => 'Angola',
		'1824' => 'Anguilla',
		'1801' => 'Antarctica',
		'1607' => 'Antigua and Barbuda',
		'1608' => 'Argentina',
		'1836' => 'Armenia',
		'1609' => 'Australia',
		'1610' => 'Austria',
		'1837' => 'Azerbaijan',
		'1611' => 'Bahamas',
		'1612' => 'Bahrain',
		'1787' => 'Bangladesh',
		'1613' => 'Barbados',
		'1838' => 'Belarus',
		'1614' => 'Belgium',
		'1668' => 'Belize',
		'1640' => 'Benin',
		'1615' => 'Bermuda',
		'1616' => 'Bhutan',
		'1617' => 'Bolivia',
		'1853' => 'Bosnia/Herzegovina',
		'1618' => 'Botswana',
		'1619' => 'Brazil',
		'1829' => 'British Indian Ocean Territ\'s',
		'1620' => 'Brunei',
		'1621' => 'Bulgaria',
		'1769' => 'Burkina Faso',
		'1622' => 'Burma (Myanmar)',
		'1623' => 'Burundi',
		'1624' => 'Cambodia',
		'1625' => 'Cameroon',
		'1626' => 'Canada',
		'1788' => 'Cape Verde',
		'1789' => 'Cayman Islands',
		'1627' => 'Central African Republic',
		'1629' => 'Chad',
		'1630' => 'Chile',
		'1632' => 'Colombia',
		'1804' => 'Comoros',
		'1635' => 'Costa Rica',
		'1679' => 'Cote D\'Ivoire',
		'1834' => 'Croatia',
		'1636' => 'Cuba',
		'1882' => 'Cyprus (European Union)',
		'1883' => 'Cyprus (Non-European Union)',
		'1849' => 'Czech Republic',
		'1633' => 'Democratic Republic of the Congo',
		'1641' => 'Denmark',
		'1749' => 'Djibouti',
		'1642' => 'Dominica',
		'1643' => 'Dominican Republic',
		'1645' => 'Ecuador',
		'1768' => 'Egypt',
		'1646' => 'El Salvador',
		'1790' => 'Equatorial Guinea',
		'1860' => 'Eritrea',
		'1831' => 'Estonia',
		'1648' => 'Ethiopia',
		'1649' => 'Falkland Islands '&' Dependencies',
		'1865' => 'Faroe Islands',
		'1862' => 'Federated States of Micronesia',
		'1650' => 'Fiji',
		'1651' => 'Finland',
		'1851' => 'Former Yugoslav Republic of Macedonia',
		'1653' => 'France',
		'1822' => 'French Overseas Territories',
		'1654' => 'Gabon',
		'1655' => 'Gambia',
		'1847' => 'Georgia',
		'1656' => 'Germany',
		'1658' => 'Ghana',
		'1659' => 'Gibraltar',
		'1661' => 'Greece',
		'1828' => 'Greenland and the Faroe Islands',
		'1662' => 'Grenada',
		'1663' => 'Guatemala',
		'1664' => 'Guinea',
		'1802' => 'Guinea Bissau',
		'1665' => 'Guyana',
		'1666' => 'Haiti',
		'1667' => 'Honduras',
		'1669' => 'Hong Kong',
		'1670' => 'Hungary',
		'1671' => 'Iceland',
		'1672' => 'India',
		'1673' => 'Indonesia',
		'1674' => 'Iran',
		'1675' => 'Iraq',
		'1676' => 'Ireland',
		'1677' => 'Israel',
		'1678' => 'Italy',
		'1680' => 'Jamaica',
		'1681' => 'Japan',
		'1682' => 'Jordan',
		'1839' => 'Kazakhstan',
		'1683' => 'Kenya',
		'1660' => 'Kiribati',
		'1881' => 'Kosovo',
		'1686' => 'Kuwait',
		'1840' => 'Kyrgyzstan',
		'1687' => 'Laos',
		'1832' => 'Latvia',
		'1688' => 'Lebanon',
		'1689' => 'Leeward Islands',
		'1690' => 'Lesotho',
		'1691' => 'Liberia',
		'1692' => 'Libya',
		'1827' => 'Liechtenstein',
		'1833' => 'Lithuania',
		'1693' => 'Luxembourg',
		'1694' => 'Macau',
		'1852' => 'Macedonia',
		'1695' => 'Madagascar (Malagasy Republic)',
		'1696' => 'Malawi',
		'1698' => 'Malaysia',
		'1793' => 'Maldives',
		'1699' => 'Mali',
		'1700' => 'Malta',
		'1861' => 'Marshall Islands',
		'1701' => 'Mauritania',
		'1702' => 'Mauritius',
		'1821' => 'Mayotte',
		'1703' => 'Mexico',
		'1841' => 'Moldovia',
		'1825' => 'Monaco',
		'1704' => 'Mongolia',
		'1864' => 'Montenegro',
		'1705' => 'Montserrat',
		'1706' => 'Morocco',
		'1707' => 'Mozambique',
		'1798' => 'Namibia',
		'1805' => 'Nauru',
		'1709' => 'Nepal',
		'1710' => 'Netherlands',
		'1637' => 'Netherlands Antilles',
		'1712' => 'New Guinea',
		'1714' => 'New Zealand',
		'1715' => 'Nicaragua',
		'1716' => 'Niger',
		'1717' => 'Nigeria',
		'1685' => 'North Korea',
		'1873' => 'Northern Mariana Islands',
		'1718' => 'Norway',
		'1708' => 'Oman',
		'1721' => 'Pakistan',
		'1874' => 'Palau',
		'1870' => 'Palestine',
		'1722' => 'Panama',
		'1723' => 'Papua New Guinea',
		'1724' => 'Paraguay',
		'1631' => 'People\'s Republic of China',
		'1725' => 'Peru',
		'1726' => 'Philippines',
		'1823' => 'Pitcairn Islands',
		'1727' => 'Poland',
		'1728' => 'Portugal',
		'1730' => 'Puerto Rico',
		'1731' => 'Qatar',
		'1634' => 'Republic of the Congo',
		'1733' => 'Romania',
		'1842' => 'Russia',
		'1734' => 'Rwanda',
		'1741' => 'Samoa',
		'1826' => 'San Marino',
		'1803' => 'Sao Tome and Principe',
		'1743' => 'Saudi Arabia',
		'1785' => 'Senegal',
		'1780' => 'Serbia',
		'1744' => 'Seychelles',
		'1745' => 'Sierra Leone',
		'1746' => 'Singapore',
		'1850' => 'Slovakia',
		'1835' => 'Slovenia',
		'1747' => 'Solomon Islands',
		'1748' => 'Somalia',
		'1750' => 'South Africa',
		'1830' => 'South Georgia & South Sandwich Islands',
		'1684' => 'South Korea',
		'1751' => 'Spain',
		'1628' => 'Sri Lanka',
		'1735' => 'St Helena and Dependencies',
		'1736' => 'St Kitts & Nevis',
		'1737' => 'St Lucia',
		'1738' => 'St Vincent and the Grenadines',
		'1783' => 'Stateless',
		'1752' => 'Sudan',
		'1753' => 'Surinam',
		'1754' => 'Swaziland',
		'1755' => 'Sweden',
		'1756' => 'Switzerland',
		'1757' => 'Syria',
		'1884' => 'South Sudan',
		'1652' => 'Taiwan',
		'1843' => 'Tajikistan',
		'1759' => 'Tanzania',
		'1760' => 'Thailand',
		'1762' => 'Togo',
		'1784' => 'Tonga',
		'1763' => 'Trinidad and Tobago',
		'1765' => 'Tunisia',
		'1766' => 'Turkey',
		'1844' => 'Turkmenistan',
		'1799' => 'Turks and Caicos Islands',
		'1647' => 'Tuvalu',
		'1767' => 'Uganda',
		'1845' => 'Ukraine',
		'1764' => 'United Arab Emirates',
		'1770' => 'Uruguay',
		'1796' => 'US Trust Territories of the Pacific Isl.',
		'1771' => 'USA',
		'1846' => 'Uzbekistan',
		'1713' => 'Vanuatu',
		'1878' => 'Vatican City',
		'1773' => 'Venezuela',
		'1774' => 'Vietnam',
		'1776' => 'Virgin Islands (British)',
		'1800' => 'Virgin Islands (US)',
		'1880' => 'Western Sahara',
		'1778' => 'Windward Islands',
		'1601' => 'Yemen',
		'1781' => 'Zambia',
		'1732' => 'Zimbabwe',
		'1782' => 'Not Known'
	);
	
	return $countries;	
}

function get_nationalities() {
/*	
	$records = file_get_contents('https://kmis.brookes.ac.uk/csms/lookup_api.nationality');
	$records = json_decode($records, true);
	$nationalities = array();
	foreach ($records as $record) {
		if (($record['CODE'] != '') && ($record['DESCRIPTION'] != '')) {
			$nationalities[$record['CODE']] = $record['DESCRIPTION'];
		}
	}
	asort($nationalities);
*/
	$nationalities = array (
		'1602' => 'Afghan',
		'1603' => 'Albanian',
		'1604' => 'Algerian',
		'1771' => 'American (USA)',
		'1800' => 'American (Virgin Islands (US))',
		'1854' => 'American Samoan',
		'1605' => 'Andorran',
		'1606' => 'Angolan',
		'1607' => 'Antiguan/Barbudan',
		'1608' => 'Argentinian',
		'1836' => 'Armenian',
		'1609' => 'Australian',
		'1610' => 'Austrian',
		'1837' => 'Azerbaijani',
		'1611' => 'Bahamian',
		'1612' => 'Bahraini',
		'1787' => 'Bangladeshi',
		'1613' => 'Barbadian',
		'1838' => 'Belarusian',
		'1614' => 'Belgian',
		'1668' => 'Belizian',
		'1640' => 'Beninese',
		'1616' => 'Bhutanese',
		'1617' => 'Bolivian',
		'1853' => 'Bosnian/Herzergovinian',
		'1619' => 'Brazilian',
		'806' => 'British Citizen',
		'1824' => 'British National (Anguilla)',
		'1801' => 'British National (Antarctica)',
		'1615' => 'British National (Bermuda)',
		'1829' => 'British National (British Indian Ocean Territ\'s)',
		'1789' => 'British National (Cayman Islands)',
		'1649' => 'British National (Falkland Islands & Dependencies)',
		'1659' => 'British National (Gibraltar)',
		'1660' => 'British National (Kiribati)',
		'1689' => 'British National (Leeward Islands)',
		'1705' => 'British National (Montserrat)',
		'1823' => 'British National (Pitcairn Islands)',
		'1830' => 'British National (South Georgia & South Sandwich Islands)',
		'1735' => 'British National (St Helena and Dependencies)',
		'1736' => 'British National (St Kitts & Nevis)',
		'1799' => 'British National (Turks and Caicos Islands)',
		'1776' => 'British National (Virgin Islands (British))',
		'1620' => 'Bruneian',
		'1621' => 'Bulgarian',
		'1769' => 'Burkinabe',
		'1622' => 'Burmese (Myanmar)',
		'1623' => 'Burundi',
		'1624' => 'Cambodian',
		'1625' => 'Cameroonian',
		'1626' => 'Canadian',
		'1788' => 'Cape Verdean',
		'1627' => 'Central African',
		'1629' => 'Chadian',
		'1630' => 'Chilean',
		'1669' => 'Chinese (Hong Kong)',
		'1631' => 'Chinese (People\'s Republic of China)',
		'1632' => 'Colombian',
		'1804' => 'Comoran',
		'1633' => 'Congolese (Democratic Republic of the Congo)',
		'1634' => 'Congolese (Republic of the Congo)',
		'1635' => 'Costa Rican',
		'1834' => 'Croatian',
		'1636' => 'Cuban',
		'1638' => 'Cypriot',
		'1882' => 'Cypriot (European Union)',
		'1883' => 'Cypriot (Non-European Union)',
		'1849' => 'Czech',
		'1641' => 'Danish (Denmark)',
		'1828' => 'Danish (Greenland and the Faroe Islands)',
		'1749' => 'Djiboutian',
		'1642' => 'Dominican (Dominica)',
		'1643' => 'Dominican (Dominican Republic)',
		'1637' => 'Dutch (Netherlands Antilles)',
		'1710' => 'Dutch (Netherlands)',
		'1645' => 'Ecuadorian',
		'1768' => 'Egyptian',
		'1764' => 'Emirati',
		'1790' => 'Equatorial Guinean',
		'1860' => 'Eritrean',
		'1831' => 'Estonian',
		'1648' => 'Ethiopian',
		'1865' => 'Faroese',
		'1650' => 'Fijian',
		'1726' => 'Filipino',
		'1651' => 'Finn',
		'1653' => 'French (France)',
		'1822' => 'French (French Overseas Territories)',
		'1821' => 'French (Mayotte)',
		'1654' => 'Gabonese',
		'1655' => 'Gambian',
		'1847' => 'Georgian',
		'1656' => 'German',
		'1658' => 'Ghanaian',
		'1661' => 'Greek',
		'1662' => 'Grenadian',
		'1663' => 'Guatemalan',
		'1802' => 'Guinea Bissau Citizen',
		'1664' => 'Guinean',
		'1665' => 'Guyanese',
		'1666' => 'Haitian',
		'1667' => 'Honduran',
		'1670' => 'Hungarian',
		'1671' => 'Icelander',
		'1672' => 'Indian',
		'1673' => 'Indonesian',
		'1674' => 'Iranian',
		'1675' => 'Iraqi',
		'1676' => 'Irish',
		'1677' => 'Israeli',
		'1678' => 'Italian',
		'1679' => 'Ivorian',
		'1680' => 'Jamaican',
		'1681' => 'Japanese',
		'1682' => 'Jordanian',
		'1839' => 'Kazakhstani',
		'1683' => 'Kenyan',
		'1881' => 'Kosovan',
		'1686' => 'Kuwaiti',
		'1840' => 'Kyrgyzstani',
		'1687' => 'Laotian',
		'1832' => 'Latvian',
		'1688' => 'Lebanese',
		'1691' => 'Liberian',
		'1692' => 'Libyan',
		'1827' => 'Liechtensteiner',
		'1833' => 'Lithuanian',
		'1693' => 'Luxembourger',
		'1851' => 'Macedonian (Former Yugoslav Republic of Macedonia)',
		'1852' => 'Macedonian (Macedonia)',
		'1695' => 'Malagasy',
		'1696' => 'Malawian',
		'1698' => 'Malaysian',
		'1793' => 'Maldivian',
		'1699' => 'Malian',
		'1700' => 'Maltese',
		'1861' => 'Marshallese',
		'1701' => 'Mauritanian',
		'1702' => 'Mauritian',
		'1703' => 'Mexican',
		'1862' => 'Micronesian',
		'1841' => 'Moldovian',
		'1825' => 'Monegasque',
		'1704' => 'Mongolian',
		'1864' => 'Montenegrin',
		'1706' => 'Moroccan',
		'1690' => 'Mosotho',
		'1618' => 'Motswana',
		'1707' => 'Mozambican',
		'1798' => 'Namibian',
		'1805' => 'Nauruan',
		'1709' => 'Nepalese',
		'1712' => 'New Guinean',
		'1714' => 'New Zealander',
		'1713' => 'Ni-Vanuatu',
		'1715' => 'Nicaraguan',
		'1716' => 'Nigerian (Niger)',
		'1717' => 'Nigerian (Nigeria)',
		'1685' => 'North Korean',
		'1873' => 'Northern Mariana Islands',
		'1718' => 'Norwegian',
		'1782' => 'Not Known',
		'1708' => 'Omani',
		'1721' => 'Pakistani',
		'1874' => 'Palauan',
		'1870' => 'Palestinian',
		'1722' => 'Panamanian',
		'1723' => 'Papua New Guinean',
		'1724' => 'Paraguayan',
		'1725' => 'Peruvian',
		'1727' => 'Polish',
		'1694' => 'Portugese',
		'1728' => 'Portuguese',
		'1730' => 'Puerto Rican (US Citizens)',
		'1731' => 'Qatari',
		'1733' => 'Romanian',
		'1842' => 'Russian',
		'1734' => 'Rwandan',
		'1880' => 'Sahrawi',
		'1646' => 'Salvadoran',
		'1826' => 'Samarinese',
		'1741' => 'Samoan',
		'1803' => 'Sao Tomean',
		'1743' => 'Saudi Arabian',
		'1785' => 'Senegalese',
		'1780' => 'Serbian',
		'1744' => 'Seychellois',
		'1745' => 'Sierra Leonean',
		'1746' => 'Singaporean',
		'1850' => 'Slovak',
		'1835' => 'Slovenian',
		'1747' => 'Solomon Islander',
		'1748' => 'Somali',
		'1750' => 'South African',
		'1684' => 'South Korean',
		'1884' => 'South Sudanese',
		'1751' => 'Spanish',
		'1628' => 'Sri Lankan',
		'1737' => 'St Lucian',
		'1783' => 'Stateless',
		'1752' => 'Sudanese',
		'1753' => 'Surinamese',
		'1754' => 'Swazi',
		'1755' => 'Swedish',
		'1756' => 'Swiss',
		'1757' => 'Syrian',
		'1652' => 'Taiwanese',
		'1843' => 'Tajikistani',
		'1759' => 'Tanzanian',
		'1760' => 'Thai',
		'1762' => 'Togolese',
		'1784' => 'Tongan',
		'1763' => 'Trinidadian/ Tobagonian',
		'1765' => 'Tunisian',
		'1766' => 'Turkish',
		'1844' => 'Turkmen',
		'1647' => 'Tuvaluan',
		'1796' => 'US Citizens',
		'1767' => 'Ugandan',
		'1845' => 'Ukrainian',
		'1770' => 'Uruguayan',
		'1846' => 'Uzbekistani',
		'1878' => 'Vatican City',
		'1773' => 'Venezuelan',
		'1774' => 'Vietnamese',
		'1738' => 'Vincentian',
		'1601' => 'Yemeni',
		'1781' => 'Zambian',
		'1732' => 'Zimbabwean'
	);
	
	return $nationalities;	
}

function get_course_names() {
	
	$courses = array();
	$recs = get_course_records();
	foreach ($recs as $rec) {
		$courses[$rec->code] = $rec->code . ' ' . $rec->name;
	}
	
	return $courses;	
}

function is_programme($course_code) {
	if ((strlen($course_code) == 4) && ctype_alpha(substr($course_code, 0, 2)) && is_numeric(substr($course_code, 2))) {
		return true;
	} else {
		return false;
	}
}

function get_organisations() {
	
	$organisations = array();
	$recs = get_organisation_records();
	foreach ($recs as $rec) {
		if (($rec->code != 0) && ($rec->suspended == 0)) {
			$organisations[$rec->id] = $rec->name;
		}
	}
	
	return $organisations;	
}

function get_application_status($user_id, $application, &$text, &$button) { // Get the status from the given user's perspective

	$text = '';
	$button = '';
	
	// Prepare the submission/approval trail
	$date = date_create();
	$format = 'd-m-y H:i';
	if ($application->approval_level > 0) { // Applicant has submitted the application
		date_timestamp_set($date, $application->application_date);
		$text .= date_format($date, $format) . ' ';
		if ($application->userid == $user_id) {
			$name = 'you';
		} else {
			$approver = get_complete_user_data('id', $application->userid);
			$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
		}
		$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('submitted', 'local_obu_application'), 'by' => $name));
		$text .= '<br />';
		if (($application->approval_level == 1) && ($application->approval_state > 0)) { // The workflow ended here
			date_timestamp_set($date, $application->approval_1_date);
			$text .= date_format($date, $format) . ' ';
			$approver = get_complete_user_data('email', strtolower($application->manager_email));
			if ($approver === false) {
				$name = $application->manager_email;
			} else if ($approver->id == $user_id) {
				$name = 'you';
			} else {
				$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
			}
			if ($application->approval_state == 1) {
				$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('rejected', 'local_obu_application'), 'by' => $name));
			} else {
				$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('approved', 'local_obu_application'), 'by' => $name));
			}
			$text .= ' ' . $application->approval_1_comment . '<br />';
		} else if ($application->approval_level > 1) {
			if ($application->manager_email != '') {
				date_timestamp_set($date, $application->approval_1_date);
				$text .= date_format($date, $format) . ' ';
				$approver = get_complete_user_data('email', strtolower($application->manager_email));
				if ($approver === false) {
					$name = $application->manager_email;
				} else if ($approver->id == $user_id) {
					$name = 'you';
				} else {
					$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
				}
				$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('approved', 'local_obu_application'), 'by' => $name));
				$text .= ' ' . $application->approval_1_comment . '<br />';
			}
			if (($application->approval_level == 2) && ($application->approval_state > 0)) { // The workflow ended here
				date_timestamp_set($date, $application->approval_2_date);
				$text .= date_format($date, $format) . ' ';
				$approver = get_complete_user_data('email', strtolower($application->funder_email));
				if ($approver === false) {
					$name = $application->funder_email;
				} else if ($approver->id == $user_id) {
					$name = 'you';
				} else {
					$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
				}
				if ($application->approval_state == 1) {
					$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('rejected', 'local_obu_application'), 'by' => $name));
				} else {
					$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('approved', 'local_obu_application'), 'by' => $name));
				}
				$text .= ' ' . $application->approval_2_comment . '<br />';
			} else if ($application->approval_level > 2) {
				if ($application->self_funding == '0') { // This step would have been skipped
					date_timestamp_set($date, $application->approval_2_date);
					$text .= date_format($date, $format) . ' ';
					$approver = get_complete_user_data('email', strtolower($application->funder_email));
					if ($approver === false) {
						$name = $application->funder_email;
					} else if ($approver->id == $user_id) {
						$name = 'you';
					} else {
						$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
					}
					$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('approved', 'local_obu_application'), 'by' => $name));
					$text .= ' ' . $application->approval_2_comment . '<br />';
				}
				if ($application->approval_state > 0) { // The workflow ended here
					date_timestamp_set($date, $application->approval_3_date);
					$text .= date_format($date, $format) . ' ';
					$approver = get_complete_user_data('username', 'hls');
					if ($approver->id == $user_id) {
						$name = 'you';
					} else {
						$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
					}
					if ($application->approval_state == 1) {
						$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('rejected', 'local_obu_application'), 'by' => $name));
					} else {
						$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('approved', 'local_obu_application'), 'by' => $name));
					}
					$text .= ' ' . $application->approval_3_comment . '<br />';
					if (is_manager()) {
						if ($application->admissions_xfer > 0) {
							$text .= get_string('admissions', 'local_obu_application') . ' ' . get_string('data_xfer', 'local_obu_application') . ': ' .$application->admissions_xfer . '<br />';
						}
						if ($application->finance_xfer > 0) {
							$text .= get_string('finance', 'local_obu_application') . ' ' . get_string('data_xfer', 'local_obu_application') . ': ' . $application->finance_xfer . '<br />';
						}
					}
				}
			}
		}
	}

	// If the state is zero, display the next action required.  Otherwise, the application has already been rejected or processed 
	if ($application->approval_state == 0) { // Awaiting submission/rejection/approval from someone
		if ($application->approval_level == 0) { // Applicant hasn't submitted the application
			if ($application->userid == $user_id) {
				$name = 'you';
				$button = 'submit';
			} else {
				$approver = get_complete_user_data('id', $application->userid);
				$name = $approver->firstname . ' ' . $approver->lastname;
				$button = 'continue';
			}
			$action = html_writer::span(get_string('awaiting_action', 'local_obu_application', array('action' => get_string('submission', 'local_obu_application'), 'by' => $name)), '', array('style' => 'color:red'));
			$text .= '<p />' . $action;
		} else {
			if ($application->approval_level == 1) { // Manager
				$approver = get_complete_user_data('email', strtolower($application->manager_email));
				if ($approver === false) {
					$name = $application->manager_email;
				} else {
					$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
				}
			} else if ($application->approval_level == 2) { // Funder
				$approver = get_complete_user_data('email', strtolower($application->funder_email));
				if ($approver === false) {
					$name = $application->funder_email;
				} else {
					$name = $approver->firstname . ' ' . $approver->lastname;
				}
			} else { // HLS
				$approver = get_complete_user_data('username', 'hls');
				$name = $approver->firstname . ' ' . $approver->lastname;
			}
			if (($approver !== false) && ($approver->id == $user_id)) {
				$name = 'you';
				$button = 'approve';
			} else if (($approver !== false) && ($approver->username == 'hls') && is_manager()) {
				$button = 'approve';
			} else {
				$button = 'continue';
			}
			$action = html_writer::span(get_string('awaiting_action', 'local_obu_application', array('action' => get_string('approval', 'local_obu_application'), 'by' => $name)), '', array('style' => 'color:red'));
			$text .= '<p />' . $action;
		}
	} else { // Application processed - nothing more to say...
		$button = 'continue';
	}
}

function update_workflow(&$application, $approved = true, $data = null) {

	$approver_email = '';
	
	// Update the application record
	if ($application->approval_level == 0) { // Being submitted
/*		$application->approval_level = 1;
		$approver_email = $application->manager_email;
	} else if ($application->approval_level == 1) { // Manager
		$application->approval_1_comment = $data->comment;
		$application->approval_1_date = time();
		if (!$approved) {
			$application->approval_state = 1; // Rejected
		} else*/
		if ($application->self_funding == 0) {
			$application->approval_level = 2; // Funder
			$application->funding_id = $data->funding_organisation;
			if ($application->funding_id == 0) { // 'Other Organisation'
				$application->funding_organisation = '';
				$application->funder_email = $data->funder_email; // Must have been given
			} else { // A known organisation with a fixed email address
				$organisation = read_organisation($application->funding_id);
				$application->funding_organisation = $organisation->name;
				$application->funder_email = $organisation->email;
			}
			$approver_email = $application->funder_email;
		} else {
			$application->approval_level = 3; // Brookes
			$hls = get_complete_user_data('username', 'hls');
			$approver_email = $hls->email;
		}
	} else if ($application->approval_level == 2) { // Funder
		$application->approval_2_comment = $data->comment;
		$application->approval_2_date = time();
		if (!$approved) {
			$application->approval_state = 1; // Rejected
		} else {
			$application->approval_level = 3; // Brookes
			
			// Store the funding details
			if ($application->funding_organisation != '') { // NHS trust (previously selected by the manager)
				$application->funding_method = $data->funding_method;
				$application->funder_name = $data->funder_name;
				if ($application->funding_method == 1) { // Invoice
					$application->invoice_ref = $data->invoice_ref;
					$application->invoice_address = $data->invoice_address;
					$application->invoice_email = $data->invoice_email;
					$application->invoice_phone = $data->invoice_phone;
					$application->invoice_contact = $data->invoice_contact;
				}
			} else { // Must be an invoice to a non-NHS organisation
				$application->funding_method = 0;
				$application->funding_organisation = $data->funding_organisation;
				$application->invoice_ref = $data->invoice_ref;
				$application->invoice_address = $data->invoice_address;
				$application->invoice_email = $data->invoice_email;
				$application->invoice_phone = $data->invoice_phone;
				$application->invoice_contact = $data->invoice_contact;
			}

			// Add the additional funding fields for a programme of study
			if (is_programme($application->course_code)) {
				$application->fund_programme = $data->fund_programme;
				if ($data->fund_programme) {
					$application->fund_module_1 = '';
					$application->fund_module_2 = '';
					$application->fund_module_3 = '';
					$application->fund_module_4 = '';
					$application->fund_module_5 = '';
					$application->fund_module_6 = '';
					$application->fund_module_7 = '';
					$application->fund_module_8 = '';
					$application->fund_module_9 = '';
				} else {
					$application->fund_module_1 = $data->fund_module_1;
					$application->fund_module_2 = $data->fund_module_2;
					$application->fund_module_3 = $data->fund_module_3;
					$application->fund_module_4 = $data->fund_module_4;
					$application->fund_module_5 = $data->fund_module_5;
					$application->fund_module_6 = $data->fund_module_6;
					$application->fund_module_7 = $data->fund_module_7;
					$application->fund_module_8 = $data->fund_module_8;
					$application->fund_module_9 = $data->fund_module_9;
				}
			}

			$hls = get_complete_user_data('username', 'hls');
			$approver_email = $hls->email;
		}
	} else { // Brookes
		$application->approval_3_comment = $data->comment;
		$application->approval_3_date = time();
		if (!$approved) {
			$application->approval_state = 1; // Rejected
		} else {
			$application->approval_state = 2; // It ends here
		}
	}
	update_application($application);
	
	// Update the stored approval requests and send notification emails
	update_approver($application, $approver_email);
}

function update_approver($application, $approver_email) {

	// Update the stored approval requests
	read_approval($application->id, $approval);
	if ($approver_email == '') {
		delete_approval($approval);
	} else {
		$approval->approver = strtolower($approver_email);
		$approval->request_date = time();
		write_approval($approval);
	}
	
	// Determine the URL to use to link to the application
	$process = new moodle_url('/local/obu_application/process.php') . '?id=' . $application->id;
	$mdl_process = new moodle_url('/local/obu_application/mdl_process.php') . '?id=' . $application->id; // 'Mainstream' Moodle

	// Email the new status to the applicant and to the HLS Team (if not the next approver)
	$applicant = get_complete_user_data('id', $application->userid);
	$hls = get_complete_user_data('username', 'hls');
    $applicant->customheaders = array ( // Headers to help both redirect bounces and suppress auto-responders
		'Sender: ' . $hls->email,
		'Precedence: Bulk',
		'X-Auto-Response-Suppress: All',
		'Auto-Submitted: auto-generated'
	);
    $hls->customheaders = array ( // Headers to help both redirect bounces and suppress auto-responders
		'Sender: ' . $hls->email,
		'Precedence: Bulk',
		'X-Auto-Response-Suppress: All',
		'Auto-Submitted: auto-generated'
	);
	get_application_status($applicant->id, $application, $text, $button_text); // Get the status from the applicant's perspective
	$html = '<h4><a href="' . $process . '">HLS Application (Ref HLS/' . $application->id . ')</a></h4>' . $text;
	email_to_user($applicant, $hls, 'The Status of Your HLS Application (Ref HLS/' . $application->id . ')', html_to_text($html), $html);
	if ($approver_email != $hls->email) { // Update HLS unless they are the next approver
		get_application_status($hls->id, $application, $text, $button_text); // get the status from the HLS's perspective
		$html = '<h4><a href="' . $mdl_process . '">' . $application->course_code . ' ' . $application->course_name . ' (Application Ref HLS/' . $application->id . ')</a></h4>' . $text;
//		email_to_user($hls, $applicant, 'Status Update: ' . $application->course_code . ' ' . $application->course_name . ' (' . $applicant->firstname . ' ' . $applicant->lastname . ')', html_to_text($html), $html);
	}
	
	// Notify the next approver (if there is one)
	if ($approver_email != '') {
		$approver = get_complete_user_data('email', strtolower($approver_email));
		if ($approver === false) { // Approver hasn't yet registered
			// Moodle requires a user to send emails to, not just an email address
			$approver = new stdClass();
			$approver->email = $approver_email;
			$approver->firstname = '';
			$approver->lastname = '';
			$approver->maildisplay = true;
			$approver->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
			$approver->id = -99; // Moodle User ID. If it is for someone who is not a Moodle user, use an invalid ID like -99.
			$approver->firstnamephonetic = '';
			$approver->lastnamephonetic = '';
			$approver->middlename = '';
			$approver->alternatename = '';
		}			
		if ($approver->email == $hls->email) { // HLS require the course name and will use 'mainstream' Moodle for their approvals
/*			$link = '<a href="' . $mdl_process . '">' . $application->course_code . ' ' . $application->course_name . ' (Application Ref HLS/'. $application->id . ')</a>';
			$html = get_string('request_approval', 'local_obu_application', $link);
			email_to_user($approver, $applicant, 'Approval Required: ' . $application->course_code . ' ' . $application->course_name
				. ' (' . $applicant->firstname . ' ' . $applicant->lastname . ')', html_to_text($html), $html);
*/		} else {
			$link = '<a href="' . $process . '">HLS Application (Application Ref HLS/' . $application->id . ')</a>';
			$html = get_string('request_approval', 'local_obu_application', $link);
			email_to_user($approver, $applicant, 'Request for HLS Application Approval ('
				. $applicant->firstname . ' ' . $applicant->lastname . ')', html_to_text($html), $html);
		}
	}
}

function encode_xml($string) {
	return(htmlentities($string, ENT_NOQUOTES | ENT_XML1, 'UTF-8'));
}

function decode_xml($string) {
	return(html_entity_decode($string, ENT_NOQUOTES | ENT_XML1, 'UTF-8'));
}

function get_select_elements($supplement) {
	$selects = array();
	
	$fld_start = '<input ';
	$fld_start_len = strlen($fld_start);
	$fld_end = '>';
	$fld_end_len = strlen($fld_end);
	$offset = 0;
	
	do {
		$pos = strpos($supplement, $fld_start, $offset);
		if ($pos === false) {
			break;
		}
		$offset = $pos + $fld_start_len;
		$pos = strpos($supplement, $fld_end, $offset);
		if ($pos === false) {
			break;
		}
		$element = split_input_field(substr($supplement, $offset, ($pos - $offset)));
		$offset = $pos + $fld_end_len;
		if ($element['type'] == 'select') {
			$selects[$element['id']] = $element['name'];
		}
	} while(true);
	
	return $selects;
}

function get_file_elements($supplement) {
	$files = array();
	
	$fld_start = '<input ';
	$fld_start_len = strlen($fld_start);
	$fld_end = '>';
	$fld_end_len = strlen($fld_end);
	$offset = 0;
	
	do {
		$pos = strpos($supplement, $fld_start, $offset);
		if ($pos === false) {
			break;
		}
		$offset = $pos + $fld_start_len;
		$pos = strpos($supplement, $fld_end, $offset);
		if ($pos === false) {
			break;
		}
		$element = split_input_field(substr($supplement, $offset, ($pos - $offset)));
		$offset = $pos + $fld_end_len;
		if ($element['type'] == 'file') {
			$files[] = $element['id'];
		}
	} while(true);
	
	return $files;
}

function split_input_field($input_field) {
	$parts = str_replace('" ', '"|^|', $input_field);
	$parts = explode('|^|', $parts);
	$params = array();
	$options = '';
	foreach ($parts as $part) {
		$pos = strpos($part, '="');
		$key = substr($part, 0, $pos);
		
		// We were forced to use 'maxlength' so map it
		if (isset($params['type']) && ($params['type'] == 'select') && ($key == 'maxlength')) {
			$key = 'selected';
		}
		
		if (($key == 'size') || ($key == 'maxlength')) {
			if ($options != '') {
				$options .= ' ';
			}
			$options .= $part;
		} else {
			$pos += 2;
			$value = substr($part, $pos, (strlen($part) - 1 - $pos));
			$value = str_replace('"', '', $value);
			
			// If the 'value' parameter is suffixed then the field (or one of the required group) must be completed
			if ($key == 'value') {
				$suffix = substr($value, (strlen($value) - 1));
				if (($suffix == '#') || ($suffix == '*')) {
					$value = substr($value, 0, (strlen($value) - 1)); // Strip-off the indicator
					if ($suffix == '#') {
						$params['rule'] = 'group';
					} else {
						$params['rule'] = 'required';
					}
				}
			}
			
			$params[$key] = $value;
		}
	}
	if ($options != '') {
		// We were forced to use 'size' and 'maxlength' in 'area' (textarea) so map them
		if ($params['type'] == 'area') {
			$options = str_replace('size', 'cols', $options);
			$options = str_replace('maxlength', 'rows', $options);
		}
		$params['options'] = $options;
	}
	
	return $params;
}

function pack_supplement_data($fields) {
	$xml = new SimpleXMLElement('<supplement_data/>');
	foreach ($fields as $key => $value) {
		$xml->addChild($key, encode_xml($value));
	}
	
    return $xml->asXML();
}

function unpack_supplement_data($data, &$fields) {

	$fields = array();
	if ($data) {
		$xml = new SimpleXMLElement($data);
		foreach ($xml as $key => $value) {
			$fields[$key] = (string)$value;
		}
	}
	
	return true;
}

function get_file_link($file_pathnamehash) {
    global $CFG, $USER;

	$fs = get_file_storage();
	$file = $fs->get_file_by_hash($file_pathnamehash);
	
	$url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
		
	return '<a href="' . $url . '">' . $file->get_filename() . '</a>';
}

?>
