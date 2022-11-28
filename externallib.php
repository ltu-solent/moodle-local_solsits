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
 * External functions for SOL Assignments
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_solsits\helper;
use local_solsits\sitsassign;
use local_solsits\solcourse;
use local_solsits\soltemplate;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/externallib.php');

class local_solsits_external extends external_api {

    public static function add_assignments_parameters(): external_function_parameters {
        return new external_function_parameters([
            'assignments' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_RAW, 'The course id to add the assignment to'),
                    'sitsref' => new external_value(PARAM_RAW, 'SITS internal reference for the assignment'),
                    'sitting' => new external_value(PARAM_RAW, 'Sitting reference'),
                    'sittingdesc' => new external_value(PARAM_RAW, 'Sitting type', VALUE_DEFAULT, 'FIRST_SITTING'),
                    'externaldate' => new external_value(PARAM_INT, 'Timestamp of the board date', VALUE_DEFAULT, 0),
                    'title' => new external_value(PARAM_TEXT, 'Assignment title'),
                    'weighting' => new external_value(PARAM_FLOAT, 'Assignment weighting expressed as a decimal'),
                    'assessmentcode' => new external_value(PARAM_TEXT, 'Assessment code (PROJ1 etc)'),
                    'duedate' => new external_value(PARAM_INT, 'Due date timestamp (usually 4pm)'),
                    'grademarkexempt' => new external_value(PARAM_BOOL, 'Is this grademark exempt', VALUE_DEFAULT, false),
                    'availablefrom' => new external_value(PARAM_INT, 'When the assignment is available to the student from', VALUE_OPTIONAL),
                    // Do we want all the other dates? Or do we continue to process them ourselves?
                ])
            )
        ]);
    }

    public static function add_assignments($assignments) {
        global $DB;
        $params = self::validate_parameters(self::add_assignments_parameters(),
                array('assignments' => $assignments));
        $transaction = $DB->start_delegated_transaction();
        $inserted = [];
        foreach ($params['assignments'] as $assignment) {
            if ($DB->record_exists('local_solsits',['sitsref' => $assignment->sitsref])) {
                throw new invalid_parameter_exception(
                    get_string('error:sitsrefinuse', 'local_solsits', $assignment->sitsref)
                );
            }
            if (!$DB->record_exists('course', ['id' => $assignment->courseid])) {
                throw new invalid_parameter_exception(
                    get_string('error:coursenotexist', 'local_solsits', $assignment->courseid)
                );
            }
            $context = context_course::instance($assignment->courseid, IGNORE_MISSING);
            self::validate_context($context);

            // Check that the user has the permission to create the assignment with this method.
            // And all the core assign capabilities to add/update/delete.
            require_capability('local/solassignments:manage', $context);

            // Send data to helper class to create the assignment.
            // Then when you've got the cmid back, create the solassignment record and save that.
            // Return the solassignmentid
            $assign = new sitsassign(null, $assignment);
            $assign->add();
            $inserted[] = $assign->get('id');
        }

        $transaction->allow_commit();
        return $inserted;
    }

    public static function add_assignments_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                new external_value(PARAM_INT, 'ID in the SOLSITS assignment table')
            ])
        );
    }

    public static function update_assignments_parameters() {
        return new external_function_parameters([
            'assignments' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_RAW, 'The course id to add the assignment to'),
                    'sitsref' => new external_value(PARAM_RAW, 'SITS internal reference for the assignment'),
                    'sitting' => new external_value(PARAM_RAW, 'Sitting reference'),
                    'sittingdesc' => new external_value(PARAM_RAW, 'Sitting type', VALUE_DEFAULT, 'FIRST_SITTING'),
                    'externaldate' => new external_value(PARAM_INT, 'Timestamp of the board date', VALUE_DEFAULT, 0),
                    'title' => new external_value(PARAM_TEXT, 'Assignment title'),
                    'weighting' => new external_value(PARAM_FLOAT, 'Assignment weighting expressed as a decimal'),
                    'assessmentcode' => new external_value(PARAM_TEXT, 'Assessment code (PROJ1 etc)'),
                    'duedate' => new external_value(PARAM_INT, 'Due date timestamp (usually 4pm)'),
                    'grademarkexempt' => new external_value(PARAM_BOOL, 'Is this grademark exempt', VALUE_DEFAULT, false),
                    'availablefrom' => new external_value(PARAM_INT, 'When the assignment is available to the student from', VALUE_OPTIONAL),
                    // Do we want all the other dates? Or do we continue to process them ourselves?
                ])
            )
        ]);
    }

    public static function update_assignments($assignments) {
        // Do updates.
    }

    public static function update_assignments_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                new external_value(PARAM_INT, 'ID in the SOLSITS assignment table')
            ])
        );
    }

    public static function register_sitscourses_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_INT, 'Course id'),
                    'pagetype' => new external_value(PARAM_ALPHA, 'course or module', VALUE_DEFAULT, 'module'),
                    'session' => new external_value(PARAM_RAW, 'Session e.g. 2022/2023')
                ])
            )
        ]);
    }

    public static function register_sitscourses($courses) {
        global $DB, $USER;
        $params = self::validate_parameters(self::register_sitscourses_parameters(),
                array('courses' => $courses));
        $transaction = $DB->start_delegated_transaction();
        $inserted = [];
        $validpagetypes = helper::get_pagetypes();
        foreach ($params['courses'] as $course) {
            if (!in_array($course['pagetype'], $validpagetypes)) {
                throw new invalid_parameter_exception(
                    get_string('error:invalidpagetype', 'local_solsits', $course['pagetype'])
                );
            }
            $existing = solcourse::get_record(['courseid' => $course['courseid']]);
            if (!$existing) {
                $sitscourse = new solcourse(0, (object)$course);
                $sitscourse->save();
                $inserted[] = [
                    'id' => $sitscourse->get('id'),
                    'courseid' => $sitscourse->get('courseid'),
                    'templateapplied' => $sitscourse->get('templateapplied'),
                    'pagetype' => $sitscourse->get('pagetype'),
                    'session' => $sitscourse->get('session')
                ];
            } else {
                $inserted[] = [
                    'id' => $existing->get('id'),
                    'courseid' => $existing->get('courseid'),
                    'templateapplied' => $existing->get('templateapplied'),
                    'pagetype' => $existing->get('pagetype'),
                    'session' => $existing->get('session')
                ];
            }
        }
        $transaction->allow_commit();
        return $inserted;
    }

    public static function register_sitscourses_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT),
                'courseid' => new external_value(PARAM_INT),
                'templateapplied' => new external_value(PARAM_BOOL),
                'pagetype' => new external_value(PARAM_ALPHA),
                'session' => new external_value(PARAM_RAW)
            ])
        );
    }
}
