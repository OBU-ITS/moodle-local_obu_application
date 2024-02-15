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

class profile_educational_establishments_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $data = new stdClass();
        $data->record = $this->_customdata['record'];

        $fields = [
            'p16school' => $data->record->p16school,
            'p16schoolperiod' => $data->record->p16schoolperiod,
            'p16fe' => $data->record->p16fe,
            'p16feperiod' => $data->record->p16feperiod,
            'training' => $data->record->training,
            'trainingperiod' => $data->record->trainingperiod
        ];
        $this->set_data($fields);

        $date_options = array('startyear' => 1931, 'stopyear'  => 2030, 'timezone'  => 99, 'optional' => false);

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        // Education
        $mform->addElement('text', 'p16school', get_string('p16school', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('p16school', PARAM_TEXT);
        $mform->addElement('text', 'p16schoolperiod', get_string('period', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('p16schoolperiod', PARAM_TEXT);
        $mform->addElement('text', 'p16fe', get_string('p16fe', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('p16fe', PARAM_TEXT);
        $mform->addElement('text', 'p16feperiod', get_string('period', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('p16feperiod', PARAM_TEXT);
        $mform->addElement('text', 'training', get_string('training', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('training', PARAM_TEXT);
        $mform->addRule('training', null, 'required', null, 'server');
        $mform->addElement('text', 'trainingperiod', get_string('period', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->setType('trainingperiod', PARAM_TEXT);
        $mform->addRule('trainingperiod', null, 'required', null, 'server');

        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
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
