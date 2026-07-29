@xmlsitemap @p1
Feature: XML sitemap

  As a site owner
  I want published pages to be listed in the XML sitemap
  So that search engines can discover and index the site

  Background:
    Given the following "civictheme_page" content:
      | title                        | moderation_state |
      | [TEST] Sitemap Indexed Page  | published        |
      | [TEST] Sitemap Excluded Page | draft            |
    And I run drush "xmlsitemap:rebuild" "--yes"

  @api
  Scenario: Sitemap lists the front page and published pages only
    Given I am an anonymous user
    When I go to "sitemap.xml"
    Then the response status code should be 200
    And the response header "content-type" should contain the value "xml"
    And the response should contain "<urlset"
    # Only the front page carries an explicit priority: every other link uses
    # the 0.5 default, which the sitemap format omits.
    And the response should contain "<priority>1.0</priority>"
    And the response should contain "sitemap-indexed-page"
    And the response should not contain "sitemap-excluded-page"
