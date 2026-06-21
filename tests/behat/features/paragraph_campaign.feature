@p1 @civictheme @drevops @campaign
Feature: Campaign card

  Background:
    Given the following "civictheme_page" content:
      | title                        | status |
      | [TEST] Page Campaign two     | 1      |
      | [TEST] Page Campaign one     | 1      |

  @api
  Scenario: Anonymous user sees a campaign card with label, heading, body and two actions
    Given I am an anonymous user
    And the following fields for the paragraph "campaign" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Campaign two":
      | field_subtitle   | For teams already on Drupal                       |
      | field_title      | Curious what your next project would cost          |
      | field_content    | Send us a recent quote and we will show the cost.  |
      | field_c_p_theme  | dark                                               |
      | field_link:title | See the cost, See how we work                      |
      | field_link:uri   | https://example.com/cost, https://example.com/work |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Campaign two"
    Then I should see an "article .ct-campaign-card" element
    And I should see an "article .ct-campaign-card.ct-theme-dark" element
    And I should see the text "For teams already on Drupal"
    And I should see the text "Curious what your next project would cost"
    And I should see the text "Send us a recent quote and we will show the cost."
    And I should see the link "See the cost"
    And I should see the link "See how we work"
    And I should see an "article .ct-campaign-card__action--secondary" element
    And I should see an "article .ct-campaign-card__action--primary" element
    And save screenshot

  @api
  Scenario: A single action renders one primary button and keeps the layout intact
    Given I am an anonymous user
    And the following fields for the paragraph "campaign" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Campaign one":
      | field_title      | A single call to action      |
      | field_c_p_theme  | dark                         |
      | field_link:title | Get in touch                 |
      | field_link:uri   | https://example.com/contact  |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Campaign one"
    Then I should see an "article .ct-campaign-card" element
    And I should see the link "Get in touch"
    And I should see 1 "article .ct-campaign-card__action" element
    And I should see an "article .ct-campaign-card__action--primary" element
    And I should not see an "article .ct-campaign-card__action--secondary" element

  @api
  Scenario: A content editor is offered the campaign component on a page
    Given I am logged in as a user with the "Site Administrator" role
    When I visit "node/add/civictheme_page"
    Then the response should contain "Add Campaign"
