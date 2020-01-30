Feature:
  Add Curriculum tags to the system

  Background:
    Given I am logged in as "admin" with password "apple123"

  Scenario Outline: Add Tag Set
    Given I am on "/admin/curriculum/tags"
    When I press "Add Tag Set"
      And I fill in "objective_code" with "<code>"
      And I fill in "objective_name" with "<title>"
      And I scroll to "tag-options"
      And I fill in "max_level" with "2"
      And I fill in "level_1" with "level 1"
      And I fill in "level_2" with "level 2"
      And I scroll to "tag-display-options"
      And I press "Save"
    Then I should see "You have successfully added"
    When I follow "click here"
    Then I should see "ENTRADA CURRICULUM TAG SET"

    Examples:
      | code   | title    |
      | ENT01  | ENTRADA CURRICULUM TAG SET1     |
      | ENT02  | ENTRADA CURRICULUM TAG SET2     |

  Scenario: Edit tag set
    Given I am on "/admin/curriculum/tags"
    When I follow "ENTRADA CURRICULUM TAG SET2"
      And I follow "Edit Tag Set"
      And I scroll to "tag-options"
      And I fill in "max_level" with "3"
      And I fill in "level_3" with "level 3"
      And I scroll to "tag-attributes"
      And I press "choose-tagset-btn"
      And I fill in "Begin typing to search..." with "SET1"
      And I click on the text "ENTRADA CURRICULUM TAG SET1"
      And I press "Save"
    Then I should see "You have successfully updated"
    When I follow "click here"
    Then I should see "ENTRADA CURRICULUM TAG SET2"


  Scenario Outline: Add Tag
    Given I am on "/admin/curriculum/tags"
    When I follow "ENTRADA CURRICULUM TAG SET1"
      And I follow "Add Tag"
      And I fill in "objective_code" with "<code>"
      And I fill in "objective_title[en]" with "<title>"
      And I scroll to "non_examinable"
      And I press "Save & Close"
    Then I should see "You have successfully added <title> to the system"
    When I follow "click here"
    Then I should see "<title>"

    Examples:
      | code   | title          |
      | ENT04  | ENT01.TAG4     |
      | ENT03  | ENT01.TAG3     |
      | ENT02  | ENT01.TAG2     |
      | ENT01  | ENT01.TAG1     |


  @javascript
  Scenario Outline: Edit Tag
    Given I am on "/admin/curriculum/tags"
    When I follow "ENTRADA CURRICULUM TAG SET1"
      And I follow "table-view"
      And I follow "edit_<oldtitle>"
      And I should see "Edit Tag"
      And I should see "Tag Details"
      And I fill in "objective_title[en]" with "<newtitle>"
      And I press "Save"
    Then I should see "<newtitle>"

    Examples:
      | oldtitle        | newtitle         |
      | ENT01.TAG1      | ENT01.TAG1.0     |
      | ENT01.TAG2      | ENT01.TAG2.0     |
      | ENT01.TAG3      | ENT01.TAG3.0     |
      | ENT01.TAG4      | ENT01.TAG4.0     |


  @javascript
  Scenario Outline: Add child
    Given I am on "/admin/curriculum/tags"
    When I follow "ENTRADA CURRICULUM TAG SET1"
      And I follow "table-view"
      And I follow "add_<title>"
      And I should see "Add Tag"
      And I should see "Tag Details"
      And I fill in "objective_code" with "<code>"
      And I fill in "objective_title[en]" with "<childtitle>"
      And I press "Save"
    Then I should see "<childtitle>"

    Examples:
      | title            | childtitle       | code  |
      | ENT01.TAG1.0     | ENT01.TAG1.1     |ENT011 |
      | ENT01.TAG2.0     | ENT01.TAG2.1     |ENT021 |
      | ENT01.TAG3.0     | ENT01.TAG3.1     |ENT031 |
      | ENT01.TAG4.0     | ENT01.TAG4.1     |ENT041 |



  Scenario Outline: Add Tag with Global Tag Mapping
    Given I am on "/admin/curriculum/tags"
    When I follow "ENTRADA CURRICULUM TAG SET2"
      And I follow "Add Tag"
      And I fill in "objective_code" with "<code>"
      And I fill in "objective_title[en]" with "<title>"
      And I follow "Map Curriculum Tags"
      And I press "choose-tagset-btn"
      And I click on the text "<map>"
      And I press "Save & Close"
      Then I should see "You have successfully added <title> to the system"
    When I follow "click here"
      Then I should see "<title>"

    Examples:
      | code        | title       | map           |
      | SET02ENT04  | ENT02.TAG4  | ENT01.TAG4.0  |
      | SET02ENT03  | ENT02.TAG3  | ENT01.TAG3.0  |
      | SET02ENT02  | ENT02.TAG2  | ENT01.TAG2.0  |
      | SET02ENT01  | ENT02.TAG1  | ENT01.TAG1.0  |

  Scenario Outline: Check Tags mapped
    Given I am on "/admin/curriculum/tags"
    When I follow "ENTRADA CURRICULUM TAG SET1"
      And I follow "table-view"
      And I follow "edit_<title>"
      And I should see "Edit Tag"
      And I follow "Map Curriculum Tags"
      And I should see "<title> is mapped from:"
      And I should see "<mapped-from>"

    Examples:
      | title           | mapped-from   |
      | ENT01.TAG1.0    | ENT02.TAG1    |
      | ENT01.TAG2.0    | ENT02.TAG2    |
      | ENT01.TAG3.0    | ENT02.TAG3    |
      | ENT01.TAG4.0    | ENT02.TAG4    |


  Scenario Outline: Check Global Tag Mapping and History
    Given I am on "/admin/curriculum/tags"
    When I follow "ENTRADA CURRICULUM TAG SET2"
      And I follow "table-view"
      And I follow "edit_<title>"
      And I should see "Edit Tag"
      And I follow "Map Curriculum Tags"
      And I should see "Tags mapped to <title>"
      And I should see "<mapped-to>"
      And I follow "History"
    Then I should see "mapped [ENTRADA CURRICULUM TAG SET1] <mapped-to>"

    Examples:
      | title         | mapped-to     |
      | ENT02.TAG1    | ENT01.TAG1.0  |
      | ENT02.TAG2    | ENT01.TAG2.0  |
      | ENT02.TAG3    | ENT01.TAG3.0  |
      | ENT02.TAG4    | ENT01.TAG4.0  |