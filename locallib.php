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
 * @copyright  2021, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

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

// Get all applications managers/administrators
function get_managers() {
	return get_users_by_role(4, 5);
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

	$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
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

function application_user_delete($user) {

	delete_applicant($user->id); // Delete our own records first

	return user_delete_user($user);
}

function send_application_confirmation_email($user) {
	global $CFG;

	$data = new stdClass();
	$data->fullname = fullname($user);
	$data->sitename = format_string($CFG->pageheading);
	$data->admin = generate_email_signoff();

	$subject = get_string('emailconfirmationsubject', 'local_obu_application');
	$username = urlencode($user->username);
	$username = str_replace('.', '%2E', $username); // Prevent problems with trailing dots
	$link = $CFG->wwwroot . '/local/obu_application/confirm.php?data=' . $user->secret . '/' . $username;
	$data->link = '<a href="' . $link . '">Confirm your account</a>';
	$message = get_string('emailconfirmation', 'local_obu_application', $data);
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

	$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
	echo $OUTPUT->header();
	echo $OUTPUT->box_start('generalbox boxwidthnormal');
	echo '<h1 class="mb-4">' . $header . '</h1>';
	echo '<p>' . $message . '</p>';
	echo '<div class="login-divider"></div>';
	echo html_writer::link($CFG->wwwroot . '/local/obu_application/', get_string('continue'), array('class'=>'btn btn-primary'));
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

function get_titles() {
	$titles = array (
		'' => 'Please select',
		'Mr' => 'Mr',
		'Mrs' => 'Mrs',
		'Miss' => 'Miss',
		'Ms' => 'Ms',
		'Dr' => 'Dr',
		'Prof' => 'Prof'
	);

	return $titles;
}

function get_areas() {
	$areas = array (
		'AF' => 'Afghanistan',
		'AX' => 'Åland Islands',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AG' => 'Antigua and Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas, The',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia [Bolivia, Plurinational State of]',
		'BQ' => 'Bonaire, Sint Eustatius and Saba',
		'BA' => 'Bosnia and Herzegovina',
		'BW' => 'Botswana',
		'BR' => 'Brazil',
		'VG' => 'British Virgin Islands [Virgin Islands, British]',
		'BN' => 'Brunei [Brunei Darussalam]',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'IC' => 'Canary Islands',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CG' => 'Congo',
		'CD' => 'Congo (Democratic Republic) [Congo (The Democratic Republic of the)]',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'HR' => 'Croatia',
		'CU' => 'Cuba',
		'CW' => 'Curaçao',
		'XA' => 'Cyprus (European Union)',
		'XB' => 'Cyprus (Non-European Union)',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'TL' => 'East Timor [Timor Leste]',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'XF' => 'England',
		'GQ' => 'Equatorial Guinea',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'SZ' => 'Eswatini',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands [Falkland Islands (Malvinas)]',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'GA' => 'Gabon',
		'GM' => 'Gambia, The',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong (Special Administrative Region of China) [Hong Kong]',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran [Iran, Islamic Republic of]',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle of Man',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'CI' => 'Ivory Coast [Côte D\'ivoire]',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KP' => 'Korea (North) [Korea, Democratic People\'s Republic of]',
		'KR' => 'Korea (South) [Korea, Republic of]',
		'QO' => 'Kosovo',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Laos [Lao People\'s Democratic Republic]',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macao (Special Administrative Region of China) [Macao]',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia [Micronesia, Federated States of]',
		'MD' => 'Moldova [Moldova, Republic of]',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'MM' => 'Myanmar (Burma) [The Republic of the Union of Myanmar]',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'AN' => 'Netherlands Antilles',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'MK' => 'North Macedonia',
		'XG' => 'Northern Ireland',
		'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		'PS' => 'Occupied Palestinian Territories [Palestine, State of]',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PW' => 'Palau',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn, Henderson, Ducie and Oeno Islands [Pitcairn]',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'RE' => 'Réunion',
		'RO' => 'Romania',
		'RU' => 'Russia [Russian Federation]',
		'RW' => 'Rwanda',
		'WS' => 'Samoa',
		'SM' => 'San Marino',
		'ST' => 'Sao Tome and Principe',
		'SA' => 'Saudi Arabia',
		'XH' => 'Scotland',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SX' => 'Sint Maarten (Dutch part)',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia and The South Sandwich Islands',
		'SS' => 'South Sudan',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'BL' => 'St Barthélemy',
		'SH' => 'St Helena, Ascension and Tristan da Cunha',
		'KN' => 'St Kitts and Nevis',
		'LC' => 'St Lucia',
		'MF' => 'St Martin (French Part) [St Martin]',
		'PM' => 'St Pierre and Miquelon',
		'VC' => 'St Vincent and The Grenadines',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard and Jan Mayen',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'SY' => 'Syria [Syrian Arab Republic]',
		'TW' => 'Taiwan [Taiwan, Province of China]',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania [Tanzania, United Republic of]',
		'TH' => 'Thailand',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad and Tobago',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks and Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'US' => 'United States',
		'VI' => 'United States Virgin Islands [Virgin Islands, U. S.]',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VA' => 'Vatican City [Holy See (Vatican City State)]',
		'VE' => 'Venezuela [Venezuela, Bolivarian Republic of]',
		'VN' => 'Vietnam [Viet Nam]',
		'XI' => 'Wales',
		'WF' => 'Wallis and Futuna',
		'EH' => 'Western Sahara',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe'
	);

	return $areas;
}

function get_nations() {
	$nations = array (
		'AF' => 'Afghanistan',
		'AX' => 'Åland Islands (Ahvenamaa)',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AG' => 'Antigua and Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas, The',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BQ' => 'Bonaire, Sint Eustatius & Saba',
		'BA' => 'Bosnia and Herzegovina',
		'BW' => 'Botswana',
		'BR' => 'Brazil',
		'BAT' => 'British Antarctic Territories',
		'IO' => 'British Indian Ocean Territory',
		'BNO' => 'British National (Overseas)',
		'BOC' => 'British Overseas Citizen',
		'BOTC' => 'British Overseas Territories',
		'GBP' => 'British Protected Person',
		'GPS' => 'British Subject',
		'VG' => 'British Virgin Islands',
		'BN' => 'Brunei (Brunei Darussalam)',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'MM' => 'Burma (Myanmar)',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'IC' => 'Canary Islands',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'TD' => 'Chad',
		'XL' => 'Channel Islands',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CG' => 'Congo',
		'CD' => 'Congo (Democratic Republic) [C',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'HR' => 'Croatia',
		'CU' => 'Cuba',
		'CW' => 'Curaçao',
		'XA' => 'Cyprus (European Union)',
		'XB' => 'Cyprus (Non-European Union)',
		'XC' => 'Cyprus not otherwise specified',
		'CZ' => 'Czech Republic',
		'XM' => 'Czechoslovakia',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'TL' => 'East Timor (Timor Leste)',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'GQ' => 'Equatorial Guinea',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France {includes Corsica}',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'XW' => 'French West Indies',
		'GA' => 'Gabon',
		'GM' => 'Gambia, The',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe (include StMartin)',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle of Man',
		'IL' => 'Israel',
		'IT' => 'Italy (includes Sardinia)',
		'CI' => 'Ivory Coast [Côte D\'ivoire]',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KP' => 'Korea (North)',
		'KR' => 'Korea (South)',
		'QO' => 'Kosovo',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Laos',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macao',
		'MK' => 'Macedonia',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia',
		'MD' => 'Moldova',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'AN' => 'Netherlands Antilles',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		'PS' => 'Occupied Palestinian Territory',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PW' => 'Palau',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn',
		'PL' => 'Poland',
		'PT' => 'Portugal (includes Madeira)',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'RE' => 'Réunion',
		'RO' => 'Romania',
		'RU' => 'Russia (Russian Federation)',
		'RW' => 'Rwanda',
		'BL' => 'Saint Barthelemy',
		'WS' => 'Samoa',
		'SM' => 'San Marino',
		'ST' => 'Sao Tome and Principe',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'QN' => 'Serbia and Montenegro',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SX' => 'Sint Maarten (Dutch Part)',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia',
		'SS' => 'South Sudan',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SH' => 'St Helena',
		'KN' => 'St Kitts and Nevis',
		'LC' => 'St Lucia',
		'PM' => 'St Pierre And Miquelon',
		'VC' => 'St Vincent and The Grenadines',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard And Jan Mayen',
		'SZ' => 'Swaziland',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'SY' => 'Syria (Syrian Arab Republic)',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad and Tobago',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks and Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom',
		'US' => 'United States',
		'UY' => 'Uruguay',
		'XN' => 'USSR not otherwise specified',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VA' => 'Vatican City',
		'VE' => 'Venezuela',
		'VN' => 'Vietnam (Viet Nam)',
		'VI' => 'Virgin Islands (US)',
		'WF' => 'Wallis & Futuna',
		'EH' => 'Western Sahara',
		'YE' => 'Yemen',
		'XO' => 'Yugoslavia not specified',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe',
		'AA' => 'Stateless'
	);

	return $nations;
}

function get_course_names() {

	$courses = array();
	$recs = get_course_records();
	foreach ($recs as $rec) {
		if ($rec->suspended == 0) {
			$courses[$rec->code] = $rec->name . ' [' . $rec->code . ']';
		}
	}

	asort($courses); // Sort by name

	return $courses;
}

function get_organisations() {

	$organisations = array();
	$recs = get_organisation_records();
	foreach ($recs as $rec) {
//		if (($rec->code != 0) && ($rec->suspended == 0)) {
		if ($rec->suspended == 0) {
			$organisations[$rec->id] = $rec->name;
		}
	}

	return $organisations;
}

function get_dates() {
	$months = [ 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC' ];
	$month = date('m');
	$year = date('y');
	$dates = array();

	for ($i = 0; $i <= 12; $i++) {
		$dates[$months[$month - 1] . $year] = $months[$month - 1] . $year;
		if ($month < 12) {
			$month++;
		} else {
			$year++;
			$month = 1;
		}
	}

	return $dates;
}

function get_course_dates() {
	$months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

	// Set the current date to the first of November 2023 for testing purposes
	$currentDate = strtotime('2024-08-01');

	// Get the current month and year based on the modified date
	$month = date('m', $currentDate);
	$year = date('y', $currentDate);

	$dates = array();

	while (count($dates) < 4) {
		if ($months[$month - 2] == 'SEP') {
			$dates[$months[$month - 2] . $year] = $months[$month - 2] . $year . " (Sem 1)";
		} elseif ($months[$month - 2] == 'JAN') {
			$dates[$months[$month - 2] . $year] = $months[$month - 2] . $year . " (Sem 2)";
		} elseif ($months[$month - 2] == 'JUN') {
			$dates[$months[$month - 2] . $year] = $months[$month - 2] . $year . " (Sem 3)";
		}

		if ($month < 12) {
			$month++;
		} else {
			$year++;
			$month = 1;
		}
	}

	return $dates;
}

function get_application_status($user_id, $application, &$text, &$button) { // Get the status from the given user's perspective

	$text = '';
	$button = '';
	$manager = is_manager();
	$administrator = is_administrator();

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
			} else if ($application->approval_level > 2) { // HLS
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
					} else if ($application->approval_state == 2) {
						$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('approved', 'local_obu_application'), 'by' => $name));
					} else {
						$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('withdrawn', 'local_obu_application'), 'by' => $name));
					}
					$text .= ' ' . $application->approval_3_comment . '<br />';
					if ($manager) {
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
				$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
				$button = 'continue';
			}
			$action = html_writer::span(get_string('awaiting_action', 'local_obu_application', array('action' => get_string('submission', 'local_obu_application'), 'by' => $name)), '',
				array('style' => 'color:red'));
			$text .= '<p />' . $action;
		} else {
			if ($application->approval_level == 1) { // Programme administrator/manager
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
					$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
				}
			} else { // HLS
				$approver = get_complete_user_data('username', 'hls');
				$name = $approver->firstname . ' ' . $approver->lastname;
			}
			if (($approver !== false) && ($approver->id == $user_id)) {
				$name = 'you';
				$button = 'approve';
			} else if (($approver !== false) && ($approver->username == 'hls') && $manager) {
				$button = 'approve';
			} else {
				$button = 'continue';
			}
			$action = html_writer::span(get_string('awaiting_action', 'local_obu_application', array('action' => get_string('approval', 'local_obu_application'), 'by' => $name)), '',
				array('style' => 'color:red'));
			$text .= '<p />' . $action;
		}
	} else if ($manager && ($application->approval_level == 3) && ($application->approval_state == 2)) { // A manager can revoke or withdraw an HLS-approved application
		$button = 'revoke';
	} else if ($administrator && ($application->approval_state == 1 || $application->approval_state == 3)) { // An administrator can reinstate a rejected application
		$button = 'reinstate';
	} else { // Application processed - nothing more to say...
		$button = 'continue';
	}
}

function update_workflow(&$application, $approved = true, $data = null) {

	$approver_email = '';

	// Update the application record
	if (($application->approval_level == 0) && ($application->manager_email != '')) { // Submitter (with a programme administrator/manager)
		$application->approval_level = 1;
		$approver_email = $application->manager_email;
	} else if ($application->approval_level <= 1) { // Submitter (without a programme administrator/manager) or administrator/manager
		if ($application->approval_level == 1) { // Programme administrator/manager
			$application->approval_1_comment = $data->comment;
			$application->approval_1_date = time();
		}
		if (!$approved) {
			$application->approval_state = 1; // Rejected
		} else if ($application->approval_state == 1) {
			$application->approval_1_comment = '';
			$application->approval_1_date = 0; // Reinstated
			$application->approval_state = 0;
			$approver_email = $application->manager_email;
		} else if ($application->self_funding == 0) {
			$application->approval_level = 2; // Funder
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
		} else if ($application->approval_state == 1) {
			$application->approval_2_comment = '';
			$application->approval_2_date = 0; // Reinstated
			$application->approval_state = 0;
			$approver_email = $application->funder_email;
		} else {
			$application->approval_level = 3; // Brookes

			// Store the funding details
			if ($application->funding_organisation != '') { // NHS trust (previously selected by the applicant)
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
	} else { // HLS
		if ($application->approval_state == 0) { // Not yet approved or rejected
			$application->approval_3_comment = $data->comment;
			$application->approval_3_date = time();
			if ($approved) { // Approved
				$application->approval_state = 2;
			} else { // Rejected
				$application->approval_state = 1;
			}
		} else { // Already approved/rejected so must be revoking or withdrawing
			$application->approval_3_comment = '';
			if ($approved && ($application->approval_state == 1 || $application->approval_state == 3)) {
				$application->approval_3_date = 0; // Reinstated
				$application->approval_state = 0;
				$hls = get_complete_user_data('username', 'hls');
				$approver_email = $hls->email;
			}
			else if ($approved) { // Revoked
				$application->approval_state = 0;
				$application->approval_3_date = 0;
				$application->admissions_xfer = 0;
				$application->finance_xfer = 0;
			} else { // Withdrawn
				$application->approval_state = 3;
				$application->approval_3_date = time();
			}
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
//		email_to_user($hls, $applicant, 'Status Update: ' . $application->course_code . ' ' . $application->course_name . ' (' . $applicant->firstname . ' ' . $applicant->lastname . ')',
//			html_to_text($html), $html);
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
