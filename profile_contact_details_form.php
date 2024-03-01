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
 * OBU Application - Contact details form
 *
 * @package    obu_application
 * @category   local
 * @author     Emir Kamel
 * @copyright  2023, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class profile_contact_details_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
        $data->user = $this->_customdata['user'];
        $data->applicant = $this->_customdata['applicant'];
        $data->titles = $this->_customdata['titles'];
        $data->nations = $this->_customdata['nations'];
        $data->domicile_code = $this->_customdata['default_domicile_code'];

        if ($data->user !== false) {
            $fields = [
                'username' => $data->user->username,
                'firstname' => $data->user->firstname,
                'lastname' => $data->user->lastname,
                'phone1' => $data->user->phone1,
                'phone2' => $data->user->phone2,
                'email' => $data->user->email
            ];

            if ($data->applicant !== false) {
                if (($data->applicant->domicile_code != '') && ($data->applicant->domicile_code != 'ZZ')) {
                    $data->domicile_code = $data->applicant->domicile_code;
                }
                $applicant_fields = [
                    'title' => $data->applicant->title,
                    'address_1' => $data->applicant->address_1,
                    'address_2' => $data->applicant->address_2,
                    'address_3' => $data->applicant->address_3,
                    'city' => $data->applicant->city,
                    'domicile_code' => $data->domicile_code,
                    'postcode' => $data->applicant->postcode
                ];
                $fields = array_merge($fields, $applicant_fields);
            }

            $this->set_data($fields);
        }

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        $title = $mform->addElement('select', 'title', get_string('title', 'local_obu_application'), $data->titles);
        $title->setSelected($data->title);
        $mform->addRule('title', null, 'required', null, 'server');

        $mform->addElement('text', 'firstname', get_string('firstnames', 'local_obu_application'), 'size="30" maxlength="100"');
        if ($data->user->email != $data->user->username) {
            $mform->disabledIf('firstname', 'email', 'neq', $data->user->username);
        } else {
            $mform->setType('firstname', PARAM_TEXT);
            $mform->addRule('firstname', null, 'required', null, 'server');
        }

        $mform->addElement('text', 'lastname', get_string('lastname'), 'size="30" maxlength="100"');
        if ($data->user->email != $data->user->username) {
            $mform->disabledIf('lastname', 'email', 'neq', $data->user->username);
        } else {
            $mform->setType('lastname', PARAM_TEXT);
            $mform->addRule('lastname', null, 'required', null, 'server');
        }

        $mform->addElement('text', 'address_1', get_string('address_1', 'local_obu_application'), 'size="30" maxlength="50"');
        $mform->setType('address_1', PARAM_TEXT);
        $mform->addRule('address_1', null, 'required', null, 'server');

        $mform->addElement('text', 'address_2', get_string('address_2', 'local_obu_application'), 'size="30" maxlength="50"');
        $mform->setType('address_2', PARAM_TEXT);

        $mform->addElement('text', 'address_3', get_string('address_3', 'local_obu_application'), 'size="30" maxlength="50"');
        $mform->setType('address_3', PARAM_TEXT);

        $mform->addElement('text', 'city', get_string('city', 'local_obu_application'), 'size="30" maxlength="50"');
        $mform->setType('city', PARAM_TEXT);
        $mform->addRule('city', null, 'required', null, 'server');

        $mform->addElement('text', 'postcode', get_string('postcode', 'local_obu_application'), 'size="20" maxlength="20"');
        $mform->setType('postcode', PARAM_TEXT);
        $mform->addRule('postcode', null, 'required', null, 'server');

        $mform->addElement('html', '<p><strong>' . get_string('domicile_preamble', 'local_obu_application') . '</strong></p>');
        $domicile_code = $mform->addElement('select', 'domicile_code', get_string('domicile_country', 'local_obu_application'), $data->nations);
        $domicile_code->setSelected($data->domicile_code);
        $mform->addRule('domicile_code', null, 'required', null, 'server');

        $mform->addElement('text', 'phone1', get_string('home_phone', 'local_obu_application'), 'size="20" maxlength="20"');
        $mform->setType('phone1', PARAM_TEXT);

        $mform->addElement('text', 'phone2', get_string('mobile_phone', 'local_obu_application'), 'size="20" maxlength="20"');
        $mform->setType('phone2', PARAM_TEXT);

        if ($data->user->email != $data->user->username) {
            $mform->addElement('text', 'email', get_string('email'), 'size="25" maxlength="100"');
            $mform->disabledIf('email', 'firstname', 'neq', '?****?');

            $mform->addElement('html', '<p><strong>' . get_string('personalemail_preamble', 'local_obu_application') . '</strong></p>');
            $mform->addElement('text', 'personalemail', get_string('personalemail', 'local_obu_application'), 'size="25" maxlength="100"');
            $mform->setType('personalemail', PARAM_RAW_TRIMMED);
        } else {
            $mform->addElement('header', 'newemail', get_string('newemail', 'local_obu_application'), '');
            $mform->addElement('text', 'email', get_string('email'), 'size="25" maxlength="100"');
            $mform->setType('email', PARAM_RAW_TRIMMED);
            $mform->addRule('email', get_string('missingemail'), 'required', null, 'server');

            $mform->addElement('html', '<p><strong>' . get_string('personalemail_preamble', 'local_obu_application') . '</strong></p>');
            $mform->addElement('text', 'personal_email', get_string('personalemail', 'local_obu_application'), 'size="25" maxlength="100"');
            $mform->setType('personal_email', PARAM_RAW_TRIMMED);

            $mform->addElement('text', 'username', get_string('confirm_email', 'local_obu_application'), 'size="25" maxlength="100"');
            $mform->setType('username', PARAM_RAW_TRIMMED);
            $mform->addRule('username', get_string('missingemail'), 'required', null, 'server');
        }

        // buttons
        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if (!$data['domicile_code']) {
            $errors['domicile_code'] = get_string('value_required', 'local_obu_application');
        }

        if ((!$data['phone1'] || $data['phone1'] == '') && (!$data['phone2'] || $data['phone2'] == '')) {
            $errors['phone1'] = get_string('no_phone', 'local_obu_application');
        }

        if ($data['email'] == $data['username']) {
            if (!validate_email($data['email']) || ($data['email'] != strtolower($data['email']))) {
                $errors['email'] = get_string('invalidemail');
            }
            if (empty($data['username'])) {
                $errors['username'] = get_string('missingemail');
            } else if ($data['username'] != $data['email']) {
                $errors['username'] = get_string('invalidemail');
            }
        }

        if (!empty($errors)) {
            $errors['form_errors'] = get_string('form_errors', 'local_obu_application');
        }

        return $errors;
    }
}
