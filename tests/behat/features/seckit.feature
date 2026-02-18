@seckit @p2
Feature: Seckit

  As a site owner
  I want to ensure that the Seckit module is correctly configured
  In order to improve security and protect against common vulnerabilities

  @api
  Scenario: Check for HSTS and CSP headers
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Content-Security-Policy" should contain the value "connect-src 'self' https://www.googletagmanager.com https://www.google-analytics.com https://www.recaptcha.net https://www.google.com;"
    And the response header "Content-Security-Policy" should contain the value "default-src 'self';"
    And the response header "Content-Security-Policy" should contain the value "font-src 'self' https://fonts.gstatic.com;"
    And the response header "Content-Security-Policy" should contain the value "img-src 'self' data"
    And the response header "Content-Security-Policy" should contain the value "media-src 'self'"
    And the response header "Content-Security-Policy" should contain the value "report-uri /report-csp-violation"
    And the response header "Content-Security-Policy" should contain the value "script-src 'self' https://www.googletagmanager.com https://www.gstatic.com https://www.recaptcha.net https://www.google.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net/gh/cferdinandi/tabby@12.0.3/dist/js/tabby.min.js https://unpkg.com/@popperjs/core@2.11.6/dist/umd/popper.js https://unpkg.com/tippy.js@6.3.7/dist/tippy.umd.js;"
    And the response header "Content-Security-Policy" should contain the value "style-src 'self' https://cdnjs.cloudflare.com/ajax/libs/highlight.js/ 'unsafe-inline' https://fonts.googleapis.com/ https://cdn.jsdelivr.net/gh/cferdinandi/tabby@12.0.3/dist/css/tabby-ui.min.css https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/codemirror.css https://unpkg.com/tippy.js@6.3.7/dist/tippy.css https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css;"
    And the response header "Strict-Transport-Security" should contain the value "max-age=31536000"
    And the response header "Strict-Transport-Security" should contain the value "includeSubDomains"
    And the response header "Strict-Transport-Security" should contain the value "preload"
    And the response header "Expect-CT" should contain the value "max-age=86400, enforce"
    And the response header "Feature-Policy" should contain the value "camera 'none'; microphone 'none'; geolocation 'none'; fullscreen 'self'"
    And the response header "From-Origin" should contain the value "same"
    And the response header "Referrer-Policy" should contain the value "strict-origin-when-cross-origin"
    And the response header "X-Content-Type-Options" should contain the value "nosniff"
