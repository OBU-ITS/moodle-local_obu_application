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

        $data = new stdClass();
		$data->titles = $this->_customdata['titles'];
        $data->email_label = $this->_customdata['email_label'];
        $data->show_email_notification = $this->_customdata['show_email_notification'];

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');


        $mform->addElement('select', 'title', get_string('title', 'local_obu_application'), $data->titles);

        $mform->addElement('text', 'firstname', get_string('firstnames', 'local_obu_application'), 'size="30" maxlength="100"');
        $mform->setType('firstname', PARAM_TEXT);

        $mform->addElement('text', 'lastname', get_string('lastname'), 'size="30" maxlength="100"');
        $mform->setType('lastname', PARAM_TEXT);

        if($data->show_email_notification) {
            $mform->addElement('html', '<p><strong>Please enter a contact email address (not NHS or previous Brookes addresses due to firewall restrictions)</strong></p>');
        }

		$mform->addElement('text', 'email', $data->email_label, 'size="30" maxlength="100"');
		$mform->setType('email', PARAM_RAW_TRIMMED);
        $mform->addRule('email', 'Invalid email address', 'email', null, 'client');

		$mform->addElement('text', 'username', get_string('confirm_email', 'local_obu_application'), 'size="30" maxlength="100"');
		$mform->setType('username', PARAM_RAW_TRIMMED);
        $mform->addRule('username', 'Invalid email address', 'email', null, 'client');
		$mform->addRule(array('username','email'), "Emails do not match", 'compare', 'eq', 'client');

        $mform->addElement('passwordunmask', 'password', get_string('password'), 'size="30" maxlength="32"');
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

		if (!validate_email($data['email']) || ($data['email'] != strtolower($data['email']))) {
			$errors['email'] = get_string('invalidemail');
		}

		if (empty($data['username'])) {
			$errors['username'] = get_string('missingemail');
		} else if ($data['username'] != $data['email']) {
			$errors['username'] = get_string('invalidemail');
}
		if (!isset($errors['email'])) {
			if ($DB->record_exists('user', array('email' => $data['email']))) {
				$errors['email'] = get_string('emailexists') . ' <a href="forgot_password.php">' . get_string('newpassword') . '?</a>';
			}
		}

        $errmsg = '';
        if (!check_password_policy($data['password'], $errmsg)) {
            $errors['password'] = $errmsg;
        }

//		if ((!$data['phone1'] || $data['phone1'] == '') && (!$data['phone2'] || $data['phone2'] == '')) {
//            $errors['phone1'] = get_string('no_phone', 'local_obu_application');
//		}

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

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
