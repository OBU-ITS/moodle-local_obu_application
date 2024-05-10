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
 * OBU Application - Visa page
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

local_obu_application_require_obu_login();

$home = new moodle_url('/local/obu_application/');
$url = $home . 'outside_uk_residence.php';
$back = $home . 'application.php';

$PAGE->add_body_class('limitedwidth');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_url($url);

$message = '';

$record = local_obu_application_read_applicant($USER->id, false);
$homeResidencies = array('XF', 'XH', 'XI', 'XG', 'JE', 'GG');
if(!in_array($record->residence_code, $homeResidencies) || $record->nationality_code != 'GB') {
    $message = get_string('page_outside-uk-residence_message', 'local_obu_application');
}
else {
    $message = "<p>You have reached this page by accident, please continue with your application.</p>";
}

echo $OUTPUT->header();
?>

    <div class="hero"></div>
    <style>
        .hero {
            position:absolute;
            top:0;
            left:0;
            height: 15vh;
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
            background-position: center 25%;
            filter: brightness(95%);
        }
        .hero-content {
            width: 100%;
            padding: 0.5rem 1.5rem;
            background-color: rgba(255,255,255,.8);
            backdrop-filter: saturate(180%) blur(20px);
            margin-bottom: 3rem;
        }
        .hero-content h1 {
            z-index: 100;
            position: relative;
            color: black;
        }
    </style>
    <div class="hero-content">
        <h1><?php echo get_string('page_outside-uk-residence_heading', 'local_obu_application') ?></h1>
    </div>
    <section class="block_html block card mb-3" >
        <div class="card-body p-3">

            <?php

            if ($message) {
                notice($message, $back);
            }

            ?>
        </div>
    </section>

<?php

echo $OUTPUT->footer();
