@p1 @civictheme @civictheme_manual_list
Feature: Manual list fields

  Scenario: Fields appear as expected
    Given I am logged in as a user with the role "Site Administrator"
    When I visit "node/add/civictheme_page"
    And I fill in "Title" with "[TEST] Page fields"
    When I press "Add Manual list"

    Then I should see "Content"

    And I should see "List items"
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_event_card_add_more']" element
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_event_card_ref_add_more']" element
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_navigation_card_add_more']" element
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_navigation_card_ref_add_more']" element
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_promo_card_add_more']" element
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_promo_card_ref_add_more']" element
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_publication_card_add_more']" element
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_service_card_add_more']" element
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_subject_card_add_more']" element
    And should see a "[name='field_c_n_components_0_subform_field_c_p_list_items_civictheme_subject_card_ref_add_more']" element

    And I should see "Title"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_title][0][value]']" element
    And should not see a "[name='field_c_n_components[0][subform][field_c_p_title][0][value]'].required" element
    And should not see a "[name='field_c_n_components[0][subform][field_c_p_title][0][value]'][disabled]" element

    And I should see "Content"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_content][0][value]']" element
    And should not see a "[name='field_c_n_components[0][subform][field_c_p_content][0][value]'].required" element
    And should not see a "[name='field_c_n_components[0][subform][field_c_p_content][0][value]'][disabled]" element

    And I should see "Link above"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_link_above][0][uri]']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_link_above][0][title]']" element

    And I should see "Link below"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_link_below][0][uri]']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_link_below][0][title]']" element

    And I should see "Appearance"

    And I should see "Theme"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_theme]']" element

    And I should see "Vertical spacing"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_vertical_spacing]']" element
    And should see a ".field--name-field-c-n-vertical-spacing .required" element
    And should not see a "[name='field_c_n_components[0][subform][field_c_p_vertical_spacing]'][disabled]" element

    And I should see "Background"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_background][value]']" element

    And I should see "Column count"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_column_count]']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_column_count]'].required" element
    And should not see a "[name='field_c_n_components[0][subform][field_c_p_list_column_count]'][disabled]" element

    And I should see "Fill width in the last row"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_fill_width][value]']" element

    And I should see "Layout"
    And should see a "[name='field_c_n_components[0][subform][field_p_list_layout]']" element
