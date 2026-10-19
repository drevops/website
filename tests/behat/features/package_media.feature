@package
Feature: Private packages

  As a site administrator
  I want to store zip and 7z packages that only authorised roles can reach
  So that a private package is never served to anyone else

  Background:
    Given the following managed files exist:
      | path        | uri                                  | status |
      | package.zip | private://packages/behat-package.zip | 1      |
    And the following "package" media exist:
      | name           | field_do_m_package                   |
      | [TEST] Package | private://packages/behat-package.zip |

  Scenario Outline: Package downloads are limited to the roles granted access
    Given I am logged in as a user with the role "<role>"
    When I go to "/system/files/packages/behat-package.zip"
    Then the response status code should be <code>

    Examples:
      | role                          | code |
      | authenticated                 | 403  |
      | civictheme_content_author     | 403  |
      | civictheme_content_approver   | 403  |
      | civictheme_site_administrator | 200  |
      | do_content_api                | 200  |

  Scenario: Site visitor cannot download a package, and the refusal is not cached
    Given I am an anonymous user
    When I go to "/system/files/packages/behat-package.zip"
    Then the response status code should be 403
    And the response header "Cache-Control" should contain "no-cache"
    And the response header "Cache-Control" should not contain "public"

  Scenario: Site visitor cannot reach a package through its storage path
    Given I am an anonymous user
    When I go to "/sites/default/files/private/packages/behat-package.zip"
    Then the response status code should be 404

  Scenario: Content API service downloads a package with its API key
    Given the following users exist:
      | name                       | mail                         | roles          | api_key                          |
      | [TEST] Package API client  | test-package-api@example.com | do_content_api | behatpackageapikey00000000000001 |
    And I am an anonymous user
    And the REST header "api-key" has the value "behatpackageapikey00000000000001"
    When I send a REST "GET" request to "/system/files/packages/behat-package.zip"
    Then the REST response status code should be 200

  Scenario: Content API service lists packages with its API key
    Given the following users exist:
      | name                       | mail                         | roles          | api_key                          |
      | [TEST] Package API client  | test-package-api@example.com | do_content_api | behatpackageapikey00000000000001 |
    And I am an anonymous user
    And the REST header "api-key" has the value "behatpackageapikey00000000000001"
    And the REST header "Accept" has the value "application/vnd.api+json"
    When I send a REST "GET" request to "/jsonapi/media/package?include=field_do_m_package"
    Then the REST response status code should be 200
    And the REST response should contain "[TEST] Package"
    And the REST response should contain "behat-package.zip"

  Scenario: Site visitor does not see packages over JSON:API
    Given I am an anonymous user
    And the REST header "Accept" has the value "application/vnd.api+json"
    When I send a REST "GET" request to "/jsonapi/media/package"
    Then the REST response status code should be 200
    And the response should not contain "[TEST] Package"

  Scenario: Site administrator sees packages in the media overview
    Given I am logged in as a user with the role "civictheme_site_administrator"
    When I go to "/admin/content/media"
    Then I should see "[TEST] Package"

  Scenario: Content author does not see packages in the media overview
    Given the following "civictheme_document" media exist:
      | name            | field_c_m_document |
      | [TEST] Document | response.json      |
    And I am logged in as a user with the role "civictheme_content_author"
    When I go to "/admin/content/media"
    Then I should see "[TEST] Document"
    And I should not see "[TEST] Package"

  Scenario: Content author cannot create a package
    Given I am logged in as a user with the role "civictheme_content_author"
    When I go to "/media/add/package"
    Then the response status code should be 403

  Scenario Outline: Package files appear in the files overview only for roles with package access
    Given the following managed files exist:
      | path          | uri                                | status |
      | response.json | public://behat-files-overview.json | 1      |
    And I am logged in as a user with the role "<role>"
    When I go to "/admin/content/files"
    Then I should see "behat-files-overview.json"
    And I should <visibility> "behat-package.zip"

    Examples:
      | role                          | visibility |
      | civictheme_content_author     | not see    |
      | civictheme_content_approver   | not see    |
      | civictheme_site_administrator | see        |

  Scenario: Site administrator deletes a package together with its file
    Given I am logged in as a user with the role "civictheme_site_administrator"
    When I visit the "package" media delete page with the name "[TEST] Package"
    And I press "Delete"
    Then I should see "The media item [TEST] Package has been deleted."
    When I go to "/admin/content/files"
    Then I should not see "behat-package.zip"
    When I go to "/system/files/packages/behat-package.zip"
    Then the response status code should be 404

  Scenario Outline: Site administrator uploads a <extension> package to private storage
    Given the following "package" media do not exist:
      | name                                |
      | [TEST] Uploaded <extension> package |
    And I am logged in as a user with the role "civictheme_site_administrator"
    When I go to "/media/add/package"
    And I fill in "Name" with "[TEST] Uploaded <extension> package"
    And I attach the file "package.<extension>" to "files[field_do_m_package_0]"
    And I press "Save"
    Then I should see "Package [TEST] Uploaded <extension> package has been created."
    When I visit the "package" media edit page with the name "[TEST] Uploaded <extension> package"
    Then the response should contain "/system/files/packages/"
    Given the following "package" media do not exist:
      | name                                |
      | [TEST] Uploaded <extension> package |

    Examples:
      | extension |
      | zip       |
      | 7z        |

  Scenario: Site administrator cannot upload a file other than a zip or 7z package
    Given I am logged in as a user with the role "civictheme_site_administrator"
    When I go to "/media/add/package"
    And I attach the file "image.jpg" to "files[field_do_m_package_0]"
    And I press "Upload"
    Then I should see "Only files with the following extensions are allowed: zip 7z."
