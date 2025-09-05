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
use core\context;
use local_solsits\helper;

/**
 * Tests for SOL-SITS Integration
 *
 * @covers \local_solsits\task\send_assign_config_errors_message_task
 * @package    local_solsits
 * @category   test
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class send_assign_config_errors_message_task_test extends advanced_testcase {
    /**
     * Send emails if assignment falls within window
     *
     * @dataProvider send_email_provider
     * @param int $duedate
     * @param string $range
     * @return void
     */
    public function test_send_email_task($duedate, $range): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_settings();
        set_config('assignmentconfigwarning_ranges', 'r0-1,r1-2,r2-3', 'local_solsits');
        $ranges = send_assign_config_errors_message_task::get_ranges();
        $menu = helper::get_ranges_menu();
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context\course::instance($course->id);
        $sitsassign = $ssdg->create_sits_assign([
            'sitsref' => 'PROJECT1_ABC101_2023/24',
            'reattempt' => 0,
            'title' => 'Project 1 (100%)',
            'weighting' => 100,
            'duedate' => $duedate,
            'grademarkexempt' => false,
            'availablefrom' => 0,
            'courseid' => $course->id,
        ]);
        $sitsassign->create_assignment();
        $mlroleid = $this->getDataGenerator()->create_role([
            'name' => 'Module leader',
            'shortname' => 'moduleleader',
            'archetype' => 'editingteacher',
        ]);
        assign_capability('local/solsits:releasegrades', CAP_ALLOW, $mlroleid, $coursecontext);
        $ml = $this->getDataGenerator()->create_and_enrol($course, 'moduleleader');
        $registryuser = $this->getDataGenerator()->create_user();
        set_config('assignmentconfigwarning_mailinglist', join(',', [$registryuser->username]), 'local_solsits');

        $sink = $this->redirectEmails();
        $task = new \local_solsits\task\send_assign_config_errors_message_task();
        $task->execute();
        $expectedoutput = '';
        if ($range != 'nil') {
            $emails = $sink->get_messages();
            // One to module leader, the other to configured Gateway list.
            $this->assertEquals(2, $sink->count());
            $email = reset($emails);
            $map = helper::map_range($range);
            $startdate = date('Y-m-d', $map['start']);
            $enddate = date('Y-m-d', $map['end']);
            $subject = get_string('forinformation', 'local_solsits') . get_string('rangedates', 'local_solsits', [
                'start' => $startdate,
                'end' => $enddate,
            ]);
            $this->assertSame($subject, $email->subject);
            $this->assertStringContainsString($sitsassign->get('sitsref'), $email->body);
            foreach ($ranges as $k => $r) {
                $itemcount = 0;
                if ($range == $k) {
                    $itemcount = 1;
                }
                $map = helper::map_range($k);
                $startdate = date('Y-m-d', $map['start']);
                $enddate = date('Y-m-d', $map['end']);
                $rangestring = get_string('rangedates', 'local_solsits', [
                    'start' => $startdate,
                    'end' => $enddate,
                ]);
                $expectedoutput .= $itemcount . ' ' . $rangestring . '
';
            }
        } else {
            $this->assertEquals(0, $sink->count());
            foreach ($ranges as $k => $r) {
                $map = helper::map_range($k);
                $startdate = date('Y-m-d', $map['start']);
                $enddate = date('Y-m-d', $map['end']);
                $rangestring = get_string('rangedates', 'local_solsits', [
                    'start' => $startdate,
                    'end' => $enddate,
                ]);
                $expectedoutput .= '0 ' . $rangestring . '
';
            }
        }
        $this->expectOutputString($expectedoutput);
    }

    /**
     * Settings for assignments
     *
     * @return array
     */
    public static function send_email_provider(): array {
        return [
            '0-1 week window dd 5 days' => [
                'duedate' => strtotime('+5 days'),
                'range' => 'r0-1',
            ],
            '1-2 week window dd 10 days' => [
                'duedate' => strtotime('+10 days'),
                'range' => 'r1-2',
            ],
            '2-3 week window dd 15 days' => [
                'duedate' => strtotime('+15 days'),
                'range' => 'r2-3',
            ],
            '3+ week window dd 30 days' => [
                'duedate' => strtotime('+30 days'),
                'range' => 'nil',
            ],
        ];
    }

    /**
     * Prevent sending lots of emails
     *
     * @dataProvider send_email_provider
     * @param int $duedate
     * @param string $range
     * @return void
     */
    public function test_prevent_runs_within_interval($duedate, $range): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_settings();
        set_config('assignmentconfigwarning_ranges', 'r0-1,r1-2,r2-3', 'local_solsits');
        $ranges = send_assign_config_errors_message_task::get_ranges();
        $menu = helper::get_ranges_menu();
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context\course::instance($course->id);
        $sitsassign = $ssdg->create_sits_assign([
            'sitsref' => 'PROJECT1_ABC101_2023/24',
            'reattempt' => 0,
            'title' => 'Project 1 (100%)',
            'weighting' => 100,
            'duedate' => $duedate,
            'grademarkexempt' => false,
            'availablefrom' => 0,
            'courseid' => $course->id,
        ]);
        $sitsassign->create_assignment();
        $mlroleid = $this->getDataGenerator()->create_role([
            'name' => 'Module leader',
            'shortname' => 'moduleleader',
            'archetype' => 'editingteacher',
        ]);
        assign_capability('local/solsits:releasegrades', CAP_ALLOW, $mlroleid, $coursecontext);
        $this->getDataGenerator()->create_and_enrol($course, 'moduleleader');
        $registryuser = $this->getDataGenerator()->create_user();
        set_config('assignmentconfigwarning_mailinglist', join(',', [$registryuser->username]), 'local_solsits');
        $expectedoutput = '';
        $sink = $this->redirectEmails();
        $task = new \local_solsits\task\send_assign_config_errors_message_task();
        // Will send 2 emails.
        $task->execute();

        // Will send 0 emails.
        $lastrunnow = get_config('local_solsits', 'assign_config_errors_lastrun');
        $task->execute();
        $expectedoutput .= 'This task can only run once a week. Last run ' . date('Y-m-d H:i:s', $lastrunnow) . PHP_EOL;

        // Will send 0 emails.
        $lastrun5 = time() - (DAYSECS * 5);
        set_config('assign_config_errors_lastrun', $lastrun5, 'local_solsits');
        $task->execute();
        $expectedoutput .= 'This task can only run once a week. Last run ' . date('Y-m-d H:i:s', $lastrun5) . PHP_EOL;

        // Will send 0 emails.
        $lastrun6 = time() - (DAYSECS * 6);
        set_config('assign_config_errors_lastrun', $lastrun6, 'local_solsits');
        $task->execute();
        $expectedoutput .= 'This task can only run once a week. Last run ' . date('Y-m-d H:i:s', $lastrun6) . PHP_EOL;

        // Will send 2 emails.
        set_config('assign_config_errors_lastrun', time() - (DAYSECS * 7), 'local_solsits');
        $task->execute();

        $sink->get_messages();
        if ($range != 'nil') {
            $this->assertEquals(4, $sink->count());
        } else {
            $this->assertEquals(0, $sink->count());
        }
        $this->expectOutputRegex('#' . $expectedoutput . '#');
    }

    /**
     * Settings required to create an assignment
     *
     * @return void
     */
    private function set_settings() {
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $ssdg->create_solent_gradescales();
        set_config('gradingdueinterval', '4', 'local_solsits');
        set_config('gradingdueintervalsecondplus', '2', 'local_solsits');
    }
}
