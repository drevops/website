@p1 @civictheme @drevops @card
Feature: Card markers

  As a content editor
  I want one card that can lead with a number, a dot or an icon
  So that I can build numbered lists, feature lists and icon grids without separate components

  Background:
    Given the following managed files:
      | path     | uri                       | status |
      | icon.svg | public://do_test/icon.svg | 1      |

    And the following media "civictheme_icon" exist:
      | name           | field_c_m_icon |
      | [TEST] DO Icon | icon.svg       |

    And the following "civictheme_page" content:
      | title                      | status |
      | [TEST] Page Cards numbered | 1      |
      | [TEST] Page Cards markers  | 1      |
      | [TEST] Page Cards columns  | 1      |

  @api
  Scenario: A content editor is offered the card group component on a page
    Given I am logged in as a user with the "Site Administrator" role
    When I visit "node/add/civictheme_page"
    Then the response should contain "Add Card group"

  @api
  Scenario: Number cards lead with a sequential index reflecting their order
    Given I am an anonymous user
    And a card group with 1 columns and the following cards on the "[TEST] Page Cards numbered" page:
      | type   | title            | description             |
      | number | [TEST] Discovery | [TEST] We map the work. |
      | number | [TEST] Delivery  | [TEST] We build it.     |
      | number | [TEST] Support   | [TEST] We maintain it.  |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Cards numbered"
    Then I should see an "article .ct-card-group.ct-card-group--cols-1" element
    And I should see 3 ".ct-card--number" elements
    And I should see "01" in the ".ct-card:nth-child(1) .ct-card__number" element
    And I should see "02" in the ".ct-card:nth-child(2) .ct-card__number" element
    And I should see "03" in the ".ct-card:nth-child(3) .ct-card__number" element
    And I should see the text "[TEST] Discovery"
    And I should see the text "[TEST] We map the work."
    And save screenshot

  @api
  Scenario: Dot and icon cards lead with their markers
    Given I am an anonymous user
    And a card group with 1 columns and the following cards on the "[TEST] Page Cards markers" page:
      | type | title         | description      | icon           |
      | dot  | [TEST] Tested | [TEST] Covered.  |                |
      | icon | [TEST] Secure | [TEST] Hardened. | [TEST] DO Icon |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Cards markers"
    Then I should see a ".ct-card--dot .ct-card__dot" element
    And I should see a ".ct-card--icon .ct-card__icon svg" element
    And I should see the text "[TEST] Tested"
    And I should see the text "[TEST] Secure"

  @api
  Scenario Outline: Card groups render in the configured column count
    Given I am an anonymous user
    And a card group with <columns> columns and the following cards on the "[TEST] Page Cards columns" page:
      | type | title           | description      |
      | dot  | [TEST] Item one | [TEST] Body one. |
      | dot  | [TEST] Item two | [TEST] Body two. |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Cards columns"
    Then I should see an "article .ct-card-group--cols-<columns>" element

    Examples:
      | columns |
      | 1       |
      | 2       |
      | 4       |
