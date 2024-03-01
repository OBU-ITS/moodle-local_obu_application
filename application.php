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
 * @author     Joe Souch
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');
require_once ('./profile_contact_details_form.php');
require_once ('./profile_personal_details_form.php');
require_once ('./profile_educational_establishments_form.php');
require_once ('./profile_professional_qualification_form.php');
require_once ('./profile_current_employment_form.php');
require_once ('./profile_professional_registration_form.php');
require_once ('./profile_criminal_record_form.php');

// Try to prevent searching for sites that allow sign-up.
if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .= '<meta name="robots" content="noindex" />';

require_obu_login();

$PAGE->add_body_class('limitedwidth');
$PAGE->set_url($CFG->httpswwwroot . '/local/obu_application/application.php');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

echo $OUTPUT->header();

?>
    <h1 class="mb-4">Apply for a new module or course</h1>
    <p>
        Please complete the mandatory fields below. Detailed guidance can be <a href="application_guidance.php" target="_blank">found here</a>.
    </p>
    <p>
        If you have any queries, please contact <a href="mailto:hlscpdadmissions@brookes.ac.uk">hlscpdadmissions@brookes.ac.uk</a>.
    </p>
    <div id="accordion" class="clearfix collapsible">
        <?php

        $record = read_applicant($USER->id, false); // May not exist yet
        if (($record === false) || ($record->domicile_code == '') || ($record->domicile_code == 'ZZ')) { // Must complete the contact details first
            $message = get_string('complete_contact_details', 'local_obu_application');
        } else {
            $message = '';
        }

        $nations = get_nations();
        $areas = get_areas();
        $parameters = [
            'user' => read_user($USER->id),
            'applicant' => $record,
            'titles' => get_titles(),
            'nations' => $nations,
            'default_domicile_code' => 'GB',
            'record' => $record,
            'areas' => $areas,
            'default_birth_code' => 'GB',
            'default_nationality_code' => 'GB',
            'default_residence_code' => 'XF'
        ];

        $contactDetailsForm = new profile_contact_details_form(null, $parameters);
        $personalDetailsForm = new profile_personal_details_form(null, $parameters);
        $educationalEstablishmentsForm = new profile_educational_establishments_form(null, $parameters);
        $professionalQualificationForm = new profile_professional_qualification_form(null, $parameters);
        $currentEmploymentForm = new profile_current_employment_form(null, $parameters);
        $professionalRegistrationForm = new profile_professional_registration_form(null, $parameters);
        $criminalRecordForm = new profile_criminal_record_form(null, $parameters);
        $accordion_items = array(
            ["title" => "Contact Details", "data" => $contactDetailsForm, "last_updated" => $record->contact_details_update],
            ["title" => "Personal Details", "data" => $personalDetailsForm, "last_updated" => $record->personal_details_update],
            ["title" => "Education Establishment Attended", "data" => $educationalEstablishmentsForm, "last_updated" => $record->edu_establishments_update],
            ["title" => "Highest Professional Qualification", "data" => $professionalQualificationForm, "last_updated" => $record->pro_qualification_update],
            ["title" => "Current Employment", "data" => $currentEmploymentForm, "last_updated" => $record->current_employment_update],
            ["title" => "Professional Registration", "data" => $professionalRegistrationForm, "last_updated" => $record->pro_registration_update],
            ["title" => "Criminal Record", "data" => $criminalRecordForm, "last_updated" => $record->criminal_record_update]);

        $counter = 0;
        $date = date_create();
        $format = 'd/m/y';
        foreach ($accordion_items as $accordion_item) {
            date_timestamp_set($date, $accordion_item["last_updated"]);
            ?>
            <div class="d-flex align-items-center mb-2" id="heading<?php echo $counter ?>">
                <div class="position-relative d-flex ftoggler align-items-center position-relative mr-1">
                    <a data-toggle="collapse" href="#id_<?php echo $counter ?>_headcontainer" role="button" aria-expanded="true" aria-controls="id_<?php echo $counter ?>_headcontainer" class="btn btn-icon mr-1 icons-collapse-expand stretched-link fheader collapsed" id="collapseElement-0">
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
                <div class="position-relative  ftoggler align-items-center position-relative ml-auto">
                    <strong class="text-primary">Last updated: <?php echo date_format($date, $format) ?></strong>
                </div>
            </div>
            <div id="id_<?php echo $counter ?>_headcontainer" class="fcontainer collapseable collapse" style=""  aria-labelledby="heading<?php echo $counter ?>" data-parent="#accordion">
                <?php $accordion_item["data"]->display(); ?>
            </div>
            <?php
            $counter++;
        }
        ?>
    </div>
    <div>
        <a class="btn btn-primary" href="course.php">Start Application</a>
    </div>
<?php

echo $OUTPUT->footer();
