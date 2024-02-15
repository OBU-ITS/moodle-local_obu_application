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

class profile_professional_qualification_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $data = new stdClass();
        $data->record = $this->_customdata['record'];

        $fields = [
            'prof_level' => $data->record->prof_level,
            'prof_award' => $data->record->prof_award,
            'prof_date' => $data->record->prof_date,
            'credit' => $data->record->credit,
            'credit_name' => $data->record->credit_name,
            'credit_organisation' => $data->record->credit_organisation
        ];
        $this->set_data($fields);

        $date_options = array('startyear' => 1931, 'stopyear'  => 2030, 'timezone'  => 99, 'optional' => false);

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        // Professional qualification
        $mform->addElement('html', '<p><strong>' . get_string('prof_level_preamble', 'local_obu_application') . '</strong></p>');
        $mform->addElement('text', 'prof_level', get_string('prof_level', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('prof_level', PARAM_TEXT);
        $mform->addRule('prof_level', null, 'required', null, 'server');
        $mform->addElement('html', '<p><strong>' . get_string('prof_award_preamble', 'local_obu_application') . '</strong></p>');
        $mform->addElement('text', 'prof_award', get_string('prof_award', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('prof_award', PARAM_TEXT);
        $mform->addRule('prof_award', null, 'required', null, 'server');
        $mform->addElement('date_selector', 'prof_date', get_string('prof_date', 'local_obu_application'));
        $mform->addRule('prof_date', null, 'required', null, 'server');
        $mform->addElement('html', '<p \><strong>' . get_string('credit_preamble', 'local_obu_application') . '</strong>');
        $mform->addElement('advcheckbox', 'credit', get_string('credit', 'local_obu_application'), get_string('credit_text', 'local_obu_application'), null, array(0, 1));
        $mform->addElement('html', '<p><strong>' . get_string('credit_name_preamble', 'local_obu_application') . '</strong></p>');
        $mform->addElement('text', 'credit_name', get_string('credit_name', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('credit_name', PARAM_TEXT);
        $mform->disabledIf('credit_name', 'credit', 'eq', '0');
        $mform->addElement('html', '<p><strong>' . get_string('credit_organisation_preamble', 'local_obu_application') . '</strong></p>');
        $mform->addElement('text', 'credit_organisation', get_string('credit_organisation', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('credit_organisation', PARAM_TEXT);
        $mform->disabledIf('credit_organisation', 'credit', 'eq', '0');
        $mform->setType('trainingperiod', PARAM_TEXT);
        $mform->addRule('trainingperiod', null, 'required', null, 'server');

        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if ($data['credit'] == '1') {
            if ($data['credit_name'] == '') {
                $errors['credit_name'] = get_string('value_required', 'local_obu_application');
            }
            if ($data['credit_organisation'] == '') {
                $errors['credit_organisation'] = get_string('value_required', 'local_obu_application');
            }
        }

        if (!empty($errors)) {
            $errors['form_errors'] = get_string('form_errors', 'local_obu_application');
        }

        return $errors;
    }
}
