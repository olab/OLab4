Feature:
  Create, manage and take exams. Create and manage exam folders.

  Background:
    Given I am logged in as "admin" with password "apple123"

  @javascript
  Scenario Outline: Add exam folders.
    Given I am on "http://entrada-1x-me.localhost/admin/exams/exams"
    Then I should see "Exams"
    When I click on "#add-folder"
    Then I should see "Add Folder"
    When I click on "#select_parent_folder_button"
      And I wait a second
    Then I should see "Index"
    When I click on ".qbf-folder > table > tbody > tr:nth-child(<parentFolderPosition>) > td.folder-selector"
      And I press "Done"
      And I fill in "folder_title" with "<folderTitle>"
      And I click on "#image-picker span.folder-image:nth-child(<colorPosition>)"
      And I wait a second
      And I press "Save"
    Then I should see "folder has been successfully added"
    Examples:
      | folderTitle        | parentFolderPosition | colorPosition |
      | Exam Folder #1 | 1                    | 4             |
      | Exam Folder #2 | 1                    | 5             |
      | Exam Folder #3 | 3                    | 6             |
      | Exam Folder #4 | 3                    | 7             |
      | Exam Folder #X | 4                    | 8             |
      | Exam Folder #6 | 4                    | 9             |

  @javascript
  Scenario: Browsing Folders and Editing a Question Folder
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/exams"
    Then I should see "Exam Folder #2"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(3) > span.bank-folder"
      And I wait for AJAX to finish
    Then I should see "Exam Folder #X"
    When I click on ".folder-edit-btn > button"
      And I wait a second
    Then I should see "Edit"
    When I follow "Edit"
    Then I should see "Edit Folder"
    When I fill in "folder_title" with "Exam Folder #5"
      And I fill in "folder_description" with "Testing folder editing."
      And I click on "#image-picker span.folder-image:nth-child(1)"
      And I press "Save"
    Then I should see "folder has been successfully updated"
    When I follow "click here"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(3) > span.bank-folder"
      And I wait a second
    Then I should see "Exam Folder #5"

  @javascript
  Scenario: Browsing Folders and Deleting a Folder
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/exams"
    Then I should see "Exam Folder #1"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(2) > span.bank-folder"
      And I wait for AJAX to finish
    Then I should see "Exam Folder #3"
    When I click on ".folder-edit-btn > button"
      And I wait a second
    Then I should see "Delete"
    When I follow "Delete"
      And I wait a second
    Then I should see "approved to delete"
    When I press "delete-folder-modal-delete"
    Then I should see "Successfully deleted 1"
    When I reload the page
      And I wait for AJAX to finish
    Then I should not see "Exam Folder #3"

  @javascript
  Scenario: Creating an Exam and Adding Questions
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/exams"
    Then I should see "Exam Folder #1"
      And I follow "Add Exam"
      And I wait a second
      And I fill in "exam_title" with "Exam #1"
      And I click on ".qbf-folder > table > tbody > tr:nth-child(3) > td.folder-selector"
      And I press "Add Exam"
    Then I should see "Successfully"
    When I follow "Add Individual Question(s)"
    Then I should see "Question Folder #1"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(1) > span.bank-folder"
      And I wait for AJAX to finish
      And I click on "div#folders > ul > li.bank-folder-li:nth-child(2) > span.bank-folder"
      And I wait for AJAX to finish
    Then I should see "MCV Lorem ipsum"
    When I click on "#select-all-question-bank"
      And I press "Attach Selected"
    Then I should see "Successfully added"
      And I should see "MCV Lorem ipsum"

#  @javascript
#  Scenario: Creating and attaching a question to an existing exam
#    Given I am on "http://entrada-1x-me.localhost/admin/exams/exams"
#    Then I should see "Exam Folder #1"
#    When I click on "div#folders > ul > li.bank-folder-li:nth-child(2) > span.bank-folder"
#    And I wait for AJAX to finish
#    Then I should see "Exam #1"
#    When I follow "Exam #1"
#    Then I should see "SA Lorem ipsum"
#    When I follow "Add Individual Question(s)"
#    # When I click on "open-dropdown"...
#    # And I click on "add-and-attach-btn"...
#    And I click on "button.btn-success"
#    And I follow "Add & Attach New Question"
#    And I wait a second
#    Then I should see "Create New Question"
#    When I fill in texteditor on field "question-text" with "MCH From exam."
#    And I select "Multiple Choice Horizontal" from "question-type"
#    And I click on "#select_parent_folder_button"
#    And I wait a second
#    And I click on ".qbf-folder > table > tbody > tr:nth-child(2) > td.sub-folder-selector"
#    And I wait for AJAX to finish
#    And I wait a second
#    And I click on ".qbf-folder > table > tbody > tr:nth-child(1) > td.folder-selector"
#    And I press "Done"
#    And I fill in texteditor on field "question_answer_1" with "Wrong Answer MCH (from exam)"
#    And I fill in texteditor on field "question_answer_2" with "Correct Answer MCH (from exam)"
#    And I click on ".add-answer"
#    And I wait for AJAX to finish
#    And I fill in texteditor on field "question_answer_3" with "Another Wrong Answer MCH (from exam)"
#    And I click on "div.exam-question-answer:nth-child(2) span.answer-correct"
#    And I follow "Add Curriculum Tag"
#    And I wait a second
#    And I click on "#objective_title_1"
#    And I wait for AJAX to finish
#    And I click on "#check_objective_4"
#    And I click on "#check_objective_5"
#    And I follow "Done"
#    And I press "Save"
#    Then I should see "added 1 questions to the exam"
#    And I wait for AJAX to finish
#    Then I should see "MCH From exam."
#    And I should see "Correct Answer MCH (from exam)"
