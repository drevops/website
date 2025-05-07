@analytics @p2
Feature: Google Analytics

  As a site owner
  I want to ensure that Google Analytics is correctly configured
  In order to track user interactions and gather analytics data

  @api @javascript
  Scenario: Check Google Analytics 4 script on homepage
    Given I am an anonymous user
    When I am on the homepage
    Then the response should contain "https://www.googletagmanager.com/gtag/js?id=G-"

  @api @javascript
  Scenario: Google Analytics 4 script should not be present for logged in users
    Given I am logged in as a user with the "Administrator" role
    When I am on the homepage
    Then the response should not contain "https://www.googletagmanager.com/gtag/js?id=G-"
