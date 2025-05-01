<?php

/**
 * @file
 * Behat trait for testing robots.txt file.
 */

declare(strict_types=1);

use Behat\Gherkin\Node\TableNode;

/**
 * Trait containing step definitions for testing robots.txt.
 */
trait RobotsTrait {

  /**
   * Checks allowed paths in the robots.txt file.
   *
   * @Then the following paths should be allowed for robots:
   */
  public function pathsAllowedForRobots(TableNode $table): void {
    $robotsTxt = $this->getSession()->getPage()->getContent();
    $rows = $table->getRows();

    // Skip the header row if it exists.
    if (isset($rows[0][0]) && $rows[0][0] === 'Path') {
      array_shift($rows);
    }

    foreach ($rows as $row) {
      $path = $row[0];

      // Check if path is explicitly disallowed.
      $disallowPattern = 'Disallow: ' . preg_quote((string) $path, '/');
      if (preg_match('/' . $disallowPattern . '/', (string) $robotsTxt)) {
        throw new \Exception(sprintf('Path "%s" is explicitly disallowed in robots.txt', $path));
      }

      // Check if path is covered by a more general disallow rule.
      $pathParts = explode('/', trim((string) $path, '/'));
      $counter = count($pathParts);
      for ($i = 0; $i < $counter; $i++) {
        $partialPath = '/' . implode('/', array_slice($pathParts, 0, $i + 1));
        $partialDisallowPattern = 'Disallow: ' . preg_quote($partialPath, '/');
        if (preg_match('/' . $partialDisallowPattern . '/', (string) $robotsTxt)) {
          throw new \Exception(sprintf('Path "%s" is implicitly disallowed by rule for "%s" in robots.txt', $path, $partialPath));
        }
      }
    }
  }

  /**
   * Checks disallowed paths in the robots.txt file.
   *
   * @Then the following paths should be disallowed for robots:
   */
  public function pathsDisallowedForRobots(TableNode $table): void {
    $robotsTxt = $this->getSession()->getPage()->getContent();
    $rows = $table->getRows();

    // Skip the header row if it exists.
    if (isset($rows[0][0]) && $rows[0][0] === 'Path') {
      array_shift($rows);
    }

    foreach ($rows as $row) {
      $path = $row[0];

      // Check if path is explicitly disallowed or covered by a wildcard.
      $disallowFound = FALSE;

      // Check exact match.
      $exactPattern = 'Disallow: ' . preg_quote((string) $path, '/');
      if (preg_match('/' . $exactPattern . '/', (string) $robotsTxt)) {
        $disallowFound = TRUE;
        continue;
      }

      // Check if disallowed by a wildcard pattern.
      preg_match_all('/Disallow: ([^\r\n]+)/', (string) $robotsTxt, $matches);

      foreach ($matches[1] as $disallowPattern) {
        $disallowPattern = trim($disallowPattern);

        // Convert robots.txt pattern to regex.
        $pattern = str_replace(['*', '/'], ['.+', '\/'], $disallowPattern);
        if (preg_match('/^' . $pattern . '/', (string) $path)) {
          $disallowFound = TRUE;
          break;
        }
      }

      if (!$disallowFound) {
        throw new \Exception(sprintf('Path "%s" is not disallowed in robots.txt', $path));
      }
    }
  }

}
