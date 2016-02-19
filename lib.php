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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_obu_application_extends_navigation($navigation) {
    global $CFG;
	
	if (!isloggedin() || isguestuser() || !has_capability('local/obu_application:manage', context_system::instance())) {
		return;
	}
	
	// Find the 'applications' node
	$nodeParent = $navigation->find(get_string('applications', 'local_obu_applications'), navigation_node::TYPE_SYSTEM);
	
	// If necessary, add the 'applications' node to 'home'
	if (!$nodeParent) {
		$nodeHome = $navigation->children->get('1')->parent;
		if ($nodeHome) {
			$nodeParent = $nodeHome->add(get_string('applications', 'local_obu_application'), null, navigation_node::TYPE_SYSTEM);
		}
	}
	
	if ($nodeParent) {
		$node = $nodeParent->add(get_string('application_approvals', 'local_obu_application'), '/local/obu_application/mdl_approvals.php');
		$node = $nodeParent->add(get_string('hls_approvals', 'local_obu_application'), '/local/obu_application/mdl_approvals.php?approver=hls');
		$node = $nodeParent->add(get_string('list_applications', 'local_obu_application'), '/local/obu_application/mdl_list.php');
		$node = $nodeParent->add(get_string('courses', 'local_obu_application'), '/local/obu_application/mdl_course.php');
		$node = $nodeParent->add(get_string('forms', 'local_obu_application'), '/local/obu_application/mdl_form.php');
		$node = $nodeParent->add(get_string('organisations', 'local_obu_application'), '/local/obu_application/mdl_organisation.php');
	}
}
