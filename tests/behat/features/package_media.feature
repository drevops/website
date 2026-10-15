@package
Feature: Private packages

  As a site administrator
  I want to store zip and 7z packages that only authorised roles can reach
  So that a private package is never served to anyone else

  Background:
    Given the following managed files:
      | path        | uri                                  | status |
      | package.zip | private://packages/behat-package.zip | 1      |
    And the following media "package" exist:
      | name           | field_do_m_package                   |
      | [TEST] Package | private://packages/behat-package.zip |

  @api
  Scenario Outline: Package downloads are limited to the roles granted access
    Given I am logged in as a user with the "<role>" role
    When I go to "/system/files/packages/behat-package.zip"
    Then the response status code should be <code>

    Examples:
      | role                          | code |
      | authenticated                 | 403  |
      | civictheme_content_author     | 403  |
      | civictheme_content_approver   | 403  |
      | civictheme_site_administrator | 200  |
      | do_content_api                | 200  |

  @api
  Scenario: Site visitor cannot download a package, and the refusal is not cached
    Given I am an anonymous user
    When I go to "/system/files/packages/behat-package.zip"
    Then the response status code should be 403
    And the response header "Cache-Control" should contain the value "no-cache"
    And the response header "Cache-Control" should not contain the value "public"

  @api
  Scenario: Site visitor cannot reach a package through its storage path
    Given I am an anonymous user
    When I go to "/sites/default/files/private/packages/behat-package.zip"
    Then the response status code should be 404

  @api
  Scenario: Content API service downloads a package with its API key
    Given the following users:
      | name                       | mail                         | roles          | api_key                          |
      | [TEST] Package API client  | test-package-api@example.com | do_content_api | behatpackageapikey00000000000001 |
    And I am an anonymous user
    And a REST header "api-key" with value "behatpackageapikey00000000000001"
    When I send a REST "GET" request to "/system/files/packages/behat-package.zip"
    Then the REST response status code should be 200

  @api
  Scenario: Content API service lists packages with its API key
    Given the following users:
      | name                       | mail                         | roles          | api_key                          |
      | [TEST] Package API client  | test-package-api@example.com | do_content_api | behatpackageapikey00000000000001 |
    And I am an anonymous user
    And a REST header "api-key" with value "behatpackageapikey00000000000001"
    And a REST header "Accept" with value "application/vnd.api+json"
    When I send a REST "GET" request to "/jsonapi/media/package?include=field_do_m_package"
    Then the REST response status code should be 200
    And the REST response should contain "[TEST] Package"
    And the REST response should contain "behat-package.zip"

  @api
  Scenario: Site visitor does not see packages over JSON:API
    Given I am an anonymous user
    And a REST header "Accept" with value "application/vnd.api+json"
    When I send a REST "GET" request to "/jsonapi/media/package"
    Then the REST response status code should be 200
    And the response should not contain "[TEST] Package"

  @api
  Scenario: Site administrator sees packages in the media overview
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I go to "/admin/content/media"
    Then I should see the text "[TEST] Package"

  @api
  Scenario: Content author does not see packages in the media overview
    Given the following media "civictheme_document" exist:
      | name            | field_c_m_document |
      | [TEST] Document | response.json      |
    And I am logged in as a user with the "civictheme_content_author" role
    When I go to "/admin/content/media"
    Then I should see the text "[TEST] Document"
    And I should not see the text "[TEST] Package"

  @api
  Scenario: Content author cannot create a package
    Given I am logged in as a user with the "civictheme_content_author" role
    When I go to "/media/add/package"
    Then the response status code should be 403

  @api
  Scenario Outline: Package files appear in the files overview only for roles with package access
    Given the following managed files:
      | path          | uri                                | status |
      | response.json | public://behat-files-overview.json | 1      |
    And I am logged in as a user with the "<role>" role
    When I go to "/admin/content/files"
    Then I should see the text "behat-files-overview.json"
    And I should <visibility> the text "behat-package.zip"

    Examples:
      | role                          | visibility |
      | civictheme_content_author     | not see    |
      | civictheme_content_approver   | not see    |
      | civictheme_site_administrator | see        |

  @api
  Scenario: Site administrator deletes a package together with its file
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I visit the media "package" delete page with the name "[TEST] Package"
    And I press "Delete"
    Then I should see the text "The media item [TEST] Package has been deleted."
    When I go to "/admin/content/files"
    Then I should not see the text "behat-package.zip"
    When I go to "/system/files/packages/behat-package.zip"
    Then the response status code should be 404

  @api
  Scenario Outline: Site administrator uploads a <extension> package to private storage
    Given the following media "package" do not exist:
      | name                                |
      | [TEST] Uploaded <extension> package |
    And I am logged in as a user with the "civictheme_site_administrator" role
    When I go to "/media/add/package"
    And I fill in "Name" with "[TEST] Uploaded <extension> package"
    And I attach the file "package.<extension>" to "files[field_do_m_package_0]"
    And I press "Save"
    Then I should see the text "Package [TEST] Uploaded <extension> package has been created."
    When I edit the media "package" with the name "[TEST] Uploaded <extension> package"
    Then the response should contain "/system/files/packages/"
    And the following media "package" do not exist:
      | name                                |
      | [TEST] Uploaded <extension> package |

    Examples:
      | extension |
      | zip       |
      | 7z        |

  @api
  Scenario: Site administrator cannot upload a file other than a zip or 7z package
    Given I am logged in as a user with the "civictheme_site_administrator" role
    When I go to "/media/add/package"
    And I attach the file "image.jpg" to "files[field_do_m_package_0]"
    And I press "Upload"
    Then I should see the text "Only files with the following extensions are allowed: zip 7z."
