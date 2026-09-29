@mod @mod_videodebate
Feature: Use a Video Debate activity
  In order to participate in an evidence-based discussion
  As a student
  I need to open a Video Debate and see its debate controls

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher@example.com  |
      | student1 | Student   | One      | student@example.com  |
    And the following "courses" exist:
      | fullname    | shortname | category |
      | Test course | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity    | course | name         | idnumber |
      | videodebate | C1     | Test debate  | VD1      |

  @javascript
  Scenario: Student opens the debate
    Given I log in as "student1"
    When I am on the "Test debate" "videodebate activity" page
    Then I should see "What position do you defend?"
    And I should see "Argument"
    And I should see "Video progress"
