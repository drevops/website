@p1 @drevops @media
Feature: Media library

  As a content editor
  I want the media library to show each item as it looks
  So that I can pick the right image or icon

  Background:
    Given the following managed files exist:
      | path      | uri                                | status |
      | image.svg | public://do_test/library-image.svg | 1      |
    And the following "civictheme_icon" media exist:
      | name                | field_c_m_icon                     |
      | [TEST] Library icon | public://do_test/library-image.svg |
    And I am logged in as a user with the role "administrator"

  Scenario: An SVG icon shows its own artwork in the media library
    When I go to "/admin/content/media-grid"
    Then the element ".media-library-item img" with the attribute "src" and a value containing "/sites/default/files/do_test/library-image.svg" should exist

  Scenario: An SVG thumbnail in the media table is sized by the thumbnail style
    When I go to "/admin/content/media"
    Then the element "table img[src$='/do_test/library-image.svg']" with the attribute "width" and the value "100" should exist
    And the element "table img[src$='/do_test/library-image.svg']" with the attribute "height" and the value "25" should exist

  @javascript
  Scenario: Media library cards are square
    When I go to "/admin/content/media-grid"
    Then the element ".media-library-item--grid .media-library-item__preview" should have the CSS property "min-height" with the value "0px"
    And the element ".media-library-item .media-library-item__preview .field img[src$='.svg']" should have the CSS property "object-fit" with the value "contain"
