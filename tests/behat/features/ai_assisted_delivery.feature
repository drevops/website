@p1 @civictheme @drevops @ai_assisted_delivery
Feature: AI-assisted delivery page

  As a prospective client already on Drupal
  I want a page that explains the AI-assisted offer and what it would cost
  So that I can decide whether to ask for a costing

  Background:
    Given the following "civictheme_page" content:
      | title                       | status | field_c_n_hide_sidebar |
      | [TEST] AI-assisted delivery | 1      | 1                      |

    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] AI-assisted delivery":
      | field_c_p_type        | inner                                   |
      | field_c_p_theme       | dark                                    |
      | field_c_p_subtitle    | [TEST] AI-assisted delivery             |
      | field_c_p_title       | [TEST] The same senior Drupal work      |
      | field_c_p_summary     | [TEST] Same senior work, often for less |
      | field_c_p_links:title | See what it would cost                  |
      | field_c_p_links:uri   | internal:/contact                       |

    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] AI-assisted delivery":
      | field_c_p_type     | section                             |
      | field_c_p_theme    | dark                                |
      | field_c_p_subtitle | [TEST] A free no-obligation costing |
      | field_c_p_title    | [TEST] No commitment just a picture |
      | field_c_p_summary  | [TEST] Send a quote or a link       |

    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] AI-assisted delivery":
      | field_c_p_type     | section                          |
      | field_c_p_theme    | dark                             |
      | field_c_p_subtitle | [TEST] Why agency work costs      |
      | field_c_p_title    | [TEST] It is rarely about work   |

    And a card group with 1 columns and the following cards on the "[TEST] AI-assisted delivery" page:
      | type | title                  | description                  |
      | dot  | [TEST] Layers of work  | [TEST] Coordination is real. |
      | dot  | [TEST] Winning work    | [TEST] Sales lands in rates. |
      | dot  | [TEST] Scratch restart | [TEST] No shared tooling.    |

    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] AI-assisted delivery":
      | field_c_p_type     | section                            |
      | field_c_p_theme    | dark                               |
      | field_c_p_subtitle | [TEST] How we keep it lower        |
      | field_c_p_title    | [TEST] Four things bring it down   |
      | field_c_p_summary  | [TEST] Around a third fewer hours. |

    And a card group with 1 columns and the following cards on the "[TEST] AI-assisted delivery" page:
      | type | title                 | description                   |
      | dot  | [TEST] Pay engineers  | [TEST] No extra layer to fund. |
      | dot  | [TEST] Tooling lifts  | [TEST] Vortex handles setup.   |
      | dot  | [TEST] CI from day one | [TEST] Quality is built in.   |
      | dot  | [TEST] AI on the rote | [TEST] Senior judgement stays. |

    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] AI-assisted delivery":
      | field_c_p_type     | section                          |
      | field_c_p_theme    | dark                             |
      | field_c_p_subtitle | [TEST] A fair question about AI   |
      | field_c_p_title    | [TEST] Can AI code be trusted     |

    And a card group with 1 columns and the following cards on the "[TEST] AI-assisted delivery" page:
      | type | title                   | description                   |
      | dot  | [TEST] Reviewed, tested | [TEST] CI gates every build.  |
      | dot  | [TEST] Your data stays  | [TEST] Nothing trains models. |

    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] AI-assisted delivery":
      | field_c_p_type     | section                            |
      | field_c_p_theme    | dark                               |
      | field_c_p_subtitle | [TEST] Who you would work with     |
      | field_c_p_title    | [TEST] Drupal is what we do        |
      | field_c_p_summary  | [TEST] We created Vortex and more. |

    And the following fields for the paragraph "cta" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] AI-assisted delivery":
      | field_c_p_type   | display                                    |
      | field_c_p_theme  | dark                                       |
      | field_title      | [TEST] See what your project would cost    |
      | field_subtitle   | [TEST] Send your scope and your last quote |
      | field_link:title | See what it would cost, info@drevops.com   |
      | field_link:uri   | internal:/contact, mailto:info@drevops.com |

  @api
  Scenario: Anonymous user sees the hero, intro bands, dot lists and CTA all in the dark scheme
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] AI-assisted delivery"
    Then I should see an "article .component-hero--inner.ct-theme-dark" element
    And I should see 5 "article .component-hero--section.ct-theme-dark" elements
    And I should see 3 "article .ct-card-group.ct-card-group--cols-1.ct-theme-dark" elements
    And I should see 9 "article .ct-card--dot.ct-theme-dark" elements
    And I should see an "article .ct-cta.ct-theme-dark" element
    And I should see an "article a.ct-cta__email" element
    And I should see the text "[TEST] The same senior Drupal work"
    And I should see the text "[TEST] A free no-obligation costing"
    And I should see the text "[TEST] Send a quote or a link"
    And I should see the text "[TEST] It is rarely about work"
    And I should see the text "[TEST] Layers of work"
    And I should see the text "[TEST] Drupal is what we do"

  @api
  Scenario: The CivicTheme banner is suppressed so the hero opens the page
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] AI-assisted delivery"
    Then I should see an "article .component-hero--inner" element
    And I should not see a ".ct-banner" element

  @api
  Scenario: A content editor can assemble the page from the hero, dot-list card and CTA components
    Given I am logged in as a user with the "Content Author" role
    When I visit "node/add/civictheme_page"
    Then the response should contain "Add Hero"
    And the response should contain "Add Card group"
    And the response should contain "Add CTA band"

  @api @javascript
  Scenario: The assembled page keeps its section order and reflows on a phone
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] AI-assisted delivery"
    Then the page components render in this order:
      | hero:inner   |
      | hero:section |
      | hero:section |
      | card-group   |
      | hero:section |
      | card-group   |
      | hero:section |
      | card-group   |
      | hero:section |
      | cta          |
    And the page reflows without horizontal scrolling at 390 pixels wide
