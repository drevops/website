@metatags @p0
Feature: Page content metatags

  As a site owner
  I want to ensure that the default metatags are present for all pages
  In order to improve SEO and social sharing

  @api
  Scenario: CivicTheme page content type contains default metatags
    Given the following civictheme_page content:
      | title              | status | field_c_n_summary                           |
      | Test Metatags Page | 1      | This is a test summary for metatags testing |
    When I visit the "civictheme_page" content page with the title "Test Metatags Page"
    Then the response should contain "<title>Test Metatags Page | "
    And the response should contain "<meta name=\"description\" content=\"This is a test summary for metatags testing\""
    And the response should contain "<link rel=\"canonical\" href=\""

  @api
  Scenario: Page without a thumbnail is shared with the site-wide card image
    Given the following civictheme_page content:
      | title                   | status | field_c_n_summary                      |
      | [TEST] Social Card Page | 1      | [TEST] A summary shown on a share card |
    When I visit the "civictheme_page" content page with the title "[TEST] Social Card Page"
    Then the response should contain "<meta property=\"og:site_name\" content=\"DrevOps\""
    And the response should contain "<meta property=\"og:type\" content=\"website\""
    And the response should contain "<meta property=\"og:title\" content=\"[TEST] Social Card Page | DrevOps\""
    And the response should contain "<meta property=\"og:description\" content=\"[TEST] A summary shown on a share card\""
    And the response should contain "/modules/custom/do_base/assets/social-share.png\""
    And the response should contain "<meta property=\"og:image:width\" content=\"1200\""
    And the response should contain "<meta property=\"og:image:height\" content=\"630\""
    # Without this card type X renders a small square thumbnail instead of the
    # wide image, which is the whole point of supplying a 1200x630 asset.
    And the response should contain "<meta name=\"twitter:card\" content=\"summary_large_image\""
    And the response should contain "<meta name=\"twitter:site\" content=\"@drev_ops\""
    And the response should contain "<meta name=\"twitter:title\" content=\"[TEST] Social Card Page | DrevOps\""
    And the response should contain "<meta name=\"twitter:description\" content=\"[TEST] A summary shown on a share card\""
    And the response should contain "<meta name=\"twitter:image\" content=\""

  @api
  Scenario: Page with a thumbnail is shared with that image, sized for the card
    Given the following managed files:
      | path      | uri                        | status |
      | image.jpg | public://do_test/image.jpg | 1      |
    And the following media "civictheme_image" exist:
      | name                     | field_c_m_image |
      | [TEST] Social Card Image | image.jpg       |
    And the following civictheme_page content:
      | title                             | status | field_c_n_thumbnail      |
      | [TEST] Social Card Thumbnail Page | 1      | [TEST] Social Card Image |
    When I visit the "civictheme_page" content page with the title "[TEST] Social Card Thumbnail Page"
    Then the response should contain "/styles/social_share/"
    And the response should not contain "/modules/custom/do_base/assets/social-share.png"

  @api
  Scenario: Front page is shared with a complete card
    Given I am an anonymous user
    When I am on the homepage
    Then the response should contain "<meta property=\"og:image\" content=\""
    And the response should contain "<meta property=\"og:description\" content=\""
    And the response should contain "<meta name=\"twitter:card\" content=\"summary_large_image\""
    And the response should contain "<meta name=\"twitter:image\" content=\""

  @api @blog
  Scenario: Blog post is marked up as an article for search engines
    Given the following blog content:
      | title                   | status | field_c_n_summary        | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Social Card Post | 1      | [TEST] A summary of post | large                 | inherit                | normal                      | both                       |
    When I visit the "blog" content page with the title "[TEST] Social Card Post"
    Then the response should contain "<meta property=\"og:type\" content=\"article\""
    And the response should contain "<meta property=\"article:published_time\" content=\""
    And the response should contain "<script type=\"application/ld+json\">"
    And the response should contain "\"@type\": \"Article\""
    And the response should contain "\"@type\": \"Organization\""
    And the response should contain "\"@type\": \"WebSite\""
    And the response should contain "\"headline\": \"[TEST] Social Card Post\""
    # The post carries no thumbnail, so this also proves the structured data
    # image shares the resolver's fallback rather than being dropped.
    And the response should contain "/modules/custom/do_base/assets/social-share.png"
