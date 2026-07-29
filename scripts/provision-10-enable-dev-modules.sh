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
if echo "${environment}" | grep -q -e dev -e stage -e local; then
  drush pm:enable devel
fi

# Component validation only runs where development dependencies are installed.
# Hosting environments build with "--no-dev", so the module code is absent
# there and enabling it would fail.
if echo "${environment}" | grep -q -e local -e ci; then
  drush pm:enable sdc_devel
fi

info "Finished enabling development modules."
