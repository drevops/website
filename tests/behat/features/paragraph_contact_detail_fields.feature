@p1 @civictheme @drevops @contact_detail
Feature: Contact detail fields

  As a content editor
  I want to add a contact detail to a page with a label, value and note
  So that I can build the contact information column from structured pieces

  @api
  Scenario: The form exposes label, value and note, with the label required
    Given I am logged in as a user with the "Site Administrator" role
    When I visit "node/add/civictheme_page"
    And I fill in "Title" with "[TEST] Page contact detail fields"
    And I press "Add Contact detail"

    Then I should see the text "Label"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_subtitle][0][value]']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_subtitle][0][value]'].required" element

    And I should see the text "Value"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_content][0][value]']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_content][0][value]'].required" element

    And I should see the text "Note"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_summary][0][value]']" element

    And I should see the text "Theme"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_theme]']" element
