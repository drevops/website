@tracking @google
Feature: Google Analytics is injected
  As a site user
  I want to ensure Google Analytics script is present on the page

  @api @javascript
  Scenario: Check Google Analytics 4 script on homepage
    Given I am an anonymous user
    When I am on the homepage
    And the response should contain "https://www.googletagmanager.com/gtag/js?id=G-"
