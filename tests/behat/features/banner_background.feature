@p1 @civictheme @drevops @banner
Feature: Banner background

  As a content editor
  I want to set a raster image or an SVG as the banner background
  So that pages can carry photographic and vector artwork behind their title

  Background:
    Given the following managed files exist:
      | path      | uri                        | status |
      | image.jpg | public://do_test/image.jpg | 1      |
      | image.svg | public://do_test/image.svg | 1      |

  Scenario: A raster background is drawn through the banner image style
    Given the following "civictheme_image" media exist:
      | name                          | field_c_m_image            |
      | [TEST] Banner background JPEG | public://do_test/image.jpg |
    And the following civictheme_page content exists:
      | title                   | status | field_c_n_banner_background   |
      | [TEST] Banner JPEG page | 1      | [TEST] Banner background JPEG |
    When I visit the "civictheme_page" content page with the title "[TEST] Banner JPEG page"
    Then the element ".ct-banner__inner" with the attribute "style" and a value containing "/styles/banner_background/" should exist

  Scenario: An SVG background is drawn from the original file
    Given the following "civictheme_icon" media exist:
      | name                         | field_c_m_icon             |
      | [TEST] Banner background SVG | public://do_test/image.svg |
    And the following civictheme_page content exists:
      | title                  | status | field_c_n_banner_background  |
      | [TEST] Banner SVG page | 1      | [TEST] Banner background SVG |
    When I visit the "civictheme_page" content page with the title "[TEST] Banner SVG page"
    Then the element ".ct-banner__inner" with the attribute "style" and a value containing "/sites/default/files/do_test/image.svg" should exist
    And the element ".ct-banner__inner" with the attribute "style" and a value containing "/styles/" should not exist
    # A vector has no derivative, so there is nothing for the preload to fetch.
    And the response should not contain "rel=\"preload\" href=\"/sites/default/files/do_test/image.svg\""

  Scenario: A page without a background of its own shows the sitewide aurora background
    Given the following civictheme_page content exists:
      | title                      | status |
      | [TEST] Banner default page | 1      |
    When I visit the "civictheme_page" content page with the title "[TEST] Banner default page"
    Then the element ".ct-banner__inner" with the attribute "style" and a value containing "/sites/default/files/icons/banner-aurora.svg" should exist

  @blog
  Scenario: A blog post accepts an SVG background
    Given the following "civictheme_icon" media exist:
      | name                              | field_c_m_icon             |
      | [TEST] Blog banner background SVG | public://do_test/image.svg |
    And the following "blog" content exists:
      | title                  | moderation_state | field_c_n_banner_background       | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Banner SVG post | published        | [TEST] Blog banner background SVG | large                 | inherit                | normal                      | both                       |
    When I visit the "blog" content page with the title "[TEST] Banner SVG post"
    Then the element ".ct-banner__inner" with the attribute "style" and a value containing "/sites/default/files/do_test/image.svg" should exist

  @project
  Scenario: A project accepts an SVG background
    Given the following "civictheme_icon" media exist:
      | name                                 | field_c_m_icon             |
      | [TEST] Project banner background SVG | public://do_test/image.svg |
    And the following "project" content exists:
      | title                     | moderation_state | field_c_n_banner_background          | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Banner SVG project | published        | [TEST] Project banner background SVG | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    When I visit the "project" content page with the title "[TEST] Banner SVG project"
    Then the element ".ct-banner__inner" with the attribute "style" and a value containing "/sites/default/files/do_test/image.svg" should exist
