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

defined('MOODLE_INTERNAL') || die();

/**
 * Define extra fields in the Assignment activity
 *
 * @param moodleform_mod $formwrapper
 * @param MoodleQuickForm $mform
 * @return void
 */
function local_solsits_coursemodule_standard_elements(moodleform_mod $formwrapper, MoodleQuickForm $mform) {
    global $DB;
    // Is this an assignment?
    $cm = $formwrapper->get_coursemodule();
    if ($cm->modname != 'assign') {
        return;
    }
    $solassign = $DB->get_record('local_solassignments', ['cmid' => $cm->id]);

    if (!$solassign) {
        // Not a SITS assignment, so don't bother.
        return;
    }
    $mform->addElement('header', 'sits_section', new lang_string('sits', 'local_solsits'));

    $mform->addElement('static', 'sits_ref', new lang_string('sitsreference', 'local_solsits'), $solassign->sitsref);
    $mform->addElement('static', 'sits_sitting', new lang_string('sittingreference', 'local_solsits'), $solassign->sitting);
    $mform->addElement('static', 'sits_sittingdesc', new lang_string('sittingdescription', 'local_solsits'), $solassign->sittingdesc);

    $externaldate = '';
    if ($solassign->externaldate > 0) {
        $externaldate = date('Y-m-d', $solassign->externaldate);
    } else {
        $externaldate = get_string('notset', 'local_solsits');
    }
    $mform->addElement('static', 'sits_externaldate', new lang_string('externaldate', 'local_solsits'), $externaldate);
    $mform->addElement('static', 'sits_status', new lang_string('status', 'local_solsits'), $solassign->status);

    $weighting = (int)($solassign->weighting * 100);
    $mform->addElement('static', 'sits_weighting', new lang_string('weighting', 'local_solsits'), $weighting . '%');

    $mform->addElement('static', 'sits_assessmentcode', new lang_string('assessmentcode', 'local_solsits'), $solassign->assessmentcode);

    $duedate = date('y-m-d', $solassign->duedate);
    $mform->addElement('static', 'sits_duedate', new lang_string('duedate', 'local_solsits'), $duedate);

    $grademarkexempt = $solassign->grademarkexempt ? get_string('Yes') : get_string('No');
    $mform->addElement('static', 'sits_grademarkexempt', new lang_string('grademarkexempt', 'local_solsits'), $grademarkexempt);

    $availablefrom = '';
    if ($solassign->availablefrom > 0) {
        $availablefrom = get_string('immediately', 'local_solsits');
    } else {
        $availablefrom = date('Y-m-d', $solassign->availablefrom);
    }
    $mform->addElement('static', 'sits_availablefrom', new lang_string('availablefrom', 'local_solsits'), $availablefrom);
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
    $cm = $formwrapper->get_coursemodule();
    if ($cm->modname != 'assign') {
        return;
    }
    $solassign = $DB->get_record('local_solassignments', ['cmid' => $cm->id]);
    if (!$solassign) {
        return;
    }
    // We're doing a static form, so no need to set anything.
    return;
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
function local_solsits_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    // We're doing a static form, so we're not saving data.
    return $data;
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
