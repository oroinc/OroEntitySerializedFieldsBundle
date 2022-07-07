@regression
@ticket-BAP-21052
@fixture-OroUserBundle:users.yml
Feature: Serialized fields usage in custom reports
  In order to manage data of serialized fields
  As an Administrator
  I want to have an ability to use serialized fields in custom reports

  Scenario: Login and enter to user entity management page
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And filter Name as is equal to "User"
    And I click view User in grid

  Scenario Outline: Create serialized fields for user entity
    And click "Create field"
    And I fill form with:
      | Field name   | <Field Name>     |
      | Storage type | Serialized field |
      | Type         | <Type>           |
    And click "Continue"
    And I fill form with:
      | Label                | <Label>            |
      | Add To Grid Settings | Yes and display    |
      | Show Grid Filter     | <Show Grid Filter> |
    When I save and close form
    Then I should see "Field saved" flash message

    Examples:
      | Field Name       | Type  | Label            | Show Grid Filter |
      | serialized_float | Float | Serialized Float | Yes              |
      | serialized_date  | Date  | Serialized Date  | Yes              |

  Scenario Outline: Set serialized fields values created in scenario above to users
    When I go to System/User Management/Users
    And click Edit <User Name> in grid
    And I fill form with:
      | Serialized Float | <Float Input Value> |
      | Serialized Date  | <Date Input Value> |
    And I save form
    Then I should see "User saved" flash message

    Examples:
      | User Name | Float Input Value | Date Input Value  |
      | megan     | 15.55             | <Date:2013-01-10> |
      | charlie   | 20.22             | <Date:2013-01-15> |

  Scenario: Check custom report creation using serialized fields
    And I go to Reports & Segments/ Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Users Report |
      | Entity      | User         |
      | Report Type | Table        |
    And I add the following columns:
      | Username         |
      | Serialized Float |
      | Serialized Date  |
    And I add the following filters:
      | Field Condition | Serialized Float | is not empty |
    When I save and close form
    Then I should see "Report saved" flash message
    And there are 2 records in grid

  Scenario: Checking report grid sorting by serialized fields
    Given I reload the page
    When I sort grid by Serialized Float
    Then I should see following grid:
      | Username | Serialized Float | Serialized Date |
      | megan    | 15.55            | Jan 10, 2013    |
      | charlie  | 20.22            | Jan 15, 2013    |
    When I sort grid by Serialized Float again
    Then I should see following grid:
      | Username | Serialized Float | Serialized Date |
      | charlie  | 20.22            | Jan 15, 2013    |
      | megan    | 15.55            | Jan 10, 2013    |
    When I sort grid by Serialized Date
    Then I should see following grid:
      | Username | Serialized Float | Serialized Date |
      | megan    | 15.55            | Jan 10, 2013    |
      | charlie  | 20.22            | Jan 15, 2013    |
    When I sort grid by Serialized Date again
    Then I should see following grid:
      | Username | Serialized Float | Serialized Date |
      | charlie  | 20.22            | Jan 15, 2013    |
      | megan    | 15.55            | Jan 10, 2013    |

  Scenario: Checking report grid filtering by serialized fields
    Given I reset grid
    When I filter "Serialized Float" as less than "20"
    Then I should see following grid:
      | Username | Serialized Float | Serialized Date |
      | megan    | 15.55            | Jan 10, 2013    |
    When I filter "Serialized Float" as more than "20"
    Then I should see following grid:
      | Username | Serialized Float | Serialized Date |
      | charlie  | 20.22            | Jan 15, 2013    |
    When I reset Serialized Float filter
    And I filter "Serialized Date" as between "Jan 9, 2013" and "Jan 11, 2013"
    Then I should see following grid:
      | Username | Serialized Float | Serialized Date |
      | megan    | 15.55            | Jan 10, 2013    |
