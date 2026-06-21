@p1 @civictheme @drevops @hero
Feature: Hero fields

  As a content editor
  I want to add a hero to a page and choose its type
  So that I can open a page or introduce a section in the right context

  @api
  Scenario: Fields appear as expected and the heading is required
    Given I am logged in as a user with the "Site Administrator" role
    When I visit "node/add/civictheme_page"
    And I fill in "Title" with "[TEST] Page hero fields"
    And I press "Add Hero"

    Then I should see the text "Type"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_type]']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_type]'] option[value='home']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_type]'] option[value='inner']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_type]'] option[value='page']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_type]'] option[value='contact']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_type]'] option[value='article']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_type]'] option[value='section']" element

    And I should see the text "Eyebrow"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_subtitle][0][value]']" element

    And I should see the text "Heading"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_title][0][value]']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_title][0][value]'].required" element

    And I should see the text "Lead"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_summary][0][value]']" element

    And I should see the text "Actions"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_links][0][uri]']" element

    And I should see the text "Background image"

    And I should see the text "Theme"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_theme]']" element
