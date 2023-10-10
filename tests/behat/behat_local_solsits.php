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
 * Behat steps for local solsits
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;
use local_solsits\generator;

/**
 * Behat steps for local solsits
 */
class behat_local_solsits extends behat_base {

    /**
     * Create new sitsassign record
     *
     * @Given /^the following SITS assignment exists:$/
     * @param TableNode $data
     * @return void
     */
    public function the_following_sitsassign_exists(TableNode $data) {
        global $DB;
        $assigndata = $data->getRowsHash();

        if (!isset($assigndata['course'])) {
            throw new Exception('The course shortname must be provided in the course field');
        }
        $course = $DB->get_record('course', ['shortname' => $assigndata['course']], '*', MUST_EXIST);
        $assigndata['courseid'] = $course->id;
        unset($assigndata['course']);

        if (!isset($assigndata['sitsref'])) {
            throw new Exception('The sitsref must be specified');
        }
        // Get the cmid for the assignment by using the idnumber.
        $cm = $DB->get_record('course_modules', ['idnumber' => $assigndata['sitsref']]);
        if ($cm) {
            $assigndata['cmid'] = $cm->id;
        }
        /** @var local_solsits_generator $ssdg */
        $ssdg = behat_util::get_data_generator()->get_plugin_generator('local_solsits');
        $sitsassign = $ssdg->create_sits_assign($assigndata);
        if (!$cm) {
            // Create the assignment activity if it doesn't already exist.
            $sitsassign->create_assignment();
        }
    }

    /**
     * Set up gradescales for Solent
     *
     * @Given /^the solent gradescales are setup$/
     * @return void
     */
    public function the_solent_gradescales_are_setup() {
        /** @var local_solsits_generator $ssdg */
        $ssdg = behat_util::get_data_generator()->get_plugin_generator('local_solsits');
        // Set them up, if they don't already exist.
        $ssdg->create_solent_gradescales();
    }

    /**
     * Creates or updates entries in SITS assign table.
     *
     * @Given /^the following SITS grades are stored for "([^"]*)":$/
     * @param string $idnumber Assignment idnumber
     * @param TableNode $data
     * @return void
     */
    public function the_following_sits_srsstatus_responses_are_stored($idnumber, TableNode $data) {
        global $DB, $USER;
        $rows = $data->getHash();
        $sitsassign = $DB->get_record('local_solsits_assign', ['sitsref' => $idnumber]);
        if (!$sitsassign) {
            throw new moodle_exception('solassignmentidnotexists', 'local_solsits');
        }
        foreach ($rows as $row) {
            $student = $DB->get_record('user', ['username' => $row['user']]);
            $graderecord = $DB->get_record('local_solsits_assign_grades', [
                'solassignmentid' => $sitsassign->id,
                'studentid' => $student->id,
            ]);
            if (!$graderecord) {
                $graderecord = new stdClass();
                $graderecord->solassignmentid = $sitsassign->id;
                $graderecord->graderid = $USER->id;
                $graderecord->studentid = $student->id;
                $graderecord->converted_grade = $row['converted_grade'] ?? 0;
                $graderecord->response = $row['response'] ?? null;
                $graderecord->message = $row['message'] ?? null;
                $graderecord->timecreated = $row['timecreated'] ?? time();
                $graderecord->timemodified = $row['timemodified'] ?? time();
                $DB->insert_record('local_solsits_assign_grades', $graderecord);
            } else {
                $graderecord->converted_grade = $row['converted_grade'] ?? $graderecord->converted_grade;
                $graderecord->response = $row['response'] ?? $graderecord->response;
                $graderecord->message = $row['message'] ?? $graderecord->message;
                $graderecord->timecreated = $row['timecreated'] ?? $graderecord->timecreated;
                $graderecord->timemodified = $row['timemodified'] ?? time();
                $DB->update_record('local_solsits_assign_grades', $graderecord);
            }
        }
    }
}
