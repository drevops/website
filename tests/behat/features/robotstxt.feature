@robotstxt @p1
Feature: Robots.txt file

  As a site owner
  I want to ensure that the robots.txt file is present and correctly configured
  In order to control how search engines crawl and index my site

  @api
  Scenario: Verify robots.txt exists and contains appropriate content in production
    Given I am an anonymous user
    When I go to "/robots.txt"
    Then the response status code should be 200
    And the response should contain "User-agent: *"
    And the response should not contain "Disallow: /"
