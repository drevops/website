@navigation
Feature: Site header navigation

  As a site visitor
  I want a clear, sticky header that works on desktop and mobile
  So that I can navigate the site from any device

  @api @smoke
  Scenario: The header shows the brand, the primary menu and the call to action
    Given the following menu links exist in the menu "Primary Navigation":
      | title           | enabled | uri                  |
      | [TEST] Nav Item | 1       | internal:/user/login |
    And I am an anonymous user
    When I go to the homepage
    Then the response should contain "logo_primary_dark_desktop.svg"
    And I should see the link "[TEST] Nav Item"
    And I should see the link "Talk to us"

  @api
  Scenario: The current section's menu link shows the active state
    Given the following menu links exist in the menu "Primary Navigation":
      | title        | enabled | uri                  |
      | [TEST] Login | 1       | internal:/user/login |
    And I am an anonymous user
    When I go to "/user/login"
    Then I should see "[TEST] Login" in the ".component-nav-active" element

  @api @javascript
  Scenario: The header stays available at the top of the page when scrolling
    Given I am an anonymous user
    When I am on the homepage
    Then the site header stays pinned to the top of the viewport on scroll

  @api @javascript
  Scenario: The mobile menu opens, locks scrolling and closes on link activation
    Given the following menu links exist in the menu "Primary Navigation":
      | title           | enabled | uri                  |
      | [TEST] Nav Item | 1       | internal:/user/login |
    And I am an anonymous user
    When I go to the homepage
    Then the site mobile menu opens, locks scrolling and closes on link activation
