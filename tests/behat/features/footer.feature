@footer @smoke
Feature: Expanded footer

  As a site visitor
  I want a rich footer with the brand, key links and contact details
  So that I can find my way around and reach DrevOps from the bottom of any page

  @api
  Scenario: The footer shows the brand, the three menus and the contact details
    Given I am an anonymous user
    When I go to the homepage
    Then I should see "A technical digital agency that builds and supports reliable websites"
    And I should see "Company"
    And I should see "Get in touch"
    And the response should contain "Website delivery"
    And the response should contain "Ongoing support"
    And the response should contain "AI-assisted delivery"
    And the response should contain "Responsible AI"
    And the response should contain "info@drevops.com"
    And the response should contain "Open source on GitHub"

  @api
  Scenario: Every footer link resolves to its destination
    Given I am an anonymous user
    When I go to the homepage
    Then the response should contain "href=\"/services\""
    And the response should contain "href=\"/blog\""
    And the response should contain "href=\"/responsible-ai\""
    And the response should contain "href=\"/contact\""
    And the response should contain "href=\"mailto:info@drevops.com\""
    And the response should contain "href=\"tel:+61430093538\""
    And the response should contain "href=\"https://github.com/drevops\""

  @api
  Scenario: The bottom bar shows the copyright and the call to action
    Given I am an anonymous user
    When I go to the homepage
    Then I should see "Melbourne, Australia"
    And I should see "Start a conversation"

  @api @javascript
  Scenario: The footer renders dark on the primary step-8 surface
    Given I am an anonymous user
    When I am on the homepage
    Then the computed "background-color" of the element ".ct-footer" should be "#2b394d"
