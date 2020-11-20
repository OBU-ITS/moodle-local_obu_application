<?php

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
 * OBU Application - Input form for data transfer
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class mdl_xfer_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->dates = $this->_customdata['dates'];
		
		$options = [];
//		$options['0'] = get_string('select', 'local_obu_application');
		$options['1'] = get_string('admissions', 'local_obu_application');
//		$options['2'] = get_string('finance', 'local_obu_application');
//		$options['3'] = get_string('process', 'local_obu_application');
		$mform->addElement('select', 'xfer_type', get_string('xfer_type', 'local_obu_application'), $options);
		$mform->addRule('xfer_type', null, 'required', null, 'server');
		$mform->addElement('text', 'xfer_id', get_string('xfer_id', 'local_obu_application'), 'size="10" maxlength="10"');
		$mform->addElement('select', 'course_date', get_string('course_date', 'local_obu_application'), $data->dates, null);
		$mform->disabledIf('course_date', 'xfer_id', 'neq', '');

        $this->add_action_buttons(true, get_string('continue', 'local_obu_forms'));
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		// Do our own validation and add errors to array
		if ($data['xfer_type'] == 0) {
			$errors['xfer_type'] = get_string('value_required', 'local_obu_application');
		}
		if (($data['xfer_type'] == 3) && ($data['xfer_id'] == '')) {
			$errors['xfer_id'] = get_string('value_required', 'local_obu_application');
		}
		
		return $errors;
	}
}