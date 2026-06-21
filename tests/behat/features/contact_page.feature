@p1 @drevops @contact
Feature: Contact page

  As a prospective client
  I want a contact page with a form and direct contact details
  So that I can reach DrevOps in whatever way suits me

  @api
  Scenario: The contact page is assembled from the shared components in order
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see an "article .component-hero.component-hero--contact" element
    And I should see an "article .component-contact-content" element
    And I should see an "article .component-grid-contact" element
    And I should see a ".webform-submission-contact-form" element
    And I should see 3 ".component-contact-detail" elements
    And I should see an "article .ct-card-group.ct-card-group--cols-1" element
    And I should not see a ".ct-banner" element
    And the element ".component-contact-content" should appear after the element ".component-hero"

  @api
  Scenario: The contact column lists the direct contact methods
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see the text "Email us directly"
    And I should see the link "info@drevops.com"
    And I should see the text "Call us"
    And I should see the text "04 3009 3538"
    And I should see the text "Based in"
    And I should see the text "Melbourne, Australia"

  @api
  Scenario: The contact column shows the numbered "what to expect" steps
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see the text "What to expect"
    And I should see the text "We'll review your message and respond within 24 hours."
    And I should see the text "A 30-minute call to understand your platform and goals."
    And I should see the text "A clear proposal with fixed-price quoting, no surprises."

  @api
  Scenario: The contact page leads with the hero in the dark scheme
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see the heading "Let's talk about your platform."
    And I should see an "article .component-hero.ct-theme-dark" element
    And I should see an "article .component-contact-detail.ct-theme-dark" element
    And I should see an "article .ct-card-group.ct-theme-dark" element

  @api @javascript
  Scenario: The contact page reflows without horizontal scrolling on a phone
    Given I am an anonymous user
    When I go to "/contact"
    Then the page has no horizontal overflow at 375 pixels wide
