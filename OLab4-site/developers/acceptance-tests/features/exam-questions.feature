Feature:
  Create and manage exam questions, create and manage question folders.

  Background:
    Given I am logged in as "admin" with password "apple123"

  @javascript
  Scenario Outline: Add Question Folders
    Given I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Questions"
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
    | Question Folder #1 | 1                    | 2             |
    | Question Folder #2 | 1                    | 4             |
    | Question Folder #3 | 2                    | 5             |
    | Question Folder #4 | 2                    | 7             |
    | Question Folder #X | 3                    | 8             |
    | Question Folder #5 | 3                    | 9             |

  @javascript
  Scenario: Browsing Folders and Editing a Question Folder
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Question Folder #2"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(2) > span.bank-folder"
      And I wait a second
    Then I should see "Question Folder #X"
    When I click on ".folder-edit-btn > button"
      And I wait a second
    Then I should see "Edit"
    When I follow "Edit"
    Then I should see "Edit Folder"
    When I fill in "folder_title" with "Question Folder #5"
      And I fill in "folder_description" with "Testing folder editing."
      And I click on "#image-picker span.folder-image:nth-child(1)"
      And I press "Save"
    Then I should see "folder has been successfully updated"
    When I follow "click here"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(2) > span.bank-folder"
      And I wait a second
    Then I should see "Question Folder #5"

  @javascript
  Scenario: Browsing Folders and Deleting a Folder
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Question Folder #1"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(1) > span.bank-folder"
      And I wait a second
    Then I should see "Question Folder #3"
    When I click on ".folder-edit-btn > button"
      And I wait a second
    Then I should see "Delete"
    When I follow "Delete"
      And I wait a second
    Then I should see "will be deleted"
    When I press "delete-folders-modal-delete"
    Then I should see "Successfully deleted"

  @javascript
  Scenario: Creating a Multiple Choice Vertical Question
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Add A New Question"
    When I click on "#add-question"
    Then I should see "Create New Question"
    When I fill in texteditor on field "question-text" with "MCV Lorem ipsum dolor sit amet."
      And I click on "#select_parent_folder_button"
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(2) > td.sub-folder-selector"
      And I wait for AJAX to finish
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(1) > td.folder-selector"
      And I press "Done"
      And I fill in texteditor on field "question_answer_1" with "Right Answer MCV"
      And I fill in texteditor on field "question_answer_2" with "Wrong Answer MCV"
      And I click on ".add-answer"
      And I wait for AJAX to finish
      And I fill in texteditor on field "question_answer_3" with "Another Wrong Answer"
      And I click on "div.exam-question-answer:nth-child(1) span.answer-correct"
      And I follow "Add Curriculum Tag"
      And I wait a second
      And I click on "#objective_title_200"
      And I wait for AJAX to finish
      And I click on "#check_objective_300"
      And I click on "#check_objective_295"
      And I follow "Done"
      And I press "Save"
    Then I should see "successfully been added"
    When I follow "click here"
      And I wait for AJAX to finish
    Then I should see "MCV Lorem ipsum"
    Then I should see "Right Answer MCV"

  @javascript
  Scenario: Creating a Multiple Choice Vertical Question (Multiple Responses)
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Add A New Question"
    When I click on "#add-question"
    Then I should see "Create New Question"
    When I fill in texteditor on field "question-text" with "MCV_MR Lorem ipsum dolor sit amet."
      And I select "Multiple Choice Vertical (multiple responses)" from "question-type"
      And I click on "#select_parent_folder_button"
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(2) > td.sub-folder-selector"
      And I wait for AJAX to finish
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(1) > td.folder-selector"
      And I press "Done"
      And I fill in texteditor on field "question_answer_1" with "Correct Answer MCV_MR"
      And I fill in texteditor on field "question_answer_2" with "Another Correct Answer MCV_MR"
      And I click on ".add-answer"
      And I wait for AJAX to finish
      And I fill in texteditor on field "question_answer_3" with "Wrong Answer MCV_MR"
      And I click on "div.exam-question-answer:nth-child(1) span.answer-correct"
      And I click on "div.exam-question-answer:nth-child(2) span.answer-correct"
      And I follow "Add Curriculum Tag"
      And I wait a second
      And I click on "#objective_title_200"
      And I wait for AJAX to finish
      And I click on "#check_objective_300"
      And I click on "#check_objective_295"
      And I follow "Done"
      And I press "Save"
    Then I should see "successfully been added"
    When I follow "click here"
      And I wait for AJAX to finish
    Then I should see "MCV_MR Lorem ipsum"
      And I should see "Correct Answer MCV_MR"

  @javascript
  Scenario: Creating a Multiple Choice Horizontal Question
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Add A New Question"
    When I click on "#add-question"
    Then I should see "Create New Question"
    When I fill in texteditor on field "question-text" with "MCH Lorem ipsum dolor sit amet."
      And I select "Multiple Choice Horizontal" from "question-type"
      And I click on "#select_parent_folder_button"
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(2) > td.sub-folder-selector"
      And I wait for AJAX to finish
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(1) > td.folder-selector"
      And I press "Done"
      And I fill in texteditor on field "question_answer_1" with "Wrong Answer MCH"
      And I fill in texteditor on field "question_answer_2" with "Correct Answer MCH"
      And I click on ".add-answer"
      And I wait for AJAX to finish
      And I fill in texteditor on field "question_answer_3" with "Another Wrong Answer MCH"
      And I click on "div.exam-question-answer:nth-child(2) span.answer-correct"
      And I follow "Add Curriculum Tag"
      And I wait a second
      And I click on "#objective_title_1"
      And I wait for AJAX to finish
      And I click on "#check_objective_4"
      And I click on "#check_objective_5"
      And I follow "Done"
      And I press "Save"
    Then I should see "successfully been added"
    When I follow "click here"
      And I wait for AJAX to finish
    Then I should see "MCH Lorem ipsum"
      And I should see "Correct Answer MCH"

  @javascript
  Scenario: Creating a Multiple Choice Horizontal Question (Multiple Responses) with all or none Grading Scheeme
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Add A New Question"
    When I click on "#add-question"
    Then I should see "Create New Question"
    When I fill in texteditor on field "question-text" with "MCH_MR Lorem ipsum dolor sit amet."
      And I select "Multiple Choice Horizontal (multiple responses)" from "question-type"
      And I select "All or none" from "grading_scheme"
      And I click on "#select_parent_folder_button"
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(2) > td.sub-folder-selector"
      And I wait for AJAX to finish
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(1) > td.folder-selector"
      And I press "Done"
      And I fill in texteditor on field "question_answer_1" with "Correct Answer MCH_MR"
      And I fill in texteditor on field "question_answer_2" with "Wrong Answer MCH_MR"
      And I click on ".add-answer"
      And I wait for AJAX to finish
      And I fill in texteditor on field "question_answer_3" with "Another Correct Answer MCH_MR"
      And I click on "div.exam-question-answer:nth-child(1) span.answer-correct"
      And I click on "div.exam-question-answer:nth-child(2) span.answer-correct"
      And I follow "Add Curriculum Tag"
      And I wait a second
      And I click on "#objective_title_1"
      And I wait for AJAX to finish
      And I click on "#check_objective_4"
      And I click on "#check_objective_5"
      And I follow "Done"
      And I press "Save"
    Then I should see "successfully been added"
    When I follow "click here"
      And I wait for AJAX to finish
    Then I should see "MCH_MR Lorem ipsum"
      And I should see "Correct Answer MCH_MR"

  @javascript
  Scenario: Creating an Essay Question
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Add A New Question"
    When I click on "#add-question"
    Then I should see "Create New Question"
    When I fill in texteditor on field "question-text" with "ESSAY Lorem ipsum dolor sit amet."
      And I select "Essay" from "question-type"
      And I click on "#select_parent_folder_button"
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(2) > td.sub-folder-selector"
      And I wait for AJAX to finish
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(1) > td.folder-selector"
      And I press "Done"
      And I fill in "question_correct_text" with "SA Correct Answer"
      And I follow "Add Curriculum Tag"
      And I wait a second
      And I click on "#objective_title_1"
      And I wait for AJAX to finish
      And I click on "#check_objective_4"
      And I click on "#check_objective_5"
      And I follow "Done"
      And I press "Save"
    Then I should see "successfully been added"
    When I follow "click here"
      And I wait for AJAX to finish
    Then I should see "ESSAY Lorem ipsum"

  @javascript
  Scenario: Creating a Short Answer Question
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Add A New Question"
    When I click on "#add-question"
    Then I should see "Create New Question"
    When I fill in texteditor on field "question-text" with "SA Lorem ipsum dolor sit amet."
      And I select "Short Answer" from "question-type"
      And I click on "#select_parent_folder_button"
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(2) > td.sub-folder-selector"
      And I wait for AJAX to finish
      And I wait a second
      And I click on ".qbf-folder > table > tbody > tr:nth-child(1) > td.folder-selector"
      And I press "Done"
      And I fill in "question_correct_text" with "SA Correct Answer"
      And I follow "Add Curriculum Tag"
      And I wait a second
      And I click on "#objective_title_1"
      And I wait for AJAX to finish
      And I click on "#check_objective_4"
      And I click on "#check_objective_5"
      And I follow "Done"
      And I press "Save"
    Then I should see "successfully been added"
    When I follow "click here"
      And I wait for AJAX to finish
    Then I should see "SA Lorem ipsum"

  @javascript
  Scenario: Tagging Questions Using the Bulk Tag Tool
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Question Folder #1"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(1) > span.bank-folder"
      And I wait for AJAX to finish
    Then I should see "Question Folder #4"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(2) > span.bank-folder"
      And I wait for AJAX to finish
    Then I should see "ESSAY Lorem ipsum"
    When I click on "#toggle-all-question-bank"
    Then I should not see "Knowledge for Practice"
    When I click on "#select-all-question-bank"
      And I press "Actions"
      And I follow "Tag Questions"
      And I wait a second
    Then I should see "Curriculum Tag Sets"
    When I click on "#objective_title_2328"
      And I wait a second
      And I click on "#check_objective_2330"
      And I click on "#apply_tags"
      And I reload the page
      And I wait for AJAX to finish
      And I click on "#toggle-all-question-bank"
    Then I should see "Knowledge for Practice"

  @javascript
  Scenario: Importing Exam Questions From Plain Text
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    When I follow "Import"
    Then I should see "Import Exam Questions"
    When I fill in "question_text" with:
      """
      1. What is your favorite color (mc_v) ?
      a. red
      b. green
      c. blue
      answer: a
      type: mc_v
      locked: b

      2. What is your favorite color (mc_v_m)?
      a. blue
      b. red
      c. green
      d. pink
      answer: a, d
      type: mc_v_m
      locked: c, d
      curriculum_tags: 100, 101

      3. What's the sky color (mc_h)?
      a. red
      b. green
      c. blue
      answer: c
      type: mc_h

      4. What is your favorite cake (mc_h_m)?
      a. blueberry
      b. red velvet
      c. green stuff
      d. pink lemon
      answer: a
      answer: d
      type: mc_h_m
      curriculum_tags: 100

      5. What's the name of the Gandalf horse (short)?
      type: short

      6. How can we stop the political polarization around the world (essay)?
      type: essay

      7. Match one of the questions choices (match).
      a. This is the first answer choice.
      b. This is the second answer choice.
      c. This is the third answer choice.
      item: This is the first item stem, its correct answer is choice C.
      answer: c
      item: This is the second item stem, its correct answer is choice A.
      answer: a
      type: match

      8. Write about yourself.
      type: text

      9. The _?_ are dark and _?_, and I have miles to go before I _?_ (f_n_b).
      answer: woods|forest|lives
      answer: deep|pure|bright
      answer: see|cry|sleep
      type: fnb

      10. The _?_ are dark and _?_, and I have miles to go before I _?_ (f_n_b_2).
      answer: woods|forest|lives, deep|pure|bright, see|cry|sleep
      type: fnb
      """
      And I select "Question Folder #2" from "folder_id"
      And I press "Import Questions"
    Then I should see "Write about yourself."
    Then I should see "favorite cake"
    Then I should see "woods"
    When I press "Confirm Question Import"
    Then I should see "Successfully imported 10"
    When I follow "click here"
    Then I should see "Write about yourself."
    Then I should see "favorite cake"
    Then I should not see "woods"

  @javascript
  Scenario: Deleting a Folder With Questions Inside Should Fail
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Question Folder #1"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(1) > span.bank-folder"
      And I wait a second
    Then I should see "Question Folder #4"
    When I click on ".folder-edit-btn > button"
      And I wait a second
    Then I should see "Delete"
    When I follow "Delete"
      And I wait a second
    Then I should not see "will be deleted"
      And I should see "can't be deleted"
    When I press "delete-folders-modal-delete"
    Then I should see "Nothing to delete"

  @javascript
  Scenario: Editing a Question
    Given  I am on "http://entrada-1x-me.localhost/admin/exams/questions"
    Then I should see "Question Folder #1"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(1) > span.bank-folder"
      And I wait for AJAX to finish
    Then I should see "Question Folder #4"
    When I click on "div#folders > ul > li.bank-folder-li:nth-child(2) > span.bank-folder"
      And I wait for AJAX to finish
    Then I should see "ESSAY Lorem ipsum"
    When I click on "div.exam-question:nth-child(1) a.edit-question"
      And I fill in texteditor on field "question-text" with "ESSAY EDITED Lorem ipsum."
      And I press "Save"
    Then I should see "successfully updated"
    When I follow "click here"
      And I wait for AJAX to finish
    Then I should see "ESSAY EDITED Lorem"
