@p1 @civictheme @drevops @imagezoom
Feature: Image zoom

  As a site visitor
  I want to open an image placed in content in a lightbox and zoom into it
  So that I can read the details of diagrams and screenshots

  Background:
    Given the following managed files exist:
      | path            | uri                              | status |
      | image-large.png | public://do_test/image-large.png | 1      |

    And the following "civictheme_image" media exist:
      | name                 | field_c_m_image                  | uuid                                 |
      | [TEST] DO Zoom image | public://do_test/image-large.png | 3f6c1a2e-8b7d-4c5e-9a1f-2d3e4b5c6d7e |

    And the following "civictheme_page" content exists:
      | title                  | status |
      | [TEST] Page Image zoom | 1      |

    And the following fields for the "civictheme_content" paragraph exist in the field "field_c_n_components" within the "civictheme_page" "node" entity with the value "[TEST] Page Image zoom" in the field "title":
      | field_c_p_theme            | light                                                                                                       |
      | field_c_p_vertical_spacing | both                                                                                                        |
      | field_c_p_content:value    | <drupal-media data-entity-type="media" data-entity-uuid="3f6c1a2e-8b7d-4c5e-9a1f-2d3e4b5c6d7e"></drupal-media> |
      | field_c_p_content:format   | civictheme_rich_text                                                                                        |

  Scenario: An image embedded in content renders through an image style and links to its original
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Image zoom"
    Then I should see an "article figure.ct-figure a.photoswipe img" element
    And the element "article figure.ct-figure a.photoswipe img" with the attribute "src" and a value containing "/styles/wide/" should exist
    And the element "article figure.ct-figure a.photoswipe" with the attribute "href" and a value containing "/sites/default/files/do_test/image-large.png" should exist
    And the element "article figure.ct-figure a.photoswipe" with the attribute "data-pswp-width" and the value "2400" should exist
    And the element "article figure.ct-figure a.photoswipe" with the attribute "data-pswp-height" and the value "1200" should exist

  @javascript
  Scenario: Clicking an image embedded in content opens its original in a zoomable lightbox
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Image zoom"
    Then the element "article figure.ct-figure a.photoswipe img" should have the CSS property "cursor" with the value "zoom-in"
    When I click on the element "article figure.ct-figure a.photoswipe img"
    And I wait for 1 second
    Then I should see a ".pswp.pswp--open.pswp--zoom-allowed" element
    And the element ".pswp__img" with the attribute "src" and a value containing "/sites/default/files/do_test/image-large.png" should exist
    When I click on the element ".pswp__button--zoom"
    And I wait for 1 second
    Then I should see a ".pswp.pswp--zoomed-in" element
    And save screenshot

  @javascript
  Scenario: Closing the lightbox returns focus to the image link
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Image zoom"
    And I click on the element "article figure.ct-figure a.photoswipe img"
    And I wait for 1 second
    And I click on the element ".pswp__button--close"
    And I wait for 1 second
    Then I should not see a ".pswp" element
    And the element "article figure.ct-figure a.photoswipe" should have keyboard focus
