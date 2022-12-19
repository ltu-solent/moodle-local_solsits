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
}
