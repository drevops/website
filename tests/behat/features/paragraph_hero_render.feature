@p1 @civictheme @drevops @hero
Feature: Hero render

  As a site visitor
  I want each hero variant to render in its intended treatment
  So that pages and sections open in the right context

  Background:
    Given the following managed files:
      | path      | uri                        | status |
      | image.jpg | public://do_test/image.jpg | 1      |

    And the following media "civictheme_image" exist:
      | name            | field_c_m_image |
      | [TEST] DO Image | image.jpg       |

    And the following "civictheme_page" content:
      | title                    | status |
      | [TEST] Page Hero home    | 1      |
      | [TEST] Page Hero section | 1      |
      | [TEST] Page Hero article | 1      |

  @api @javascript
  Scenario: The home hero opens with a full banner, scroll indicator and content
    Given I am an anonymous user
    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Hero home":
      | field_c_p_type     | home                  |
      | field_c_p_theme    | dark                  |
      | field_c_p_subtitle | [TEST] Hero eyebrow   |
      | field_c_p_title    | [TEST] Hero home lead |
      | field_c_p_summary  | [TEST] Hero home body |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Hero home"
    Then I should see an "article .component-hero.component-hero--home" element
    And I should see an "article .component-hero--home.ct-theme-dark" element
    And I should see an "article .component-hero--home .component-hero-eyebrow" element
    And I should see an "article .component-hero--home .component-hero-title" element
    And I should see an "article .component-hero--home .component-hero-lead" element
    And I should see an "article .component-hero--home .component-scroll-indicator" element
    And I should see the text "[TEST] Hero home lead"
    And save screenshot

  @api @javascript
  Scenario: The section hero renders a centred band with no scroll indicator
    Given I am an anonymous user
    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Hero section":
      | field_c_p_type     | section                  |
      | field_c_p_theme    | dark                     |
      | field_c_p_subtitle | [TEST] Hero section label |
      | field_c_p_title    | [TEST] Hero section lead |
      | field_c_p_summary  | [TEST] Hero section body |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Hero section"
    Then I should see an "article .component-hero.component-hero--section" element
    And I should see an "article .component-hero--section .component-hero-title" element
    And I should not see an "article .component-hero--section .component-scroll-indicator" element
    And I should see the text "[TEST] Hero section lead"
    And save screenshot

  @api @javascript
  Scenario: The article hero renders the background image behind an overlay
    Given I am an anonymous user
    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Page Hero article":
      | field_c_p_type  | article                  |
      | field_c_p_theme | dark                     |
      | field_c_p_title | [TEST] Hero article lead |
      | field_c_p_image | [TEST] DO Image          |

    When I visit the "civictheme_page" content page with the title "[TEST] Page Hero article"
    Then I should see an "article .component-hero.component-hero--article" element
    And I should see an "article .component-hero--article .component-hero__image img" element
    And I should see an "article .component-hero--article .component-hero__overlay" element
    And I should see an "article .component-hero--article .component-hero__title" element
    And I should see the text "[TEST] Hero article lead"
    And save screenshot
