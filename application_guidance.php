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
 * OBU Application - Application Guidance
 *
 * @package    obu_application
 * @category   local
 * @author     Joe Souch
 * @copyright  2024, Oxford Brookes University
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

local_obu_application_require_obu_login();

$PAGE->add_body_class('limitedwidth');
$PAGE->set_url($CFG->httpswwwroot . '/local/obu_application/application_guidance.php');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

echo $OUTPUT->header();

?>
    <h1 class="mb-4">How to Apply</h1>
    <p>
        In the application form sections, please:
    </p>
    <ul>
        <li>Please complete all required fields in the application form</li>
        <li>Choose the course from the dropdown list</li>
        <li>Enter the start date (please choose September or January as these are the University standard start dates -
            unless you are taking a bespoke course with a non-standard start date)</li>
        <li>Add your personal statement and proceed to the next sections and complete/upload any other information that
            may be required (some courses will require references and documents to be uploaded)</li>
        <li>If you are self-funding, please enter a 'tick' in the box 'Are you a self-funding applicant?'</li>
        <li>If you work for an organisation who has confirmed that they will fund your tuition fees, please select the
            correct organisation from the drop down list. If your organisation does not appear in this list, please
            choose 'other organisation' and enter the e-mail address of the person who will be able to approve your
            application</li>
    </ul>
    <p>
        Once you have successfully submitted your application, it will go forward for approval. If your tuition fees are
        being funded by your organisation, an e-mail will be sent to the organisation or e-mail address you have entered
        in your application, for that person to approve, and let Oxford Brookes know that it is ready to be processed
        and submitted to the Faculty of Health & Life Sciences for consideration.
    </p>
<p>
    <strong>If you have any queries or problems you may contact<br />
    <a href="mailto:hlscpdadmissions@brookes.ac.uk">hlscpdadmissions@brookes.ac.uk</a>.</strong>
</p>
<?php

echo $OUTPUT->footer();