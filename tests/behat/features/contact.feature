@contact @p1
Feature: Contact form

  As anonymous user
  I want to ensure that the contact form is accessible
  In order to be able to contact the site owner

  Scenario: Anonymous user can use the Contact link and Contact form.
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see the heading Contact
    And I should see "Contact"
    And I should see "Your Name"
    And I should see "Your Email"
    And I should see "Message"
    And I should see the button "Send message"

  Scenario: Anonymous user can fill and submit the contact form
    Given I am an anonymous user
    When I go to "/contact"
    Then I should see the heading Contact
    When I fill in "Name" with "Test User"
    And I fill in "Email" with "test@example.com"
    And I fill in "Message" with "This is a test message for the contact form."
    And I save screenshot
    And I press "Send message"
    Then I should not see an "#edit-submit" element
    And I should see "Thank you for contacting us. We will get back to you within one business day."
    And I should see a ".webform-confirmation .ct-message--success" element
    And I should not see a ".webform-confirmation__back" element
    And I save screenshot

  @javascript
  Scenario: Anonymous user gets validation errors on contact form
    Given I am an anonymous user
    When I go to "/contact"
    Given the browser validation for the form ".webform-submission-contact-form" is disabled
    Then I should see the heading Contact
    When I press "Send message"
    Then I should see "Name field is required."
    And I should see "Email field is required."
    And I should see "Message field is required."
    And I save screenshot
