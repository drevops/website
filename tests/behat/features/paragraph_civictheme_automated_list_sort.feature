@p0 @civictheme @civictheme_automated_list
Feature: Automated list sorting

  As a content editor
  I want sticky content pinned to the top of an automated list
  So that I can highlight important content without changing its authored date

  Background:
    Given the following "civictheme_topics" terms:
      | name              |
      | [TEST] List Topic |

    And the following "civictheme_page" content:
      | title                      | moderation_state |
      | [TEST] Automated List Page | published        |

    And the following fields for the paragraph "civictheme_automated_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Automated List Page":
      | field_c_p_title             | [TEST] Automated list title |
      | field_c_p_list_content_type | civictheme_page             |
      | field_c_p_list_topics       | [TEST] List Topic           |

  @api
  Scenario: Sticky content appears above more recently authored content
    Given I am an anonymous user
    And the following "civictheme_page" content:
      | title                | moderation_state | created            | sticky | promote | field_c_n_topics  |
      | [TEST] Newest Page   | published        | [relative:-1 day]  | 0      | 0       | [TEST] List Topic |
      | [TEST] Sticky Page   | published        | [relative:-2 days] | 1      | 0       | [TEST] List Topic |
      | [TEST] Promoted Page | published        | [relative:-3 days] | 0      | 1       | [TEST] List Topic |

    When I visit the "civictheme_page" content page with the title "[TEST] Automated List Page"
    Then I should see the text "[TEST] Automated list title"
    And I should see 3 ".ct-promo-card" elements

    And the text "[TEST] Newest Page" should appear after the text "[TEST] Sticky Page"
    And the text "[TEST] Promoted Page" should appear after the text "[TEST] Newest Page"

  @api
  Scenario: Content without a sticky flag is ordered by the authored date
    Given I am an anonymous user
    And the following "civictheme_page" content:
      | title                | moderation_state | created            | sticky | promote | field_c_n_topics  |
      | [TEST] Newest Page   | published        | [relative:-1 day]  | 0      | 0       | [TEST] List Topic |
      | [TEST] Middle Page   | published        | [relative:-2 days] | 0      | 0       | [TEST] List Topic |
      | [TEST] Promoted Page | published        | [relative:-3 days] | 0      | 1       | [TEST] List Topic |

    When I visit the "civictheme_page" content page with the title "[TEST] Automated List Page"
    Then I should see 3 ".ct-promo-card" elements

    And the text "[TEST] Middle Page" should appear after the text "[TEST] Newest Page"
    And the text "[TEST] Promoted Page" should appear after the text "[TEST] Middle Page"
