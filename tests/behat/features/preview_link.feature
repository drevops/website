@p1 @preview_link
Feature: Preview links for unpublished content

  As a content editor
  I want to send someone a link to content that is not published yet
  So that they can review it without needing an account on the site

  Background:
    Given the following "civictheme_page" content exists:
      | title                     | moderation_state | field_c_n_summary        |
      | [TEST] Preview Draft Page | draft            | [TEST] Draft page summary |

  Scenario: Content Author generates a preview link for a draft page
    Given I am logged in as a user with the role "Content Author"
    When I visit the "civictheme_page" content page with the title "[TEST] Preview Draft Page"
    And I follow "Preview Link"
    Then the response status code should be 200
    And I should see "Preview link"

    When I press "Save and regenerate preview link"
    Then the response status code should be 200
    And I should see "Expiry:"
    And the response should contain "/preview-link/node/"

  Scenario: Site Administrator can reach the preview link form for a draft page
    Given I am logged in as a user with the role "Site Administrator"
    When I visit the "civictheme_page" content page with the title "[TEST] Preview Draft Page"
    And I follow "Preview Link"
    Then the response status code should be 200
    And I should see the button "Save and regenerate preview link"

  Scenario: Editor without the permission is not offered a preview link
    Given I am logged in as a user with the permissions "access content, access administration pages, access content overview, view any unpublished content"
    When I visit the "civictheme_page" content page with the title "[TEST] Preview Draft Page"
    Then I should not see the link "Preview Link"

  Scenario: Site visitor cannot reach a draft page without a preview link
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Preview Draft Page"
    Then the response status code should be 403
