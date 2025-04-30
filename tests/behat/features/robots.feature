@robotstxt @seo
Feature: Robots.txt file
  In order to ensure search engines can properly index the site
  As a site administrator
  I want to verify the robots.txt file allows scanning of all public pages

  @api
  Scenario: Verify robots.txt exists and contains appropriate content
    Given I am an anonymous user
    When I go to "/robots.txt"
    Then I should get a 200 HTTP response
    And the response should contain "User-agent: *"
    And the response should not contain "Disallow: /"

  @api
  Scenario: Verify robots.txt allows public content
    Given I am an anonymous user
    When I go to "/robots.txt"
    Then I should get a 200 HTTP response
    And the following paths should be allowed for robots:
      | Path      |
      | /         |
      | /contact  |
      | /node/1   |
