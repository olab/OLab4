Feature:
  Run Entrada Installation and configure University settings

  Scenario: Install Entrada
    Given I am on "/setup"
    When I press "Continue"
      And I press "Continue"
      And I fill in "database_password" with "password"
      And I fill in "entrada_database" with "entrada_test"
      And I fill in "auth_database" with "entrada_test_auth"
      And I fill in "clerkship_database" with "entrada_test_clerkship"
      And I scroll to "continue-button"
      And I press "Continue"
      And I fill in "admin_email" with "entrada-admin@example.org"
      And I fill in "admin_username" with "admin"
      And I fill in "admin_password" with "apple123"
      And I fill in "re_admin_password" with "apple123"
      And I press "Continue"
      And I press "Continue"
      And I press "View Site"
    Then I should see "Entrada ME Login"

  Scenario: Set Profile
    Given I am logged in as "admin" with password "apple123"
    And I wait for AJAX to finish
    And I wait a second
    When I check the "privacy_level_2" radio button
      And I press "Proceed"
      And I follow "Logout"
      And I am logged in as "admin" with password "apple123"
    Then I should not see "Privacy Level Setting"

  Scenario: Edit University
    Given I am logged in as "admin" with password "apple123"
    And I am on "/admin/settings"
    When I follow "Your University"
      And I follow "Edit Your University"
      And I fill in "aamc_institution_id" with "AAMC1"
      And I fill in "aamc_institution_name" with "AAMC name"
      And I fill in "aamc_program_id" with "AAMC program id"
      And I fill in "aamc_program_name" with "AAMC program name"
      And I press "Save"
      And I follow "click here"
    Then I should see "AAMC1"
    And I should see "AAMC name"
    And I should see "AAMC program id"
    And I should see "AAMC program name"

