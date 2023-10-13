@local @local_solsits @sol @javascript
Feature: Guidance message is displayed if assignment is on the wrong course
  In order to explain an assignment is on the wrong course
  As a teacher
  I should see some guidance on a Summative assignment page

  Background:
    Given the following "courses" exist:
    | fullname                | shortname             | category | idnumber              |
    | Quercus course (ABC101) | ABC101_123456789      | 0        | ABC101_123456789      |
    | SITS course (ABC101)    | ABC101_A_SEM1_2023/24 | 0        | ABC101_A_SEM1_2023/24 |
    | SITS Course (ABC101)    | ABC101_A_SEM1_2024/25 | 0        | ABC101_A_SEM1_2024/25 |
    And the following "users" exist:
    | username      | firstname    | lastname | email                |
    | teacher1      | Teacher      | 1        | teacher1@example.com |
    | student1      | Student      | 1        | student1@example.com |
    And the following "course enrolments" exist:
    | user          | course                | role           |
    | teacher1      | ABC101_123456789      | editingteacher |
    | student1      | ABC101_123456789      | student        |
    | teacher1      | ABC101_A_SEM1_2023/24 | editingteacher |
    | student1      | ABC101_A_SEM1_2023/24 | student        |
    | teacher1      | ABC101_A_SEM1_2024/25 | editingteacher |
    | student1      | ABC101_A_SEM1_2024/25 | student        |
    And I log in as "admin"
    And the following config values are set as admin:
    | config | value  | plugin |
    | theme  | solent |        |

  Scenario: View Quercus assignment on SITS course error message
    Given the following "activities" exist:
      | activity | name           | course                | idnumber   |
      | assign   | Quercus        | ABC101_A_SEM1_2023/24 | PROJ_2022  |
    When I am on the "PROJ_2022" Activity page logged in as teacher1
    Then I should see "The Quercus assignment (PROJ_2022) should not be on the Gateway module (ABC101_A_SEM1_2023/24)"
    When I am on the "PROJ_2022" Activity page logged in as student1
    Then I should not see "The Quercus assignment (PROJ_2022) should not be on the Gateway module (ABC101_A_SEM1_2023/24)"

  Scenario: View wrong SITS assignment on SITS course error message
    Given the following "activities" exist:
      | activity | name           | course                | idnumber                             |
      | assign   | SITS1          | ABC101_A_SEM1_2024/25 | ABC101_A_SEM1_2023/24_ABC10101_001_0 |
    When I am on the "ABC101_A_SEM1_2023/24_ABC10101_001_0" Activity page logged in as teacher1
    Then I should see "This assignment (ABC101_A_SEM1_2023/24_ABC10101_001_0) should not be on ABC101_A_SEM1_2024/25"
    When I am on the "ABC101_A_SEM1_2023/24_ABC10101_001_0" Activity page logged in as student1
    Then I should not see "This assignment (ABC101_A_SEM1_2023/24_ABC10101_001_0) should not be on ABC101_A_SEM1_2024/25"

  Scenario: No error is displayed for the correct SITS assignment on the correct SITS course
    Given the following "activities" exist:
      | activity | name           | course                | idnumber                             |
      | assign   | SITS1          | ABC101_A_SEM1_2023/24 | ABC101_A_SEM1_2023/24_ABC10101_001_0 |
    And the following SITS assignment exists:
      | sitsref         | ABC101_A_SEM1_2023/24_ABC10101_001_0 |
      | course          | ABC101_A_SEM1_2023/24                |
      | title           | SITS1                                |
      | weighting       | 50                                   |
      | duedate         | ## 5 May 2023 16:00:00 ##            |
      | assessmentcode  | ABC10101                             |
      | assessmentname  | Project 1                            |
      | sequence        | 001                                  |
      | availablefrom   | 0                                    |
      | reattempt       | 0                                    |
      | grademarkexempt | 0                                    |
    When I am on the "ABC101_A_SEM1_2023/24_ABC10101_001_0" Activity page logged in as teacher1
    Then I should not see "This assignment (ABC101_A_SEM1_2023/24_ABC10101_001_0) should not be on ABC101_A_SEM1_2023/24"
    When I am on the "ABC101_A_SEM1_2023/24_ABC10101_001_0" Activity page logged in as student1
    Then I should not see "This assignment (ABC101_A_SEM1_2023/24_ABC10101_001_0) should not be on ABC101_A_SEM1_2023/24"
