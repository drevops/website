@footer @p1 @drevops
Feature: Site footer menu

  As a site visitor
  I want the site's policies linked from the footer of every page
  So that I can find them wherever I am on the site

  @api
  Scenario: Site visitor sees the policy links in the footer menu
    Given I am an anonymous user
    When I go to the homepage
    Then I should see "Privacy policy" in the ".ct-footer__middle .ct-navigation" element
    And I should see "Responsible AI policy" in the ".ct-footer__middle .ct-navigation" element

  @javascript
  Scenario: Site visitor sees the footer menu as a single row on a desktop
    When I set the viewport to "1440" by "900"
    And I visit "/"
    Then the child elements of ".ct-footer__middle .ct-navigation__menu" should be on a single row
