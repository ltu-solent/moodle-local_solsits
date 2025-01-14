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
 * Manage templates page
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\context;
use local_solsits\sitsassign;

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_solsits/manageassignments', '', null, '/local/solsits/manageassignments.php');
$context = context\system::instance();
require_capability('local/solsits:manageassignments', $context);

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$confirmdelete = optional_param('confirmdelete', null, PARAM_BOOL);
$confirmrecreate = optional_param('confirmrecreate', null, PARAM_BOOL);

if (($confirmdelete || $confirmrecreate) && confirm_sesskey()) {
    if ($id == 0) {
        throw new moodle_exception('invalidrequest');
    }
    // Only delete if the cm does not exist.
    $sitsassign = new sitsassign($id);
    $sitsref = $sitsassign->get('sitsref');
    $message = null;
    $cmexists = false;
    try {
        [$course, $cm] = get_course_and_cm_from_cmid($sitsassign->get('cmid'), 'assign');
        $cmexists = true;
    } catch (Exception $ex) {
        $cmexists = false;
    }
    if ($confirmdelete) {
        $deleteme = false;
        if ($sitsassign->get('cmid') == 0) {
            $deleteme = true;
        } else if (!$cmexists) {
            $deleteme = true;
        }
        if ($deleteme) {
            $message = get_string('sitsassign:deleted', 'local_solsits', $sitsref);
            $sitsassign->delete();
        }
    }
    if ($confirmrecreate && !$cmexists) {
        $sitsassign->set('cmid', 0);
        $sitsassign->save();
        $message = get_string('sitsassign:recreated', 'local_solsits', $sitsref);
    }

    if ($message) {
        redirect(new moodle_url('/local/solsits/manageassignments.php'),
            $message,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

$PAGE->set_context($context);
$PAGE->set_heading(get_string('manageassignments', 'local_solsits'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manageassignments', 'local_solsits'));
$PAGE->set_url($CFG->wwwroot.'/local/solsits/manageassignments.php');

echo $OUTPUT->header();

if (in_array($action, ['delete', 'recreate'])) {
    if ($id == 0) {
        throw new moodle_exception('invalidrequest');
    }
    $sitsassign = new sitsassign($id);
    $sitsref = $sitsassign->get('sitsref');
    $heading = null;
    $body = null;
    $cmexists = false;
    $candelete = false;
    $canrecreate = false;
    $actionlabel = '';
    $buttonparams = [
        'action' => $action,
        'id' => $id,
        'sesskey' => sesskey(),
    ];
    try {
        [$course, $cm] = get_course_and_cm_from_cmid($sitsassign->get('cmid'), 'assign');
        $cmexists = true;
    } catch (Exception $ex) {
        $cmexists = false;
    }
    if ($action == 'delete') {
        $candelete = false;
        // Only delete if the cm does not exist.
        if (($sitsassign->get('cmid') == 0) || !$cmexists) {
            $candelete = true;
            $heading = new lang_string('sitsassign:confirmdelete', 'local_solsits', $sitsref);
            $body = new lang_string('sitsassign:confirmdeletebody', 'local_solsits', $sitsref);
            $actionlabel = get_string('delete');
            $buttonparams['confirmdelete'] = true;
        }
        if (!$candelete) {
            throw new moodle_exception('sitsassign:cannotdelete', 'local_solsits', null, $sitsref);
        }
    }

    if ($action == 'recreate') {
        $canrecreate = false;
        if (!$cmexists) {
            $canrecreate = true;
            $heading = new lang_string('sitsassign:confirmrecreate', 'local_solsits', $sitsref);
            $body = new lang_string('sitsassign:confirmrecreatebody', 'local_solsits', $sitsref);
            $actionlabel = get_string('sitsassign:recreate', 'local_solsits');
            $buttonparams['confirmrecreate'] = true;
        }
        if (!$canrecreate) {
            throw new moodle_exception('sitsassign:cannotrecreate', 'local_solsits', null, $sitsref);
        }
    }

    if ($candelete || $canrecreate) {
        echo html_writer::tag('h3', $heading);
        $actionurl = new moodle_url('/local/solsits/manageassignments.php', $buttonparams);
        $actionbutton = new single_button($actionurl, $actionlabel);
        echo $OUTPUT->confirm(
            $body,
            $actionbutton,
            new moodle_url('/local/solsits/manageassignments.php')
        );
        echo $OUTPUT->footer();
        exit();
    }
}

$params = [
    'selectedcourses' => [],
    'showerrorsonly' => false,
    'session' => '',
];

$filterform = new \local_solsits\forms\solassign_filter_form(null);
if ($filterdata = $filterform->get_data()) {
    $params['session'] = $filterdata->session;
    $params['selectedcourses'] = $filterdata->selectedcourses;
    $params['showerrorsonly'] = $filterdata->showerrorsonly;
} else {
    $params['selectedcourses'] = optional_param_array('selectedcourses', [], PARAM_INT);
    $params['session'] = optional_param('session', '', PARAM_RAW);
    $params['showerrorsonly'] = optional_param('showerrorsonly', false, PARAM_BOOL);
    $filterform->set_data($params);
}

$filterform->display();

$table = new \local_solsits\tables\solassign_table('solassignments', $params);

$table->out(100, false);

echo $OUTPUT->footer();
