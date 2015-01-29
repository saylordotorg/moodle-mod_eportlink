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
