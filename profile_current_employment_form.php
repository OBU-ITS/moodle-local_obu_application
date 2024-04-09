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

class profile_current_employment_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $data = new stdClass();
        $data->record = $this->_customdata['record'];

        $fields = [
            'emp_place' => $data->record->emp_place,
            'emp_area' => $data->record->emp_area,
            'emp_title' => $data->record->emp_title,
            'emp_prof' => $data->record->emp_prof
        ];
        $this->set_data($fields);

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        // Employment
        $mform->addElement('text', 'emp_place', get_string('emp_place', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('emp_place', PARAM_TEXT);
        $mform->addRule('emp_place', null, 'required', null, 'server');
        $mform->addElement('text', 'emp_area', get_string('emp_area', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('emp_area', PARAM_TEXT);
        $mform->addElement('text', 'emp_title', get_string('emp_title', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('emp_title', PARAM_TEXT);
        $mform->addElement('text', 'emp_prof', get_string('emp_prof', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('emp_prof', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
        $mform->addElement('static', 'submitinfo', '', get_string('submit_info', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if (!empty($errors)) {
            $errors['form_errors'] = get_string('form_errors', 'local_obu_application');
        }

        return $errors;
    }
}
