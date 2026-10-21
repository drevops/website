@p1 @drevops @brokenlinks
Feature: Broken links

  As a site administrator
  I want every link placed in content to be checked once per day
  So that I can fix broken links before visitors find them

  Scenario: Site administrator opens the broken links report
    Given I am logged in as a user with the role "administrator"
    When I visit "/admin/reports/linkchecker"
    Then the response status code should be 200
    And I should see "Broken links report"

  Scenario: Link checker re-checks every link daily and checks internal links too
    Given I am logged in as a user with the role "administrator"
    When I visit "/admin/config/content/linkchecker"
    Then the response status code should be 200
    And the option "1 day" within the select "Check interval for links" should be selected
    And the option "Internal and external" within the select "What type of links should be checked?" should be selected

  Scenario: Anonymous visitor cannot open the broken links report or its settings
    Given I am an anonymous user
    When I go to "/admin/reports/linkchecker"
    Then the response status code should be 403
    When I go to "/admin/config/content/linkchecker"
    Then the response status code should be 403

  Scenario: Links placed in page content are recorded for checking
    Given the following "civictheme_page" content exists:
      | title                    | status |
      | [TEST] Page Broken links | 1      |
    And the following fields for the "civictheme_content" paragraph exist in the field "field_c_n_components" within the "civictheme_page" "node" entity with the value "[TEST] Page Broken links" in the field "title":
      | field_c_p_theme            | light                                                                                                                                   |
      | field_c_p_vertical_spacing | both                                                                                                                                    |
      | field_c_p_content:value    | <p><a href="https://do-test-external.invalid/page">[TEST] External link</a> <a href="/do-test-internal-missing">[TEST] Internal link</a></p> |
      | field_c_p_content:format   | civictheme_rich_text                                                                                                                    |
    And I am logged in as a user with the role "administrator"
    When I visit "/admin/reports/linkchecker?url=do-test-external"
    Then I should see "https://do-test-external.invalid/page"
    And I should see "[TEST] Page Broken links"
    And I should see "Never"
    # The stored URL carries the host of the request that saved the page, so
    # only the path is asserted.
    When I visit "/admin/reports/linkchecker?url=do-test-internal-missing"
    Then I should see "/do-test-internal-missing"
    And I should see "[TEST] Page Broken links"

  @project
  Scenario: A project live URL on a host that cannot be resolved is reported as broken
    Given the following "project" content exists:
      | title                       | moderation_state | field_do_n_year | field_do_n_status | field_do_n_live_url:uri             | field_c_n_banner_type | field_c_n_banner_theme | field_c_n_banner_blend_mode | field_c_n_vertical_spacing |
      | [TEST] Project Broken links | published        | 2025            | completed         | https://do-test-unreachable.invalid | large                 | inherit                | normal                      | both                       |
    And I am logged in as a user with the role "administrator"
    When I visit "/admin/reports/linkchecker?url=do-test-unreachable"
    Then I should see "https://do-test-unreachable.invalid"
    And I should see "[TEST] Project Broken links"
    And I should see "Never"
    When the link checker checks the links containing "do-test-unreachable"
    And I visit "/admin/reports/linkchecker?url=do-test-unreachable&result=1"
    Then I should see "https://do-test-unreachable.invalid"
    And I should see "502"
    And I should see "Could not resolve host"
