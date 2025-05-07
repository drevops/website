@homepage @smoke
Feature: Homepage

  As a site owner
  I want to ensure that the homepage is accessible
  In order to provide a good user experience

  @api
  Scenario: Anonymous user visits homepage
    Given I go to the homepage
    And the path should be "/"
    Then I save screenshot

  @api @javascript
  Scenario: Anonymous user visits homepage using a real browser
    Given I go to the homepage
    And the path should be "/"
    Then I save screenshot
