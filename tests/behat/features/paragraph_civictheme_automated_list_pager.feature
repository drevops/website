@p0 @civictheme @civictheme_automated_list
Feature: Automated list pagination

  As a site visitor
  I want each automated list on a page to have its own pager
  So that moving through one list does not move the others

  Background:
    Given the following "civictheme_topics" terms:
      | name               |
      | [TEST] Alpha Topic |
      | [TEST] Bravo Topic |

    And the following "civictheme_page" content:
      | title                    | moderation_state |
      | [TEST] Two Lists Page    | published        |

    And the following fields for the paragraph "civictheme_automated_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Two Lists Page":
      | field_c_p_title             | [TEST] Alpha list  |
      | field_c_p_list_content_type | civictheme_page    |
      | field_c_p_list_topics       | [TEST] Alpha Topic |
      | field_c_p_list_limit_type   | unlimited          |
      | field_c_p_list_limit        | 2                  |

    And the following fields for the paragraph "civictheme_automated_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Two Lists Page":
      | field_c_p_title             | [TEST] Bravo list  |
      | field_c_p_list_content_type | civictheme_page    |
      | field_c_p_list_topics       | [TEST] Bravo Topic |
      | field_c_p_list_limit_type   | unlimited          |
      | field_c_p_list_limit        | 2                  |

    And the following "civictheme_page" content:
      | title              | moderation_state | created            | field_c_n_topics   |
      | [TEST] Alpha One   | published        | [relative:-1 day]  | [TEST] Alpha Topic |
      | [TEST] Alpha Two   | published        | [relative:-2 days] | [TEST] Alpha Topic |
      | [TEST] Alpha Three | published        | [relative:-3 days] | [TEST] Alpha Topic |
      | [TEST] Bravo One   | published        | [relative:-1 day]  | [TEST] Bravo Topic |
      | [TEST] Bravo Two   | published        | [relative:-2 days] | [TEST] Bravo Topic |
      | [TEST] Bravo Three | published        | [relative:-3 days] | [TEST] Bravo Topic |

  @api
  Scenario: Each list carries a pager of its own
    Given I am an anonymous user

    When I visit the "civictheme_page" content page with the title "[TEST] Two Lists Page"
    Then I should see 2 ".ct-pagination" elements
    And I should see "[TEST] Alpha One"
    And I should see "[TEST] Bravo One"
    And I should not see "[TEST] Alpha Three"
    And I should not see "[TEST] Bravo Three"

  @api
  Scenario: Paging the second list leaves the first where it was
    Given I am an anonymous user
    And I visit the "civictheme_page" content page with the title "[TEST] Two Lists Page"

    When I follow the link "2" with the index 2
    Then I should see "[TEST] Bravo Three"
    And I should not see "[TEST] Bravo One"
    And I should see "[TEST] Alpha One"
    And I should not see "[TEST] Alpha Three"

  @api
  Scenario: Paging the first list leaves the second where it was
    Given I am an anonymous user
    And I visit the "civictheme_page" content page with the title "[TEST] Two Lists Page"

    When I follow the link "2" with the index 1
    Then I should see "[TEST] Alpha Three"
    And I should not see "[TEST] Alpha One"
    And I should see "[TEST] Bravo One"
    And I should not see "[TEST] Bravo Three"

  @api
  Scenario: A list already moved on keeps its page while another is paged
    Given I am an anonymous user
    And I visit the "civictheme_page" content page with the title "[TEST] Two Lists Page"
    And I follow the link "2" with the index 2

    When I follow the link "2" with the index 1
    Then current url should have the "page" parameter with the "1,1" value
    And I should see "[TEST] Alpha Three"
    And I should see "[TEST] Bravo Three"
    And I should not see "[TEST] Alpha One"
    And I should not see "[TEST] Bravo One"
