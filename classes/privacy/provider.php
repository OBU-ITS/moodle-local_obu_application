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
 * OBU Application - Privacy Subsystem implementation
 *
 * @package    local_obu_application
 * @author     Peter Welham
 * @copyright  2021, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_obu_application\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider, \core_privacy\local\request\core_userlist_provider {

	public static function get_metadata(collection $collection) : collection {
	 
		$collection->add_database_table(
			'local_obu_applicant',
			[
				'id' => 'privacy:metadata:local_obu_application:id',
				'userid' => 'privacy:metadata:local_obu_application:userid',
				'title' => 'privacy:metadata:local_obu_application:title',
				'address_1' => 'privacy:metadata:local_obu_application:address_1',
				'address_2' => 'privacy:metadata:local_obu_application:address_2',
				'address_3' => 'privacy:metadata:local_obu_application:address_3',
				'city' => 'privacy:metadata:local_obu_application:city',
				'domicile_code' => 'privacy:metadata:local_obu_application:domicile_code',
				'domicile_country' => 'privacy:metadata:local_obu_application:domicile_country',
				'postcode' => 'privacy:metadata:local_obu_application:postcode',
                'personal_email' => 'privacy:metadata:local_obu_application:personal_email',
				'birth_code' => 'privacy:metadata:local_obu_application:birth_code',
				'birth_country' => 'privacy:metadata:local_obu_application:birth_country',
				'birthdate' => 'privacy:metadata:local_obu_application:birthdate',
				'nationality_code' => 'privacy:metadata:local_obu_application:nationality_code',
				'nationality' => 'privacy:metadata:local_obu_application:nationality',
				'gender' => 'privacy:metadata:local_obu_application:gender',
				'residence_code' => 'privacy:metadata:local_obu_application:residence_code',
				'residence_area' => 'privacy:metadata:local_obu_application:residence_area',
				'p16school' => 'privacy:metadata:local_obu_application:p16school',
				'p16schoolperiod' => 'privacy:metadata:local_obu_application:p16schoolperiod',
				'p16fe' => 'privacy:metadata:local_obu_application:p16fe',
				'p16feperiod' => 'privacy:metadata:local_obu_application:p16feperiod',
				'training' => 'privacy:metadata:local_obu_application:training',
				'trainingperiod' => 'privacy:metadata:local_obu_application:trainingperiod',
				'prof_level' => 'privacy:metadata:local_obu_application:prof_level',
				'prof_award' => 'privacy:metadata:local_obu_application:prof_award',
				'prof_date' => 'privacy:metadata:local_obu_application:prof_date',
				'credit' => 'privacy:metadata:local_obu_application:credit',
				'credit_name' => 'privacy:metadata:local_obu_application:credit_name',
				'credit_organisation' => 'privacy:metadata:local_obu_application:credit_organisation',
				'emp_place' => 'privacy:metadata:local_obu_application:emp_place',
				'emp_area' => 'privacy:metadata:local_obu_application:emp_area',
				'emp_title' => 'privacy:metadata:local_obu_application:emp_title',
				'emp_prof' => 'privacy:metadata:local_obu_application:emp_prof',
                'professional_registration' => 'privacy:metadata:local_obu_application:professional_registration',
				'prof_reg_no' => 'privacy:metadata:local_obu_application:prof_reg_no',
				'criminal_record' => 'privacy:metadata:local_obu_application:criminal_record',
				'contact_details_update' => 'privacy:metadata:local_obu_application:contact_details_update',
                'criminal_record_update' => 'privacy:metadata:local_obu_application:criminal_record_update',
                'current_employment_update' => 'privacy:metadata:local_obu_application:current_employment_update',
                'edu_establishments_update' => 'privacy:metadata:local_obu_application:edu_establishments_update',
                'personal_details_update' => 'privacy:metadata:local_obu_application:personal_details_update',
                'pro_qualification_update' => 'privacy:metadata:local_obu_application:pro_qualification_update',
                'pro_registration_update' => 'privacy:metadata:local_obu_application:pro_registration_update',
				'course_code' => 'privacy:metadata:local_obu_application:course_code',
				'course_name' => 'privacy:metadata:local_obu_application:course_name',
				'course_date' => 'privacy:metadata:local_obu_application:course_date',
				'studying' => 'privacy:metadata:local_obu_application:studying',
				'student_number' => 'privacy:metadata:local_obu_application:student_number',
				'statement' => 'privacy:metadata:local_obu_application:statement',
                'visa_requirement' => 'privacy:metadata:local_obu_application:visa_requirement',
                'visa_data' => 'privacy:metadata:local_obu_application:visa_data',
				'supplement_data' => 'privacy:metadata:local_obu_application:supplement_data',
				'course_update' => 'privacy:metadata:local_obu_application:course_update'
			],
			'privacy:metadata:local_obu_applicant'
		);

		$collection->add_database_table(
			'local_obu_application',
			[
				'id' => 'privacy:metadata:local_obu_application:id',
				'userid' => 'privacy:metadata:local_obu_application:userid',
				'title' => 'privacy:metadata:local_obu_application:title',
				'firstname' => 'privacy:metadata:local_obu_application:firstname',
				'lastname' => 'privacy:metadata:local_obu_application:lastname',
				'address_1' => 'privacy:metadata:local_obu_application:address_1',
				'address_2' => 'privacy:metadata:local_obu_application:address_2',
				'address_3' => 'privacy:metadata:local_obu_application:address_3',
				'city' => 'privacy:metadata:local_obu_application:city',
				'domicile_code' => 'privacy:metadata:local_obu_application:domicile_code',
				'domicile_country' => 'privacy:metadata:local_obu_application:domicile_country',
				'postcode' => 'privacy:metadata:local_obu_application:postcode',
				'home_phone' => 'privacy:metadata:local_obu_application:home_phone',
				'mobile_phone' => 'privacy:metadata:local_obu_application:mobile_phone',
				'email' => 'privacy:metadata:local_obu_application:email',
                'personal_email' => 'privacy:metadata:local_obu_application:personal_email',
				'birth_code' => 'privacy:metadata:local_obu_application:birth_code',
				'birth_country' => 'privacy:metadata:local_obu_application:birth_country',
				'birthdate' => 'privacy:metadata:local_obu_application:birthdate',
				'nationality_code' => 'privacy:metadata:local_obu_application:nationality_code',
				'nationality' => 'privacy:metadata:local_obu_application:nationality',
				'gender' => 'privacy:metadata:local_obu_application:gender',
				'residence_code' => 'privacy:metadata:local_obu_application:residence_code',
				'residence_area' => 'privacy:metadata:local_obu_application:residence_area',
				'p16school' => 'privacy:metadata:local_obu_application:p16school',
				'p16schoolperiod' => 'privacy:metadata:local_obu_application:p16schoolperiod',
				'p16fe' => 'privacy:metadata:local_obu_application:p16fe',
				'p16feperiod' => 'privacy:metadata:local_obu_application:p16feperiod',
				'training' => 'privacy:metadata:local_obu_application:training',
				'trainingperiod' => 'privacy:metadata:local_obu_application:trainingperiod',
				'prof_level' => 'privacy:metadata:local_obu_application:prof_level',
				'prof_award' => 'privacy:metadata:local_obu_application:prof_award',
				'prof_date' => 'privacy:metadata:local_obu_application:prof_date',
				'credit' => 'privacy:metadata:local_obu_application:credit',
				'credit_name' => 'privacy:metadata:local_obu_application:credit_name',
				'credit_organisation' => 'privacy:metadata:local_obu_application:credit_organisation',
				'emp_place' => 'privacy:metadata:local_obu_application:emp_place',
				'emp_area' => 'privacy:metadata:local_obu_application:emp_area',
				'emp_title' => 'privacy:metadata:local_obu_application:emp_title',
				'emp_prof' => 'privacy:metadata:local_obu_application:emp_prof',
				'prof_reg_no' => 'privacy:metadata:local_obu_application:prof_reg_no',
				'criminal_record' => 'privacy:metadata:local_obu_application:criminal_record',
				'course_code' => 'privacy:metadata:local_obu_application:course_code',
				'course_name' => 'privacy:metadata:local_obu_application:course_name',
				'course_date' => 'privacy:metadata:local_obu_application:course_date',
				'studying' => 'privacy:metadata:local_obu_application:studying',
				'student_number' => 'privacy:metadata:local_obu_application:student_number',
				'statement' => 'privacy:metadata:local_obu_application:statement',
                'visa_requirement' => 'privacy:metadata:local_obu_application:visa_requirement',
                'visa_data' => 'privacy:metadata:local_obu_application:visa_data',
				'supplement_data' => 'privacy:metadata:local_obu_application:supplement_data',
				'self_funding' => 'privacy:metadata:local_obu_application:self_funding',
				'manager_email' => 'privacy:metadata:local_obu_application:manager_email',
				'declaration' => 'privacy:metadata:local_obu_application:declaration',
				'funder_email' => 'privacy:metadata:local_obu_application:funder_email',
				'funding_method' => 'privacy:metadata:local_obu_application:funding_method',
				'funding_id' => 'privacy:metadata:local_obu_application:funding_id',
				'funding_organisation' => 'privacy:metadata:local_obu_application:funding_organisation',
				'funder_name' => 'privacy:metadata:local_obu_application:funder_name',
                'invoice_ref' => 'privacy:metadata:local_obu_application:invoice_ref',
                'invoice_address' => 'privacy:metadata:local_obu_application:invoice_address',
                'invoice_email' => 'privacy:metadata:local_obu_application:invoice_email',
                'invoice_phone' => 'privacy:metadata:local_obu_application:invoice_phone',
                'invoice_contact' => 'privacy:metadata:local_obu_application:invoice_contact',
                'fund_programme' => 'privacy:metadata:local_obu_application:fund_programme',
                'fund_module_1' => 'privacy:metadata:local_obu_application:fund_module_1',
                'fund_module_2' => 'privacy:metadata:local_obu_application:fund_module_2',
                'fund_module_3' => 'privacy:metadata:local_obu_application:fund_module_3',
                'fund_module_4' => 'privacy:metadata:local_obu_application:fund_module_4',
                'fund_module_5' => 'privacy:metadata:local_obu_application:fund_module_5',
                'fund_module_6' => 'privacy:metadata:local_obu_application:fund_module_6',
                'fund_module_7' => 'privacy:metadata:local_obu_application:fund_module_7',
                'fund_module_8' => 'privacy:metadata:local_obu_application:fund_module_8',
                'fund_module_9' => 'privacy:metadata:local_obu_application:fund_module_9',
				'application_date' => 'privacy:metadata:local_obu_application:application_date',
                'approval_level' => 'privacy:metadata:local_obu_application:approval_level',
				'approval_state' => 'privacy:metadata:local_obu_application:approval_state',
				'approval_1_comment' => 'privacy:metadata:local_obu_application:approval_1_comment',
				'approval_1_date' => 'privacy:metadata:local_obu_application:approval_1_date',
				'approval_2_comment' => 'privacy:metadata:local_obu_application:approval_2_comment',
				'approval_2_date' => 'privacy:metadata:local_obu_application:approval_2_date',
				'approval_3_comment' => 'privacy:metadata:local_obu_application:approval_3_comment',
				'approval_3_date' => 'privacy:metadata:local_obu_application:approval_3_date',
                'admissions_xfer' => 'privacy:metadata:local_obu_application:admissions_xfer',
                'finance_xfer' => 'privacy:metadata:local_obu_application:finance_xfer'
			],
			'privacy:metadata:local_obu_application'
		);

		return $collection;
	}

	public static function get_contexts_for_userid(int $userid) : contextlist {

		$sql = "SELECT DISTINCT c.id FROM {context} c
			JOIN {local_obu_applicant} fd ON fd.userid = c.instanceid
			WHERE (c.contextlevel = :contextlevel) AND (c.instanceid = :userid)";

		$params = [
			'contextlevel' => CONTEXT_USER,
			'userid' => $userid
		];

		$contextlist = new \core_privacy\local\request\contextlist();
		$contextlist->add_from_sql($sql, $params);

		return $contextlist;
	} 

	public static function export_user_data(approved_contextlist $contextlist) {
		global $DB;

		if (empty($contextlist->count())) {
			return;
		}

		$user = $contextlist->get_user();

		foreach ($contextlist->get_contexts() as $context) {

			if ($context->contextlevel != CONTEXT_USER) {
				continue;
			}
			
			$rec  = $DB->get_record('local_obu_applicant', ['userid' => $user->id]);
			$data = new \stdClass;
			$data->id = $rec->id;
			$data->userid = $rec->userid;
			$data->title = $rec->title;
			$data->address_1 = $rec->address_1;
			$data->address_2 = $rec->address_2;
			$data->address_3 = $rec->address_3;
			$data->city = $rec->city;
			$data->domicile_code = $rec->domicile_code;
			$data->domicile_country = $rec->domicile_country;
			$data->postcode = $rec->postcode;
            $data->personal_email = $rec->personal_email;
			$data->birth_code = $rec->birth_code;
			$data->birth_country = $rec->birth_country;
			if ($rec->birthdate == 0) {
				$data->birthdate = '';
			} else {
				$data->birthdate = transform::datetime($rec->birthdate);
			}
			$data->nationality_code = $rec->nationality_code;
			$data->nationality = $rec->nationality;
			$data->gender = $rec->gender;
			$data->residence_code = $rec->residence_code;
			$data->residence_area = $rec->residence_area;
			$data->p16school = $rec->p16school;
			$data->p16schoolperiod = $rec->p16schoolperiod;
			$data->p16fe = $rec->p16fe;
			$data->p16feperiod = $rec->p16feperiod;
			$data->training = $rec->training;
			$data->trainingperiod = $rec->trainingperiod;
			$data->prof_level = $rec->prof_level;
			$data->prof_award = $rec->prof_award;
			$data->prof_date = $rec->prof_date;
			if ($rec->prof_date == 0) {
				$data->prof_date = '';
			} else {
				$data->prof_date = transform::datetime($rec->prof_date);
			}
			if ($rec->credit == 1) {
				$data->credit = 'Y';
			} else {
				$data->credit = 'N';
			}
			$data->credit_name = $rec->credit_name;
			$data->credit_organisation = $rec->credit_organisation;
			$data->emp_place = $rec->emp_place;
			$data->emp_area = $rec->emp_area;
			$data->emp_title = $rec->emp_title;
			$data->emp_prof = $rec->emp_prof;
            if ($rec->professional_registration == 1) {
                $data->professional_registration = 'Y';
            } else {
                $data->professional_registration = 'N';
            }
			$data->prof_reg_no = $rec->prof_reg_no;
			if ($rec->criminal_record == 1) {
				$data->criminal_record = 'Y';
			} else {
				$data->criminal_record = 'N';
			}
			if ($rec->profile_update == 0) {
				$data->profile_update = '';
			} else {
				$data->profile_update = transform::datetime($rec->profile_update);
			}
			$data->course_code = $rec->course_code;
			$data->course_name = $rec->course_name;
			$data->course_date = $rec->course_date;
			if ($rec->studying == '2') {
                $data->studying = 'N';
			} else {
                $data->studying = 'Y';
			}
			$data->student_number = $rec->student_number;
			$data->statement = $rec->statement;
            $data->visa_requirement = $rec->visa_requirement;
            if ($rec->visa_data === NULL) {
                $data->visa_data = '';
            } else {
                $xml = new \SimpleXMLElement($rec->visa_data);
                $fields = array();
                foreach ($xml as $key => $value) {
                    $fields[$key] = (string)$value;
                }
                $data->visa_data = $fields;
            }
			if ($rec->supplement_data === NULL) {
				$data->supplement_data = '';
			} else {
				$xml = new \SimpleXMLElement($rec->supplement_data);
				$fields = array();
				foreach ($xml as $key => $value) {
					$fields[$key] = (string)$value;
				}
				$data->supplement_data = $fields;
			}
			if ($rec->course_update == 0) {
				$data->course_update = '';
			} else {
				$data->course_update = transform::datetime($rec->course_update);
			}
			writer::with_context($context)->export_data([get_string('privacy:applications', 'local_obu_application')], $data);

			$recs = $DB->get_records('local_obu_application', ['userid' => $user->id]);
			foreach ($recs as $rec) {
				$data = new \stdClass;
				$data->id = $rec->id;
				$data->userid = $rec->userid;
				$data->title = $rec->title;
				$data->firstname = $rec->firstname;
				$data->lastname = $rec->lastname;
				$data->address_1 = $rec->address_1;
				$data->address_2 = $rec->address_2;
				$data->address_3 = $rec->address_3;
				$data->city = $rec->city;
				$data->domicile_code = $rec->domicile_code;
				$data->domicile_country = $rec->domicile_country;
				$data->postcode = $rec->postcode;
				$data->home_phone = $rec->home_phone;
				$data->mobile_phone = $rec->mobile_phone;
				$data->email = $rec->email;
                $data->personal_email = $rec->personal_email;
				$data->birth_code = $rec->birth_code;
				$data->birth_country = $rec->birth_country;
				if ($rec->birthdate == 0) {
					$data->birthdate = '';
				} else {
					$data->birthdate = transform::datetime($rec->birthdate);
				}
				$data->nationality_code = $rec->nationality_code;
				$data->nationality = $rec->nationality;
				$data->gender = $rec->gender;
				$data->residence_code = $rec->residence_code;
				$data->residence_area = $rec->residence_area;
				$data->p16school = $rec->p16school;
				$data->p16schoolperiod = $rec->p16schoolperiod;
				$data->p16fe = $rec->p16fe;
				$data->p16feperiod = $rec->p16feperiod;
				$data->training = $rec->training;
				$data->trainingperiod = $rec->trainingperiod;
				$data->prof_level = $rec->prof_level;
				$data->prof_award = $rec->prof_award;
				if ($rec->prof_date == 0) {
					$data->prof_date = '';
				} else {
					$data->prof_date = transform::datetime($rec->prof_date);
				}
				if ($rec->credit == 1) {
					$data->credit = 'Y';
				} else {
					$data->credit = 'N';
				}
				$data->credit_name = $rec->credit_name;
				$data->credit_organisation = $rec->credit_organisation;
				$data->emp_place = $rec->emp_place;
				$data->emp_area = $rec->emp_area;
				$data->emp_title = $rec->emp_title;
				$data->emp_prof = $rec->emp_prof;
				$data->prof_reg_no = $rec->prof_reg_no;
				if ($rec->criminal_record == 1) {
					$data->criminal_record = 'Y';
				} else {
					$data->criminal_record = 'N';
				}
				$data->course_code = $rec->course_code;
				$data->course_name = $rec->course_name;
				$data->course_date = $rec->course_date;
				if ($rec->studying == '2') {
                    $data->studying = 'N';
				} else {
                    $data->studying = 'Y';
				}
				$data->student_number = $rec->student_number;
				$data->statement = $rec->statement;
                $data->visa_requirement = $rec->visa_requirement;
                if ($rec->visa_data === NULL) {
                    $data->visa_data = '';
                } else {
                    $xml = new \SimpleXMLElement($rec->visa_data);
                    $fields = array();
                    foreach ($xml as $key => $value) {
                        $fields[$key] = (string)$value;
                    }
                    $data->visa_data = $fields;
                }
				if ($rec->supplement_data === NULL) {
					$data->supplement_data = '';
				} else {
					$xml = new \SimpleXMLElement($rec->supplement_data);
					$fields = array();
					foreach ($xml as $key => $value) {
						$fields[$key] = (string)$value;
					}
					$data->supplement_data = $fields;
				}
				if ($rec->self_funding == 1) {
					$data->self_funding = 'Y';
				} else {
					$data->self_funding = 'N';
				}
				$data->manager_email = $rec->manager_email;
				if ($rec->declaration == 1) {
					$data->declaration = 'Y';
				} else {
					$data->declaration = 'N';
				}
				$data->funder_email = $rec->funder_email;
				$data->funding_method = $rec->funding_method;
				$data->funding_id = $rec->funding_id;
				$data->funding_organisation = $rec->funding_organisation;
                $data->funder_name = $rec->funder_name;
				$data->invoice_ref = $rec->invoice_ref;
                $data->invoice_address = $rec->invoice_address;
                $data->invoice_email = $rec->invoice_email;
                $data->invoice_phone = $rec->invoice_phone;
                $data->invoice_contact = $rec->invoice_contact;
				if ($rec->application_date == 0) {
					$data->application_date = '';
				} else {
					$data->application_date = transform::datetime($rec->application_date);
				}
				if ($rec->approval_state == 1) {
					$data->approval_state = get_string('rejected', 'local_obu_application');
				} else if ($rec->approval_state == 2) {
					$data->approval_state = get_string('approved', 'local_obu_application');
				} else {
					$data->approval_state = get_string('submitted', 'local_obu_application');
				}
				$data->approval_1_comment = $rec->approval_1_comment;
				if ($rec->approval_1_date == 0) {
					$data->approval_1_date = '';
				} else {
					$data->approval_1_date = transform::datetime($rec->approval_1_date);
				}
				$data->approval_2_comment = $rec->approval_2_comment;
				if ($rec->approval_2_date == 0) {
					$data->approval_2_date = '';
				} else {
					$data->approval_2_date = transform::datetime($rec->approval_2_date);
				}
				$data->approval_3_comment = $rec->approval_3_comment;
				if ($rec->approval_3_date == 0) {
					$data->approval_3_date = '';
				} else {
					$data->approval_3_date = transform::datetime($rec->approval_3_date);
				}

				writer::with_context($context)->export_data([get_string('privacy:applications', 'local_obu_application'), get_string('privacy:application', 'local_obu_application', $rec->id)], $data);
			}
		}

		return;
	}

	public static function delete_data_for_all_users_in_context(\context $context) {

		if ($context->contextlevel == CONTEXT_USER) {
			self::delete_data($context->instanceid);
		}
		
		return;
	}

	public static function delete_data_for_user(approved_contextlist $contextlist) {

		if (empty($contextlist->count())) {
			return;
		}

		$userid = $contextlist->get_user()->id;
		foreach ($contextlist->get_contexts() as $context) {
			if ($context->contextlevel == CONTEXT_USER) {
				self::delete_data($userid);
			}
		}
		
		return;
	}

	public static function get_users_in_context(userlist $userlist) {

		$context = $userlist->get_context();
		if ($context->contextlevel == CONTEXT_USER) {
			$userlist->add_user($context->instanceid);
		}

		return;
	}

	public static function delete_data_for_users(approved_userlist $userlist) {

		$context = $userlist->get_context();
		if ($context->contextlevel == CONTEXT_USER) {
			self::delete_data($context->instanceid);
		}

		return;
	}
	
	static function delete_data($userid) {
		global $DB;

		// Firstly, delete any outstanding approvals
		$recs = $DB->get_records('local_obu_application', ['userid' => $userid]);
		foreach ($recs as $rec) {
			$DB->delete_records('local_obu_approval', ['application_id' => $rec->id]);
		}

		// Secondly, delete any applications
		$DB->delete_records('local_obu_application', ['userid' => $userid]);

		// Now, the main event
		$DB->delete_records('local_obu_applicant', ['userid' => $userid]);

		return;
	}
}
