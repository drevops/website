@tracking @google
Feature: Google Analytics is injected
  As a site user
  I want to ensure Google Analytics script is present on the page

  @api @javascript
  Scenario: Check Google Analytics 4 script on homepage
    Given I am an anonymous user
    When I am on the homepage
    Then the response should contain "https://www.googletagmanager.com/gtag/js?id=G-"

  @api @javascript
  Scenario: Google Analytics 4 script should not be present for logged in users
    Given I am logged in as a user with the "authenticated" role
    When I am on the homepage
    Then the response should not contain "https://www.googletagmanager.com/gtag/js?id=G-"
