@p1 @drevops @blog
Feature: Blog post content type

  As a content editor
  I want blog articles to live on their own Blog post content type that opens ready to write in
  So that articles stop being indistinguishable from Pages and I stop repeating the same setup on every post

  # The migration of existing articles onto this bundle is asserted against
  # restored production content rather than here, because a clean test database
  # holds none of the articles the deploy hook looks for.

  @api
  Scenario: Blog post is offered as a content type
    Given I am logged in as a user with the "Content Author" role
    When I visit "node/add"
    Then I should see the text "Blog post"
    When I visit "node/add/blog"
    Then I should see the text "Create Blog post"

  @api
  Scenario: The Components field opens on a single Content paragraph ready for editing
    Given I am logged in as a user with the "Content Author" role
    When I visit "node/add/blog"
    Then the field "field_c_n_components[0][subform][field_c_p_content][0][value]" should exist

  @api
  Scenario: The Page Components field still opens with no paragraph added
    Given I am logged in as a user with the "Content Author" role
    When I visit "node/add/civictheme_page"
    Then the field "field_c_n_components[0][subform][field_c_p_content][0][value]" should not exist

  @api
  Scenario: Blog post offers the same authoring controls as a Page
    Given I am logged in as a user with the "Content Author" role
    When I visit "node/add/blog"

    Then the field "title[0][value]" should exist
    And the field "field_c_n_summary[0][value]" should exist
    And the field "field_c_n_topics[target_id]" should exist

    And the field "field_c_n_banner_title[0][value]" should exist
    And the field "field_c_n_banner_type" should exist
    And the field "field_c_n_banner_theme" should exist
    And the field "field_c_n_banner_blend_mode" should exist
    And the field "field_c_n_banner_hide_breadcrumb[value]" should exist

    And the field "field_c_n_show_toc[value]" should exist
    And the field "field_c_n_show_last_updated[value]" should exist
    And the field "field_c_n_hide_sidebar[value]" should exist
    And the field "field_c_n_hide_tags[value]" should exist
    And the field "field_c_n_vertical_spacing" should exist

    And I should see the text "Banner"
    And I should see the text "Appearance"
    And I should see the text "Categorisation"
    And I should see the text "Featured image"
    And I should see the text "Thumbnail"

  @api
  Scenario: Blog post follows the editorial workflow
    Given I am logged in as a user with the "Site Administrator" role
    And the following "blog" content:
      | title                 | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Workflow Post  | published        | large                 | inherit                | normal                      | both                       |
    When I visit the "blog" content edit page with the title "[TEST] Workflow Post"
    Then the option "Draft" should exist within the select element "moderation_state[0][state]"
    And the option "Published" should exist within the select element "moderation_state[0][state]"
    And the option "Archived" should exist within the select element "moderation_state[0][state]"

  @api
  Scenario: A new blog post gets a /blog URL by default
    Given the following "blog" content:
      | title                       | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Testing the new type | published        | large                 | inherit                | normal                      | both                       |
    When I visit the "blog" content page with the title "[TEST] Testing the new type"
    Then the path should be "/blog/test-testing-new-type"
    And I should see the text "[TEST] Testing the new type"

  @api
  Scenario: A draft blog post is not visible to an anonymous user
    Given the following "blog" content:
      | title                     | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Unpublished Post   | draft            | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I go to "/blog/test-unpublished-post"
    Then the response status code should be 403

  @api
  Scenario: A published blog post renders its components
    Given the following "blog" content:
      | title                | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Rendered Post | published        | large                 | inherit                | normal                      | both                       |
    And the following fields for the paragraph "civictheme_content" exist in the field "field_c_n_components" within the "blog" "node" identified by the field "title" and the value "[TEST] Rendered Post":
      | field_c_p_content:value  | [TEST] Component body text. |
      | field_c_p_content:format | civictheme_rich_text        |
      | field_c_p_theme          | light                       |
      | field_c_p_vertical_spacing | both                      |
    And I am an anonymous user
    When I visit the "blog" content page with the title "[TEST] Rendered Post"
    Then I should see the text "[TEST] Rendered Post"
    And I should see the text "[TEST] Component body text."

  @api
  Scenario: A published blog post renders its topic tags
    Given the following "civictheme_topics" terms:
      | name           |
      | [TEST] Topic 1 |
      | [TEST] Topic 2 |
    And the following "blog" content:
      | title              | moderation_state | field_c_n_topics                | field_c_n_hide_tags | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Tagged Post | published        | [TEST] Topic 1, [TEST] Topic 2  | 0                   | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "blog" content page with the title "[TEST] Tagged Post"
    Then should see a ".ct-tag-list" element
    And I should see the text "[TEST] Topic 1"
    And I should see the text "[TEST] Topic 2"

  @api
  Scenario: A blog post with hidden tags renders no topic tags
    Given the following "civictheme_topics" terms:
      | name           |
      | [TEST] Topic 3 |
    And the following "blog" content:
      | title                | moderation_state | field_c_n_topics | field_c_n_hide_tags | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Untagged Post | published        | [TEST] Topic 3   | 1                   | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "blog" content page with the title "[TEST] Untagged Post"
    Then should not see a ".ct-tag-list" element
    And I should not see the text "[TEST] Topic 3"

  @api
  Scenario: A blog post with the table of contents enabled renders it
    Given the following "blog" content:
      | title           | moderation_state | field_c_n_show_toc | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Toc Post | published        | 1                  | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "blog" content page with the title "[TEST] Toc Post"
    Then should see a "[data-table-of-contents-anchor-selector]" element

  @api
  Scenario: A blog post without the table of contents does not render it
    Given the following "blog" content:
      | title              | moderation_state | field_c_n_show_toc | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] No Toc Post | published        | 0                  | large                 | inherit                | normal                      | both                       |
    And I am an anonymous user
    When I visit the "blog" content page with the title "[TEST] No Toc Post"
    Then should not see a "[data-table-of-contents-anchor-selector]" element

  @api
  Scenario: Blog post is selectable in an automated list
    Given I am logged in as a user with the "Content Author" role
    When I visit "node/add/civictheme_page"
    And I press "Add Automated list"
    Then I should see the text "Blog post"
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_content_type]'][value='blog']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_content_type]'][value='all']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_content_type]'][value='civictheme_page']" element
    And should see a "[name='field_c_n_components[0][subform][field_c_p_list_content_type]'][value='civictheme_event']" element

  @api
  Scenario: An automated list set to Blog post returns only blog posts
    Given the following "blog" content:
      | title                | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Listed Post 1 | published        | large                 | inherit                | normal                      | both                       |
      | [TEST] Listed Post 2 | published        | large                 | inherit                | normal                      | both                       |
    And the following "civictheme_page" content:
      | title                | moderation_state | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Excluded Page | published        | large                 | inherit                | normal                      | both                       |
      | [TEST] Blog Listing  | published        | large                 | inherit                | normal                      | both                       |
    And the following fields for the paragraph "civictheme_automated_list" exist in the field "field_c_n_components" within the "civictheme_page" "node" identified by the field "title" and the value "[TEST] Blog Listing":
      | field_c_p_list_content_type | blog                              |
      | field_c_p_list_type         | civictheme_automated_list__block1 |
      | field_c_p_list_item_view_as | civictheme_promo_card             |
      | field_c_p_list_item_theme   | light                             |
      | field_c_p_list_limit_type   | unlimited                         |
      | field_c_p_list_limit        | 9                                 |
      | field_c_p_list_column_count | 3                                 |
      | field_c_p_theme             | light                             |
      | field_c_p_vertical_spacing  | bottom                            |
    And I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Blog Listing"
    Then I should see the text "[TEST] Listed Post 1"
    And I should see the text "[TEST] Listed Post 2"
    And I should not see the text "[TEST] Excluded Page"
