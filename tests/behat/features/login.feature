@login @smoke
Feature: Login

  As a site owner
  I want to be able to log in
  So that I can manage my site

  @api
  Scenario: Administrator user logs in
    Given I am logged in as a user with the "Administrator" role
    When I go to "admin"
    Then I save screenshot

  @api @javascript
  Scenario: Administrator user logs in using a real browser
    Given I am logged in as a user with the "Administrator" role
    When I go to "admin"
    Then I save screenshot
