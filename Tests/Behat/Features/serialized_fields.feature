@ticket-BAP-17275
Feature: Serialized fields
  In order to manage serialized fields
  As an Administrator
  I want to have possibility to create, remove and restore serialized fields

  Scenario: Create serialized field
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And filter Name as is equal to "Group"
    And I click view Group in grid
    And click "Create field"
    And I fill form with:
      |Field name  |field1          |
      |Storage type|Serialized field|
      |Type        |String          |
    And click "Continue"
    When I save and close form
    Then I should see "Field saved" flash message
    # check that created serialized field is available on the entity form and can be saved
    And I go to System/ User Management/ Groups
    And I click "Create Group"
    And I fill form with:
      |Name   |Group1 |
      |Field1 |Test1  |
    And I save and close form
    And I should see "Group saved" flash message

  Scenario: Remove serialized field
    Given I go to System/ Entities/ Entity Management
    And filter Name as is equal to "Group"
    And I click view Group in grid
    When I click remove Field1 in grid
    And click "Yes" in confirmation dialogue
    Then I should see "Field successfully deleted" flash message
    # check that removed serialized field is not available on the entity form and can update entity with removed serialized field
    And I go to System/ User Management/ Groups
    And I click edit Group1 in grid
    And I should not see "Field1"
    And I fill form with:
      |Name   |Group2 |
    And I save and close form
    And I should see "Group saved" flash message

  @skip
  Scenario: Restore serialized field
    Given I go to System/ Entities/ Entity Management
    And filter Name as is equal to "Group"
    And I click view Group in grid
    When I click restore Field1 in grid
    Then I should see "Field was restored" flash message
    # check that restored serialized field is available on the entity form and can be saved
    When I go to System/ User Management/ Groups
    And I click edit Group2 in grid
    And I fill form with:
      |Field1 |Test2 |
    And I save and close form
    Then I should see "Group saved" flash message
