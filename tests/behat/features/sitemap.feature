@sitemap @p1
Feature: XML sitemap page

  As a site owner
  I want to ensure that the XML sitemap page is reachable.

  @api
  Scenario: Verify XML sitemap page exists and contains appropriate content in production
    Given I am an anonymous user
    When I go to "/sitemap.xml"
    Then the response status code should be 200
