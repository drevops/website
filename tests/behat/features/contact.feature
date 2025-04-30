@contact @form
Feature: Contact form

  Ensure that contact form is reachable and works correctly.

  @api @javascript
  Scenario: Anonymous user can use the Contact link and Contact form.
    Given I am an anonymous user
    When I go to homepage
    Then I should see the link Contact
    And I click the link with title Contact
    Then I should see the heading Contact
    And I should see "Contact"
    And I should see "Your Name"
    And I should see "Your Email"
    And I should see "Subject"
    And I should see "Message"
    And I should see the button "Send message"

  @api @javascript
  Scenario: Anonymous user can fill and submit the contact form
    Given I am an anonymous user
    When I go to homepage
    And I click the link with title Contact
    Then I fill in "Name" with "Test User"
    And I fill in "Email" with "test@example.com"
    And I fill in "Subject" with "Test Contact"
    And I fill in "Message" with "This is a test message for the contact form."
    And I press "Send message"
    Then I save screenshot
    And I should see the text "Your message has been sent."
    Then I save screenshot

  @api @javascript
  Scenario: Anonymous user gets validation errors on contact form
    Given I am an anonymous user
    When I go to homepage
    And I click the link with title Contact
    And I disable browser validation for the form with selector "form.webform-submission-contact-form"
    When I press "Send message"
    Then I should see the text "Name field is required."
    And I should see the text "Email field is required."
    And I should see the text "Subject field is required."
    And I should see the text "Message field is required."
    And I save screenshot
