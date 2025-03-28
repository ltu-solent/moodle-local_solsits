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
use core\context;
use local_solsits\task\task_trait;
use mod_assign_test_generator;
use mod_assign_testable_assign;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
require_once($CFG->dirroot . '/local/solsits/tests/generator.php');
require_once($CFG->dirroot . '/local/solsits/tests/task/task_trait.php');
require_once($CFG->dirroot . '/filter/shortcodes/lib/helpers.php');

/**
 * Test sitsassign persistent class
 * @covers \local_solsits\sitsassign
 * @group sol
 */
final class sitsassign_test extends advanced_testcase {
    use generator;
    use mod_assign_test_generator;
    use task_trait;
    /**
     * Test getting a list of assignments that can now be created
     *
     * @covers \local_solsits\sitsassign::get_create_list
     * @return void
     */
    public function test_get_create_list(): void {
        $this->resetAfterTest();
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $tcourse = $this->getDataGenerator()->create_course([
            'fullname' => 'Template applied',
            'shortname' => 'templated',
            'idnumber' => 'templated',
        ]);
        $ntcourse = $this->getDataGenerator()->create_course([
            'fullname' => 'Template NOT applied',
            'shortname' => 'nottemplated',
            'idnumber' => 'nottemplated',
        ]);
        $sits = $this->getDataGenerator()->create_module('assign', [
            'course' => $tcourse->id,
            'name' => 'sits',
            'idnumber' => 'SITS_1',
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
    public function test_create_assignment($sitsassign, $coursedeleted = false): void {
        $this->resetAfterTest();
        $this->set_settings();
        $this->setTimezone('UTC');
        $config = get_config('local_solsits');
        // Perhaps change this is to a WS user with permissions to "Manage activities".
        $this->setAdminUser();
        /** @var local_solsits_generator $ssdg */
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
        $context = context\module::instance($cm->id);
        $assignment = new assign($context, $cm, $course);
        $this->assertEquals($duedate, $assignment->get_instance()->duedate);
        $this->assertEquals($availablefrom, $assignment->get_instance()->allowsubmissionsfromdate);
        if ($sitsassign['reattempt'] == 0) {
            $cutoffdate = helper::set_time($duedate, '16:00', "+{$config->cutoffinterval} week");
            if ($assign->is_exam()) {
                $cutoffdate = $duedate;
            }
            $gradingduedate = helper::set_time($duedate, '16:00', "+{$config->gradingdueinterval} week");
            $this->assertEquals($cutoffdate, $assignment->get_instance()->cutoffdate);
            $this->assertEquals($gradingduedate, $assignment->get_instance()->gradingduedate);
            $this->assertEquals(1, $cm->visible);
            $this->assertEquals(2, $cm->completion);
            $this->assertEquals('[assignmentintro note="Do not remove"]', $assignment->get_instance()->intro);
        } else {
            $cutoffdate = helper::set_time($duedate, '16:00', "+{$config->cutoffintervalsecondplus} week");
            $gradingduedate = helper::set_time($duedate, '16:00', "+{$config->gradingdueintervalsecondplus} week");
            $this->assertEquals($cutoffdate, $assignment->get_instance()->cutoffdate);
            $this->assertEquals($gradingduedate, $assignment->get_instance()->gradingduedate);
            $this->assertEquals(0, $cm->visible);
            $this->assertEquals(0, $cm->completion);
            $reattemptmessage = get_config('local_solsits', 'assignmentmessage_studentreattempt');
            $env = (object)[
                'context' => $context,
            ];
            $expected = \local_solsits\local\shortcodes::assignmentintro(null, null, null, $env, null);
            $filter = new \filter_shortcodes\text_filter($context, []);
            $actual = $filter->filter($assignment->get_instance()->intro);
            $this->assertEquals($expected, $actual);
            $this->assertStringContainsString($reattemptmessage, $actual);
        }
        $this->assertEquals(0, $cm->completionexpected);
        // Check it's in section 1.
        $this->assertEquals($config->targetsection, $cm->sectionnum);
        // Check which grade scale is being used.
        // How to switch between using the grademark scales and the numeric gradescales?
        if ($assign->get('grademarkexempt')) {
            // The grade field in the assignment table is either a positive number representing a point
            // grade, or a negative number representing the id of the scale being used.
            // If the scale id is 3, the grade field will be -3. The formula below converts 3 to -3.
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
    public static function create_assignment_provider(): array {
        return [
            'valid_duedate' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
            'first_reattempt' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 1,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+4 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
            'deleted_course' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => true,
            ],
            'no_duedate' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => 0,
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
            'availablefrom' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+3 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => strtotime('+1 week'),
                ],
                'coursedeleted' => false,
            ],
            'grademark_exempt' => [
                'sitsassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
            'exam_cutoffdate' => [
                'sitsassign' => [
                    'sitsref' => 'EXAM_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
            'Long title' => [
                'sitsassign' => [
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
                'coursedeleted' => false,
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
    public function test_update_assignment($oldassign, $newassign, $coursedeleted): void {
        global $DB;
        $this->resetAfterTest();
        $this->set_settings();
        $this->setTimezone('UTC');
        $config = get_config('local_solsits');
        set_config('enablecompletion', COMPLETION_ENABLED);
        // Perhaps change this is to a WS user with permissions to "Manage activities".
        $this->setAdminUser();
        /** @var local_solsits_generator $ssdg */
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
        $oldsitsassign->create_assignment();

        if ($oldassign['duedate'] > 0) {
            // Change an assignment setting, to make sure it doesn't get overwritten when the update comes.
            $cmid = $oldsitsassign->get('cmid');
            [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'assign');
            $context = context\module::instance($cmid);
            $assignrecord = $DB->get_record('assign', ['id' => $cm->instance]);
            $assignrecord->markingallocation = 1;
            $DB->update_record('assign', $assignrecord);
        }

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
        $context = context\module::instance($cm->id);
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

        $this->assertEquals(1, $assignment->get_instance()->markingallocation);

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
                $this->assertEquals(
                    get_string('completionexpectedfor', 'completion', (object)['instancename' => $newassign['title']]),
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
    public static function update_assignment_provider(): array {
        return [
            'new_duedate' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+2 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
            'deleted_course_after_old_assign' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+2 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => true,
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
                    'availablefrom' => 0,
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
            'availablefrom_past-availablefrom_future' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+3 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => strtotime('-1 week'),
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+3 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => strtotime('+1 week'),
                ],
                'coursedeleted' => false,
            ],
            'grademark_exempt-no_grademarkexempt' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0,
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
            'exam_cutoffdate-new_duedate' => [
                'oldassign' => [
                    'sitsref' => 'EXAM_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0,
                ],
                'newassign' => [
                    'sitsref' => 'EXAM_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+2 week'),
                    'grademarkexempt' => true,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
            'new_weighting' => [
                'oldassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (100%)',
                    'weighting' => 100,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'newassign' => [
                    'sitsref' => 'PROJECT1_ABC101_2023/24',
                    'reattempt' => 0,
                    'title' => 'Project 1 (50%)',
                    'weighting' => 50,
                    'duedate' => strtotime('+1 week'),
                    'grademarkexempt' => false,
                    'availablefrom' => 0,
                ],
                'coursedeleted' => false,
            ],
        ];
    }

    /**
     * Get queued grades for export test.
     * Reads the local_solsits_assign_grades table for assignments that have not yet been exported.
     *
     * @return void
     */
    public function test_get_queued_grades_for_export(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_solent_gradescales();
        $config = get_config('local_solsits');
        set_config('default', 1, 'assignfeedback_misconduct');

        // The module needs SITS data as this is used in the grade export.
        $course = $this->getDataGenerator()->create_course([
            'shortname' => 'ABC101_A_S1_2022/23',
            'idnumber' => 'ABC101_A_S1_2022/23',
            'customfield_academic_year' => '2022/23',
            'customfield_module_code' => 'ABC101',
        ]);

        // Already exported assignment grades.
        $sitsassignexported = $dg->create_sits_assign([
            'sitsref' => "ABC101_A_S1_2022/23_ABC10101_001_0",
            'cmid' => 0,
            'courseid' => $course->id,
            'reattempt' => 0,
            'title' => "CGI Production - Portfolio 1 (50%)",
            'weighting' => 50,
            'duedate' => strtotime('+1 week'),
            'availablefrom' => 0,
            'grademarkexempt' => false,
            'scale' => 'grademark',
            'assessmentcode' => 'ABC10101',
            'assessmentname' => 'Portfolio 1',
            'sequence' => '001',
        ]);
        $sitsassignexported->create_assignment();
        $cm = get_coursemodule_from_id('assign', $sitsassignexported->get('cmid'), $course->id);
        $context = context\module::instance($cm->id);
        $assignexported = new mod_assign_testable_assign($context, $cm, $course);

        // This has not been exported and so will be picked up for export.
        $sitsassign = $dg->create_sits_assign([
            'sitsref' => 'ABC101_A_S1_2022/23_ABC10102_001_0_0_1',
            'cmid' => 0,
            'courseid' => $course->id,
            'reattempt' => 0,
            'title' => "CGI Production - Report 1 (50%)",
            'weighting' => 50,
            'duedate' => strtotime('+1 week'),
            'availablefrom' => 0,
            'grademarkexempt' => false,
            'scale' => 'grademark',
            'assessmentcode' => 'ABC10102',
            'assessmentname' => 'Report 1',
            'sequence' => '001',
        ]);
        $sitsassign->create_assignment();
        $cm = get_coursemodule_from_id('assign', $sitsassign->get('cmid'), $course->id);
        $context = context\module::instance($cm->id);
        $assign = new mod_assign_testable_assign($context, $cm, $course);

        $students = [];
        $grades = [];
        for ($x = 0; $x < 20; $x++) {
            // Student needs to have a numeric idnumber for grades to be uploaded to sits.
            $students[$x] = $this->getDataGenerator()->create_user(['idnumber' => '200000' . $x]);
            $this->getDataGenerator()->enrol_user($students[$x]->id, $course->id, 'student');
            // Mimic grademark scale with various values so we can test convert_grade.
            if ($x < 19) {
                $grades[$x]['grade'] = (float)$x;
                $this->add_submission($students[$x], $assign, 'My submission');
                $this->submit_for_grading($students[$x], $assign);
            } else {
                $grades[$x]['grade'] = 0;
            }
            $grades[$x]['feedbackcomments'] = "Comment for {$x}. " . $this->getDataGenerator()->loremipsum;
            $grades[$x]['feedbackmisconduct'] = random_int(0, 1);
        }
        $moduleleader = $this->getDataGenerator()->create_user([
            'firstname' => 'Module',
            'lastname' => 'Leader',
        ]);
        $this->getDataGenerator()->enrol_user($moduleleader->id, $course->id, 'editingteacher');
        $this->mark_assignments($students, $grades, $assign, $moduleleader, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED);
        $this->mark_assignments($students, $grades, $assignexported, $moduleleader, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED);
        $this->setAdminUser();
        $insertedgrades = [];
        for ($x = 0; $x < 20; $x++) {
            // Spoof already exported grades.
            $insertedgrades[] = $dg->create_assign_grade([
                'solassignmentid' => $sitsassignexported->get('id'),
                'graderid' => $moduleleader->id,
                'studentid' => $students[$x]->id,
                'converted_grade' => helper::convert_grade($config->grademarkscale, $grades[$x]['grade']),
                'message' => '',
                'response' => 'SUCCESS',
            ]);
        }
        // We have already exported some grades, and others are waiting to be queued, so the queue is currently empty.
        $waiting = sitsassign::get_retry_list();
        $this->assertCount(0, $waiting);
        // This is backup test to make sure there's nothing to export.
        $export = $sitsassignexported->get_queued_grades_for_export();
        $this->assertCount(0, $export['grades']);

        $expectedoutput = 'Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, ' .
        'Grader id: ' . $moduleleader->id . ', Grade: 0, Student idnumber: 2000000
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 0, Student idnumber: 2000001
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 55, Student idnumber: 20000010
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 58, Student idnumber: 20000011
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 62, Student idnumber: 20000012
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 65, Student idnumber: 20000013
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 68, Student idnumber: 20000014
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 74, Student idnumber: 20000015
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 83, Student idnumber: 20000016
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 92, Student idnumber: 20000017
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 100, Student idnumber: 20000018
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 0, Student idnumber: 20000019
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 1, Student idnumber: 2000002
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 15, Student idnumber: 2000003
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 20, Student idnumber: 2000004
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 35, Student idnumber: 2000005
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 42, Student idnumber: 2000006
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 45, Student idnumber: 2000007
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 48, Student idnumber: 2000008
Queued - Course: ABC101_A_S1_2022/23, Assignment code: ABC101_A_S1_2022/23_ABC10102_001_0_0_1, Grader id: ' . $moduleleader->id .
        ', Grade: 52, Student idnumber: 2000009
';
        $this->expectOutputString($expectedoutput);
        $this->execute_task('\local_solsits\task\get_new_grades_task');
        $waiting = sitsassign::get_retry_list();
        $this->assertCount(1, $waiting);
        $export = $sitsassign->get_queued_grades_for_export();
        $this->assertCount(20, $export['grades']);
        $queuedgrades = $DB->get_records('local_solsits_assign_grades', ['solassignmentid' => $sitsassign->get('id')]);
        $this->assertCount(20, $queuedgrades);
        $this->assertEquals($sitsassign->get('title'), $export['assignment']['assignmenttitle']);
        $this->assertEquals($sitsassign->get('sitsref'), $export['assignment']['sitsref']);
        $this->assertEquals($moduleleader->firstname, $export['unitleader']['firstname']);
        $this->assertEquals($moduleleader->lastname, $export['unitleader']['lastname']);
        foreach ($export['grades'] as $exportgrade) {
            $studentid = $exportgrade['moodlestudentid'];
            foreach ($queuedgrades as $queuedgrade) {
                if ($queuedgrade->studentid == $studentid) {
                    $x = substr($exportgrade['studentidnumber'], 6);
                    $misconductstring = get_string('no');
                    if ($grades[$x]['feedbackmisconduct']) {
                        $misconductstring = get_string('yes');
                    }
                    $this->assertEquals($exportgrade['result'], $queuedgrade->converted_grade);
                    $this->assertEquals($misconductstring, $exportgrade['misconduct']);
                    // Check time submitted.
                    if ($x > 18) {
                        $this->assertEquals(get_string('notsubmitted', 'local_solsits'), $exportgrade['submissiontime']);
                    } else {
                        $submission = $DB->get_record('assign_submission', [
                            'userid' => $students[$x]->id,
                            'assignment' => $assign->get_instance()->id,
                        ]);
                        $this->assertEquals(date('d/m/Y H:i:s', $submission->timemodified), $exportgrade['submissiontime']);
                    }
                }
            }
        }
    }

    /**
     * Get a list of assignments for exporting grades
     *
     * @return void
     */
    public function test_get_retry_list(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_solent_gradescales();
        $config = get_config('local_solsits');
        set_config('default', 1, 'assignfeedback_misconduct');

        // The module needs SITS data as this is used in the grade export.
        $course = $this->getDataGenerator()->create_course([
            'shortname' => 'ABC101_A_S1_2022/23',
            'idnumber' => 'ABC101_A_S1_2022/23',
            'customfield_academic_year' => '2022/23',
            'customfield_module_code' => 'ABC101',
        ]);

        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $students = [];
        for ($x = 0; $x < 5; $x++) {
            $students[$x] = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($students[$x]->id, $course->id, 'student');
        }

        $sitsassigns = [];
        for ($x = 0; $x < 5; $x++) {
            $sa = $dg->create_sits_assign([
                'courseid' => $course->id,
            ]);
            $sa->create_assignment();
            $sitsassigns[$x] = $sa;
            foreach ($students as $student) {
                $dg->create_assign_grade([
                    'solassignmentid' => $sa->get('id'),
                    'graderid' => $teacher->id,
                    'studentid' => $student->id,
                    'converted_grade' => (60 + $x),
                ]);
            }
        }
        $retrylist = sitsassign::get_retry_list(1);
        $this->assertCount(1, $retrylist);
        $retrylist = sitsassign::get_retry_list(5);
        $this->assertCount(5, $retrylist);
        $retrylist = sitsassign::get_retry_list(6);
        $this->assertCount(5, $retrylist);
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
