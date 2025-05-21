@p0 @civictheme @civictheme_manual_list
Feature: Manual list render

  Background:
    Given the following managed files:
      | path      | uri                                | status |
      | image.jpg | public://civictheme_test/image.jpg | 1      |

    And the following media "civictheme_image" exist:
      | name                    | field_c_m_image |
      | [TEST] CivicTheme Image | image.jpg       |

    And "civictheme_page" content:
      | title                           | status |
      | [TEST] Page Manual list content | 1      |
      | [TEST] Referenced Page          | 1      |

    And "civictheme_event" content:
      | title                   | status |
      | [TEST] Referenced Event | 1      |

  @api
  Scenario: Manual list, Cards, Spotlight
    Given I am an anonymous user
    And the following fields for the paragraph "civictheme_manual_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Manual list content":
      | field_c_p_title             | [TEST] Manual list title |
      | field_c_p_list_column_count | 4                        |
      | field_c_p_list_fill_width   | 0                        |
      | field_p_list_layout         | spotlight                |
    And the following fields for the paragraph "civictheme_promo_card" exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" identified by the field "field_c_p_title" and the value "[TEST] Manual list title":
      | field_c_p_title   | Card title 1                                  |
      | field_c_p_summary | Card summary 1                                |
      | field_c_p_link    | 0: Test link 1 - 1: https://example.com/link1 |
      | field_c_p_image   | [TEST] CivicTheme Image                       |
      | field_c_p_theme   | light                                         |
    And the following fields for the paragraph "civictheme_promo_card" exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" identified by the field "field_c_p_title" and the value "[TEST] Manual list title":
      | field_c_p_title   | Card title 2                                  |
      | field_c_p_summary | Card summary 2                                |
      | field_c_p_link    | 0: Test link 2 - 1: https://example.com/link2 |
      | field_c_p_image   | [TEST] CivicTheme Image                       |
      | field_c_p_theme   | light                                         |
    And the following fields for the paragraph "civictheme_promo_card" exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" identified by the field "field_c_p_title" and the value "[TEST] Manual list title":
      | field_c_p_title   | Card title 3                                  |
      | field_c_p_summary | Card summary 3                                |
      | field_c_p_link    | 0: Test link 3 - 1: https://example.com/link3 |
      | field_c_p_image   | [TEST] CivicTheme Image                       |
      | field_c_p_theme   | dark                                          |
    And the following fields for the paragraph "civictheme_promo_card" exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" identified by the field "field_c_p_title" and the value "[TEST] Manual list title":
      | field_c_p_title   | Card title 4                                  |
      | field_c_p_summary | Card summary 4                                |
      | field_c_p_link    | 0: Test link 4 - 1: https://example.com/link4 |
      | field_c_p_image   | [TEST] CivicTheme Image                       |
      | field_c_p_theme   | light                                         |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Manual list content"
    Then I should see the text "[TEST] Manual list title"

    And I should see 4 ".ct-promo-card" elements
    And I should see 3 ".ct-promo-card.ct-theme-light" element
    And I should see 1 ".ct-promo-card.ct-theme-dark" elements
    And I should see 1 ".ct-spotlight" elements

    And I should see the text "Card title 1"
    And I should see the text "Card summary 1"
    And the response should contain "https://example.com/link1"
    And I should see the text "Card title 2"
    And I should see the text "Card summary 2"
    And the response should contain "https://example.com/link2"
    And I should see the text "Card title 3"
    And I should see the text "Card summary 3"
    And the response should contain "https://example.com/link3"
    And I should see the text "Card title 4"
    And I should see the text "Card summary 4"
    And the response should contain "https://example.com/link4"
    And save screenshot

  @api
  Scenario: Manual list, Reference cards
    Given I am an anonymous user
    And the following fields for the paragraph "civictheme_manual_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Manual list content":
      | field_c_p_title             | [TEST] Manual list title |
      | field_c_p_list_column_count | 3                        |
      | field_c_p_list_fill_width   | 0                        |
    And the following fields for the paragraph "civictheme_event_card_ref" exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" identified by the field "field_c_p_title" and the value "[TEST] Manual list title":
      | field_c_p_reference | [TEST] Referenced Event |
      | field_c_p_theme     | light                   |
    And the following fields for the paragraph "civictheme_subject_card_ref" exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" identified by the field "field_c_p_title" and the value "[TEST] Manual list title":
      | field_c_p_reference | [TEST] Referenced Page |
      | field_c_p_theme     | light                  |
    And the following fields for the paragraph "civictheme_navigation_card_ref" exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" identified by the field "field_c_p_title" and the value "[TEST] Manual list title":
      | field_c_p_reference | [TEST] Referenced Page |
      | field_c_p_theme     | dark                   |
    And the following fields for the paragraph "civictheme_promo_card_ref" exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" identified by the field "field_c_p_title" and the value "[TEST] Manual list title":
      | field_c_p_reference | [TEST] Referenced Page |
      | field_c_p_theme     | light                  |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Manual list content"
    Then I should see the text "[TEST] Manual list title"

    And I should see 1 ".ct-event-card" elements
    And I should see 1 ".ct-event-card.ct-theme-light" elements
    And I should see 1 ".ct-subject-card" elements
    And I should see 1 ".ct-subject-card.ct-theme-light" elements
    And I should see 1 ".ct-navigation-card" elements
    And I should see 1 ".ct-navigation-card.ct-theme-dark" elements
    And I should see 1 ".ct-promo-card" elements
    And I should see 1 ".ct-promo-card.ct-theme-light" elements

    And I should see the text "[TEST] Referenced Event"
    And I should see the text "[TEST] Referenced Page"
