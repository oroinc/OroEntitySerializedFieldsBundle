@regression
@ticket-BAP-21052
@fixture-OroUserBundle:users.yml
Feature: Serialized fields on entity's grids
  In order to manage data of serialized fields
  As an Administrator
  I want to have an ability to filter and sort with serialized fields on entity's grids

  Scenario: Login and enter to user entity management page
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And filter Name as is equal to "User"
    And I click view User in grid

  Scenario Outline: Create filterable serialized fields for user entity
    When click "Create field"
    And I fill form with:
      | Field name   | <Field Name>   |
      | Storage type | <Storage Type> |
      | Type         | <Type>         |
    And click "Continue"
    And I fill form with:
      | Label                | <Label>         |
      | Add To Grid Settings | Yes and display |
      | Show Grid Filter     | Yes             |
    And I save and close form
    Then I should see "Field saved" flash message

    Examples:
      | Field Name          | Type     | Label               | Storage Type     |
      | serialized_string   | String   | Serialized String   | Serialized field |
      | serialized_bigint   | BigInt   | Serialized BigInt   | Serialized field |
      | serialized_boolean  | Boolean  | Serialized Boolean  | Serialized field |
      | serialized_date     | Date     | Serialized field Date | Serialized field |
      | serialized_datetime | DateTime | Serialized DateTime | Serialized field |
      | serialized_decimal  | Decimal  | Serialized Decimal  | Serialized field |
      | serialized_float    | Float    | Serialized Float    | Serialized field |
      | serialized_integer  | Integer  | Serialized Integer  | Serialized field |
      | serialized_money    | Money    | Serialized Money    | Serialized field |
      | serialized_percent  | Percent  | Serialized Percent  | Serialized field |
      | serialized_smallint | SmallInt | Serialized SmallInt | Serialized field |

  Scenario Outline: Create not filterable serialized fields for user entity
    And click "Create field"
    And I fill form with:
      | Field name   | <Field Name>     |
      | Storage type | Serialized field |
      | Type         | <Type>           |
    And click "Continue"
    And I fill form with:
      | Label | <Label> |
    When I save and close form

    Examples:
      | Field Name         | Type    | Label              |
      | serialized_text    | Text    | Serialized Text    |
      | serialized_wysiwyg | WYSIWYG | Serialized WYSIWYG |

  Scenario Outline: Set serialized fields values created in scenario above to users
    When I go to System/User Management/Users
    And click Edit <User Name> in grid
    And I fill form with:
      | Serialized BigInt   | <BigInt Input Value>   |
      | Serialized Boolean  | <Boolean Input Value>  |
      | Serialized field Date     | <Date Input Value>     |
      | Serialized DateTime | <DateTime Input Value> |
      | Serialized Decimal  | <Decimal Input Value>  |
      | Serialized Float    | <Float Input Value>    |
      | Serialized Integer  | <Integer Input Value>  |
      | Serialized Money    | <Money Input Value>    |
      | Serialized Percent  | <Percent Input Value>  |
      | Serialized SmallInt | <SmallInt Input Value> |
      | Serialized String   | <String Input Value>   |
      | Serialized Text     | Text value             |
    And I save form
    Then I should see "User saved" flash message

    Examples:
      | User Name | BigInt Input Value | Boolean Input Value | Date Input Value  | DateTime Input Value | Decimal Input Value | Float Input Value    | Integer Input Value | Money Input Value | Percent Input Value | SmallInt Input Value | String Input Value |
      | admin     | 0                  | Yes                 | <Date:1942-01-01> | <Date:1942-01-01>    | 0                   | 0                    | 0                   | 0                 | 0                   | 0                    | String value 1     |
      | megan     | 2147483646         | No                  | <Date:2023-12-31> | <Date:2023-12-31>    | 12345678.90         | 99999999999999.12345 | 2147483647          | 89999999999999.99 | 899999999999999     | 32767                | String value 2     |
      | charlie   | 9999               |                     | <Date:2013-01-09> | <Date:2013-12-31>    | 78.99               | 19999999999999       | 999                 | 924.567           | 923.45              | 4234                 | String value 3     |

  Scenario: Checking entity grid sorting by serialized fields
    Given I go to System/User Management/Users
    When I sort grid by "Serialized BigInt"
    Then I should see following grid:
      | Username | Serialized BigInt |
      | admin    | 0                 |
      | charlie  | 9999              |
      | megan    | 2147483646        |
    When I sort grid by "Serialized BigInt" again
    Then I should see following grid:
      | Username | Serialized BigInt |
      | megan    | 2147483646        |
      | charlie  | 9999              |
      | admin    | 0                 |
    When I sort grid by "Serialized Boolean"
    Then I should see following grid:
      | Username | Serialized Boolean |
      | megan    | No                 |
      | admin    | Yes                |
      | charlie  | N/A                |
    When I sort grid by "Serialized Boolean" again
    Then I should see following grid:
      | Username | Serialized Boolean |
      | charlie  | N/A                |
      | admin    | Yes                |
      | megan    | No                 |
    When I sort grid by "Serialized field Date"
    Then I should see following grid:
      | Username | Serialized field Date |
      | admin    | Jan 1, 1942     |
      | charlie  | Jan 9, 2013     |
      | megan    | Dec 31, 2023    |
    When I sort grid by "Serialized field Date" again
    Then I should see following grid:
      | Username | Serialized field Date |
      | megan    | Dec 31, 2023    |
      | charlie  | Jan 9, 2013     |
      | admin    | Jan 1, 1942     |
    When I sort grid by "Serialized DateTime"
    Then I should see following grid:
      | Username | Serialized DateTime    |
      | admin    | Jan 1, 1942, 12:00 AM  |
      | charlie  | Dec 31, 2013, 12:00 AM |
      | megan    | Dec 31, 2023, 12:00 AM |
    When I sort grid by "Serialized DateTime" again
    Then I should see following grid:
      | Username | Serialized DateTime    |
      | megan    | Dec 31, 2023, 12:00 AM |
      | charlie  | Dec 31, 2013, 12:00 AM |
      | admin    | Jan 1, 1942, 12:00 AM  |
    When I sort grid by "Serialized Decimal"
    Then I should see following grid:
      | Username | Serialized Decimal |
      | admin    | N/A                |
      | charlie  | 78.99              |
      | megan    | 12,345,678.9       |
    When I sort grid by "Serialized Decimal" again
    Then I should see following grid:
      | Username | Serialized Decimal |
      | megan    | 12,345,678.9       |
      | charlie  | 78.99              |
      | admin    | N/A                |
    When I sort grid by "Serialized Float"
    Then I should see following grid:
      | Username | Serialized Float         |
      | admin    | N/A                      |
      | charlie  | 19,999,999,999,999       |
      | megan    | 99,999,999,999,999.12345 |
    When I sort grid by "Serialized Float" again
    Then I should see following grid:
      | Username | Serialized Float         |
      | megan    | 99,999,999,999,999.12345 |
      | charlie  | 19,999,999,999,999       |
      | admin    | N/A                      |
    When I sort grid by "Serialized Integer"
    Then I should see following grid:
      | Username | Serialized Integer |
      | admin    | 0                  |
      | charlie  | 999                |
      | megan    | 2147483647         |
    When I sort grid by "Serialized Integer" again
    Then I should see following grid:
      | Username | Serialized Integer |
      | megan    | 2147483647         |
      | charlie  | 999                |
      | admin    | 0                  |
    When I sort grid by "Serialized Money"
    Then I should see following grid:
      | Username | Serialized Money       |
      | admin    | N/A                    |
      | charlie  | $924.57                |
      | megan    | $90,000,000,000,000.00 |
    When I sort grid by "Serialized Money" again
    Then I should see following grid:
      | Username | Serialized Money       |
      | megan    | $90,000,000,000,000.00 |
      | charlie  | $924.57                |
      | admin    | N/A                    |
    When I sort grid by "Serialized Percent"
    Then I should see following grid:
      | Username | Serialized Percent   |
      | admin    | N/A                  |
      | charlie  | 923.45%              |
      | megan    | 900,000,000,000,000% |
    When I sort grid by "Serialized Percent" again
    Then I should see following grid:
      | Username | Serialized Percent   |
      | megan    | 900,000,000,000,000% |
      | charlie  | 923.45%              |
      | admin    | N/A                  |
    When I sort grid by "Serialized SmallInt"
    Then I should see following grid:
      | Username | Serialized SmallInt |
      | admin    | 0                   |
      | charlie  | 4234                |
      | megan    | 32767               |
    When I sort grid by "Serialized SmallInt" again
    Then I should see following grid:
      | Username | Serialized SmallInt |
      | megan    | 32767               |
      | charlie  | 4234                |
      | admin    | 0                   |
    When I sort grid by "Serialized String"
    Then I should see following grid:
      | Username | Serialized String |
      | admin    | String value 1    |
      | megan    | String value 2    |
      | charlie  | String value 3    |
    When I sort grid by "Serialized String" again
    Then I should see following grid:
      | Username | Serialized String |
      | charlie  | String value 3    |
      | megan    | String value 2    |
      | admin    | String value 1    |

  Scenario: Checking entity grid filtering by serialized fields
    Given I go to System/User Management/Users
    # BigInt
    And I filter "Serialized BigInt" as more than "9998"
    Then there are 2 records in grid
    And I should see following grid:
      | Username |
      | charlie  |
      | megan    |
    And I click "Reset"
    #  Boolean
    And I check "Yes" in Serialized Boolean filter
    Then there is one record in grid
    And I should see following grid:
      | Username |
      | admin    |
    And I click "Reset"
    #  Date
    And I filter "Serialized field Date" as between "Jan 1, 2000" and "Dec 31, 2023"
    Then there are 2 records in grid
    And I should see following grid:
      | Username |
      | charlie  |
      | megan    |
    #  DateTime
    And I filter "Serialized DateTime" as between "Jan 1, 2000 11:30 AM" and "Dec 31, 2023 11:30 AM"
    Then there are 2 records in grid
    And I should see following grid:
      | Username |
      | charlie  |
      | megan    |
    #  Decimal
    And I filter "Serialized Decimal" as Equals "78.99"
    Then there is one record in grid
    And I should see following grid:
      | Username |
      | charlie  |
    #  Float
    And I filter "Serialized Float" as more than "10"
    Then there is one record in grid
    And I should see following grid:
      | Username |
      | charlie  |
    #  Integer
    And I filter "Serialized Integer" as equals or more than "999"
    Then there is one record in grid
    And I should see following grid:
      | Username |
      | charlie  |
    #  Money
    And I filter "Serialized Money" as equals or less than "924.57"
    Then there is one record in grid
    And I should see following grid:
      | Username |
      | charlie  |
    #  Percent
    And I filter "Serialized Percent" as equals "923.45"
    Then there is one record in grid
    And I should see following grid:
      | Username |
      | charlie  |
    #  SmallInt
    And I filter "Serialized SmallInt" as less than "5000"
    Then there is one record in grid
    And I should see following grid:
      | Username |
      | charlie  |
    And I click "Reset"
    #  String
    And I filter "Serialized String" as contains "2"
    Then there is one record in grid
    And I should see following grid:
      | Username |
      | megan    |
