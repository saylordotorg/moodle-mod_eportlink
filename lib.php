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

require_once($CFG->dirroot . '/mod/eportlink/locallib.php');
require_once($CFG->libdir  . '/eventslib.php');

/**
 * Add certificate instance.
 *
 * @param array $certificate
 * @return array $certificate new certificate object
 */
function eportlink_add_instance($post) {
    global $DB, $CFG;

    // Issue certs
    if( isset($post->users) ) {
        // Checklist array from the form comes in the format:
        // int user_id => boolean issue_certificate
        foreach ($post->users as $user_id => $issue_certificate) {
            if($issue_certificate) {
                $user = $DB->get_record('user', array('id'=>$user_id), '*', MUST_EXIST);

                $certificate = array();
                $course_url = new moodle_url('/course/view.php', array('id' => $post->course));
                $certificate['name'] = $post->name;
                $certificate['achievement_id'] = $post->achievementid;
                $certificate['description'] = $post->description;
                $certificate['course_link'] = $course_url->__toString();
                $certificate['recipient'] = array('name' => fullname($user), 'email'=> $user->email);
                if($post->finalquiz) {
                    $quiz = $DB->get_record('quiz', array('id'=>$post->finalquiz), '*', MUST_EXIST);
                    $users_grade = ( quiz_get_best_grade($quiz, $user->id) / $quiz->grade ) * 100;
                    $certificate['evidence_items'] = array( array('string_object' => (string) $users_grade, 'description' => $quiz->name, 'custom'=> true, 'category' => 'grade'));
                }

                $curl = curl_init('https://api.eportlink.com/v1/credentials');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( array('credential' => $certificate) ));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->eportlink_api_key.'"' ) );
                if(!$result = curl_exec($curl)) {
                    // throw API exception
                    // include the user id that triggered the error
                    // direct the user to eportlink's support
                    // dump the post to debug_info
                    throw new moodle_exception('manualadderror:add', 'eportlink', 'https://eportlink.com/contact/support', $user_id, var_dump($post));
                }
                curl_close($curl);

                // Log the creation
                $event = eportlink_log_creation( 
                    json_decode($result)->credential->id,
                    $post->course,
                    $user_id
                );
                $event->trigger();
            }
        }
    }

    $completion_activities = array();
    foreach ($post->activities as $activity_id => $track_activity) {
        if($track_activity) {
            $completion_activities[$activity_id] = false;
        }
    }

    // Save record
    $db_record = new stdClass();
    $db_record->completionactivities = serialize_completion_array($completion_activities);
    $db_record->name = $post->name;
    $db_record->course = $post->course;
    $db_record->description = $post->description;
    $db_record->achievementid = $post->achievementid;
    $db_record->finalquiz = $post->finalquiz;
    $db_record->passinggrade = $post->passinggrade;
    $db_record->timecreated = time();

    return $DB->insert_record('eportlink', $db_record);
}

/**
 * Update certificate instance.
 *
 * @param stdClass $post
 * @return stdClass $certificate updated 
 */
function eportlink_update_instance($post) {
    // To update your certificate details, go to eportlink.com.
    global $DB, $CFG;
    $eportlink_cm = get_coursemodule_from_id('eportlink', $post->coursemodule, 0, false, MUST_EXIST);

    // Issue certs
    if( isset($post->users) ) {
        // Checklist array from the form comes in the format:
        // int user_id => boolean issue_certificate
        foreach ($post->users as $user_id => $issue_certificate) {
            if($issue_certificate) {
                $user = $DB->get_record('user', array('id'=>$user_id), '*', MUST_EXIST);

                $certificate = array();
                $course_url = new moodle_url('/course/view.php', array('id' => $post->course));
                $certificate['name'] = $post->name;
                $certificate['achievement_id'] = $post->achievementid;
                $certificate['description'] = $post->description;
                $certificate['course_link'] = $course_url->__toString();
                $certificate['recipient'] = array('name' => fullname($user), 'email'=> $user->email);
                if($post->finalquiz) {
                    $quiz = $DB->get_record('quiz', array('id'=>$post->finalquiz), '*', MUST_EXIST);
                    $users_grade = ( quiz_get_best_grade($quiz, $user->id) / $quiz->grade ) * 100;
                    $certificate['evidence_items'] = array( array('string_object' => (string) $users_grade, 'description' => $quiz->name, 'custom'=> true, 'category' => 'grade'));
                }

                $curl = curl_init('https://api.eportlink.com/v1/credentials');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( array('credential' => $certificate) ));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->eportlink_api_key.'"' ) );
                if(!$result = curl_exec($curl)) {
                    // throw API exception
                    // include the user id that triggered the error
                    // direct the user to eportlink's support
                    // dump the post to debug_info
                    throw new moodle_exception('manualadderror:edit', 'eportlink', 'https://eportlink.com/contact/support', $user_id, var_dump($post));
                }
                curl_close($curl);

                // Log the creation
                $event = eportlink_log_creation( 
                    json_decode($result)->credential->id,
                    $post->course,
                    $user_id
                );
                $event->trigger();
            }
        }
    }

    $completion_activities = array();
    foreach ($post->activities as $activity_id => $track_activity) {
        if($track_activity) {
            $completion_activities[$activity_id] = false;
        }
    }

    // Save record
    $db_record = new stdClass();
    $db_record->id = $post->instance;
    $db_record->completionactivities = serialize_completion_array($completion_activities);
    $db_record->name = $post->name;
    $db_record->description = $post->description;
    $db_record->passinggrade = $post->passinggrade;
    $db_record->finalquiz = $post->finalquiz;

    return $DB->update_record('eportlink', $db_record);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance.
 *
 * @param int $id
 * @return bool true if successful
 */
function eportlink_delete_instance($id) {
    global $DB;

    // Ensure the certificate exists
    if (!$certificate = $DB->get_record('eportlink', array('id' => $id))) {
        return false;
    }

    return $DB->delete_records('eportlink', array('id' => $id));
}

/**
 * Supported feature list
 *
 * @uses FEATURE_MOD_INTRO
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function eportlink_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:               return false;
        default: return null;
    }
}
