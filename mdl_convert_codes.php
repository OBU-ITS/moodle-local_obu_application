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
 * OBU Application - Convert Country Codes [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');

require_login();

$home = new moodle_url('/');
if (!is_siteadmin()) {
	redirect($home);
}

$nations = get_nations();
$areas = get_areas();

$domicile_map = array(
	'1615' => 'BM',
	'1619' => 'BR',
	'1626' => 'CA',
	'1628' => 'LK',
	'1631' => 'CN',
	'1645' => 'EC',
	'1648' => 'ET',
	'1655' => 'GM',
	'1658' => 'GH',
	'1659' => 'GI',
	'1661' => 'GR',
	'1669' => 'HK',
	'1670' => 'HU',
	'1672' => 'IN',
	'1674' => 'IR',
	'1675' => 'IQ',
	'1676' => 'IE',
	'1680' => 'JM',
	'1683' => 'KE',
	'1688' => 'LB',
	'1692' => 'LY',
	'1700' => 'MT',
	'1704' => 'MN',
	'1717' => 'NG',
	'1721' => 'PK',
	'1726' => 'PH',
	'1728' => 'PT',
	'1731' => 'QA',
	'1732' => 'ZW',
	'1743' => 'SA',
	'1750' => 'ZA',
	'1756' => 'CH',
	'1757' => 'SY',
	'1763' => 'TT',
	'1764' => 'AE',
	'1766' => 'TR',
	'1767' => 'UG',
	'1771' => 'US',
	'1781' => 'ZM',
	'1782' => 'ZZ',
	'1787' => 'BD',
	'1798' => 'NA',
	'1845' => 'UA',
	'1870' => 'PS',
	'1882' => 'XA',
	'2003' => 'GB',
	'2006' => 'GB',
	'2007' => 'GB',
	'2099' => 'GB',
	'2110' => 'GB',
	'2120' => 'GB',
	'2170' => 'GB',
	'2188' => 'GB',
	'2201' => 'GB',
	'2203' => 'GB',
	'2204' => 'GB',
	'2205' => 'GB',
	'2206' => 'GB',
	'2207' => 'GB',
	'2208' => 'GB',
	'2209' => 'GB',
	'2210' => 'GB',
	'2211' => 'GB',
	'2212' => 'GB',
	'2213' => 'GB',
	'2250' => 'GB',
	'2260' => 'GB',
	'2290' => 'GB',
	'2302' => 'GB',
	'2303' => 'GB',
	'2304' => 'GB',
	'2305' => 'GB',
	'2306' => 'GB',
	'2308' => 'GB',
	'2309' => 'GB',
	'2310' => 'GB',
	'2312' => 'GB',
	'2313' => 'GB',
	'2315' => 'GB',
	'2316' => 'GB',
	'2318' => 'GB',
	'2319' => 'GB',
	'2320' => 'GB',
	'2331' => 'GB',
	'2332' => 'GB',
	'2333' => 'GB',
	'2334' => 'GB',
	'2336' => 'GB',
	'2360' => 'GB',
	'2373' => 'GB',
	'2381' => 'GB',
	'2382' => 'GB',
	'2390' => 'GB',
	'2391' => 'GB',
	'2400' => 'GB',
	'2572' => 'GB',
	'2593' => 'GB',
	'2595' => 'GB',
	'2663' => 'GB',
	'2666' => 'GB',
	'2667' => 'GB',
	'2668' => 'GB',
	'2670' => 'GB',
	'2671' => 'GB',
	'2673' => 'GB',
	'2676' => 'GB',
	'2679' => 'GB',
	'2681' => 'GB',
	'2800' => 'GB',
	'2802' => 'GB',
	'2803' => 'GB',
	'2806' => 'GB',
	'2808' => 'GB',
	'2811' => 'GB',
	'2815' => 'GB',
	'2820' => 'GB',
	'2823' => 'GB',
	'2826' => 'GB',
	'2835' => 'GB',
	'2840' => 'GB',
	'2845' => 'GB',
	'2850' => 'GB',
	'2852' => 'GB',
	'2855' => 'GB',
	'2860' => 'GB',
	'2865' => 'GB',
	'2867' => 'GB',
	'2868' => 'GB',
	'2869' => 'GB',
	'2871' => 'GB',
	'2872' => 'GB',
	'2873' => 'GB',
	'2875' => 'GB',
	'2878' => 'GB',
	'2881' => 'GB',
	'2884' => 'GB',
	'2885' => 'GB',
	'2886' => 'GB',
	'2888' => 'GB',
	'2891' => 'GB',
	'2893' => 'GB',
	'2908' => 'GB',
	'2910' => 'GB',
	'2916' => 'GB',
	'2919' => 'GB',
	'2925' => 'GB',
	'2926' => 'GB',
	'2928' => 'GB',
	'2929' => 'GB',
	'2931' => 'GB',
	'2933' => 'GB',
	'2935' => 'GB',
	'2936' => 'GB',
	'2937' => 'GB',
	'2938' => 'GB'
);

$nationality_map = array(
	'806' => 'GB',
	'1602' => 'AF',
	'1603' => 'AL',
	'1604' => 'DZ',
	'1608' => 'AR',
	'1609' => 'AU',
	'1615' => 'BNO',
	'1619' => 'BR',
	'1621' => 'BG',
	'1625' => 'CM',
	'1626' => 'CA',
	'1628' => 'LK',
	'1631' => 'CN',
	'1633' => 'CG',
	'1638' => 'XC',
	'1641' => 'DK',
	'1645' => 'EC',
	'1648' => 'ET',
	'1649' => 'BNO',
	'1651' => 'FI',
	'1653' => 'FR',
	'1655' => 'GM',
	'1656' => 'DE',
	'1658' => 'GH',
	'1659' => 'BNO',
	'1661' => 'GR',
	'1665' => 'GY',
	'1669' => 'HK',
	'1670' => 'HU',
	'1672' => 'IN',
	'1673' => 'ID',
	'1674' => 'IR',
	'1675' => 'IQ',
	'1676' => 'IE',
	'1678' => 'IT',
	'1680' => 'JM',
	'1681' => 'JP',
	'1683' => 'KE',
	'1688' => 'LB',
	'1692' => 'LY',
	'1694' => 'PT',
	'1696' => 'MW',
	'1698' => 'MY',
	'1700' => 'MT',
	'1702' => 'MU',
	'1704' => 'MN',
	'1708' => 'OM',
	'1709' => 'NP',
	'1710' => 'NL',
	'1714' => 'NZ',
	'1717' => 'NG',
	'1718' => 'NO',
	'1721' => 'PK',
	'1726' => 'PH',
	'1727' => 'PL',
	'1728' => 'PT',
	'1731' => 'QA',
	'1732' => 'ZW',
	'1733' => 'RO',
	'1743' => 'SA',
	'1746' => 'SG',
	'1748' => 'SO',
	'1750' => 'ZA',
	'1751' => 'ES',
	'1755' => 'SE',
	'1756' => 'CH',
	'1757' => 'SY',
	'1760' => 'TH',
	'1763' => 'TT',
	'1766' => 'TR',
	'1767' => 'UG',
	'1768' => 'EG',
	'1771' => 'US',
	'1776' => 'BNO',
	'1781' => 'ZM',
	'1782' => 'ZZ',
	'1787' => 'BD',
	'1796' => 'US',
	'1798' => 'NA',
	'1822' => 'GF',
	'1824' => 'BNO',
	'1829' => 'BNO',
	'1832' => 'LV',
	'1833' => 'LT',
	'1834' => 'HR',
	'1835' => 'SI',
	'1842' => 'RU',
	'1845' => 'UA',
	'1849' => 'CZ',
	'1850' => 'SK',
	'1860' => 'ER',
	'1870' => 'PS'
);

$url = new moodle_url('/local/obu_application/mdl_convert_codes.php');
$heading = 'Convert Codes';
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading('Applicant/Application');
$applicants = $DB->get_records('local_obu_applicant');
foreach ($applicants as $applicant) {
	echo '<h4>' . $applicant->id . '</h4>';
	if ($applicant->domicile_code == '0' || $applicant->domicile_code == '') { // Contact details not entered (and, therefore, no applications)
		echo 'No Domicile Code';
		$applicant->domicile_code = '';
		$applicant->nationality_code = '';
	} else {
		echo 'Domicile Country: ' . $applicant->domicile_code . ' ' . $applicant->domicile_country;
		$applicant->domicile_code = convert_domicile($applicant->domicile_code);
		$applicant->domicile_country = $nations[$applicant->domicile_code];
		echo ' => ' . $applicant->domicile_code . ' ' . $applicant->domicile_country . '<br \>';
		if ($applicant->nationality_code == '0' || $applicant->nationality_code == '') { // Personal details not entered (and, therefore, no applications)
			echo 'No Nationality Code';
			$applicant->nationality_code = '';
		} else {
			echo 'Birth Country: ' . $applicant->birth_code . ' ' . $applicant->birth_country;
			$applicant->birth_code = convert_birth($applicant->birth_code);
			$applicant->birth_country = $nations[$applicant->birth_code];
			echo ' => ' . $applicant->birth_code . ' ' . $applicant->birth_country . '<br \>';
			echo 'Nationality: ' . $applicant->nationality_code . ' ' . $applicant->nationality;
			$applicant->nationality_code = convert_nationality($applicant->nationality_code);
			$applicant->nationality = $nations[$applicant->nationality_code];
			echo ' => ' . $applicant->nationality_code . ' ' . $applicant->nationality . '<br \>';
			echo 'Residence Area: ' . $applicant->residence_code . ' ' . $applicant->residence_area;
			$applicant->residence_code = convert_residence($applicant->residence_code);
			$applicant->residence_area = $areas[$applicant->residence_code];
			echo ' => ' . $applicant->residence_code . ' ' . $applicant->residence_area . '<br \>';
			$applications = get_applications($applicant->userid); // get all applications for the given user
			foreach ($applications as $application) {
				echo '<h5>' . $applicant->id . '/' . $application->id . '</h5>';
				echo 'Domicile Country: ' . $application->domicile_code . ' ' . $application->domicile_country;
				$application->domicile_code = convert_domicile($application->domicile_code);
				$application->domicile_country = $nations[$application->domicile_code];
				echo ' => ' . $application->domicile_code . ' ' . $application->domicile_country . '<br \>';
				echo 'Birth Country: ' . $application->birth_code . ' ' . $application->birth_country;
				$application->birth_code = convert_birth($application->birth_code);
				$application->birth_country = $nations[$application->birth_code];
				echo ' => ' . $application->birth_code . ' ' . $application->birth_country . '<br \>';
				echo 'Nationality: ' . $application->nationality_code . ' ' . $application->nationality;
				$application->nationality_code = convert_nationality($application->nationality_code);
				$application->nationality = $nations[$application->nationality_code];
				echo ' => ' . $application->nationality_code . ' ' . $application->nationality . '<br \>';
				echo 'Residence Area: ' . $application->residence_code . ' ' . $application->residence_area;
				$application->residence_code = convert_residence($application->residence_code);
				$application->residence_area = $areas[$application->residence_code];
				echo ' => ' . $application->residence_code . ' ' . $application->residence_area . '<br \>';
				$DB->update_record('local_obu_application', $application);
			}
		}
	}
	$DB->update_record('local_obu_applicant', $applicant);
}

echo $OUTPUT->footer();

function convert_domicile($old) {
	global $nations, $domicile_map;
	
	if ($old == '0') { // Not set
		$new = '';
	} else if (array_key_exists($old, $nations)) {
		$new = $old;
	} else if (array_key_exists($old, $domicile_map)) {
		$new = $domicile_map[$old];
	} else {
		$new = 'ZZ';
	}
	
	return $new;
}

function convert_birth($old) {
	global $nations;
	
	if (array_key_exists($old, $nations)) {
		$new = $old;
	} else {
		$new = 'ZZ';
	}
	
	return $new;
}

function convert_nationality($old) {
	global $nations, $nationality_map;
	
	if ($old == '0') { // Not set
		$new = '';
	} else if (array_key_exists($old, $nations)) {
		$new = $old;
	} else if (array_key_exists($old, $nationality_map)) {
		$new = $nationality_map[$old];
	} else {
		$new = 'ZZ';
	}
	
	return $new;
}

function convert_residence($old) {
	global $areas;
	
	if (array_key_exists($old, $areas)) {
		$new = $old;
	} else {
		$new = 'ZZ';
	}
	
	return $new;
}

