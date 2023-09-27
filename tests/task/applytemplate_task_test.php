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
 * Tests for applying the template
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/solsits/tests/generator.php');
require_once($CFG->dirroot . '/local/solsits/tests/task/task_trait.php');

use advanced_testcase;
use local_solsits\generator;
use local_solsits\soltemplate;

/**
 * Apply template test class
 * @group sol
 */
class applytemplate_task_test extends advanced_testcase {
    use task_trait;
    use generator;

    /**
     * Test applytemplate scheduled task
     *
     * @covers \local_solsits\task\applytemplate_task
     * @return void
     */
    public function test_runscheduledtask() {
        global $DB;
        $this->resetAfterTest();
        // This is a task, so the user has permissions to do all things.
        $this->setAdminUser();
        // Set up some courses - different sessions and page type combinations.
        $targetcourses = [
            '2020/21_module' => [],
            '2021/22_module' => [],
            '2022/23_module' => [],
            '2020/21_course' => [],
            '2021/22_course' => [],
            '2022/23_course' => [],
        ];
        foreach ($targetcourses as $key => $list) {
            [$session, $pagetype] = explode('_', $key);
            // Create x courses for each key.
            for ($x = 0; $x < 3; $x++) {
                $course = $this->getDataGenerator()->create_course([
                    'idnumber' => $key . '_' . $x,
                    'shortname' => $key . '_' . $x,
                    'visible' => 0, // Initially the course should be hidden until the template has been applied.
                    'customfield_pagetype' => $pagetype,
                    'customfield_academic_year' => $session,
                    'customfield_templateapplied' => 0,
                ]);
                $targetcourses[$key][] = $course;
            }
        }
        // Explicitly set this to 1 (default is 1).
        set_config('maxtemplates', 1, 'local_solsits');
        $solcourses = soltemplate::get_templateapplied_records();
        $this->assertCount(18, $solcourses);
        // This should do nothing.
        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = soltemplate::get_templateapplied_records();
        $this->assertCount(18, $solcourses);

        $soltemplates = [];
        // Create a disabled template.
        $soltemplates['2020/21_module'] = $this->create_template_course('2020/21', 'module', 0);

        // This should still do nothing.
        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = soltemplate::get_templateapplied_records();
        $this->assertCount(18, $solcourses);

        // Let's enable this template.
        $soltemplates['2020/21_module']->set('enabled', true)->save();

        // This should now process 1 module.
        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = soltemplate::get_templateapplied_records('', '', 1);
        $this->assertCount(1, $solcourses);
        $firstcourse = reset($solcourses); // Get the course that has had the template applied.
        foreach ($targetcourses['2020/21_module'] as $key => $module) {
            $labelintemplates = $DB->get_record('label', [
                'course' => $module->id,
                'name' => 'Label from Template template_2020/21_module.',
            ]);
            // The visibility has changed, so get updated record.
            $updatedcourse = $DB->get_record('course', ['id' => $module->id]);
            $targetcourses['2020/21_module'][$key] = $updatedcourse;
            if ($firstcourse->id == $module->id) {
                // This has the template applied and is now visible.
                $this->assertNotFalse($labelintemplates);
                $this->assertEquals(1, $updatedcourse->visible);
            } else {
                // The others do not and are still hidden.
                $this->assertFalse($labelintemplates);
                $this->assertEquals(0, $updatedcourse->visible);
            }
        }
        // Running the task again, should do the remaining two.
        set_config('maxtemplates', 3, 'local_solsits');
        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = soltemplate::get_templateapplied_records('', '', 1);
        $this->assertCount(3, $solcourses);
        foreach ($targetcourses['2020/21_module'] as $key => $module) {
            $labelintemplates = $DB->get_record('label', [
                'course' => $module->id,
                'name' => 'Label from Template template_2020/21_module.',
            ]);
            // The visibility has changed, so get updated record.
            $updatedcourse = $DB->get_record('course', ['id' => $module->id]);
            $targetcourses['2020/21_module'][$key] = $updatedcourse;
            // This has the template applied and is now visible.
            $this->assertNotFalse($labelintemplates);
            $this->assertEquals(1, $updatedcourse->visible);
        }

        $solcourses = soltemplate::get_templateapplied_records('', '', 0);
        $this->assertCount(15, $solcourses);

        // Create 2 more templates, set max at 2. This will take 3 runs to complete.
        set_config('maxtemplates', 2, 'local_solsits');

        foreach (['2021/22_module', '2021/22_course'] as $newyear) {
            [$session, $pagetype] = explode('_', $newyear);
            $soltemplates[$newyear] = $this->create_template_course($session, $pagetype, 1);
        }

        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = soltemplate::get_templateapplied_records('', '', 1);
        $this->assertCount(5, $solcourses);

        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = soltemplate::get_templateapplied_records('', '', 1);
        $this->assertCount(7, $solcourses);

        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = soltemplate::get_templateapplied_records('', '', 1);
        $this->assertCount(9, $solcourses);
        $expectedoutput = 'Template module_2020/21 has been applied to 2020/21_module_0
Template module_2020/21 has been applied to 2020/21_module_1
Template module_2020/21 has been applied to 2020/21_module_2
Template module_2021/22 has been applied to 2021/22_module_0
Template module_2021/22 has been applied to 2021/22_module_1
Template module_2021/22 has been applied to 2021/22_module_2
Template course_2021/22 has been applied to 2021/22_course_0
Template course_2021/22 has been applied to 2021/22_course_1
Template course_2021/22 has been applied to 2021/22_course_2
';
        $this->expectOutputString($expectedoutput);
    }

    /**
     * Under certain circumstances a template will not be applied
     *
     * @param bool $visible Is target course visible
     * @param bool $hasactivities Has it been editted
     * @param bool $hasusers Does this course already have users enrolled?
     * @param string $message Expected error message
     * @covers \local_solsits\task\applytemplate_task
     * @dataProvider preventapplytemplate_provider
     * @return void
     */
    public function test_preventapplytemplate($visible, $hasactivities, $hasusers, $message) {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->create_template_course('2021/22', 'module', 1);
        $idnumber = random_string(6) . '_A_S1_2021/22';
        $course = $this->getDataGenerator()->create_course([
            'visible' => $visible,
            'idnumber' => $idnumber,
            'shortname' => $idnumber,
            'customfield_pagetype' => 'module',
            'customfield_academic_year' => '2021/22',
            'customfield_templateapplied' => 0,
        ]);
        if ($hasactivities) {
            // Add a couple of labels.
            $this->getDataGenerator()->create_module('label', [
                'course' => $course->id,
                'intro' => "Label 1 on course.",
            ]);
            $this->getDataGenerator()->create_module('label', [
                'course' => $course->id,
                'intro' => "Label 2 on course.",
            ]);
        }
        if ($hasusers) {
            $user1 = $this->getDataGenerator()->create_user();
            $user2 = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
            $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'editingteacher');
        }

        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = soltemplate::get_templateapplied_records('', '', 1);
        $this->assertCount(0, $solcourses);
        $this->expectOutputString($message . " {$idnumber}\n");
    }

    /**
     * Provider for test_preventapplytemplate
     *
     * @return array
     */
    public static function preventapplytemplate_provider(): array {
        return [
            'visible' => [
                'visible' => 1,
                'hasactivities' => 0,
                'hasusers' => 0,
                'message' => "Course visible. Cannot apply template.",
            ],
            'hasactivities' => [
                'visible' => 0,
                'hasactivities' => 1,
                'hasusers' => 0,
                'message' => "Course has been edited. Cannot apply template.",
            ],
            'hasusers' => [
                'visible' => 0,
                'hasactivities' => 0,
                'hasusers' => 1,
                'message' => 'Enrolments already exist. Cannot apply template.',
            ],
            'all' => [
                'visible' => 1,
                'hasactivities' => 1,
                'hasusers' => 1,
                'message' => "Course visible. Cannot apply template.",
            ],
        ];
    }
}
