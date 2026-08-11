@p1 @drevops @our_work
Feature: Our work page

  As a site visitor
  I want to browse the projects that have been delivered
  So that I can judge the depth of the work before making contact

  @api
  Scenario: The work section is reachable and leads the primary navigation
    Given I am an anonymous user
    When I go to "/work"
    Then the response status code should be 200
    # The link leads the menu, so the first item is the one that must point here.
    And the element ".ct-navigation__menu .ct-menu__item--level-0:first-child .ct-menu__item__link" with the attribute "href" and the value "/work" should exist

  @api @testmode
  Scenario: Each list shows only the published projects tagged for it
    Given the following "project" content:
      | title                         | moderation_state | field_c_n_topics   | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Client project 01      | published        | Custom development | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Client project 02      | published        | Custom development | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Client project 03      | published        | Custom development | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Client project 04      | published        | Custom development | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Client project 05      | published        | Custom development | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Client project 06      | published        | Custom development | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Open source project 01 | published        | Open source        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Open source project 02 | published        | Open source        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Open source project 03 | published        | Open source        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Open source project 04 | published        | Open source        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Open source project 05 | published        | Open source        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Open source project 06 | published        | Open source        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Client draft           | draft            | Custom development | 2025            | ongoing           | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I go to "/work"
    Then the response status code should be 200
    And the element ".block-field-blocknodecivictheme-pagefield-c-n-components > .ct-list:first-child" should contain 6 elements matching ".ct-promo-card"
    And the element ".block-field-blocknodecivictheme-pagefield-c-n-components > .ct-list:last-child" should contain 6 elements matching ".ct-promo-card"
    And I should see "[TEST] Client project 01" in the ".block-field-blocknodecivictheme-pagefield-c-n-components > .ct-list:first-child" element
    And I should see "[TEST] Open source project 01" in the ".block-field-blocknodecivictheme-pagefield-c-n-components > .ct-list:last-child" element
    And I should not see the text "[TEST] Client draft"
