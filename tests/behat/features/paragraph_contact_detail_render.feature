@p1 @civictheme @drevops @contact_detail
Feature: Contact detail render

  As a site visitor
  I want each contact method to show its label, value and note
  So that I can reach the team through the right channel

  @api @javascript
  Scenario: Contact details stack with actionable email and phone links
    Given the following "civictheme_page" content:
      | title                      | status |
      | [TEST] Page Contact detail | 1      |
    And I am an anonymous user
    And the following fields for the paragraph "contact_detail" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Contact detail":
      | field_c_p_subtitle       | Email us directly                  |
      | field_c_p_content:value  | info@drevops.com                   |
      | field_c_p_content:format | plain_text                         |
      | field_c_p_summary        | We typically respond within a day. |
      | field_c_p_theme          | dark                               |
    And the following fields for the paragraph "contact_detail" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Contact detail":
      | field_c_p_subtitle       | Call us                             |
      | field_c_p_content:value  | 04 3009 3538                        |
      | field_c_p_content:format | plain_text                          |
      | field_c_p_summary        | Available weekdays, Melbourne time. |
      | field_c_p_theme          | dark                                |
    And the following fields for the paragraph "contact_detail" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Contact detail":
      | field_c_p_subtitle       | Based in                         |
      | field_c_p_content:value  | Melbourne, Australia             |
      | field_c_p_content:format | plain_text                       |
      | field_c_p_summary        | We work across Australia and NZ. |
      | field_c_p_theme          | dark                             |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Contact detail"
    Then I should see an "article .component-contact-detail.ct-theme-dark" element
    And I should see an "article .component-contact-detail .component-contact-detail__label" element
    And I should see an "article .component-contact-detail .component-contact-detail__note" element
    And I should see an "article a.component-contact-detail__value--email[href='mailto:info@drevops.com']" element
    And I should see an "article a.component-contact-detail__value[href='tel:0430093538']" element
    And I should see an "article p.component-contact-detail__value" element
    And I should see the text "Email us directly"
    And I should see the text "info@drevops.com"
    And I should see the text "04 3009 3538"
    And I should see the text "Melbourne, Australia"
    And save screenshot
