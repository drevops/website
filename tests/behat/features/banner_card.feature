@p1 @civictheme @drevops @banner
Feature: Banner card type

  As a content editor
  I want a banner type that shows the featured image whole
  So that designed graphics are not cropped on desktop or hidden on mobile

  Background:
    Given the following managed files exist:
      | path      | uri                        | status |
      | image.jpg | public://do_test/image.jpg | 1      |
    And the following "civictheme_image" media exist:
      | name                        | field_c_m_image            |
      | [TEST] Banner featured JPEG | public://do_test/image.jpg |

  Scenario: Card is offered as a banner type on nodes and on the banner block
    Given I am logged in as a user with the role "Site Administrator"
    When I visit "node/add/project"
    Then I should see a "input[type='radio'][name='field_c_n_banner_type'][value='card']" element
    When I visit "block/add/civictheme_banner"
    Then the option "Card" within the select "field_c_b_banner_type" should exist

  @project
  Scenario: A card banner shows the featured image whole inside the content grid
    Given the following "project" content exists:
      | title                      | moderation_state | field_c_n_banner_featured_image | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Banner card project | published       | [TEST] Banner featured JPEG     | 2025            | completed         | card                  | inherit                | normal                      | both                       |
    When I visit the "project" content page with the title "[TEST] Banner card project"
    Then I should see a ".ct-banner.ct-banner-type--card .container .ct-banner__card-layout .ct-banner__card img.ct-banner__card-image" element
    And the element ".ct-banner__card-image" with the attribute "src" and a value containing "/styles/banner_card/" should exist
    And the element ".ct-banner__card-image" with the attribute "src" and a value containing ".webp" should exist
    And I should not see a ".ct-banner__featured-image__wrapper" element
    And I should not see a ".ct-banner__card-layout .col-m-6" element

  @project
  Scenario: A default banner keeps the cropped featured image beside the content
    Given the following "project" content exists:
      | title                         | moderation_state | field_c_n_banner_featured_image | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Banner default project | published        | [TEST] Banner featured JPEG     | 2025            | completed         | default               | inherit                | normal                      | both                       |
    When I visit the "project" content page with the title "[TEST] Banner default project"
    Then I should see a ".ct-banner.ct-banner-type--default .ct-banner__featured-image__wrapper img.ct-banner__featured-image" element
    And the element ".ct-banner__featured-image" with the attribute "src" and a value containing "/styles/banner_featured/" should exist
    And I should not see a ".ct-banner__card-layout" element
    And I should not see a ".ct-banner__card" element
