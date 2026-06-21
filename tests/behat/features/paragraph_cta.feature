@p1 @civictheme @drevops @cta
Feature: CTA band

  Background:
    Given the following "civictheme_page" content:
      | title                  | status |
      | [TEST] Page CTA full   | 1      |
      | [TEST] Page CTA single | 1      |
      | [TEST] Page CTA light  | 1      |

  @api
  Scenario: Anonymous user sees a CTA band with heading, sub-line, actions and a distinct email link
    Given I am an anonymous user
    And the following fields for the paragraph "cta" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page CTA full":
      | field_c_p_type   | display                                                                        |
      | field_title      | Let us talk about your website                                                 |
      | field_subtitle   | Tell us where things stand and we will be straight about the fit               |
      | field_c_p_theme  | dark                                                                           |
      | field_link:title | Start a conversation, See what it would cost, info@drevops.com                 |
      | field_link:uri   | https://example.com/contact, https://example.com/cost, mailto:info@drevops.com |

    When I visit the "civictheme_page" content page with the title "[TEST] Page CTA full"
    Then I should see an "article .ct-cta" element
    And I should see an "article .ct-cta.ct-theme-dark" element
    And I should see an "article .ct-cta__underline" element
    And I should see the text "Tell us where things stand and we will be straight about the fit"
    And I should see the link "Start a conversation"
    And I should see the link "See what it would cost"
    And I should see 2 "article .ct-cta__action" elements
    And I should see an "article .ct-cta__action--secondary" element
    And I should see an "article .ct-cta__action--primary" element
    And I should see an "article a.ct-cta__email" element
    And I should see the link "info@drevops.com"

  @api
  Scenario: A single action renders only a primary button and no email link
    Given I am an anonymous user
    And the following fields for the paragraph "cta" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page CTA single":
      | field_c_p_type   | display                     |
      | field_title      | A single call to action     |
      | field_c_p_theme  | dark                        |
      | field_link:title | Get in touch                |
      | field_link:uri   | https://example.com/contact |

    When I visit the "civictheme_page" content page with the title "[TEST] Page CTA single"
    Then I should see an "article .ct-cta" element
    And I should see the link "Get in touch"
    And I should see 1 "article .ct-cta__action" element
    And I should see an "article .ct-cta__action--primary" element
    And I should not see an "article .ct-cta__action--secondary" element
    And I should not see an "article a.ct-cta__email" element

  @api
  Scenario: A content editor is offered the CTA band component on a page
    Given I am logged in as a user with the "Site Administrator" role
    When I visit "node/add/civictheme_page"
    Then the response should contain "Add CTA band"

  @api
  Scenario: A CTA band honours the light theme
    Given I am an anonymous user
    And the following fields for the paragraph "cta" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page CTA light":
      | field_c_p_type   | display                     |
      | field_title      | A light scheme CTA band     |
      | field_c_p_theme  | light                       |
      | field_link:title | Get in touch                |
      | field_link:uri   | https://example.com/contact |

    When I visit the "civictheme_page" content page with the title "[TEST] Page CTA light"
    Then I should see an "article .ct-cta.ct-theme-light" element
    And I should not see an "article .ct-cta.ct-theme-dark" element
    And I should see the link "Get in touch"
