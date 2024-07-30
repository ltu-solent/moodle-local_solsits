@local @local_solsits
Feature: Testing fixed_gradetype in local_solsits
  In order to prevent tutors changing grade types on summative assignments
  As a teacher
  I cannot change the grade type

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

  Scenario: Tutors cannot change Scale to Point when using Grademarkexempt
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
    And I should see "Scale" in the "#id_grade_modgrade_type" "css_element"
    And the "id_grade_modgrade_type" select box should not contain "None"
    And the "id_grade_modgrade_type" select box should not contain "Point"
    And I should see "Solent grademark exempt scale" in the "#id_grade_modgrade_scale" "css_element"

  Scenario: Tutors cannot change Scale to Point when using Grademark
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
      | grademarkexempt | 0              |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "SITS2"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should see "SITS reference"
    And I should see "Scale" in the "#id_grade_modgrade_type" "css_element"
    And the "id_grade_modgrade_type" select box should not contain "None"
    And the "id_grade_modgrade_type" select box should not contain "Point"
    And I should see "Solent grademark scale" in the "#id_grade_modgrade_scale" "css_element"

  Scenario: Tutors cannot change Point to Scale when using Points
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
      | grademarkexempt | 0              |
      | scale           | points         |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "SITS2"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should see "SITS reference"
    And I should see "Point" in the "#id_grade_modgrade_type" "css_element"
    And the "id_grade_modgrade_type" select box should not contain "None"
    And the "id_grade_modgrade_type" select box should not contain "Scale"

  Scenario: Tutors can change grade type on Formative assignments
    Given the following "activities" exist:
      | activity | name           | course | idnumber   |
      | assign   | Formative1     | C1     |            |
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "Formative1"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should not see "SITS reference"
    And I should see "Point" in the "#id_grade_modgrade_type" "css_element"
    And the "id_grade_modgrade_type" select box should contain "None"
    And the "id_grade_modgrade_type" select box should contain "Scale"
