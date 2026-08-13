@p1 @civictheme @drevops @logostrip
Feature: Logo strip render

  As a site visitor
  I want to see a band of logos rendered on a page
  So that I can recognise the organisations and projects behind the site

  Background:
    Given the following managed files:
      | path      | uri                        | status |
      | image.jpg | public://do_test/image.jpg | 1      |

    And the following media "civictheme_image" exist:
      | name                 | field_c_m_image |
      | [TEST] DO Logo one   | image.jpg       |
      | [TEST] DO Logo two   | image.jpg       |
      | [TEST] DO Logo three | image.jpg       |

    And the following "civictheme_page" content:
      | title                         | status |
      | [TEST] Page Logo strip test 1 | 1      |
      | [TEST] Page Logo strip test 2 | 1      |

  @api
  Scenario: CivicTheme page renders a light Logo strip with a heading and every logo
    Given I am an anonymous user
    And the following fields for the paragraph "logo_strip" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Logo strip test 1":
      | field_c_p_title            | [TEST] Logo strip title                              |
      | field_c_p_theme            | light                                                |
      | field_c_p_vertical_spacing | both                                                 |
      | field_c_p_background       | 0                                                    |
      | field_p_logos              | [TEST] DO Logo one, [TEST] DO Logo two, [TEST] DO Logo three |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Logo strip test 1"
    Then I should see an "article .ct-logo-strip" element
    And I should see an "article .ct-logo-strip.ct-theme-light" element
    And I should not see an "article .ct-logo-strip.ct-theme-dark" element
    And I should see an "article .ct-logo-strip.ct-vertical-spacing-inset--both" element
    And I should not see an "article .ct-logo-strip.ct-logo-strip--with-background" element
    And I should see 3 ".ct-logo-strip__item" elements
    And I should see 3 ".ct-logo-strip__item img" elements
    And I should see the text "[TEST] Logo strip title"
    And save screenshot

  @api
  Scenario: CivicTheme page renders a dark Logo strip with a background
    Given I am an anonymous user
    And the following fields for the paragraph "logo_strip" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Logo strip test 2":
      | field_c_p_title            | [TEST] Logo strip dark title |
      | field_c_p_theme            | dark                         |
      | field_c_p_vertical_spacing | top                          |
      | field_c_p_background       | 1                            |
      | field_p_logos              | [TEST] DO Logo one           |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Logo strip test 2"
    Then I should see an "article .ct-logo-strip" element
    And I should see an "article .ct-logo-strip.ct-theme-dark" element
    And I should not see an "article .ct-logo-strip.ct-theme-light" element
    And I should see an "article .ct-logo-strip.ct-logo-strip--with-background" element
    And I should see an "article .ct-logo-strip.ct-vertical-spacing-inset--top" element
    And I should see 1 ".ct-logo-strip__item" elements
    And I should see the text "[TEST] Logo strip dark title"
    And save screenshot

  @api
  Scenario: CivicTheme page renders nothing for a Logo strip that has no logos
    Given I am an anonymous user
    And the following fields for the paragraph "logo_strip" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Logo strip test 1":
      | field_c_p_title            | [TEST] Logo strip empty title |
      | field_c_p_theme            | light                         |
      | field_c_p_vertical_spacing | both                          |
      | field_c_p_background       | 0                             |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Logo strip test 1"
    Then I should not see an "article .ct-logo-strip" element
    And I should not see the text "[TEST] Logo strip empty title"
    And save screenshot
