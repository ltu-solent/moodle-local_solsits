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
require_once($CFG->dirroot . '/local/solsits/tests/task/task_trait.php');

use advanced_testcase;
use local_solsits\solcourse;
use stdClass;

/**
 * Apply template test class
 */
class applytemplate_task_test extends advanced_testcase {
    use task_trait;

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
                    'visible' => 0 // Initially the course should be hidden until the template has been applied.
                ]);
                $targetcourses[$key][] = $course;
                $sitscourse = new stdClass();
                $sitscourse->courseid = $course->id;
                $sitscourse->pagetype = $pagetype;
                $sitscourse->session = $session;
                // Add course to solcourse table to manage templating.
                $solcourse = new solcourse(0, $sitscourse);
                $solcourse->save();
            }
        }
        // Explicitly set this to 1 (default is 1).
        set_config('maxtemplates', 1, 'local_solsits');
        $solcourses = solcourse::get_records_select('templateapplied = 0');
        $this->assertCount(18, $solcourses);
        // This should do nothing.
        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = solcourse::get_records_select('templateapplied = 0');
        $this->assertCount(18, $solcourses);

        $soltemplates = [];
        // Create a disabled template.
        $soltemplates['2020/21_module'] = $this->create_template_course('2020/21', 'module', 0);

        // This should still do nothing.
        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = solcourse::get_records_select('templateapplied = 0');
        $this->assertCount(18, $solcourses);

        // Let's enable this template.
        $soltemplates['2020/21_module']->set('enabled', true)->save();

        // This should now process 1 module.
        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = solcourse::get_records_select('templateapplied = 1');
        $this->assertCount(1, $solcourses);
        $firstcourse = reset($solcourses); // Get the course that has had the template applied.
        foreach ($targetcourses['2020/21_module'] as $key => $module) {
            $labelintemplates = $DB->get_record('label', [
                'course' => $module->id, 'name' => 'Label from Template template_2020/21_module.']);
            // The visibility has changed, so get updated record.
            $updatedcourse = $DB->get_record('course', ['id' => $module->id]);
            $targetcourses['2020/21_module'][$key] = $updatedcourse;
            if ($firstcourse->get('courseid') == $module->id) {
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
        $solcourses = solcourse::get_records_select('templateapplied = 1');
        $this->assertCount(3, $solcourses);
        foreach ($targetcourses['2020/21_module'] as $key => $module) {
            $labelintemplates = $DB->get_record('label', [
                'course' => $module->id, 'name' => 'Label from Template template_2020/21_module.']);
            // The visibility has changed, so get updated record.
            $updatedcourse = $DB->get_record('course', ['id' => $module->id]);
            $targetcourses['2020/21_module'][$key] = $updatedcourse;
            // This has the template applied and is now visible.
            $this->assertNotFalse($labelintemplates);
            $this->assertEquals(1, $updatedcourse->visible);
        }

        $solcourses = solcourse::get_records_select('templateapplied = 0');
        $this->assertCount(15, $solcourses);

        // Create 2 more templates, set max at 2. This will take 3 runs to complete.
        set_config('maxtemplates', 2, 'local_solsits');

        foreach (['2021/22_module', '2021/22_course'] as $newyear) {
            [$session, $pagetype] = explode('_', $newyear);
            $soltemplates[$newyear] = $this->create_template_course($session, $pagetype, 1);
        }

        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = solcourse::get_records_select('templateapplied = 1');
        $this->assertCount(5, $solcourses);

        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = solcourse::get_records_select('templateapplied = 1');
        $this->assertCount(7, $solcourses);

        $this->execute_task('\local_solsits\task\applytemplate_task');
        $solcourses = solcourse::get_records_select('templateapplied = 1');
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
}
