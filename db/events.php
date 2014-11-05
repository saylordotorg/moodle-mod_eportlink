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

//Event Handlers

//New observer for Moodle 2.7 Events2 API. Awesome.
/*$observers = array(
 
    array(
        'eventname'   => '\mod_quiz\event\attempt_submitted',
        'callback'    => 'local_eportlink_observer::attempt_submitted',
    ),
    array(
        'eventname'   => '\core\event\user_graded',
        'callback'    => 'local_eportlink_observer::user_graded',
    ),
    array(
        'eventname'   => '\mod_quiz\event\question_manually_graded',
        'callback'    => 'local_eportlink_observer::question_manually_graded',
    ),
);*/
$observers = array(
 
    array(
        'eventname'   => '\mod_quiz\event\attempt_submitted',
        'includefile' => '/local/eportlink/lib.php',
        'callback'    => 'attempt_submitted_handler',
        'internal'    => false
    ),
    array(
        'eventname'   => '\core\event\user_graded',
        'includefile' => '/local/eportlink/lib.php',
        'callback'    => 'user_graded_handler',
        'internal'    => false
    ),
    array(
        'eventname'   => '\mod_quiz\event\question_manually_graded',
        'includefile' => '/local/eportlink/lib.php',
        'callback'    => 'question_manually_graded_handler',
        'internal'    => false
    ),
);

?>