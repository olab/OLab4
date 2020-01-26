Feature:
  Configure curriculum layout, add cohort and create a new course

  Background:
    Given I am logged in as "admin" with password "apple123"

  Scenario: Add Curriculum Layout
    Given I am on "/admin/curriculum"
    When I follow "Curriculum Layout"
    And I follow "Add Layout"
    And I fill in "curriculum_type_name" with "Clerkship"
    And I scroll to "curriculum_form"
    And I add Curriculum Period
      | start      | end        | name    |
      | 2018-09-01 | 2020-05-31 | Class of 2020 |
      | 2017-09-01 | 2019-05-31 | Class of 2019 |
      | 2016-09-01 | 2018-05-31 | Class of 2018 |
    And I scroll to "delete_selected"
    And I press "Save"
    Then I should see "You have successfully added Clerkship to the system."
    When I follow "click here"
    Then I should see "Clerkship"

  Scenario: Remove Curriculum Layout
    Given I am on "/admin/curriculum"
    When I follow "Curriculum Layout"
    And I check the "remove_ids[]" check button with "5" value
    And I check the "remove_ids[]" check button with "6" value
    And I check the "remove_ids[]" check button with "7" value
    And I check the "remove_ids[]" check button with "8" value
    And I press "Delete"
    And I press "Confirm Delete"
    Then I should not see "Term 5"
    And I should not see "Term 6"
    And I should not see "Term 7"
    And I should not see "Term 8"

  Scenario Outline: Edit Curriculum Layout
    Given I am on "/admin/curriculum"
    When I follow "Curriculum Layout"
    And I follow "<typeName>"
    And I scroll to "curriculum_form"
    And I press "Add Curriculum Period"
    And I fill in "start_add-1" with "<start>"
    And I fill in "finish_add-1" with "<end>"
    And I fill in "curriculum_period_title_add-1" with "<title>"
    And I press "Save"
    Then I should see "You have successfully updated <typeName> to the system."
    When I follow "click here"
    Then I should see "<typeName>"
    Examples:
      | typeName  | start       | end         | title         |
      | Term 1    | 2015-09-01  | 2015-12-31  | Class of 2015 |
      | Term 2    | 2016-01-01  | 2016-05-31  | Class of 2016 |

  Scenario: Add course
    Given I am on "/admin/courses"
    When I follow "Add A New Course"
      And I select "Clerkship" from "curriculum_type_id"
      And I fill in "course_name" with "Entrada 101"
      And I fill in "course_code" with "ENT101"
      And I press "Proceed"
    Then I should see "successfully created Entrada 101"
    When I scroll to "course-objectives-section"
      And I follow "Show Curriculum Tag Sets"
      And I click in the following:
        |Curriculum Objectives      |
        |Medical Expert             |
        |Clinical Presentations     |
        |Clinical Assessment        |
        |ME2.1 History and Physical |
      And I scroll to "objective_title_11"
      And I check "check_objective_34"
      And I scroll to "objective_title_10"
      And I check "check_objective_94"
      And I scroll to "course-enrolment-section"
      And I add Curriculum Period to course
        |name                                                 |type         |typeValue     |
        |Class of 2020 - September 1st, 2018 to May 31st, 2020|Cohort       |Class of 2020  |
        |Class of 2019 - September 1st, 2017 to May 31st, 2019|Cohort       |Class of 2019  |
        |Class of 2018 - September 1st, 2016 to May 31st, 2018|Cohort       |Class of 2018  |
      And I scroll to "course-syllabus-section"
      And I press "Save"
    Then I should see "You have successfully updated"
    When I follow "Content"
      And I fill in "course_url" with "www.ENT101.com"
      And I fill in texteditor on field "course_description" with "desc"
      And I fill in texteditor on field "course_message" with "msg"
      And I scroll to "course-objectives-section"
      And I press "Save"
    Then I should see "You have successfully updated"
    And the "course_url" field should contain "www.ENT101.com"
    And the "course_description" field should contain "desc"
    And the "course_message" field should contain "msg"

  Scenario Outline: Course Enrolment
    Given I am on "/admin/courses"
    When I follow "Entrada 101"
      And I follow "Enrolment"
      And I select "<name>" from "cperiod_select"
    Then I should see "<group>"
    And I should see "<students> learners"

    Examples:
      |name                                                 |group            |students |
      |Class of 2020 - September 1st, 2018 to May 31st, 2020|Class of 2020    |2        |
      |Class of 2019 - September 1st, 2017 to May 31st, 2019|Class of 2019    |2        |

  Scenario Outline: EmptyCourseGroup
    Given I am on "/admin/courses"
    When I follow "Entrada 101"
      And I follow "Groups"
      And I follow "Add New Groups"
      And I fill in "prefix" with "<groupName>"
      And I fill in "empty_group_number" with "<emptyGroups>"
      And I press "Add"
    Then I should see "You have successfully added <emptyGroups> course groups to the system"
    When I follow "click here"
    Then I should see "Course Groups"

    Examples:
      | groupName             | emptyGroups |
      | TestGroupEmpty        | 3           |
      | SecondTestGroupEmpty  | 2           |

  Scenario Outline: NotEmptyCourseGroup
    Given I am on "/admin/courses"
    When I follow "Entrada 101"
      And I follow "Groups"
      And I follow "Add New Groups"
      And I fill in "prefix" with "<groupName>"
      And I check the "Automatically populate groups" radio button
      And I scroll to "group_number"
      And I fill in "group_number" with "<groupNumber>"
      And I scroll to "gender_section"
      And I press "Add"
    Then I should see "You have successfully added <groupNumber> course groups to the system"
    When I follow "click here"
    Then I should see "Course Groups"

    Examples:
      | groupName             | groupNumber |
      | TestPopulateGroup     | 3           |
      | SecondPopGroup        | 2           |