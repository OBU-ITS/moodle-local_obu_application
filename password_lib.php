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
 * OBU Application - Password library functions
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham (derived from '/login/lib.php')
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

function password_reset_request() {
    global $DB, $OUTPUT, $CFG, $PAGE;
	
    $systemcontext = context_system::instance();
    $mform = new login_forgot_password_form();

    if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot . '/local/obu_application/');
    } else if ($data = $mform->get_data()) {
        // Requesting user has submitted form data.
        // Next find the user account in the database which the requesting user claims to own.

		$select = $DB->sql_like('email', ':email', false, true, false, '|') . " AND mnethostid = :mnethostid AND deleted = 0 AND suspended = 0";
		$params = array('email' => $DB->sql_like_escape($data->email, '|'), 'mnethostid' => $CFG->mnet_localhost_id);
		$user = $DB->get_record_select('user', $select, $params, '*', IGNORE_MULTIPLE);

        // Target user details have now been identified, or we know that there is no such account.
        // Send email address to account's email address if appropriate.
        $pwresetstatus = PWRESET_STATUS_NOEMAILSENT;
        if ($user and !empty($user->confirmed)) {
            $userauth = get_auth_plugin($user->auth);
            if (!$userauth->can_reset_password() or !has_capability('moodle/user:changeownpassword', $systemcontext, $user->id)) {
                if (email_password_change_info($user)) {
                    $pwresetstatus = PWRESET_STATUS_OTHEREMAILSENT;
                } else {
                    print_error('cannotmailconfirm');
                }
            } else {
                // The account the requesting user claims to be is entitled to change their password.
                // Next, check if they have an existing password reset in progress.
                $resetinprogress = $DB->get_record('user_password_resets', array('userid' => $user->id));
                if (empty($resetinprogress)) {
                    // Completely new reset request - common case.
                    $resetrecord = generate_password_reset($user);
                    $sendemail = true;
                } else if ($resetinprogress->timerequested < (time() - $CFG->pwresettime)) {
                    // Preexisting, but expired request - delete old record & create new one.
                    // Uncommon case - expired requests are cleaned up by cron.
                    $DB->delete_records('user_password_resets', array('id' => $resetinprogress->id));
                    $resetrecord = generate_password_reset($user);
                    $sendemail = true;
                } else if (empty($resetinprogress->timererequested)) {
                    // Preexisting, valid request. This is the first time user has re-requested the reset.
                    // Re-sending the same email once can actually help in certain circumstances
                    // eg by reducing the delay caused by greylisting.
                    $resetinprogress->timererequested = time();
                    $DB->update_record('user_password_resets', $resetinprogress);
                    $resetrecord = $resetinprogress;
                    $sendemail = true;
                } else {
                    // Preexisting, valid request. User has already re-requested email.
                    $pwresetstatus = PWRESET_STATUS_ALREADYSENT;
                    $sendemail = false;
                }

                if ($sendemail) {
                    $sendresult = email_password_change_confirmation($user, $resetrecord);
                    if ($sendresult) {
                        $pwresetstatus = PWRESET_STATUS_TOKENSENT;
                    } else {
                        print_error('cannotmailconfirm');
                    }
                }
            }
        }

        // Any email has now been sent.
        // Next display results to requesting user if settings permit.
        echo $OUTPUT->header();

        if (!empty($CFG->protectusernames)) {
            // Neither confirm, nor deny existance of any username or email address in database.
            // Print general (non-commital) message.
            notice(get_string('emailpasswordconfirmmaybesent'), $CFG->wwwroot . '/local/obu_application/index.php');
            die; // Never reached.
        } else if (empty($user)) {
            // Protect usernames is off, and we couldn't find the user with details specified.
            // Print failure advice.
            notice(get_string('emailpasswordconfirmnotsent'), $CFG->wwwroot . '/local/obu_application/forgot_password.php');
            die; // Never reached.
        } else if (empty($user->email)) {
            // User doesn't have an email set - can't send a password change confimation email.
            notice(get_string('emailpasswordconfirmnoemail'), $CFG->wwwroot . '/local/obu_application/index.php');
            die; // Never reached.
        } else if ($pwresetstatus == PWRESET_STATUS_ALREADYSENT) {
            // User found, protectusernames is off, but user has already (re) requested a reset.
            // Don't send a 3rd reset email.
            $stremailalreadysent = get_string('emailalreadysent');
            notice($stremailalreadysent, $CFG->wwwroot . '/local/obu_application/index.php');
            die; // Never reached.
        } else if ($pwresetstatus == PWRESET_STATUS_NOEMAILSENT) {
            // User found, protectusernames is off, but user is not confirmed.
            // Pretend we sent them an email.
            // This is a big usability problem - need to tell users why we didn't send them an email.
            // Obfuscate email address to protect privacy.
            $protectedemail = preg_replace('/([^@]*)@(.*)/', '******@$2', $user->email);
            $stremailpasswordconfirmsent = get_string('emailpasswordconfirmsent', '', $protectedemail);
            notice($stremailpasswordconfirmsent, $CFG->wwwroot . '/local/obu_application/index.php');
            die; // Never reached.
        } else {
            // Confirm email sent. (Obfuscate email address to protect privacy).
            $protectedemail = preg_replace('/([^@]*)@(.*)/', '******@$2', $user->email);
            // This is a small usability problem - may be obfuscating the email address which the user has just supplied.
            $stremailresetconfirmsent = get_string('emailresetconfirmsent', '', $protectedemail);
            notice($stremailresetconfirmsent, $CFG->wwwroot . '/local/obu_application/index.php');
            die; // Never reached.
        }
        die; // Never reached.
    }

    // Make sure we really are on the https page when https login required.
    $PAGE->verify_https_required();

    // DISPLAY FORM.

    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('passwordforgotteninstructions', 'local_obu_application'), 'generalbox boxwidthnormal boxaligncenter');
    $mform->display();

    echo $OUTPUT->footer();
}

/**
 * This function processes a user's submitted token to validate the request to set a new password.
 * If the user's token is validated, they are prompted to set a new password.
 * @param string $token the one-use identifier which should verify the password reset request as being valid.
 * @return void
 */
function password_set($token) {
    global $DB, $CFG, $OUTPUT, $PAGE, $SESSION;
	
    $pwresettime = isset($CFG->pwresettime) ? $CFG->pwresettime : 1800;
    $sql = "SELECT u.*, upr.token, upr.timerequested, upr.id as tokenid
		FROM {user} u
		JOIN {user_password_resets} upr ON upr.userid = u.id
		WHERE upr.token = ?";
    $user = $DB->get_record_sql($sql, array($token));

    $forgotpasswordurl = $CFG->httpswwwroot . '/local/obu_application/forgot_password.php';
    if (empty($user) or ($user->timerequested < (time() - $pwresettime - DAYSECS))) {
        // There is no valid reset request record - not even a recently expired one.
        // (suspicious)
        // Direct the user to the forgot password page to request a password reset.
        echo $OUTPUT->header();
        notice(get_string('noresetrecord'), $forgotpasswordurl);
        die; // Never reached.
    }
    if ($user->timerequested < (time() - $pwresettime)) {
        // There is a reset record, but it's expired.
        // Direct the user to the forgot password page to request a password reset.
        $pwresetmins = floor($pwresettime / MINSECS);
        echo $OUTPUT->header();
        notice(get_string('resetrecordexpired', '', $pwresetmins), $forgotpasswordurl);
        die; // Never reached.
    }

    if ($user->auth === 'nologin') {
        // Bad luck - user is not able to login, do not let them set password.
        echo $OUTPUT->header();
        print_error('forgotteninvalidurl');
        die; // Never reached.
    }

    // Check this isn't guest user.
    if (isguestuser($user)) {
        print_error('cannotresetguestpwd');
    }

    // Token is correct, and unexpired.
    $mform = new login_set_password_form(null, null, 'post', '', 'autocomplete="yes"');
    $data = $mform->get_data();
    if (empty($data)) {
        // User hasn't submitted form, they got here directly from email link.
        // Next, display the form.
        $setdata = new stdClass();
        $setdata->username = $user->username;
        $setdata->username2 = $user->username;
        $setdata->token = $user->token;
        $mform->set_data($setdata);
        $PAGE->verify_https_required();
        echo $OUTPUT->header();
        echo $OUTPUT->box(get_string('setpasswordinstructions'), 'generalbox boxwidthnormal boxaligncenter');
        $mform->display();
        echo $OUTPUT->footer();
        return;
    } else {
        // User has submitted form.
        // Delete this token so it can't be used again.
        $DB->delete_records('user_password_resets', array('id' => $user->tokenid));
        $userauth = get_auth_plugin($user->auth);
        if (!$userauth->user_update_password($user, $data->password)) {
            print_error('errorpasswordupdate', 'auth');
        }
        // Reset login lockout (if present) before a new password is set.
        login_unlock_account($user);
        // Clear any requirement to change passwords.
        unset_user_preference('auth_forcepasswordchange', $user);
        unset_user_preference('create_password', $user);

        if (!empty($user->lang)) {
            // Unset previous session language - use user preference instead.
            unset($SESSION->lang);
        }
        complete_user_login($user); // Triggers the login event.

        $urltogo = get_return_url();
        unset($SESSION->wantsurl);
        redirect($urltogo, get_string('passwordset'), 1);
    }
}

/** Create a new record in the database to track a new password set request for user.
 * @param object $user the user record, the requester would like a new password set for.
 * @return record created.
 */
function generate_password_reset ($user) {
    global $DB;
	
    $resetrecord = new stdClass();
    $resetrecord->timerequested = time();
    $resetrecord->userid = $user->id;
    $resetrecord->token = random_string(32);
    $resetrecord->id = $DB->insert_record('user_password_resets', $resetrecord);
	
    return $resetrecord;
}

/**
 * Sends a password change confirmation email.
 *
 * @param stdClass $user A {@link $USER} object
 * @param stdClass $resetrecord An object tracking metadata regarding password reset request
 * @return bool Returns true if mail was sent OK and false if there was an error.
 */
function email_password_change_confirmation($user, $resetrecord) {
    global $CFG;

    $supportuser = core_user::get_support_user();
    $pwresetmins = isset($CFG->pwresettime) ? floor($CFG->pwresettime / MINSECS) : 30;

    $data = new stdClass();
    $data->firstname = $user->firstname;
    $data->lastname = $user->lastname;
    $data->username = $user->username;
    $data->sitename = format_string(get_string('plugintitle', 'local_obu_application'));
    $data->link = $CFG->httpswwwroot . '/local/obu_application/forgot_password.php?token=' . $resetrecord->token;
    $data->admin = generate_email_signoff();
    $data->resetminutes = $pwresetmins;

    $message = get_string('emailresetconfirmation', '', $data);
    $subject = get_string('emailresetconfirmationsubject', '', $data->sitename);

    // Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
    return email_to_user($user, $supportuser, $subject, $message);
}

/**
 * Sends an email containinginformation on how to change your password.
 *
 * @param stdClass $user A {@link $USER} object
 * @return bool Returns true if mail was sent OK and false if there was an error.
 */
function email_password_change_info($user) {
    global $CFG;

    $supportuser = core_user::get_support_user();
    $systemcontext = context_system::instance();

    $data = new stdClass();
    $data->firstname = $user->firstname;
    $data->lastname = $user->lastname;
    $data->sitename = format_string(get_string('plugintitle', 'local_obu_application'));
    $data->admin = generate_email_signoff();

    $userauth = get_auth_plugin($user->auth);

    if (!is_enabled_auth($user->auth) or $user->auth == 'nologin') {
        $message = get_string('emailpasswordchangeinfodisabled', '', $data);
        $subject = get_string('emailpasswordchangeinfosubject', '', $data->sitename);
        // Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
        return email_to_user($user, $supportuser, $subject, $message);
    }

    if ($userauth->can_change_password() and $userauth->change_password_url()) {
        // We have some external url for password changing.
        $data->link .= $userauth->change_password_url();

    } else {
        // No way to change password, sorry.
        $data->link = '';
    }

    if (!empty($data->link) and has_capability('moodle/user:changeownpassword', $systemcontext, $user->id)) {
        $message = get_string('emailpasswordchangeinfo', '', $data);
        $subject = get_string('emailpasswordchangeinfosubject', '', $data->sitename);
    } else {
        $message = get_string('emailpasswordchangeinfofail', '', $data);
        $subject = get_string('emailpasswordchangeinfosubject', '', $data->sitename);
    }

    // Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
    return email_to_user($user, $supportuser, $subject, $message);
}
