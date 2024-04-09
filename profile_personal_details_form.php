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
 * OBU Application - User profile form
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

class profile_personal_details_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $data = new stdClass();
        $data->record = $this->_customdata['record'];
        $data->nations = $this->_customdata['nations'];
        $data->areas = $this->_customdata['areas'];

        if (($data->record->birth_code != '') && ($data->record->birth_code != 'ZZ')) {
            $data->birth_code = $data->record->birth_code;
        }

        if (($data->record->nationality_code != '') && ($data->record->nationality_code != 'ZZ')) {
            $data->nationality_code = $data->record->nationality_code;
        }

        $homeResidenciesNotEngland = array('XF', 'XH', 'XI', 'XG', 'JE', 'GG');
        if(in_array($data->record->residence_code, $homeResidenciesNotEngland)) {
            $data->residence_code = 'XF'; // Yes
        }
        else if (($data->record->residence_code != '') && ($data->record->residence_code != 'ZZ')) {
            $data->residence_code = 'AF'; // No
        }

        $fields = [
            'birth_code' => $data->birth_code,
            'birthdate' => $data->record->birthdate,
            'nationality_code' => $data->nationality_code,
            'gender' => $data->record->gender,
            'residence_code' => $data->residence_code
        ];
        $this->set_data($fields);

        $current_year = date('Y');
        $date_options = array('startyear' => $current_year - 90, 'stopyear'  => $current_year - 10, 'timezone'  => 99, 'optional' => false);

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        // General - birth country, date, nationality, gender and residence
        $mform->addElement('select', 'birth_code', get_string('birth_country', 'local_obu_application'), $data->nations, null);
        $mform->addRule('birth_code', null, 'required', null, 'server');
        $mform->addElement('date_selector', 'birthdate', get_string('birthdate', 'local_obu_application'), $date_options);
        $mform->addRule('birthdate', null, 'required', null, 'server');
        $mform->addElement('select', 'nationality_code', get_string('nationality', 'local_obu_application'), $data->nations, null);
        $mform->addRule('nationality_code', null, 'required', null, 'server');
        $mform->addElement('static', 'nationality_note', null, get_string('nationality_note', 'local_obu_application'));
        $genders = [];
        $genders[''] = get_string('select', 'local_obu_application');
        $genders['N'] = get_string('gender_not_available', 'local_obu_application');
        $genders['F'] = get_string('gender_female', 'local_obu_application');
        $genders['M'] = get_string('gender_male', 'local_obu_application');
        $mform->addElement('select', 'gender', get_string('gender', 'local_obu_application'), $genders);
        $mform->addRule('gender', null, 'required', null, 'server');
        $mform->addElement('html', '<p><strong>' . get_string('residence_preamble', 'local_obu_application') . '</strong></p>');
        $options = [];
        $options[''] = 'Please select'; // No choice made yet
        $options['XF'] = get_string('yes', 'local_obu_application');
        $options['AF'] = get_string('no', 'local_obu_application');
        $mform->addElement('select', 'residence_code', get_string('residence_area', 'local_obu_application'), $options);
        $mform->addRule('residence_code', null, 'required', null, 'server');

        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
        $mform->addElement('static', 'submitinfo', '', get_string('submit_info', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if ($data['birth_code'] == '') {
            $errors['birth_code'] = get_string('value_required', 'local_obu_application');
        }

        if ((mktime() - $data['birthdate']) < 504921600) { // Must be at least 16 years old!
            $errors['birthdate'] = get_string('invalid_date', 'local_obu_application');
        }

        if ($data['nationality_code'] == '') {
            $errors['nationality_code'] = get_string('value_required', 'local_obu_application');
        }

        if ($data['residence_code'] == '') {
            $errors['residence_code'] = get_string('value_required', 'local_obu_application');
        }

        return $errors;
    }
}
