@p1 @analytics @do_analytics
Feature: Analytics events

  As a site owner
  I want every click on a component to reach Google Analytics with its context
  So that I can tell which components visitors use and where on the page they are

  Background:
    Given the following "civictheme_page" content exists:
      | title                        | status |
      | [TEST] Page Analytics events | 1      |

    And the following fields for the "civictheme_manual_list" paragraph exist in the field "field_c_n_components" within the "civictheme_page" "node" entity with the value "[TEST] Page Analytics events" in the field "title":
      | field_c_p_title             | [TEST] Analytics list |
      | field_c_p_list_column_count | 2                     |
      | field_c_p_list_fill_width   | 0                     |
    And the following fields for the "civictheme_promo_card" paragraph exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" entity with the value "[TEST] Analytics list" in the field "field_c_p_title":
      | field_c_p_title      | [TEST] Analytics card 1         |
      | field_c_p_summary    | [TEST] Analytics card summary 1 |
      | field_c_p_link:title | [TEST] Analytics link 1         |
      | field_c_p_link:uri   | internal:/contact               |
      | field_c_p_theme      | light                           |
    And the following fields for the "civictheme_promo_card" paragraph exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" entity with the value "[TEST] Analytics list" in the field "field_c_p_title":
      | field_c_p_title      | [TEST] Analytics card 2         |
      | field_c_p_summary    | [TEST] Analytics card summary 2 |
      | field_c_p_link:title | [TEST] Analytics link 2         |
      | field_c_p_link:uri   | https://example.com/analytics   |
      | field_c_p_theme      | light                           |
    And the following fields for the "civictheme_content" paragraph exist in the field "field_c_n_components" within the "civictheme_page" "node" entity with the value "[TEST] Page Analytics events" in the field "title":
      | field_c_p_theme            | light                                                                                                            |
      | field_c_p_vertical_spacing | both                                                                                                             |
      | field_c_p_content:value    | <p><a href="mailto:do-test@example.com">Email do-test@example.com</a> <a href="/do-test/guide.pdf">Guide</a></p> |
      | field_c_p_content:format   | civictheme_rich_text                                                                                             |

  Scenario: Components and blocks carry the context the tracker reports
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    Then the element ".ct-list" with the attribute "data-analytics-component" and the value "manual_list" should exist
    And the element ".ct-list" with the attribute "data-analytics-component-title" and the value "[TEST] Analytics list" should exist
    And the element ".ct-promo-card" with the attribute "data-analytics-component-title" and the value "[TEST] Analytics card 1" should exist
    And the element ".ct-promo-card" with the attribute "data-analytics-component-id" and a value containing "paragraph:" should exist
    And the element ".ct-basic-content" with the attribute "data-analytics-component" and the value "content" should exist
    And the element "#block-drevops-content" with the attribute "data-analytics-region" and the value "content" should exist
    And the element "#block-drevops-content" with the attribute "data-analytics-block" and the value "drevops_content" should exist

  @javascript
  Scenario: Clicking a link in a card sends the card and its list as context
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    And I disable link navigation
    And I click on the element "[data-analytics-component-title='[TEST] Analytics card 2'] a[href='https://example.com/analytics']"
    Then the analytics event "component_click" should have been sent with:
      | component          | promo_card                    |
      | component_title    | [TEST] Analytics card 2       |
      | component_position | 2                             |
      | parent_component   | manual_list                   |
      | region             | content                       |
      | block              | drevops_content               |
      | page_type          | civictheme_page               |
      | link_type          | external                      |
      | link_url           | https://example.com/analytics |
      | link_domain        | example.com                   |

  @javascript
  Scenario: Clicking an internal link in a card reports its path
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    And I disable link navigation
    And I click on the element "[data-analytics-component-title='[TEST] Analytics card 1'] a[href$='/contact']"
    Then the analytics event "component_click" should have been sent with:
      | component          | promo_card              |
      | component_title    | [TEST] Analytics card 1 |
      | component_position | 1                       |
      | link_type          | internal                |
      | link_url           | /contact                |

  @javascript
  Scenario: Email and download links are classified, and the email address is not sent
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    And I disable link navigation
    And I click on the element ".ct-basic-content a[href$='/do-test/guide.pdf']"
    Then the analytics event "component_click" should have been sent with:
      | component | content            |
      | link_type | download           |
      | link_url  | /do-test/guide.pdf |
      | link_text | Guide              |
    When I click on the element ".ct-basic-content a[href^='mailto:']"
    Then the analytics event "component_click" should have been sent with:
      | component | content       |
      | link_type | email         |
      | link_text | Email [email] |
    And the last "component_click" analytics event should not have the parameter "link_url"

  @javascript
  Scenario: Clicking a footer menu link sends the block and region it is placed in
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    And I disable link navigation
    And I click on the element "[data-analytics-block='drevops_footer_menu_1'] a[href]"
    Then the analytics event "component_click" should have been sent with:
      | component | navigation            |
      | element   | link                  |
      | block     | drevops_footer_menu_1 |
      | region    | footer_middle_5       |
      | link_type | internal              |

  @javascript
  Scenario: A component scrolled into view sends a view event
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    And I scroll to the element ".ct-promo-card"
    And I wait for 1 second
    Then the analytics event "component_view" should have been sent with:
      | component        | promo_card              |
      | component_title  | [TEST] Analytics card 1 |
      | parent_component | manual_list             |
      | page_type        | civictheme_page         |
