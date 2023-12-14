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
 * OBU Application - Manager Report options form
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

class mdl_organisation_report_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
        $data->organisations = $this->_customdata['organisations'];

        $organisationOptions = [];
        $organisationOptions['-1'] = get_string('select', 'local_obu_application');
        foreach ($data->organisations as $organisation_id => $organisation_name) {
            $organisationOptions[$organisation_id] = $organisation_name;
        }

        $mform->addElement('html', '<h2>' . get_string('organisation_options', 'local_obu_application') . '</h2>');

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        // Organisation
        $mform->addElement('autocomplete', 'organisation', get_string('organisation', 'local_obu_application'), $organisationOptions, null);
        $mform->addRule('organisation', get_string('required'), 'required');

        // Date
        $mform->addElement('date_selector', 'application_date', get_string('application_date', 'local_obu_application'));

        // Second Date
        $mform->addElement('date_selector', 'application_second_date', get_string('application_second_date', 'local_obu_application'));

        // Options
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('continue', 'local_obu_application'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonarray', '', array(' '), false);
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if ($data['application_second_date'] < $data['application_date']) {
            $errors['application_second_date'] = get_string('form_error_second_date', 'local_obu_application');
        }

        if (!empty($errors)) {
            $errors['form_errors'] = get_string('form_errors', 'local_obu_application');
        }

        return $errors;
    }
}