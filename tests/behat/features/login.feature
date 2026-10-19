@login @smoke
Feature: Login

  As a site administrator
  I want to log into the system
  So that I can access administrative functions and manage the site

  Scenario: Administrator user logs in
    Given I am logged in as a user with the role "Administrator"
    When I go to "admin"
    Then the path should be "/admin"
    And I save screenshot

  @javascript
  Scenario: Administrator user logs in using a real browser
    Given I am logged in as a user with the role "Administrator"
    When I go to "admin"
    Then the path should be "/admin"
    And I save screenshot

  Scenario: Login page has correct vertical spacing class
    Given I go to "/user/login"
    Then I should see a ".block-system-main-block.ct-vertical-spacing--both" element

  Scenario: Password reset page has correct vertical spacing class
    Given I go to "/user/password"
    Then I should see a ".block-system-main-block.ct-vertical-spacing--both" element
