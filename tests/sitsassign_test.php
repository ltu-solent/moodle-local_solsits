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
 * sitsassign records tests
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use advanced_testcase;
use assign;
use context_module;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/assign/locallib.php');
/**
 * Test sitsassign persistent class
 * @covers \local_solsits\sitsassign
 * @group sol
 */
class sitsassign_test extends advanced_testcase {
    /**
     * Test getting a list of assignments that can now be created
     *
     * @covers \local_solsits\sitsassign::get_create_list
     * @return void
     */
    public function test_get_create_list() {
        $this->resetAfterTest();
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $tcourse = $this->getDataGenerator()->create_course([
            'fullname' => 'Template applied',
            'shortname' => 'templated',
            'idnumber' => 'templated'
        ]);
        $ntcourse = $this->getDataGenerator()->create_course([
            'fullname' => 'Template NOT applied',
            'shortname' => 'nottemplated',
            'idnumber' => 'nottemplated'
        ]);
        $sits = $this->getDataGenerator()->create_module('assign', [
            'course' => $tcourse->id,
            'name' => 'sits',
            'idnumber' => 'SITS_1'
        ]);
        $sitscm = get_coursemodule_from_instance('assign', $sits->id);
        // Add this one in for noise.
        $ssdg->create_sits_assign(['cmid' => $sitscm->id, 'courseid' => $tcourse->id]);
        // No cmid indicates the assignment hasn't been created in the course yet.
        $ssdg->create_sits_assign(['courseid' => $tcourse->id]);
        // No set duedate should not be included.
        $ssdg->create_sits_assign(['courseid' => $tcourse->id, 'duedate' => 0]);
        $ssdg->create_sits_assign(['courseid' => $ntcourse->id]);
        $ssdg->create_sits_assign(['courseid' => $ntcourse->id, 'duedate' => 0]);

        $createlist = sitsassign::get_create_list();
        $this->assertCount(0, $createlist);
        // Pretend to apply templates to one of the courses.
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $customfields = $handler->get_instance_data($tcourse->id, true);
        $context = $handler->get_instance_context($tcourse->id);
        foreach ($customfields as $key => $customfield) {
            if ($customfield->get_field()->get('shortname') == 'templateapplied') {
                $customfield->set('value', 1);
                $customfield->set('contextid', $context->id);
                $customfield->save();
            }
        }
        $createlist = sitsassign::get_create_list();
        $this->assertCount(1, $createlist);
        $sitsassign = reset($createlist);
        $this->assertSame($tcourse->id, $sitsassign->courseid);
    }

    /**
     * Test create assignment
     *
     * @param array $sitsassign Settings for sitsassign table
     * @param bool $coursedeleted Create a course to get an id for sitsassign, then delete it.
     * @dataProvider create_assignment_provider
     * @return void
     */
    public function test_create_assignment($sitsassign, $coursedeleted = false) {
        $this->resetAfterTest();
        $this->set_settings();
        $this->setTimezone('UTC');
        $config = get_config('local_solsits');
        // Perhaps change this is to a WS user with permissions to "Manage activities".
        $this->setAdminUser();
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $course = $this->getDataGenerator()->create_course();
        // Pretend to apply template to the course.
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
        $sitsassign['courseid'] = $course->id;
        // The duedate coming from SITS doesn't necessarily have the correct time site (1600), so we need to adjust for this.
        $duedate = helper::set_time($sitsassign['duedate']);
        $availablefrom = $sitsassign['availablefrom'] == 0 ? 0 : helper::set_time($sitsassign['availablefrom'], '');
        $assign = $ssdg->create_sits_assign($sitsassign);

        if ($coursedeleted) {
            delete_course($course->id);
            $this->expectOutputRegex('/\+\+ Deleted - Activity modules \+\+/');
        }

        // Run actual create assignment method under test.
        $result = $assign->create_assignment();

        if ($coursedeleted) {
            $this->assertFalse($result);
            $this->expectOutputString("The courseid {$assign->get('courseid')} no longer exists.  {$assign->get('sitsref')}");
            // No more tests required.
            return;
        }
        if ($sitsassign['duedate'] == 0) {
            $this->assertFalse($result);
            $this->expectOutputString("Due date has not been set (0), so no assignment has been created. " .
                "{$assign->get('sitsref')}\n");
            return;
        }
        $this->assertTrue($result);
        $this->assertTrue($assign->get('cmid') > 0);
        [$course2, $cm] = get_course_and_cm_from_cmid($assign->get('cmid'), 'assign');
        $this->assertSame($assign->get('sitsref'), $cm->idnumber);
        $this->assertSame($course->id, $course2->id);
        // Check relative dates have worked out.
        $context = context_module::instance($cm->id);
        $assignment = new assign($context, $cm, $course);
        $this->assertEquals($duedate, $assignment->get_instance()->duedate);
        $this->assertEquals($availablefrom, $assignment->get_instance()->allowsubmissionsfromdate);
        $gradingduedate = helper::set_time($duedate, '16:00', "+{$config->gradingdueinterval} week");
        $this->assertEquals($gradingduedate, $assignment->get_instance()->gradingduedate);
        if ($sitsassign['reattempt'] == 0) {
            $cutoffdate = helper::set_time($duedate, '16:00', "+{$config->cutoffinterval} week");
            if ($assign->is_exam()) {
                $cutoffdate = $duedate;
            }
            $this->assertEquals($cutoffdate, $assignment->get_instance()->cutoffdate);
            $this->assertEquals(1, $cm->visible);
            $this->assertEquals(2, $cm->completion);
        } else {
            $cutoffdate = helper::set_time($duedate, '16:00', "+{$config->cutoffintervalsecondplus} week");
            $this->assertEquals($cutoffdate, $assignment->get_instance()->cutoffdate);
            $this->assertEquals(0, $cm->visible);
            $this->assertEquals(0, $cm->completion);
        }
        $this->assertEquals($duedate, $cm->completionexpected);
        // Check it's in section 1.
        $this->assertEquals($config->targetsection, $cm->sectionnum);
        // Check which grade scale is being used.
        if ($assign->get('grademarkexempt')) {
            $this->assertEquals($assignment->get_instance()->grade, $config->grademarkexemptscale * -1);
        } else {
            $this->assertEquals($assignment->get_instance()->grade, $config->grademarkscale * -1);
        }
    }

    /**
     * Create assignment provider
     *
     * @return array
     */
    public function create_assignment_provider(): array {
        return [
            'valid_duedate' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ],
            'first_reattempt' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 1,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+4 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ],
            'deleted_course' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'coursedeleted' => true
            ],
            'no_duedate' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => 0,
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ],
            'availablefrom' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+3 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => strtotime('+1 week')
                ],
                'coursedeleted' => false
            ],
            'grademark_exempt' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ],
            'exam_cutoffdate' => [
                'sitsassign' => [
                    'sitsref' => 'EXAM_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ],
        ];
    }

    /**
     * Update an existing assignment
     *
     * @dataProvider update_assignment_provider
     * @param array $oldassign
     * @param array $newassign
     * @param bool $coursedeleted
     * @return void
     */
    public function test_update_assignment($oldassign, $newassign, $coursedeleted) {
        global $DB;
        $this->resetAfterTest();
        $this->set_settings();
        $this->setTimezone('UTC');
        $config = get_config('local_solsits');
        set_config('enablecompletion', COMPLETION_ENABLED);
        // Perhaps change this is to a WS user with permissions to "Manage activities".
        $this->setAdminUser();
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $course = $this->getDataGenerator()->create_course();
        // Pretend to apply template to the course.
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
        $oldassign['courseid'] = $course->id;
        // The duedate coming from SITS doesn't necessarily have the correct time site (1600), so we need to adjust for this.
        $oldduedate = helper::set_time($oldassign['duedate']);
        $oldavailablefrom = $oldassign['availablefrom'] == 0 ? 0 : helper::set_time($oldassign['availablefrom'], '');
        $newduedate = helper::set_time($newassign['duedate']);
        $newavailablefrom = $newassign['availablefrom'] == 0 ? 0 : helper::set_time($newassign['availablefrom'], '');
        $oldsitsassign = $ssdg->create_sits_assign($oldassign);

        // Run create assignment method.
        $result = $oldsitsassign->create_assignment();

        // Delete the course after creating the assignment to leave a stranded record.
        if ($coursedeleted) {
            delete_course($course->id);
            $this->expectOutputRegex('/\+\+ Deleted - Activity modules \+\+/');
        }

        // Get the assignment from sitsassign and update it with new data.
        $newsitsassign = new sitsassign($oldsitsassign->get('id'), (object)$newassign);
        $newsitsassign->save();

        $result = $newsitsassign->update_assignment();

        if ($coursedeleted) {
            $this->assertFalse($result);
            $this->expectOutputString("The courseid {$newsitsassign->get('courseid')} no longer exists. " .
                "{$newsitsassign->get('sitsref')}");
            // No more tests required.
            return;
        }
        // If the old date is 0 then the record will be updated, but the scheduled task will create the activity later.
        if ($newassign['duedate'] == 0 || $oldassign['duedate'] == 0) {
            $this->assertFalse($result);
            $this->expectOutputRegex("/Due date has not been set \(0\), so no assignment has been created/");
            return;
        }

        $this->assertTrue($result);
        $this->assertTrue($newsitsassign->get('cmid') > 0);
        [$course2, $cm] = get_course_and_cm_from_cmid($newsitsassign->get('cmid'), 'assign');
        $this->assertSame($newsitsassign->get('sitsref'), $cm->idnumber);
        $this->assertSame($course->id, $course2->id);

        // Check relative dates have worked out.
        $context = context_module::instance($cm->id);
        $assignment = new assign($context, $cm, $course);
        if ($oldduedate != $newduedate) {
            $this->assertEquals($newduedate, $assignment->get_instance()->duedate);
        } else {
            $this->assertEquals($oldduedate, $assignment->get_instance()->duedate);
        }
        if ($oldavailablefrom != $newavailablefrom) {
            $this->assertEquals($newavailablefrom, $assignment->get_instance()->allowsubmissionsfromdate);
        } else {
            $this->assertEquals($oldavailablefrom, $assignment->get_instance()->allowsubmissionsfromdate);
        }

        $gradingduedate = helper::set_time($newduedate, '16:00', "+{$config->gradingdueinterval} week");
        $this->assertEquals($gradingduedate, $assignment->get_instance()->gradingduedate);
        if ($newassign['reattempt'] == 0) {
            $cutoffdate = helper::set_time($newduedate, '16:00', "+{$config->cutoffinterval} week");
            // Does an exam have a completion due setting?
            if ($newsitsassign->is_exam()) {
                $cutoffdate = $newduedate;
            }
            $this->assertEquals($cutoffdate, $assignment->get_instance()->cutoffdate);
            $this->assertEquals(1, $cm->visible);
            $this->assertEquals(2, $cm->completion);
        } else {
            $cutoffdate = helper::set_time($newduedate, '16:00', "+{$config->cutoffintervalsecondplus} week");
            $this->assertEquals($cutoffdate, $assignment->get_instance()->cutoffdate);
            $this->assertEquals(0, $cm->visible);
            $this->assertEquals(0, $cm->completion);
        }

        $events = $DB->get_records('event', ['courseid' => $course2->id, 'instance' => $cm->instance]);
        foreach ($events as $event) {
            if ($event->eventtype == \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED) {
                $this->assertEquals($newduedate, $event->timestart);
                $this->assertEquals(get_string('completionexpectedfor', 'completion', (object)['instancename' => $newassign['title']]),
                        $event->name);
            }
            if ($event->eventtype == ASSIGN_EVENT_TYPE_DUE) {
                $this->assertEquals($newduedate, $event->timestart);
                $this->assertEquals(get_string('calendardue', 'assign', $newassign['title']), $event->name);
            }
        }

        // Check it's in section 1.
        $this->assertEquals($config->targetsection, $cm->sectionnum);
        // Check which grade scale is being used.
        if ($newsitsassign->get('grademarkexempt')) {
            $this->assertEquals($assignment->get_instance()->grade, $config->grademarkexemptscale * -1);
        } else {
            $this->assertEquals($assignment->get_instance()->grade, $config->grademarkscale * -1);
        }
    }

    /**
     * Update assignment provider
     *
     * @return array
     */
    public function update_assignment_provider(): array {
        return [
            'new_duedate' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+2 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ],
            'deleted_course_after_old_assign' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+2 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'coursedeleted' => true
            ],
            // In this scenario, the scheduled task will create the assignment.
            'no_duedate-valid_duedate' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => 0,
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ],
            'availablefrom_past-availablefrom_future' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+3 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => strtotime('-1 week')
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+3 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => strtotime('+1 week')
                ],
                'coursedeleted' => false
            ],
            'grademark_exempt-no_grademarkexempt' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ],
            'exam_cutoffdate-new_duedate' => [
                'oldassign' => [
                    'sitsref' => 'EXAM_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0
                ],
                'newassign' => [
                    'sitsref' => 'EXAM_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+2 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ],
            'new_weighting' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (50%)',
                    'weighting' => 50,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0
                ],
                'coursedeleted' => false
            ]
        ];
    }

    /**
     * Settings required to create an assignment
     *
     * @return void
     */
    private function set_settings() {
        // Create Solent Grademark scales.
        $solentscale = $this->getDataGenerator()->create_scale([
            'name' => 'Solent',
            'scale' => 'N, S, F3, F2, F1, D3, D2, D1, C3, C2, C1, B3, B2, B1, A4, A3, A2, A1'
        ]);
        set_config('grademarkscale', $solentscale->id, 'local_solsits');
        // Create Solent numeric scales.
        $solentnumeric = $this->getDataGenerator()->create_scale([
            'name' => 'Solent numeric',
            'scale' => '0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, ' .
                    '21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, ' .
                    '41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, ' .
                    '61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, ' .
                    '81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100'
        ]);
        set_config('grademarkexemptscale', $solentnumeric->id, 'local_solsits');
    }
}
