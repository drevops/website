@p1 @preview_link
Feature: Preview links for unpublished content

  As a content editor
  I want to send someone a link to content that is not published yet
  So that they can review it without needing an account on the site

  Background:
    Given the following "civictheme_page" content:
      | title                     | moderation_state | field_c_n_summary        |
      | [TEST] Preview Draft Page | draft            | [TEST] Draft page summary |

  @api
  Scenario: Content Author generates a preview link for a draft page
    Given I am logged in as a user with the "Content Author" role
    When I visit the "civictheme_page" content page with the title "[TEST] Preview Draft Page"
    And I click "Preview Link"
    Then the response status code should be 200
    And I should see "Preview link"
    And I should see the button "Save and regenerate preview link"

  @api
  Scenario: Site Administrator generates a preview link for a draft page
    Given I am logged in as a user with the "Site Administrator" role
    When I visit the "civictheme_page" content page with the title "[TEST] Preview Draft Page"
    And I click "Preview Link"
    Then the response status code should be 200
    And I should see the button "Save and regenerate preview link"

  @api
  Scenario: Editor without the permission is not offered a preview link
    Given I am logged in as a user with the "access content, access administration pages, access content overview, view any unpublished content" permissions
    When I visit the "civictheme_page" content page with the title "[TEST] Preview Draft Page"
    Then I should not see the link "Preview Link"

  @api
  Scenario: Site visitor cannot reach a draft page without a preview link
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Preview Draft Page"
    Then the response status code should be 403
