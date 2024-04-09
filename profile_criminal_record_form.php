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

class profile_criminal_record_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $data = new stdClass();
        $data->record = $this->_customdata['record'];

        $fields = [
            'criminal_record' => $data->record->criminal_record
        ];
        $this->set_data($fields);

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        // Criminal record
        $options = [];
        $options[''] = 'Please select'; // No choice made yet
        $options['1'] = get_string('yes', 'local_obu_application');
        $options['2'] = get_string('no', 'local_obu_application');
        $mform->addElement('select', 'criminal_record', get_string('criminal_record', 'local_obu_application'), $options);
        $mform->addRule('criminal_record', null, 'required', null, 'server');

        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
        $mform->addElement('static', 'submitinfo', '', get_string('submit_info', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if ($data['criminal_record'] == '') {
            $errors['criminal_record'] = get_string('value_required', 'local_obu_application');
        }

        return $errors;
    }
}
