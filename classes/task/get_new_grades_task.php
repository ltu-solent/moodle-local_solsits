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
 * Get newly released grades from grade items for sits assignments
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/externallib.php');

use core\context;
use core\task\scheduled_task;
use local_solsits\helper;
use local_solsits\sitsassign;
use mod_assign_external;
use stdClass;

/**
 * Get newly released grades. Used in conjunction with export_grades.
 */
class get_new_grades_task extends scheduled_task {
    /**
     * Name of the task
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('getnewgradestask', 'local_solsits');
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function execute() {
        global $DB;
        // This gets the last time grades were stored in this table.
        $lastruntime = $DB->get_field_sql('SELECT max(timecreated) FROM {local_solsits_assign_grades}');

        if ($lastruntime == null) {
            $lastruntime = 0;
        } else {
            // Backtrack this lastruntime 1 minute to capture some unnecessary updates
            // rather than allow the gap to be created.
            $lastruntime = $lastruntime - 60;
        }
        // Get assign ids for new assignments.
        $assignids = $DB->get_records_sql(
            'SELECT iteminstance
            FROM {grade_items}
            WHERE itemmodule = :itemmodule
                AND idnumber != :idnumber
                AND (locked > :locked AND locktime = :locktime)',
            [
                'itemmodule' => 'assign',
                'idnumber' => '',
                'locked' => $lastruntime,
                'locktime' => 0,
            ]
        );
        if (!$assignids) {
            mtrace('No grades have been released');
            return;
        }
        $assignids = array_keys($assignids);
        foreach ($assignids as $assignid) {
            $cm = \get_coursemodule_from_instance('assign', $assignid, 0);
            if (!helper::is_sits_assignment($cm->id)) {
                // Only handle SITS assignments. Nothing else.
                continue;
            }
            $this->store_grades_for_assignment($assignid, $cm, $lastruntime);
        }
    }

    /**
     * Gets grades for given assignment and inserts them in the processing queue
     *
     * @param int $assignid
     * @param stdClass $cm
     * @param integer $lastruntime
     * @return void
     */
    private function store_grades_for_assignment($assignid, $cm, $lastruntime = 0) {
        global $DB;
        $course = get_course($cm->course);
        $sitsassign = sitsassign::get_record(['cmid' => $cm->id]);

        // Get user that locked the grades and scale id.
        $releasedby = $DB->get_record_sql('SELECT u.id, u.firstname, u.lastname, u.email, h.timemodified, h.scaleid
            FROM {grade_items_history} h
            JOIN {user} u ON u.id = h.loggeduser
            WHERE (h.itemmodule = :itemmodule AND h.iteminstance = :iteminstance)
            AND locked > :locked
            ORDER BY h.timemodified DESC
            LIMIT 1', [
                'itemmodule' => 'assign',
                'iteminstance' => $cm->instance,
                'locked' => $lastruntime,
            ]);
        $students = get_role_users(
            5,
            context\course::instance($course->id),
            false,
            'u.id, u.lastname, u.firstname, idnumber',
            'idnumber, u.lastname, u.firstname'
        );
        $allgrades = mod_assign_external::get_grades([$assignid]);
        if (empty($allgrades['assignments'])) {
            // There are no grades for this assignment, so do nothing.
            mtrace("No grades for {$sitsassign->get('sitsref')}");
            return;
        }
        // Returns an array of assignments, pick off the first (and only) one.
        $allgrades = reset($allgrades['assignments']);
        foreach ($students as $student) {
            if (!is_numeric($student->idnumber)) {
                // Students only have numeric idnumbers. Staff have alphanumeric.
                mtrace(get_string('invalidstudent', 'local_solsits', [
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'idnumber' => $student->idnumber,
                ]));
                continue;
            }
            $grade = $this->user_grade($allgrades, $student, $releasedby->scaleid);
            // Grade -1 is "Not marked". Send this through as a zero to SITS.
            if ($grade == -1) {
                $grade = 0;
            }
            $solgradeitem = new stdClass();
            $solgradeitem->solassignmentid = $sitsassign->get('id');
            $solgradeitem->graderid = $releasedby->id;
            $solgradeitem->studentid = $student->id;
            $solgradeitem->converted_grade = $grade;
            $solgradeitem->timecreated = time();
            $solgradeitem->timemodified = time();
            $isqueued = $sitsassign->enqueue_grade($solgradeitem);
            $tracedata = [
                'course' => $course->shortname,
                'sitsref' => $cm->idnumber,
                'graderid' => $releasedby->id,
                'grade' => $grade,
                'studentidnumber' => $student->idnumber,
            ];
            if ($isqueued) {
                mtrace(get_string('gradequeued', 'local_solsits', $tracedata));
            }
        }
    }

    /**
     * Filter out the grade for given user, and convert it to a SITS digestible form.
     *
     * @param array $allgrades
     * @param stdClass $student User object for the student
     * @param int $scaleid
     * @return string
     */
    private function user_grade($allgrades, $student, $scaleid): string {
        $grade = (string) 0;
        foreach ($allgrades['grades'] as $value) {
            if ($value['userid'] == $student->id) {
                $grade = (string) helper::convert_grade($scaleid, $value['grade']);
            }
        }
        return $grade;
    }
}
