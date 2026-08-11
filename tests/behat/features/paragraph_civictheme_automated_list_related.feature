@p0 @civictheme @civictheme_automated_list
Feature: Automated list following the page's own topics

  As a site visitor
  I want a post to link to others on the same topic
  So that I can keep reading without going back to a paginated listing

  Background:
    Given the following "civictheme_topics" terms:
      | name                |
      | [TEST] Shared Topic |
      | [TEST] Other Topic  |

    And the following "blog" content:
      | title                     | moderation_state | created            | field_c_n_topics    |
      | [TEST] Post Being Read    | published        | [relative:-1 day]  | [TEST] Shared Topic |
      | [TEST] Post Same Topic    | published        | [relative:-2 days] | [TEST] Shared Topic |
      | [TEST] Post Other Topic   | published        | [relative:-3 days] | [TEST] Other Topic  |
      | [TEST] Post Without Topic | published        | [relative:-4 days] |                     |

    And the following fields for the paragraph "civictheme_automated_list" exist in the field "field_c_n_components" within the "blog" "node" identified by the field "title" and the value "[TEST] Post Being Read":
      | field_c_p_title                 | [TEST] Related posts |
      | field_c_p_list_type             | civictheme_automated_list__block1 |
      | field_c_p_list_content_type     | blog                 |
      | field_c_p_list_limit_type       | limited              |
      | field_c_p_list_limit            | 3                    |
      | field_c_p_list_topics_from_page | 1                    |

  @api
  Scenario: The list shows posts sharing the page's topic
    Given I am an anonymous user

    When I visit the "blog" content page with the title "[TEST] Post Being Read"
    Then I should see the text "[TEST] Related posts"
    And I should see "[TEST] Post Same Topic"

  @api
  Scenario: The list leaves out the page it is on and posts on other topics
    Given I am an anonymous user

    When I visit the "blog" content page with the title "[TEST] Post Being Read"
    Then I should not see "[TEST] Post Other Topic"
    And I should not see "[TEST] Post Without Topic"
    And I should see 1 ".ct-promo-card" elements

  @api
  Scenario: A post whose topics nothing shares shows no heading
    Given the following fields for the paragraph "civictheme_automated_list" exist in the field "field_c_n_components" within the "blog" "node" identified by the field "title" and the value "[TEST] Post Without Topic":
      | field_c_p_title                 | [TEST] Related posts |
      | field_c_p_list_type             | civictheme_automated_list__block1 |
      | field_c_p_list_content_type     | blog                 |
      | field_c_p_list_limit_type       | limited              |
      | field_c_p_list_limit            | 3                    |
      | field_c_p_list_topics_from_page | 1                    |

    And I am an anonymous user

    When I visit the "blog" content page with the title "[TEST] Post Without Topic"
    Then I should not see the text "[TEST] Related posts"

  @api
  Scenario: A post links to the topic pages it belongs to
    Given I am an anonymous user

    When I visit the "blog" content page with the title "[TEST] Post Being Read"
    Then I should see the link "[TEST] Shared Topic"

    When I click "[TEST] Shared Topic"
    Then the path should be "/topics/test-shared-topic"
    And I should see "[TEST] Post Same Topic"
    And I should see "[TEST] Post Being Read"
    And I should not see "[TEST] Post Other Topic"
    And I should not see "[TEST] Post Without Topic"
