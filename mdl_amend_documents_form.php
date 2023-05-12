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
 * OBU Application - Amend details form [Moodle]
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

class mdl_amend_documents_form extends moodleform{
    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
        $data->record = $this->_customdata['record'];

        if ($data->record->visa_data){
            $mform->addElement('filepicker', 'visafile' , get_string('visa_file', 'local_obu_application'), null, array('maxbytes' => 2097152, 'accepted_types' => array('.pdf')));

        }
        if ($data->record->supplement_data){
            $mform->addElement('filepicker', 'supplementfile' , get_string('supplement_file', 'local_obu_application'), null, array('maxbytes' => 2097152, 'accepted_types' => array('.pdf')));

        }
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
