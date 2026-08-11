@p1 @drevops @our_work
Feature: Our work page

  As a site visitor
  I want to reach the work section from anywhere on the site
  So that I can find the projects that have been delivered

  # CI provisions from the production database, so editorial copy and the
  # number of published projects change without a code change. Assertions
  # here stay on the route and the navigation structure; project rendering,
  # listing and pagination are covered against fixtures in
  # project_content_type.feature and
  # paragraph_civictheme_automated_list_pager.feature.

  @api
  Scenario: The work section is reachable and leads the primary navigation
    Given I am an anonymous user
    When I go to "/work"
    Then the response status code should be 200
    # The link leads the menu, so the first item is the one that must point here.
    And the element ".ct-navigation__menu .ct-menu__item--level-0:first-child .ct-menu__item__link" with the attribute "href" and the value "/work" should exist
