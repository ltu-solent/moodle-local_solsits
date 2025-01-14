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

namespace local_solsits\external;

use core\context;
use core\exception\invalid_parameter_exception;
use core\exception\moodle_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_solsits\helper;
use local_solsits\sitsassign;
use stdClass;

/**
 * Class update_assignments
 *
 * @package    local_solsits
 * @copyright  2024 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_assignments extends external_api {
    /**
     * Update SITS assignment record
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'assignments' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_INT, 'The course id to add the assignment to'),
                    'sitsref' => new external_value(PARAM_RAW, 'SITS internal reference for the assignment'),
                    'title' => new external_value(PARAM_TEXT, 'Assignment title'),
                    'reattempt' => new external_value(PARAM_INT, 'Reattempt sequence', VALUE_OPTIONAL, 0),
                    'weighting' => new external_value(PARAM_INT, 'Assignment weighting expressed as an integer'),
                    'assessmentcode' => new external_value(PARAM_TEXT, 'Assessment code (PROJ1 etc)'),
                    'assessmentname' => new external_value(PARAM_TEXT, 'Assessment name'),
                    'sequence' => new external_value(PARAM_ALPHANUM, 'Sequence of assessment, padded index'),
                    'duedate' => new external_value(PARAM_INT, 'Due date timestamp (usually 4pm)'),
                    'grademarkexempt' => new external_value(PARAM_BOOL, 'Is this grademark exempt', VALUE_DEFAULT, false),
                    'scale' => new external_value(PARAM_ALPHANUMEXT, 'Shortname of scale to be used', VALUE_OPTIONAL),
                    'availablefrom' => new external_value(
                        PARAM_INT, 'When the assignment is available to the student from', VALUE_OPTIONAL),
                ])
            ),
        ]);
    }

    /**
     * Update SITS assignment record
     *
     * @param array $assignments
     * @return void
     */
    public static function execute($assignments) {
        // Do updates.
        // If there is no cmid, just update the record, and it will be updated when the assignment is created.
        // If the cmid exists, then immediately recalculate dates and update the course module.
        // If the weighting is different or the grademark exempt has changed, should this be a new assignment?
        // What do we do if an assignment has been removed? If there are submissions do we still delete it?
        // Do we change the title and hide it?
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(),
                ['assignments' => $assignments]);
        $transaction = $DB->start_delegated_transaction();
        $inserted = [];
        foreach ($params['assignments'] as $assignment) {
            $assignment = (object)$assignment;
            if (!$DB->record_exists('local_solsits_assign', ['sitsref' => $assignment->sitsref])) {
                throw new moodle_exception('error:sitsrefnotexist', 'local_solsits', null, $assignment->sitsref);
            }
            // We don't ever want to change the courseid, but we need it for context, so check that sitsref and courseid
            // match.
            $sitsassign = sitsassign::get_record(['sitsref' => $assignment->sitsref]);
            if (isset($assignment->courseid) && $assignment->courseid != $sitsassign->get('courseid')) {
                throw new moodle_exception('error:courseiddoesnotmatch', 'local_solsits');
            } else {
                $assignment->courseid = $sitsassign->get('courseid');
            }

            $context = context\course::instance($assignment->courseid);
            self::validate_context($context);

            $assignment = helper::set_scale($assignment);

            // Check that the user has the permission to create the assignment with this method.
            // And all the core assign capabilities to add/update/delete.
            require_capability('local/solsits:manageassignments', $context);

            // Save the assignment in the local_sits_assign table.
            // A task will create the actual assignments later.
            $assign = new sitsassign($sitsassign->get('id'), $assignment);
            $assign->save();
            if ($assign->get('cmid') > 0) {
                // Recalulate dates and update Course module settings, only if it has already been created.
                $assign->update_assignment();
            }
            $assignment->id = $assign->get('id');
            $assignment->cmid = $assign->get('cmid');
            $inserted[] = $assignment;
        }

        $transaction->allow_commit();
        return $inserted;
    }

    /**
     * Data returned when updating an assignment
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'ID in the sitsassign table'),
                'courseid' => new external_value(PARAM_INT, 'The course id to add the assignment to'),
                'cmid' => new external_value(PARAM_INT, 'The coursemodule id'),
                'sitsref' => new external_value(PARAM_RAW, 'SITS internal reference for the assignment'),
                'title' => new external_value(PARAM_TEXT, 'Assignment title'),
                'reattempt' => new external_value(PARAM_INT, 'Reattempt sequence', VALUE_OPTIONAL, 0),
                'weighting' => new external_value(PARAM_INT, 'Assignment weighting expressed as an integer'),
                'assessmentcode' => new external_value(PARAM_TEXT, 'Assessment code (PROJ1 etc)'),
                'assessmentname' => new external_value(PARAM_TEXT, 'Assessment name'),
                'sequence' => new external_value(PARAM_ALPHANUM, 'Sequence of assessment, padded index'),
                'duedate' => new external_value(PARAM_INT, 'Due date timestamp (usually 4pm)'),
                'grademarkexempt' => new external_value(PARAM_BOOL, 'Is this grademark exempt', VALUE_DEFAULT, false),
                'scale' => new external_value(PARAM_ALPHANUMEXT, 'Shortname of scale to be used', VALUE_OPTIONAL),
                'availablefrom' => new external_value(
                        PARAM_INT, 'When the assignment is available to the student from', VALUE_OPTIONAL),
            ])
        );
    }
}
