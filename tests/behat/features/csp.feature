@csp @p2
Feature: Content Security Policy

  As a site owner
  I want the Content-Security-Policy header to include a per-request nonce
  and match the previously enforced policy
  So that BigPipe and other Drupal inline scripts are not blocked by CSP
  and no source is silently widened or narrowed during the migration

  Scenario: CSP header contains a nonce for anonymous users
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Content-Security-Policy" should contain "'nonce-"

  Scenario: CSP header contains a nonce for authenticated users
    Given I am logged in as a user with the role "administrator"
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Content-Security-Policy" should contain "'nonce-"

  Scenario: CSP policy preserves previously allowed sources
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Content-Security-Policy" should contain "default-src 'self'"
    And the response header "Content-Security-Policy" should contain "object-src 'none'"
    And the response header "Content-Security-Policy" should contain "frame-ancestors 'none'"
    And the response header "Content-Security-Policy" should contain "report-uri /report-csp-violation"
    And the response header "Content-Security-Policy" should contain "https://www.googletagmanager.com"
    And the response header "Content-Security-Policy" should contain "https://www.recaptcha.net"
    And the response header "Content-Security-Policy" should contain "https://www.youtube.com"
    And the response header "Content-Security-Policy" should contain "https://www.google-analytics.com"

  Scenario: CSP allows no external font source
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    # Both faces are served from this origin, so a policy that still reaches
    # out to Google is one a stylesheet could quietly start using again. There
    # is no "font-src" to assert: the module omits a directive that matches
    # "default-src", so fonts fall through to the "'self'" asserted above.
    And the response header "Content-Security-Policy" should not contain "https://fonts.gstatic.com"
    And the response header "Content-Security-Policy" should not contain "https://fonts.googleapis.com"
