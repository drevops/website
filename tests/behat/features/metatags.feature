@seo @metatags
Feature: Page content metatags
  As a site user, I want to verify all default metatags appear for CivicTheme Page content type

  @api
  Scenario: CivicTheme page content type contains default metatags
    Given civictheme_page content:
      | title               | status | field_c_n_summary                           |
      | Test Metatags Page  | 1      | This is a test summary for metatags testing |
    When I visit the "civictheme_page" content page with the title "Test Metatags Page"
    Then the response should contain "<title>Test Metatags Page | "
    And the response should contain "<meta name=\"description\" content=\"This is a test summary for metatags testing\""
    And the response should contain "<link rel=\"canonical\" href=\""
