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
 * OBU Application - Set password form
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham (derived from '/login/set_password_form.php')
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class login_set_password_form extends moodleform {

    /**
     * Define the set password form
     */
    public function definition() {
        $mform = $this->_form;

        // Include the username in the form so browsers will recognise that a password is being set.
        $mform->addElement('text', 'username', '', 'style="display: none;"');
        $mform->setType('username', PARAM_RAW);
        // Token gives authority to change password.
        $mform->addElement('hidden', 'token', '');
        $mform->setType('token', PARAM_ALPHANUM);

        // Visible elements.
        $mform->addElement('text', 'username2', get_string('email'), 'disabled size="40" maxlength="100"');

        $mform->addElement('passwordunmask', 'password', get_string('newpassword', 'local_obu_application'), 'size="40" maxlength="32"');
        $mform->setType('password', PARAM_RAW);

        $mform->addElement('passwordunmask', 'password2', get_string('confirmnewpassword', 'local_obu_application'), 'size="40" maxlength="32"');
        $mform->setType('password2', PARAM_RAW);

        $this->add_action_buttons(true, get_string('setpassword'));
    }

    /**
     * Perform extra password change validation.
     * @param array $data submitted form fields.
     * @param array $files submitted with the form.
     * @return array errors occuring during validation.
     */
    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);

        if (empty($data['password']))
            $errors['password'] = get_string('password_required', 'local_obu_application');

        if (empty($data['password2']))
            $errors['password2'] = get_string('confirm_password_required', 'local_obu_application');

        if ($data['password'] !== $data['password2']) {
            $errors['password2'] = get_string('passwordsdiffer');
            return $errors;
        }

        $error_message = '';
        if (!check_password_policy($data['password'], $error_message)) {
            $errors['password'] = $error_message;
            return $errors;
        }

        return $errors;
    }
}
