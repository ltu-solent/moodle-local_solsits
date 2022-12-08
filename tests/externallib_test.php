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
 */
class externallib_test extends externallib_advanced_testcase {

    /**
     * Test register sitscourse
     *
     * @dataProvider register_sitscourse_provider
     * @covers \local_solsits\externallib::register_sitscourse
     * @return void
     */
    public function test_register_sitscourse($code, $occurrence, $period, $session, $pagetype, $error, $nocourse) {
        $this->resetAfterTest();
        $wsuser = $this->getDataGenerator()->create_user();
        $systemcontext = context_system::instance();
        $this->setUser($wsuser);
        $wsroleid = $this->assignUserCapability('local/solsits:registersitscourse', $systemcontext->id);
        set_role_contextlevels($wsroleid, [CONTEXT_SYSTEM]);
        $idnumber = 'empty';
        if ($pagetype == 'module') {
            $idnumber = implode('_', [$code, $occurrence, $period, $session]);
        } else if ($pagetype == 'course') {
            $idnumber = $code;
        }

        if ($nocourse) {
            // Some made up id.
            $course = new stdClass();
            $course->id = 999499999;
        } else {
            $course = $this->getDataGenerator()->create_course([
                'shortname' => $idnumber,
                'idnumber' => $idnumber
            ]);
        }
        $registerthis = [
            'courseid' => $course->id,
            'pagetype' => $pagetype,
            'session' => $session
        ];
        try {
            $result = \local_solsits_external::register_sitscourses([$registerthis]);
            $this->assertEquals($course->id, $result[0]['courseid']);
            $this->assertEquals($session, $result[0]['session']);
            $this->assertEquals($pagetype, $result[0]['pagetype']);
        } catch (Exception $ex) {
            $this->assertTrue($ex instanceof $error['exception']['class']);
            $this->assertEquals($error['exception']['message'], $ex->getMessage());
        }

        // Call without required capability.
        $this->unassignUserCapability('local/solsits:registersitscourse', $systemcontext->id, $wsroleid);
        $this->expectException(\required_capability_exception::class);
        \local_solsits_external::register_sitscourses([$registerthis]);
    }

    /**
     * Provider method for register_sitscourse
     *
     * @return array
     */
    public function register_sitscourse_provider() {
        return [
            'module' => [
                'code' => 'ABC101',
                'occurrence' => 'A',
                'period' => 'S1',
                'session' => '2022/23',
                'pagetype' => 'module',
                'error' => false,
                'nocourse' => false,
            ],
            'course' => [
                'code' => 'MSCPSY',
                'occurrence' => '',
                'period' => '',
                'session' => '2022/23',
                'pagetype' => 'course',
                'error' => false,
                'nocourse' => false,
            ],
            'bad_pagetype' => [
                'code' => 'ABC101',
                'occurrence' => 'A',
                'period' => 'S1',
                'session' => '2022/23',
                'pagetype' => 'gobule',
                'error' => [
                    'exception' => [
                        'class' => invalid_parameter_exception::class,
                        'message' => 'Invalid parameter value detected (Invalid pagetype: gobule)'
                    ]
                ],
                'nocourse' => false
            ],
            'bad_session' => [
                'code' => 'ABC101',
                'occurrence' => 'A',
                'period' => 'S1',
                'session' => '2022/2023',
                'pagetype' => 'module',
                'error' => [
                    'exception' => [
                        'class' => invalid_persistent_exception::class,
                        'message' => 'Error: Invalid session (session: Invalid session)'
                    ]
                ],
                'nocourse' => false
            ],
            'no_course' => [
                'code' => 'ABC101',
                'occurrence' => 'A',
                'period' => 'S1',
                'session' => '2022/23',
                'pagetype' => 'module',
                'error' => [
                    'exception' => [
                        'class' => invalid_parameter_exception::class,
                        'message' => 'Invalid parameter value detected (Course specified doesn\'t exist: 999499999)'
                    ]
                ],
                'nocourse' => true
            ]
        ];
    }
}
