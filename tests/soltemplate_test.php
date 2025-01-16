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
 * Soltemplate unit tests
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use advanced_testcase;
use core_course\customfield\course_handler;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Test local_solsits helper functions.
 * @group sol
 */
final class soltemplate_test extends advanced_testcase {

    /**
     * Get template records that match the criteria
     * @covers \local_solsits\soltemplate::get_templateapplied_records
     *
     * @return void
     */
    public function test_get_templateapplied_records(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        // Customfields are already installed.
        $courses = [
            'module' => [],
            'course' => [],
            'noneoftheabove' => [],
        ];
        $sessions = [
            '2021/22',
            '2022/23',
        ];
        // Create 5 modules, 5 courses, 5 something elses.
        foreach ($courses as $key => $empty) {
            for ($x = 0; $x < 5; $x++) {
                foreach ($sessions as $session) {
                    $params = [
                        'fullname' => "Test {$key} {$x}",
                        'shortname' => "{$key}10{$x}_A_SEM1_{$session}",
                        'idnumber' => "{$key}10{$x}_A_SEM1_{$session}",
                    ];
                    if (in_array($key, ['module', 'course'])) {
                        $params['customfield_pagetype'] = $key;
                        $params['customfield_academic_year'] = $session;
                        $params['visible'] = 0;
                    }
                    $courses[$session][$key][] = $this->getDataGenerator()->create_course($params);
                }
            }
        }
        $this->assertCount(20, soltemplate::get_templateapplied_records());
        $this->assertCount(10, soltemplate::get_templateapplied_records('module'));
        $this->assertCount(10, soltemplate::get_templateapplied_records('course'));
        $this->assertCount(10, soltemplate::get_templateapplied_records('', '2021/22'));
        $this->assertCount(10, soltemplate::get_templateapplied_records('', '2022/23'));
        $this->assertCount(5, soltemplate::get_templateapplied_records('module', '2021/22'));
        $this->assertCount(5, soltemplate::get_templateapplied_records('course', '2021/22'));

        // Pretend to apply templates to one of the sessions, then recount.
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        foreach ($courses['2021/22'] as $pagetype => $tcourses) {
            foreach ($tcourses as $course) {
                $customfields = $handler->get_instance_data($course->id, true);
                $context = $handler->get_instance_context($course->id);
                foreach ($customfields as $key => $customfield) {
                    if ($customfield->get_field()->get('shortname') == 'templateapplied') {
                        $customfield->set('value', 1);
                        $customfield->set('contextid', $context->id);
                        $customfield->save();
                    }
                }
            }
        }

        $this->assertCount(10, soltemplate::get_templateapplied_records());
        $this->assertCount(5, soltemplate::get_templateapplied_records('module'));
        $this->assertCount(5, soltemplate::get_templateapplied_records('course'));
        $this->assertCount(0, soltemplate::get_templateapplied_records('', '2021/22'));
        $this->assertCount(10, soltemplate::get_templateapplied_records('', '2022/23'));
        $this->assertCount(0, soltemplate::get_templateapplied_records('module', '2021/22'));
        $this->assertCount(0, soltemplate::get_templateapplied_records('course', '2021/22'));
        $this->assertCount(5, soltemplate::get_templateapplied_records('module', '2022/23'));
        $this->assertCount(5, soltemplate::get_templateapplied_records('course', '2022/23'));
        $this->assertCount(10, soltemplate::get_templateapplied_records('', '2021/22', 1));
        $this->assertCount(10, soltemplate::get_templateapplied_records('', '', 1));
    }
}
