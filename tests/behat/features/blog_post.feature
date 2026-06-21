@p1 @civictheme @drevops @blog
Feature: Blog post assembly

  As a site visitor
  I want a blog post to open with an image hero and read through to a closing call to action
  So that I can read DrevOps articles in context

  Background:
    Given the following "civictheme_topics" terms:
      | name           |
      | Blog           |
      | [TEST] Topic A |
      | [TEST] Topic B |

    And the following managed files:
      | path      | uri                        | status |
      | image.jpg | public://do_test/image.jpg | 1      |

    And the following media "civictheme_image" exist:
      | name              | field_c_m_image |
      | [TEST] Post Image | image.jpg       |

    And the following "civictheme_page" content:
      | title                 | status | field_c_n_topics                     | field_read_time |
      | [TEST] Assembled Post | 1      | Blog, [TEST] Topic A, [TEST] Topic B | 6 min read      |
      | [TEST] Plain Page     | 1      |                                      |                 |

    And the following fields for the paragraph "hero" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Assembled Post":
      | field_c_p_type  | article           |
      | field_c_p_theme | dark              |
      | field_c_p_image | [TEST] Post Image |

    And the "civictheme_page" "node" "[TEST] Assembled Post" has a "civictheme_rich_text" content paragraph:
      """
      <p class="ct-text-large">[TEST] The lead paragraph of the assembled post.</p>
      <p>[TEST] A body paragraph that follows the hero.</p>
      """

    And the following fields for the paragraph "cta" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Assembled Post":
      | field_c_p_type   | display                     |
      | field_c_p_theme  | dark                        |
      | field_title      | [TEST] Ship faster          |
      | field_subtitle   | [TEST] Get a free review    |
      | field_link:title | [TEST] Start a conversation |
      | field_link:uri   | https://example.com/contact |

  @api
  Scenario: The post opens with an image hero carrying its date, read time, title and tags
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Assembled Post"
    Then I should see an "article .component-hero.component-hero--article" element
    And I should see an "article .component-hero--article.ct-theme-dark" element
    And I should see an "article .component-hero--article .component-hero__image img" element
    And I should see an "article .component-hero--article .component-hero__overlay" element
    And I should see an "article .component-hero__title" element
    And I should see "[TEST] Assembled Post"
    And I should see an "article .component-hero__meta time" element
    And I should see "6 min read"
    And I should see an "article .component-hero__tags .ct-tag" element
    And I should see "[TEST] Topic A"
    And I should see "[TEST] Topic B"

  @api
  Scenario: The article body and the closing call to action render in the dark scheme
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Assembled Post"
    Then I should see an "article .ct-basic-content" element
    And I should see "[TEST] The lead paragraph of the assembled post."
    And I should see an "article .ct-cta" element
    And I should see an "article .ct-cta.ct-theme-dark" element
    And I should see "[TEST] Ship faster"

  @api
  Scenario: A page that opens with a hero hides the legacy banner
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Assembled Post"
    Then I should not see an ".ct-banner" element

  @api
  Scenario: A page without a hero still shows the banner
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Plain Page"
    Then I should see an ".ct-banner" element
