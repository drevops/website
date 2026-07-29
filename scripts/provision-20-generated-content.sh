#!/usr/bin/env bash
##
# Generate content for non-production environments.
#
# shellcheck disable=SC2086

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

info() { printf "   ==> %s\n" "${1}"; }
task() { printf "     > %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }

drush() { php -d memory_limit=2G vendor/bin/drush.php -y "$@"; }

GENERATED_CONTENT_SKIP="${GENERATED_CONTENT_SKIP:-0}"

info "Started generated content operations."

environment="$(drush php:eval "print \Drupal\core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

# Perform operations based on the current environment.
if echo "${environment}" | grep -q -e dev -e ci -e local; then
  if [ "${VORTEX_PROVISION_OVERRIDE_DB:-0}" = "1" ]; then

    if [ "${GENERATED_CONTENT_SKIP}" = "1" ]; then
      note "Skipping generation of content."
    else
      task "Enabling generated content module."
      export GENERATED_CONTENT_CREATE=1
      drush pm:enable -y do_generated_content
      note "Generated content module enabled."
    fi
  else
    note "Using existing database with existing content."
  fi
else
  note "Skipping generated content operations in production environment."
fi

info "Finished generated content operations."
