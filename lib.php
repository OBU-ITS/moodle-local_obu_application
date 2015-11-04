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
/*
	// The required grandparent node must already exist
	$nodeProfile = $navigation->find('myprofile', navigation_node::TYPE_UNKNOWN);
	if (!$nodeProfile) {
		return;
	}
	
	// BRISC
	if ((get_config('local_obu_apps', 'showbrisc') == '1') && has_capability('moodle/blog:create', context_system::instance())) { // Only show if allowed
		$nodeEmpskills = $nodeProfile->get('empskills', navigation_node::TYPE_UNKNOWN); // Parent ('get' faster than 'find')
		if (!$nodeEmpskills) { // Add the parent if necessary
			$nodeEmpskills = $nodeProfile->add(get_string('empskills', 'local_empskills'),
				null, TYPE_CUSTOM, null, 'empskills', null); // The 'key' is 'empskills'
		}
		if ($nodeEmpskills) {
			$nodeEmpskills->add('BRISC', '/local/obu_apps/brisc.php'); // BRISC web app
		}
	}
	
	// QuAK
	if ((get_config('local_obu_apps', 'showquak') == '1') && !empty($CFG->navadduserpostslinks)) { // Only show if allowed
		$nodeForumPosts	= null;
		$title = get_string('forumposts', 'mod_forum'); // The required node wasn't given a specific key so we must search for the title
		$children = $nodeProfile->get_children_key_list();
		foreach ($children as $child) {
			$node = $nodeProfile->get($child);
			if ($node->get_content() == $title) {
				$nodeForumPosts	= $node;
				break;
			}				
		}
		if ($nodeForumPosts) {
			$nodeForumPosts->add('QuAK', '/local/obu_apps/quak.php'); // QuAK web app
		}
	}
*/}
