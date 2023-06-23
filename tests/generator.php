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
            'numsections' => 5
        ]);
        $this->getDataGenerator()->create_module('label', [
            'course' => $template->id,
            'intro' => "Label from Template {$idnumber}."
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
     * Helper to create the grades we use for summative assignments
     *
     * @return void
     */
    private function create_solent_gradescales() {
        $solentscale = $this->getDataGenerator()->create_scale([
            'name' => 'Solent',
            'scale' => 'N, S, F3, F2, F1, D3, D2, D1, C3, C2, C1, B3, B2, B1, A4, A3, A2, A1'
        ]);
        set_config('grademarkscale', $solentscale->id, 'local_solsits');
        $solentnumeric = $this->getDataGenerator()->create_scale([
            'name' => 'Solent numeric',
            'scale' => '0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, ' .
                    '21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, ' .
                    '41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, ' .
                    '61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, ' .
                    '81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100'
        ]);
        set_config('grademarkexemptscale', $solentnumeric->id, 'local_solsits');
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
