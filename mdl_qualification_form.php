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
 * OBU Application - Qualification maintenance form
 *
 * @package    obu_application
 * @category   local
 * @author     Emir Kamel
 * @copyright  2025, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class mdl_qualification_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
        $data->id = $this->_customdata['id'];
        $data->delete = $this->_customdata['delete'];
        $data->qualifications = $this->_customdata['qualifications'];
        $data->record = $this->_customdata['record'];

        if ($data->record != null) {
            $fields = [
                'code' => $data->record->code,
                'label' => $data->record->label,
                'priority' => $data->record->priority,
                'admissions_type' => $data->record->admissions_type,
                'cpd_subset' => $data->record->cpd_subset,
                'crm_dropdown_text' => $data->record->crm_dropdown_text,
                'notes' => $data->record->notes
            ];
            $this->set_data($fields);
        }

        $mform->addElement('html', '<h2>' . get_string('update_qualification', 'local_obu_application') . '</h2>');

        if ($data->id == '') {
            $select = $mform->addElement('select', 'id', get_string('qualification', 'local_obu_application'), $data->qualifications, null);
            $select->setSelected(0);
            $this->add_action_buttons(true, get_string('continue', 'local_obu_application'));
            return;
        }

        $mform->addElement('hidden', 'id', $data->id);
        $mform->setType('id', PARAM_RAW);

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        if ($data->delete) {
            $mform->addElement('static', 'code', get_string('code', 'local_obu_application'));
            $mform->addElement('static', 'label', get_string('label', 'local_obu_application'));
            $mform->addElement('static', 'priority', get_string('priority', 'local_obu_application'));
            $mform->addElement('static', 'admissions_type', get_string('admissions_type', 'local_obu_application'));
            $mform->addElement('static', 'cpd_subset', get_string('cpd_subset', 'local_obu_application'));
            if ($data->record->cpd_subset == '1') {
                $cpd_subset_formatted = '&#10004;'; // Tick
            } else {
                $cpd_subset_formatted = '&#10008;'; // Cross
            }
            $mform->addElement('static', 'cpd_subset_formatted', get_string('cpd_subset', 'local_obu_application'), $cpd_subset_formatted);
            $mform->addElement('static', 'crm_dropdown_text', get_string('crm_dropdown_text', 'local_obu_application'));
            $mform->addElement('static', 'notes', get_string('notes', 'local_obu_application'));
        } else {
            $mform->addElement('text', 'code', get_string('code', 'local_obu_application'), 'size="10" maxlength="5"');
            $mform->setType('code', PARAM_TEXT);
            $mform->addElement('text', 'label', get_string('label', 'local_obu_application'), 'size="75" maxlength="100"');
            $mform->setType('label', PARAM_TEXT);
            $mform->addElement('text', 'priority', get_string('priority', 'local_obu_application'), 'size="1" maxlength="2"');
            $mform->setType('priority', PARAM_INT);
            $select = $mform->addElement('select', 'admissions_type', get_string('admissions_type', 'local_obu_application'), ['Please select', 'PG', 'UG', 'UG and PG'], null);
            $select->setSelected(0);
            $mform->addElement('advcheckbox', 'cpd_subset', get_string('cpd_subset', 'local_obu_application'), null, null, array(0, 1));
            $mform->addElement('text', 'crm_dropdown_text', get_string('crm_dropdown_text', 'local_obu_application'), 'size="75" maxlength="100"');
            $mform->addElement('text', 'notes', get_string('notes', 'local_obu_application'), 'size="75" maxlength="100"');
        }

        // Options
        $buttonarray = array();
        if ($data->delete) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('confirm_delete', 'local_obu_application'));
        } else {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'local_obu_application'));
            if (($data->id != '0') && ($data->applications == 0)) {
                $buttonarray[] = &$mform->createElement('submit', 'deletebutton', get_string('delete', 'local_obu_application'));
            }
        }
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonarray', '', array(' '), false);
        $mform->closeHeaderBefore('buttonarray');
    }

//    function validation($data, $files) {
//        global $CFG, $DB;
//        $errors = parent::validation($data, $files);
//
//        // Check that we have been given sufficient information
//        if (isset($data['submitbutton']) && ($data['submitbutton'] == get_string('save', 'local_obu_application'))) {
//            if ($data['name'] == '') {
//                $errors['name'] = get_string('value_required', 'local_obu_application');
//            }
//            if ($data['email'] == '') {
//                $errors['email'] = get_string('value_required', 'local_obu_application');
//            }
//            if ($data['code'] == '') {
//                $errors['code'] = get_string('value_required', 'local_obu_application');
//            }
//            if ($data['address'] == '') {
//                $errors['address'] = get_string('value_required', 'local_obu_application');
//            }
//        }
//
//        if (!empty($errors)) {
//            $errors['form_errors'] = get_string('form_errors', 'local_obu_application');
//        }
//
//        return $errors;
//    }
}