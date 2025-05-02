@robotstxt @seo
Feature: Robots.txt file
  In order to ensure search engines can properly index the site
  As a site administrator
  I want to verify the robots.txt file allows scanning of all public pages

  @api @prod
  Scenario: Verify robots.txt exists and contains appropriate content in production
    Given I am an anonymous user
    When I go to "/robots.txt"
    Then I should get a 200 HTTP response
    And the response should contain "User-agent: *"
    And the response should not contain "Disallow: /"
