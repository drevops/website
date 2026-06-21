@branding @smoke
Feature: Brand chrome

  As a site visitor
  I want the header and footer to carry the DrevOps brand identity
  So that the logo and typography match the redesign across every page

  @api
  Scenario: Header and footer display the DrevOps dark logo
    Given I am an anonymous user
    When I go to the homepage
    Then the response should contain "logo_primary_dark_desktop.svg"
    And the response should contain "logo_primary_dark_mobile.svg"
