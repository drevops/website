@seckit @p2
Feature: Seckit

  As a site owner
  I want to ensure that the Seckit module is correctly configured
  In order to improve security and protect against common vulnerabilities

  @api
  Scenario: Seckit emits the expected non-CSP security headers
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Strict-Transport-Security" should contain the value "max-age=31536000"
    And the response header "Strict-Transport-Security" should contain the value "includeSubDomains"
    And the response header "Strict-Transport-Security" should contain the value "preload"
    And the response header "Expect-CT" should contain the value "max-age=86400, enforce"
    And the response header "Feature-Policy" should contain the value "camera 'none'; microphone 'none'; geolocation 'none'; fullscreen 'self'"
    And the response header "From-Origin" should contain the value "same"
    And the response header "Referrer-Policy" should contain the value "strict-origin-when-cross-origin"
    And the response header "X-Content-Type-Options" should contain the value "nosniff"
