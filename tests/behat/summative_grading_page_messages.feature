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
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | config | value  | plugin |
      | theme  | solent |        |
      | assignmentmessage_marksuploadinclude | <p>Grades for this assignment will be sent to {SRS} once they have been released to students in SOL.</p> | local_solsits |

  Scenario: View Quercus assignment grading message
    Given the following "activities" exist:
      | activity | name           | course | idnumber   |
      | assign   | Quercus1       | C1     | Quercus1   |
    And I am on the "Quercus1" Activity page logged in as teacher1
    When I follow "View all submissions"
    Then I should see "Grades for this assignment will be sent to Quercus once they have been released to students in SOL."
    And I should not see "Grades for this assignment will be sent to Gateway (SITS)"

  Scenario: View SITS assignment grading message
    Given the following "activities" exist:
      | activity | name           | course | idnumber   |
      | assign   | SITS1          | C1     | SITS1      |
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
      | activity | name           | course | idnumber   |
      | assign   | Formative1     | C1     |            |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "Formative1"
    When I follow "View all submissions"
    Then I should not see "Grades for this assignment will be sent to Gateway (SITS)"
    And I should not see "Grades for this assignment will be sent to Quercus"