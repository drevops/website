@p0 @drevops @related_posts
Feature: Related posts block

  As a site visitor
  I want a page to link to posts on the same topic
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

  @api
  Scenario: The block lists posts sharing the topics of the post it is on
    Given I am an anonymous user

    When I visit the "blog" content page with the title "[TEST] Post Being Read"
    Then I should see the text "Related posts"
    And I should see "[TEST] Post Same Topic"

  @api
  Scenario: The block leaves out the page it is on and posts on other topics
    Given I am an anonymous user

    When I visit the "blog" content page with the title "[TEST] Post Being Read"
    Then I should not see "[TEST] Post Other Topic"
    And I should not see "[TEST] Post Without Topic"
    And I should see 1 ".ct-promo-card" elements

  @api
  Scenario: A page whose topics nothing shares shows no heading
    Given I am an anonymous user

    When I visit the "blog" content page with the title "[TEST] Post Without Topic"
    Then I should not see the text "Related posts"

  @api
  Scenario: The block reaches pages under the services path
    Given the following "civictheme_page" content:
      | title               | status | field_c_n_topics    |
      | [TEST] Service Page | 1      | [TEST] Shared Topic |

    And the "civictheme_page" content "[TEST] Service Page" has the path alias "/services/test-service-page"
    And I am an anonymous user

    When I go to "/services/test-service-page"
    Then I should see the text "Related posts"
    And I should see "[TEST] Post Being Read"
    And I should see "[TEST] Post Same Topic"

  @api
  Scenario: The block stays off pages outside its visibility list
    Given the following "civictheme_page" content:
      | title             | status | field_c_n_topics    |
      | [TEST] Plain Page | 1      | [TEST] Shared Topic |

    And the "civictheme_page" content "[TEST] Plain Page" has the path alias "/test-plain-page"
    And I am an anonymous user

    When I go to "/test-plain-page"
    Then I should not see the text "Related posts"

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
