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
final class add_assignments_test extends externallib_advanced_testcase {
    use generator;
    use task_trait;
    use mod_assign_test_generator;

    /**
     * Add assignments
     * @covers \local_solsits_external::add_assignments
     *
     * @dataProvider add_assignments_provider
     * @param array $assign Assignment settings
     * @param string|null $courseidnumber Course idnumber. Null, if don't create.
     * @param string $expectederror The exception type exected, if error.
     * @param bool $cmexists Create a cm for testing
     * @return void
     */
    public function test_add_assignments($assign, $courseidnumber, $expectederror, $cmexists = false): void {
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_solent_gradescales();
        $config = get_config('local_solsits');
        $defaultscale = $config->defaultscale ?? '';
        $course = null;
        if ($courseidnumber) {
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
            $assign['courseid'] = $course->id;
        }
        if ($expectederror) {
            $this->expectException($expectederror);
        }
        $this->setAdminUser();
        add_assignments::execute([$assign]);
        if ($cmexists) {
            $this->execute_task('\local_solsits\task\create_assignment_task');
            $sitsassign = sitsassign::get_record(['sitsref' => $assign['sitsref']]);
            $this->assertTrue(helper::is_sits_assignment($sitsassign->get('cmid')));
            $this->expectOutputRegex('/New assignment successfully created/');
        }
        $sitsassign = sitsassign::get_record(['sitsref' => $assign['sitsref']]);
        $this->assertEquals($assign['sitsref'], $sitsassign->get('sitsref'));
        $this->assertEquals($assign['title'], $sitsassign->get('title'));
        $this->assertEquals($assign['weighting'], $sitsassign->get('weighting'));
        $this->assertEquals($assign['duedate'], $sitsassign->get('duedate'));
        $this->assertEquals($assign['grademarkexempt'], $sitsassign->get('grademarkexempt'));
        $scale = $assign['scale'] ?? $defaultscale;
        $this->assertEquals($scale, $sitsassign->get('scale'));
        $this->assertEquals($assign['availablefrom'], $sitsassign->get('availablefrom'));
        $this->assertEquals($assign['reattempt'], $sitsassign->get('reattempt'));
        $this->assertEquals($assign['assessmentcode'], $sitsassign->get('assessmentcode'));
        $this->assertEquals($assign['assessmentname'], $sitsassign->get('assessmentname'));
        $this->assertEquals($assign['sequence'], $sitsassign->get('sequence'));
        if ($courseidnumber) {
            $this->assertEquals($course->id, $sitsassign->get('courseid'));
        }
        if (!$cmexists) {
            $this->assertEquals(0, $sitsassign->get('cmid'));
            return;
        }

        // Check scale.
        // Now check the actual coursemodule has been created with the correct scale.
        $cm = get_coursemodule_from_id('assign', $sitsassign->get('cmid'));
        $context = context\module::instance($cm->id);
        $assign = new mod_assign_testable_assign($context, $cm, $course);
        $gradeitem = $assign->get_grade_item();
        $scale = $sitsassign->get('scale');
        if ($scale == 'points') {
            $this->assertEquals(GRADE_TYPE_VALUE, $gradeitem->gradetype);
        } else {
            $this->assertEquals(GRADE_TYPE_SCALE, $gradeitem->gradetype);
            if ($sitsassign->get('grademarkexempt')) {
                $scale = 'grademarkexemptscale';
            } else {
                $scale = 'grademarkscale';
            }
            $scaleid = $config->{$scale};
            $this->assertEquals($scaleid, $gradeitem->scaleid);
        }
    }

    /**
     * Add assignments using points gradetype
     * @covers \local_solsits_external::add_assignments
     *
     * @dataProvider add_assignments_provider
     * @param array $assign Assignment settings
     * @param string|null $courseidnumber Course idnumber. Null, if don't create.
     * @param string $expectederror The exception type exected, if error.
     * @param bool $cmexists Create a cm for testing
     * @return void
     */
    public function test_add_assignments_points($assign, $courseidnumber, $expectederror, $cmexists): void {
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_solent_gradescales();
        set_config('defaultscale', 'points', 'local_solsits');
        $config = get_config('local_solsits');
        $defaultscale = $config->defaultscale ?? '';
        $course = null;
        if ($courseidnumber) {
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
            $assign['courseid'] = $course->id;
        }
        if ($expectederror) {
            $this->expectException($expectederror);
        }
        $this->setAdminUser();
        add_assignments::execute([$assign]);
        if ($cmexists) {
            $this->execute_task('\local_solsits\task\create_assignment_task');
            $sitsassign = sitsassign::get_record(['sitsref' => $assign['sitsref']]);
            $this->assertTrue(helper::is_sits_assignment($sitsassign->get('cmid')));
            $this->expectOutputRegex('/New assignment successfully created/');
        }
        $sitsassign = sitsassign::get_record(['sitsref' => $assign['sitsref']]);
        $this->assertEquals($assign['sitsref'], $sitsassign->get('sitsref'));
        $this->assertEquals($assign['title'], $sitsassign->get('title'));
        $this->assertEquals($assign['weighting'], $sitsassign->get('weighting'));
        $this->assertEquals($assign['duedate'], $sitsassign->get('duedate'));
        $this->assertEquals($assign['grademarkexempt'], $sitsassign->get('grademarkexempt'));
        $scale = $assign['scale'] ?? $defaultscale;
        $this->assertEquals($scale, $sitsassign->get('scale'));
        $this->assertEquals($assign['availablefrom'], $sitsassign->get('availablefrom'));
        $this->assertEquals($assign['reattempt'], $sitsassign->get('reattempt'));
        $this->assertEquals($assign['assessmentcode'], $sitsassign->get('assessmentcode'));
        $this->assertEquals($assign['assessmentname'], $sitsassign->get('assessmentname'));
        $this->assertEquals($assign['sequence'], $sitsassign->get('sequence'));
        if ($courseidnumber) {
            $this->assertEquals($course->id, $sitsassign->get('courseid'));
        }
        if (!$cmexists) {
            $this->assertEquals(0, $sitsassign->get('cmid'));
            return;
        }

        // Check scale.
        // Now check the actual coursemodule has been created with the correct scale.
        $cm = get_coursemodule_from_id('assign', $sitsassign->get('cmid'));
        $context = context\module::instance($cm->id);
        $assign = new mod_assign_testable_assign($context, $cm, $course);
        $gradeitem = $assign->get_grade_item();
        $scale = $sitsassign->get('scale');
        if ($scale == 'points') {
            $this->assertEquals(GRADE_TYPE_VALUE, $gradeitem->gradetype);
        } else {
            $this->assertEquals(GRADE_TYPE_SCALE, $gradeitem->gradetype);
            if ($sitsassign->get('grademarkexempt')) {
                $scale = 'grademarkexemptscale';
            } else {
                $scale = 'grademarkscale';
            }
            $scaleid = $config->{$scale};
            $this->assertEquals($scaleid, $gradeitem->scaleid);
        }
    }

    /**
     * Provider from add_assignments
     *
     * @return array
     */
    public static function add_assignments_provider(): array {
        return [
            'Valid assignment' => [
                'assign' => [
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
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
                'cmexists' => true,
            ],
            'No due date' => [
                'assign' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (50%)',
                    'weighting' => '50',
                    'duedate' => 0,
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                ],
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
                'cmexists' => false,
            ],
            'Course not exists' => [
                'assign' => [
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
                'courseidnumber' => null,
                'expectederror' => 'invalid_parameter_exception',
                'cmexists' => false,
            ],
            'With scale' => [
                'assign' => [
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
                    'scale' => 'points',
                ],
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
                'cmexists' => true,
            ],
            'With non-existent scale' => [
                'assign' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (50%)',
                    'weighting' => '50',
                    'duedate' => 0,
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                    'scale' => 'Pass-Fail',
                ],
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
                'cmexists' => false,
            ],
            'With malformed scale' => [
                'assign' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'CGI Production - Portfolio 1 (50%)',
                    'weighting' => '50',
                    'duedate' => 0,
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 0,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Project 1',
                    'sequence' => '001',
                    'scale' => 'Pass/Fail',
                ],
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => 'invalid_parameter_exception',
                'cmexists' => false,
            ],
            // Max length 255.
            'Long title' => [
                'assign' => [
                    'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_0',
                    'title' => 'Communication  Reflective and Professional Practic - ' .
                        'Personal Reflective Document (50%) First Reattempt',
                    'weighting' => '50',
                    'duedate' => strtotime('+2 weeks 16:00'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                    'reattempt' => 1,
                    'assessmentcode' => 'AAP50201',
                    'assessmentname' => 'Personal Reflective Document',
                    'sequence' => '001',
                ],
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
                'cmexists' => true,
            ],
        ];
    }

    /**
     * Try adding the same assignment twice.
     * @covers \local_solsits_external::add_assignments
     *
     * @return void
     */
    public function test_duplicate_add_assignment(): void {
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

        // Now try to add an assignment with the same sitsref: Fail.
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('error:sitsrefinuse', 'local_solsits', $assign['sitsref']));
        add_assignments::execute([$assign]);
    }

    /**
     * Reattempts should use the same gradingtype (Point/Scale) as the original version.
     * @covers \local_solsits_external::add_assignments
     *
     * @return void
     */
    public function test_reattempt_adopt_a_scale(): void {
        global $DB;
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_solent_gradescales();
        set_config('defaultscale', '', 'local_solsits');
        $firstattempt = [
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
        $firstattempt['courseid'] = $course->id;
        $this->setAdminUser();

        add_assignments::execute([$firstattempt]);
        // The previous scale can only be applied, if the assignment activity actually exists.
        $this->execute_task('\local_solsits\task\create_assignment_task');

        $sitsassign = sitsassign::get_record(['sitsref' => $firstattempt['sitsref']]);
        $this->assertEquals($firstattempt['sitsref'], $sitsassign->get('sitsref'));
        $this->assertEquals($firstattempt['title'], $sitsassign->get('title'));
        $this->assertEquals($firstattempt['weighting'], $sitsassign->get('weighting'));
        $this->assertEquals($firstattempt['duedate'], $sitsassign->get('duedate'));
        $this->assertEquals($firstattempt['grademarkexempt'], $sitsassign->get('grademarkexempt'));
        $this->assertEquals($firstattempt['availablefrom'], $sitsassign->get('availablefrom'));
        $this->assertEquals($firstattempt['reattempt'], $sitsassign->get('reattempt'));
        $this->assertEquals($firstattempt['assessmentcode'], $sitsassign->get('assessmentcode'));
        $this->assertEquals($firstattempt['assessmentname'], $sitsassign->get('assessmentname'));
        $this->assertEquals($firstattempt['sequence'], $sitsassign->get('sequence'));
        $this->assertEquals('', $sitsassign->get('scale'));
        $this->assertNotEquals(0, $sitsassign->get('cmid'));
        $this->assertEquals($course->id, $sitsassign->get('courseid'));

        // Now use Points.
        set_config('defaultscale', 'points', 'local_solsits');

        $secondattempt = [
            'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_1',
            'title' => 'CGI Production - Portfolio 1 (100%) - Reattempt 1',
            'weighting' => '50',
            'duedate' => strtotime('+2 weeks 16:00'),
            'grademarkexempt' => false,
            'availablefrom' => 0,
            'reattempt' => 1,
            'assessmentcode' => 'AAP50201',
            'assessmentname' => 'Project 1',
            'sequence' => '001',
            'courseid' => $course->id,
        ];
        add_assignments::execute([$secondattempt]);
        $this->execute_task('\local_solsits\task\create_assignment_task');
        $this->expectOutputString('New assignment successfully created: AAP502_A_SEM1_2023/24_AAP50201_001_0
New assignment successfully created: AAP502_A_SEM1_2023/24_AAP50201_001_1
');
        $sitsassign = sitsassign::get_record(['sitsref' => $secondattempt['sitsref']]);
        $this->assertEquals('grademarkscale', $sitsassign->get('scale'));
        [$course, $cm] = get_course_and_cm_from_cmid($sitsassign->get('cmid'), 'assign');
        $assign = new mod_assign_testable_assign($context, $cm, $course);
        $gradeitem = $assign->get_grade_item();
        $config = get_config('local_solsits');
        $this->assertEquals(GRADE_TYPE_SCALE, $gradeitem->gradetype);
        $this->assertEquals($config->grademarkscale, $gradeitem->scaleid);
    }
}
