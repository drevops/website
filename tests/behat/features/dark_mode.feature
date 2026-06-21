@dark_mode
Feature: Dark colour scheme

  As a site visitor
  I want every part of the site to use the dark colour scheme
  So that the experience is consistent and matches the redesign

  @api
  Scenario: The site chrome renders in the dark scheme
    Given I am an anonymous user
    When I go to the homepage
    Then the response should contain "ct-theme-dark"

  @api
  Scenario: A published page renders in the dark scheme
    Given the following civictheme_page content:
      | title            | status |
      | [TEST] Dark Page | 1      |
    When I visit the "civictheme_page" content page with the title "[TEST] Dark Page"
    Then the response should contain "ct-theme-dark"
