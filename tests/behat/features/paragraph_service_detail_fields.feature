@p1 @civictheme @drevops @service_detail
Feature: Service detail fields

  As a content editor
  I want to add a service detail to a page and fill in its parts
  So that I can present a service in full on the services page

  @api
  Scenario: Fields appear as expected and the title is required
    Given I am logged in as a user with the "Site Administrator" role
    When I visit "node/add/civictheme_page"
    And I fill in "Title" with "[TEST] Page service detail fields"
    And I press "Add Service detail"

    Then I should see the text "Title"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_title][0][value]']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_title][0][value]'].required" element

    And I should see the text "Tagline"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_subtitle][0][value]']" element

    And I should see the text "Description"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_content][0][value]']" element

    And I should see the text "What's included"
    And should see a "[name='field_c_n_components[0][subform][field_p_includes][0][value]']" element

    And I should see the text "Price label"
    And should see a "[name='field_c_n_components[0][subform][field_p_price_label][0][value]']" element

    And I should see the text "Price"
    And should see a "[name='field_c_n_components[0][subform][field_p_price][0][value]']" element

    And I should see the text "Action"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_link][0][uri]']" element

    And I should see the text "Theme"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_theme]']" element
