Feature: Paragraphs library access for site administrator

  As a site administrator
  I want to access and manage the paragraphs library
  So that I can create and reuse paragraph components

  @api
  Scenario: Site administrator can access paragraphs library
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I visit "/admin/content/paragraphs"
    Then the response status code should be 200
    And I should see the text "Paragraphs library"

  @api
  Scenario: Site administrator can create paragraph library items
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I visit "/admin/content/paragraphs/add/default"
    Then the response status code should be 200