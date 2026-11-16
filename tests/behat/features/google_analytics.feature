@analytics @p2
Feature: Google Analytics

  As a site owner
  I want only production to report to Google Analytics
  In order to keep test traffic out of the production reports

  @javascript
  Scenario: Google Analytics 4 script is not loaded outside production
    Given I am an anonymous user
    When I am on the homepage
    Then the response should not contain "https://www.googletagmanager.com/gtag/js?id=G-"
    And the response should contain "@drevops/axel@"
