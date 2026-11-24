@security_headers @p2
Feature: Security headers

  As a site owner
  I want every page to restrict browser features and cross-origin access
  So that other sites cannot use the pages against their visitors

  Scenario: Pages carry the cross-origin and browser feature headers
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Permissions-Policy" should contain "camera=(), microphone=(), geolocation=()"
    And the response header "Permissions-Policy" should contain "fullscreen=(self"
    And the response header "Permissions-Policy" should contain "https://www.youtube.com"
    And the response header "Cross-Origin-Opener-Policy" should contain "same-origin"
    And the response header "Cross-Origin-Resource-Policy" should contain "same-origin"
    And the response header "X-Permitted-Cross-Domain-Policies" should contain "none"
    And the response should not contain the header "X-Generator"

  Scenario: The headers do not displace the content security policy
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Content-Security-Policy" should contain "frame-ancestors 'none'"
