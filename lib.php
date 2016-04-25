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
 * OBU Application - Provide left hand navigation links
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once($CFG->dirroot . '/local/obu_application/db_update.php');

function local_obu_application_extends_navigation($navigation) {
    global $CFG, $USER;
	
	if (!isloggedin() || isguestuser()) {
		return;
	}
	
	$applicant = (read_applicant($USER->id, false) !== false);
	if (!has_capability('local/obu_application:manage', context_system::instance()) && !$applicant) {
		return;
	}
	
	// Find the 'applications' node
	$nodeParent = $navigation->find(get_string('applications', 'local_obu_application'), navigation_node::TYPE_SYSTEM);
	
	// If necessary, add the 'applications' node to 'home'
	if (!$nodeParent) {
		$nodeHome = $navigation->children->get('1')->parent;
		if ($nodeHome) {
			$nodeParent = $nodeHome->add(get_string('applications', 'local_obu_application'), null, navigation_node::TYPE_SYSTEM);
		}
	}
	
	if ($nodeParent) {
		if (has_capability('local/obu_application:manage', context_system::instance())) {
			$node = $nodeParent->add(get_string('application_approvals', 'local_obu_application'), '/local/obu_application/mdl_approvals.php');
			$node = $nodeParent->add(get_string('hls_approvals', 'local_obu_application'), '/local/obu_application/mdl_approvals.php?approver=hls');
			$node = $nodeParent->add(get_string('list_applications', 'local_obu_application'), '/local/obu_application/mdl_list.php');
			$node = $nodeParent->add(get_string('courses', 'local_obu_application'), '/local/obu_application/mdl_course.php');
			$node = $nodeParent->add(get_string('supplements', 'local_obu_application'), '/local/obu_application/mdl_supplement.php');
			$node = $nodeParent->add(get_string('organisations', 'local_obu_application'), '/local/obu_application/mdl_organisation.php');
		}
		if ($applicant) {
			$node = $nodeParent->add(get_string('hls_applications', 'local_obu_application'), '/local/obu_application/');
		}
	}
}

function local_obu_application_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
	global $USER;
	
    // Check that the context is a 'user' one and that the filearea is valid
    if (($context->contextlevel != CONTEXT_USER) || ($filearea !== 'file')) {
        return false; 
    }
 
    // Make sure the user is logged in
    require_login();
 
    $itemid = array_shift($args); // The first item in the $args array

    // Extract the filename / filepath from the $args array
    $filename = array_pop($args); // The last item in the $args array
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }
 
    // Retrieve the file from the pool
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_obu_application', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist!
    }
	
    // Check the capability
    if (($USER->id != $file->get_userid()) && !has_capability('local/obu_application:manage', $context)) {
        return false;
    }
 
    // We can now send the file back to the browser 
	send_stored_file($file, 86400, 0, $forcedownload, $options);
}