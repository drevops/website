#!/usr/bin/env bash

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

drush() { ./vendor/bin/drush -y "$@"; }

# Perform operations based on the current environment.
if drush php:eval "print \Drupal\core\Site\Settings::get('environment');" | grep -q -e local; then
  drush pm:enable devel
fi
