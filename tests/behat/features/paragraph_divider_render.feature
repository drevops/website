@p1 @civictheme @drevops @divider
Feature: Divider render

  Background:
    Given the following managed files:
      | path      | uri                        | status |
      | image.jpg | public://do_test/image.jpg | 1      |

    And the following media "civictheme_image" exist:
      | name                    | field_c_m_image |
      | [TEST] DO Image | image.jpg  |

    And "civictheme_page" content:
      | title                      | status |
      | [TEST] Page Divider test 1 | 1      |
      | [TEST] Page Divider test 2 | 1      |

  @api @javascript
  Scenario: CivicTheme page content type page can be viewed by anonymous with Divider light with vertical spacing and left image
    Given I am an anonymous user
    And the following fields for the paragraph "divider" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Divider test 1":
      | field_p_alignment              | left                    |
      | field_p_size                   | large                    |
      | field_c_p_theme                | light                   |
      | field_c_p_vertical_spacing     | both                    |
      | field_c_p_image                | [TEST] DO Image |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Divider test 1"
    Then I should see an "article .ct-divider" element
    And I should see an "article .ct-divider.ct-theme-light" element
    And I should not see an "article .ct-divider.ct-theme-dark" element
    And I should see an "article .ct-divider.ct-vertical-spacing-inset--both" element
    And I should see an "article .ct-divider.ct-text-align-left" element
    And I should see an "article .ct-divider.ct-size--large" element
    And save screenshot

  @api @javascript
  Scenario: CivicTheme page content type page can be viewed by anonymous with Divider dark with vertical spacing and right image
    Given I am an anonymous user
    And the following fields for the paragraph "divider" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Divider test 2":
      | field_p_alignment              | right                   |
      | field_p_size                   | large                   |
      | field_c_p_theme                | dark                    |
      | field_c_p_vertical_spacing     | both                    |
      | field_c_p_image                | [TEST] DO Image |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Divider test 2"
    Then I should see an "article .ct-divider" element
    And I should see an "article .ct-divider.ct-theme-dark" element
    And I should not see an "article .ct-divider.ct-theme-light" element
    And I should see an "article .ct-divider.ct-vertical-spacing-inset--both" element
    And I should see an "article .ct-divider.ct-text-align-right" element
    And I should see an "article .ct-divider.ct-size--large" element
    And save screenshot
