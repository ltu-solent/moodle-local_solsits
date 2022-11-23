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
 * Lib for solassignments
 *
 * @package   local_solassignments
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define extra fields in the Assignment activity
 *
 * @param moodleform_mod $formwrapper
 * @param MoodleQuickForm $mform
 * @return void
 */
function local_solassignments_coursemodule_standard_elements(moodleform_mod $formwrapper, MoodleQuickForm $mform) {
    global $DB;
    // Is this an assignment?
    $cm = $formwrapper->get_coursemodule();
    if ($cm->modname != 'assign') {
        return;
    }
    $solassign = $DB->get_record('local_solassignments', ['cmid' => $cm->id]);

    $isadmin = is_siteadmin();
    $mform->addElement('header', 'sits_section', 'SITS');

    $mform->addElement('text', 'sits_ref', 'Sits reference');
    $mform->setType('sits_ref', PARAM_TEXT);

    $mform->addElement('text', 'sits_sitting', 'Sitting reference');
    $mform->setType('sits_sitting', PARAM_TEXT);

    $mform->addElement('text', 'sits_sitting_desc', 'Sitting description');
    $mform->setType('sits_sitting_desc', PARAM_TEXT);
    $mform->setDefault('sits_sitting_desc', 'FIRST_SITTING');

    if ($isadmin) {
        $mform->addElement('date_time_selector', 'sits_sitting_date', 'Sitting date');
        $mform->setType('sits_sitting_date', PARAM_INT);
        $mform->setDefault('sits_sitting_date', 0);
    } else {
        $sittingdate = '';
        if (isset($solassign) && $solassign->sitting_date > 0) {
            $sittingdate = date('Y-m-d', $solassign->sitting_date);
        } else {
            $sittingdate = 'Not set';
        }
        $mform->addElement('static', 'sits_sitting_date', 'Sitting date', $sittingdate);
    }

    $mform->addElement('text', 'sits_status', 'Status');
    $mform->setType('sits_status', PARAM_TEXT);

    if (!$isadmin) {
        $mform->freeze(['sits_ref', 'sits_sitting', 'sits_sitting_desc', 'sits_sitting_date', 'sits_status']);
    }
}

/**
 * Update the form values with data from the local_solassignments table, if any.
 *
 * @param moodleform_mod $formwrapper
 * @param MoodleQuickForm $mform
 * @return void
 */
function local_solassignments_coursemodule_definition_after_data(moodleform_mod $formwrapper, MoodleQuickForm $mform) {
    global $DB;
    $cm = $formwrapper->get_coursemodule();
    if ($cm->modname != 'assign') {
        return;
    }
    $solassign = $DB->get_record('local_solassignments', ['cmid' => $cm->id]);
    if (!$solassign) {
        return;
    }
    // Assign the data.
    // error_log(print_r($solassign, true));
    $mform->getElement('sits_ref')->setValue($solassign->sitsref);
    $mform->getElement('sits_sitting')->setValue($solassign->sitting);
    $mform->getElement('sits_sitting_desc')->setValue($solassign->sitting_desc);
    $el = $mform->getElement('sits_sitting_date');
    if ($solassign->sitting_date > 0) {
        $el->setValue($solassign->sitting_date);
    }
    $mform->getElement('sits_status')->setValue($solassign->status);
}

/**
 * Called after the assignment has been created. Use this to update the local_solassignments table.
 *
 * @param stdClass $data
 * @param stdClass $course
 * @return stdClass Updated data
 */
function local_solassignments_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    $isadmin = is_siteadmin();
    if (!$isadmin) {
        return $data;
    }
    $solassign = $DB->get_record('local_solassignments', ['cmid' => $data->coursemodule]);
    if (!$solassign) {
        $solassign = new stdClass();
        $solassign->cmid = $data->coursemodule;
        $solassign->courseid = $data->course;
        $solassign->timecreated = time();
    }

    $solassign->sitsref = $data->sits_ref;
    $solassign->sitting = $data->sits_sitting;
    $solassign->sitting_desc = $data->sits_sitting_desc;
    $solassign->sitting_date = $data->sits_sitting_date;
    $solassign->status = $data->sits_status;
    $solassign->timemodified = time();

    if (isset($solassign->id)) {
        $DB->update_record('local_solassignments', $solassign);
    } else {
        $DB->insert_record('local_solassignments', $solassign);
    }
    return $data;
}