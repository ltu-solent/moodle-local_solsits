@local @local_solsits @sol @javascript
Feature: Guidance message is displayed to those who can view the grading page
  In order to explain the Summative assignment workflow
  As a teacher
  I should see some guidance on a Summative assignment page

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course1  | C1        | 0        |
    And the following "users" exist:
      | username      | firstname    | lastname | email                     |
      | moduleleader1 | Moduleleader | 1        | moduleleader1@example.com |
      | teacher1      | Teacher      | 1        | teacher1@example.com      |
      | student1      | Student      | 1        | student1@example.com      |
    And the following "roles" exist:
      | shortname    | name          | archetype      |
      | moduleleader | Module leader | editingteacher |
    And I log in as "admin"
    And I set the following system permissions of "Module leader" role:
      | capability                  | permission |
      | local/solsits:releasegrades | Allow      |
    And the following "course enrolments" exist:
      | user          | course | role           |
      | moduleleader1 | C1     | moduleleader   |
      | teacher1      | C1     | editingteacher |
      | student1      | C1     | student        |
    And the following config values are set as admin:
      | config | value  | plugin |
      | theme  | solent |        |
      | assignmentmessage_marksuploadinclude | <p>Grades for this assignment will be sent to {SRS} once they have been released to students in SOL.</p> | local_solsits |

  Scenario: View Quercus assignment grading message
    Given the following "activities" exist:
      | activity | name           | course | idnumber   | assignsubmission_onlinetext_enabled |
      | assign   | Quercus1       | C1     | Quercus1   | 1                                   |
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                   |
      | Quercus1              | student1  | I'm the student1 submission  |
    And I am on the "Quercus1" Activity page logged in as teacher1
    When I follow "View all submissions"
    Then I should see "Grades for this assignment will be sent to Quercus once they have been released to students in SOL."
    And I should not see "Grades for this assignment will be sent to Gateway (SITS)"

  Scenario: View SITS assignment grading message
    Given the following "activities" exist:
      | activity | name           | course | idnumber   | assignsubmission_onlinetext_enabled |
      | assign   | SITS1          | C1     | SITS1      | 1                                   |
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                   |
      | SITS1                 | student1  | I'm the student1 submission  |
    And the following sits assignment exists:
      | sitsref         | SITS1         |
      | course          | C1            |
      | title           | ASSIGN1       |
      | weighting       | 50            |
      | duedate         | ## 5 May 2023 16:00:00 ## |
      | assessmentcode  | PROJ1         |
      | assessmentname  | Project 1     |
      | sequence        | 001           |
      | availablefrom   | 0             |
      | reattempt       | 0             |
      | grademarkexempt | 0             |
    And I am on the "SITS1" Activity page logged in as teacher1
    When I follow "View all submissions"
    Then I should see "Grades for this assignment will be sent to Gateway (SITS) once they have been released to students in SOL."
    And I should not see "Grades for this assignment will be sent to Quercus"

  Scenario: No grading message on Formative assignment page
    Given the following "activities" exist:
      | activity | name           | course | idnumber   | assignsubmission_onlinetext_enabled |
      | assign   | Formative1     | C1     |            | 1                                   |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "Formative1"
    When I follow "View all submissions"
    Then I should not see "Grades for this assignment will be sent to Gateway (SITS)"
    And I should not see "Grades for this assignment will be sent to Quercus"

  @_alert
  Scenario: Reattempt warning is displayed on SITS reattempt assignments if they have not been released
    Given the following "activity" exists:
      | activity                            | assign                               |
      | name                                | CGI Production - Portfolio 1 (100%)  |
      | course                              | C1                                   |
      | idnumber                            | AAP601_A_SEM1_2023/24_ABC10101_001_0 |
      | assignsubmission_onlinetext_enabled | 1                                    |
      | markingworkflow                     | 1                                    |
    And the following "activity" exists:
      | activity                            | assign                                              |
      | name                                | CGI Production - Portfolio 1 (100%) First Reattempt |
      | course                              | C1                                                  |
      | idnumber                            | AAP601_A_SEM1_2023/24_ABC10101_001_1                |
      | assignsubmission_onlinetext_enabled | 1                                                   |
      | markingworkflow                     | 1                                                   |
    And the following "mod_assign > submissions" exist:
      | assign                               | user      | onlinetext                  |
      | AAP601_A_SEM1_2023/24_ABC10101_001_0 | student1  | I'm the student1 submission |
      | AAP601_A_SEM1_2023/24_ABC10101_001_1 | student1  | I'm the student1 submission |
    And the following sits assignment exists:
      | sitsref         | AAP601_A_SEM1_2023/24_ABC10101_001_0 |
      | course          | C1                                   |
      | title           | CGI Production - Portfolio 1 (100%)  |
      | weighting       | 100                                  |
      | duedate         | ## 5 May 2023 16:00:00 ##            |
      | assessmentcode  | ABC10101                             |
      | assessmentname  | Portfolio 1                          |
      | sequence        | 001                                  |
      | availablefrom   | 0                                    |
      | reattempt       | 0                                    |
      | grademarkexempt | 0                                    |
    And the following sits assignment exists:
      | sitsref         | AAP601_A_SEM1_2023/24_ABC10101_001_1                |
      | course          | C1                                                  |
      | title           | CGI Production - Portfolio 1 (100%) First Reattempt |
      | weighting       | 100                                                 |
      | duedate         | ## 5 May 2023 16:00:00 ##                           |
      | assessmentcode  | ABC10101                                            |
      | assessmentname  | Portfolio 1                                         |
      | sequence        | 001                                                 |
      | availablefrom   | 0                                                   |
      | reattempt       | 1                                                   |
      | grademarkexempt | 0                                                   |
    And I am on the "AAP601_A_SEM1_2023/24_ABC10101_001_0" Activity page logged in as teacher1
    When I follow "View all submissions"
    Then I should not see "submissions before the first attempt submissions have been marked and released."
    And I am on the "AAP601_A_SEM1_2023/24_ABC10101_001_1" Activity page logged in as moduleleader1
    When I follow "View all submissions"
    Then I should see "Please do not release these First reattempt submissions before the first attempt submissions have been marked and released."
    When I set the field "selectall" to "1"
    And I set the field "operation" to "Set marking workflow state"
    And I click on "Go" "button" confirming the dialogue
    And I set the field "Marking workflow state" to "Released"
    And I set the field "Notify student" to "No"
    And I press "Save changes"
    And I am on the "AAP601_A_SEM1_2023/24_ABC10101_001_1" "assign activity" page
    And I follow "View all submissions"
    Then I should see "Released" in the "I'm the student1 submission" "table_row"
    And I should not see "Please do not release these First reattempt submissions before the first attempt submissions have been marked and released."
