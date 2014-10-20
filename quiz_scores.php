<?php

/**
 * This file updates the quizzes in eportfolio. This file handles requests comming from eportfolio to retrieve
 * quizzes or a user based on email address
 *
 */
require_once("../../config.php");
require_once("locallib.php");

function get_user_quiz_scores() {
  
  $secret = 'tNrY2mPXGS533jkkxDxR4j8YDkSm6krgNqdR1bqEny2NMRyHd7hBqQvU8dYFF9';

  // get our method name
  $request_method = strtolower($_SERVER['REQUEST_METHOD']);
  
  // get post data
  $request_data = array();

  switch ($request_method) {    

    // Handle get posts  
    case 'get':
      $request_data = $_GET;
      break;
   }    

   if(!$request_data[email]) {
      return header('HTTP/1.0 500 Internal Server Error');
   }

   $user_id = get_user_id($request_data[email]);

   if(!$user_id) {
      return header('HTTP/1.0 500 Internal Server Error');
   }

   $courses = get_user_courses($user_id);

   if(count($courses) == 0) {
      return header('HTTP/1.0 500 Internal Server Error');
   }

   $data = array();
   
  foreach ($courses as $cid => $course) {

    $quizzes = get_quizzes($cid, $user_id, $course);
    
    if(count($quizzes) > 0)
      $data[$course['shortname']] = $quizzes;
  } 
  
  return json_encode($data);
}

function get_user_id($email) {
  global $CFG;  
  $sql = "SELECT u.id FROM {$CFG->prefix}user u WHERE u.email = '$email'";
  $user_id = (integer)get_field_sql($sql);
  return $user_id;
}

function get_user_courses($user_id) {
  $sql = "SELECT c.id, c.shortname, c.fullname 
          FROM  mdl_user u,  mdl_role_assignments ra,  mdl_context con,  mdl_course c,  mdl_role r 
          WHERE  u.id = $user_id 
          AND  u.id = ra.userid 
          AND  ra.contextid = con.id 
          AND  con.contextlevel = 50 
          AND  con.instanceid = c.id 
          AND  ra.roleid = r.id 
          AND  r.shortname = 'student';";

  $courses = array();
  $count = 0; // keep count

  $rs = get_recordset_sql($sql);
  
  while ($c = rs_fetch_next_record($rs)) {   
      $course = array();
      $course['shortname'] = $c->shortname;
      // get full name to retrieve the certificate
      $course['fullnamename'] = $c->fullname;

      $courses[$c->id] = $course;
      $count++;
  }
  rs_close($rs);

  return $courses;
}

function get_quizzes($course_id, $user_id, $course) {
  
  $sql = "SELECT mq.name as name, mqg.grade as grade, mqg.timemodified, mq.grade as maxgrade
          FROM mdl_quiz_grades mqg, mdl_quiz mq 
          WHERE mqg.userid = $user_id 
          AND mq.course = $course_id
          AND mq.id = mqg.quiz;";

  $quizzes = array();

  $count = 0; // keep count

  $rs = get_recordset_sql($sql);
  
  // creates a key value pair of quizname and grade
  // like Bio101 => 5
  while ($q = rs_fetch_next_record($rs)) {
      $quiz = array();             
      $quiz['quiz_name'] = $q->name;
      $quiz['exam_score'] = $q->grade;
      $quiz['exam_completed_at'] = $q->timemodified ? $q->timemodified : time();
      $quiz['max_score'] = $q->maxgrade;
      $quiz['exam_score'] = round(100 * $quiz['exam_score'] / $quiz['max_score']);

      if(preg_match("/Final/i", $quiz['quiz_name']))
      {
        $quiz['completed_course'] = is_completed($quiz['exam_score'], $course_id);
        if($quiz['completed_course'] == "true")
        {
          $quiz['certificate_code'] = get_certificate($user_id, $course['fullname']);          
        }
          
      }        

      $quizzes[$count] = $quiz;
      $count++;
  }

  rs_close($rs);

  return $quizzes;
}

function is_completed($exam_score, $course_id) {
  global $CFG;
  require_once($CFG->libdir . '/gradelib.php');

  $str_grade = grade_format_gradevalue($exam_score, grade_item::fetch_course_item($course_id));
  
  //$passed_course = strpos($str_grade, 'Pass') != false;
  //return ($passed_course) ? "false" : "true";

  $passed_course =  preg_match("/Fail/i", $str_grade);
  return $passed_course ? 'false' : 'true';
}

function get_certificate($user_id, $course_fullname) {
  
  global $CFG;
  $sql = "SELECT s.code 
          FROM {$CFG->prefix}certificate_issues s 
          WHERE s.userid = $user_id 
          AND s.classname = $course_fullname";

  $certificate_code = get_field_sql($sql);
  return $certificate_code;
}

echo(get_user_quiz_scores());
?>