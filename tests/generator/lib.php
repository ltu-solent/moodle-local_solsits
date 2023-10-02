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
 * Generator class for local_solsits
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_solsits\sitsassign;

defined('MOODLE_INTERNAL') || die();

// require_once($CFG->dirroot . '/lib/behat/classes/util.php');
 /**
  * Generator class
  */
class local_solsits_generator extends component_generator_base {
    /**
     * How many assignment have been created.
     *
     * @var integer
     */
    public $assigncount = 0;

    /**
     * Reset the counters
     *
     * @return void
     */
    public function reset() {
        $this->assigncount = 0;
    }

    /**
     * Creates a record in the local_solsits_assign table
     *
     * @param array $record
     * @return sitsassign
     */
    public function create_sits_assign(array $record) {
        global $USER;
        $this->assigncount++;
        $i = $this->assigncount;

        $record = (object)array_merge([
            'sitsref' => "SITS{$i}",
            'cmid' => 0,
            'courseid' => 0,
            'reattempt' => 0, // Set 0 for first attempt, then 1,2,3 for reattempts.
            'title' => "ASSIGN{$i}",
            'weighting' => 100,
            'duedate' => strtotime('+1 week'),
            'availablefrom' => 0,
            'grademarkexempt' => false,
            'scale' => 'grademark',
            'assessmentcode' => 'assessment' . $i,
            'assessmentname' => 'Essay ' . $i,
            'sequence' => '001',
            'usermodified' => $USER->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ], (array)$record);

        $assignment = new sitsassign(0, $record);
        $assignment->create();
        return $assignment;
    }

    /**
     * Create an entry in the local_solsits_assign_grades table
     *
     * @param array $record
     * @return object The created record.
     */
    public function create_assign_grade(array $record) {
        global $DB;
        if (!isset($record['solassignmentid'])) {
            throw new moodle_exception('solassignmentidnotset', 'local_solsits');
        }
        if (!$DB->record_exists('local_solsits_assign', ['id' => $record['solassignmentid']])) {
            throw new moodle_exception('solassignmentidnotexists', 'local_solsits');
        }
        if (!isset($record['graderid'])) {
            throw new moodle_exception('gradernotset', 'local_solsits');
        }
        if (!$DB->record_exists('user', ['id' => $record['graderid']])) {
            throw new moodle_exception('graderidnotexists', 'local_solsits');
        }
        if (!isset($record['studentid'])) {
            throw new moodle_exception('studentidnotset', 'local_solsits');
        }
        if (!$DB->record_exists('user', ['id' => $record['studentid']])) {
            throw new moodle_exception('studentidnotexists', 'local_solsits');
        }
        $record = (object)array_merge([
            'converted_grade' => 0,
            'message' => '',
            'response' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ], (array)$record);
        $insertid = $DB->insert_record('local_solsits_assign_grades', $record);
        $record->id = $insertid;
        return $record;
    }

    /**
     * Helper to create the grades we use for summative assignments
     *
     * @return void
     */
    public function create_solent_gradescales() {
        global $DB;
        $dg = \testing_util::get_data_generator();
        if (!$DB->record_exists('scale', ['name' => 'Solent'])) {
            $solentscale = $dg->create_scale([
                'name' => 'Solent',
                'scale' => 'N, S, F3, F2, F1, D3, D2, D1, C3, C2, C1, B3, B2, B1, A4, A3, A2, A1',
            ]);
            set_config('grademarkscale', $solentscale->id, 'local_solsits');
        }
        if (!$DB->record_exists('scale', ['name' => 'Solent numeric'])) {
            $solentnumeric = $dg->create_scale([
                'name' => 'Solent numeric',
                'scale' => '0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, ' .
                        '21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, ' .
                        '41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, ' .
                        '61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, ' .
                        '81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100',
            ]);
            set_config('grademarkexemptscale', $solentnumeric->id, 'local_solsits');
        }
    }
}
