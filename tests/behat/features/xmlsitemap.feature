@xmlsitemap @p1
Feature: XML sitemap

  As a site owner
  I want published pages to be listed in the XML sitemap
  So that search engines can discover and index the site

  @api
  Scenario: Sitemap lists the front page and published pages only
    Given the following "civictheme_page" content:
      | title                        | moderation_state |
      | [TEST] Sitemap Indexed Page  | published        |
      | [TEST] Sitemap Excluded Page | draft            |
    And I run drush "xmlsitemap:rebuild" "--yes"
    And I am an anonymous user
    When I go to "sitemap.xml"
    Then the response status code should be 200
    And the response header "content-type" should contain the value "xml"
    And the response should contain "<urlset"
    # The front page is the only entry carrying an explicit priority, because
    # every other link uses the 0.5 default that the sitemap format omits. The
    # closing "loc" anchors it to a real URL entry rather than a stray value.
    And the response should contain "</loc><changefreq>daily</changefreq><priority>1.0</priority>"
    And the response should contain "sitemap-indexed-page"
    And the response should not contain "sitemap-excluded-page"
