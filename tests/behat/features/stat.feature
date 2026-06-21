@p1 @drevops @stat
Feature: Stat grid

  As a content editor
  I want a grid of statistics that count up as they come into view
  So that I can present key numbers in a way that draws attention

  Background:
    Given the following "civictheme_page" content:
      | title                 | status |
      | [TEST] Page Stat test | 1      |

  @api
  Scenario: The stat grid renders its items with values, suffixes and labels
    Given I am an anonymous user
    And the following fields for the paragraph "stat" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Stat test":
      | field_subtitle  | [TEST] The essentials |
      | field_c_p_theme | dark                  |
    And the following fields for the paragraph "stat_item" exist in the field "field_items" within the "stat" "paragraph" identified by the field "field_subtitle" and the value "[TEST] The essentials":
      | field_stat_value  | 1                            |
      | field_stat_suffix | day                          |
      | field_stat_label  | [TEST] To set up CI/CD       |
    And the following fields for the paragraph "stat_item" exist in the field "field_items" within the "stat" "paragraph" identified by the field "field_subtitle" and the value "[TEST] The essentials":
      | field_stat_value  | 10                           |
      | field_stat_suffix | yrs                          |
      | field_stat_label  | [TEST] Delivering platforms  |
    And the following fields for the paragraph "stat_item" exist in the field "field_items" within the "stat" "paragraph" identified by the field "field_subtitle" and the value "[TEST] The essentials":
      | field_stat_value  | 40                           |
      | field_stat_suffix | +                            |
      | field_stat_label  | [TEST] Open-source tools     |
    And the following fields for the paragraph "stat_item" exist in the field "field_items" within the "stat" "paragraph" identified by the field "field_subtitle" and the value "[TEST] The essentials":
      | field_stat_value  | 100                          |
      | field_stat_suffix | %                            |
      | field_stat_label  | [TEST] Automated tests       |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Stat test"
    Then I should see an "article .ct-stat" element
    And I should see an "article .ct-stat.ct-theme-dark" element
    And I should see 4 ".ct-stat-item" elements
    And I should see the text "[TEST] The essentials"
    And I should see the text "[TEST] To set up CI/CD"
    And I should see the text "[TEST] Delivering platforms"
    And I should see the text "[TEST] Open-source tools"
    And I should see the text "[TEST] Automated tests"
    And I should see the text "yrs"
    And the response should contain "data-target=\"1\""
    And the response should contain "data-target=\"10\""
    And the response should contain "data-target=\"40\""
    And the response should contain "data-target=\"100\""
    And the response should contain "ct-stat-item__suffix"
    And save screenshot

  @api @javascript
  Scenario: The numbers count up to their target values when scrolled into view
    Given I am an anonymous user
    And the following fields for the paragraph "stat" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Stat test":
      | field_subtitle  | [TEST] The essentials |
      | field_c_p_theme | dark                  |
    And the following fields for the paragraph "stat_item" exist in the field "field_items" within the "stat" "paragraph" identified by the field "field_subtitle" and the value "[TEST] The essentials":
      | field_stat_value  | 40                       |
      | field_stat_suffix | +                        |
      | field_stat_label  | [TEST] Open-source tools |
    And the following fields for the paragraph "stat_item" exist in the field "field_items" within the "stat" "paragraph" identified by the field "field_subtitle" and the value "[TEST] The essentials":
      | field_stat_value  | 100                    |
      | field_stat_suffix | %                      |
      | field_stat_label  | [TEST] Automated tests |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Stat test"
    Then the stat counters reach their target values
    And save screenshot

  @api
  Scenario: A content editor can add a stat grid and a stat item
    Given I am logged in as a user with the "Site Administrator" role
    When I visit "node/add/civictheme_page"
    And I fill in "Title" with "[TEST] Page stat fields"
    And I press "Add Stat grid"

    Then I should see the text "Section label"
    And should see a "[name='field_c_n_components[0][subform][field_subtitle][0][value]']" element
    And should see a "[name='field_c_n_components_0_subform_field_items_stat_item_add_more']" element

    When I press "Add Stat item"
    Then I should see the text "Value"
    And should see a "[name='field_c_n_components[0][subform][field_items][0][subform][field_stat_value][0][value]']" element
    And should see a "[name='field_c_n_components[0][subform][field_items][0][subform][field_stat_suffix][0][value]']" element
    And should see a "[name='field_c_n_components[0][subform][field_items][0][subform][field_stat_label][0][value]']" element
