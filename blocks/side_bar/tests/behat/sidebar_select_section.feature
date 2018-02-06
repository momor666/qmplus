@block @block_side_bar
Feature: Section setting
  As a Admin

  Scenario: Add a block
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "blocks" exist:
      | blockname    | contextlevel | reference | pagetypepattern | defaultregion |
      | side_bar | Course       | C1        | course-view-*   | site-pre      |
    When I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    Then I should see "NED Sidebar"

