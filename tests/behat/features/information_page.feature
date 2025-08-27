@information @smoke
Feature: Information page

  As a site visitor
  I want to access the Information page
  So that I can view information content on the site

  @api
  Scenario: Anonymous user visits Information page
    When I visit the "civictheme_page" content page with the title "Information"
    Then the response status code should be 200
    And I should see "Information"
    And I save screenshot

  @api
  Scenario: Information page is accessible via alias
    When I go to "/information"
    Then the response status code should be 200
    And I should see "Information"
    And I save screenshot