Feature: Environment indicator access for site administrator

  As a site administrator
  I want to see the environment indicator on the administration sidebar
  So that I know which environment I'm working in

  @api @javascript
  Scenario: Site administrator sees the environment colour on the Navigation sidebar
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I visit "/"
    Then the element "body" with the attribute "style" and the value containing "--do-environment-indicator-color" should exist
    And the element ".admin-toolbar" should have the computed style "border-inline-start-width" of "8px"

  @api @javascript
  Scenario: Site administrator no longer sees the full width indicator strip
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I visit "/"
    Then I should not see a "#environment-indicator" element

  @api @javascript
  Scenario: Content author without the environment indicator permission sees no environment colour
    Given I am logged in as a user with the "civictheme_content_author" role
    When I visit "/"
    Then I should see an "#admin-toolbar" element
    And the element ".admin-toolbar" should have the computed style "border-inline-start-width" of "0px"
