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
 * AIS client
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use advanced_testcase;

/**
 * AIS client test
 * @group sol
 */
class ais_client_test extends advanced_testcase {
    /**
     * Test export_grades function
     *
     * @dataProvider export_grades_provider
     * @covers \local_solsits\ais_client\export_grades
     *
     * @param array $export List of grades to be exported
     * @param array $response Expected response
     * @return void
     */
    public function test_export_grades($export, $response) {
        $this->resetAfterTest();
        set_config('ais_exportgrades_url', 'https://example.com/moodle', 'local_solsits');
        set_config('ais_exportgrades_endpoint', '/api/Results/upload', 'local_solsits');
        set_config('ais_exportgrades_key', 'RANDOM##1234', 'local_solsits');
        $config = get_config('local_solsits');
        $ais = new ais_client([], $config->ais_exportgrades_url, $config->ais_exportgrades_key);
        $ais->mock_response(json_encode($response));
        $mockedresponse = $ais->export_grades($export);
        if (!$mockedresponse) {
            $this->assertFalse($response);
        }
        $mockedresponse = json_decode($mockedresponse);
        $this->assertEquals($response['sitsref'], $mockedresponse->sitsref);
        $this->assertEquals($response['status'], $mockedresponse->status);
        $this->assertEquals($response['message'], $mockedresponse->message);
        $this->assertEquals($response['errorcode'], $mockedresponse->errorcode);
        $this->assertCount(count($export['grades']), $mockedresponse->grades);
        if (count($export['grades']) == 0) {
            // No grades to check, so all done.
            return;
        }
        $responses = $response['grades'];
        $mockedgrades = $mockedresponse->grades;
        foreach ($mockedgrades as $mockedgrade) {
            foreach ($responses as $response) {
                if ($response['moodlestudentid'] == $mockedgrade->moodlestudentid) {
                    $this->assertEquals($response['response'], $mockedgrade->response);
                    $this->assertEquals($response['message'], $mockedgrade->message);
                    continue;
                }
            }
        }
    }

    /**
     * Provider for test_export_grades
     *
     * @return array Returns the data exported, and the expected response for that data.
     */
    public function export_grades_provider(): array {
        return [
            'no grades sent' => [
                'export' => [
                    'module' => [
                        'modulecode' => 'ABC101',
                        'moduleinstanceid' => 'ABC101_A_SEM1_2023/24',
                        'moduletitle' => 'Making Widgets',
                        'modulestartdate' => '21/09/2023 00:00:00',
                        'moduleenddate' => '20/12/2023 23:59:59',
                        'academic_year' => '2023/24'
                    ],
                    'assignment' => [
                        'sitsref' => 'ABC101_A_SEM1_2022/23_ABC10102_001_0_0_1',
                        'assignmenttitle' => 'Tweak a widget',
                        'duedate' => '10/12/2023 16:00:00',
                        'assignid' => '1',
                        'reattempt' => '0',
                        'sequence' => '001'
                    ],
                    'unitleader' => [
                        'firstname' => 'Teacher',
                        'lastname' => 'Test',
                        'email' => 'teacher.test@example.com'
                    ],
                    'grades' => []
                ],
                'response' => [
                    'sitsref' => 'ABC101_A_SEM1_2022/23_ABC10102_001_0_0_1',
                    'grades' => [],
                    'status' => 'SUCCESS',
                    'message' => '',
                    'errorcode' => ''
                ]
            ],
            'module not in sits' => [
                'export' => [
                    'module' => [
                        'modulecode' => 'ABC102',
                        'moduleinstanceid' => 'ABC102_A_SEM1_2023/24',
                        'moduletitle' => 'Making Widgets',
                        'modulestartdate' => '21/09/2023 00:00:00',
                        'moduleenddate' => '20/12/2023 23:59:59',
                        'academic_year' => '2023/24'
                    ],
                    'assignment' => [
                        'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                        'assignmenttitle' => 'Tweak a widget',
                        'duedate' => '10/12/2023 16:00:00',
                        'assignid' => '1',
                        'reattempt' => '0',
                        'sequence' => '001'
                    ],
                    'unitleader' => [
                        'firstname' => 'Teacher',
                        'lastname' => 'Test',
                        'email' => 'teacher.test@example.com'
                    ],
                    'grades' => [
                        [
                            'firstname' => 'Stuart',
                            'lastname' => 'Dent',
                            'studentidnumber' => '19999999',
                            'moodlestudentid' => '57',
                            'result' => 68,
                            'submissiontime' => '09/12/2023 13:45:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Steve',
                            'lastname' => 'Jobs',
                            'studentidnumber' => '19999998',
                            'moodlestudentid' => '58',
                            'result' => 99,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ]
                    ]
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'grades' => [
                        [
                            'moodlestudentid' => '57',
                            'response' => 'FAILED',
                            'message' => 'Module not found in SITS',
                        ],
                        [
                            'moodlestudentid' => '58',
                            'response' => 'FAILED',
                            'message' => 'Module not found in SITS',
                        ]
                    ],
                    'status' => 'FAILED',
                    'message' => 'Module not found in SITS',
                    'errorcode' => '1'
                ]
            ],
            'module instance not in sits' => [
                'export' => [
                    'module' => [
                        'modulecode' => 'ABC102',
                        'moduleinstanceid' => 'ABC102_A_SEM1_2023/24',
                        'moduletitle' => 'Making Widgets',
                        'modulestartdate' => '21/09/2023 00:00:00',
                        'moduleenddate' => '20/12/2023 23:59:59',
                        'academic_year' => '2023/24'
                    ],
                    'assignment' => [
                        'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                        'assignmenttitle' => 'Tweak a widget',
                        'duedate' => '10/12/2023 16:00:00',
                        'assignid' => '1',
                        'reattempt' => '0',
                        'sequence' => '001'
                    ],
                    'unitleader' => [
                        'firstname' => 'Teacher',
                        'lastname' => 'Test',
                        'email' => 'teacher.test@example.com'
                    ],
                    'grades' => [
                        [
                            'firstname' => 'Stuart',
                            'lastname' => 'Dent',
                            'studentidnumber' => '19999999',
                            'moodlestudentid' => '57',
                            'result' => 68,
                            'submissiontime' => '09/12/2023 13:45:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Steve',
                            'lastname' => 'Jobs',
                            'studentidnumber' => '19999998',
                            'moodlestudentid' => '58',
                            'result' => 99,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ]
                    ]
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'grades' => [
                        [
                            'moodlestudentid' => '57',
                            'response' => 'FAILED',
                            'message' => 'Module instance not found in SITS',
                        ],
                        [
                            'moodlestudentid' => '58',
                            'response' => 'FAILED',
                            'message' => 'Module instance not found in SITS',
                        ]
                    ],
                    'status' => 'FAILED',
                    'message' => 'Module instance not found in SITS',
                    'errorcode' => '2'
                ]
            ],
            'assessment not found in sits' => [
                'export' => [
                    'module' => [
                        'modulecode' => 'ABC102',
                        'moduleinstanceid' => 'ABC102_A_SEM1_2023/24',
                        'moduletitle' => 'Making Widgets',
                        'modulestartdate' => '21/09/2023 00:00:00',
                        'moduleenddate' => '20/12/2023 23:59:59',
                        'academic_year' => '2023/24'
                    ],
                    'assignment' => [
                        'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                        'assignmenttitle' => 'Tweak a widget',
                        'duedate' => '10/12/2023 16:00:00',
                        'assignid' => '1',
                        'reattempt' => '0',
                        'sequence' => '001'
                    ],
                    'unitleader' => [
                        'firstname' => 'Teacher',
                        'lastname' => 'Test',
                        'email' => 'teacher.test@example.com'
                    ],
                    'grades' => [
                        [
                            'firstname' => 'Stuart',
                            'lastname' => 'Dent',
                            'studentidnumber' => '19999999',
                            'moodlestudentid' => '57',
                            'result' => 68,
                            'submissiontime' => '09/12/2023 13:45:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Steve',
                            'lastname' => 'Jobs',
                            'studentidnumber' => '19999998',
                            'moodlestudentid' => '58',
                            'result' => 99,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ]
                    ]
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'grades' => [
                        [
                            'moodlestudentid' => '57',
                            'response' => 'FAILED',
                            'message' => 'Assessment not found in SITS',
                        ],
                        [
                            'moodlestudentid' => '58',
                            'response' => 'FAILED',
                            'message' => 'Assessment not found in SITS',
                        ]
                    ],
                    'status' => 'FAILED',
                    'message' => 'Assessment not found in SITS',
                    'errorcode' => '3'
                ]
            ],
            'Module assessment link not found in SITS' => [
                'export' => [
                    'module' => [
                        'modulecode' => 'ABC102',
                        'moduleinstanceid' => 'ABC102_A_SEM1_2023/24',
                        'moduletitle' => 'Making Widgets',
                        'modulestartdate' => '21/09/2023 00:00:00',
                        'moduleenddate' => '20/12/2023 23:59:59',
                        'academic_year' => '2023/24'
                    ],
                    'assignment' => [
                        'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                        'assignmenttitle' => 'Tweak a widget',
                        'duedate' => '10/12/2023 16:00:00',
                        'assignid' => '1',
                        'reattempt' => '0',
                        'sequence' => '001'
                    ],
                    'unitleader' => [
                        'firstname' => 'Teacher',
                        'lastname' => 'Test',
                        'email' => 'teacher.test@example.com'
                    ],
                    'grades' => [
                        [
                            'firstname' => 'Stuart',
                            'lastname' => 'Dent',
                            'studentidnumber' => '19999999',
                            'moodlestudentid' => '57',
                            'result' => 68,
                            'submissiontime' => '09/12/2023 13:45:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Steve',
                            'lastname' => 'Jobs',
                            'studentidnumber' => '19999998',
                            'moodlestudentid' => '58',
                            'result' => 99,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ]
                    ]
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'grades' => [
                        [
                            'moodlestudentid' => '57',
                            'response' => 'FAILED',
                            'message' => 'Module assessment link not found in SITS',
                        ],
                        [
                            'moodlestudentid' => '58',
                            'response' => 'FAILED',
                            'message' => 'Module assessment link not found in SITS',
                        ]
                    ],
                    'status' => 'FAILED',
                    'message' => 'Module assessment link not found in SITS',
                    'errorcode' => '4'
                ]
            ],
            'Student and assessment validation failures' => [
                'export' => [
                    'module' => [
                        'modulecode' => 'ABC102',
                        'moduleinstanceid' => 'ABC102_A_SEM1_2023/24',
                        'moduletitle' => 'Making Widgets',
                        'modulestartdate' => '21/09/2023 00:00:00',
                        'moduleenddate' => '20/12/2023 23:59:59',
                        'academic_year' => '2023/24'
                    ],
                    'assignment' => [
                        'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                        'assignmenttitle' => 'Tweak a widget',
                        'duedate' => '10/12/2023 16:00:00',
                        'assignid' => '1',
                        'reattempt' => '0',
                        'sequence' => '001'
                    ],
                    'unitleader' => [
                        'firstname' => 'Teacher',
                        'lastname' => 'Test',
                        'email' => 'teacher.test@example.com'
                    ],
                    'grades' => [
                        [
                            'firstname' => 'Stuart',
                            'lastname' => 'Dent',
                            'studentidnumber' => '99000057',
                            'moodlestudentid' => '57',
                            'result' => 68,
                            'submissiontime' => '09/12/2023 13:45:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Steve',
                            'lastname' => 'Jobs',
                            'studentidnumber' => '99000058',
                            'moodlestudentid' => '58',
                            'result' => 99,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Raginhild',
                            'lastname' => 'Xu',
                            'studentidnumber' => '99000059',
                            'moodlestudentid' => '59',
                            'result' => 99,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Tara',
                            'lastname' => 'Sneijers',
                            'studentidnumber' => '99000060',
                            'moodlestudentid' => '60',
                            'result' => null,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Maia',
                            'lastname' => 'Singh',
                            'studentidnumber' => '99000061',
                            'moodlestudentid' => '61',
                            'result' => -1,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Amadi',
                            'lastname' => 'Muhammad',
                            'studentidnumber' => '99000062',
                            'moodlestudentid' => '62',
                            'result' => 80,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Lark',
                            'lastname' => 'Adamić',
                            'studentidnumber' => '99000063',
                            'moodlestudentid' => '63',
                            'result' => 80,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Noe',
                            'lastname' => 'Naoumov',
                            'studentidnumber' => '99000063',
                            'moodlestudentid' => '64',
                            'result' => 80,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ]
                    ]
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_001_0_0_1',
                    'grades' => [
                        [
                            'moodlestudentid' => '57',
                            'response' => 'FAILED',
                            'message' => 'Student code not found in SITS',
                        ],
                        [
                            'moodlestudentid' => '58',
                            'response' => 'FAILED',
                            'message' => 'Student pathway route not found in SITS',
                        ],
                        [
                            'moodlestudentid' => '59',
                            'response' => 'FAILED',
                            'message' => 'Student module link not found in SITS',
                        ],
                        [
                            'moodlestudentid' => '60',
                            'response' => 'FAILED',
                            'message' => 'Invalid assessment result entered',
                        ],
                        [
                            'moodlestudentid' => '61',
                            'response' => 'FAILED',
                            'message' => 'Invalid assessment result entered',
                        ],
                        [
                            'moodlestudentid' => '62',
                            'response' => 'FAILED',
                            'message' => 'Student assessment record not found in SITS',
                        ],
                        [
                            'moodlestudentid' => '63',
                            'response' => 'FAILED',
                            'message' => 'Student assessment record already has a mark in SITS',
                        ],
                        [
                            'moodlestudentid' => '64',
                            'response' => 'FAILED',
                            'message' => 'Error uploading the assessment result',
                        ]
                    ],
                    'status' => 'SUCCESS',
                    'message' => '',
                    'errorcode' => ''
                ]
            ],
            'Student and reassessment validation failures' => [
                'export' => [
                    'module' => [
                        'modulecode' => 'ABC102',
                        'moduleinstanceid' => 'ABC102_A_SEM1_2023/24',
                        'moduletitle' => 'Making Widgets',
                        'modulestartdate' => '21/09/2023 00:00:00',
                        'moduleenddate' => '20/12/2023 23:59:59',
                        'academic_year' => '2023/24'
                    ],
                    'assignment' => [
                        'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_002_0_0_1',
                        'assignmenttitle' => 'Tweak a widget',
                        'duedate' => '10/12/2023 16:00:00',
                        'assignid' => '1',
                        'reattempt' => '0',
                        'sequence' => '002'
                    ],
                    'unitleader' => [
                        'firstname' => 'Teacher',
                        'lastname' => 'Test',
                        'email' => 'teacher.test@example.com'
                    ],
                    'grades' => [
                        [
                            'firstname' => 'Amadi',
                            'lastname' => 'Muhammad',
                            'studentidnumber' => '99000062',
                            'moodlestudentid' => '62',
                            'result' => 80,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Lark',
                            'lastname' => 'Adamić',
                            'studentidnumber' => '99000063',
                            'moodlestudentid' => '63',
                            'result' => 80,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ],
                        [
                            'firstname' => 'Noe',
                            'lastname' => 'Naoumov',
                            'studentidnumber' => '99000063',
                            'moodlestudentid' => '64',
                            'result' => 80,
                            'submissiontime' => '10/12/2023 11:25:12',
                            'misconduct' => 'No'
                        ]
                    ]
                ],
                'response' => [
                    'sitsref' => 'ABC102_A_SEM1_2022/23_ABC10202_002_0_0_1',
                    'grades' => [
                        [
                            'moodlestudentid' => '62',
                            'response' => 'FAILED',
                            'message' => 'Student re-assessment record not found in SITS',
                        ],
                        [
                            'moodlestudentid' => '63',
                            'response' => 'FAILED',
                            'message' => 'Student re-assessment record already has a mark in SITS',
                        ],
                        [
                            'moodlestudentid' => '64',
                            'response' => 'FAILED',
                            'message' => 'Error uploading the re-assessment result',
                        ]
                    ],
                    'status' => 'SUCCESS',
                    'message' => '',
                    'errorcode' => ''
                ]
            ]
        ];
    }
}
