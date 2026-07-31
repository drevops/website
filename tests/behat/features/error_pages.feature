@p1 @drevops @errorpages
Feature: Built-in error pages

  As a site visitor
  I want an error page to look like the rest of the site and offer me a way out
  So that I can tell I am still on DrevOps and carry on browsing

  # Drupal answers these routes with a bare sentence and no wrapper of its own,
  # which lands on the light page background in the browser's default font. The
  # theme wraps that sentence in a dark, full-width content band, the same way
  # every other band on the site is painted over the light page.

  @api
  Scenario: Access denied renders in the site design
    Given I am an anonymous user
    When I visit "/admin"
    Then the response status code should be 403
    And I should see a ".ct-page.ct-theme-light" element
    And I should see "You are not authorized to access this page." in the ".ct-layout__main .ct-basic-content.ct-theme-dark.ct-basic-content--with-background" element

  @api
  Scenario: The Access denied message band spans the full width of the page
    Given I am an anonymous user
    When I visit "/admin"
    Then I should see a ".ct-layout__inner.container-fluid" element
    And I should not see a ".ct-layout__inner.container" element

  @api
  Scenario: Access denied offers a way back to the site
    Given I am an anonymous user
    When I visit "/admin"
    Then I should see "Return to homepage" in the ".ct-layout__main .ct-button--primary" element
    And the element ".ct-layout__main .ct-button--primary" with the attribute "href" and the value "/" should exist

  @api
  Scenario: Access denied does not offer a menu of pages the visitor cannot reach
    Given I am an anonymous user
    When I visit "/admin"
    Then I should not see a ".ct-layout__sidebar_top_left" element

  @api
  Scenario: Page not found renders in the site design
    Given I am an anonymous user
    When I visit "/test-page-that-does-not-exist"
    Then the response status code should be 404
    And I should see a ".ct-page.ct-theme-light" element
    And I should see "The requested page could not be found." in the ".ct-layout__main .ct-basic-content.ct-theme-dark.ct-basic-content--with-background" element
    And I should see "Return to homepage" in the ".ct-layout__main .ct-button--primary" element
    And I should not see a ".ct-layout__sidebar_top_left" element

  # The error page treatment hides the sidebars and releases the layout from
  # its container. Both are page-level changes, so a page that is not an error
  # page is the place to catch them leaking.
  @api
  Scenario: Pages that are not error pages keep their sidebar and contained layout
    Given I am an anonymous user
    When I go to "/about-us"
    Then the response status code should be 200
    And I should see a ".ct-layout__sidebar_top_left" element
    And I should see a ".ct-layout__inner.container" element
    And I should not see a ".ct-layout__main .ct-basic-content--with-background" element
