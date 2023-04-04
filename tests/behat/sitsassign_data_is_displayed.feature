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
    And the following "activities" exist:
      | activity | name           | course | idnumber   |
      | assign   | Formative1     | C1     |            |
      | assign   | SITS1          | C1     | SITS1      |
      | assign   | Quercus1       | C1     | Quercus1   |
    And the following sits assignment exists:
      | sitsref  | SITS1   |
      | course   | C1      |
      | title    | ASSIGN1 |

  Scenario: View SITS data
    Given I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "SITS1"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should see "SITS reference"
    And I should see "SITS1" in the "#fitem_id_sits_ref" "css_element"

  Scenario: I should not see Quercus data
    Given I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "Quercus1"
    And I follow "Settings"
    When I expand all fieldsets
    Then I should not see "SITS reference"
    And "#fitem_id_sits_ref" "css_element" should not exist

  Scenario: I should not see Formative data
    Given I log in as "teacher1"
      And I am on "Course1" course homepage
      And I follow "Formative1"
      And I follow "Settings"
      When I expand all fieldsets
      Then I should not see "SITS reference"
      And "#fitem_id_sits_ref" "css_element" should not exist