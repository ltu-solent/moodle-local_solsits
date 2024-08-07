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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * External Webservice functions for local_solsits
 */
class local_solsits_external extends external_api {

    /**
     * Parameters required for adding SITS assignments
     *
     * @return external_function_parameters
     */
    public static function add_assignments_parameters(): external_function_parameters {
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
     * Add SITS assignments to Moodle
     *
     * @param array $assignments
     * @return array Details about added records.
     */
    public static function add_assignments($assignments) {
        global $DB;
        $params = self::validate_parameters(self::add_assignments_parameters(),
                ['assignments' => $assignments]);
        $transaction = $DB->start_delegated_transaction();
        $inserted = [];
        foreach ($params['assignments'] as $assignment) {
            $assignment = (object)$assignment;
            if ($DB->record_exists('local_solsits_assign', ['sitsref' => $assignment->sitsref])) {
                throw new moodle_exception('error:sitsrefinuse', 'local_solsits', null, $assignment->sitsref);
            }
            if (!$DB->record_exists('course', ['id' => $assignment->courseid])) {
                throw new moodle_exception('error:coursenotexist', 'local_solsits', null, $assignment->courseid);
            }
            $context = context_course::instance($assignment->courseid, IGNORE_MISSING);
            self::validate_context($context);
            $assignment = self::set_scale($assignment);

            // Check that the user has the permission to create the assignment with this method.
            // And all the core assign capabilities to add/update/delete.
            require_capability('local/solsits:manageassignments', $context);

            // Save the assignment in the local_sits_assign table.
            // A task will create the actual assignments later.
            $assign = new sitsassign(0, $assignment);
            $assign->save();
            $assignment->id = $assign->get('id');
            $assignment->cmid = $assign->get('cmid');
            $inserted[] = $assignment;
        }

        $transaction->allow_commit();
        return $inserted;
    }

    /**
     * Expected return values when adding a SITS assignment
     *
     * @return external_multiple_structure
     */
    public static function add_assignments_returns(): external_multiple_structure {
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

    /**
     * Update SITS assignment record
     *
     * @return external_function_parameters
     */
    public static function update_assignments_parameters(): external_function_parameters {
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
    public static function update_assignments($assignments) {
        // Do updates.
        // If there is no cmid, just update the record, and it will be updated when the assignment is created.
        // If the cmid exists, then immediately recalculate dates and update the course module.
        // If the weighting is different or the grademark exempt has changed, should this be a new assignment?
        // What do we do if an assignment has been removed? If there are submissions do we still delete it?
        // Do we change the title and hide it?
        global $DB;
        $params = self::validate_parameters(self::add_assignments_parameters(),
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

            $context = context_course::instance($assignment->courseid);
            self::validate_context($context);

            $assignment = self::set_scale($assignment);

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
    public static function update_assignments_returns(): external_multiple_structure {
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

    /**
     * Sets the scale depending on a number of conditions
     *
     * @param stdClass $assignment The assignment object from the ws.
     * @return stdClass The assignment object updated with the scale.
     */
    private static function set_scale(stdClass $assignment): stdClass {
        global $DB;
        $config = get_config('local_solsits');
        // For now, the scale isn't being set by AIS, so we're defaulting to defaultscale, if set.
        // If we go live with defaultscale after an assignment has already been created, we don't want to
        // change the scale, but maybe it's ok to set defaultscale.
        // This will ultimately be handled by add_assignment or update_assignment functions.
        $defaultscale = $config->defaultscale ?? '';
        // Use numeric scale by default for all new assignments.
        if (empty($assignment->scale)) {
            $assignment->scale = $defaultscale;
        }
        // If this is not a reattempt, we're just going with whatever the default is.
        if ($assignment->reattempt == 0) {
            return $assignment;
        }

        // Find the original sitsref by replacing the final part of the current sitsref with zero.
        $attemptpos = strrpos($assignment->sitsref, '_');
        if ($attemptpos === false) {
            return $assignment;
        }
        $originalsitsref = substr($assignment->sitsref, 0, $attemptpos) . '_0';
        $originalsitsassign = $DB->get_record('local_solsits_assign', ['sitsref' => $originalsitsref]);
        if (!$originalsitsassign) {
            return $assignment;
        }

        try {
            [$course, $cm] = get_course_and_cm_from_cmid($originalsitsassign->cmid, 'assign');
            $context = context_module::instance($cm->id);
            $originalassign = new \assign($context, $cm, $course);
            if (!$originalassign) {
                return $assignment;
            }
            // If a scale isn't being used on the original return the default.
            if (!$originalassign->get_grade_item()->scaleid) {
                return $assignment;
            }
            // Use the scale being used on the old assignment.
            if ($originalsitsassign->grademarkexempt) {
                $originalscale = 'grademarkexemptscale';
            } else {
                $originalscale = 'grademarkscale';
            }
            $assignment->scale = $originalscale;
        } catch (Exception $ex) {
            return $assignment;
        }

        return $assignment;
    }

    /**
     * Validate parameters for register sitscourses.
     *
     * @deprecated Not used, no longer required.
     * @return external_function_parameters
     */
    public static function register_sitscourses_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Register SITS courses and modules in Moodle
     *
     * @deprecated Not used, no longer required.
     * @return null
     */
    public static function register_sitscourses() {
        return;
    }

    /**
     * Returned data format for register sitscourses
     *
     * @deprecated Not used, no longer required.
     * @return null
     */
    public static function register_sitscourses_returns() {
        return null;
    }

    /**
     * Returns the sitscourse record for a given courseid
     *
     * @deprecated Not used, no longer required.
     * @return external_function_parameters
     */
    public static function get_sitscourse_template_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Get the sitscourse record for a given courseid
     *
     * @deprecated Not used, no longer required.
     * @return null
     */
    public static function get_sitscourse_template() {
        return null;
    }

    /**
     * Returned data format for register sitscourses
     *
     * @deprecated Not used, no longer required.
     * @return null
     */
    public static function get_sitscourse_template_returns() {
        return null;
    }

    /**
     * Search courses parameters
     *
     * @return external_function_parameters
     */
    public static function search_courses_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Search string'),
            'session' => new external_value(PARAM_TEXT, 'Only include courses in session in results'),
        ]);
    }

    /**
     * Search courses
     *
     * @param string $query
     * @param string $session
     * @return array
     */
    public static function search_courses($query, $session): array {
        global $DB;
        $params = self::validate_parameters(self::search_courses_parameters(),
            [
                'session' => $session,
                'query' => $query,
            ]
        );

        $select = "SELECT id courseid, CONCAT(shortname, ': ', fullname) label FROM {course} ";
        $wheres = [];
        $qparams = [];
        if ($params['session']) {
            $sessionmenu = helper::get_session_menu();
            if (in_array($params['session'], $sessionmenu)) {
                $like = $DB->sql_like('shortname', ':session');
                $wheres[] = $like;
                $qparams['session'] = '%' . $DB->sql_like_escape($params['session']) . '%';
            }
        }
        if ($params['query']) {
            $likeshortname = $DB->sql_like("shortname", ':shortname', false, false);
            $likefullname = $DB->sql_like("fullname", ':fullname', false, false);
            $qparams['shortname'] = '%' . $DB->sql_like_escape($params['query']) . '%';
            $qparams['fullname'] = '%' . $DB->sql_like_escape($params['query']) . '%';
            $wheres[] = " ($likeshortname OR $likefullname) ";
        }

        $where = " WHERE 1=1 ";
        if (!empty($wheres)) {
            $where = " WHERE " . join(' AND ', $wheres);
        }

        $courses = $DB->get_records_sql($select . $where, $qparams, 0, 100);
        return $courses;
    }

    /**
     * Defines the returned structure of the array.
     *
     * @return external_multiple_structure
     */
    public static function search_courses_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'courseid'),
                'label' => new external_value(PARAM_RAW, 'User friendly label - Shortname'),
            ])
        );
    }

    /**
     * AIS parameters (none required).
     *
     * @return external_function_parameters
     */
    public static function ais_testconnection_parameters(): external_function_parameters {
        return new external_function_parameters([
            'something' => new external_value(PARAM_TEXT, 'Nothing needed'),
        ]);
    }

    /**
     * Test AIS function. Only available to siteadmins.
     *
     * @return array Response from remote API
     */
    public static function ais_testconnection() {
        if (!is_siteadmin()) {
            throw new moodle_exception('nopermissions');
        }
        $config = get_config('local_solsits');
        $client = new local_solsits\ais_client([], $config->ais_exportgrades_url, $config->ais_exportgrades_key);
        $result = $client->test_connection();
        return ['result' => $result];
    }

    /**
     * Return result for ais_testconnection
     *
     * @return external_single_structure
     */
    public static function ais_testconnection_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_RAW, 'Something interesting'),
        ]);
    }
}
