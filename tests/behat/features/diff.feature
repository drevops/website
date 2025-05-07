@api @p1
Feature: Diff module for revision comparison

  As a content author
  I want to compare different revisions of content
  So that I can track changes and understand what was modified

  Scenario: Content author can access and use revision comparison
    Given I am logged in as a user with the "Administrator" role
    When I visit "node/add/civictheme_page"
    And I fill in "Title" with "[TEST] Page for Diff"
    And I fill in "Summary" with "Initial summary"
    And I press "Save"
    When I visit the "civictheme_page" content edit page with the title "[TEST] Page for Diff"
    And I fill in "Summary" with "Updated summary"
    And I press "Save"

    When I visit the "civictheme_page" content revisions page with the title "[TEST] Page for Diff"
    And I select the radio button with the id "edit-node-revisions-table-0-select-column-two"
    And I select the radio button with the id "edit-node-revisions-table-1-select-column-one"
    And I press "Compare selected revisions"
    Then I should see "Changes to [TEST] Page for Diff"
    And I should see "Initial summary"
    And I should see "Updated summary"
