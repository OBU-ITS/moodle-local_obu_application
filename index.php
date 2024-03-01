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

// Try to prevent searching for sites that allow sign-up.
if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .= '<meta name="robots" content="noindex" />';

require_obu_login();

$process = new moodle_url('/local/obu_application/process.php');

$PAGE->add_body_class('mediumwidth');
$PAGE->set_url($CFG->httpswwwroot . '/local/obu_application/index.php');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

echo $OUTPUT->header();

$funder = is_funder();
$lang_ext = $funder ? '_funder' : '';

?>
    <div class="hero"></div>
<style>
    .hero {
        position:absolute;
        top:0;
        left:0;
        height: 70vh;
        width:100%;
    }
    .hero::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url(/local/obu_application/moodle-hls-login-bg.jpg);
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center 70%;
        filter: brightness(95%);
    }
    .hero-content {
        width: 100%;
        padding: 3rem 3rem 1rem;
        background-color: rgba(255,255,255,.8);
        backdrop-filter: saturate(180%) blur(20px);
    }
    .hero-content .intro {
        font-size: 22px;
        margin-bottom: 1rem;
    }
    .hero-content .cta {
        margin-bottom: 2rem;
    }
    .hero-content .cta a{
        padding: 0.5rem 1.5rem;
        font-size: 20px;
    }
    .hls-history {
        margin-top: 3rem;
    }
    .hero-content h1 {
        z-index: 100;
        position: relative;
        color: black;
        font-size: 50px;
        margin-bottom: 2rem;
    }
</style>
    <div class="hero-content">
        <h1 class="h2"><?php echo get_string('index_welcome_heading' . $lang_ext, 'local_obu_application', array('name' => $USER->firstname)); ?></h1>
        <p class="intro">
            <?php echo get_string('index_welcome_text' . $lang_ext, 'local_obu_application');?>
        </p>
        <p class="cta">
            <a class="btn btn-primary" href="application.php">Start your application</a>
        </p>
        <hr class="divider">
        <p class="footer">
            <?php echo get_string('index_welcome_support' . $lang_ext, 'local_obu_application');?>
        </p>
    </div>
    <section class="hls-history block_html block card mb-3" >
        <div class="card-body p-3">
            <h4 class="card-title d-inline"><?php echo get_string('index_overview_heading' . $lang_ext, 'local_obu_application'); ?></h4>
            <div class="card-text content mt-3">
<?php

$manager = is_manager();
$process = new moodle_url('/local/obu_application/process.php');

if($funder) {
    $approvals = get_approvals($USER->email); // get outstanding approval requests
    if ($approvals) {
        foreach ($approvals as $approval) {
            $application = read_application($approval->application_id);
            $application_title = $application->firstname . ' ' . $application->lastname . ' (Application Ref HLS/' . $application->id . ')';

                echo '<hr class="divider">';

            echo '<h5><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h5>';
            echo get_application_status($USER->id, $application, $manager);
            $first = false;
        }
    } else {
        echo get_string('index_overview_empty_funder', 'local_obu_application');
    }
}
else {
    $applications = get_applications($USER->id); // get all applications for the user
    $show_history_link = false;
    if ($applications) {
        $first = true;
        foreach ($applications as $application) {
            $text = get_application_status($USER->id, $application, $manager);
            $button = get_application_button_text($USER->id, $application, $manager);
            $application_title = $application->course_code . ' ' . $application->course_name . ' (Application Ref HLS/' . $application->id . ')';
            echo '<hr class="divider">';

            if (($button != 'submit') || $manager) {
                echo '<h5><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h5>';
            }
            else {
                echo '<h5>' . $application_title . '</h5>';
            }
            echo $text;
            $first = false;
        }
    } else {
        echo get_string('index_overview_empty', 'local_obu_application');
    }

    if($show_history_link) {
?>
    <hr class="divider">
    <div class="footer">
        <p><a href="#">See full history</a></p>
    </div>

<?php
    }
}

?>


            </div>

        </div>

    </section>
<?php

echo $OUTPUT->footer();
