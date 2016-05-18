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
 * OBU Application - Return a CSV file for transfer to Admissions
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');

require_login();

$manager = has_capability('local/obu_application:manage', context_system::instance());

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=applications.csv');
$fp = fopen('php://output', 'w');
$applications = get_applications(); // get all applications
foreach ($applications as $application) {
	if (($application->approval_level == 3) && ($application->approval_status == 2)) { // Approved by HLS
		
	}
}
