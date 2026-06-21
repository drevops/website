@javascript
Feature: Dark colour scheme

  As a site visitor
  I want the website to render in the DrevOps dark brand colours
  So that every page reflects the brand rather than the default CivicTheme palette

  Scenario: The brand colour tokens resolve to the dark palette
    Given I am an anonymous user
    When I am on the homepage
    Then the computed "--ct-color-dark-background" of the element "html" should be "#152235"
    And the computed "--ct-color-dark-heading" of the element "html" should be "#ffffff"
    And the computed "--ct-color-dark-body" of the element "html" should be "#eff6ff"
    And the computed "--ct-color-dark-interaction-background" of the element "html" should be "#96e7f4"
    And the computed "--ct-color-dark-highlight" of the element "html" should be "#ff9c86"
    And the computed "--ct-color-light-background" of the element "html" should be "#152235"

  Scenario: The page renders on the dark navy canvas
    Given I am an anonymous user
    When I am on the homepage
    Then the computed "background-color" of the element ".ct-page" should be "#152235"
