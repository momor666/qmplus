@block @block_landingpage
Feature: Users are redirected to the appropriate landing page

  Background:
    Given the following "users" exist:
      | username |
      | student1 |
    And the following "categories" exist:
      | name          | category  | idnumber      |
      | Science       |           | Science       |
      | Languages     |           | Languages     |
      | Physics       | Science   | Physics       |
      | French        | Languages | French        |
      | Science Cat 1 | Science   | Science Cat 1 |
      | Physics Cat 1 | Physics   | Physics Cat 1 |
      | French Cat 1  | French    | French Cat 1  |
    And the following "courses" exist:
      | shortname    | fullname               | idnumber | category  |
      | Science 01   | Science landing page   | SC01     | Science   |
      | Physics 01   | Physics landing page   | PY01     | Physics   |
      | Languages 01 | Languages landing page | LN01     | Languages |
      | French 01    | French landing page    | FR01     | French    |
    And the following "courses" exist:
      | shortname | fullname   | category      |
      | Phys 05   | Physics 05 | Physics Cat 1 |
      | Sci 10    | Science 10 | Science Cat 1 |
      | Frn 12    | French 12  | French Cat 1  |
    And I log in as "admin"
    And I navigate to "Users > Accounts > User profile fields" in site administration
    And I click on "Edit" "link" in the "Landing page" "table_row"
    And I set the field "Menu options" to multiline:
    """
    SC01
    PY01
    LN01
    FR01
    """
    And I press "Save changes"
    And I am on site homepage
    And I turn editing mode on
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the field "Content" to "<a href=\"blocks/landingpage/tests/testredirect.php\">Test landing page redirect</a>"
    And I press "Save changes"
    And I log out

  Scenario: User studying French gets redirected to French landing page
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | Frn 12 | student |
    When I log in as "student1"
    And I am on site homepage
    And I follow "Test landing page redirect"
    Then I should see "French landing page"

  Scenario: User studying French and Physics gets given a choice of landing pages
    Given the following "course enrolments" exist:
      | user     | course  | role    |
      | student1 | Frn 12  | student |
      | student1 | Phys 05 | student |
    When I log in as "student1"
    And I am on site homepage
    And I follow "Test landing page redirect"
    Then I should see "Pick a Home page"

    When I set the field with xpath "//input[@value='FR01']" to "1"
    And I press "Save changes"
    Then I should see "French landing page"

    When I am on site homepage
    And I follow "Test landing page redirect"
    Then I should see "French landing page"

  Scenario: User studying French and Physics can change their landing page
    Given the following "course enrolments" exist:
      | user     | course  | role    |
      | student1 | Frn 12  | student |
      | student1 | Phys 05 | student |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Physics landing page"
    And I turn editing mode on
    And I add the "Landing page" block
    And I follow "Participants"
    And I navigate to "Enrolled users > Enrolment methods" in current page administration
    And I click on "Enable" "link" in the "Guest access" "table_row"
    And I log out
    And I log in as "student1"
    And I am on site homepage
    And I follow "Test landing page redirect"
    And I set the field with xpath "//input[@value='PY01']" to "1"
    And I press "Save changes"
    And I should see "Physics landing page"

    When I set the field "Pick a Home page" to "FR01"
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test landing page redirect"
    Then I should see "French landing page"
