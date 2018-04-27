### This feature file defines all the steps required for testing the Assessment & Evaluation module

Feature:
  Assessment and Evaluation

  ## Items:	Create 1 of each of the Item Types
  Background:
    Given I am logged in as "admin" with password "apple123"

  Scenario Outline: Create Assessment Response Descriptors
    Given I am on "/admin/settings/manage/assessmentresponsecategories?org=1"
    When I follow "Add Category"
    And I fill in "Category" with "<category-text>"
    And I press "Save"
    Then I should see "Successfully added the Assessment Response Category"
    When I follow "click here"
    And I fill in "Search:" with "<category-text>"
    And I wait for AJAX to finish
    Then I should see "<category-text>"

    Examples:
    | category-text |
    | A Descriptor Example 1 |
    | A Descriptor Example 2 |
    | A Descriptor Example 3 |

  Scenario Outline: Create Item with Responses and Curriculum Tags
    Given I am on "/admin/assessments"
    Then I follow "Items"
    And I follow "Add A New Item"
    Then I select "<item-type>" from "item-type"
    And I fill in "item-text" with "<item-text>"
    When I should see "Item Responses"
     And I scroll to "response-section"
     And I fill in "item_response_1" with "Response Text"
     And I press "descriptor-1"
     And I fill in "Begin typing to search..." with "A Descriptor Example 1"
     And I click on the text "A Descriptor Example 1"
     And I fill in "item_response_2" with "Response Text"
     And I press "descriptor-2"
     And I fill in "Begin typing to search..." with "A Descriptor Example 2"
     And I click on the text "A Descriptor Example 2"
    Then I should see "Associated Curriculum Tags"
      And I click on the text "AAMC Physician Competencies Reference Set"
      And I check "objectives[2329]"
    When I press "Save"
    Then I should see "The item has been successfully added"

    Examples:
      | item-type | item-text |
      |     1     | This is a item text example 1 |
      |     2     | This is a item text example 2 |
      |     3     | This is a item text example 3 |
      |     4     | This is a item text example 4 |
      |     5     | This is a item text example 5 |
      |     6     | This is a item text example 6 |
      |     11     | This is a item text example 11 |
      |     12     | This is a item text example 12 |

  Scenario Outline: Create "Numeric Field" Item
    Given I am on "/admin/assessments"
    Then I follow "Items"
    And I follow "Add A New Item"
    Then I select "<item-type>" from "item-type"
    And I fill in "item-text" with "<item-text>"
    When I should see "Associated Curriculum Tags"
    And I click on the text "AAMC Physician Competencies Reference Set"
    And I check "objectives[2329]"
    Then I press "Save"
    And I should see "The item has been successfully added"

    Examples:
      | item-type | item-text |
      |     10     | This is a item text example 10 |

  Scenario Outline: Create "Free Text" and "Date Selector" Items
    Given I am on "/admin/assessments"
    Then I follow "Items"
    And I follow "Add A New Item"
    Then I select "<item-type>" from "item-type"
    And I fill in "item-text" with "<item-text>"
    Then I press "Save"
    And I should see "The item has been successfully added"

    Examples:
      | item-type | item-text |
      |     7     | This is a item text example 7 |
      |     8     | This is a item text example 8 |

#  Scenario: Create a "Field Note" Item
#    Given I am on "/admin/assessments"
#    Then I follow "Items"
#    And I follow "Add A New Item"
#    Then I select "13" from "item-type"
#    And I fill in "item-text" with "This is a item text example"
#    Then I scroll to "field-note-response"
#    And I press "field-note-objective-btn"
#    Then I follow link where "data-label" is "AAMC Physician Competencies Reference Set"
#    And I click on the text "Abdominal Distension"
#    Then I fill in texteditor on field "field-note-response-1" with "This is a Level of Competency text example"
#    Then I fill in texteditor on field "field-note-response-2" with "This is a Level of Competency text example"
#    Then I fill in texteditor on field "field-note-response-3" with "This is a Level of Competency text example"
#    Then I fill in texteditor on field "field-note-response-4" with "This is a Level of Competency text example"
#    Then I press "Save"
#    And I should see "The item has been successfully added"

## Create a Group Item

  Scenario: Create a Group Item
    Given I am on "/admin/assessments"
    Then I follow "Items"
    Then I follow "Grouped Items"
    And I follow "Add A New Grouped Item"
    And I fill in "Grouped Item Name" with "Grouped Item Example"
    Then I press "Add Grouped Item"
    And I should see "Successfully created Grouped Item."
    Then I scroll to "display-notice-box"
    Then I follow "Attach Existing Item(s)"
    Then I select "2" items to attach
    And I press "Attach Selected"
    And I should see "Successfully"
    Then I scroll to "display-success-box"
    Then I fill in "Description" with "Text example for description"
    And I press "Save"
    Then I should see "Successfully updated Grouped Item."

## Create 2 Forms (one which will be used in the Gradebook, the other can be used later to test completion of an Evaluation form)

  Scenario Outline: Create Form
    Given I am on "/admin/assessments"
    Then I follow "Forms"
    And I follow "Add Form"
    And I fill in "Form Name" with "<form-name>"
    Then I press "Add Form"
    Then I should see "Successfully created the form."
    And I follow "Add Item(s)"
    Then I select "2" items to attach
    And I press "Attach Selected"
    And I should see "Successfully"
    Then I scroll to "display-success-box"
    Then I fill in "Form Description" with "Text example for description"
    And I press "Save"
    Then I should see "Successfully updated the form."

    Examples:
    | form-name |
    | Form Example 1 |
    | Form Example 2 |
