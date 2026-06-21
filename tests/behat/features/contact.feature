@contact @p1
Feature: Contact form

  As a site visitor
  I want to send an enquiry through the contact form
  So that I can start a conversation with DrevOps about my platform

  @api
  Scenario: Anonymous user sees the enquiry form and its fields
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see the heading "Contact"
    And I should see "Name"
    And I should see "Email"
    And I should see "Organisation"
    And I should see "What do you need help with?"
    And I should see "Tell us more"
    And I should see the button "Send message"

  @api
  Scenario: The enquiry topic offers all the expected choices
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see "New website build"
    And I should see "Upgrade or migration"
    And I should see "Ongoing support"
    And I should see "Platform audit or site check"
    And I should see "Something else"

  @api
  Scenario: Anonymous user submits an enquiry and sees a confirmation
    Given I am an anonymous user
    When I go to "/contact"
    And I fill in "Name" with "[TEST] Jane Client"
    And I fill in "Email" with "jane@example.com"
    And I fill in "Organisation" with "[TEST] Acme Corp"
    And I select "Upgrade or migration" from "What do you need help with?"
    And I fill in "Tell us more" with "[TEST] We need help upgrading our Drupal 7 site."
    And I press "Send message"
    Then I should see "Thank you - your enquiry has been received."

  @api @javascript
  Scenario: The form blocks submission when required fields are empty
    Given I am an anonymous user
    When I go to "/contact"
    And browser validation for the form ".webform-submission-contact-form" is disabled
    And I press "Send message"
    Then I should see the text "Name field is required."
    And I should see the text "Email field is required."

  @api
  Scenario: A spam bot submission is blocked by the honeypot
    Given I am an anonymous user
    When I go to "/contact"
    And I fill in "Name" with "[TEST] Spam Bot"
    And I fill in "Email" with "spam@example.com"
    And I fill in "url" with "https://spam.example.com"
    And I press "Send message"
    Then I should not see "Thank you - your enquiry has been received."
