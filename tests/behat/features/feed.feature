@feed @p1
Feature: Automated list RSS feed

  As a site visitor
  I want to subscribe to RSS feeds for content lists
  So that I can stay updated on new content

  Background:
    Given "civictheme_topics" terms:
      | name              |
      | [TEST] Feed Topic |
    And the following "civictheme_page" content with fields:
      | title                           | moderation_state | field_c_n_topics  |
      | [TEST] Feed Article One         | published        | [TEST] Feed Topic |
      | [TEST] Feed Article Two         | published        | [TEST] Feed Topic |
      | [TEST] Feed Unpublished Article | draft            | [TEST] Feed Topic |
      | [TEST] Feed Listing Page        | published        |                   |
      | [TEST] Unrelated Article        | published        |                   |
    And the following fields for the paragraph "civictheme_automated_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Feed Listing Page":
      | field_c_p_list_content_type     | civictheme_page              |
      | field_c_p_list_topics           | [TEST] Feed Topic            |
      | field_c_p_list_feed_slug        | testfeed                     |
      | field_c_p_list_feed_title       | [TEST] Feed Title            |
      | field_c_p_list_feed_description | [TEST] Feed description text |

  @api
  Scenario: RSS feed button links to valid feed with correct content
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Feed Listing Page"
    Then I should see the link "RSS Feed"
    When I click "RSS Feed"
    Then the response status code should be 200
    And the response header "content-type" should contain the value "application/rss+xml"
    And the response should contain "[TEST] Feed Title"
    And the response should contain "[TEST] Feed description text"
    And the response should contain "[TEST] Feed Article One"
    And the response should contain "[TEST] Feed Article Two"
    And the response should not contain "[TEST] Feed Unpublished Article"
    And the response should not contain "[TEST] Unrelated Article"
    And the response should not contain "<svg"

  @api
  Scenario: Feed with non-existent topic returns not found
    Given I am an anonymous user
    When I go to "/feed/civictheme_page/999999/all"
    Then the response status code should be 404
