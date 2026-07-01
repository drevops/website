@api
Feature: Publishing API-authored content through the editorial UI

  As a reviewer who also holds the content authoring API permission
  I want to publish a draft page through the editorial UI
  So that content authored through the API can be reviewed and go live

  Scenario: An account with the content authoring API permission publishes a draft page
    Given I am logged in as a user with the "access content, access administration pages, access content overview, edit any civictheme_page content, view any unpublished content, use content authoring api, use civictheme_editorial transition create_new_draft, use civictheme_editorial transition publish" permissions
    And the following "civictheme_page" content:
      | title                | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | API draft to publish | draft            | large                 | inherit                | normal                      | both                       |
    When I visit the "civictheme_page" content edit page with the title "API draft to publish"
    And I select "Published" from "moderation_state[0][state]"
    And I press "Save"
    Then the "civictheme_page" content "API draft to publish" should be published
