@metatags @p0
Feature: Page content metatags

  As a site owner
  I want to ensure that the default metatags are present for all pages
  In order to improve SEO and social sharing

  @api
  Scenario: CivicTheme page content type contains default metatags
    Given the following civictheme_page content:
      | title              | status | field_c_n_summary                           |
      | Test Metatags Page | 1      | This is a test summary for metatags testing |
    When I visit the "civictheme_page" content page with the title "Test Metatags Page"
    Then the response should contain "<title>Test Metatags Page | "
    And the response should contain "<meta name=\"description\" content=\"This is a test summary for metatags testing\""
    And the response should contain "<link rel=\"canonical\" href=\""
    And the response should contain "test-metatags-page"
    And the meta tag should exist with the following attributes:
      | name    | robots        |
      | content | index, follow |
    And the meta tag should exist with the following attributes:
      | property | og:type |
      | content  | website |
    And the meta tag should exist with the following attributes:
      | property | og:title           |
      | content  | Test Metatags Page |
    And the meta tag should exist with the following attributes:
      | property | og:description                              |
      | content  | This is a test summary for metatags testing |
    And the meta tag should exist with the following attributes:
      | name    | twitter:card        |
      | content | summary_large_image |
    And the meta tag should exist with the following attributes:
      | name    | twitter:title      |
      | content | Test Metatags Page |
    And the meta tag should exist with the following attributes:
      | name    | twitter:description                         |
      | content | This is a test summary for metatags testing |

  @api
  Scenario: Pages expose the branded Open Graph and Twitter share image
    Given I am an anonymous user
    When I go to the homepage
    Then the response should contain "<meta property=\"og:image\" content=\"http"
    And the response should contain "<meta name=\"twitter:image\" content=\"http"
    And the response should contain "/themes/custom/drevops/dist/assets/images/og-image.png\" />"

  @api
  Scenario: Content editor can access the metatags override fields on a page
    Given I am logged in as a user with the "civictheme_content_author" role
    And the following civictheme_page content:
      | title                  | status |
      | Test Metatags Override | 1      |
    When I visit the "civictheme_page" content edit page with the title "Test Metatags Override"
    Then I should see "Metatags"
    And I should see "Open Graph"
    And I should see "Twitter Cards"
