@securitytxt @p1
Feature: Security.txt file

  As a site visitor
  I want to find the security contact at the standard security.txt location
  So that I can report a vulnerability to the right people

  @api
  Scenario: Verify security.txt is served with the vulnerability reporting contact
    Given I am an anonymous user
    When I go to "/.well-known/security.txt"
    Then the response status code should be 200
    And the response header "content-type" should contain the value "text/plain"
    And the response should contain "Contact: mailto:info@drevops.com"
    And the response should contain "Expires:"
    And the response should contain "Preferred-Languages: en"
    And the response should contain "Canonical: https://www.drevops.com/.well-known/security.txt"
