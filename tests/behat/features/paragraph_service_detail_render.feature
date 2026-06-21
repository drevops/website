@p1 @civictheme @drevops @service_detail
Feature: Service detail render

  As a site visitor
  I want each service detail to render in full
  So that I can weigh up a service before getting in touch

  Background:
    Given the following "civictheme_page" content:
      | title                       | status |
      | [TEST] Page service details | 1      |

  @api
  Scenario: A service detail renders its header, two-column body and pricing footer
    Given I am an anonymous user
    And the following fields for the paragraph "service_detail" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page service details":
      | field_c_p_title          | [TEST] Website delivery                             |
      | field_c_p_subtitle       | [TEST] From requirements to production              |
      | field_c_p_content:value  | <p>[TEST] We build your site end to end.</p>        |
      | field_c_p_content:format | civictheme_rich_text                                |
      | field_p_includes         | [TEST] Architecture, [TEST] Testing, [TEST] Hosting |
      | field_p_price_label      | Pricing                                             |
      | field_p_price            | [TEST] Fixed price up front                         |
      | field_c_p_link:title     | Discuss your project                                |
      | field_c_p_link:uri       | https://example.com/contact                         |
      | field_c_p_theme          | dark                                                |

    When I visit the "civictheme_page" content page with the title "[TEST] Page service details"
    Then I should see an "article .component-service-detail.ct-theme-dark" element
    And I should see an "article .component-service-detail .component-service-detail-header .component-service-number" element
    And I should see an "article .component-service-detail .component-service-detail-title" element
    And I should see an "article .component-service-detail .component-service-detail-tagline" element
    And I should see an "article .component-service-detail .component-service-detail-desc" element
    And I should see 3 "article .component-service-detail .component-service-detail-includes li" elements
    And I should see an "article .component-service-detail .component-service-detail-footer .component-pricing-value" element

    And I should see the text "[TEST] Website delivery"
    And I should see the text "[TEST] From requirements to production"
    And I should see the text "[TEST] We build your site end to end."
    And I should see the text "[TEST] Architecture"
    And I should see the text "Pricing"
    And I should see the text "[TEST] Fixed price up front"
    And I should see the text "Discuss your project"
    And the response should contain "https://example.com/contact"
    And save screenshot

  @api
  Scenario: Service details show a sequential index reflecting their order
    Given I am an anonymous user
    And the following fields for the paragraph "service_detail" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page service details":
      | field_c_p_title | [TEST] First service |
      | field_c_p_theme | dark                 |
    And the following fields for the paragraph "service_detail" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page service details":
      | field_c_p_title | [TEST] Second service |
      | field_c_p_theme | dark                  |

    When I visit the "civictheme_page" content page with the title "[TEST] Page service details"
    Then I should see 2 "article .component-service-detail" elements
    And I should see "01" in the "article .component-service-number" element
    And I should see the text "02"
    And save screenshot
