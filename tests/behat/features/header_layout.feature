@header @p1 @drevops
Feature: Site header layout

  As a site visitor
  I want the logo and the menu to keep their place at every screen size
  So that I can recognise and navigate the site on any device

  @javascript
  Scenario Outline: Site visitor sees the logos at their natural shape on the content edge at <width>px
    When I set the viewport to "<width>" by "<height>"
    And I visit "/"
    Then the image ".ct-header__logo .ct-logo__image--<logo>" should be rendered at its natural aspect ratio
    And the element ".ct-header__logo .ct-logo__image--<logo>" should start at the left edge of the element ".ct-header__middle .container"
    And the image ".ct-footer__logo .ct-logo__image--<logo>" should be rendered at its natural aspect ratio
    And the element ".ct-footer__logo .ct-logo__image--<logo>" should start at the left edge of the element ".ct-footer__top"

    Examples:
      | width | height | logo    |
      | 600   | 900    | mobile  |
      | 800   | 1024   | mobile  |
      | 1024  | 768    | desktop |
      | 1440  | 900    | desktop |

  # The browser window cannot shrink below about 500px, so the computed
  # 'flex-wrap' stands in for the narrowest phones.
  @javascript
  Scenario: Site visitor sees the logo and the menu button side by side on a phone
    When I set the viewport to "600" by "900"
    And I visit "/"
    Then the child elements of ".ct-header__middle .row" should be on a single row
    And the element ".ct-header__middle .row" should have the computed style "flex-wrap" of "nowrap"

  @javascript
  Scenario Outline: Site visitor sees the primary menu on a single row at <width>px
    When I set the viewport to "<width>" by "<height>"
    And I visit "/"
    Then the child elements of ".ct-header__middle .row" should be on a single row
    And the child elements of ".ct-header .ct-navigation__menu" should be on a single row

    Examples:
      | width | height |
      | 800   | 1024   |
      | 1024  | 768    |
      | 1440  | 900    |

  @javascript
  Scenario: Site visitor sees an opaque header over the page on a phone
    When I set the viewport to "600" by "900"
    And I visit "/"
    Then the element ".ct-header__middle" should have an opaque background
    And the element ".ct-header__middle" should have the computed style "backdrop-filter" of "none"

  @javascript
  Scenario: Site visitor sees a frosted header over the page on a desktop
    When I set the viewport to "1440" by "900"
    And I visit "/"
    Then the element ".ct-header__middle" should have the computed style "backdrop-filter" of "blur(8px)"
