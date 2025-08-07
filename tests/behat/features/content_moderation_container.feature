Feature: Content moderation block container class

  As a site administrator
  I want to ensure the moderation block has proper container styling
  So that it displays correctly when the sidebar is missing

  @api
  Scenario: Moderation block has container class
    Given I am logged in as a user with the "administrator" role
    And "civictheme_page" content:
      | title           | moderation_state | field_c_n_hide_sidebar |
      | Test Page Draft | draft            | 1                      |
    When I visit the "civictheme_page" content page with the title "Test Page Draft"
    Then I should see a ".block-extra-field-blocknodecivictheme-pagecontent-moderation-control.container" element
