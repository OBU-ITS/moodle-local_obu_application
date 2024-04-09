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
 * OBU Application - Reject form [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Emir Kamel
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class mdl_reject_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
        $data->source = $this->_customdata['source'];
        $data->record = $this->_customdata['record'];

        // Start with the required hidden fields
        $mform->addElement('hidden', 'source', $data->source);
        $mform->setType('source', PARAM_RAW);
        $mform->addElement('hidden', 'id', $data->record->id);
        $mform->setType('id', PARAM_RAW);

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        $mform->addElement('html', '<h3>' . get_string('rejection_head', 'local_obu_application') . '</h3>');
        $mform->addElement('html', '<p><strong>' . get_string('reject_comment', 'local_obu_application') . '</strong></p>');
        $mform->addElement('text', 'comment', get_string('comment', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->addRule('comment', get_string('required'), 'required');
        $mform->setType('comment', PARAM_TEXT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('reject', 'local_obu_application'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonarray', '', array(' '), false);
        $mform->closeHeaderBefore('buttonarray');
    }
}