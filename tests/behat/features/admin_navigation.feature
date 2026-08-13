Feature: Administration navigation for site administrator

  As a site administrator
  I want to navigate the site administration through the core Navigation sidebar
  So that I have a single administration navigation that does not break contextual links

  @api @javascript
  Scenario: Site administrator can see the Navigation sidebar and not the classic Toolbar
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I visit "/"
    Then I should see an "#admin-toolbar" element
    And I should not see a "#toolbar-administration" element

  @api @javascript
  Scenario: Site administrator sees the administration top bar above the sticky site header
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I visit "/"
    Then the element ".top-bar" should stack above the element ".ct-page__header--sticky"

  @api @javascript
  Scenario: Site administrator sees the open mobile navigation above the administration top bar
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I visit "/"
    And I set the viewport to 390 by 844
    And I trigger the JS event "click" on the element ".ct-mobile-navigation-trigger"
    And I wait for 1 second
    Then the element ".ct-page__header--sticky" should stack above the element ".top-bar"
