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
    And I should see "Thank you for contacting us. We will get back to you within 2 business days."
    And I should see a ".webform-confirmation .ct-message--success[role='status']" element
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

  @javascript
  Scenario: The confirmation scrolls into view below the sticky header after a failed attempt
    Given I am an anonymous user
    When I go to "/contact"
    Given the browser validation for the form ".webform-submission-contact-form" is disabled
    When I press "Send message"
    And I wait for 2 seconds
    Then I should see "Name field is required."
    When I fill in "Name" with "Test User"
    And I fill in "Email" with "test@example.com"
    And I fill in "Message" with "This is a test message for the contact form."
    And I press "Send message"
    And I wait for 2 seconds
    Then I should see "Thank you for contacting us. We will get back to you within 2 business days."
    And the element ".webform-confirmation" should not be covered by the element ".ct-header"

  Scenario: The sample enquiry webform has no public page
    Given I am an anonymous user
    When I go to "/enquiry"
    Then the response status code should be 404
