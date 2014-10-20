<?php

/**
 * Notify eportfolio that a quiz has been completed
 *
 * @param object $quiz The quiz for which the best grade is to be calculated and then saved.
 * @param object $grade The grade object that they received on the quiz.
 *
 * To enable, add the following lines to the bottom of mod/quiz/locallib.php's quiz_save_best_grade function 
 *
 *   require_once '../saylor_eportfolio/notify_exam_completed.php';
 *   notify_eportfolio_exam_completed($quiz, $grade);
 *
 *   curl --data "secret=tNrY2mPXGS533jkkxDxR4j8YDkSm6krgNqdR1bqEny2NMRyHd7hBqQvU8dYFF9&user_id=24&email=joel.duffin@gmail.com&course_identifier=ECON203&exam_score=2&exam_completed_at=1333150906&completed_course=true"  http://eportfolio.saylor.org/api/exam_completed
 *
 */
 
 
 
 
 /**
* All other event classes must extend this class.
*
* @package core
* @since Moodle 2.6
* @copyright 2013 Petr Skoda {@link http://skodak.org}
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*
* @property-read string $eventname Name of the event (=== class name with leading \)
* @property-read string $component Full frankenstyle component name
* @property-read string $action what happened
* @property-read string $target what/who was target of the action
* @property-read string $objecttable name of database table where is object record stored
* @property-read int $objectid optional id of the object
* @property-read string $crud letter indicating event type
* @property-read int $edulevel log level (one of the constants LEVEL_)
* @property-read int $contextid
* @property-read int $contextlevel
* @property-read int $contextinstanceid
* @property-read int $userid who did this?
* @property-read int $courseid the courseid of the event context, 0 for contexts above course
* @property-read int $relateduserid
* @property-read int $anonymous 1 means event should not be visible in reports, 0 means normal event,
* create() argument may be also true/false.
* @property-read mixed $other array or scalar, can not contain objects
* @property-read int $timecreated
*/

/*
 protected function get_legacy_eventdata() {
$attempt = $this->get_record_snapshot('quiz_attempts', $this->objectid);
$legacyeventdata = new \stdClass();
$legacyeventdata->component = 'mod_quiz';
$legacyeventdata->attemptid = $this->objectid;
$legacyeventdata->timestamp = $attempt->timefinish;
$legacyeventdata->userid = $this->relateduserid;
$legacyeventdata->quizid = $attempt->quiz;
$legacyeventdata->cmid = $this->contextinstanceid;
$legacyeventdata->courseid = $this->courseid;
$legacyeventdata->submitterid = $this->other['submitterid'];
$legacyeventdata->timefinish = $attempt->timefinish;
return $legacyeventdata;
}
*/
 
 //$attempts = quiz_get_user_attempts($quiz->id, $userid); function quiz_save_best_grade($quiz, $userid = null, $attempts = array())
 //$grade = new stdClass();
 //$grade->quiz = $quiz->id;
 //$grade->userid = $userid;
 //$grade->grade = $bestgrade;
 //$grade->timemodified = time();

function notify_eportfolio_exam_completed($quiz, $grade) {
  global $CFG, $USER, $COURSE, $DB;

  // build an array with the form data to send
  $data = array();
  $data['username'] = $USER->username;
  $data['email'] = $USER->email;
  $data['course_identifier'] = $DB->get_record('course', array('id' => $event->courseid));
  $data['quiz_name'] = $quiz->name;
  $data['exam_completed_at'] = $grade->timemodified ? $grade->timemodified : time();

  $exam_score = round(100*$grade->grade/$quiz->grade);
  $data['exam_score'] = $exam_score;

  $secret = 'tNrY2mPXGS533jkkxDxR4j8YDkSm6krgNqdR1bqEny2NMRyHd7hBqQvU8dYFF9';
  $data['secret'] = $secret;

  // this is a final
  if (strpos($quiz->name, 'Final') != false) {

    // get the user's grade for the course
    require_once($CFG->libdir . '/gradelib.php');
    $str_grade = grade_format_gradevalue($exam_score, grade_item::fetch_course_item($COURSE->id));
    $passed_course = (strpos($str_grade, 'Pass') === false) ? false : true;
    $data['completed_course'] = ($passed_course ? "true" : "false");

    // we only send the score for a final when they pass the course
    if ($passed_course) {

      // issue the certificate
      require_once("../certificate/lib.php");
      $certificate = get_record('certificate', 'course', $COURSE->id);
      $cert_issue = certificate_prepare_issue($COURSE, $USER, $certificate);

      // get the certificate verification code
      $sql = "SELECT s.code FROM {$CFG->prefix}certificate_issues s WHERE s.userid = $USER->id AND s.classname = '{$COURSE->fullname}'";
      $certificate_code = get_field_sql($sql);
      $data['certificate_code'] = $certificate_code;

      notify_eportfolio($data);
    }
  // the quiz is not the final
  } else {
    notify_eportfolio($data);
  }
}

function notify_eportfolio($data) {
  global $CFG;

  $port = '';
  //$host = 'staging.saylor.org';
  $host = 'eportfolio.saylor.org';
  // $host = 'localhost';
  // $port = ':3000';
  $url = 'http://' . $host . $port . '/api/exam_completed';

  // request headers
  $headers = array();
  $headers['Host'] = $host;
  $headers['Content-Type'] = 'application/x-www-form-urlencoded';
  $headers['User-Agent'] = 'moodle-eportfolio-add-on/PHP';

  // make the request
  require_once $CFG->libdir . '/filelib.php';
  $response = download_file_content($url, $headers, $data, false, 1300, 120, true);
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
    error($error_msg.'<br/>'.$response);
  }
}
?>