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
 * Externallib webservices test
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use context_module;
use externallib_advanced_testcase;
use local_solsits\task\task_trait;
use local_solsits_generator;
use mod_assign_testable_assign;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/solsits/tests/generator.php');
require_once($CFG->dirroot . '/local/solsits/tests/task/task_trait.php');
require_once($CFG->dirroot . '/local/solsits/externallib.php');
require_once($CFG->dirroot . '/mod/assign/tests/fixtures/testable_assign.php');

/**
 * Test externallib functions
 * @group sol
 */
class externallib_test extends externallib_advanced_testcase {
    use generator;
    use task_trait;

    /**
     * Add assignments
     * @covers \local_solsits_external::add_assignments
     *
     * @dataProvider add_assignments_provider
     * @param array $assign Assignment settings
     * @param string|null $courseidnumber Course idnumber. Null, if don't create.
     * @param string $expectederror The exception type exected, if error.
     * @return void
     */
    public function test_add_assignments($assign, $courseidnumber, $expectederror) {
        $this->resetAfterTest();
        $course = null;
        if ($courseidnumber) {
            $course = $this->getDataGenerator()->create_course([
                'shortname' => $courseidnumber,
                'idnumber' => $courseidnumber,
            ]);
            $assign['courseid'] = $course->id;
        }
        if ($expectederror) {
            $this->expectException($expectederror);
        }
        $this->setAdminUser();
        \local_solsits_external::add_assignments([$assign]);
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
        if ($courseidnumber) {
            $this->assertEquals($course->id, $sitsassign->get('courseid'));
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
            ],
            'With scale' => [
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
            ],
        ];
    }

    /**
     * Try adding the same assignment twice.
     * @covers \local_solsits_external::add_assignments
     *
     * @return void
     */
    public function test_duplicate_add_assignment() {
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
        \local_solsits_external::add_assignments([$assign]);
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
        $this->expectException('invalid_parameter_exception');
        \local_solsits_external::add_assignments([$assign]);
    }

    /**
     * Test update assignments from externallib
     *
     * @param array $before Assignment configuration
     * @param array $after Assignment configuration with change
     * @param string $courseidnumber
     * @param string $expectederror
     * @param boolean $cmexists
     * @dataProvider update_assignment_provider
     * @covers \local_solsits_external::update_assignments
     * @return void
     */
    public function test_update_assignment($before, $after, $courseidnumber, $expectederror = '', $cmexists = false) {
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = get_config('local_solsits');
        $course = $this->getDataGenerator()->create_course([
            'shortname' => $courseidnumber,
            'idnumber' => $courseidnumber,
            'customfield_academic_year' => '2023/24',
            'customfield_pagetype' => 'module',
            'customfield_templateapplied' => 1,
        ]);
        // Need this for creating the assign activities.
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $ssdg->create_solent_gradescales();

        if ($expectederror) {
            $this->expectException($expectederror);
        }

        if (!empty($before)) {
            $before['courseid'] = $course->id;
            // Create before sitsassign record.
            \local_solsits_external::add_assignments([$before]);
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

        // Run update assignment.
        \local_solsits_external::update_assignments([$after]);
        $sitsassignafter = sitsassign::get_record(['sitsref' => $after['sitsref']]);
        // First check the sitsassign table update has happened.
        $this->assertEquals($after['sitsref'], $sitsassignafter->get('sitsref'));
        $this->assertEquals($after['title'], $sitsassignafter->get('title'));
        $this->assertEquals($after['weighting'], $sitsassignafter->get('weighting'));
        $this->assertEquals($after['duedate'], $sitsassignafter->get('duedate'));
        $this->assertEquals($after['grademarkexempt'], $sitsassignafter->get('grademarkexempt'));
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
        $context = context_module::instance($cm->id);
        $assign = new mod_assign_testable_assign($context, $cm, $course);

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
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => 'invalid_parameter_exception',
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
                'courseidnumber' => 'AAP502_A_SEM1_2023/24',
                'expectederror' => '',
                'cmexists' => true,
            ],
        ];
    }
}
