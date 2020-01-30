Feature:
  Add users to the system

  Background:
    Given I am logged in as "admin" with password "apple123"

  Scenario: Add cohort
    Given I am on "/admin/groups"
    When I follow "Add New Cohort"
    And I fill in "group_name" with "Class of 2020"
    And I select "Cohort" from "group_type"
    And I press "Proceed"
    Then I should see "You have successfully added"

  Scenario Outline: Add user
    Given I am on "/admin/users"
    When I follow "Add New User"
      And I fill in "username" with "<username>"
      And I scroll to "firstname"
      And I fill in "firstname" with "<firstname>"
      And I fill in "lastname" with "<lastname>"
      And I fill in "email" with "<email>"
      And I scroll to "organisations"
      And I select "1" from "organisations"
      And I select "<group>" from "groups"
      And I select "<role>" from "roles"
      And I press "Add Permission"
      And I scroll to "add_user"
      And I press "Add User"
    Then I should see "You have successfully added <firstname> <lastname>"
    When I follow "click here"
    Then I should see "Manage Users"

    Examples:
      | username      | firstname                 | lastname               | email                     | group | role  |
      | student2018a  | firstname.2018a           | lastname.2018a         | student.2018a@example.org | 1     |  4    |
      | student2018b  | firstname.2018b           | lastname.2018b         | student.2018b@example.org | 1     |  4    |
      | student2019a  | firstname.2019a           | lastname.2019a         | student.2019a@example.org | 1     |  3    |
      | student2019b  | firstname.2019b           | lastname.2019b         | student.2019b@example.org | 1     |  3    |
      | student2020a  | firstname.2020a           | lastname.2020a         | student.2020a@example.org | 1     |  2    |
      | student2020b  | firstname.2020b           | lastname.2020b         | student.2020b@example.org | 1     |  2    |
      | facultyleca   | firstname.faculty.lec.a   | lastname.faculty.lec.a | faculty.lec.a@example.org | 3     |  20   |
      | facultydira   | firstname.faculty.dir.a   | lastname.faculty.dir.a | faculty.dir.a@example.org | 3     |  21   |
      | staffadmina   | firstname.staff.admin.a   | lastname.staff.admin.a | staff.admin.a@example.org | 5     |  27   |
      | staffcooda    | firstname.staff.cood.a    | lastname.staff.cood.a  | staff.cood.a@example.org  | 5     |  26   |
