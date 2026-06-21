@blog
Feature: Blog listing and homepage teaser

  As a site visitor
  I want a blog listing and a homepage preview of the latest posts
  So that I can discover and reach DrevOps articles

  @api
  Scenario: A page tagged with the Blog label appears on the listing as a card that links through
    Given the following managed files:
      | path      | uri                          | status |
      | image.jpg | public://blog_test/image.jpg | 1      |
    And the following media "civictheme_image" exist:
      | name              | field_c_m_image |
      | [TEST] Blog Image | image.jpg       |
    And the following "civictheme_page" content:
      | title                | status | field_c_n_topics | field_c_n_summary        | field_read_time | field_c_n_thumbnail |
      | [TEST] Blog Post One | 1      | Blog             | [TEST] Blog post summary | 7 min read      | [TEST] Blog Image   |
    And I am an anonymous user

    When I go to "/blog"
    Then I should get a "200" HTTP response
    And the response should contain "ct-page--blog"
    And I should see "[TEST] Blog Post One"
    And I should see "[TEST] Blog post summary"
    And I should see "7 min read"
    And the response should contain "ct-promo-card"
    And the response should contain "/sites/default/files/styles/civictheme_promo_card"

    When I follow "[TEST] Blog Post One"
    Then I should get a "200" HTTP response
    And I should see "[TEST] Blog Post One"

  @api
  Scenario: The homepage shows a teaser of the latest blog posts
    Given I am an anonymous user

    When I go to the homepage
    Then I should see "From the blog"
    And I should see "All articles"
    And the response should contain "ct-promo-card"
