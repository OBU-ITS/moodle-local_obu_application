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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require_once($CFG->libdir . '/password_compat/lib/password.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/local/obu_application/db_update.php');

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
	$user->id = user_create_user($user, false, false);
	
	// Save any custom profile field information
	profile_save_data($user);
	
	// Trigger event
	\core\event\user_created::create_from_userid($user->id)->trigger();
	
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

	$supportuser = core_user::get_support_user();

	$data = new stdClass();
	$data->firstname = fullname($user);
	$data->sitename = format_string($CFG->pageheading);
	$data->admin = generate_email_signoff();

	$subject = get_string('emailconfirmationsubject', '', $data->sitename);
	$username = urlencode($user->username);
	$username = str_replace('.', '%2E', $username); // Prevent problems with trailing dots.
	$data->link = $CFG->wwwroot . '/local/obu_application/confirm.php?data=' . $user->secret . '/' . $username;
	$message = get_string('emailconfirmation', '', $data);
	$messagehtml = text_to_html($message, false, false, true);

	$user->mailformat = 1;  // Always send HTML version as well.

	// Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
	return email_to_user($user, $supportuser, $subject, $message, $messagehtml);
}

function authenticate_application_user($username, $password, $ignorelockout = false, &$failurereason = null) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/authlib.php');

    if ($user = get_complete_user_data('username', $username, $CFG->mnet_localhost_id)) {
        // we have found the user
    } else if ($email = clean_param($username, PARAM_EMAIL)) {
		$select = "mnethostid = :mnethostid AND LOWER(email) = LOWER(:email) AND deleted = 0";
		$params = array('mnethostid' => $CFG->mnet_localhost_id, 'email' => $email);
		$users = $DB->get_records_select('user', $select, $params, 'id', 'id', 0, 2);
		if (count($users) === 1) {
			// Use email for login only if unique.
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
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Suspended Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            return false;
        }
        if ($auth=='nologin' or !is_enabled_auth($auth)) {
            // Legacy way to suspend user.
            $failurereason = AUTH_LOGIN_SUSPENDED;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Disabled Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            return false;
        }
        $auths = array($auth);

    } else {
        // Check if there's a deleted record (cheaply), this should not happen because we mangle usernames in delete_user().
        if ($DB->get_field('user', 'id', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id,  'deleted' => 1))) {
            $failurereason = AUTH_LOGIN_NOUSER;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                    'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Deleted Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
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
                $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                        'reason' => $failurereason)));
                $event->trigger();

                error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Unknown user, can not create new accounts:  $username  ".
                        $_SERVER['HTTP_USER_AGENT']);
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

function require_obu_login($courseorid = null, $autologinguest = true, $cm = null, $setwantsurltome = true, $preventredirect = false) {
    global $CFG, $SESSION, $USER, $PAGE, $SITE, $DB, $OUTPUT;
	
	$login_url = '/local/obu_application/login.php';

    // Must not redirect when byteserving already started.
    if (!empty($_SERVER['HTTP_RANGE'])) {
        $preventredirect = true;
    }

	// Do not touch global $COURSE via $PAGE->set_course(),
	// the reasons is we need to be able to call require_obu_login() at any time!!
	$course = $SITE;

    // If this is an AJAX request and $setwantsurltome is true then we need to override it and set it to false.
    // Otherwise the AJAX request URL will be set to $SESSION->wantsurl and events such as self enrolment in the future
    // risk leading the user back to the AJAX request URL.
    if ($setwantsurltome && defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
        $setwantsurltome = false;
    }

    // Redirect to the login page if session has expired, only with dbsessions enabled (MDL-35029) to maintain current behaviour.
    if ((!isloggedin() or isguestuser()) && !empty($SESSION->has_timed_out) && !$preventredirect && !empty($CFG->dbsessions)) {
        if ($setwantsurltome) {
            $SESSION->wantsurl = qualified_me();
        }
        redirect($login_url);
    }

    // If the user is not even logged in yet then make sure they are.
    if (!isloggedin()) {
        if ($autologinguest and !empty($CFG->guestloginbutton) and !empty($CFG->autologinguests)) {
            if (!$guest = get_complete_user_data('id', $CFG->siteguest)) {
                // Misconfigured site guest, just redirect to login page.
                redirect($login_url);
                exit; // Never reached.
            }
            $lang = isset($SESSION->lang) ? $SESSION->lang : $CFG->lang;
            complete_user_login($guest);
            $USER->autologinguest = true;
            $SESSION->lang = $lang;
        } else {
            // NOTE: $USER->site check was obsoleted by session test cookie, $USER->confirmed test is in login/index.php.
            if ($preventredirect) {
                throw new require_login_exception('You are not logged in');
            }

            if ($setwantsurltome) {
                $SESSION->wantsurl = qualified_me();
            }
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $SESSION->fromurl  = $_SERVER['HTTP_REFERER'];
            }
            redirect($login_url);
            exit; // Never reached.
        }
    }

    // Make sure the USER has a sesskey set up. Used for CSRF protection.
    sesskey();

    // Do not bother admins with any formalities.
    if (is_siteadmin()) {
        // Set accesstime or the user will appear offline which messes up messaging.
        user_accesstime_log($course->id);
        return;
    }

    // Fetch the system context, the course context, and prefetch its child contexts.
    $sysctx = context_system::instance();
    $coursecontext = context_course::instance($course->id, MUST_EXIST);
	$cmcontext = null;

    // If the site is currently under maintenance, then print a message.
    if (!empty($CFG->maintenance_enabled) and !has_capability('moodle/site:config', $sysctx)) {
        if ($preventredirect) {
            throw new require_login_exception('Maintenance in progress');
        }
        print_maintenance_message();
    }

    // Finally access granted, update lastaccess times.
    user_accesstime_log($course->id);
}

function get_application_status($user_id, $application, &$text, &$button) { // Get the status from the given user's perspective

	$text = '';
	$button = '';
	$context = context_system::instance();
	$manager = has_capability('local/obu_application:manage', $context);
	
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
			$approver = get_complete_user_data('email', $application->manager_email);
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
			$text .= ' ' . $application->approval_1_notes . '<br />';
		} else if ($application->approval_level > 1) {
			date_timestamp_set($date, $application->approval_1_date);
			$text .= date_format($date, $format) . ' ';
			$approver = get_complete_user_data('email', $application->manager_email);
			if ($approver === false) {
				$name = $application->manager_email;
			} else if ($approver->id == $user_id) {
				$name = 'you';
			} else {
				$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
			}
			$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('approved', 'local_obu_application'), 'by' => $name));
			$text .= ' ' . $application->approval_1_notes . '<br />';
			if (($application->approval_level == 2) && ($application->approval_state > 0)) { // The workflow ended here
				date_timestamp_set($date, $application->approval_2_date);
				$text .= date_format($date, $format) . ' ';
				$approver = get_complete_user_data('email', $application->tel_email);
				if ($approver === false) {
					$name = $application->tel_email;
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
				$text .= ' ' . $application->approval_2_notes . '<br />';
			} else if ($application->approval_level > 2) {
				date_timestamp_set($date, $application->approval_2_date);
				$text .= date_format($date, $format) . ' ';
				$approver = get_complete_user_data('email', $application->tel_email);
				if ($approver === false) {
					$name = $application->tel_email;
				} else if ($approver->id == $user_id) {
					$name = 'you';
				} else {
					$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
				}
				$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('approved', 'local_obu_application'), 'by' => $name));
				$text .= ' ' . $application->approval_2_notes . '<br />';
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
						$text .= get_string('actioned_by', 'local_obu_application', array('action' => get_string('approved', 'local_obu_forms'), 'by' => $name));
					}
					$text .= ' ' . $application->approval_3_notes . '<br />';
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
			$text .= '<p />' . get_string('awaiting_action', 'local_obu_application', array('action' => get_string('submission', 'local_obu_application'), 'by' => $name));
		} else {
			if ($application->approval_level == 1) {
				$approver = get_complete_user_data('email', $application->manager_email);
				if ($approver === false) {
					$name = $application->manager_email;
				} else {
					$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
				}
			} else if ($application->approval_level == 2) {
				$approver = get_complete_user_data('email', $application->tel_email);
				if ($approver === false) {
					$name = $application->tel_email;
				} else {
					$name = $approver->firstname . ' ' . $approver->lastname;
				}
			} else {
				$approver = get_complete_user_data('username', 'hls');
				$name = $approver->firstname . ' ' . $approver->lastname . ' (' . $approver->email . ')';
			}
			if (($approver !== false) && ($approver->id == $user_id)) {
				$name = 'you';
				$button = 'approve';
			} else if (($name == 'HLS Team') && $manager) {
				$button = 'approve';
			} else {
				$button = 'continue';
			}
			$text .= '<p />' . get_string('awaiting_action', 'local_obu_application', array('action' => get_string('approval', 'local_obu_application'), 'by' => $name));
		}
	} else { // Application processed - nothing more to say...
		$button = 'continue';
	}
}

function update_workflow(&$application, $approved = true, $comment = null) {

	$approver_email = '';
	
	// Update the application record
	if ($application->approval_level == 0) { // Being submitted
		$application->approval_level = 1;
		$approver_email = $application->manager_email;
	} else if ($record->approval_level == 1) {
		$application->approval_1_notes = $comment;
		$application->approval_1_date = time();
		if (!$approved) {
			$application->approval_state = 1; // Rejected
		} else if ($application->self_funding == 0) {
			$application->approval_level = 2;
			$approver_email = $application->tel_email;
		} else {
			$application->approval_level = 3; // Brookes
			$hls = get_complete_user_data('username', 'hls');
			$approver_email = $hls->email;
		}
	} else if ($application->approval_level == 2) {
		$application->approval_2_notes = $comment;
		$application->approval_2_date = time();
		if (!$approved) {
			$application->approval_state = 1; // Rejected
		} else {
			$application->approval_level = 3; // Brookes
			$hls = get_complete_user_data('username', 'hls');
			$approver_email = $hls->email;
		}
	} else {
		$application->approval_3_notes = $comment;
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
		$approval->approver = $approver_email;
		$approval->request_date = time();
		write_approval($approval);
	}
	
	// Determine the URL to use to link to the application
	$process = new moodle_url('/local/obu_application/process.php') . '?id=' . $application->id;
	$mdl_process = new moodle_url('/local/obu_application/mdl_process.php') . '?id=' . $application->id; // 'Mainstream' Moodle

	// Email the new status to the applicant and to the HLS Team (if not the next approver)
	$applicant = get_complete_user_data('id', $application->userid);
	$hls = get_complete_user_data('username', 'hls');
	get_application_status($applicant->id, $application, $text, $button_text); // get the status from the applicant's perspective
	$html = '<h4><a href="' . $process . '">HLS Application (Ref No ' . $application->id . ')</a></h4>' . $text;
	email_to_user($applicant, $hls, 'The Status of Your HLS Application (Ref No ' . $application->id . ')', html_to_text($html), $html);
	if ($approver_email != $hls->email) {
		get_application_status($hls->id, $application, $text, $button_text); // get the status from the HLS's perspective
		$html = '<h4><a href="' . $mdl_process . '">HLS Application (Ref No ' . $application->id . ')</a></h4>' . $text;
		email_to_user($hls, $applicant, 'Application ' . $application->id . ' Status Update (' . $applicant->firstname . ' ' . $applicant->lastname . ')', html_to_text($html), $html);
	}
	
	// Notify the next approver (if there is one)
	if ($approver_email != '') {
		if (strpos($process, 'moodle.brookes') === false) {
			$approver_email = $hls->email; // Send all approval emails to the HLS Team if we aren't 'live'
		}
		$approver = get_complete_user_data('email', $approver_email);
		if ($approver->email == $hls->email) { // HLS will use 'mainstream' Moodle for their approvals
			$link = '<a href="' . $mdl_process . '">HLS Application (Ref No ' . $application->id . ')</a>';
		} else {
			$link = '<a href="' . $process . '">HLS Application (Ref No ' . $application->id . ')</a>';
		}
		$html = get_string('request_approval', 'local_obu_application', $link);
		email_to_user($approver, $applicant, 'Request for HLS Application Approval (' . $applicant->firstname . ' ' . $applicant->lastname . ')', html_to_text($html), $html);
	}
}

?>
