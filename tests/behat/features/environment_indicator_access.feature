Feature: Environment indicator access for site administrator

  As a site administrator
  I want to see the environment indicator
  So that I know which environment I'm working in

  @api @javascript
  Scenario: Site administrator can see environment indicator toolbar styling
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I visit "/"
    Then the element ".toolbar-menu-administration" with the attribute "style" and the value containing "border-left-color" should exist
