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
 * OBU Application - Public functions
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2021, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
function local_obu_application_extend_navigation($navigation) {
	
	if (!is_siteadmin()) {
		return;
	}

	$nodeHome = $navigation->children->get('1')->parent;
	$node = $nodeHome->add(get_string('applications_administration', 'local_obu_application'), '/local/obu_application/mdl_site_admin.php', navigation_node::TYPE_SYSTEM);
	$node->showinflatnavigation = true;
	
	return;
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
	
    // We can now send the file back to the browser 
	send_stored_file($file, 86400, 0, $forcedownload, $options);
}