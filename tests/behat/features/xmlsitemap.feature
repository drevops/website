@xmlsitemap @p1
Feature: XML sitemap

  As a site owner
  I want published pages to be listed in the XML sitemap
  So that search engines can discover and index the site

  @api
  Scenario: Sitemap is served as a well-formed sitemap document
    Given I run drush "xmlsitemap:regenerate"
    And I am an anonymous user
    When I go to "/sitemap.xml"
    Then the response status code should be 200
    And the response should be in XML format
    And the XML should use the namespace "http://www.sitemaps.org/schemas/sitemap/0.9"
    And the XML element "//*[local-name()='urlset']" should exist
    And the XML element "//*[local-name()='url']/*[local-name()='loc']" should exist

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

  @api
  Scenario: Sitemap lists URLs on the canonical host
    Given I run drush "xmlsitemap:rebuild" "--yes"
    And I am an anonymous user
    When I go to "sitemap.xml"
    Then the response status code should be 200
    # Cron regenerates the sitemap with no request to take a host from, so the
    # host is stated in settings. Any other host makes every listed URL a
    # redirect, because that is what the site serves.
    And the response should contain "<loc>https://www.drevops.com/"
    And the response should not contain "<loc>https://drevops.com/"

  @api @blog
  Scenario: Sitemap lists published blog posts only
    Given the following "blog" content:
      | title                        | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Sitemap Indexed Post  | published        | large                 | inherit                | normal                      | both                       |
      | [TEST] Sitemap Excluded Post | draft            | large                 | inherit                | normal                      | both                       |
    And I run drush "xmlsitemap:rebuild" "--yes"
    And I am an anonymous user
    When I go to "sitemap.xml"
    Then the response status code should be 200
    # Asserting the full alias covers both sitemap inclusion and the bundle's
    # pathauto pattern placing the post under "/blog".
    And the response should contain "blog/test-sitemap-indexed-post"
    And the response should not contain "sitemap-excluded-post"

  @api @project
  Scenario: Sitemap lists published projects only
    Given the following "project" content:
      | title                           | moderation_state | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Sitemap Indexed Project  | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Sitemap Excluded Project | draft            | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    And I run drush "xmlsitemap:rebuild" "--yes"
    And I am an anonymous user
    When I go to "sitemap.xml"
    Then the response status code should be 200
    And the response should contain "work/test-sitemap-indexed-project"
    And the response should not contain "sitemap-excluded-project"
