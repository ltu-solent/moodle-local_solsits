@local @local_solsits @sol @javascript
Feature: The ability to release grades depends on permissions and workflow status
  In order to control when grades are released for Summative assignments the workflow is managed
  As a teacher or module leader
  I should and should not be able to perform actions depending on assignment type and who I am

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course1  | C1        | 0        |
    And the following "users" exist:
      | username      | firstname    | lastname | email                     |
      | moduleleader1 | Moduleleader | 1        | moduleleader1@example.com |
      | teacher1      | Teacher      | 1        | teacher1@example.com      |
      | student1      | Student      | 1        | student1@example.com      |
      | student2      | Student      | 2        | student2@example.com      |
      | external1     | External     | Examiner | ee1@example.com           |
    And the following "roles" exist:
      | shortname    | name              | archetype      |
      | moduleleader | Module leader     | editingteacher |
      | ee           | External examiner | teacher        |
    And I log in as "admin"
    And I set the following system permissions of "Module leader" role:
      | capability                           | permission |
      | local/solsits:releasegrades          | Allow      |
    And I set the following system permissions of "External examiner" role:
      | capability                           | permission |
      | local/solsits:submissionsselectusers | Allow      |
    And the following "course enrolments" exist:
      | user          | course | role           |
      | moduleleader1 | C1     | moduleleader   |
      | teacher1      | C1     | editingteacher |
      | student1      | C1     | student        |
      | student2      | C1     | student        |
      | external1     | C1     | ee             |
    And the following config values are set as admin:
      | config | value  | plugin |
      | theme  | solent |        |
      | assignmentmessage_marksuploadinclude | <p>Grades for this assignment will be sent to {SRS} once they have been released to students in SOL.</p> | local_solsits |

  Scenario: Module leader can release summative assignments, but not re-release them
    Given the following "activity" exists:
      | activity                            | assign |
      | name                                | SITS1  |
      | course                              | C1     |
      | idnumber                            | SITS1  |
      | assignsubmission_onlinetext_enabled | 1      |
      | markingworkflow                     | 1      |
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                   |
      | SITS1                 | student1  | I'm the student1 submission  |
    And the following SITS assignment exists:
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
    And I am on the "SITS1" Activity page logged in as moduleleader1
    When I follow "View all submissions"
    Then I should not see "Grades for this assignment have been released and locked."
    And I set the field "selectall" to "1"
    And I set the field "operation" to "Set marking workflow state"
    And I click on "Go" "button" confirming the dialogue
    And I set the field "Marking workflow state" to "Released"
    And I set the field "Notify student" to "No"
    And I press "Save changes"
    And I am on the "SITS1" "assign activity" page
    And I follow "View all submissions"
    Then I should see "Grades for this assignment have been released and locked."
    # Try to re-release the grades.
    When I set the field "selectall" to "1"
    And I set the field "operation" to "Set marking workflow state"
    When I click on "Go" "button" confirming the dialogue
    Then I should not see "Marking workflow state"
    And the "Notify student" "field" should be disabled
    And I should not see "Save changes"

  Scenario: Teacher cannot release summative assignments
    Given the following "activity" exists:
      | activity                            | assign |
      | name                                | SITS1  |
      | course                              | C1     |
      | idnumber                            | SITS1  |
      | assignsubmission_onlinetext_enabled | 1      |
      | markingworkflow                     | 1      |
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                   |
      | SITS1                 | student1  | I'm the student1 submission  |
    And the following SITS assignment exists:
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
    Then I should not see "Grades for this assignment have been released and locked."
    And I set the field "selectall" to "1"
    And I set the field "operation" to "Set marking workflow state"
    And I click on "Go" "button" confirming the dialogue
    Then I should see "Marking workflow state"
    And the "Marking workflow state" select box should not contain "Released"
    And the "Marking workflow state" select box should contain "Ready for release"
    And the "Notify student" "field" should be disabled
    And I press "Save changes"
    And I am on the "SITS1" "assign activity" page
    And I follow "View all submissions"
    Then I should not see "Grades for this assignment have been released and locked."

  Scenario: Teacher cannot select individual submissions without selecting all
    Given the following "activity" exists:
      | activity                            | assign |
      | name                                | SITS1  |
      | course                              | C1     |
      | idnumber                            | SITS1  |
      | assignsubmission_onlinetext_enabled | 1      |
      | markingworkflow                     | 1      |
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                   |
      | SITS1                 | student1  | I'm the student1 submission  |
      | SITS1                 | student2  | I'm the student2 submission  |
    And the following SITS assignment exists:
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
    And I follow "View all submissions"
    When I click on "Select Student 1" "checkbox"
    Then the field "Select Student 2" matches value "checked"

  Scenario: External Examiner CAN select individual submissions without selecting all
    Given the following "activity" exists:
      | activity                            | assign |
      | name                                | SITS1  |
      | course                              | C1     |
      | idnumber                            | SITS1  |
      | assignsubmission_onlinetext_enabled | 1      |
      | markingworkflow                     | 1      |
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                   |
      | SITS1                 | student1  | I'm the student1 submission  |
      | SITS1                 | student2  | I'm the student2 submission  |
    And the following SITS assignment exists:
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
    And I am on the "SITS1" Activity page logged in as external1
    And I follow "View all submissions"
    When I click on "Select Student 1" "checkbox"
    Then the field "Select Student 2" does not match value "checked"
