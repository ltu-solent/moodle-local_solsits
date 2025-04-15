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

use context_course;

/**
 * Tests for SOL-SITS Integration
 *
 * @covers \local_solsits\task\send_assign_config_errors_message_task
 * @package    local_solsits
 * @category   test
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class send_assign_config_errors_message_task_test extends \advanced_testcase {
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
        $ranges = [
            'r0-1weeks' => [
                'start' => time(),
                'end' => strtotime('+1 week'),
            ],
            'r1-2weeks' => [
                'start' => strtotime('+1 week'),
                'end' => strtotime('+2 weeks'),
            ],
            'r2-3weeks' => [
                'start' => strtotime('+2 week'),
                'end' => strtotime('+3 weeks'),
            ],
        ];
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);
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
        $task = new \local_solsits\task\send_assign_config_errors_messsage_task();
        $task->execute();
        $expectedoutput = '';
        if ($range != 'nil') {
            $emails = $sink->get_messages();
            // One to module leader, the other to configured Gateway list.
            $this->assertEquals(2, $sink->count());
            $email = reset($emails);
            $this->assertSame(get_string($range, 'local_solsits'), $email->subject);
            $this->assertStringContainsString($sitsassign->get('sitsref'), $email->body);
            foreach ($ranges as $k => $r) {
                $itemcount = 0;
                if ($range == $k) {
                    $itemcount = 1;
                }
                $expectedoutput .= $itemcount . ' items found for range ' .
                    date('Y-m-d', $r['start']) . ' - ' . date('Y-m-d', $r['end']) . '
';
            }
        } else {
            $this->assertEquals(0, $sink->count());
            foreach ($ranges as $r) {
                $expectedoutput .= '0 items found for range ' . date('Y-m-d', $r['start']) . ' - ' . date('Y-m-d', $r['end']) . '
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
                'range' => 'r0-1weeks',
            ],
            '1-2 week window dd 10 days' => [
                'duedate' => strtotime('+10 days'),
                'range' => 'r1-2weeks',
            ],
            '2-3 week window dd 15 days' => [
                'duedate' => strtotime('+15 days'),
                'range' => 'r2-3weeks',
            ],
            '3+ week window dd 30 days' => [
                'duedate' => strtotime('+30 days'),
                'range' => 'nil',
            ],
        ];
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
