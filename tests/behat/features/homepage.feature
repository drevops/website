@homepage @smoke
Feature: Homepage

  As a site visitor
  I want to access the homepage
  So that I can view the main landing page and navigate the site

  @api
  Scenario: Anonymous user visits homepage
    Given I am an anonymous user
    When I go to the homepage
    Then the path should be "<front>"
    And I save screenshot

  @api @javascript
  Scenario: Anonymous user visits homepage using a real browser
    Given I am an anonymous user
    When I go to the homepage
    Then the path should be "<front>"
    And I save screenshot

  @api
  Scenario: The homepage assembles the shared components in order
    Given I am an anonymous user

    When I go to the homepage
    Then I should get a "200" HTTP response
    # The hero leads the page, so the inherited CivicTheme banner is suppressed.
    And the response should not contain "ct-banner"
    # Every section is a shared component, identified by its component markup.
    And the response should contain "component-hero--home"
    And the response should contain "component-hero--section"
    And the response should contain "ct-card--number"
    And the response should contain "ct-card--dot"
    And the response should contain "ct-card--icon"
    And the response should contain "ct-stat"
    And the response should contain "ct-cta"
    # The shared components render in the dark scheme.
    And the response should contain "ct-theme-dark"
    # The sections appear in the order set out in the acceptance criteria.
    And the text "For teams whose website is real infrastructure." should appear after the text "Reliable websites, delivered faster"
    And the text "Website Delivery" should appear after the text "For teams whose website is real infrastructure."
    And the text "The same standard, in fewer hours" should appear after the text "Website Delivery"
    And the text "Curious what your next project would cost with us?" should appear after the text "The same standard, in fewer hours"
    And the text "The essentials" should appear after the text "Curious what your next project would cost with us?"
    And the text "Victorian Government" should appear after the text "The essentials"
    And the text "Automated testing is not optional" should appear after the text "Victorian Government"
    And the text "before any work begins" should appear after the text "Automated testing is not optional"
    And the text "From the blog" should appear after the text "before any work begins"
    And the text "Tell us where things stand" should appear after the text "From the blog"

  @api @javascript
  Scenario: The homepage reflows on a phone without horizontal scrolling
    Given I am an anonymous user

    When I go to the homepage
    Then the homepage reflows without horizontal scrolling on a phone
