#!/usr/bin/env bash

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

info() { printf "   ==> %s\n" "${1}"; }
task() { printf "     > %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }

drush() { ./vendor/bin/drush -y "$@"; }

info "Started enabling development modules."

environment="$(drush php:eval "print \Drupal\core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

# Perform operations based on the current environment.
case "${environment}" in
  dev | stage | local)
    drush pm:enable devel
    ;;
esac

# Component validation runs in CI as well as on developer machines, so this
# module is enabled everywhere except production.
case "${environment}" in
  dev | stage | local | ci)
    drush pm:enable sdc_devel
    ;;
esac

info "Finished enabling development modules."
