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

namespace local_solsits\task;

use advanced_testcase;
use local_solsits\generator;
use local_solsits\helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/solsits/tests/generator.php');

/**
 * Tests for SOL-SITS Integration
 *
 * @package    local_solsits
 * @category   test
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class send_module_config_errors_message_task_test extends advanced_testcase {
    use generator;

    /**
     * Send emails if module falls within window
     *
     * @covers \local_solsits\task\send_module_config_errors_message_task::execute
     * @dataProvider send_email_provider
     * @param int $startdate
     * @param string $range
     * @param bool $emailsent
     * @param bool $hasstudents - Should students be enrolled
     * @param bool $hascontent - Don't send reminders if there's content.
     * @param bool $templateapplied - Don't send reminders if the template has not been applied.
     * @return void
     */
    public function test_send_email_task($startdate, $range, $emailsent, $hasstudents, $hascontent, $templateapplied): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_settings();

        set_config('moduleconfigurationwarning_ranges', $range, 'local_solsits');
        $course = $this->getDataGenerator()->create_course([
            'startdate' => $startdate,
            'enddate' => ($startdate + (YEARSECS)),
            'shortname' => 'TEST101_A_SEM1_2023/24',
            'idnumber' => 'TEST101_A_SEM1_2023/24',
            'customfield_pagetype' => 'module',
            'customfield_session' => '2023/24',
            'customfield_templateapplied' => 0,
            'visible' => 0,
        ]);
        if ($templateapplied) {
            // Apply template to create the custom fields and values.
            $this->apply_template($course->id, '2023/24', 'module', 1);
        }

        $course = get_course($course->id);
        if ($hascontent) {
            $this->getDataGenerator()->create_course_section([
                'course' => $course->id,
                'name' => 'Test section',
                'section' => 6,
            ]);
            $this->getDataGenerator()->create_module('label', [
                'course' => $course->id,
                'section' => 6,
                'intro' => "Label in new section.",
            ]);
        }
        if ($hasstudents) {
            $this->getDataGenerator()->create_and_enrol($course, 'student');
        }
        $mlroleid = $this->getDataGenerator()->create_role([
            'name' => 'Module leader',
            'shortname' => 'moduleleader',
            'archetype' => 'editingteacher',
        ]);
        $coursecontext = \core\context\course::instance($course->id);
        assign_capability('local/solsits:releasegrades', CAP_ALLOW, $mlroleid, $coursecontext);
        $this->getDataGenerator()->create_and_enrol($course, 'moduleleader');
        $sink = $this->redirectEmails();
        // Run the task.
        ob_start();
        (new send_module_config_errors_message_task())->execute();
        ob_end_clean();
        $messages = $sink->get_messages();
        if (!$emailsent) {
            $this->assertEmpty($messages);
            return;
        }
        $this->assertCount(1, $messages);
        $sink->close();
    }

    /**
     * Data provider for test_send_email_task
     *
     * @return array
     */
    public static function send_email_provider(): array {
        $now = time();
        return [
            'Module starting in 3 days (range 0-1)' => [
                'startdate' => ($now + (DAYSECS * 3)),
                'range' => 'r0-1',
                'emailsent' => true,
                'hasstudents' => true,
                'hascontent' => false,
                'templateapplied' => true,
            ],
            'Module starting in 10 days (range 1-2)' => [
                'startdate' => ($now + (DAYSECS * 10)),
                'range' => 'r1-2',
                'emailsent' => true,
                'hasstudents' => true,
                'hascontent' => false,
                'templateapplied' => true,
            ],
            'Module starting in 20 days (range 2-3)' => [
                'startdate' => ($now + (DAYSECS * 20)),
                'range' => 'r2-3',
                'emailsent' => true,
                'hasstudents' => true,
                'hascontent' => false,
                'templateapplied' => true,
            ],
            'Module starting in 3 days (range 1-2, not in range)' => [
                'startdate' => ($now + (DAYSECS * 3)),
                'range' => 'r1-2',
                'emailsent' => false,
                'hasstudents' => true,
                'hascontent' => false,
                'templateapplied' => true,
            ],
            'Module starting in 10 days (range 1-2 no students)' => [
                'startdate' => ($now + (DAYSECS * 10)),
                'range' => 'r1-2',
                'emailsent' => false,
                'hasstudents' => false,
                'hascontent' => false,
                'templateapplied' => true,
            ],
            'Module starting in 3 days (range 0-1 has content)' => [
                'startdate' => ($now + (DAYSECS * 3)),
                'range' => 'r0-1',
                'emailsent' => false,
                'hasstudents' => true,
                'hascontent' => true,
                'templateapplied' => true,
            ],
            'Module starting in 3 days (range 0-1 not visible because not templateapplied)' => [
                'startdate' => ($now + (DAYSECS * 3)),
                'range' => 'r0-1',
                'emailsent' => false,
                'hasstudents' => true,
                'hascontent' => false,
                'templateapplied' => false,
            ],
        ];
    }

    /**
     * Settings required for task
     *
     * @return void
     */
    private function set_settings(): void {
        set_config('moduleconfigurationwarning_mailinglist', '', 'local_solsits');
        $this->create_template_course('2023/24', 'module', 1);
    }
}
