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
 * Externallib webservices test
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use context_system;
use core\invalid_persistent_exception;
use Exception;
use externallib_advanced_testcase;
use invalid_parameter_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/solsits/externallib.php');

/**
 * Test externallib functions
 * @group sol
 */
class externallib_test extends externallib_advanced_testcase {
    /**
     * Add assignments
     * @covers \local_solsits_external::add_assignments
     *
     * @dataProvider add_assignments_provider
     * @param string $sitsref
     * @param string $title
     * @param string $weighting Expressed as decimal
     * @param string $assessmentcode
     * @param int $duedate
     * @param bool $grademarkexempt
     * @param int $availablefrom
     * @return void
     */
    public function test_add_assignments($sitsref, $title, $weighting, $assessmentcode, $duedate, $grademarkexempt,
        $availablefrom) {
        $this->resetAfterTest();
    }

    /**
     * Provider from add_assignments
     *
     * @return array
     */
    public function add_assignments_provider() {
        return [
            'example1' => [
                'sitsref' => 'PROJ1_ABC101_A_S1_2023/2024',
                'title' => 'Project 1 (50%)',
                'weighting' => '.5',
                'assessmentcode' => 'PROJ1',
                'duedate' => strtotime('+2 weeks 16:00'),
                'grademarkexempt' => false,
                'availablefrom' => 0
            ]
        ];
    }
}
