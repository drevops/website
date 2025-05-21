@p1 @entity_clone @user_roles
Feature: Entity Clone permissions for Content Author role

  As a site owner
  I want to ensure that the Content Author role has all needed Entity Clone permission.

  As a Content Author
  I want to be able to clone content entities
  So that I can efficiently create similar content items

  @api
  Scenario: Check that Content Author role has entity clone permissions
    Given I am logged in as a user with the "administrator" role
    When I visit "/admin/people/permissions/civictheme_content_author"
    Then I should see the text "Entity Clone"
    And the "Clone all Paragraph entities." checkbox should be checked
    And the "Clone all Content entities." checkbox should be checked
    And the "Clone all Taxonomy term entities." checkbox should be checked

  @api
  Scenario: Content Author can clone CivicTheme Page content
    Given I am logged in as a user with the "Content Author" role
    And "civictheme_page" content:
      | title                      | field_c_n_summary     | status |
      | Test CivicTheme Clone Page | Test page for cloning | 1      |
    When I go to "admin/content"
    Then I should see the link "Test CivicTheme Clone Page"

    When I click "Test CivicTheme Clone Page"
    Then I should see the link "Clone"

    When I click "Clone"
    Then I should see "Clone Content"

    When I press "Clone"
    Then I should see text matching "The entity Test CivicTheme Clone Page \(\d+\) of type node was cloned"
    And I should see "Test CivicTheme Clone Page - Cloned"
