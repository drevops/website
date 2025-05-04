@seckit @p2
Feature: Seckit

  Ensure that security settings are correct.

  @api
  Scenario: Check for HSTS and CSP headers
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And response header "Content-Security-Policy" contains "connect-src 'self' https://www.googletagmanager.com https://www.google-analytics.com https://www.recaptcha.net https://www.google.com;"
    And response header "Content-Security-Policy" contains "default-src 'self';"
    And response header "Content-Security-Policy" contains "font-src 'self' https://fonts.gstatic.com;"
    And response header "Content-Security-Policy" contains "img-src 'self' data"
    And response header "Content-Security-Policy" contains "media-src 'self'"
    And response header "Content-Security-Policy" contains "report-uri /report-csp-violation"
    And response header "Content-Security-Policy" contains "script-src 'self' https://www.googletagmanager.com https://www.gstatic.com https://www.recaptcha.net  https://www.google.com;"
    And response header "Content-Security-Policy" contains "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com/;"
    And response header "Strict-Transport-Security" contains "max-age=31536000"
    And response header "Strict-Transport-Security" contains "includeSubDomains"
    And response header "Strict-Transport-Security" contains "preload"
    And response header "Expect-CT" contains "max-age=86400, enforce"
    And response header "Feature-Policy" contains "camera 'none'; microphone 'none'; geolocation 'none'; fullscreen 'self'"
    And response header "From-Origin" contains "same"
    And response header "Referrer-Policy" contains "strict-origin-when-cross-origin"
    And response header "X-Content-Type-Options" contains "nosniff"
