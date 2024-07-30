@local @local_solsits @sol @javascript
Feature: Display SITS assignment data in the settings page, if available
  In order know if this is a SITS assignment
  As a teacher
  I should see SITS data in an assignment settings page

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
    And the solent gradescales are setup

  Scenario: View SITS data Grademarkexempt
    Given the following SITS assignment exists:
      | sitsref         | SITS2          |
      | course          | C1             |
      | title           | SITS2          |
      | weighting       | 50             |
      | duedate         | ## 2 June 2023 16:00:00 ## |
      | assessmentcode  | PROJ2          |
      | assessmentname  | Project 2      |
      | sequence        | 001            |
      | availablefrom   | ## 2 June 2023 09:00:00 ##  |
      | reattempt       | 0              |
      | grademarkexempt | 1              |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "SITS2"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should see "SITS reference"
    And I should see "SITS2" in the "#fitem_id_sits_sitsref" "css_element"
    And I should see "Project 2" in the "#fitem_id_sits_assessmentname" "css_element"
    And I should see "PROJ2" in the "#fitem_id_sits_assessmentcode" "css_element"
    And I should see "001" in the "#fitem_id_sits_sequence" "css_element"
    And I should see "First attempt" in the "#fitem_id_sits_reattempt" "css_element"
    And I should see "50%" in the "#fitem_id_sits_weighting" "css_element"
    And I should see "Yes" in the "#fitem_id_sits_grademarkexempt" "css_element"
    And I should see "" in the "#fitem_id_sits_scale" "css_element"
    And I should see "2 June 2023, 9:00:00 AM" in the "#fitem_id_sits_availablefrom" "css_element"
    And I should see "2 June 2023, 4:00:00 PM" in the "#fitem_id_sits_duedate" "css_element"

  Scenario: View SITS data Grademark
    Given the following SITS assignment exists:
      | sitsref         | SITS1         |
      | course          | C1            |
      | title           | SITS1         |
      | weighting       | 50            |
      | duedate         | ## 5 May 2023 16:00:00 ## |
      | assessmentcode  | PROJ1         |
      | assessmentname  | Project 1     |
      | sequence        | 001           |
      | availablefrom   | 0             |
      | reattempt       | 0             |
      | grademarkexempt | 0             |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "SITS1"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should see "SITS reference"
    And I should see "SITS1" in the "#fitem_id_sits_sitsref" "css_element"
    And I should see "Project 1" in the "#fitem_id_sits_assessmentname" "css_element"
    And I should see "PROJ1" in the "#fitem_id_sits_assessmentcode" "css_element"
    And I should see "001" in the "#fitem_id_sits_sequence" "css_element"
    And I should see "First attempt" in the "#fitem_id_sits_reattempt" "css_element"
    And I should see "50%" in the "#fitem_id_sits_weighting" "css_element"
    And I should see "No" in the "#fitem_id_sits_grademarkexempt" "css_element"
    And I should see "" in the "#fitem_id_sits_scale" "css_element"
    And I should see "Immediately" in the "#fitem_id_sits_availablefrom" "css_element"
    And I should see "5 May 2023, 4:00:00 PM" in the "#fitem_id_sits_duedate" "css_element"

  Scenario: View SITS data on reattempt where first sitting used Grademark but Points is in use
    Given the following SITS assignment exists:
      | sitsref         | SITS1_0       |
      | course          | C1            |
      | title           | SITS1 first   |
      | weighting       | 50            |
      | duedate         | ## 5 May 2023 16:00:00 ## |
      | assessmentcode  | PROJ1         |
      | assessmentname  | Project 1     |
      | sequence        | 001           |
      | availablefrom   | 0             |
      | reattempt       | 0             |
      | grademarkexempt | 0             |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "SITS1 first"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should see "SITS reference"
    And I should see "SITS1_0" in the "#fitem_id_sits_sitsref" "css_element"
    And I should see "Project 1" in the "#fitem_id_sits_assessmentname" "css_element"
    And I should see "PROJ1" in the "#fitem_id_sits_assessmentcode" "css_element"
    And I should see "001" in the "#fitem_id_sits_sequence" "css_element"
    And I should see "First attempt" in the "#fitem_id_sits_reattempt" "css_element"
    And I should see "50%" in the "#fitem_id_sits_weighting" "css_element"
    And I should see "No" in the "#fitem_id_sits_grademarkexempt" "css_element"
    And I should see "" in the "#fitem_id_sits_scale" "css_element"
    And I should see "Immediately" in the "#fitem_id_sits_availablefrom" "css_element"
    And I should see "5 May 2023, 4:00:00 PM" in the "#fitem_id_sits_duedate" "css_element"
    And the following config values are set as admin:
      | config       | value  | plugin        |
      | defaultScale | points | local_solsits |
    And the following SITS assignment exists:
      | sitsref         | SITS1_1         |
      | course          | C1              |
      | title           | SITS1 reattempt |
      | weighting       | 50              |
      | duedate         | ## 5 May 2023 16:00:00 ## |
      | assessmentcode  | PROJ1           |
      | assessmentname  | Project 1       |
      | sequence        | 001             |
      | availablefrom   | 0               |
      | reattempt       | 1               |
      | grademarkexempt | 0               |
    And I am on "Course1" course homepage
    And I follow "SITS1 reattempt"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should see "SITS reference"
    And I should see "SITS1_1" in the "#fitem_id_sits_sitsref" "css_element"
    And I should see "Project 1" in the "#fitem_id_sits_assessmentname" "css_element"
    And I should see "PROJ1" in the "#fitem_id_sits_assessmentcode" "css_element"
    And I should see "001" in the "#fitem_id_sits_sequence" "css_element"
    And I should see "First reattempt" in the "#fitem_id_sits_reattempt" "css_element"
    And I should see "50%" in the "#fitem_id_sits_weighting" "css_element"
    And I should see "No" in the "#fitem_id_sits_grademarkexempt" "css_element"
    And I should see "Solent grademark scale" in the "#fitem_id_sits_scale" "css_element"
    And I should see "Immediately" in the "#fitem_id_sits_availablefrom" "css_element"
    And I should see "5 May 2023, 4:00:00 PM" in the "#fitem_id_sits_duedate" "css_element"

  Scenario: View SITS data using Points
    Given the following config values are set as admin:
    | config       | value  | plugin        |
    | defaultScale | points | local_solsits |
    And the following SITS assignment exists:
      | sitsref         | SITS1         |
      | course          | C1            |
      | title           | SITS1         |
      | weighting       | 50            |
      | duedate         | ## 5 May 2023 16:00:00 ## |
      | assessmentcode  | PROJ1         |
      | assessmentname  | Project 1     |
      | sequence        | 001           |
      | availablefrom   | 0             |
      | reattempt       | 0             |
      | grademarkexempt | 0             |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "SITS1"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should see "SITS reference"
    And I should see "SITS1" in the "#fitem_id_sits_sitsref" "css_element"
    And I should see "Project 1" in the "#fitem_id_sits_assessmentname" "css_element"
    And I should see "PROJ1" in the "#fitem_id_sits_assessmentcode" "css_element"
    And I should see "001" in the "#fitem_id_sits_sequence" "css_element"
    And I should see "First attempt" in the "#fitem_id_sits_reattempt" "css_element"
    And I should see "50%" in the "#fitem_id_sits_weighting" "css_element"
    And I should see "No" in the "#fitem_id_sits_grademarkexempt" "css_element"
    And I should see "Points" in the "#fitem_id_sits_scale" "css_element"
    And I should see "Immediately" in the "#fitem_id_sits_availablefrom" "css_element"
    And I should see "5 May 2023, 4:00:00 PM" in the "#fitem_id_sits_duedate" "css_element"

  Scenario: I should not see Quercus data
    Given the following "activities" exist:
      | activity | name           | course | idnumber   |
      | assign   | Quercus1       | C1     | Quercus1   |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "Quercus1"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should not see "SITS reference"
    And "#fitem_id_sits_ref" "css_element" should not exist

  Scenario: I should not see Formative data
    Given the following "activities" exist:
      | activity | name           | course | idnumber   |
      | assign   | Formative1     | C1     |            |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "Formative1"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should not see "SITS reference"
    And "#fitem_id_sits_ref" "css_element" should not exist
