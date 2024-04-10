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
 * Generator trait to help create things
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

defined('MOODLE_INTERNAL') || die();
global $CFG;

use stdClass;
use mod_assign_testable_assign;

/**
 * A trait to help along the generator
 */
trait generator {
    /**
     * Creates a template course and registers it as a soltemplate
     *
     * @param string $session
     * @param string $pagetype
     * @param bool $enabled
     * @return soltemplate
     */
    private function create_template_course($session, $pagetype = 'module', $enabled = 1) {
        $idnumber = 'template_' . $session . '_' . $pagetype;
        $template = $this->getDataGenerator()->create_course([
            'fullname' => "Template " . $idnumber,
            'idnumber' => $idnumber,
            'shortname' => $idnumber,
            'numsections' => 5,
        ]);
        $this->getDataGenerator()->create_module('label', [
            'course' => $template->id,
            'intro' => "Label from Template {$idnumber}.",
        ]);
        $record = new stdClass();
        $record->courseid = $template->id;
        $record->pagetype = $pagetype;
        $record->session = $session;
        $record->enabled = $enabled;
        $soltemplate = new soltemplate(0, $record);
        $soltemplate->save();
        return $soltemplate;
    }

    /**
     * Mark, Release and Lock grades for students on this assignment
     *
     * @param array $students
     * @param array $grades Includes grade and feedback
     * @param mod_assign_testable_assign $assign
     * @param object $moduleleader
     * @param string $workflowstate ASSIGN_MARKING_WORKFLOW_STATE_ constant
     * @return void
     */
    private function mark_assignments($students, $grades, $assign, $moduleleader,
            $workflowstate = ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
        $this->setUser($moduleleader);
        foreach ($students as $x => $student) {
            $data = new stdClass();
            $data->grade = $grades[$x]['grade'];
            $data->workflowstate = $workflowstate;

            // Add some feedback.
            $data->misconduct_check = $grades[$x]['feedbackmisconduct'] ?? 0;
            $data->assignfeedbackcomments_editor['text'] = $grades[$x]['feedbackcomments'] ?? '';
            $data->assignfeedbackcomments_editor['format'] = FORMAT_HTML;

            $assign->testable_apply_grade_to_user($data, $student->id, 0);
            if ($workflowstate == ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
                $assign->lock_submission($student->id);
            }
        }
        if ($workflowstate == ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
            $gradeitem = $assign->get_grade_item();
            $gradeitem->set_locked(time(), false, true);
        }
    }
}
