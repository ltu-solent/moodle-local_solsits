@local @local_solsits @sol @javascript
Feature: Manage SITS assignments
  In order manage SITS assignments
  As an administrator
  I should be able to view options in a table page

  Background:
    Given the following "courses" exist:
    | fullname | shortname | category | customfield_templateapplied |
    | Course1  | C1        | 0        | 1                           |
    | Course2  | C2        | 0        | 1                           |
    And the solent gradescales are setup
    And the following SITS assignment exists:
    | sitsref         | C1_SITS1       |
    | course          | C1             |
    | title           | Report 1 (20%) |
    | weighting       | 20             |
    | duedate         | ## 5 May 2023 16:00:00 ## |
    | assessmentcode  | REPORT1        |
    | assessmentname  | Report 1       |
    | sequence        | 001            |
    | availablefrom   | 0              |
    | reattempt       | 0              |
    | grademarkexempt | 1              |
    And the following SITS assignment exists:
    | sitsref         | C1_SITS2       |
    | course          | C1             |
    | title           | Report 2 (20%) |
    | weighting       | 20             |
    | duedate         | ## 5 May 2023 16:00:00 ## |
    | assessmentcode  | REPORT2        |
    | assessmentname  | Report 2       |
    | sequence        | 002            |
    | availablefrom   | 0              |
    | reattempt       | 0              |
    | grademarkexempt | 0              |
    And the following SITS assignment exists:
    | sitsref         | C1_SITS3       |
    | course          | C1             |
    | title           | Report 3 (20%) |
    | weighting       | 20             |
    | duedate         | ## 5 May 2023 16:00:00 ## |
    | assessmentcode  | REPORT3        |
    | assessmentname  | Report 3       |
    | sequence        | 003            |
    | availablefrom   | 0              |
    | reattempt       | 0              |
    | grademarkexempt | 0              |
    And the following SITS assignment exists:
    | sitsref         | C1_SITS4       |
    | course          | C1             |
    | title           | Report 4 (20%) |
    | weighting       | 20             |
    | duedate         | ## 5 May 2023 16:00:00 ## |
    | assessmentcode  | REPORT4        |
    | assessmentname  | Report 4       |
    | sequence        | 004            |
    | availablefrom   | 0              |
    | reattempt       | 0              |
    | grademarkexempt | 0              |
    And the following SITS assignment exists:
    | sitsref         | C1_SITS5       |
    | course          | C1             |
    | title           | Report 5 (20%) |
    | weighting       | 20             |
    | duedate         | 0              |
    | assessmentcode  | REPORT5        |
    | assessmentname  | Report 5       |
    | sequence        | 005            |
    | availablefrom   | 0              |
    | reattempt       | 0              |
    | grademarkexempt | 0              |
    And the following SITS assignment exists:
    | sitsref         | C2_SITS1       |
    | course          | C2             |
    | title           | Report 1 (20%) |
    | weighting       | 20             |
    | duedate         | ## 5 May 2023 16:00:00 ## |
    | assessmentcode  | REPORT1        |
    | assessmentname  | Report 1       |
    | sequence        | 001            |
    | availablefrom   | 0              |
    | reattempt       | 0              |
    | grademarkexempt | 1              |
    And the following SITS assignment exists:
    | sitsref         | C2_SITS5       |
    | course          | C2             |
    | title           | Report 5 (20%) |
    | weighting       | 20             |
    | duedate         | 0              |
    | assessmentcode  | REPORT5        |
    | assessmentname  | Report 5       |
    | sequence        | 005            |
    | availablefrom   | 0              |
    | reattempt       | 0              |
    | grademarkexempt | 0              |
    And I am logged in as "admin"

  Scenario: View SITS assign data table
    Given I navigate to "Plugins > Local plugins > Manage assignments" in site administration
    Then I should see "Delete" in the "C1_SITS5" "table_row"
    And I should see "Delete" in the "C2_SITS5" "table_row"
    And I should not see "Delete" in the "C1_SITS1" "table_row"
    And I should not see "Delete" in the "C2_SITS1" "table_row"
    And I should not see "Recreate assignment activity" in the "C1_SITS2" "table_row"

  Scenario: Assignment has been deleted and needs to be recreated
    Given I am on "Course1" course homepage with editing mode on
    Then I should see "Report 2 (20%)"
    And I delete "Report 2 (20%)" activity
    And I run all adhoc tasks
    When I navigate to "Plugins > Local plugins > Manage assignments" in site administration
    Then I should see "Recreate assignment activity" in the "C1_SITS2" "table_row"
    And I should see "No longer exists" in the "C1_SITS2" "table_row"
    And I follow "Recreate assignment activity"
    And I should see "Confirm recreation of C1_SITS2"
    And I click on "Recreate" "button"
    And I should see "SITS assignment C1_SITS2 has been queued to be recreated"
    And I should see "-" in the "C1_SITS2" "table_row"
    And I run the scheduled task "\local_solsits\task\create_assignment_task"
    When I navigate to "Plugins > Local plugins > Manage assignments" in site administration
    Then I should not see "No longer exists" in the "C1_SITS2" "table_row"
    And I should not see "-" in the "C1_SITS2" "table_row"
    And I am on "Course1" course homepage with editing mode on
    Then I should see "Report 2 (20%)"

  Scenario: Delete sitsassign record
    Given I am on "Course1" course homepage
    Then I should not see "Report 5 (20%)"
    And I navigate to "Plugins > Local plugins > Manage assignments" in site administration
    And I should see "Delete" in the "C1_SITS5" "table_row"
    And I click on "Delete" "link" in the "C1_SITS5" "table_row"
    And I should see "Confirm deletion of C1_SITS5"
    And I click on "Delete" "button"
    And I should see "SITS assignment C1_SITS5 has been deleted."
    And "C1_SITS5" "table_row" should not exist
