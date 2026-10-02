@footer @p1 @drevops
Feature: Site footer menu

  As a site visitor
  I want the site's policies linked from the footer of every page
  So that I can check how my data and AI are handled before I get in touch

  @api
  Scenario: Site visitor sees the policy links in the footer menu
    Given I am an anonymous user
    When I go to the homepage
    Then the element ".ct-footer__middle .ct-menu__item__link" with the attribute "href" and the value "/privacy-policy" should exist
    And the element ".ct-footer__middle .ct-menu__item__link" with the attribute "href" and the value "/responsible-ai" should exist
    And I should see "Privacy policy" in the ".ct-footer__middle" element
    And I should see "Responsible AI policy" in the ".ct-footer__middle" element
    And I should not see "Home" in the ".ct-footer__middle" element
    And I should see "Built with" in the ".ct-footer__bottom" element
    And I should not see "Privacy policy" in the ".ct-footer__bottom" element
    And I should not see "Responsible AI policy" in the ".ct-footer__bottom" element

  @api
  Scenario: Site visitor sees a link added to the footer menu in the footer
    Given the following menu links exist in the menu "Footer":
      | title              | enabled | uri        |
      | [TEST] Footer link | 1       | internal:/ |
    And I am an anonymous user
    When I go to the homepage
    Then I should see "[TEST] Footer link" in the ".ct-footer__middle .ct-navigation" element
    And the element ".ct-footer__middle .ct-navigation__items" with the attribute "aria-label" and the value "Footer" should exist
