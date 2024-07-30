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
 * Tests for getting and queue new grades
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\task;

use advanced_testcase;
use local_solsits\generator;
use local_solsits\helper;
use mod_assign_test_generator;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/grade/grade_item.php');
require_once($CFG->dirroot . '/local/solsits/tests/task/task_trait.php');
require_once($CFG->dirroot . '/local/solsits/tests/generator.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Get new grades for export test
 *
 * @covers \local_solsits\task\get_new_grades
 * @group sol
 */
class get_new_grades_task_test extends advanced_testcase {

    use task_trait;
    use mod_assign_test_generator;
    use generator;

    /**
     * Test processing grademark scale assignments.
     *
     * @return void
     */
    public function test_grademark_assignment() {
        global $DB;
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course([
            'shortname' => 'ABC101_A_S1_2022/23',
            'idnumber' => 'ABC101_A_S1_2022/23',
        ]);
        $students = [];
        $grades = [];
        for ($x = 0; $x < 20; $x++) {
            // Student needs to have a numeric idnumber for grades to be uploaded to sits.
            $students[$x] = $this->getDataGenerator()->create_user(['idnumber' => '200000' . $x]);
            $this->getDataGenerator()->enrol_user($students[$x]->id, $course->id, 'student');
            // Mimic grademark scale with various values so we can test convert_grade.
            if ($x < 19) {
                $grades[$x]['grade'] = (float)$x;
            } else {
                $grades[$x]['grade'] = 0;
            }
            $grades[$x]['feedbackcomments'] = "Comment for {$x}. " . $this->getDataGenerator()->loremipsum;
            $grades[$x]['feedbackmisconduct'] = random_int(0, 1);
        }

        $moduleleader = $this->getDataGenerator()->create_user([
            'firstname' => 'Module',
            'lastname' => 'Leader',
        ]);
        $this->getDataGenerator()->enrol_user($moduleleader->id, $course->id, 'editingteacher');
        $dg->create_solent_gradescales();

        $config = get_config('local_solsits');

        $assign = $this->create_instance($course, [
            'blindmarking' => 1,
            'idnumber' => 'ABC101_A_S1_2022/23_PROJ1_0_1',
            'grade' => $config->grademarkscale * -1,
        ]);
        $sitsassign = $dg->create_sits_assign([
            'cmid' => $assign->get_course_module()->id,
            'courseid' => $course->id,
        ]);

        $this->mark_assignments($students, $grades, $assign, $moduleleader, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED);
        $this->setAdminUser();
        foreach ($students as $student) {
            // Test grade has been stored.
            $studentgrade = $assign->get_user_grade($student->id, false);
            $x = substr($student->idnumber, 6);
            $this->assertEquals($grades[$x]['grade'], $studentgrade->grade);
        }
        $expectedoutput = 'Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' .
        $moduleleader->id . ', Grade: 0, Student idnumber: 2000000
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 0, Student idnumber: 2000001
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 55, Student idnumber: 20000010
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 58, Student idnumber: 20000011
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 62, Student idnumber: 20000012
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 65, Student idnumber: 20000013
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 68, Student idnumber: 20000014
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 74, Student idnumber: 20000015
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 83, Student idnumber: 20000016
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 92, Student idnumber: 20000017
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 100, Student idnumber: 20000018
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 0, Student idnumber: 20000019
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 1, Student idnumber: 2000002
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 15, Student idnumber: 2000003
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 20, Student idnumber: 2000004
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 35, Student idnumber: 2000005
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 42, Student idnumber: 2000006
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 45, Student idnumber: 2000007
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 48, Student idnumber: 2000008
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 52, Student idnumber: 2000009
';
        $this->expectOutputString($expectedoutput);
        $this->execute_task('\local_solsits\task\get_new_grades_task');

        // Test grades have been prepared for export.
        $queuedgrades = $DB->get_records('local_solsits_assign_grades', ['solassignmentid' => $sitsassign->get('id')]);
        $this->assertCount(20, $queuedgrades);

        foreach ($queuedgrades as $queuedgrade) {
            $student = $DB->get_record('user', ['id' => $queuedgrade->studentid]);
            // Find x from idnumber.
            $x = substr($student->idnumber, 6);

            $convertedgrade = helper::convert_grade($config->grademarkscale, $grades[$x]['grade']);
            if ($convertedgrade == -1) {
                $convertedgrade = 0;
            }
            $this->assertEquals($convertedgrade, $queuedgrade->converted_grade);
        }
    }

    /**
     * Test processing grademarkexempt scale assignments.
     *
     * @return void
     */
    public function test_grademarkexempt_assignment() {
        global $DB;
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course([
            'shortname' => 'ABC101_A_S1_2022/23',
            'idnumber' => 'ABC101_A_S1_2022/23',
        ]);
        $students = [];
        $grades = [];
        for ($x = 0; $x < 102; $x++) {
            // Student needs to have a numeric idnumber for grades to be uploaded to sits.
            $students[$x] = $this->getDataGenerator()->create_user(['idnumber' => '200000' . $x]);
            $this->getDataGenerator()->enrol_user($students[$x]->id, $course->id, 'student');
            // Mimic grademarkexempt scale with various values so we can test convert_grade.
            switch ($x) {
                case 100:
                    $grades[$x]['grade'] = null;
                case 101:
                    $grades[$x]['grade'] = -1;
                default:
                    $grades[$x]['grade'] = (float)$x;
            }
        }

        $moduleleader = $this->getDataGenerator()->create_user([
            'firstname' => 'Module',
            'lastname' => 'Leader',
        ]);
        $this->getDataGenerator()->enrol_user($moduleleader->id, $course->id, 'editingteacher');
        $dg->create_solent_gradescales();

        $config = get_config('local_solsits');

        $assign = $this->create_instance($course, [
            'blindmarking' => 1,
            'idnumber' => 'ABC101_A_S1_2022/23_PROJ1_0_1',
            'grade' => $config->grademarkexemptscale * -1,
        ]);
        $sitsassign = $dg->create_sits_assign([
            'cmid' => $assign->get_course_module()->id,
            'courseid' => $course->id,
        ]);

        $this->mark_assignments($students, $grades, $assign, $moduleleader, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED);
        $this->setAdminUser();
        foreach ($students as $student) {
            // Test grade has been stored.
            $studentgrade = $assign->get_user_grade($student->id, false);
            $x = substr($student->idnumber, 6);
            $this->assertEquals($grades[$x]['grade'], $studentgrade->grade);
        }
        $expectedoutput = 'Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' .
        $moduleleader->id . ', Grade: 0, Student idnumber: 2000000
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 0, Student idnumber: 2000001
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 9, Student idnumber: 20000010
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 99, Student idnumber: 200000100
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 100, Student idnumber: 200000101
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 10, Student idnumber: 20000011
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 11, Student idnumber: 20000012
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 12, Student idnumber: 20000013
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 13, Student idnumber: 20000014
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 14, Student idnumber: 20000015
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 15, Student idnumber: 20000016
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 16, Student idnumber: 20000017
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 17, Student idnumber: 20000018
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 18, Student idnumber: 20000019
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 1, Student idnumber: 2000002
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 19, Student idnumber: 20000020
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 20, Student idnumber: 20000021
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 21, Student idnumber: 20000022
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 22, Student idnumber: 20000023
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 23, Student idnumber: 20000024
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 24, Student idnumber: 20000025
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 25, Student idnumber: 20000026
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 26, Student idnumber: 20000027
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 27, Student idnumber: 20000028
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 28, Student idnumber: 20000029
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 2, Student idnumber: 2000003
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 29, Student idnumber: 20000030
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 30, Student idnumber: 20000031
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 31, Student idnumber: 20000032
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 32, Student idnumber: 20000033
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 33, Student idnumber: 20000034
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 34, Student idnumber: 20000035
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 35, Student idnumber: 20000036
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 36, Student idnumber: 20000037
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 37, Student idnumber: 20000038
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 38, Student idnumber: 20000039
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 3, Student idnumber: 2000004
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 39, Student idnumber: 20000040
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 40, Student idnumber: 20000041
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 41, Student idnumber: 20000042
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 42, Student idnumber: 20000043
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 43, Student idnumber: 20000044
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 44, Student idnumber: 20000045
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 45, Student idnumber: 20000046
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 46, Student idnumber: 20000047
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 47, Student idnumber: 20000048
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 48, Student idnumber: 20000049
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 4, Student idnumber: 2000005
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 49, Student idnumber: 20000050
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 50, Student idnumber: 20000051
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 51, Student idnumber: 20000052
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 52, Student idnumber: 20000053
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 53, Student idnumber: 20000054
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 54, Student idnumber: 20000055
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 55, Student idnumber: 20000056
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 56, Student idnumber: 20000057
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 57, Student idnumber: 20000058
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 58, Student idnumber: 20000059
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 5, Student idnumber: 2000006
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 59, Student idnumber: 20000060
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 60, Student idnumber: 20000061
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 61, Student idnumber: 20000062
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 62, Student idnumber: 20000063
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 63, Student idnumber: 20000064
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 64, Student idnumber: 20000065
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 65, Student idnumber: 20000066
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 66, Student idnumber: 20000067
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 67, Student idnumber: 20000068
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 68, Student idnumber: 20000069
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 6, Student idnumber: 2000007
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 69, Student idnumber: 20000070
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 70, Student idnumber: 20000071
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 71, Student idnumber: 20000072
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 72, Student idnumber: 20000073
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 73, Student idnumber: 20000074
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 74, Student idnumber: 20000075
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 75, Student idnumber: 20000076
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 76, Student idnumber: 20000077
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 77, Student idnumber: 20000078
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 78, Student idnumber: 20000079
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 7, Student idnumber: 2000008
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 79, Student idnumber: 20000080
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 80, Student idnumber: 20000081
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 81, Student idnumber: 20000082
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 82, Student idnumber: 20000083
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 83, Student idnumber: 20000084
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 84, Student idnumber: 20000085
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 85, Student idnumber: 20000086
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 86, Student idnumber: 20000087
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 87, Student idnumber: 20000088
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 88, Student idnumber: 20000089
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 8, Student idnumber: 2000009
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 89, Student idnumber: 20000090
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 90, Student idnumber: 20000091
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 91, Student idnumber: 20000092
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 92, Student idnumber: 20000093
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 93, Student idnumber: 20000094
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 94, Student idnumber: 20000095
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 95, Student idnumber: 20000096
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 96, Student idnumber: 20000097
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 97, Student idnumber: 20000098
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_PROJ1_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 98, Student idnumber: 20000099
';
        $this->expectOutputString($expectedoutput);
        $this->execute_task('\local_solsits\task\get_new_grades_task');

        // Test grades have been prepared for export.
        $queuedgrades = $DB->get_records('local_solsits_assign_grades', ['solassignmentid' => $sitsassign->get('id')]);
        $this->assertCount(102, $queuedgrades);

        foreach ($queuedgrades as $queuedgrade) {
            $student = $DB->get_record('user', ['id' => $queuedgrade->studentid]);
            // Find x from idnumber.
            $x = substr($student->idnumber, 6);

            $convertedgrade = helper::convert_grade($config->grademarkexemptscale, $grades[$x]['grade']);
            $this->assertEquals($convertedgrade, $queuedgrade->converted_grade);
        }
    }

    /**
     * Test processing grades using Points (/100)
     *
     * @return void
     */
    public function test_pointsbasedsystem() {
        global $DB;
        $this->resetAfterTest();
        $config = get_config('local_solsits');
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_solent_gradescales();

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course([
            'shortname' => 'ABC101_A_SEM1_2022/23',
            'idnumber' => 'ABC101_A_SEM1_2022/23',
        ]);
        $inputgrades = [
            'input' => [-1 , 0, 39, 50, 59.4, 59.5, 59.6, 70, 71, 100],
            'output' => [ 0, 0, 39, 50, 59  , 60  , 60  , 70, 71, 100],
        ];
        $inputcount = count($inputgrades['input']);
        $students = [];
        $grades = [];
        for ($x = 0; $x < $inputcount; $x++) {
            $students[$x] = $this->getDataGenerator()->create_user(['idnumber' => '200000' . $x]);
            $this->getDataGenerator()->enrol_user($students[$x]->id, $course->id, 'student');
            $grades[$x]['feedbackcomments'] = "Comment for {$x}. " . $this->getDataGenerator()->loremipsum;
            $grades[$x]['feedbackmisconduct'] = random_int(0, 1);
            $grades[$x]['grade'] = (float)$inputgrades['input'][$x];
        }
        $moduleleader = $this->getDataGenerator()->create_user([
            'firstname' => 'Module',
            'lastname' => 'Leader',
        ]);
        $this->getDataGenerator()->enrol_user($moduleleader->id, $course->id, 'editingteacher');
        $assign = $this->create_instance($course, [
            'blindmarking' => 1,
            'idnumber' => 'ABC101_A_SEM1_2022/23_PROJ1_0_1',
            'grade' => 100,
        ]);
        $sitsassign = $dg->create_sits_assign([
            'cmid' => $assign->get_course_module()->id,
            'courseid' => $course->id,
            'scale' => 'points',
        ]);
        $this->mark_assignments($students, $grades, $assign, $moduleleader, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED);
        $this->setAdminUser();
        foreach ($students as $student) {
            // Test grade has been stored.
            $studentgrade = $assign->get_user_grade($student->id, false);
            $x = substr($student->idnumber, 6);
            $this->assertEquals($grades[$x]['grade'], $studentgrade->grade);
        }

        $expectedoutput = 'Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 0, Student idnumber: 2000000
Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 0, Student idnumber: 2000001
Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 39, Student idnumber: 2000002
Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 50, Student idnumber: 2000003
Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 59, Student idnumber: 2000004
Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 60, Student idnumber: 2000005
Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 60, Student idnumber: 2000006
Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 70, Student idnumber: 2000007
Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 71, Student idnumber: 2000008
Queued - Course: ABC101_A_SEM1_2022/23, Assignment code: ABC101_A_SEM1_2022/23_PROJ1_0_1, Grader id: ' .
            $moduleleader->id . ', Grade: 100, Student idnumber: 2000009
';
        $this->expectOutputString($expectedoutput);

        $this->execute_task('\local_solsits\task\get_new_grades_task');
        // Test grades have been prepared for export.
        $queuedgrades = $DB->get_records('local_solsits_assign_grades', ['solassignmentid' => $sitsassign->get('id')]);
        $this->assertCount($inputcount, $queuedgrades);

        foreach ($queuedgrades as $queuedgrade) {
            $student = $DB->get_record('user', ['id' => $queuedgrade->studentid]);
            // Find x from idnumber.
            $x = substr($student->idnumber, 6);

            $convertedgrade = $inputgrades['output'][$x];
            $this->assertEquals($convertedgrade, $queuedgrade->converted_grade);
        }
    }

    /**
     * Test that marked, but not released grades are not exported
     *
     * @return void
     */
    public function test_not_released() {
        global $DB;
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course([
            'shortname' => 'ABC101_A_S1_2022/23',
            'idnumber' => 'ABC101_A_S1_2022/23',
        ]);
        $students = [];
        $grades = [];
        for ($x = 0; $x < 20; $x++) {
            // Student needs to have a numeric idnumber for grades to be uploaded to sits.
            $students[$x] = $this->getDataGenerator()->create_user(['idnumber' => '200000' . $x]);
            $this->getDataGenerator()->enrol_user($students[$x]->id, $course->id, 'student');
            // Mimic grademark scale with various values so we can test convert_grade.
            if ($x < 19) {
                $grades[$x]['grade'] = (float)$x;
            } else {
                $grades[$x]['grade'] = 0;
            }
        }

        $moduleleader = $this->getDataGenerator()->create_user([
            'firstname' => 'Module',
            'lastname' => 'Leader',
        ]);
        $this->getDataGenerator()->enrol_user($moduleleader->id, $course->id, 'editingteacher');
        $dg->create_solent_gradescales();

        $config = get_config('local_solsits');

        $assign = $this->create_instance($course, [
            'blindmarking' => 1,
            'idnumber' => 'ABC101_A_S1_2022/23_PROJ1_0_1',
            'grade' => $config->grademarkscale * -1,
        ]);
        $sitsassign = $dg->create_sits_assign([
            'cmid' => $assign->get_course_module()->id,
            'courseid' => $course->id,
        ]);

        $this->mark_assignments($students, $grades, $assign, $moduleleader, ASSIGN_MARKING_WORKFLOW_STATE_READYFORRELEASE);
        $this->setAdminUser();
        foreach ($students as $student) {
            // Test grade has been stored.
            $studentgrade = $assign->get_user_grade($student->id, false);
            $x = substr($student->idnumber, 6);
            $this->assertEquals($grades[$x]['grade'], $studentgrade->grade);
        }
        $this->expectOutputString('No grades have been released
');
        $this->execute_task('\local_solsits\task\get_new_grades_task');

        // Test grades have been prepared for export.
        $queuedgrades = $DB->get_records('local_solsits_assign_grades', ['solassignmentid' => $sitsassign->get('id')]);
        $this->assertCount(0, $queuedgrades);
    }

    /**
     * Test that simple formative assignments are not exported
     *
     * @return void
     */
    public function test_formative() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course([
            'shortname' => 'ABC101_A_S1_2022/23',
            'idnumber' => 'ABC101_A_S1_2022/23',
        ]);
        $students = [];
        $grades = [];
        for ($x = 0; $x < 20; $x++) {
            // Student needs to have a numeric idnumber for grades to be uploaded to sits.
            $students[$x] = $this->getDataGenerator()->create_user(['idnumber' => '200000' . $x]);
            $this->getDataGenerator()->enrol_user($students[$x]->id, $course->id, 'student');
            // Mimic grademark scale with various values so we can test convert_grade.
            if ($x < 19) {
                $grades[$x]['grade'] = (float)$x;
            } else {
                $grades[$x]['grade'] = 0;
            }
        }

        $moduleleader = $this->getDataGenerator()->create_user([
            'firstname' => 'Module',
            'lastname' => 'Leader',
        ]);
        $this->getDataGenerator()->enrol_user($moduleleader->id, $course->id, 'editingteacher');
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_solent_gradescales();

        $config = get_config('local_solsits');
        // We don't allow idnumbers for formative assignments.
        $assign = $this->create_instance($course, [
            'blindmarking' => 1,
            'idnumber' => '',
            'grade' => $config->grademarkscale * -1,
        ]);

        $this->mark_assignments($students, $grades, $assign, $moduleleader, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED);
        $this->setAdminUser();
        foreach ($students as $student) {
            // Test grade has been stored.
            $studentgrade = $assign->get_user_grade($student->id, false);
            $x = substr($student->idnumber, 6);
            $this->assertEquals($grades[$x]['grade'], $studentgrade->grade);
        }

        $this->expectOutputString('No grades have been released
');
        $this->execute_task('\local_solsits\task\get_new_grades_task');

        // No grades should have been exported.
        $queuedgrades = $DB->get_records('local_solsits_assign_grades');
        $this->assertCount(0, $queuedgrades);
    }

    /**
     * Test that Quercus assignments are not exported. Quercus grades are handled entirely by Quercus.
     *
     * @return void
     */
    public function test_quercus() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course([
            'shortname' => 'ABC101_A_S1_2022/23',
            'idnumber' => 'ABC101_A_S1_2022/23',
        ]);
        $students = [];
        $grades = [];
        for ($x = 0; $x < 20; $x++) {
            // Student needs to have a numeric idnumber for grades to be uploaded to sits.
            $students[$x] = $this->getDataGenerator()->create_user(['idnumber' => '200000' . $x]);
            $this->getDataGenerator()->enrol_user($students[$x]->id, $course->id, 'student');
            // Mimic grademark scale with various values so we can test convert_grade.
            if ($x < 19) {
                $grades[$x]['grade'] = (float)$x;
            } else {
                $grades[$x]['grade'] = 0;
            }
        }

        $moduleleader = $this->getDataGenerator()->create_user([
            'firstname' => 'Module',
            'lastname' => 'Leader',
        ]);
        $this->getDataGenerator()->enrol_user($moduleleader->id, $course->id, 'editingteacher');
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_solent_gradescales();

        $config = get_config('local_solsits');

        $assign = $this->create_instance($course, [
            'blindmarking' => 1,
            'idnumber' => 'ABC101_A_S1_2022/23_PROJ1_0_1',
            'grade' => $config->grademarkscale * -1,
        ]);
        // The difference between Quercus and SITS assignments is that SITS assignments are logged in the
        // local_solsits_assign table, and Quercus asssignments are not.

        $this->mark_assignments($students, $grades, $assign, $moduleleader, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED);
        $this->setAdminUser();
        foreach ($students as $student) {
            // Test grade has been stored.
            $studentgrade = $assign->get_user_grade($student->id, false);
            $x = substr($student->idnumber, 6);
            $this->assertEquals($grades[$x]['grade'], $studentgrade->grade);
        }

        $this->execute_task('\local_solsits\task\get_new_grades_task');

        // No grades should have been exported.
        $queuedgrades = $DB->get_records('local_solsits_assign_grades');
        $this->assertCount(0, $queuedgrades);
    }
}
