@xmlsitemap @p1
Feature: XML sitemap page

  As a site owner
  I want to ensure that the XML sitemap page is reachable.

  @api
  Scenario: XML Sitemap is accessible
    Given I run drush "simple-sitemap:generate" "--uri=http://example.com"
    When I go to "sitemap.xml"
    Then the response status code should be 200
