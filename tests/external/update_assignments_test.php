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
use externallib_advanced_testcase;
use local_solsits\generator;
use local_solsits\helper;
use local_solsits\sitsassign;
use local_solsits\task\task_trait;
use mod_assign_test_generator;
use mod_assign_testable_assign;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/solsits/tests/generator.php');
require_once($CFG->dirroot . '/local/solsits/tests/task/task_trait.php');
require_once($CFG->dirroot . '/mod/assign/tests/fixtures/testable_assign.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for SOL-SITS Integration
 *
 * @package    local_solsits
 * @category   test
 * @copyright  2024 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class update_assignments_test extends externallib_advanced_testcase {
    use generator;
    use mod_assign_test_generator;
    use task_trait;
    /**
     * Try updating an existing assignment with the wrong courseid
     * @covers \local_solsits_external::update_assignments
     *
     * @return void
     */
    public function test_wrong_courseid_update_assignment(): void {
        $this->resetAfterTest();
        $assign = [
            'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
            'title' => 'CGI Production - Portfolio 1 (100%)',
            'weighting' => '50',
            'duedate' => strtotime('+2 weeks 16:00'),
            'grademarkexempt' => false,
            'availablefrom' => 0,
            'reattempt' => 0,
            'assessmentcode' => 'AAP50201',
            'assessmentname' => 'Project 1',
            'sequence' => '001',
        ];
        $courseidnumber = 'AAP502_A_SEM1_2023/24';

        $course = $this->getDataGenerator()->create_course([
            'shortname' => $courseidnumber,
            'idnumber' => $courseidnumber,
        ]);
        $wrongcourse = $this->getDataGenerator()->create_course();
        $assign['courseid'] = $course->id;
        $this->setAdminUser();
        // First time of trying: Success.
        add_assignments::execute([$assign]);
        $sitsassign = sitsassign::get_record(['sitsref' => $assign['sitsref']]);
        $this->assertEquals($assign['sitsref'], $sitsassign->get('sitsref'));
        $this->assertEquals($assign['title'], $sitsassign->get('title'));
        $this->assertEquals($assign['weighting'], $sitsassign->get('weighting'));
        $this->assertEquals($assign['duedate'], $sitsassign->get('duedate'));
        $this->assertEquals($assign['grademarkexempt'], $sitsassign->get('grademarkexempt'));
        $this->assertEquals($assign['availablefrom'], $sitsassign->get('availablefrom'));
        $this->assertEquals($assign['reattempt'], $sitsassign->get('reattempt'));
        $this->assertEquals($assign['assessmentcode'], $sitsassign->get('assessmentcode'));
        $this->assertEquals($assign['assessmentname'], $sitsassign->get('assessmentname'));
        $this->assertEquals($assign['sequence'], $sitsassign->get('sequence'));
        $this->assertEquals(0, $sitsassign->get('cmid'));
        $this->assertEquals($course->id, $sitsassign->get('courseid'));

        // Now try to update the assignment with the wrong courseid: Fail.
        $assign['courseid'] = $wrongcourse->id;
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('error:courseiddoesnotmatch', 'local_solsits'));
        update_assignments::execute([$assign]);
    }

    /**
     * Test update assignments from externallib
     *
     * @param array $before Assignment configuration
     * @param array $after Assignment configuration with change
     * @param string $afterscale Set a default scale after the assignment was created
     * @param string $courseidnumber
     * @param string $expectederror
     * @param boolean $cmexists
     * @dataProvider update_assignment_provider
     * @covers \local_solsits_external::update_assignments
     * @return void
     */
    public function test_update_assignment(
        $before,
        $after,
        $afterscale,
        $courseidnumber,
        $expectederror = '',
        $cmexists = false): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course([
            'shortname' => $courseidnumber,
            'idnumber' => $courseidnumber,
        ]);
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $customfields = $handler->get_instance_data($course->id, true);
        $context = $handler->get_instance_context($course->id);
        foreach ($customfields as $key => $customfield) {
            if ($customfield->get_field()->get('shortname') == 'templateapplied') {
                $customfield->set('value', 1);
                $customfield->set('contextid', $context->id);
                $customfield->save();
            }
        }
        // Need this for creating the assign activities.
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $ssdg->create_solent_gradescales();
        $config = get_config('local_solsits');

        if ($expectederror) {
            $this->expectException($expectederror);
        }

        if (!empty($before)) {
            $before['courseid'] = $course->id;
            // Create before sitsassign record.
            add_assignments::execute([$before]);
            $sitsassignbefore = sitsassign::get_record(['sitsref' => $before['sitsref']]);
        }
        $after['courseid'] = $course->id;
        // Run create assignment task.
        if ($cmexists) {
            $this->execute_task('\local_solsits\task\create_assignment_task');
            if (!empty($before)) {
                // Just check the assign course module has been created.
                $sitsassignbefore = sitsassign::get_record(['sitsref' => $before['sitsref']]);
                $this->assertTrue(helper::is_sits_assignment($sitsassignbefore->get('cmid')));
                $this->expectOutputRegex('/New assignment successfully created/');
            }
        }
        set_config('defaultscale', $afterscale, 'local_solsits');
        $config = get_config('local_solsits');

        // Run update assignment.
        update_assignments::execute([$after]);
        $sitsassignafter = sitsassign::get_record(['sitsref' => $after['sitsref']]);
        // First check the sitsassign table update has happened.
        $this->assertEquals($after['sitsref'], $sitsassignafter->get('sitsref'));
        $this->assertEquals($after['title'], $sitsassignafter->get('title'));
        $this->assertEquals($after['weighting'], $sitsassignafter->get('weighting'));
        $this->assertEquals($after['duedate'], $sitsassignafter->get('duedate'));
        $this->assertEquals($after['grademarkexempt'], $sitsassignafter->get('grademarkexempt'));
        $scale = $after['scale'] ?? $afterscale;
        $this->assertEquals($scale, $sitsassignafter->get('scale'));
        $this->assertEquals($after['availablefrom'], $sitsassignafter->get('availablefrom'));
        $this->assertEquals($after['reattempt'], $sitsassignafter->get('reattempt'));
        $this->assertEquals($after['assessmentcode'], $sitsassignafter->get('assessmentcode'));
        $this->assertEquals($after['assessmentname'], $sitsassignafter->get('assessmentname'));
        $this->assertEquals($after['sequence'], $sitsassignafter->get('sequence'));
        $this->assertEquals($course->id, $sitsassignafter->get('courseid'));
        if (!$cmexists) {
            $this->assertEquals(0, $sitsassignafter->get('cmid'));
            return; // Nothing more to check in this context.
        }

        // Now check the actual coursemodule has been updated.
        $cm = get_coursemodule_from_id('assign', $sitsassignafter->get('cmid'));
        $context = context\module::instance($cm->id);
        $assign = new mod_assign_testable_assign($context, $cm, $course);
        $gradeitem = $assign->get_grade_item();
        $scale = $sitsassignafter->get('scale');
        if ($scale == 'points') {
            $this->assertEquals(GRADE_TYPE_VALUE, $gradeitem->gradetype);
        } else {
            $this->assertEquals(GRADE_TYPE_SCALE, $gradeitem->gradetype);
            if ($sitsassignafter->get('grademarkexempt')) {
                $scale = 'grademarkexemptscale';
            } else {
                $scale = 'grademarkscale';
            }
            $scaleid = $config->{$scale};
            $this->assertEquals($scaleid, $gradeitem->scaleid);
        }

        $duedate = helper::set_time($after['duedate']);
        $availablefrom = $after['availablefrom'] == 0 ? 0 : helper::set_time($after['availablefrom'], '');
        $gradingduedate = helper::set_time($duedate, '16:00', "+{$config->gradingdueinterval} week");

        $this->assertEquals($sitsassignafter->get('sitsref'), $assign->get_course_module()->idnumber);
        $this->assertEquals($sitsassignafter->get('title'), $assign->get_course_module()->name);
        $this->assertEquals($duedate, $assign->get_instance()->duedate);
        $this->assertEquals($availablefrom, $assign->get_instance()->allowsubmissionsfromdate);
        $this->assertEquals($gradingduedate, $assign->get_instance()->gradingduedate);
    }

    /**
     * Provider for test_update_assignment
     *
     * @return array
     */
    public static function update_assignment_provider(): array {
        // Need before and after.
        return [
            'Existing sits assignment - No Course Module' => [
                'before' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (50%)',
                    'weighting' => '50',
                    'duedate' => strtotime('+2 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'after' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (100%)',
                    'weighting' => '100',
                    'duedate' => strtotime('+3 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'afterscale' => '',
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
                'cmexists' => false,
            ],
            'Not existing sits assignment' => [
                'before' => [], // Empty to create a non-existing context.
                'after' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (50%)',
                    'weighting' => '50',
                    'duedate' => strtotime('+2 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'afterscale' => '',
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => 'moodle_exception',
                'cmexists' => false,
            ],
            'Existing sits assignment - Course Module exists' => [
                'before' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (50%)',
                    'weighting' => '50',
                    'duedate' => strtotime('+2 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'after' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (100%)',
                    'weighting' => '100',
                    'duedate' => strtotime('+3 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'afterscale' => '',
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
                'cmexists' => true,
            ],
            'Existing sits assignment - Change default scale' => [
                'before' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (50%)',
                    'weighting' => '50',
                    'duedate' => strtotime('+2 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'after' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (100%)',
                    'weighting' => '100',
                    'duedate' => strtotime('+3 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'afterscale' => 'points',
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
                'cmexists' => true,
            ],
        ];
    }

    /**
     * If an an assignment has grades already, don't change the gradetype or scale, but do apply other changes.
     *
     * @param array $before Assignment configuration
     * @param array $after Assignment configuration with change
     * @param string $courseidnumber
     * @param string $expectederror
     * @return void
     * @dataProvider update_assignment_withgrades_provider
     * @covers \local_solsits\sitsassign::update_assignment
     */
    public function test_update_assignment_withgrades($before, $after, $courseidnumber, $expectederror = ''): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course([
            'shortname' => $courseidnumber,
            'idnumber' => $courseidnumber,
        ]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student', [
            'idnumber' => '2000001',
        ]);
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $customfields = $handler->get_instance_data($course->id, true);
        $context = $handler->get_instance_context($course->id);
        foreach ($customfields as $key => $customfield) {
            if ($customfield->get_field()->get('shortname') == 'templateapplied') {
                $customfield->set('value', 1);
                $customfield->set('contextid', $context->id);
                $customfield->save();
            }
        }
        // Need this for creating the assign activities.
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $ssdg->create_solent_gradescales();
        $config = get_config('local_solsits');

        if ($expectederror) {
            $this->expectException($expectederror);
        }

        $before['courseid'] = $course->id;
        // Create before sitsassign record.
        add_assignments::execute([$before]);
        $sitsassignbefore = sitsassign::get_record(['sitsref' => $before['sitsref']]);

        $after['courseid'] = $course->id;
        // Run create assignment task.

        $this->execute_task('\local_solsits\task\create_assignment_task');
        // Just check the assign course module has been created.
        $sitsassignbefore = sitsassign::get_record(['sitsref' => $before['sitsref']]);
        $this->assertTrue(helper::is_sits_assignment($sitsassignbefore->get('cmid')));
        $this->expectOutputRegex('/New assignment successfully created/');

        // Add grades to the assignment.
        $cm = get_coursemodule_from_id('assign', $sitsassignbefore->get('cmid'));
        $context = context\module::instance($cm->id);
        $assign = new mod_assign_testable_assign($context, $cm, $course);
        $beforegradetype = $assign->get_grade_item()->gradetype;
        $students = [$student];
        $grades = [
            [
                'grade' => '50',
                'feedbackcomments' => 'Feedback comment',
                'feedbackmisconduct' => 0,
            ],
        ];
        // Add a submission and grade to prevent the grade scale being changed.
        $this->add_submission($student, $assign, 'My submission');
        $this->submit_for_grading($student, $assign);
        $this->mark_assignments($students, $grades, $assign, $teacher, ASSIGN_MARKING_WORKFLOW_STATE_INMARKING);
        $this->setAdminUser();
        set_config('defaultscale', 'points', 'local_solsits');
        $config = get_config('local_solsits');

        // Run update assignment.
        update_assignments::execute([$after]);
        $sitsassignafter = sitsassign::get_record(['sitsref' => $after['sitsref']]);
        // First check the sitsassign table update has happened.
        $this->assertEquals($after['sitsref'], $sitsassignafter->get('sitsref'));
        $this->assertEquals($after['title'], $sitsassignafter->get('title'));
        $this->assertEquals($after['weighting'], $sitsassignafter->get('weighting'));
        $this->assertEquals($after['duedate'], $sitsassignafter->get('duedate'));
        $this->assertEquals($after['grademarkexempt'], $sitsassignafter->get('grademarkexempt'));
        // There should be no change in the scale being used.
        $this->assertEquals('points', $sitsassignafter->get('scale'));
        $this->assertEquals($after['availablefrom'], $sitsassignafter->get('availablefrom'));
        $this->assertEquals($after['reattempt'], $sitsassignafter->get('reattempt'));
        $this->assertEquals($after['assessmentcode'], $sitsassignafter->get('assessmentcode'));
        $this->assertEquals($after['assessmentname'], $sitsassignafter->get('assessmentname'));
        $this->assertEquals($after['sequence'], $sitsassignafter->get('sequence'));
        $this->assertEquals($course->id, $sitsassignafter->get('courseid'));

        // Now check the actual coursemodule has been updated.
        $cm = get_coursemodule_from_id('assign', $sitsassignafter->get('cmid'));
        $context = context\module::instance($cm->id);
        $assign = new mod_assign_testable_assign($context, $cm, $course);
        $gradeitem = $assign->get_grade_item();
        $this->assertEquals($beforegradetype, $gradeitem->gradetype);
        if ($beforegradetype == GRADE_TYPE_SCALE) {
            if ($sitsassignbefore->get('grademarkexempt')) {
                $scale = 'grademarkexemptscale';
            } else {
                $scale = 'grademarkscale';
            }
            $scaleid = $config->{$scale};
            $this->assertEquals($scaleid, $gradeitem->scaleid);
        }

        // Ensure other changes have been applied.
        $duedate = helper::set_time($after['duedate']);
        $availablefrom = $after['availablefrom'] == 0 ? 0 : helper::set_time($after['availablefrom'], '');
        $gradingduedate = helper::set_time($duedate, '16:00', "+{$config->gradingdueinterval} week");

        $this->assertEquals($sitsassignafter->get('sitsref'), $assign->get_course_module()->idnumber);
        $this->assertEquals($sitsassignafter->get('title'), $assign->get_course_module()->name);
        $this->assertEquals($duedate, $assign->get_instance()->duedate);
        $this->assertEquals($availablefrom, $assign->get_instance()->allowsubmissionsfromdate);
        $this->assertEquals($gradingduedate, $assign->get_instance()->gradingduedate);
    }

    /**
     * Provider for test_update_assignment_withgrades
     *
     * @return array
     */
    public static function update_assignment_withgrades_provider(): array {
        // Need before and after.
        return [
            'Existing sits assignment - grademark' => [
                'before' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (50%)',
                    'weighting' => '50',
                    'duedate' => strtotime('+2 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'after' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (100%)',
                    'weighting' => '100',
                    'duedate' => strtotime('+3 weeks 16:00'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
            ],
            'Existing sits assignment - grademark exempt' => [
                'before' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (50%)',
                    'weighting' => '50',
                    'duedate' => strtotime('+2 weeks 16:00'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'after' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (100%)',
                    'weighting' => '100',
                    'duedate' => strtotime('+3 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
            ],
        ];
    }
}
