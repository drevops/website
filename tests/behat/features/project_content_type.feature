@p1 @drevops @project
Feature: Project content type

  As a content editor
  I want to publish delivered work as a Project with structured facts and a flexible narrative
  So that visitors can judge the depth of the work and browse it by sector, service and technology

  Scenario: Project is offered as a content type
    Given I am logged in as a user with the role "Content Author"
    When I visit "node/add"
    Then I should see "Project"
    When I visit "node/add/project"
    Then I should see "Create Project"

  Scenario: Project offers the structured fact fields alongside the Page controls
    Given I am logged in as a user with the role "Content Author"
    When I visit "node/add/project"

    Then the field "title[0][value]" should exist
    And the field "field_c_n_summary[0][value]" should exist
    And the field "field_c_n_topics[target_id]" should exist

    And the field "field_do_n_client[0][value]" should exist
    And the field "field_do_n_role[0][value]" should exist
    And the field "field_do_n_delivered_at[0][value]" should exist
    And the field "field_do_n_year[0][value]" should exist
    And the field "field_do_n_status" should exist
    And the field "field_do_n_live_url[0][uri]" should exist
    And the field "field_do_n_oss_contributions[0][uri]" should exist
    And the field "field_do_n_oss_contributions[0][title]" should exist
    And the field "field_do_n_sector" should exist
    And the field "field_do_n_technologies[target_id]" should exist
    And the field "field_do_n_services[0][target_id]" should exist

    And the field "field_c_n_banner_title[0][value]" should exist
    And the field "field_c_n_banner_type" should exist
    And the field "field_c_n_show_toc[value]" should exist
    And the field "field_c_n_hide_tags[value]" should exist
    And the field "field_c_n_vertical_spacing" should exist

    And I should see "Project details"
    And I should see "Categorisation"
    And I should see "Appearance"
    And I should see "Banner"

  Scenario: Project offers the same component set as a Page
    Given I am logged in as a user with the role "Content Author"
    When I visit "node/add/project"
    Then the field "field_c_n_components[0][subform][field_c_p_content][0][value]" should not exist
    And I should see the button "Add Content"
    And I should see the button "Add Automated list"
    And I should see the button "Add Manual list"
    And I should see the button "Add Steps"

  Scenario: Sector and technology are vocabularies while services are pages
    Given I am logged in as a user with the role "Site Administrator"
    When I visit "admin/structure/taxonomy"
    Then I should see "Sector"
    And I should see "Technology"
    When I visit "admin/structure/taxonomy/manage/do_sector/overview"
    Then the response status code should be 200
    When I visit "admin/structure/taxonomy/manage/do_technology/overview"
    Then the response status code should be 200
    # Services are pages in their own right, so no vocabulary stands between a
    # project and the page describing what was delivered.
    When I visit "admin/structure/taxonomy/manage/do_service/overview"
    Then the response status code should be 404

  Scenario: Project follows the editorial workflow and gets a /work URL
    Given the following "project" content exists:
      | title                  | moderation_state | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Example project | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    When I visit the "project" content page with the title "[TEST] Example project"
    Then the path should be "/work/test-example-project"
    And I should see "[TEST] Example project"
    Given I am logged in as a user with the role "Site Administrator"
    When I visit the "project" content edit page with the title "[TEST] Example project"
    Then the option "Draft" within the select "moderation_state[0][state]" should exist
    And the option "Published" within the select "moderation_state[0][state]" should exist
    And the option "Archived" within the select "moderation_state[0][state]" should exist

  Scenario: A draft project is not visible to an anonymous user
    Given the following "project" content exists:
      | title                 | moderation_state | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Draft project  | draft            | 2025            | ongoing           | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I go to "/work/test-draft-project"
    Then the response status code should be 403

  Scenario: A published project renders its narrative components
    Given the following "project" content exists:
      | title                    | moderation_state | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Narrative project | published        | 2025            | ongoing           | large                 | inherit                | normal                      | both                       |
    And the following fields for the "civictheme_content" paragraph exist in the field "field_c_n_components" within the "project" "node" entity with the value "[TEST] Narrative project" in the field "title":
      | field_c_p_content:value    | [TEST] Component body text. |
      | field_c_p_content:format   | civictheme_rich_text        |
      | field_c_p_theme            | light                       |
      | field_c_p_vertical_spacing | both                        |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Narrative project"
    Then I should see "[TEST] Narrative project"
    And I should see "[TEST] Component body text."

  Scenario: Figures are entered as fast fact cards inside a manual list
    Given the following "project" content exists:
      | title                 | moderation_state | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Figure project | published        | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    And the following fields for the "civictheme_manual_list" paragraph exist in the field "field_c_n_components" within the "project" "node" entity with the value "[TEST] Figure project" in the field "title":
      | field_c_p_title             | [TEST] Project figures |
      | field_c_p_list_column_count | 3                      |
      | field_c_p_list_fill_width   | 0                      |
    And the following fields for the "civictheme_fast_fact_card" paragraph exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" entity with the value "[TEST] Project figures" in the field "field_c_p_title":
      | field_c_p_title   | 4.2 million              |
      | field_c_p_summary | [TEST] Visitors per year |
      | field_c_p_theme   | light                    |
    And the following fields for the "civictheme_fast_fact_card" paragraph exist in the field "field_c_p_list_items" within the "civictheme_manual_list" "paragraph" entity with the value "[TEST] Project figures" in the field "field_c_p_title":
      | field_c_p_title   | 60%                        |
      | field_c_p_summary | [TEST] Faster page loads   |
      | field_c_p_theme   | light                      |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Figure project"
    Then I should see 2 ".ct-fast-fact-card" elements
    And I should see "4.2 million"
    And I should see "[TEST] Visitors per year"
    And I should see "60%"
    And I should see "[TEST] Faster page loads"

  Scenario: The At a glance panel shows every fact with its label
    Given the following "do_sector" terms exist:
      | name            |
      | [TEST] Sector 1 |
    And the following "do_technology" terms exist:
      | name                |
      | [TEST] Technology 1 |
    And the following "civictheme_page" content exists:
      | title            | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Service 1 | published        | large                 | inherit                | normal                      | both                       |
      | [TEST] Service 2 | published        | large                 | inherit                | normal                      | both                       |
    And the following "project" content exists:
      | title                    | moderation_state | field_do_n_client     | field_do_n_role      | field_do_n_delivered_at | field_do_n_year | field_do_n_status | field_do_n_live_url:uri  | field_do_n_sector | field_do_n_services                | field_do_n_technologies | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Complete project  | published        | [TEST] Example Client | [TEST] Technical lead | [TEST] Example Agency   | 2025            | completed         | https://www.example.com  | [TEST] Sector 1   | [TEST] Service 1, [TEST] Service 2 | [TEST] Technology 1     | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Complete project"
    Then should see a ".ct-at-a-glance" element
    And I should see 9 ".ct-at-a-glance__row" elements

    And I should see "Client"
    And I should see "[TEST] Example Client"
    And I should see "Role"
    And I should see "[TEST] Technical lead"
    And I should see "Delivered at"
    And I should see "[TEST] Example Agency"
    And I should see "Year"
    And I should see "2025"
    And I should see "Status"
    And I should see "Completed"
    And I should see "Live site"
    And the response should contain "https://www.example.com"

    # Each taxonomy value links to its term page, so the reader can browse
    # sideways from any project into everything sharing that term.
    And I should see 2 ".ct-at-a-glance__value a[href^='/taxonomy/term/']" elements
    And I should see "[TEST] Sector 1"
    And I should see "[TEST] Technology 1"

    # A service reads through to the page describing it rather than to a term
    # listing, so nothing stands between the project and the service itself.
    And I should see "[TEST] Service 1"
    And I should see "[TEST] Service 2"
    And should see a ".ct-at-a-glance__value a[href='/test-service-1']" element
    And should see a ".ct-at-a-glance__value a[href='/test-service-2']" element

  Scenario: A service can be read through to the page describing it
    Given the following "civictheme_page" content exists:
      | title             | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Migrations | published        | large                 | inherit                | normal                      | both                       |
    And the following "project" content exists:
      | title                   | moderation_state | field_do_n_services | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Serviced project | published        | [TEST] Migrations   | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Serviced project"
    Then should see a ".ct-at-a-glance__value a[href='/test-migrations']" element
    When I follow "[TEST] Migrations"
    Then the path should be "/test-migrations"
    And I should see "[TEST] Migrations"
    And should not see a ".ct-at-a-glance" element

  Scenario: A service page an anonymous reader cannot see is left out
    Given the following "civictheme_page" content exists:
      | title                  | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Draft service   | draft            | large                 | inherit                | normal                      | both                       |
    And the following "project" content exists:
      | title                  | moderation_state | field_do_n_services  | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Drafted project | published        | [TEST] Draft service | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Drafted project"
    # Only Year and Status remain: a service the reader cannot open leaves no
    # row behind, rather than an empty label or a link into a 403.
    Then I should see 2 ".ct-at-a-glance__row" elements
    And I should not see "[TEST] Draft service"

  Scenario: Work that cannot be attributed omits those rows entirely
    Given the following "project" content exists:
      | title                       | moderation_state | field_do_n_role      | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Unattributed project | published        | [TEST] Developer     | 2019            | ongoing           | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Unattributed project"
    Then should see a ".ct-at-a-glance" element
    And I should see 3 ".ct-at-a-glance__row" elements
    And I should not see "Client"
    And I should not see "Delivered at"
    And I should not see "Live site"
    # No value in the panel links anywhere, so an omitted row cannot leave a
    # dangling link behind it.
    And should not see a ".ct-at-a-glance__value a" element

  Scenario: Work that is no longer live is honest about it
    Given the following "project" content exists:
      | title                         | moderation_state | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Decommissioned project | published        | 2015            | decommissioned    | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Decommissioned project"
    Then I should see "Status"
    And I should see "Decommissioned"
    And I should not see "Live site"

  Scenario: Open source contributions are listed
    Given the following "project" content exists:
      | title                       | moderation_state | field_do_n_oss_contributions:uri                                       | field_do_n_oss_contributions:title | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Contributing project | published        | https://github.com/drevops/vortex, https://github.com/drevops/behat-steps | [TEST] Vortex, [TEST] Behat Steps  | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Contributing project"
    Then should see a ".ct-link-list" element
    And I should see "Open source contributions"
    And I should see "[TEST] Vortex"
    And I should see "[TEST] Behat Steps"
    And I should see 2 ".ct-link-list__items a" elements

  Scenario: A project with no contributions renders no contributions section
    Given the following "project" content exists:
      | title                          | moderation_state | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Non-contributing project | published       | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Non-contributing project"
    Then should not see a ".ct-link-list" element
    And I should not see "Open source contributions"

  Scenario: A project renders its table of contents and topic tags
    Given the following "civictheme_topics" terms exist:
      | name           |
      | [TEST] Topic 1 |
      | [TEST] Topic 2 |
    And the following "project" content exists:
      | title                 | moderation_state | field_c_n_topics               | field_c_n_hide_tags | field_c_n_show_toc | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Tagged project | published        | [TEST] Topic 1, [TEST] Topic 2 | 0                   | 1                  | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Tagged project"
    Then should see a "[data-table-of-contents-anchor-selector]" element
    And should see a ".ct-tag-list" element
    And I should see "[TEST] Topic 1"
    And I should see "[TEST] Topic 2"

  Scenario: A project with tags hidden and no table of contents renders neither
    Given the following "civictheme_topics" terms exist:
      | name           |
      | [TEST] Topic 3 |
    And the following "project" content exists:
      | title                   | moderation_state | field_c_n_topics | field_c_n_hide_tags | field_c_n_show_toc | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Untagged project | published        | [TEST] Topic 3   | 1                   | 0                  | 2025            | completed         | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "project" content page with the title "[TEST] Untagged project"
    Then should not see a "[data-table-of-contents-anchor-selector]" element
    And should not see a ".ct-tag-list" element
    And I should not see "[TEST] Topic 3"

  Scenario: Project is selectable in an automated list
    Given I am logged in as a user with the role "Content Author"
    When I visit "node/add/civictheme_page"
    And I press "Add Automated list"
    Then I should see "Project"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_content_type]'][value='project']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_content_type]'][value='all']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_content_type]'][value='civictheme_page']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_content_type]'][value='blog']" element

  Scenario: An automated list set to Project returns only projects
    Given the following "project" content exists:
      | title                   | moderation_state | field_c_n_summary          | field_do_n_year | field_do_n_status | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Listed project 1 | published        | [TEST] First project card  | 2025            | completed         | large                 | inherit                | normal                      | both                       |
      | [TEST] Listed project 2 | published        | [TEST] Second project card | 2024            | completed         | large                 | inherit                | normal                      | both                       |
    And the following "civictheme_page" content exists:
      | title                  | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Excluded page   | published        | large                 | inherit                | normal                      | both                       |
      | [TEST] Project listing | published        | large                 | inherit                | normal                      | both                       |
    And the following fields for the "civictheme_automated_list" paragraph exist in the field "field_c_n_components" within the "civictheme_page" "node" entity with the value "[TEST] Project listing" in the field "title":
      | field_c_p_list_content_type | project                           |
      | field_c_p_list_type         | civictheme_automated_list__block1 |
      | field_c_p_list_item_view_as | civictheme_promo_card             |
      | field_c_p_list_item_theme   | light                             |
      | field_c_p_list_limit_type   | unlimited                         |
      | field_c_p_list_limit        | 9                                 |
      | field_c_p_list_column_count | 3                                 |
      | field_c_p_theme             | light                             |
      | field_c_p_vertical_spacing  | bottom                            |
    And I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Project listing"
    Then I should see "[TEST] Listed project 1"
    And I should see "[TEST] First project card"
    And I should see "[TEST] Listed project 2"
    And I should not see "[TEST] Excluded page"

  Scenario: Existing content types are unaffected by the new bundle
    Given the following "civictheme_page" content exists:
      | title                | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Untouched page | published       | large                 | inherit                | normal                      | both                       |
    And the following "blog" content exists:
      | title                 | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Untouched post | published        | large                 | inherit                | normal                      | both                       |
    And the following "civictheme_event" content exists:
      | title                  | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Untouched event | published        | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Untouched page"
    Then I should see "[TEST] Untouched page"
    And should not see a ".ct-at-a-glance" element
    When I visit the "blog" content page with the title "[TEST] Untouched post"
    Then the path should be "/blog/test-untouched-post"
    And I should see "[TEST] Untouched post"
    And should not see a ".ct-at-a-glance" element
    When I visit the "civictheme_event" content page with the title "[TEST] Untouched event"
    Then I should see "[TEST] Untouched event"
    And should not see a ".ct-at-a-glance" element
