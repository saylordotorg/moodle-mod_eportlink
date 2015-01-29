<?php

// This file is part of the eportlink module for Moodle - http://moodle.org/
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
 * Certificate module core interaction API
 *
 * @package    mod
 * @subpackage eportlink
 * @copyright  eportlink <contact@saylor.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
*	eportlink_quiz_subimission_handler
*
*	Method called upon quiz submission event.
*/
function eportlink_quiz_submission_handler($event) {
	global $DB, $CFG;
	require_once($CFG->dirroot . '/mod/quiz/lib.php');

	$attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
	$quiz = $event->get_record_snapshot('quiz', $attempt->quiz);
	$user = $DB->get_record('user', array('id' => $event->relateduserid));
    $quiz_event = $event;
	//Handle grading and whether student passed exam
	$student_grade = ( quiz_get_best_grade($quiz, $user->id) / $quiz->grade ) * 100;
	//round(100 * (quiz_get_best_grade($quiz, $user->id) / $quiz->grade));

	$student_pass = false;
	$quiz_is_final = false;

	if (strpos($quiz->name, 'Final') != false) {
		$quiz_is_final = true;
	}
	else {
		return;
	}

	// If the quiz is a final and student passed, we build the data array and pass to eportfolio
	if ($student_grade >= 70 && $quiz_is_final == true) {
		eportlink_build_data($attempt, $quiz, $user, $student_grade, $quiz_event);

	}
}

function eportlink_build_data($attempt, $quiz, $user, $student_grade, $quiz_event) {
	global $DB, $CFG, $COURSE;
	require_once($CFG->dirroot . '/mod/quiz/lib.php');

	$data = array();
	$data['username'] = $user->username;
	$data['email'] = $user->email;
	$data['course_identifier'] = $COURSE->shortname;
	$data['quiz_name'] = $quiz->name;
	$data['exam_completed_at'] = time();
	$data['exam_score'] = $student_grade;
	$data['secret'] = $CFG->eportlink_api_key;
	$data['completed_course'] = "true";
	$data['certificate_code'] = '';

	eportlink_notify_eportfolio($data);

}

function eportlink_notify_eportfolio($data) {
	global $CFG;
	require_once ($CFG->libdir . '/filelib.php');

	$port = '';
	//$host = 'staging.saylor.org';
	$host = 'eportfolio.saylor.org';
	// $host = 'localhost';
	// $port = ':3000';
	$url = 'https://' . $host . $port . '/api/exam_completed';
	// request headers
	$headers = array();
	$headers['Host'] = $host;
	$headers['Content-Type'] = 'application/x-www-form-urlencoded';
	$headers['User-Agent'] = 'moodle-eportfolio-add-on/PHP';
	// make the request

	$response = download_file_content($url, $headers, $data, false, 1300, 120, true, NULL, false);

	if ($response != "Exam recorded") {
	$error_msg = 'Failed request - '.$url.'?=';
	if (isset($data['completed_course'])) {
	$error_msg = $error_msg.'completed_course='.$data['completed_course'];
	}
	if (isset($data['certificate_code'])) {
	$error_msg = $error_msg.'&certificate_code='.$data['certificate_code'];
	}
	if (isset($data['username'])) {
	$error_msg = $error_msg.'&username='.$data['username'];
	}
	if (isset($data['email'])) {
	$error_msg = $error_msg.'&email='.$data['email'];
	}
	if (isset($data['course_identifier'])) {
	$error_msg = $error_msg.'&course_identifier='.$data['course_identifier'];
	}
	if (isset($data['quiz_name'])) {
	$error_msg = $error_msg.'&quiz_name='.$data['quiz_name'];
	}
	if (isset($data['exam_completed_at'])) {
	$error_msg = $error_msg.'&exam_completed_at='.$data['exam_completed_at'];
	}
	if (isset($data['exam_score'])) {
	$error_msg = $error_msg.'&exam_score='.$data['exam_score'];
	}
	debugging($error_msg.'&&Response:'.$response, DEBUG_ALL);
	error($error_msg.'<br/>'.$response);

	}
}

/**
 * List all of the ceritificates with a specific achievement id
 *
 * @param string $achievement_id
 * @return array[stdClass] $certificates
 */
function eportlink_get_issued($achievement_id) {
	global $CFG;

	$curl = curl_init('https://api.eportlink.com/v1/credentials?full_view=true&achievement_id='.urlencode($achievement_id));
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->eportlink_api_key.'"' ) );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	if(!$result = json_decode( curl_exec($curl) )) {
	  // throw API exception
	  // include the achievement id that triggered the error
	  // direct the user to eportlink's support
	  // dump the achievement id to debug_info
	  throw new moodle_exception('getissuederror', 'eportlink', 'https://eportlink.com/contact/support', $achievement_id, $achievement_id);
	}
	curl_close($curl);
	return $result->credentials;
}

/*
 * eportlink_issue_default_certificate
 * 
 */
function eportlink_issue_default_certificate($certificate_id, $name, $email, $grade, $quiz_name) {
	global $DB, $CFG;

	// Issue certs
	$eportlink_certificate = $DB->get_record('eportlink', array('id'=>$certificate_id));

	$certificate = array();
  $course_url = new moodle_url('/course/view.php', array('id' => $eportlink_certificate->course));
	$certificate['name'] = $eportlink_certificate->name;
	$certificate['achievement_id'] = $eportlink_certificate->achievementid;
	$certificate['description'] = $eportlink_certificate->description;
  $certificate['course_link'] = $course_url->__toString();
	$certificate['recipient'] = array('name' => $name, 'email'=> $email);
	if($grade) {
		$certificate['evidence_items'] = array( array('string_object' => $grade, 'description' => $quiz_name, 'custom'=> true, 'category' => 'grade' ));
	}

	$curl = curl_init('https://api.eportlink.com/v1/credentials');
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( array('credential' => $certificate) ));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->eportlink_api_key.'"' ) );
	if(!$result = curl_exec($curl)) {
		// TODO - log this because an exception cannot be thrown in an event callback
	}
	curl_close($curl);
	return json_decode($result);
}

/*
 * eportlink_log_creation
 */
function eportlink_log_creation($certificate_id, $course_id, $user_id) {
	global $DB;

	// Get context
	$eportlink_mod = $DB->get_record('modules', array('name' => 'eportlink'), '*', MUST_EXIST);
	$cm = $DB->get_record('course_modules', array('course' => $course_id, 'module' => $eportlink_mod->id), '*', MUST_EXIST);
	$context = context_module::instance($cm->id);

	return \mod_eportlink\event\certificate_created::create(array(
	  'objectid' => $certificate_id,
	  'context' => $context,
	  'relateduserid' => $user_id
	));
}

/*
 * Quiz submission handler (checks for a completed course)
 *
 * @param core/event $event quiz mod attempt_submitted event
 */
function eportlink_quiz_submission_handler_original($event) {
	global $DB, $CFG;
	require_once($CFG->dirroot . '/mod/quiz/lib.php');

	$eportlink_certificate = $DB->get_record('eportlink', array('course' => $event->courseid));
	$attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
	$quiz    = $event->get_record_snapshot('quiz', $attempt->quiz);
	$user 	 = $DB->get_record('user', array('id' => $event->relateduserid));


	// check for the existance of a certificate and an auto-issue rule
	if( $eportlink_certificate and ($eportlink_certificate->finalquiz or $eportlink_certificate->completionactivities) ) {

		// check which quiz is used as the deciding factor in this course
		if($quiz->id == $eportlink_certificate->finalquiz) {
			$certificate_exists = eportlink_check_for_existing_certificate (
				$eportlink_certificate->achievementid, $user
			);

			// check for an existing certificate
			if(!$certificate_exists) {
				$users_grade = ( quiz_get_best_grade($quiz, $user->id) / $quiz->grade ) * 100;
				$grade_is_high_enough = ($users_grade >= $eportlink_certificate->passinggrade);

				// check for pass
				if($grade_is_high_enough) {
					// issue a ceritificate
					$api_response = eportlink_issue_default_certificate( $eportlink_certificate->id, fullname($user), $user->email, (string) $users_grade, $quiz->name);
					$certificate_event = \mod_eportlink\event\certificate_created::create(array(
					  'objectid' => $api_response->credential->id,
					  'context' => context_module::instance($event->contextinstanceid),
					  'relateduserid' => $event->relateduserid
					));
					$certificate_event->trigger();
				}
			}
		}

		$completion_activities = unserialize_completion_array($eportlink_certificate->completionactivities);
		// if this quiz is in the completion activities
		if( isset($completion_activities[$quiz->id]) ) {
			$completion_activities[$quiz->id] = true;
			$quiz_attempts = $DB->get_records('quiz_attempts', array('userid' => $user->id, 'state' => 'finished'));
			foreach($quiz_attempts as $quiz_attempt) {
				// if this quiz was already attempted, then we shouldn't be issuing a certificate
				if( $quiz_attempt->quiz == $quiz->id && $quiz_attempt->attempt > 1 ) {
					return null;
				}
				// otherwise, set this quiz as completed
				if( isset($completion_activities[$quiz_attempt->quiz]) ) {
					$completion_activities[$quiz_attempt->quiz] = true;
				}
			}

			// but was this the last required activity that was completed?
			$course_complete = true;
			foreach($completion_activities as $is_complete) {
				if(!$is_complete) {
					$course_complete = false;
				}
			}
			// if it was the final activity
			if($course_complete) {
				$certificate_exists = eportlink_check_for_existing_certificate (
					$eportlink_certificate->achievementid, $user
				);
				// make sure there isn't already a certificate
				if(!$certificate_exists) {
					// and issue a ceritificate
					$api_response = eportlink_issue_default_certificate( $eportlink_certificate->id, fullname($user), $user->email, null, null);
					$certificate_event = \mod_eportlink\event\certificate_created::create(array(
					  'objectid' => $api_response->credential->id,
					  'context' => context_module::instance($event->contextinstanceid),
					  'relateduserid' => $event->relateduserid
					));
					$certificate_event->trigger();
				}
			}
		}
	}
}

function eportlink_check_for_existing_certificate($achievement_id, $user) {
	global $DB;
	$certificate_exists = false;
	$certificates = eportlink_get_issued($achievement_id);

	foreach ($certificates as $certificate) {
		if($certificate->recipient->email == $user->email) {
			$certificate_exists = true;
		}
	}
	return $certificate_exists;
}

function serialize_completion_array($completion_array) {
	return base64_encode(serialize( (array)$completion_array ));
}

function unserialize_completion_array($completion_object) {
	return (array)unserialize(base64_decode( $completion_object ));
}

function eportlink_get_info_quiz_attempt_submitted($event) {
global $DB;
error_log(print_r("\nDEBUG: eportlink_get_info_quiz_attempt_submitted called\n", true));
error_log(print_r($event, true));
// Gather information necessary for eportlink_notify_eportfolio_exam_completed
// when quiz_attempt_submitted event is triggered.
$userid = $event->data['userid'];
$courseid = $event->data['courseid'];
$quizid = $event->data['other']['quizid'];
$quiz = $DB->get_record('quiz', array('id' => $quizid));
$attempts = quiz_get_user_attempts($quizid, $userid);
$bestgrade = quiz_calculate_best_grade($quiz, $attempts);
$grade = new stdClass();
$grade->quiz = $quizid;
$grade->userid = $userid;
$grade->grade = $bestgrade;
$grade->timemodified = $event->data['timecreated'];
if (eportlink_notify_eportfolio_exam_completed($quiz, $grade, $courseid)) {
return true;
}
else {
return false;
}
}
/*
function eportlink_notify_eportfolio_exam_completed($quiz, $grade, $courseid) {
global $CFG, $USER, $COURSE, $DB;
$user = $DB->get_record('user', array('id' => $grade->userid));
//print_r('notify_eportfolio_exam_completed called.');
// build an array with the form data to send
$data = array();
$data['username'] = $user->username;
$data['email'] = $user->email;
$data['course_identifier'] = $DB->get_record('course', array('id' => $courseid));
$data['quiz_name'] = $quiz->name;
$data['exam_completed_at'] = $grade->timemodified ? $grade->timemodified : time();
$exam_score = round(100*$grade->grade/$quiz->grade); print_r('Set exam_score: ', $exam_score, '$grade-> grade ', $grade->grade, '$quiz->grade ', $quiz->grade);
$data['exam_score'] = $exam_score;
//$secret = 'tNrY2mPXGS533jkkxDxR4j8YDkSm6krgNqdR1bqEny2NMRyHd7hBqQvU8dYFF9';
$secret = $CFG->eportlink_api_key;
$data['secret'] = $secret;
// this is a final
if (strpos($quiz->name, 'Final') != false) {
// get the user's grade for the course
require_once($CFG->libdir . '/gradelib.php');
$str_grade = grade_format_gradevalue($exam_score, grade_item::fetch_course_item($courseid));
$passed_course = (strpos($str_grade, 'Pass') === false) ? false : true;
$data['completed_course'] = ($passed_course ? "true" : "false");
// we only send the score for a final when they pass the course
if ($passed_course) {
// issue the certificate
require_once("../certificate/lib.php");
$certificate = get_record('certificate', 'course', $courseid);
$cert_issue = certificate_prepare_issue($COURSE, $user, $certificate);
// get the certificate verification code
$sql = "SELECT s.code FROM {$CFG->prefix}certificate_issues s WHERE s.userid = $user->id AND s.classname = '{$COURSE->fullname}'";
$certificate_code = get_field_sql($sql);
$data['certificate_code'] = $certificate_code;
eportlink_notify_eportfolio($data);
}
// the quiz is not the final
} else {
if (eportlink_notify_eportfolio($data) {}
}
}
*/


