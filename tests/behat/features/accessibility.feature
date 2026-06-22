@a11y @accessibility:warning
Feature: Accessibility

  As a site visitor
  I want the site to meet accessibility standards
  So that all users can access content regardless of ability

  @api @javascript
  Scenario: Anonymous user visits the homepage
    Given I am an anonymous user
    When I go to the homepage

  @api @javascript
  Scenario: Anonymous user visits the login page
    Given I am an anonymous user
    When I go to "/user/login"

  @api @javascript
  Scenario: Anonymous user visits the contact page
    Given I am an anonymous user
    When I go to "/contact"

  @api @javascript
  Scenario: Anonymous user visits the search page
    Given I am an anonymous user
    When I go to "/search"

  @api @javascript
  Scenario: Anonymous user visits a content page
    Given I am an anonymous user
    And the following "civictheme_page" content:
      | title                  | status |
      | [TEST] Accessible page | 1      |
    When I visit the "civictheme_page" content page with the title "[TEST] Accessible page"
