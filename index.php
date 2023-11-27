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
 * OBU Application - Menu page
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');

// Try to prevent searching for sites that allow sign-up.
if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .= '<meta name="robots" content="noindex" />';

require_obu_login();

$process = new moodle_url('/local/obu_application/process.php');

$PAGE->set_url($CFG->httpswwwroot . '/local/obu_application/index.php');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

echo $OUTPUT->header();

?>
    <h1 class="mb-4">Apply for a new module or course</h1>
    <p>
        Detailed guidance can be <a href="application_guidance.php" target="_blank">found here</a>.
    </p>
    <p>
        If you have any queries, please contact <a href="mailto:hlscpdadmissions@brookes.ac.uk">hlscpdadmissions@brookes.ac.uk</a>
    </p>
    <div id="accordion" class="clearfix collapsible">
<?php

$mform_contact = "Test";
$mform_general = "Test 2";
$accordion_items = array(
    ["title" => "Contact details", "data" => $mform_contact],
    ["title" => "Personal details", "data" => $mform_general]);

$counter = 0;
foreach ($accordion_items as $accordion_item) {
?>
        <div class="d-flex align-items-center mb-2" id="heading<?php echo $counter ?>">
            <div class="position-relative d-flex ftoggler align-items-center position-relative mr-1">
                <a data-toggle="collapse" href="#id_<?php echo $counter ?>_headcontainer" role="button" aria-expanded="true" aria-controls="id_<?php echo $counter ?>_headcontainer" class="btn btn-icon mr-1 icons-collapse-expand stretched-link fheader" id="collapseElement-0">
                    <span class="expanded-icon icon-no-margin p-2" title="Collapse">
                        <i class="icon fa fa-chevron-down fa-fw " aria-hidden="true"></i>
                    </span>
                    <span class="collapsed-icon icon-no-margin p-2" title="Expand">
                        <span class="dir-rtl-hide"><i class="icon fa fa-chevron-right fa-fw " aria-hidden="true"></i></span>
                        <span class="dir-ltr-hide"><i class="icon fa fa-chevron-left fa-fw " aria-hidden="true"></i></span>
                    </span>
                    <span class="sr-only"><?php echo $accordion_item["title"] ?></span>
                </a>
                <h3 class="d-flex align-self-stretch align-items-center mb-0" aria-hidden="true">
                    <?php echo $accordion_item["title"] ?>
                </h3>
            </div>
        </div>
        <div id="id_<?php echo $counter ?>_headcontainer" class="fcontainer collapseable collapse" style=""  aria-labelledby="heading<?php echo $counter ?>" data-parent="#accordion">
            <?php echo $accordion_item["data"] ?>
        </div>
<?php
    $counter++;
}
?>
    </div>
<?php

// TODO : Plan on what to do with this
//// Display any outstanding approvals
//$approvals = get_approvals($USER->email); // get outstanding approval requests
//if ($approvals) {
//	echo '<h2>' . get_string('your_approvals', 'local_obu_application') . '</h2>';
//	foreach ($approvals as $approval) {
//		$application = read_application($approval->application_id);
//		$application_title = $application->firstname . ' ' . $application->lastname . ' (Application Ref HLS/' . $application->id . ')';
//		echo '<h4><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h4>';
//		get_application_status($USER->id, $application, $text, $button); // get the approval trail and the next action (from the user's perspective)
//		echo $text;
//	}
//} else {
//	echo get_string('page_content', 'local_obu_application');
//}

// TODO : Plan on what to do with this
//// Display applications submitted
//$applications = get_applications($USER->id); // get all applications for the user
//if ($applications) {
//	echo '<h2>' . get_string('your_applications', 'local_obu_application') . '</h2>';
//	foreach ($applications as $application) {
//		get_application_status($USER->id, $application, $text, $button); // get the approval trail and the next action (from this user's perspective)
//		$application_title = $application->course_code . ' ' . $application->course_name . ' (Application Ref HLS/' . $application->id . ')';
//		if (($button != 'submit') || $currentuser || $manager) {
//			echo '<h4><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h4>';
//		} else {
//			echo '<h4>' . $application_title . '</h4>';
//		}
//		echo $text;
//	}
//	echo '<h4>' . get_string('amend_application', 'local_obu_application') . '</h4>';
//} else {
//	echo '<h4>' . get_config('local_obu_application', 'support') . '</h4>';
//}

echo $OUTPUT->footer();
