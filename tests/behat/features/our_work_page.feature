@p1 @drevops @our_work
Feature: Our work page

  As a site visitor
  I want to browse the projects that have been delivered
  So that I can judge the depth of the work before making contact

  @api
  Scenario: The page opens the primary navigation and introduces the work
    Given I am an anonymous user
    When I go to "/work"
    Then the response status code should be 200
    And I should see the text "Work you can go and look at."
    And I should see the text "Client work"
    And I should see the text "Open Source work"
    # The link leads the menu, so the first item is the one that must point here.
    And the element ".ct-navigation__menu .ct-menu__item--level-0:first-child .ct-menu__item__link" with the attribute "href" and the value "/work" should exist

  @api
  Scenario: Published projects are listed as promo cards, twelve to a page
    Given the following "project" content:
      | title                     | moderation_state | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Our work project 01 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 02 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 03 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 04 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 05 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 06 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 07 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 08 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 09 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 10 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 11 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 12 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work project 13 | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Our work draft      | draft            | 2025            | ongoing           | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I go to "/work"
    Then the response status code should be 200
    # The page carries more than one list, so the count is scoped to the first
    # one - the paginated list of client projects - rather than to the whole
    # main region. Asserting the page fills rather than the total keeps projects
    # already on the site from changing the outcome.
    And the element "[data-component-id='civictheme:list']" should contain 12 elements matching ".ct-promo-card"
    And should see a ".ct-pagination__items" element
    And I should not see the text "[TEST] Our work draft"
