# Website Developer Guidelines based on Vortex Drupal project template

This document outlines the development standards and workflows for the DrevOps marketing website. It builds upon the foundation provided by the Vortex Drupal project template, which is DrevOps' standardized starter kit for Drupal projects.

## Project Overview
- **Project Type**: Drupal 11 website
- **Purpose**: Marketing website for DrevOps organization
- **Repository**: Part of drevops/website repository
- **Base Template**: Built on [Vortex](https://vortex.drevops.com), a standardized Drupal starter kit with built-in CI/CD capabilities

## Development Environment

### Local Setup
- Use Docker-based local environment
- Setup with Ahoy commands:
  ```bash
  ahoy build    # Build the site
  ahoy up       # Start containers
  ahoy down     # Stop containers
  ```

### Key Development Commands
- `ahoy provision` - Provision the site. Already runs as a part of `ahoy build`. Used to re-import the database and run all the necessary update, cache clear and deploy commands. Should be used when a database becomes "dirty" and needs to be re-imported.
- `ahoy drush <command>` - Run Drush commands (e.g., `ahoy drush status`, `ahoy drush cex`, `ahoy drush cr`)
- `ahoy composer <command>` - Run Composer commands (e.g., `ahoy composer show package/name`, `ahoy composer update package/name`)
- `ahoy cli <command>` - Execute commands in the CLI container (e.g., `ahoy cli vendor/bin/behat -- --help`)
- `ahoy lint` - Check coding standards
- `ahoy lint-fix` - Fix coding standards issues
- `ahoy test-unit` - Run unit tests
- `ahoy test-bdd` - Run BDD tests
- `ahoy provision` - Provision the site
- `ahoy reset` - Reset environment (soft)
- `ahoy reset hard` - Reset environment (hard)
- `ahoy fetch-db` - Fetch latest database

## Coding Standards

### PHP
- Follow Drupal coding standards
- Use snake_case for local variables and method arguments
- Use camelCase for method names and class properties
- Use single quotes for strings unless they contain single quotes
- Code must pass PHPCS, PHPMD, and PHPStan checks. No warnings or errors should be present in the output of `ahoy lint` and it should exit with code 0.

### Drupal
- Use Drupal configuration management practices
- All configuration should be exportable
- Follow Drupal module development best practices

## Content Management

### Content Types
- CivicTheme Page - For standard content pages
- CivicTheme Alert - For site-wide alerts
- CivicTheme Event - For event pages

### Theme
- Uses CivicTheme as the base theme
- Custom subtheme for site-specific styling

## Testing

### Test Execution
- Unit Tests: `ahoy test-unit`
- BDD Tests: `ahoy test-bdd`
- BDD Tests using a single test: `ahoy test-bdd tests/behat/features/test.feature`
- BDD Tests skipping tagged tests: `ahoy test-bdd -- --tags="~@skipped"` (skips tests with @skipped tag)
- BDD Tests with specific tags: `ahoy test-bdd -- --tags="@smoke"` (runs only tests with @smoke tag)

### Test Writing Guidelines
- Use Behat for user journey testing. Try to avoid creating extra steps in Behat and use what is already available in the system.
- List all available step definitions: `ahoy cli vendor/bin/behat -- --definitions=i`
- To test if a step works: `ahoy test-bdd tests/behat/features/test.feature -v` (verbose mode shows additional details)
- For troubleshooting, check screenshots generated after failures in `.logs/screenshots/`
- Add `And I save screenshot` to debug steps in your feature files
- Follow BDD principles for feature testing
  - Feature: A short description of the test
  - User story: A description of the user story being tested in a format:
        As a <role>
        I want <feature>
        So that <benefit>
  - Scenario: A specific test case
  - Refer to the existing Behat tests to make sure you are following the same structure and conventions.

## Deployment Workflow

### CI/CD Pipeline
- Tests run on all Pull Requests
- Deployments occur after successful tests
- Deployments trigger via webhooks

### Environments
- Development: Feature branch deployments
- Staging: Develop branch deployments
- Production: Main branch deployments

## Common Tasks

### Adding New Features
1. Create a feature branch from `develop` in the format `feature/name`
2. Implement the feature
3. Export configuration changes using `ahoy drush cex -y`
4. Write appropriate tests
5. Run coding standards checks
6. Commit the code with a message in the format `Verb in past tense with a period at the end.`
7. Do not push to remote.

### Bug Fixes
1. Create a bugfix branch from `develop` in the format `bugfix/name`
2. Fix the issue
3. Export configuration changes using `ahoy drush cex -y`
4. Add tests to prevent regression
5. Commit the code with a message in the format `Verb in past tense with a period at the end.`
6. Do not push to remote.

### Content Updates
1. Content added via the Drush deploy hook implementations placed into `web/modules/custom/*_core/` directory.
2. Once the deployment hook is created, run `ahoy drush deploy:hook` to apply the changes.
3. If the hook deployment fails:
   a. Run assess the output
   b. Fix the code
   c. Update the hook name by adding a next sequence number to the hook name, e.g. `hook_deploy_1`, `hook_deploy_2`, etc.
   d. Run `ahoy drush deploy:hook` again.
   e. If the database becomes "dirty" and needs to be re-imported, run `ahoy provision` to re-import the database and run all the necessary update, cache clear and deploy commands.

## Documentation Resources
- Vortex Documentation: https://vortex.drevops.com - Reference for the underlying project template and architecture
- Drupal Documentation: https://www.drupal.org/documentation - General Drupal platform documentation
- CivicTheme Documentation: https://docs.civictheme.io/ - Documentation for the theme used in this project

## Purpose of This Document

This document serves as:

1. A quick reference guide for developers working on the DrevOps website
2. Documentation of project-specific standards and processes
3. A central collection of common commands and workflows
4. A reference for testing procedures and CI/CD processes

The guidelines in this document ensure consistent development practices across the team and maintain the quality standards expected of DrevOps projects.
