@p1 @drevops @services
Feature: Services page

  As a prospective client
  I want a services page that explains each service in full
  So that I can understand what DrevOps offers and how it is priced

  @api
  Scenario: The services page is assembled from the shared components in order
    Given I am an anonymous user
    When I go to "/services"
    Then I should see an "article .component-hero.component-hero--inner" element
    And I should see 3 ".component-service-detail" elements
    And I should see an "article .ct-card-group.ct-card-group--cols-2" element
    And I should see an "article .ct-cta" element
    And I should not see a ".ct-banner" element
    And the element ".component-service-detail" should appear after the element ".component-hero"
    And the element ".ct-card-group" should appear after the element ".component-service-detail"
    And the element ".ct-cta" should appear after the element ".ct-card-group"

  @api
  Scenario: Each service detail block shows its title, tagline, inclusions, pricing and action
    Given I am an anonymous user
    When I go to "/services"
    Then I should see the text "Website Delivery"
    And I should see the text "From requirements to production in one engagement."
    And I should see the text "Architecture and technical planning"
    And I should see the text "Fixed price, agreed up front"
    And I should see the link "Discuss your project"
    And I should see the text "Ongoing Support"
    And I should see the text "Prepaid, month to month"
    And I should see the text "Upgrades & Migrations"
    And I should see the link "Book a free assessment"

  @api
  Scenario: The hero, approach list and call to action render in the dark scheme
    Given I am an anonymous user
    When I go to "/services"
    Then I should see the text "What we do"
    And I should see the text "Engineering that keeps your platform running."
    And I should see an "article .component-hero.ct-theme-dark" element
    And I should see the text "AI-accelerated delivery"
    And I should see the text "Flat-rate pricing"
    And I should see the text "Tested by default"
    And I should see the text "Direct communication"
    And I should see an "article .ct-card-group.ct-theme-dark" element
    And I should see the text "Ready to talk about your platform?"
    And I should see the link "Get in touch"
    And I should see an "article .ct-cta.ct-theme-dark" element

  @api @javascript
  Scenario: The services page reflows without horizontal scrolling on a phone
    Given I am an anonymous user
    When I go to "/services"
    Then the page has no horizontal overflow at 375 pixels wide
