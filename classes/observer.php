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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
* @package   local_eportlink
* @copyright 2014, Saylor Academy <contact@saylor.org>
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/local/eportlink/notify_exam_completed.php')

class local_eportlink_observer {

	public static function attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
		global $DB;
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

	public static function user_graded(\mod_quiz\event\question_manually_graded $event) {

	}

	public static function question_manually_graded(\mod_quiz\event\question_manually_graded $event) {

	}

}