@p2
Feature: Gin content form sidebar

  As a site administrator
  I want to hide and show the content form sidebar at any window width
  So that I can make room for the form on a mid-size screen

  @javascript
  Scenario Outline: Site administrator can hide the content form sidebar
    Given I am logged in as a user with the role "civictheme_site_administrator"
    When I visit "/"
    And I clear the local storage
    And I set the viewport to <width> by 900
    And I visit "node/add/civictheme_page"
    Then the element ".meta-sidebar__trigger" with the attribute "aria-expanded" and the value "true" should exist
    When I click on the element ".meta-sidebar__trigger"
    Then the element ".meta-sidebar__trigger" with the attribute "aria-expanded" and the value "false" should exist
    And the element "body" with the attribute "data-meta-sidebar" and the value "closed" should exist

    Examples:
      | width |
      | 1100  |
      | 1440  |
