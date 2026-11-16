@p1 @analytics @do_analytics
Feature: Analytics events

  As a site owner
  I want every click on a component to reach Google Analytics with its context
  So that I can tell which components visitors use and where on the page they are

  Background:
    Given the following "civictheme_page" content exists:
      | title                            | status |
      | [TEST] Page Analytics events     | 1      |
      | [TEST] Analytics referenced page | 1      |

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
    And the following fields for the "civictheme_promo_card_ref" paragraph exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" entity with the value "[TEST] Analytics list" in the field "field_c_p_title":
      | field_c_p_reference | [TEST] Analytics referenced page |
      | field_c_p_theme     | light                            |
    And the following fields for the "civictheme_content" paragraph exist in the field "field_c_n_components" within the "civictheme_page" "node" entity with the value "[TEST] Page Analytics events" in the field "title":
      | field_c_p_theme            | light                                                                                        |
      | field_c_p_vertical_spacing | both                                                                                         |
      | field_c_p_content:value    | <p><a href="mailto:do-test@example.com">Email</a> <a href="/do-test/guide.pdf">Guide</a></p> |
      | field_c_p_content:format   | civictheme_rich_text                                                                         |

  Scenario: Components and blocks carry the Axel attributes
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    Then the element ".ct-list" with the attribute "data-axel-event" and the value "component_click" should exist
    And the element ".ct-list" with the attribute "data-axel-param-component" and the value "manual_list" should exist
    And the element ".ct-list" with the attribute "data-axel-param-component-title" and the value "[TEST] Analytics list" should exist
    And the element ".ct-promo-card" with the attribute "data-axel-param-component-title" and the value "[TEST] Analytics card 1" should exist
    And the element ".ct-promo-card" with the attribute "data-axel-param-component-id" and a value containing "paragraph:" should exist
    And the element "[data-axel-param-component='promo_card_ref']" with the attribute "data-axel-param-component-title" and the value "[TEST] Analytics referenced page" should exist
    And the element ".ct-basic-content" with the attribute "data-axel-param-component" and the value "content" should exist
    And the element "#block-drevops-content" with the attribute "data-axel-param-region" and the value "content" should exist
    And the element "#block-drevops-content" with the attribute "data-axel-param-block" and the value "drevops_content" should exist
    And the element "script[src*='@drevops/axel@']" with the attribute "integrity" and a value containing "sha384-" should exist

  @javascript
  Scenario: Clicking a link in a card sends the card and its block as context
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    And I disable link navigation
    And I click on the element "[data-axel-param-component-title='[TEST] Analytics card 2'] a[href='https://example.com/analytics']"
    Then the analytics event "component_click" should have been sent with:
      | component       | promo_card                    |
      | component_title | [TEST] Analytics card 2       |
      | region          | content                       |
      | block           | drevops_content               |
      | page_type       | civictheme_page               |
      | click_type      | link                          |
      | link_url        | https://example.com/analytics |

  @javascript
  Scenario: Email and download links in content send the content component
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    And I disable link navigation
    And I click on the element ".ct-basic-content a[href$='/do-test/guide.pdf']"
    Then the analytics event "component_click" should have been sent with:
      | component | content |
      | link_text | Guide   |
    When I click on the element ".ct-basic-content a[href^='mailto:']"
    Then the analytics event "component_click" should have been sent with:
      | component | content |
      | link_text | Email   |
      | link_url  | mailto: |

  @javascript
  Scenario: Clicking a footer menu link sends the block and region it is placed in
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    And I disable link navigation
    And I click on the element "[data-axel-param-block='drevops_footer_menu_1'] a[href]"
    Then the analytics event "component_click" should have been sent with:
      | block      | drevops_footer_menu_1 |
      | region     | footer_middle_5       |
      | click_type | link                  |

  @javascript
  Scenario: Clicking an icon-only social link sends its title without the screen-reader text
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Analytics events"
    And I disable link navigation
    And I click on the element "a.ct-social-links__button[href='https://github.com/drevops']"
    Then the analytics event "component_click" should have been sent with:
      | block     | drevops_footer_social_links |
      | link_text | GitHub                      |

  @javascript
  Scenario: Focusing a webform field sends a form start with the webform name
    Given I am an anonymous user
    When I go to "/contact"
    And I click on the element "form[data-axel-form='webform:contact'] input[name='name']"
    Then the analytics event "form_start" should have been sent with:
      | form_name | webform:contact |
      | component | webform         |

  @javascript
  Scenario: A webform keeps its form ID when a validation error redraws it
    Given I am an anonymous user
    When I go to "/contact"
    And the browser validation for the form ".webform-submission-contact-form" is disabled
    And I fill in "Name" with "[TEST] Analytics user"
    And I press "Send message"
    And I wait for 2 seconds
    Then I should see "Email field is required."
    When I fill in "Email" with "do-test@example.com"
    And I click on the element "form[data-axel-form='webform:contact'] input[name='name']"
    Then the analytics events "field_complete" should all have the same "form_id"

  @javascript
  Scenario: Submitting the contact form sends a form submit from its inline confirmation
    Given I am an anonymous user
    When I go to "/contact"
    And I fill in "Name" with "[TEST] Analytics user"
    And I fill in "Email" with "do-test@example.com"
    And I fill in "Message" with "[TEST] Analytics form submission."
    And I press "Send message"
    And I wait for 1 second
    Then I should see "Thank you for contacting us. We will get back to you within 2 business days."
    And the element ".webform-confirmation" with the attribute "data-axel-form-submitted" and the value "webform:contact" should exist
    And the analytics event "form_submit" should have been sent with:
      | form_name | webform:contact |
