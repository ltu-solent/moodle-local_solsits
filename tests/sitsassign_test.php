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

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Test sitsassign persistent class
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
        $ssdg->create_sits_assign(['courseid' => $ntcourse->id]);

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
}
