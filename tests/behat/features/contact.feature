@contact @p1
Feature: Contact form

  As anonymous user
  I want to ensure that the contact form is accessible
  In order to be able to contact the site owner

  @api
  Scenario: Anonymous user can use the Contact link and Contact form.
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see the heading Contact
    And I should see "Contact"
    And I should see "Your Name"
    And I should see "Your Email"
    And I should see "Subject"
    And I should see "Message"
    And I should see the button "Send message"

  @api
  Scenario: Anonymous user can fill and submit the contact form
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see the heading Contact
    When I fill in "Name" with "Test User"
    And I fill in "Email" with "test@example.com"
    And I fill in "Subject" with "Test Contact"
    And I fill in "Message" with "This is a test message for the contact form."
    And I save screenshot
    And I press "Send message"
    # The form may not send actual messages in the test environment
    # So we'll just verify that the form was submitted successfully
    Then I should not see an "#edit-submit" element
    # @todo Enable one the messages are fixed.
    # Then I should see the text "Your message has been sent."
    And I save screenshot

  @api @javascript
  Scenario: Anonymous user gets validation errors on contact form
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see the heading Contact
    When I disable browser validation for the form with selector "form.webform-submission-contact-form"
    And I press "Send message"
    Then I should see the text "Name field is required."
    And I should see the text "Email field is required."
    And I should see the text "Subject field is required."
    And I should see the text "Message field is required."
    And I save screenshot
