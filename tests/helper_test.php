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
 * Tests for helper functions
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use advanced_testcase;
use context_module;
use context_system;
use core_customfield\category;
use core_customfield\field;
use local_solsits;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/solsits/tests/generator.php');

/**
 * Test local_solsits helper functions.
 * @group sol
 */
class helper_test extends advanced_testcase {
    use generator;
    /**
     * Test setting up course custom fields that will be used by this plugin.
     * @covers \local_solsits\helper::create_sits_coursecustomfields
     * @return void
     */
    public function test_create_sits_coursecustomfields() {
        $this->resetAfterTest();
        // Test creating the category.
        helper::create_sits_coursecustomfields('academic_year');
        $category = category::get_record([
            'name' => 'Student Records System',
            'area' => 'course',
            'component' => 'core_course',
            'contextid' => context_system::instance()->id,
        ]);
        $this->assertEquals('Student Records System', $category->get('name'));
        $this->assertEquals(
            'Fields managed by the university\'s Student records system. Do not change unless asked to.',
            $category->get('description')
        );
        $field = field::get_record([
            'shortname' => 'academic_year',
            'categoryid' => $category->get('id'),
        ]);
        $this->assertEquals('academic_year', $field->get('shortname'));
        $this->assertEquals('Academic year', $field->get('name'));

        // Check no errors are thrown when doing the same things again. No accidental duplicates.
        helper::create_sits_coursecustomfields('academic_year');
        $fields = field::get_records(['shortname' => 'academic_year']);
        $this->assertCount(1, $fields);
        $field = reset($fields); // Get first record in array.
        $this->assertEquals($category->get('id'), $field->get('categoryid'));

        helper::create_sits_coursecustomfields('pagetype');
        $field = field::get_record([
            'shortname' => 'pagetype',
            'categoryid' => $category->get('id'),
        ]);
        $this->assertEquals('Page type', $field->get('name'));
    }

    /**
     * Is sits assignment
     * @covers \local_solsits\helper::is_sits_assignment
     *
     * @return void
     */
    public function test_is_sits_assignment() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $sits = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'sits',
            'idnumber' => 'SITS_1',
        ]);
        $quercus = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'quercus',
            'idnumber' => 'QUERCUS_1',
        ]);
        $formative = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'formative',
            'idnumber' => '',
        ]);
        $sitscm = get_coursemodule_from_instance('assign', $sits->id);
        $quercuscm = get_coursemodule_from_instance('assign', $quercus->id);
        $formativecm = get_coursemodule_from_instance('assign', $formative->id);
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_sits_assign(['cmid' => $sitscm->id, 'courseid' => $course->id]);
        $this->assertTrue(local_solsits\helper::is_sits_assignment($sitscm->id));
        $this->assertFalse(local_solsits\helper::is_sits_assignment($quercuscm->id));
        $this->assertFalse(local_solsits\helper::is_sits_assignment($formativecm->id));
    }

    /**
     * Get scales menu
     * @covers \local_solsits\helper::get_scales_menu
     * @dataProvider get_scales_menu_dataprovider
     * @param string $name Name of asssignment
     * @param string $idnumber IDNumber of assignment
     * @param bool $sitsassign Set this up as a SITS assignment?
     * @return void
     */
    public function test_get_scales_menu($name, $idnumber, $sitsassign) {
        global $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $defaultscales = get_scales_menu(($course->id));
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => $name,
            'idnumber' => $idnumber,
        ]);

        $cm = get_coursemodule_from_instance('assign', $assign->id);
        if ($sitsassign) {
            /** @var local_solsits_generator $dg */
            $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
            $dg->create_sits_assign(['cmid' => $cm->id, 'courseid' => $course->id]);
        }

        // Pretend I'm on a given page.
        $PAGE->set_cm($cm);
        $PAGE->set_url('/course/modedit.php', ['update' => $cm->id]);

        $scales = helper::get_scales_menu($course->id);
        $this->assertSame($defaultscales, $scales);

        // Create Solent Grademark scales.
        $solentscale = $this->getDataGenerator()->create_scale([
            'name' => 'Solent',
            'scale' => 'N, S, F3, F2, F1, D3, D2, D1, C3, C2, C1, B3, B2, B1, A4, A3, A2, A1',
        ]);
        set_config('grademarkscale', $solentscale->id, 'local_solsits');
        $defaultscales[$solentscale->id] = 'Solent';
        $scales = helper::get_scales_menu($course->id);
        if (helper::is_summative_assignment($cm->id)) {
            // Only the grademarkscale will be returned.
            $this->assertCount(1, $scales);
            $this->assertContains('Solent', $scales);
        } else {
            $this->assertSame($defaultscales, $scales);
        }
        // Create Solent numeric scales.
        $solentnumeric = $this->getDataGenerator()->create_scale([
            'name' => 'Solent numeric',
            'scale' => '0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, ' .
                    '21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, ' .
                    '41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, ' .
                    '61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, ' .
                    '81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100',
        ]);
        set_config('grademarkexemptscale', $solentnumeric->id, 'local_solsits');
        $defaultscales[$solentnumeric->id] = 'Solent numeric';
        $scales = helper::get_scales_menu($course->id);
        if (helper::is_summative_assignment($cm->id)) {
            // Only the grademarkscale and grademarkexempt scales will be returned.
            $this->assertCount(2, $scales);
            $this->assertContains('Solent', $scales);
            $this->assertContains('Solent numeric', $scales);
        } else {
            $this->assertSame($defaultscales, $scales);
        }
    }

    /**
     * Get scales menu data provider
     *
     * @return array
     */
    public static function get_scales_menu_dataprovider(): array {
        return [
            'sits' => [
                'name' => 'Assign 1',
                'idnumber' => 'SITS1',
                'sitsassign' => true,
            ],
            'quercus' => [
                'name' => 'Assign 1',
                'idnumber' => 'QUERCUS1',
                'sitsassign' => false,
            ],
            'formative' => [
                'name' => 'Assign 1',
                'idnumber' => '',
                'sitsassign' => false,
            ],
        ];
    }

    /**
     * Test converting grade for SITS
     *
     * @covers \local_solsits\helper::convert_grade
     * @return void
     */
    public function test_convert_grade() {
        $this->resetAfterTest();
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $ssdg->create_solent_gradescales();
        $scaleid = get_config('local_solsits', 'grademarkscale');
        $match = [-1, 0, 1, 15, 20, 35, 42, 45, 48, 52, 55, 58, 62, 65, 68, 74, 83, 92, 100];
        for ($x = 0; $x < 19; $x++) {
            $grade = helper::convert_grade($scaleid, $x);
            $this->assertEquals($match[$x], $grade);
        }
        $scaleid = get_config('local_solsits', 'grademarkexemptscale');
        $match = [null, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
                21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
                41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60,
                61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80,
                81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100,
            ];
        for ($x = 1; $x <= 100; $x++) {
            $grade = helper::convert_grade($scaleid, $x);
            $this->assertEquals($match[$x], $grade);
        }
        $x = null;
        $grade = helper::convert_grade($scaleid, $x);
        $this->assertEquals(0, $grade);
        $x = -1;
        $grade = helper::convert_grade($scaleid, $x);
        $this->assertEquals(0, $grade);
        $x = 0;
        $grade = helper::convert_grade($scaleid, $x);
        $this->assertEquals(0, $grade);
        $x = 70;
        // No valid solent scale, should just return the input.
        $grade = helper::convert_grade(0, $x);
        $this->assertEquals(70, $grade);
    }

    /**
     * Test assignments on wrong pages
     *
     * @dataProvider badassignalerts_dataprovider
     * @param array $coursedata Course
     * @param array $assigndata Assignment data
     * @param string $response
     * @param int $alertcount
     * @return void
     * @covers \local_solsits\helper::badassignalerts
     */
    public function test_badassignalerts($coursedata, $assigndata, $response, $alertcount) {
        $this->resetAfterTest();
        /** @var local_solsits_generator $ssdg */
        $ssdg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $ssdg->create_solent_gradescales();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course($coursedata);
        /** @var mod_assign_generator $assigngen */
        $assigngen = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        /** @var local_solsits_generator $sitsgen */
        $sitsgen = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        if ($assigndata['issits'] == true) {
            $assign = $sitsgen->create_sits_assign([
                'sitsref' => $assigndata['idnumber'],
                'courseid' => $course->id,
            ]);
            $assign->create_assignment();
            $cmid = $assign->get('cmid');
        } else {
            $assign = $assigngen->create_instance([
                'idnumber' => $assigndata['idnumber'],
                'course' => $course->id,
            ]);
            $cmid = $assign->cmid;
        }
        $context = context_module::instance($cmid);
        [$c, $cm] = get_course_and_cm_from_cmid($cmid, 'assign');

        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setUser($teacher);
        $alerts = helper::badassignalerts($cm, $c, $context);

        $expectedresponse = '';
        if ($response != '') {
            $expectedresponse = get_string($response, 'local_solsits', [
                'assignidnumber' => $cm->idnumber,
                'courseidnumber' => $course->idnumber,
            ]);
        }
        $this->assertCount($alertcount, $alerts);
        if (count($alerts) > 0) {
            $firstresponse = reset($alerts);
            $this->assertEquals($expectedresponse, $firstresponse->get_message());
        }

        $this->setUser($student);
        $alerts = helper::badassignalerts($cm, $c, $context);
        // Students shouldn't see these ever.
        $this->assertCount(0, $alerts);
    }

    /**
     * Data provider for test_badassignalerts
     * return array
     */
    public static function badassignalerts_dataprovider(): array {
        return [
            'quercus assignment on sits course' => [
                'coursedata' => [
                    'fullname' => 'SITS course (ABC101)',
                    'shortname' => 'ABC101_A_SEM1_2023/24',
                    'idnumber' => 'ABC101_A_SEM1_2023/24',
                ],
                'assigndata' => [
                    'issits' => false,
                    'idnumber' => 'PROJ1_2022',
                ],
                'response' => 'quercusassignmentonsitscourse',
                'alertcount' => 1,
            ],
            'sits assign on wrong sits course' => [
                'coursedata' => [
                    'fullname' => 'SITS course (ABC101)',
                    'shortname' => 'ABC101_A_SEM1_2024/25',
                    'idnumber' => 'ABC101_A_SEM1_2024/25',
                ],
                'assigndata' => [
                    'issits' => false,
                    'idnumber' => 'ABC101_A_SEM1_2023/24_ABC10101_001_0',
                ],
                'response' => 'wrongassignmentonwrongcourse',
                'alertcount' => 1,
            ],
            'quercus assignment on quercus course' => [
                'coursedata' => [
                    'fullname' => 'Quercus course (ABC101)',
                    'shortname' => 'ABC101_123456789',
                    'idnumber' => 'ABC101_123456789',
                ],
                'assigndata' => [
                    'issits' => false,
                    'idnumber' => 'PROJ1_2022',
                ],
                'response' => '',
                'alertcount' => 0,
            ],
            'sits assign on correct sits course' => [
                'coursedata' => [
                    'fullname' => 'SITS course (ABC101)',
                    'shortname' => 'ABC101_A_SEM1_2024/25',
                    'idnumber' => 'ABC101_A_SEM1_2024/25',
                ],
                'assigndata' => [
                    'issits' => true,
                    'idnumber' => 'ABC101_A_SEM1_2024/25_ABC10101_001_0',
                ],
                'response' => '',
                'alertcount' => 0,
            ],
        ];
    }
}
