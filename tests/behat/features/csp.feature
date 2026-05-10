@csp @p2
Feature: Content Security Policy

  As a site owner
  I want the Content-Security-Policy header to include a per-request nonce
  and match the previously enforced policy
  So that BigPipe and other Drupal inline scripts are not blocked by CSP
  and no source is silently widened or narrowed during the migration

  @api
  Scenario: CSP header contains a nonce for anonymous users
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Content-Security-Policy" should contain the value "'nonce-"

  @api
  Scenario: CSP header contains a nonce for authenticated users
    Given I am logged in as a user with the "administrator" role
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Content-Security-Policy" should contain the value "'nonce-"

  @api
  Scenario: CSP policy preserves previously allowed sources
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Content-Security-Policy" should contain the value "default-src 'self'"
    And the response header "Content-Security-Policy" should contain the value "object-src 'none'"
    And the response header "Content-Security-Policy" should contain the value "frame-ancestors 'none'"
    And the response header "Content-Security-Policy" should contain the value "report-uri /report-csp-violation"
    And the response header "Content-Security-Policy" should contain the value "https://www.googletagmanager.com"
    And the response header "Content-Security-Policy" should contain the value "https://www.recaptcha.net"
    And the response header "Content-Security-Policy" should contain the value "https://www.youtube.com"
    And the response header "Content-Security-Policy" should contain the value "https://fonts.gstatic.com"
    And the response header "Content-Security-Policy" should contain the value "https://www.google-analytics.com"
