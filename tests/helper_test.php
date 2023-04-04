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
use context_system;
use core_customfield\category;
use core_customfield\field;
use local_solsits;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Test local_solsits helper functions.
 */
class helper_test extends advanced_testcase {
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
            'contextid' => context_system::instance()->id
        ]);
        $this->assertEquals('Student Records System', $category->get('name'));
        $this->assertEquals(
            'Fields managed by the university\'s Student records system. Do not change unless asked to.',
            $category->get('description')
        );
        $field = field::get_record([
            'shortname' => 'academic_year',
            'categoryid' => $category->get('id')
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
            'categoryid' => $category->get('id')
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
            'idnumber' => 'SITS_1'
        ]);
        $quercus = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'quercus',
            'idnumber' => 'QUERCUS_1'
        ]);
        $formative = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'formative',
            'idnumber' => ''
        ]);
        $sitscm = get_coursemodule_from_instance('assign', $sits->id);
        $quercuscm = get_coursemodule_from_instance('assign', $quercus->id);
        $formativecm = get_coursemodule_from_instance('assign', $formative->id);
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
            'idnumber' => $idnumber
        ]);

        $cm = get_coursemodule_from_instance('assign', $assign->id);
        if ($sitsassign) {
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
            'scale' => 'N, S, F3, F2, F1, D3, D2, D1, C3, C2, C1, B3, B2, B1, A4, A3, A2, A1'
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
                    '81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100'
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
    public function get_scales_menu_dataprovider() {
        return [
            'sits' => [
                'name' => 'Assign 1',
                'idnumber' => 'SITS1',
                'sitsassign' => true
            ],
            'quercus' => [
                'name' => 'Assign 1',
                'idnumber' => 'QUERCUS1',
                'sitsassign' => false
            ],
            'formative' => [
                'name' => 'Assign 1',
                'idnumber' => '',
                'sitsassign' => false
            ]
        ];
    }
}
