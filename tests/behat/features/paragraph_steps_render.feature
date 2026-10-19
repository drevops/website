@p1 @civictheme @drevops @steps
Feature: Steps render

  As a site visitor
  I want to see the steps component rendered on a page
  So that I can follow a numbered journey and what I receive at each step

  Background:
    Given the following "civictheme_page" content exists:
      | title                    | status |
      | [TEST] Page Steps test 1 | 1      |
      | [TEST] Page Steps test 2 | 1      |

  Scenario: CivicTheme page renders light Steps with numbered items and receive notes
    Given I am an anonymous user
    And the following fields for the "steps" paragraph exist in the field "field_c_n_components" within the "civictheme_page" "node" entity with the value "[TEST] Page Steps test 1" in the field "title":
      | field_c_p_title            | [TEST] Steps title |
      | field_c_p_theme            | light              |
      | field_c_p_vertical_spacing | both               |
      | field_c_p_background       | 0                  |
    And the following fields for the "steps_item" paragraph exist in the field "field_c_p_list_items" within the "steps" "paragraph" entity with the value "[TEST] Steps title" in the field "field_c_p_title":
      | field_c_p_title   | [TEST] Step one title    |
      | field_c_p_summary | [TEST] Step one body.    |
      | field_p_receive   | [TEST] Step one outcome. |
    And the following fields for the "steps_item" paragraph exist in the field "field_c_p_list_items" within the "steps" "paragraph" entity with the value "[TEST] Steps title" in the field "field_c_p_title":
      | field_c_p_title   | [TEST] Step two title |
      | field_c_p_summary | [TEST] Step two body. |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Steps test 1"
    Then I should see an "article .ct-steps" element
    And I should see an "article .ct-steps.ct-theme-light" element
    And I should not see an "article .ct-steps.ct-theme-dark" element
    And I should see an "article .ct-steps.ct-vertical-spacing-inset--both" element
    And I should not see an "article .ct-steps.ct-steps--with-background" element
    And I should see 2 ".ct-steps__item" elements
    And I should see 2 ".ct-steps__item-number" elements
    And I should see 1 ".ct-steps__item-receive" elements
    And I should see "[TEST] Steps title"
    And I should see "[TEST] Step one title"
    And I should see "[TEST] Step one body."
    And I should see "You receive:"
    And I should see "[TEST] Step one outcome."
    And I should see "[TEST] Step two title"
    And I should see "[TEST] Step two body."
    And save screenshot

  Scenario: CivicTheme page renders dark Steps with a background
    Given I am an anonymous user
    And the following fields for the "steps" paragraph exist in the field "field_c_n_components" within the "civictheme_page" "node" entity with the value "[TEST] Page Steps test 2" in the field "title":
      | field_c_p_title            | [TEST] Steps dark title |
      | field_c_p_theme            | dark                    |
      | field_c_p_vertical_spacing | both                    |
      | field_c_p_background       | 1                       |
    And the following fields for the "steps_item" paragraph exist in the field "field_c_p_list_items" within the "steps" "paragraph" entity with the value "[TEST] Steps dark title" in the field "field_c_p_title":
      | field_c_p_title   | [TEST] Dark step title    |
      | field_c_p_summary | [TEST] Dark step body.    |
      | field_p_receive   | [TEST] Dark step outcome. |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Steps test 2"
    Then I should see an "article .ct-steps" element
    And I should see an "article .ct-steps.ct-theme-dark" element
    And I should not see an "article .ct-steps.ct-theme-light" element
    And I should see an "article .ct-steps.ct-steps--with-background" element
    And I should see an "article .ct-steps.ct-vertical-spacing-inset--both" element
    And I should see 1 ".ct-steps__item" elements
    And I should see "[TEST] Dark step title"
    And I should see "[TEST] Dark step outcome."
    And save screenshot
