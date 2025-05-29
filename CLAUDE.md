# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# DrevOps Website Developer Guidelines

This is a Drupal 11 marketing website for DrevOps organization, built on the Vortex template - a standardized Drupal starter kit with built-in CI/CD capabilities.

## Architecture Overview

### Project Structure
- **Base Framework**: Drupal 11 with Docker-based development environment
- **Theme**: CivicTheme as base theme with custom "drevops" subtheme
- **Custom Code**: Single custom module `do_core` in `web/modules/custom/do_core/`
- **Configuration**: Drupal configuration management in `config/default/`
- **Database**: MySQL 8.4 with Redis caching and Solr search

### Key Components
- **Frontend**: CivicTheme component library with custom Sass/JS in `web/themes/custom/drevops/`
- **Content Types**: CivicTheme Page (standard), CivicTheme Alert (site alerts), CivicTheme Event (events)
- **Services**: nginx-php-fpm, database (MySQL), redis, solr, clamav for file scanning
- **Build System**: Composer for PHP dependencies, npm for frontend assets

## Essential Development Commands

### Environment Management
- `ahoy build` - Full build: reset, start containers, install dependencies, provision site
- `ahoy up` - Start containers (with optional `--build --force-recreate`)
- `ahoy down` - Stop and remove containers (removes database!)
- `ahoy provision` - Re-import database and run update/cache/deploy commands
- `ahoy reset [hard]` - Reset environment (soft) or to last commit (hard)

### Daily Development
- `ahoy cli [command]` - Execute commands in CLI container
- `ahoy drush [command]` - Run Drush commands (e.g., `ahoy drush cr`, `ahoy drush cex`)
- `ahoy composer [command]` - Run Composer commands
- `ahoy login` - Generate one-time login link

### Database Operations
- `ahoy download-db` / `ahoy fetch-db` - Download database from remote
- `ahoy import-db` - Import database dump
- `ahoy export-db` - Export database dump
- `ahoy reload-db` - Rebuild database container

### Frontend Development
- `ahoy fei` - Install frontend dependencies (npm ci)
- `ahoy fe` - Build production frontend assets
- `ahoy fed` - Build development frontend assets  
- `ahoy few` - Watch frontend assets during development

### Code Quality
- `ahoy lint` - Run all linting (backend + frontend + tests)
- `ahoy lint-be` - PHPCS, PHPStan, Rector, PHPMD checks
- `ahoy lint-fe` - Twig CS Fixer and npm lint
- `ahoy lint-fix` - Fix auto-fixable linting issues
- Code must pass all linting with exit code 0

### Testing
- `ahoy test` - Run all tests (unit, kernel, functional, BDD)
- `ahoy test-unit` - PHPUnit unit tests
- `ahoy test-bdd [file]` - Behat tests (e.g., `ahoy test-bdd tests/behat/features/test.feature`)
- `ahoy test-bdd -- --tags="@smoke"` - Run tests with specific tags
- `ahoy test-bdd -- --tags="~@skipped"` - Skip tests with @skipped tag
- Screenshots saved to `.logs/screenshots/` on test failures

### Behat Testing Details
- Available step definitions: `ahoy cli vendor/bin/behat -- --definitions=i`
- Verbose test output: `ahoy test-bdd [file] -v`
- Debug with screenshots: Add `And I save screenshot` step
- Test profiles: default, p0 (non-smoke), p1 (smoke/@p1 tests)

## Configuration Management

### Drupal Configuration
- Export config: `ahoy drush cex -y` (required after config changes)
- Import config: `ahoy drush cim -y`
- Config stored in: `config/default/`
- Config splits: dev (`config/ci/`) and test environments

### Content Deployment
- Deploy hooks: `web/modules/custom/do_core/do_core.deploy.php`
- Run deployment hooks: `ahoy drush deploy:hook`
- Sequential hook naming: `hook_deploy_1`, `hook_deploy_2`, etc.
- If deployment fails, update hook name with next sequence number

## Development Workflow

### Feature Development
1. Create feature branch: `feature/short-name` (max 20 chars)
2. Implement changes
3. Export configuration: `ahoy drush cex -y`
4. Write tests (unit and/or BDD)
5. Run linting: `ahoy lint` (must pass with exit code 0)
6. Commit with format: "Verb in past tense with period."

### Git Branch Naming
- Features: `feature/add-user-auth`, `feature/fix-email-valid`
- Bugfixes: `bugfix/fix-form-validation`
- Hotfixes: `hotfix/security-patch`
- Convert human names to machine-readable: lowercase, hyphens, remove articles

### Branch Workflow
- Main development: `develop` branch
- Features branch from `develop`
- Main branch: Used for production deployments
- Current working branch: `hotfix/25.5.5`

## Code Standards

### PHP Standards
- Follow Drupal coding standards
- snake_case for local variables and method arguments
- camelCase for method names and class properties
- Single quotes for strings (double quotes if containing single quotes)
- All code must pass PHPCS, PHPStan, Rector, PHPMD

### Drupal Conventions
- Custom module namespace: `do_core`
- Use Drupal configuration management
- Follow Drupal module development best practices
- All configuration must be exportable

### Testing Requirements
- Unit tests in `tests/src/Unit/`
- Kernel tests in `tests/src/Kernel/`
- Functional tests in `tests/src/Functional/`
- BDD tests in `tests/behat/features/`
- Use existing Behat step definitions when possible

## Important Constraints

- **Never modify**: `.gitignore`, PHPCS config, PHPStan config, PHPUnit config
- **Never change**: Configuration files for linting tools without explicit request
- **Always export**: Drupal configuration after changes (`ahoy drush cex -y`)
- **No remote push**: Do not push to remote repositories unless explicitly requested
- **Clean database**: Use `ahoy provision` if database becomes "dirty"

## External Resources

- [Vortex Documentation](https://vortex.drevops.com) - Project template reference
- [CivicTheme Documentation](https://docs.civictheme.io/) - Theme component library
- [Drupal Documentation](https://www.drupal.org/documentation) - Platform documentation