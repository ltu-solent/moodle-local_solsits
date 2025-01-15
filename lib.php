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
 * Lib for solsits
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_solsits\sitsassign;

/**
 * Define extra fields in the Assignment activity
 *
 * @param moodleform_mod $formwrapper
 * @param MoodleQuickForm $mform
 * @return void
 */
function local_solsits_coursemodule_standard_elements(moodleform_mod $formwrapper, MoodleQuickForm $mform) {
    // Is this an assignment?
    sitsassign::coursemodule_form($formwrapper, $mform);
}

/**
 * Update the form values with data from the local_solassignments table, if any.
 *
 * @param moodleform_mod $formwrapper
 * @param MoodleQuickForm $mform
 * @return void
 */
function local_solsits_coursemodule_definition_after_data(moodleform_mod $formwrapper, MoodleQuickForm $mform) {
    global $DB;
    // We're doing a static form, so no need to set anything.
    return;
    $cm = $formwrapper->get_coursemodule();
    if (!isset($cm) || $cm->modname != 'assign') {
        return;
    }
    $solassign = $DB->get_record('local_solsits_assign', ['cmid' => $cm->id]);
    if (!$solassign) {
        return;
    }

    // Assign the data.
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
function local_solsits_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    // We're doing a static form, so we're not saving data.
    return $data;
    $isadmin = is_siteadmin();
    if (!$isadmin) {
        return $data;
    }
    $solassign = $DB->get_record('local_solsits_assign', ['cmid' => $data->coursemodule]);
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
        $DB->update_record('local_solsits_assign', $solassign);
    } else {
        $DB->insert_record('local_solsits_assign', $solassign);
    }
    return $data;
}

/**
 * Take some action before the course module is deleted.
 *
 * @param stdClass $cm
 * @return void
 */
function local_solsits_pre_course_module_delete($cm) {
    // This might be a better alternative to the event watcher as this might run
    // before submissions are deleted.

    // If cm is assignment.
    // If is formative assignment.
    // Send email to module leader and LTU.
    // Include the number of submissions in the email.

    // Alternatively use the event course_module_deleted.
    return;
}
