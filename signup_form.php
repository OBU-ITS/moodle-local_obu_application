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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class registration_form extends moodleform {
    function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'emailandpassword', get_string('emailandpassword', 'local_obu_application'), '');

        $mform->addElement('text', 'username', get_string('email'), 'size="25" maxlength="100"');
        $mform->setType('username', PARAM_RAW_TRIMMED);
        $mform->addRule('username', get_string('missingemail'), 'required', null, 'server');

        $mform->addElement('text', 'email', get_string('emailagain'), 'size="25" maxlength="100"');
        $mform->setType('email', PARAM_RAW_TRIMMED);
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'server');

        if (!empty($CFG->passwordpolicy)){
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('passwordunmask', 'password', get_string('password'), 'size="12" maxlength="32"');
        $mform->setType('password', PARAM_RAW);
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'server');

        $mform->addElement('header', 'supplyinfo', get_string('supplyinfo'),'');

		$mform->addElement('text', 'idnumber', get_string('title', 'local_obu_application'), 'size="30" maxlength="100"');
		$mform->setType('idnumber', PARAM_TEXT);
		$mform->addRule('idnumber', null, 'required', null, 'server');
		
		$mform->addElement('text', 'firstname', get_string('firstname'), 'size="30" maxlength="100"');
		$mform->setType('firstname', PARAM_TEXT);
		$mform->addRule('firstname', null, 'required', null, 'server');
		
		$mform->addElement('text', 'lastname', get_string('lastname'), 'size="30" maxlength="100"');
		$mform->setType('lastname', PARAM_TEXT);
		$mform->addRule('lastname', null, 'required', null, 'server');
		
		$mform->addElement('text', 'phone1', get_string('phone', 'local_obu_application'), 'size="30" maxlength="100"');
		$mform->setType('phone1', PARAM_TEXT);
		$mform->addRule('phone1', null, 'required', null, 'server');

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

        if (!validate_email($data['username'])) {
            $errors['username'] = get_string('invalidemail');
        } else if ($DB->record_exists('user', array('email' => $data['username']))) {
            $errors['username'] = get_string('emailexists') . ' <a href="forgot_password.php">' . get_string('newpassword') . '?</a>';
        }
		
        if (empty($data['email'])) {
            $errors['email'] = get_string('missingemail');
        } else if ($data['email'] != $data['username']) {
            $errors['email'] = get_string('invalidemail');
        }
		
        $errmsg = '';
        if (!check_password_policy($data['password'], $errmsg)) {
            $errors['password'] = $errmsg;
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
