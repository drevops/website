@header @p1 @drevops
Feature: Sticky site header

  As a site visitor
  I want the site header to stay at the top of the window while I scroll
  So that I can navigate away from anywhere on a long page

  @api @javascript
  Scenario: Site visitor sees the header pinned to the top of the window while an alert is shown above it
    Given the following "civictheme_alert" content:
      | title             | moderation_state | field_c_n_alert_type | field_c_n_body:value    | :format              | field_c_n_date_range:value | :end_value          |
      | [TEST] Site Alert | published        | information          | [TEST] Alert body copy. | civictheme_rich_text | 2020-01-01T00:00:00        | 2099-01-01T00:00:00 |

    And I am an anonymous user

    When I visit "/"
    And I wait for 2 seconds
    Then I should see the text "[TEST] Site Alert"
    And the element ".ct-header" should appear after the element ".ct-alert"

    When I scroll to the element ".ct-footer"
    Then the element ".ct-header" should be pinned to the top of the viewport
