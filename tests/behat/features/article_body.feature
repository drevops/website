@p1 @civictheme @drevops @article
Feature: Article body and code highlighting

  As a site visitor
  I want blog articles to render rich, syntax-highlighted content
  So that in-depth posts read well without relying on a third-party CDN

  Background:
    Given the following "civictheme_page" content:
      | title                    | status |
      | [TEST] Page Article body | 1      |

    And the "civictheme_page" "node" "[TEST] Page Article body" has a "civictheme_rich_text" content paragraph:
      """
      <p class="ct-text-large">Most teams accept slow pipelines as a fact of life, but it does not have to be that way.</p>
      <p>We audit pipelines and the same problems recur, often an uncached <code>docker-compose pull</code>.</p>
      <h2>The usual suspects</h2>
      <p>What you actually need for CI:</p>
      <ul><li>Schema and configuration</li><li>Representative content</li></ul>
      <ol><li>Build the image</li><li>Cache the layers</li></ol>
      <table>
        <thead><tr><th>Phase</th><th>Optimised</th></tr></thead>
        <tbody><tr><td>Composer install</td><td>12s</td></tr><tr><td>Database import</td><td>45s</td></tr></tbody>
      </table>
      <blockquote><p>Cache on the lock file hash, not the manifest.</p></blockquote>
      <pre><code class="language-php">function example(): bool { return TRUE; }</code></pre>
      """

  @api
  Scenario: The article body renders the full range of authored formatting
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Article body"
    Then I should see an "article .ct-basic-content" element
    And I should see an "article .ct-basic-content p.ct-text-large" element
    And I should see an "article .ct-basic-content h2" element
    And I should see an "article .ct-basic-content ul li" element
    And I should see an "article .ct-basic-content ol li" element
    And I should see an "article .ct-basic-content table thead th" element
    And I should see an "article .ct-basic-content table tbody td" element
    And I should see an "article .ct-basic-content blockquote" element
    And I should see an "article .ct-basic-content p code" element
    And I should see an "article .ct-basic-content pre code" element
    And I should see the text "The usual suspects"

  @api
  Scenario: The restricted format strips arbitrary classes and inline styles
    Given I am an anonymous user
    And the following "civictheme_page" content:
      | title                      | status |
      | [TEST] Page Article unsafe | 1      |
    And the "civictheme_page" "node" "[TEST] Page Article unsafe" has a "civictheme_rich_text" content paragraph:
      """
      <p class="danger-class" style="color: red;">Styled paragraph</p>
      <span style="font-size: 99px;">Oversized span</span>
      <p>Safe paragraph remains</p>
      """
    When I visit the "civictheme_page" content page with the title "[TEST] Page Article unsafe"
    Then I should see the text "Safe paragraph remains"
    And the response should not contain "danger-class"
    And the response should not contain "color: red"
    And the response should not contain "font-size: 99px"

  @api
  Scenario: A blog post with code loads no third-party CDN for highlighting
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Article body"
    Then the response should not contain "cdnjs.cloudflare.com"

  @api @javascript
  Scenario: Code blocks are syntax-highlighted by the self-hosted highlighter
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Article body"
    Then I should see an "article .ct-basic-content pre code.hljs" element
    And I should see an "article .ct-basic-content pre .hljs-keyword" element

  @api @javascript
  Scenario: The article body renders the dark brand code, table and quote treatments
    Given I am an anonymous user
    When I visit the "civictheme_page" content page with the title "[TEST] Page Article body"
    Then the computed "background-color" of the element ".ct-basic-content pre code" should be "#2b394d"
    And the computed "background-color" of the element ".ct-basic-content thead th" should be "#2b394d"
    And the computed "border-left-color" of the element ".ct-basic-content blockquote" should be "#1e7582"
