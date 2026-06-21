@interactions
Feature: Front-end interactions library

  As a site visitor
  I want the site's motion and the mobile menu to behave as designed
  So that the experience feels polished and navigation works on a phone

  @api
  Scenario: Anonymous user can view the homepage with the interactions library
    Given I am an anonymous user
    When I go to the homepage
    Then the path should be "<front>"
    And I save screenshot

  @api @javascript
  Scenario: Content reveals as it scrolls into view
    Given I am an anonymous user
    When I go to the homepage
    Then injected reveal content becomes visible
