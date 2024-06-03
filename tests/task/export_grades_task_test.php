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
 * Export grades task
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\task;

use advanced_testcase;
use context_module;
use local_solsits\ais_client;
use local_solsits\generator;
use local_solsits\helper;
use mod_assign_test_generator;
use mod_assign_testable_assign;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
require_once($CFG->dirroot . '/local/solsits/tests/generator.php');
require_once($CFG->dirroot . '/local/solsits/tests/task/task_trait.php');

/**
 * Export grades task test
 * @group sol
 */
class export_grades_task_test extends advanced_testcase {
    use generator;
    use mod_assign_test_generator;
    use task_trait;
    /**
     * Test executing the task
     *
     * @param array $module
     * @param array $assignment
     * @param array $unitleader
     * @param array $grades
     * @param array $response
     *
     * @covers \local_solsits\task\export_grades_task\execute
     * @dataProvider task_execute_provider
     * @return void
     */
    public function test_task_execute($module, $assignment, $unitleader, $grades, $response): void {
        global $DB;
        $this->resetAfterTest();
        /** @var local_solsits_generator $dg */
        $dg = $this->getDataGenerator()->get_plugin_generator('local_solsits');
        $dg->create_solent_gradescales();
        set_config('default', 1, 'assignfeedback_misconduct');
        set_config('ais_exportgrades_url', 'https://example.com', 'local_solsits');
        set_config('ais_exportgrades_endpoint', '/api/Results/upload', 'local_solsits');
        set_config('ais_exportgrades_key', 'RANDOM##1234', 'local_solsits');
        $config = get_config('local_solsits');
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course($module);
        $unitleaderuser = $this->getDataGenerator()->create_user($unitleader);
        $this->getDataGenerator()->enrol_user($unitleaderuser->id, $course->id, 'editingteacher');
        $sitsassign = $dg->create_sits_assign([
            'sitsref' => $assignment['sitsref'],
            'courseid' => $course->id,
            'reattempt' => $assignment['reattempt'],
            'title' => $assignment['assignmenttitle'],
            'duedate' => $assignment['duedate'],
            'assessmentcode' => $assignment['assessmentcode'],
            'assessmentname' => $assignment['assessmentname'],
        ]);
        $sitsassign->create_assignment();
        $cm = get_coursemodule_from_id('assign', $sitsassign->get('cmid'), $course->id);
        $context = context_module::instance($cm->id);
        $assign = new mod_assign_testable_assign($context, $cm, $course);
        $students = [];
        $assigngrades = [];
        $response['grades'] = [];

        $expectedoutput = '';
        if (count($grades) == 0) {
            $expectedoutput = "No grades to export to SITS\n";
        } else {
            $expectedoutput = "Begin Marks upload {$module['idnumber']} and assignment {$assignment['sitsref']}\n" .
                "Request took 0 seconds\n";
        }
        if ($response['status'] == 'FAILED') {
            $expectedoutput .= "- FAILED ({$response['errorcode']}). {$response['message']}\n";
        }
        if ($response['sitsref'] == '') {
            $expectedoutput .= "Sitsref not returned in result. Presumed timeout.\n";
        }

        foreach ($grades as $grade) {
            $student = $this->getDataGenerator()->create_user([
                'firstname' => $grade['firstname'],
                'lastname' => $grade['lastname'],
                'idnumber' => $grade['studentidnumber'],
            ]);
            $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
            $idnumber = $grade['studentidnumber'];
            $students[$idnumber] = $student;
            $assigngrades[$idnumber]['grade'] = $grade['grade'];
            $assigngrades[$idnumber]['feedbackcomments'] = "Comment for {$idnumber}. " . $this->getDataGenerator()->loremipsum;
            $assigngrades[$idnumber]['feedbackmisconduct'] = $grade['misconduct'];
            $response['grades'][] = [
                'message' => $grade['response']['message'],
                'response' => $grade['response']['response'],
                'moodlestudentid' => $student->id,
            ];
            if ($response['status'] == 'SUCCESS') {
                if ($grade['response']['response'] == 'FAILED') {
                    $expectedoutput .= "- {$grade['firstname']} {$grade['lastname']} ({$idnumber}): " .
                        "{$grade['response']['response']} - {$grade['response']['message']}\n";
                } else {
                    $expectedoutput .= "- {$grade['firstname']} {$grade['lastname']} ({$idnumber}): " .
                        "{$grade['response']['response']}\n";
                }
            }
        }

        $this->mark_assignments($students, $assigngrades, $assign, $unitleaderuser, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED);

        $this->setAdminUser();
        $insertedgrades = [];
        foreach ($students as $studentidnumber => $student) {
            $insertedgrades[$studentidnumber] = $dg->create_assign_grade([
                'solassignmentid' => $sitsassign->get('id'),
                'graderid' => $unitleaderuser->id,
                'studentid' => $student->id,
                'converted_grade' => helper::convert_grade($config->grademarkscale, $assigngrades[$studentidnumber]['grade']),
            ]);
        }

        if (count($grades) > 0) {
            $expectedoutput .= "End Marks upload {$module['idnumber']} and assignment {$assignment['sitsref']}\n";
        }

        $this->expectOutputString($expectedoutput);

        $testclient = new ais_client([], $config->ais_exportgrades_url, $config->ais_exportgrades_key);
        $testclient->mock_response(json_encode($response));
        $mocktask = $this->getMockBuilder(\local_solsits\task\export_grades_task::class)
            ->onlyMethods(['get_client'])
            ->getMock();
        // When the task is being tested, we substitute the internal ais_client for the one we've created here with the
        // test response data. No real connections are made.
        $mocktask->expects($this->any())
            ->method('get_client')
            ->willReturn($testclient);
        $mocktask->execute();
        $gradesintable = $DB->get_records('local_solsits_assign_grades');
        $this->assertCount(count($grades), $gradesintable);
        // Check the status in the response has been saved.
        foreach ($response['grades'] as $gradeitem) {
            $grade = $sitsassign->get_grade($gradeitem['moodlestudentid']);
            if (empty($response['sitsref'])) {
                $this->assertEquals(ais_client::TIMEOUT, $grade->response);
                $this->assertEquals(get_string('muptimeoutmessage', 'local_solsits'), $grade->message);
            } else {
                $this->assertEquals($gradeitem['message'], $grade->message);
                $this->assertEquals($gradeitem['response'], $grade->response);
            }
        }
    }

    /**
     * Task execute test provider
     *
     * @return array
     */
    public static function task_execute_provider(): array {
        return [
            'no grades sent' => [
                'module' => [
                    'shortname' => 'ABC101_A_SEM1_2023/24',
                    'idnumber' => 'ABC101_A_SEM1_2023/24',
                    'fullname' => 'Making Widgets',
                    'customfield_academic_year' => '2023/24',
                    'customfield_module_code' => 'ABC101',
                    'customfield_template_applied' => 1,
                    'startdate' => strtotime('21/09/2023 00:00:00'),
                    'enddate' => strtotime('20/12/2023 23:59:59'),
                ],
                'assignment' => [
                    'sitsref' => 'ABC101_A_SEM1_2022/23_ABC10102_001_0_0_1',
                    'assessmentname' => 'Tweak a widget',
                    'assignmenttitle' => 'Tweak a widget 100%',
                    'duedate' => strtotime('10/12/2023 16:00:00'),
                    'reattempt' => '0',
                    'sequence' => '001',
                    'assessmentcode' => 'ABC10102',
                ],
                'unitleader' => [
                    'firstname' => 'Teacher',
                    'lastname' => 'Test',
                    'email' => 'teacher.test@example.com',
                ],
                'grades' => [],
                'response' => [
                    'sitsref' => 'ABC101_A_SEM1_2022/23_ABC10102_001_0_0_1',
                    'status' => 'SUCCESS',
                    'message' => '',
                    'errorcode' => '',
                ],
            ],
            'module not in sits' => [
                'module' => [
                    'shortname' => 'ABC102_A_SEM1_2023/24',
                    'idnumber' => 'ABC102_A_SEM1_2023/24',
                    'fullname' => 'Making Widgets',
                    'customfield_academic_year' => '2023/24',
                    'customfield_module_code' => 'ABC102',
                    'customfield_template_applied' => 1,
                    'startdate' => strtotime('21/09/2023 00:00:00'),
                    'enddate' => strtotime('20/12/2023 23:59:59'),
                ],
                'assignment' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'assessmentname' => 'Tweak a widget',
                    'assignmenttitle' => 'Tweak a widget 100%',
                    'duedate' => strtotime('10/12/2023 16:00:00'),
                    'reattempt' => '0',
                    'sequence' => '001',
                    'assessmentcode' => 'ABC10202',
                ],
                'unitleader' => [
                    'firstname' => 'Teacher',
                    'lastname' => 'Test',
                    'email' => 'teacher.test@example.com',
                ],
                'grades' => [
                    [
                        'firstname' => 'Stuart',
                        'lastname' => 'Dent',
                        'studentidnumber' => '19999999',
                        'grade' => 17,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Module not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Steve',
                        'lastname' => 'Jobs',
                        'studentidnumber' => '19999998',
                        'grade' => 18,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Module not found in SITS',
                        ],
                    ],
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'status' => 'FAILED',
                    'message' => 'Module not found in SITS',
                    'errorcode' => '1',
                ],
            ],
            'module instance not in sits' => [
                'module' => [
                    'shortname' => 'ABC102_A_SEM1_2023/24',
                    'idnumber' => 'ABC102_A_SEM1_2023/24',
                    'fullname' => 'Making Widgets',
                    'customfield_academic_year' => '2023/24',
                    'customfield_module_code' => 'ABC102',
                    'customfield_template_applied' => 1,
                    'startdate' => strtotime('21/09/2023 00:00:00'),
                    'enddate' => strtotime('20/12/2023 23:59:59'),
                ],
                'assignment' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'assessmentname' => 'Tweak a widget',
                    'assignmenttitle' => 'Tweak a widget 100%',
                    'duedate' => strtotime('10/12/2023 16:00:00'),
                    'reattempt' => '0',
                    'sequence' => '001',
                    'assessmentcode' => 'ABC10202',
                ],
                'unitleader' => [
                    'firstname' => 'Teacher',
                    'lastname' => 'Test',
                    'email' => 'teacher.test@example.com',
                ],
                'grades' => [
                    [
                        'firstname' => 'Stuart',
                        'lastname' => 'Dent',
                        'studentidnumber' => '19999999',
                        'grade' => 1,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Module instance not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Steve',
                        'lastname' => 'Jobs',
                        'studentidnumber' => '19999998',
                        'grade' => 2,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Module instance not found in SITS',
                        ],
                    ],
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'status' => 'FAILED',
                    'message' => 'Module instance not found in SITS',
                    'errorcode' => '2',
                ],
            ],
            'assessment not found in sits' => [
                'module' => [
                    'shortname' => 'ABC102_A_SEM1_2023/24',
                    'idnumber' => 'ABC102_A_SEM1_2023/24',
                    'fullname' => 'Making Widgets',
                    'customfield_academic_year' => '2023/24',
                    'customfield_module_code' => 'ABC102',
                    'customfield_template_applied' => 1,
                    'startdate' => strtotime('21/09/2023 00:00:00'),
                    'enddate' => strtotime('20/12/2023 23:59:59'),
                ],
                'assignment' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'assessmentname' => 'Tweak a widget',
                    'assignmenttitle' => 'Tweak a widget 100%',
                    'duedate' => strtotime('10/12/2023 16:00:00'),
                    'reattempt' => '0',
                    'sequence' => '001',
                    'assessmentcode' => 'ABC10202',
                ],
                'unitleader' => [
                    'firstname' => 'Teacher',
                    'lastname' => 'Test',
                    'email' => 'teacher.test@example.com',
                ],
                'grades' => [
                    [
                        'firstname' => 'Stuart',
                        'lastname' => 'Dent',
                        'studentidnumber' => '19999999',
                        'grade' => 3,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Assessment not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Steve',
                        'lastname' => 'Jobs',
                        'studentidnumber' => '19999998',
                        'grade' => 4,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Assessment not found in SITS',
                        ],
                    ],
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'status' => 'FAILED',
                    'message' => 'Assessment not found in SITS',
                    'errorcode' => '3',
                ],
            ],
            'Module assessment link not found in SITS' => [
                'module' => [
                    'shortname' => 'ABC102_A_SEM1_2023/24',
                    'idnumber' => 'ABC102_A_SEM1_2023/24',
                    'fullname' => 'Making Widgets',
                    'customfield_academic_year' => '2023/24',
                    'customfield_module_code' => 'ABC102',
                    'customfield_template_applied' => 1,
                    'startdate' => strtotime('21/09/2023 00:00:00'),
                    'enddate' => strtotime('20/12/2023 23:59:59'),
                ],
                'assignment' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'assessmentname' => 'Tweak a widget',
                    'assignmenttitle' => 'Tweak a widget 100%',
                    'duedate' => strtotime('10/12/2023 16:00:00'),
                    'reattempt' => '0',
                    'sequence' => '001',
                    'assessmentcode' => 'ABC10202',
                ],
                'unitleader' => [
                    'firstname' => 'Teacher',
                    'lastname' => 'Test',
                    'email' => 'teacher.test@example.com',
                ],
                'grades' => [
                    [
                        'firstname' => 'Stuart',
                        'lastname' => 'Dent',
                        'studentidnumber' => '19999999',
                        'grade' => 5,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Module assessment link not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Steve',
                        'lastname' => 'Jobs',
                        'studentidnumber' => '19999998',
                        'grade' => 6,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Module assessment link not found in SITS',
                        ],
                    ],
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'status' => 'FAILED',
                    'message' => 'Module assessment link not found in SITS',
                    'errorcode' => '4',
                ],
            ],
            'Student and assessment validation failures' => [
                'module' => [
                    'shortname' => 'ABC102_A_SEM1_2023/24',
                    'idnumber' => 'ABC102_A_SEM1_2023/24',
                    'fullname' => 'Making Widgets',
                    'customfield_academic_year' => '2023/24',
                    'customfield_module_code' => 'ABC102',
                    'customfield_template_applied' => 1,
                    'startdate' => strtotime('21/09/2023 00:00:00'),
                    'enddate' => strtotime('20/12/2023 23:59:59'),
                ],
                'assignment' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'assessmentname' => 'Tweak a widget',
                    'assignmenttitle' => 'Tweak a widget 100%',
                    'duedate' => strtotime('10/12/2023 16:00:00'),
                    'reattempt' => '0',
                    'sequence' => '001',
                    'assessmentcode' => 'ABC10202',
                ],
                'unitleader' => [
                    'firstname' => 'Teacher',
                    'lastname' => 'Test',
                    'email' => 'teacher.test@example.com',
                ],
                'grades' => [
                    [
                        'firstname' => 'Stuart',
                        'lastname' => 'Dent',
                        'studentidnumber' => '99000057',
                        'grade' => 7,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student code not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Steve',
                        'lastname' => 'Jobs',
                        'studentidnumber' => '99000058',
                        'grade' => 8,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student pathway route not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Raginhild',
                        'lastname' => 'Xu',
                        'studentidnumber' => '99000059',
                        'grade' => 9,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student module link not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Tara',
                        'lastname' => 'Sneijers',
                        'studentidnumber' => '99000060',
                        'grade' => null,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Invalid assessment result entered',
                        ],
                    ],
                    [
                        'firstname' => 'Maia',
                        'lastname' => 'Singh',
                        'studentidnumber' => '99000061',
                        'grade' => -1,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Invalid assessment result entered',
                        ],
                    ],
                    [
                        'firstname' => 'Amadi',
                        'lastname' => 'Muhammad',
                        'studentidnumber' => '99000062',
                        'grade' => 10,
                        'misconduct' => 1,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student assessment record not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Lark',
                        'lastname' => 'Adamić',
                        'studentidnumber' => '99000063',
                        'grade' => 11,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student assessment record already has a mark in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Noe',
                        'lastname' => 'Naoumov',
                        'studentidnumber' => '99000064',
                        'grade' => 12,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Error uploading the assessment result',
                        ],
                    ],
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'status' => 'SUCCESS',
                    'message' => '',
                    'errorcode' => '',
                ],
            ],
            'Student and reassessment validation failures' => [
                'module' => [
                    'shortname' => 'ABC102_A_SEM1_2023/24',
                    'idnumber' => 'ABC102_A_SEM1_2023/24',
                    'fullname' => 'Making Widgets',
                    'customfield_academic_year' => '2023/24',
                    'customfield_module_code' => 'ABC102',
                    'customfield_template_applied' => 1,
                    'startdate' => strtotime('21/09/2023 00:00:00'),
                    'enddate' => strtotime('20/12/2023 23:59:59'),
                ],
                'assignment' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_002_0_0_1',
                    'assessmentname' => 'Tweak a widget',
                    'assignmenttitle' => 'Tweak a widget 100%',
                    'duedate' => strtotime('10/12/2023 16:00:00'),
                    'reattempt' => '1',
                    'sequence' => '002',
                    'assessmentcode' => 'ABC10202',
                ],
                'unitleader' => [
                    'firstname' => 'Teacher',
                    'lastname' => 'Test',
                    'email' => 'teacher.test@example.com',
                ],
                'grades' => [
                    [
                        'firstname' => 'Amadi',
                        'lastname' => 'Muhammad',
                        'studentidnumber' => '99000062',
                        'grade' => 13,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student re-assessment record not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Lark',
                        'lastname' => 'Adamić',
                        'studentidnumber' => '99000063',
                        'grade' => 14,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student re-assessment record already has a mark in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Noe',
                        'lastname' => 'Naoumov',
                        'studentidnumber' => '99000064',
                        'grade' => 15,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Error uploading the re-assessment result',
                        ],
                    ],
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_002_0_0_1',
                    'status' => 'SUCCESS',
                    'message' => '',
                    'errorcode' => '',
                ],
            ],
            'Student and assessment mixed response' => [
                'module' => [
                    'shortname' => 'ABC102_A_SEM1_2023/24',
                    'idnumber' => 'ABC102_A_SEM1_2023/24',
                    'fullname' => 'Making Widgets',
                    'customfield_academic_year' => '2023/24',
                    'customfield_module_code' => 'ABC102',
                    'customfield_template_applied' => 1,
                    'startdate' => strtotime('21/09/2023 00:00:00'),
                    'enddate' => strtotime('20/12/2023 23:59:59'),
                ],
                'assignment' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'assessmentname' => 'Tweak a widget',
                    'assignmenttitle' => 'Tweak a widget 100%',
                    'duedate' => strtotime('10/12/2023 16:00:00'),
                    'reattempt' => '0',
                    'sequence' => '001',
                    'assessmentcode' => 'ABC10202',
                ],
                'unitleader' => [
                    'firstname' => 'Teacher',
                    'lastname' => 'Test',
                    'email' => 'teacher.test@example.com',
                ],
                'grades' => [
                    [
                        'firstname' => 'Stuart',
                        'lastname' => 'Dent',
                        'studentidnumber' => '99000057',
                        'grade' => 7,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'SUCCESS',
                            'message' => '',
                        ],
                    ],
                    [
                        'firstname' => 'Steve',
                        'lastname' => 'Jobs',
                        'studentidnumber' => '99000058',
                        'grade' => 8,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student pathway route not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Raginhild',
                        'lastname' => 'Xu',
                        'studentidnumber' => '99000059',
                        'grade' => 9,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'SUCCESS',
                            'message' => '',
                        ],
                    ],
                    [
                        'firstname' => 'Tara',
                        'lastname' => 'Sneijers',
                        'studentidnumber' => '99000060',
                        'grade' => null,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Invalid assessment result entered',
                        ],
                    ],
                    [
                        'firstname' => 'Maia',
                        'lastname' => 'Singh',
                        'studentidnumber' => '99000061',
                        'grade' => -1,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Invalid assessment result entered',
                        ],
                    ],
                    [
                        'firstname' => 'Amadi',
                        'lastname' => 'Muhammad',
                        'studentidnumber' => '99000062',
                        'grade' => 10,
                        'misconduct' => 1,
                        'response' => [
                            'response' => 'SUCCESS',
                            'message' => '',
                        ],
                    ],
                    [
                        'firstname' => 'Lark',
                        'lastname' => 'Adamić',
                        'studentidnumber' => '99000063',
                        'grade' => 11,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'SUCCESS',
                            'message' => '',
                        ],
                    ],
                    [
                        'firstname' => 'Noe',
                        'lastname' => 'Naoumov',
                        'studentidnumber' => '99000064',
                        'grade' => 12,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'SUCCESS',
                            'message' => '',
                        ],
                    ],
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'status' => 'SUCCESS',
                    'message' => '',
                    'errorcode' => '',
                ],
            ],
            'MUP timeout' => [
                'module' => [
                    'shortname' => 'ABC102_A_SEM1_2023/24',
                    'idnumber' => 'ABC102_A_SEM1_2023/24',
                    'fullname' => 'Making Widgets',
                    'customfield_academic_year' => '2023/24',
                    'customfield_module_code' => 'ABC102',
                    'customfield_template_applied' => 1,
                    'startdate' => strtotime('21/09/2023 00:00:00'),
                    'enddate' => strtotime('20/12/2023 23:59:59'),
                ],
                'assignment' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_002_0_0_1',
                    'assessmentname' => 'Tweak a widget',
                    'assignmenttitle' => 'Tweak a widget 100%',
                    'duedate' => strtotime('10/12/2023 16:00:00'),
                    'reattempt' => '1',
                    'sequence' => '002',
                    'assessmentcode' => 'ABC10202',
                ],
                'unitleader' => [
                    'firstname' => 'Teacher',
                    'lastname' => 'Test',
                    'email' => 'teacher.test@example.com',
                ],
                'grades' => [
                    [
                        'firstname' => 'Amadi',
                        'lastname' => 'Muhammad',
                        'studentidnumber' => '99000062',
                        'grade' => 13,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student re-assessment record not found in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Lark',
                        'lastname' => 'Adamić',
                        'studentidnumber' => '99000063',
                        'grade' => 14,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Student re-assessment record already has a mark in SITS',
                        ],
                    ],
                    [
                        'firstname' => 'Noe',
                        'lastname' => 'Naoumov',
                        'studentidnumber' => '99000064',
                        'grade' => 15,
                        'misconduct' => 0,
                        'response' => [
                            'response' => 'FAILED',
                            'message' => 'Error uploading the re-assessment result',
                        ],
                    ],
                ],
                'response' => [
                    'sitsref' => '',
                    'status' => '',
                    'message' => '',
                    'errorcode' => '',
                ],
            ],
        ];
    }
}
