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
 * Definition of log events
 *
 * @package    mod
 * @subpackage eportlink
 * @copyright  eportlink <contact@saylor.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'eportlink', 'action'=>'view', 'mtable'=>'eportlink', 'field'=>'name'),
    array('module'=>'eportlink', 'action'=>'add', 'mtable'=>'eportlink', 'field'=>'name'),
     array('module'=>'eportlink', 'action'=>'update', 'mtable'=>'eportlink', 'field'=>'name'),
    array('module'=>'eportlink', 'action'=>'received', 'mtable'=>'eportlink', 'field'=>'name'),
);