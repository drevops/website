#!/usr/bin/env bash
##
# Login to the container registry.
#
# Supported registries:
# - docker.io
#
# @todo Add support for more registries.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Container registry name.
#
# Provide port, if required as `<server_name>:<port>`.
DREVOPS_CONTAINER_REGISTRY="${DREVOPS_CONTAINER_REGISTRY:-docker.io}"

# The username to login into the container registry.
DREVOPS_CONTAINER_REGISTRY_USER="${DREVOPS_CONTAINER_REGISTRY_USER?Missing required value for DREVOPS_CONTAINER_REGISTRY_USER.}"

# The password to login into the container registry.
DREVOPS_CONTAINER_REGISTRY_PASS="${DREVOPS_CONTAINER_REGISTRY_PASS?Missing required value for DREVOPS_CONTAINER_REGISTRY_PASS.}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#shellcheck disable=SC2043
for cmd in docker; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

[ -z "${DREVOPS_CONTAINER_REGISTRY}" ] && fail "Missing required value for DREVOPS_CONTAINER_REGISTRY." && exit 1

if [ -f "${HOME}/.docker/config.json" ] && grep -q "${DREVOPS_CONTAINER_REGISTRY}" "${HOME}/.docker/config.json"; then
  note "Already logged in to the registry \"${DREVOPS_CONTAINER_REGISTRY}\"."
elif [ -n "${DREVOPS_CONTAINER_REGISTRY_USER}" ] && [ -n "${DREVOPS_CONTAINER_REGISTRY_PASS}" ]; then
  note "Logging in to registry \"${DREVOPS_CONTAINER_REGISTRY}\"."
  echo "${DREVOPS_CONTAINER_REGISTRY_PASS}" | docker login --username "${DREVOPS_CONTAINER_REGISTRY_USER}" --password-stdin "${DREVOPS_CONTAINER_REGISTRY}"
else
  note "Skipping login into the container registry as eithe DREVOPS_CONTAINER_REGISTRY_USER or DREVOPS_CONTAINER_REGISTRY_PASS was not provided."
fi
