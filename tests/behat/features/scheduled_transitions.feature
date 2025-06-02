@p1 @scheduled_transitions
Feature: Scheduled Transitions functionality for content roles

  As a site owner
  I want to ensure that Content Authors and Site Administrators have access to Scheduled Transitions
  So that they can schedule content state changes

  As a Content Author or Site Administrator
  I want to be able to schedule content transitions
  So that I can efficiently manage content publishing workflows

  Background:
    Given "civictheme_page" content:
      | title                     | field_c_n_summary             | status |
      | Test Page for Transitions | Test page for scheduled trans | 1      |

  @api
  Scenario: Content Author has permissions for scheduled transitions on CivicTheme Page
    Given I am logged in as a user with the "Content Author" role
    When I go to "admin/content"
    Then I should see the link "Test Page for Transitions"

    When I click "Test Page for Transitions"
    When I go to "admin/content/scheduled-transitions"
    Then the response status code should be 200

  @api
  Scenario: Site Administrator has permissions for scheduled transitions on CivicTheme Page
    Given I am logged in as a user with the "Site Administrator" role
    When I go to "admin/content"
    Then I should see the link "Test Page for Transitions"

    When I click "Test Page for Transitions"
    When I go to "admin/content/scheduled-transitions"
    Then the response status code should be 200

  @api
  Scenario: Content Author can access node scheduled transitions page directly
    Given I am logged in as a user with the "Content Author" role
    When I go to "admin/content"
    Then I should see the link "Test Page for Transitions"

    When I click "Test Page for Transitions"
    When I go to "admin/content/scheduled-transitions"
    Then I should see the text "Scheduled transitions"

  @api
  Scenario: Site Administrator can access node scheduled transitions page directly
    Given I am logged in as a user with the "Site Administrator" role
    When I go to "admin/content"
    Then I should see the link "Test Page for Transitions"

    When I click "Test Page for Transitions"
    When I go to "admin/content/scheduled-transitions"
    Then I should see the text "Scheduled transitions"
