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
 * Handles viewing a certificate
 *
 * @package    mod
 * @subpackage eportlink
 * @copyright  eportlink <contact@saylor.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("$CFG->dirroot/mod/eportlink/lib.php");

$id = required_param('id', PARAM_INT);    // Course Module ID

$cm = get_coursemodule_from_id('eportlink', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=> $cm->course), '*', MUST_EXIST);
$eportlink_certificate = $DB->get_record('eportlink', array('id'=> $cm->instance), '*', MUST_EXIST);

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/eportlink:view', $context);

// Initialize $PAGE, compute blocks
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/eportlink/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($eportlink_certificate->name));
$PAGE->set_heading(format_string($course->fullname));

// Get array of certificates
$certificates = eportlink_get_issued($eportlink_certificate->achievementid);

if(has_capability('mod/eportlink:manage', $context)) {
	$table = new html_table();
	$table->head  = array (get_string('id', 'eportlink'), get_string('recipient', 'eportlink'), get_string('certificateurl', 'eportlink'), get_string('datecreated', 'eportlink'));

	foreach ($certificates as $certificate) {
		$issue_date = date_format( date_create($certificate->issued_on), "M d, Y" ) ;
	  $table->data[] = array ( 
	  	$certificate->id, 
	  	$certificate->recipient->name, 
	  	"<a href='https://eportlink.com/$certificate->id' target='_blank'>https://eportlink.com/$certificate->id</a>", 
	  	$issue_date
	  );
	}

	echo $OUTPUT->header();
	echo html_writer::tag( 'h3', get_string('viewheader', 'eportlink', $eportlink_certificate->name) );
	echo html_writer::tag( 'h5', get_string('viewsubheader', 'eportlink', $eportlink_certificate->achievementid) );
	echo html_writer::tag( 'br', null );
	echo html_writer::table($table);
	echo $OUTPUT->footer($course);
} 
else {
	// Check for this user's certificate
	$users_certificate_link = null;
	foreach ($certificates as $certificate) {
		// if($)
    if($certificate->recipient->email == $USER->email) {
      if($certificate->private) {
      	$users_certificate_link = $certificate->id . '?key=' . $certificate->private_key;
      } else {
      	$users_certificate_link = $certificate->id;
      }
    }
	}
	// Echo the page
	echo $OUTPUT->header();

	if($users_certificate_link) {
		$src = $OUTPUT->pix_url('complete_cert', 'eportlink');
		echo html_writer::start_div('text-center');
		echo html_writer::tag( 'br', null );
		$img = html_writer::img($src, get_string('viewimgcomplete', 'eportlink'), array('width' => '90%') );
		echo html_writer::link( 'https://eportlink.com/'.$users_certificate_link, $img, array('target' => '_blank') );
		echo html_writer::end_div('text-center');
	} 
	else {
		$src = $OUTPUT->pix_url('incomplete_cert', 'eportlink');
		echo html_writer::start_div('text-center');
		echo html_writer::tag( 'br', null );
		echo html_writer::img($src, get_string('viewimgincomplete', 'eportlink'), array('width' => '90%') );
		echo html_writer::end_div('text-center');
	}

	echo $OUTPUT->footer($course);
}
