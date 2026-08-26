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

namespace local_solsits;

/**
 * Tests for SOL-SITS Integration
 *
 * @package    local_solsits
 * @category   test
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_solsits\observers
 */
final class observers_test extends \advanced_testcase {
    /**
     * Default interval settings for assignments.
     * @var array $defaultsettings
     */
    private $defaultsettings = [
        'cutoffinterval' => (2 * WEEKSECS),
        'cutoffintervalsecondplus' => (1 * WEEKSECS),
        'gradingdueinterval' => (4 * WEEKSECS),
        'cutoffintervalblock' => (2 * DAYSECS),
        'cutoffintervalsecondplusblock' => (1 * DAYSECS),
        'gradingdueintervalblock' => (3 * WEEKSECS),
        'maxassignments' => 1,
        'limittoyears' => '',
    ];

    /**
     * Update course observer to check assignment dates are updated
     *
     * @covers ::course_updated
     * @dataProvider course_updated_event_provider
     * @param array $originalfields Original course fields.
     * @param array $updatedfields Updated course fields.
     * @param array $assign Assignment fields.
     * @return void
     */
    public function test_course_updated_event(array $originalfields, array $updatedfields, array $assign): void {
        // Must be admin, or have capabilities, to create assignments and update hidden custom fields.
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->set_settings();
        /** @var \local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        // Create a course.
        $courseparams = array_merge([
            'idnumber' => 'AAP502_A_SEM1_2023/24',
            'shortname' => 'AAP502_A_SEM1_2023/24',
            'fullname' => 'CGI Production (AAP502)',
            'customfield_templateapplied' => 1,
        ], $originalfields);

        $course = $this->getDataGenerator()->create_course($courseparams);

        $assign = array_merge([
            'sitsref' => 'AAP502_A_SEM1_2023/24_AAP50201_001_' . $assign['reattempt'],
            'title' => 'CGI Production - Portfolio 1 (50%)',
            'weighting' => '50',
            'duedate' => strtotime('+2 weeks 16:00'),
            'grademarkexempt' => false,
            'availablefrom' => 0,
            'reattempt' => 0,
            'assessmentcode' => 'AAP50201',
            'assessmentname' => 'Project 1',
            'sequence' => '001',
            'courseid' => $course->id,
        ], $assign);

        // Create assignments.
        $sitsassign = $ssdg->create_sits_assign($assign);
        $sitsassign->create_assignment();
        $assign['cmid'] = $sitsassign->get('cmid');

        // Update the course, this will trigger the event we're observing.
        update_course((object)array_merge(['id' => $course->id], $updatedfields));

        $deliverymode = helper::get_deliverymode($course->id);

        // Check the assignment dates.
        $cm = get_coursemodule_from_id('assign', $sitsassign->get('cmid'), $course->id);
        $context = \core\context\module::instance($cm->id);
        $modassign = new \assign($context, $cm, $course);

        $modcutoff = $modassign->get_instance()->cutoffdate;
        $modgradingdue = $modassign->get_instance()->gradingduedate;

        $gradingdueinterval = helper::calculate_interval($deliverymode, 'gradingdue', $assign['reattempt']);
        $cutoffinterval = helper::calculate_interval($deliverymode, 'cutoff', $assign['reattempt']);

        $expectedcutoff = $assign['duedate'] + $cutoffinterval;
        $expectedgradingdue = $assign['duedate'] + $gradingdueinterval;

        $this->assertEquals($expectedcutoff, $modcutoff);
        $this->assertEquals($expectedgradingdue, $modgradingdue);
    }

    /**
     * Data provider for test_course_updated_event.
     *
     * @return array
     */
    public static function course_updated_event_provider(): array {
        return [
            'deliverymode unchanged' => [
                'originalfields' => ['customfield_deliverymode' => 'S'],
                'updatedfields' => ['customfield_deliverymode' => 'S'],
                'assign' => ['reattempt' => 0],
            ],
            'deliverymode changed' => [
                'originalfields' => ['customfield_deliverymode' => 'S'],
                'updatedfields' => ['customfield_deliverymode' => 'B'],
                'assign' => ['reattempt' => 0],
            ],
            'other field updated' => [
                'originalfields' => ['customfield_org_2' => 'ARTMUS'],
                'updatedfields' => ['customfield_org_2' => 'SMUS'],
                'assign' => ['reattempt' => 0],
            ],
            'no updated fields' => [
                'originalfields' => ['customfield_deliverymode' => 'S'],
                'updatedfields' => [],
                'assign' => ['reattempt' => 0],
            ],
            'no original fields' => [
                // Default will be 'S' if not set.
                'originalfields' => [],
                'updatedfields' => ['customfield_deliverymode' => 'B'],
                'assign' => ['reattempt' => 0],
            ],
            'deliverymode unchanged reattempt' => [
                'originalfields' => ['customfield_deliverymode' => 'S'],
                'updatedfields' => ['customfield_deliverymode' => 'S'],
                'assign' => ['reattempt' => 1],
            ],
            'deliverymode changed reattempt' => [
                'originalfields' => ['customfield_deliverymode' => 'S'],
                'updatedfields' => ['customfield_deliverymode' => 'B'],
                'assign' => ['reattempt' => 1],
            ],
            'other field updated reattempt' => [
                'originalfields' => ['customfield_org_2' => 'ARTMUS'],
                'updatedfields' => ['customfield_org_2' => 'SMUS'],
                'assign' => ['reattempt' => 1],
            ],
            'no updated fields reattempt' => [
                'originalfields' => ['customfield_deliverymode' => 'S'],
                'updatedfields' => [],
                'assign' => ['reattempt' => 1],
            ],
            'no original fields reattempt' => [
                // Default will be 'S' if not set.
                'originalfields' => [],
                'updatedfields' => ['customfield_deliverymode' => 'B'],
                'assign' => ['reattempt' => 1],
            ],
        ];
    }

    /**
     * The case where the assignment either hasn't been created yet, or has been deleted when the course is updated.
     *
     * @return void
     */
    public function test_sitsassign_assignment_not_exists(): void {
        // Must be admin, or have capabilities, to create assignments and update hidden custom fields.
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->set_settings();
        /** @var \local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        // Create a course.
        $courseparams = [
            'idnumber' => 'AAP502_A_SEM1_2023/24',
            'shortname' => 'AAP502_A_SEM1_2023/24',
            'fullname' => 'CGI Production (AAP502)',
            'customfield_templateapplied' => 1,
        ];

        $course = $this->getDataGenerator()->create_course($courseparams);

        $assign = [
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
            'courseid' => $course->id,
        ];

        // Create sitsassign record.
        $sitsassign = $ssdg->create_sits_assign($assign);

        // Update the course, this will trigger the event we're observing.
        update_course((object)array_merge(['id' => $course->id], []));
        $cm = get_coursemodule_from_id('assign', $sitsassign->get('cmid'), $course->id);
        $this->assertFalse($cm);

        $sitsassign->create_assignment();

        update_course((object)array_merge(['id' => $course->id], []));
        $cm = get_coursemodule_from_id('assign', $sitsassign->get('cmid'), $course->id);
        $this->assertNotFalse($cm);

        course_delete_module($cm->id);
        // Update the course, this will trigger the event we're observing.
        update_course((object)array_merge(['id' => $course->id], []));
        $cm = get_coursemodule_from_id('assign', $sitsassign->get('cmid'), $course->id);
        $this->assertFalse($cm);
    }

    /**
     * Settings required to create an assignment
     *
     * @return void
     */
    private function set_settings() {
        /** @var \local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $ssdg->create_solent_gradescales();
        foreach ($this->defaultsettings as $key => $value) {
            set_config($key, $value, 'local_solsits');
        }
    }
}
