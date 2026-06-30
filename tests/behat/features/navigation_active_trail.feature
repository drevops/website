@navigation @p1 @drevops
Feature: Navigation active trail

  As a site visitor
  I want the navigation to highlight the section I am viewing
  So that I can orient myself within the site

  # The primary, side and mobile navigations all render the same
  # "Primary Navigation" menu and share one active trail. A page nested under a
  # section landing page (for example a blog post under "/blog") is a separate
  # node from the landing page, so without a path-based active trail the section
  # link is never highlighted. These scenarios assert the path-based active trail
  # via the always-present primary navigation.

  Background:
    Given the following "civictheme_page" content:
      | title                   | status |
      | [TEST] Navtrail Section | 1      |
      | [TEST] Navtrail Article | 1      |
    And the "civictheme_page" content "[TEST] Navtrail Section" has the path alias "/test-navtrail-section"
    And the "civictheme_page" content "[TEST] Navtrail Article" has the path alias "/test-navtrail-section/test-navtrail-article"
    And the following menu links exist in the menu "Primary Navigation":
      | title                   | enabled | uri                             |
      | [TEST] Navtrail Section | 1       | internal:/test-navtrail-section |

  @api
  Scenario: Section parent is highlighted when viewing a nested child page
    Given I am an anonymous user
    When I go to "/test-navtrail-section/test-navtrail-article"
    Then I should see "[TEST] Navtrail Section" in the ".ct-primary-navigation .ct-menu__item--active-trail" element

  @api
  Scenario: Section parent is not highlighted on an unrelated page
    Given I am an anonymous user
    When I go to the homepage
    Then I should not see a ".ct-primary-navigation .ct-menu__item--active-trail" element
