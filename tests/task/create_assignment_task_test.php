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
 * Tests for creating an assignment
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\task;

use advanced_testcase;
use local_solsits\generator;
use local_solsits\sitsassign;
use local_solsits\soltemplate;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/solsits/tests/generator.php');
require_once($CFG->dirroot . '/local/solsits/tests/task/task_trait.php');

/**
 * Test create assignment task
 *
 * @covers \local_solsits\create_assignment_task
 * @group sol
 */
class create_assignment_task_test extends advanced_testcase {
    use generator;
    use task_trait;

    /**
     * Create assignments if the course has templated applied set.
     *
     * @return void
     */
    public function test_template_applied() {
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        set_config('maxassignments', 10, 'local_solsits');
        $dg->create_solent_gradescales();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
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
        $assignments = [];
        for ($x = 0; $x < 10; $x++) {
            $assignments[$x] = $dg->create_sits_assign([
                'courseid' => $course->id,
            ]);
        }
        $this->expectOutputRegex("/^(?!No assignments found to process\.\s)" .
            "((New assignment successfully created: SITS[0-9]+)\s+){10}$/");
        $this->execute_task('\local_solsits\task\create_assignment_task');
        for ($x = 0; $x < 10; $x++) {
            $sitsassign = new sitsassign($assignments[$x]->get('id'));
            $this->assertGreaterThan(0, $sitsassign->get('cmid'));
        }
    }

    /**
     * Don't create assignments if the course template has not been applied.
     *
     * @return void
     */
    public function test_template_not_applied() {
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        set_config('maxassignments', 5, 'local_solsits');

        $course = $this->getDataGenerator()->create_course([
            'customfield_templateapplied' => 0,
        ]);

        $this->expectOutputString("No assignments found to process.
No assignments found to process.
");

        $this->execute_task('\local_solsits\task\create_assignment_task');

        $dg->create_sits_assign([
            'courseid' => $course->id,
        ]);
        $this->execute_task('\local_solsits\task\create_assignment_task');
    }

    /**
     * Create sitsassigns with no duedate set (will not create a Moodle assignment),
     * then set a valid duedate, and they will be created.
     *
     * @return void
     */
    public function test_no_duedate() {
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        set_config('maxassignments', 10, 'local_solsits');
        $dg->create_solent_gradescales();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
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
        $assignments = [];
        // Due date set to 0, so these assignments will not be created.
        for ($x = 0; $x < 10; $x++) {
            $assignments[$x] = $dg->create_sits_assign([
                'courseid' => $course->id,
                'duedate' => 0,
            ]);
        }
        // Make this into a regex because the order returned by postgres seems unpredictable.
        $this->expectOutputRegex("/^(No assignments found to process\.)\s" .
            "((New assignment successfully created: SITS[0-9]+)\s+){10}$/");
        $this->execute_task('\local_solsits\task\create_assignment_task');

        // Now setting the due date to something valid, so they will now be created.
        for ($x = 0; $x < 10; $x++) {
            $sitsassign = new sitsassign($assignments[$x]->get('id'));
            $this->assertEquals(0, $sitsassign->get('cmid'));
            $sitsassign->set('duedate', strtotime('+1 week'));
            $sitsassign->save();
        }
        $this->execute_task('\local_solsits\task\create_assignment_task');
        for ($x = 0; $x < 10; $x++) {
            $sitsassign = new sitsassign($assignments[$x]->get('id'));
            $this->assertGreaterThan(0, $sitsassign->get('cmid'));
        }
    }

    /**
     * Test maxassignment creation limitation works.
     *
     * @return void
     */
    public function test_maxassignments() {
        global $DB;
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        set_config('maxassignments', 1, 'local_solsits');
        $dg->create_solent_gradescales();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
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
        $assignments = [];
        // Due date set to 0, so these assignments will not be created.
        for ($x = 0; $x < 10; $x++) {
            $assignments[$x] = $dg->create_sits_assign([
                'courseid' => $course->id,
            ]);
        }

        $this->expectOutputRegex("/^((New assignment successfully created: SITS[0-9]+)\s+){10}" .
            "(No assignments found to process\.\s)$/");

        $count = $DB->count_records('local_solsits_assign', ['cmid' => 0]);
        $this->assertEquals(10, $count);

        $this->execute_task('\local_solsits\task\create_assignment_task'); // 1.
        $count = $DB->count_records('local_solsits_assign', ['cmid' => 0]);
        $this->assertEquals(9, $count);

        set_config('maxassignments', 4, 'local_solsits');

        $this->execute_task('\local_solsits\task\create_assignment_task'); // 5.
        $count = $DB->count_records('local_solsits_assign', ['cmid' => 0]);
        $this->assertEquals(5, $count);

        $this->execute_task('\local_solsits\task\create_assignment_task'); // 9.
        $count = $DB->count_records('local_solsits_assign', ['cmid' => 0]);
        $this->assertEquals(1, $count);

        $this->execute_task('\local_solsits\task\create_assignment_task'); // 10.
        $count = $DB->count_records('local_solsits_assign', ['cmid' => 0]);
        $this->assertEquals(0, $count);

        $this->execute_task('\local_solsits\task\create_assignment_task'); // No more.
    }
}
