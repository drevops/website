@seckit @p2
Feature: Seckit

  As a site owner
  I want to ensure that the Seckit module is correctly configured
  In order to improve security and protect against common vulnerabilities

  Scenario: Seckit emits the expected non-CSP security headers
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Strict-Transport-Security" should contain "max-age=31536000"
    And the response header "Strict-Transport-Security" should contain "includeSubDomains"
    And the response header "Strict-Transport-Security" should contain "preload"
    And the response header "Expect-CT" should contain "max-age=86400, enforce"
    And the response header "Feature-Policy" should contain "camera 'none'; microphone 'none'; geolocation 'none'; fullscreen 'self'"
    And the response header "From-Origin" should contain "same"
    And the response header "Referrer-Policy" should contain "strict-origin-when-cross-origin"
    And the response header "X-Content-Type-Options" should contain "nosniff"

  Scenario: Clickjacking protection script is served from a versioned URL
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    # An unversioned URL lets a stale script survive in browser and CDN caches,
    # leaving the clickjacking stylesheet applied and the page blank.
    And the response should contain "seckit.document_write.js?v="
