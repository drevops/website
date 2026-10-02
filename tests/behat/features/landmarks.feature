@a11y @landmarks @p1
Feature: Page landmarks

  As a site visitor
  I want every page to have exactly 1 main landmark
  So that assistive technology announces a single main region I can jump to

  # A Layout Builder display renders a second layout inside the page layout.
  # Scenarios for those displays assert the inner layout is present, so they
  # cannot pass on a page that never rendered it.

  @api
  Scenario: Site visitor sees 1 main landmark on the homepage
    Given I am an anonymous user
    When I go to the homepage
    Then I should see a "[data-layout-builder-layout]" element
    And I should see 1 "main, [role=main]" element

  @api
  Scenario: Site visitor sees 1 main landmark on a page
    Given the following "civictheme_page" content:
      | title                 | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Landmarks page | published        | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Landmarks page"
    Then I should see a "[data-layout-builder-layout]" element
    And I should see 1 "main, [role=main]" element

  @api
  Scenario: Site visitor sees 1 main landmark on a blog post
    Given the following "blog" content:
      | title                 | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Landmarks post | published        | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "blog" content page with the title "[TEST] Landmarks post"
    Then I should see a "[data-layout-builder-layout]" element
    And I should see 1 "main, [role=main]" element

  @api
  Scenario: Site visitor sees 1 main landmark on a project
    Given the following "project" content:
      | title                    | moderation_state | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Landmarks project | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Landmarks project"
    Then I should see a "[data-layout-builder-layout]" element
    And I should see 1 "main, [role=main]" element

  @api
  Scenario: Site visitor sees 1 main landmark on an event
    Given the following "civictheme_event" content:
      | title                  | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Landmarks event | published        | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "civictheme_event" content page with the title "[TEST] Landmarks event"
    Then I should see a "[data-layout-builder-layout]" element
    And I should see 1 "main, [role=main]" element

  @api
  Scenario: Site visitor sees 1 main landmark on the login page
    Given I am an anonymous user
    When I go to "/user/login"
    Then I should see 1 "main, [role=main]" element

  @api
  Scenario: Site visitor sees 1 main landmark on the page not found page
    Given I am an anonymous user
    When I go to "/test-landmarks-page-that-does-not-exist"
    Then the response status code should be 404
    And I should see 1 "main, [role=main]" element

  @api
  Scenario: Site visitor sees 1 main landmark on the access denied page
    Given I am an anonymous user
    When I go to "/admin"
    Then the response status code should be 403
    And I should see 1 "main, [role=main]" element

  @api
  Scenario: The skip link points to a single focusable target
    Given I am an anonymous user
    When I go to the homepage
    Then the element "body > .ct-skip-link a" with the attribute "href" and the value "#main-content" should exist
    And I should see 1 "#main-content" element
    And the element "#main-content" with the attribute "tabindex" and the value "-1" should exist

  @api @javascript
  Scenario: The skip link moves keyboard focus to the main content
    Given I am an anonymous user
    When I go to the homepage
    And I focus on the element "body > .ct-skip-link a"
    And I click on the element "body > .ct-skip-link a"
    Then the element "#main-content" should have keyboard focus
