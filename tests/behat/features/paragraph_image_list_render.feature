@p1 @civictheme @drevops @imagelist
Feature: Image list render

  As a site visitor
  I want to see a band of images rendered on a page
  So that I can recognise the organisations and projects behind the site

  Background:
    Given the following managed files:
      | path      | uri                        | status |
      | image.jpg | public://do_test/image.jpg | 1      |

    And the following media "civictheme_image" exist:
      | name                  | field_c_m_image |
      | [TEST] DO Image one   | image.jpg       |
      | [TEST] DO Image two   | image.jpg       |
      | [TEST] DO Image three | image.jpg       |

    And the following "civictheme_page" content:
      | title                        | status |
      | [TEST] Page Image list test 1 | 1      |
      | [TEST] Page Image list test 2 | 1      |

  @api
  Scenario: CivicTheme page renders a light Image list with a heading and every image
    Given I am an anonymous user
    And the following fields for the paragraph "image_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Image list test 1":
      | field_c_p_title            | [TEST] Image list title                                        |
      | field_c_p_theme            | light                                                          |
      | field_c_p_vertical_spacing | both                                                           |
      | field_c_p_background       | 0                                                              |
      | field_p_images             | [TEST] DO Image one, [TEST] DO Image two, [TEST] DO Image three |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Image list test 1"
    Then I should see an "article .ct-image-list" element
    And I should see an "article .ct-image-list.ct-theme-light" element
    And I should not see an "article .ct-image-list.ct-theme-dark" element
    And I should see an "article .ct-image-list.ct-vertical-spacing-inset--both" element
    And I should not see an "article .ct-image-list.ct-image-list--with-background" element
    And I should see 3 ".ct-image-list__item" elements
    And I should see 3 ".ct-image-list__item img" elements
    And I should see the text "[TEST] Image list title"
    And save screenshot

  @api
  Scenario: CivicTheme page renders a dark Image list with a background
    Given I am an anonymous user
    And the following fields for the paragraph "image_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Image list test 2":
      | field_c_p_title            | [TEST] Image list dark title |
      | field_c_p_theme            | dark                         |
      | field_c_p_vertical_spacing | top                          |
      | field_c_p_background       | 1                            |
      | field_p_images             | [TEST] DO Image one          |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Image list test 2"
    Then I should see an "article .ct-image-list" element
    And I should see an "article .ct-image-list.ct-theme-dark" element
    And I should not see an "article .ct-image-list.ct-theme-light" element
    And I should see an "article .ct-image-list.ct-image-list--with-background" element
    And I should see an "article .ct-image-list.ct-vertical-spacing-inset--top" element
    And I should see 1 ".ct-image-list__item" elements
    And I should see the text "[TEST] Image list dark title"
    And save screenshot

  @api
  Scenario: CivicTheme page renders nothing for an Image list that has no images
    Given I am an anonymous user
    And the following fields for the paragraph "image_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Image list test 1":
      | field_c_p_title            | [TEST] Image list empty title |
      | field_c_p_theme            | light                         |
      | field_c_p_vertical_spacing | both                          |
      | field_c_p_background       | 0                             |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Image list test 1"
    Then I should not see an "article .ct-image-list" element
    And I should not see the text "[TEST] Image list empty title"
    And save screenshot
