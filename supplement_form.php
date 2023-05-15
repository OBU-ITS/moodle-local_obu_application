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
 * OBU Application - Supplementary form input
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class supplement_form extends moodleform {
	
	// Arrays used in our own validation
	private $required_field = array(); // an array of field IDs, all of which must have values
	private $required_group = array(); // a group of field IDs, at least one of which must have a value

    function definition() {
		global $USER;
		
        $mform =& $this->_form;

        $data = new stdClass();
		$data->supplement = $this->_customdata['supplement'];
        $data->fields = $this->_customdata['fields'];
        $data->applicationId = $this->_customdata['applicationId'];
		$this->set_data($data->fields);
		
		$mform->addElement('hidden', 'supplement', $data->supplement->ref);
		$mform->setType('supplement', PARAM_RAW);
		$mform->addElement('hidden', 'version', $data->supplement->version);
		$mform->setType('version', PARAM_RAW);
        if ($data->applicationId){
            $mform->addElement('hidden', 'applicationId', $data->applicationId->id);
            $mform->setType('applicationId', PARAM_RAW);
        }
		
        // Process the form
		$fld_start = '<input ';
		$fld_start_len = strlen($fld_start);
        $fld_end = '>';
		$fld_end_len = strlen($fld_end);
		$offset = 0;
		$date_format = 'd-m-y';
		$fs = get_file_storage();
		$context = context_user::instance($USER->id);
		
		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_error');



		do {
			$pos = strpos($data->supplement->template, $fld_start, $offset);
			if ($pos === false) {
				break;
			}
			if ($pos > $offset) {
				$mform->addElement('html', substr($data->supplement->template, $offset, ($pos - $offset))); // output any HTML
			}
			$offset = $pos + $fld_start_len;
			$pos = strpos($data->supplement->template, $fld_end, $offset);
			if ($pos === false) {
				break;
			}
			$element = split_input_field(substr($data->supplement->template, $offset, ($pos - $offset)));
			$offset = $pos + $fld_end_len;
			switch ($element['type']) {
				case 'area':
					$mform->addElement('textarea', $element['id'], $element['value'], $element['options']);
					break;
				case 'checkbox':
					if ($element['rule'] == 'required') { // mustn't return a zero value
						$mform->addElement('checkbox', $element['id'], $element['value']);
					} else {
						$mform->addElement('advcheckbox', $element['id'], $element['value'], $element['name'], null, array(0, 1));
					}
					break;
				case 'date':
					$mform->addElement('date_selector', $element['id'], $element['value'], $element['options']);
					break;
				case 'file':
					if (isset($data->fields[$element['id']])) {
						$pathnamehash = $data->fields[$element['id']];
					} else {
						$pathnamehash = '';
					}
					if ($pathnamehash == '') {
						$draftitemid = 0;
						$itemid = 0;
					} else {
						$file = $fs->get_file_by_hash($pathnamehash);
						$itemid = $file->get_itemid();
						$draftitemid = 0;
					}
					file_prepare_draft_area($draftitemid, $context->id, 'local_obu_application', 'file', $itemid, array('subdirs' => false, 'maxbytes' => 2097152, 'maxfiles' => 1));
					$this->set_data(array($element['id'] => $draftitemid));
 					$mform->addElement('filepicker', $element['id'] , $element['value'], null, array('maxbytes' => 2097152, 'accepted_types' => array('.pdf')));
					break;
				case 'select':
					switch ($element['name']) {
						default:
					}
					$select = $mform->addElement('select', $element['id'], $element['value'], $options, null);
					switch ($element['selected']) {
					case 'start_selected':
							$select->setSelected($data->start_selected);
							break;
						default:
					}
					break;
				case 'static':
					switch ($element['name']) {
						default:
							$text = '';
					}
					$mform->addElement('static', '', $element['value'], $text); // Display the field...
					$mform->addElement('hidden', $element['id'], $text); // ...and also return it
					break;
				case 'text':
					$mform->addElement('text', $element['id'], $element['value'], $element['options']);
					$mform->setType($element['id'], PARAM_TEXT);
					break;
				case 'alphabetic':
					$mform->addElement('text', $element['id'], $element['value'], $element['options']);
					$mform->setType($element['id'], PARAM_RAW);
					$mform->addRule($element['id'], null, 'lettersonly', null, 'server'); // Let Moodle handle the rule
					break;
				case 'numeric':
					$mform->addElement('text', $element['id'], $element['value'], $element['options']);
					$mform->setType($element['id'], PARAM_RAW);
					$mform->addRule($element['id'], null, 'numeric', null, 'server'); // Let Moodle handle the rule
					break;
				default:
			}
			
			if (array_key_exists('rule', $element)) { // An extra validation rule applies to this field
				if ($element['rule'] == 'group') { // At least one of this group of fields is required
					$this->required_group[] = $element['id']; // For our own validation
				} else {
					$mform->addRule($element['id'], null, $element['rule'], null, 'server'); // Let Moodle handle the rule
					if ($element['rule'] == 'required') {
						$this->required_field[] = $element['id']; // For our own extra validation
					}
				}
			}
		} while(true);

		$mform->addElement('html', substr($data->supplement->template, $offset)); // output any remaining HTML

        $this->add_action_buttons(true, get_string('save_continue', 'local_obu_application'));
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		// Check if at least one field in a required group has an entry
		if (empty($this->required_group)) {
			$group_entry = true;
		} else {
			$group_entry = false;
			foreach ($this->required_group as $key) {
				if ($data[$key] != '') {
					$group_entry = true;
				}
			}
		}
		
		// Do our own validation and add errors to array
		$required_value = false;
		foreach ($data as $key => $value) {
			if (in_array($key, $this->required_field, true) && ($value == '')) {
				$required_value = true; // Leave the field error display to Moodle
			} else if (!$group_entry && in_array($key, $this->required_group, true)) { // One of a required group with no entries
				$errors[$key] = get_string('group_required', 'local_obu_application');
			}
		}
		
		if ($required_value || !empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}
		
		return $errors;
    }
}