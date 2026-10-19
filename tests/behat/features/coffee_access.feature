Feature: Coffee module access for site administrator

  As a site administrator
  I want to access the coffee module functionality
  So that I can quickly navigate the site

  @javascript
  Scenario: Site administrator can see coffee form wrapper after triggering
    Given I am logged in as a user with the role "civictheme_site_administrator"
    When I visit "/"
    Then I should see an ".coffee-form-wrapper" element
