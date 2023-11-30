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
 * OBU Application - User registration (signup) form
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham (derived from '/login/signup_form.php')
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class registration_form extends moodleform {
    function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->

        $data = new stdClass();
		$data->titles = $this->_customdata['titles'];
        $data->email_label = $this->_customdata['email_label'];
        $data->show_email_notification = $this->_customdata['show_email_notification'];

        $mform->addElement('select', 'title', get_string('title', 'local_obu_application'), $data->titles);
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('text', 'firstname', get_string('firstnames', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('firstname', PARAM_TEXT);

        $mform->addElement('text', 'lastname', get_string('lastname'), 'size="40" maxlength="100"');
        $mform->setType('lastname', PARAM_TEXT);

        if($data->show_email_notification) {
            $mform->addElement('html', get_string('emailnotification', 'local_obu_application'));
        }

		$mform->addElement('text', 'email', $data->email_label, 'size="40" maxlength="100"');
		$mform->setType('email', PARAM_RAW_TRIMMED);
        $mform->addRule('email', get_string('invalid_email', 'local_obu_application'), 'email', null, 'client');

		$mform->addElement('text', 'username', get_string('confirm_email', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('username', PARAM_RAW_TRIMMED);
        $mform->addRule('username', get_string('invalid_email', 'local_obu_application'), 'email', null, 'client');
		$mform->addRule(array('username','email'), get_string('invalid_email_compare', 'local_obu_application'), 'compare', 'eq', 'client');

        $mform->addElement('passwordunmask', 'password', get_string('password'), 'size="40" maxlength="32"');
        $mform->setType('password', PARAM_RAW);

        // Use reCAPTCHA if it's setup
		if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey)) {
			$mform->addElement('recaptcha', 'recaptcha_element', get_string('recaptcha', 'auth'), array('https' => $CFG->loginhttps));
			$mform->addHelpButton('recaptcha_element', 'recaptcha', 'auth');
		}

        if (!empty($CFG->sitepolicy)) {
            $mform->addElement('header', 'policyagreement', get_string('policyagreement'), '');
            $mform->setExpanded('policyagreement');
            $mform->addElement('static', 'policylink', '', '<a href="'.$CFG->sitepolicy.'" onclick="this.target=\'_blank\'">'.get_String('policyagreementclick').'</a>');
            $mform->addElement('checkbox', 'policyagreed', get_string('policyaccept'));
            $mform->addRule('policyagreed', get_string('policyagree'), 'required', null, 'server');
        }

        // buttons
        $this->add_action_buttons(true, get_string('register', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if (empty($data['title']))
            $errors['title'] = get_string('title_required', 'local_obu_application');

        if (empty($data['firstname']))
            $errors['firstname'] = get_string('firstname_required', 'local_obu_application');

        if (empty($data['lastname']))
            $errors['lastname'] = get_string('lastname_required', 'local_obu_application');

        if (empty($data['email']))
            $errors['email'] = get_string('email_required', 'local_obu_application');
        else if (!validate_email($data['email']) || ($data['email'] != strtolower($data['email'])))
            $errors['email'] = get_string('invalid_email', 'local_obu_application');

        if (empty($data['username']))
            $errors['username'] = get_string('username_required', 'local_obu_application');
        else if (!validate_email($data['username']) || ($data['username'] != strtolower($data['username'])))
            $errors['username'] = get_string('invalid_email', 'local_obu_application');

        if(!empty($data['email']) && !empty($data['username']) && $data['username'] != $data['email'])
            $errors['username'] = get_string('invalid_email_compare', 'local_obu_application');

        if (empty($data['password']))
            $errors['password'] = get_string('password_required', 'local_obu_application');
        else {
            $errmsg = '';
            if (!check_password_policy($data['password'], $errmsg))
                $errors['password'] = $errmsg;
        }

		if (!isset($errors['email'])) {
			if ($DB->record_exists('user', array('email' => $data['email']))) {
				$errors['email'] = get_string('emailexists') . ' <a href="forgot_password.php">' . get_string('newpassword') . '?</a>';
			}
		}

        // If reCAPTCHA is setup we would have used it - so check it!
		if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey)) {
            $recaptcha_element = $this->_form->getElement('recaptcha_element');
            if (!empty($this->_form->_submitValues['recaptcha_challenge_field'])) {
                $challenge_field = $this->_form->_submitValues['recaptcha_challenge_field'];
                $response_field = $this->_form->_submitValues['recaptcha_response_field'];
                if (true !== ($result = $recaptcha_element->verify($challenge_field, $response_field))) {
                    $errors['recaptcha'] = $result;
                }
            } else {
                $errors['recaptcha'] = get_string('missingrecaptchachallengefield');
            }
        }

        return $errors;
    }
}
