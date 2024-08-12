#!/usr/bin/env bash
##
# Download DB dump from Lagoon environment.
#
# This script will create a database dump from in the specified environment and
# download it into specified directory.
#
# It will also remove previously created DB dumps.
#
# It does not rely on 'lagoon-cli', which makes it capable of
# running on hosts without installed lagooncli.
#
# It does require using SSH key added to one of the users in Lagoon who has
# SSH access.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091,SC2029,SC2124,SC2140

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Flag to download a fresh copy of the database.
DREVOPS_DB_DOWNLOAD_REFRESH="${DREVOPS_DB_DOWNLOAD_REFRESH:-}"

# Lagoon project name.
LAGOON_PROJECT="${LAGOON_PROJECT:?Missing required environment variable LAGOON_PROJECT.}"

# The source environment branch for the database source.
DREVOPS_DB_DOWNLOAD_ENVIRONMENT="${DREVOPS_DB_DOWNLOAD_ENVIRONMENT:-main}"

# Remote DB dump directory location.
DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR="/tmp"

# Remote DB dump file name. Cached by the date suffix.
DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE="${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE:-db_$(date +%Y%m%d).sql}"

# Wildcard file name to cleanup previously created dump files.
#
# Cleanup runs only if the variable is set and $DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE
# does not exist.
DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP="${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP:-db_*.sql}"

# SSH key fingerprint used to connect to a remote.
DREVOPS_DB_DOWNLOAD_SSH_FINGERPRINT="${DREVOPS_DB_DOWNLOAD_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
DREVOPS_DB_DOWNLOAD_SSH_FILE="${DREVOPS_DB_DOWNLOAD_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# The SSH host of the Lagoon environment.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST:-ssh.lagoon.amazeeio.cloud}"

# The SSH port of the Lagoon environment.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT:-32222}"

# The SSH user of the Lagoon environment.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER:-${LAGOON_PROJECT}-${DREVOPS_DB_DOWNLOAD_ENVIRONMENT}}"

# Directory where DB dumps are stored on the host.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name on the host.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

# Name of the webroot directory with Drupal codebase.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in ssh rsync; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started database dump download from Lagoon."

mkdir -p "${DREVOPS_DB_DIR}"

# Try to read credentials from the credentials file.
if [ -f ".env.local" ]; then
  # shellcheck disable=SC1090
  t=$(mktemp) && export -p >"${t}" && set -a && . ".env.local" && set +a && . "${t}" && rm "${t}" && unset t
fi

export DREVOPS_SSH_PREFIX="DB_DOWNLOAD" && . ./scripts/drevops/setup-ssh.sh

ssh_opts=(-o "UserKnownHostsFile=/dev/null")
ssh_opts+=(-o "StrictHostKeyChecking=no")
ssh_opts+=(-o "LogLevel=error")
ssh_opts+=(-o "IdentitiesOnly=yes")
ssh_opts+=(-p "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT}")
if [ "${DREVOPS_DB_DOWNLOAD_SSH_FILE:-}" != false ]; then
  ssh_opts+=(-i "${DREVOPS_DB_DOWNLOAD_SSH_FILE}")
fi

# Initiates an SSH connection to a remote server using provided SSH options.
# On the server:
# 1. Checks for the existence of a specific database dump file.
# 2. If the file doesn't exist or a refresh is requested:
#    a. Optionally removes any previous database dumps.
#    b. Uses `drush` to create a new database dump with specific table structure options.
# 3. If the file exists and no refresh is requested, notifies of using the existing dump.
ssh \
  "${ssh_opts[@]}" \
  "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER}@${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST}" service=cli container=cli \
  "if [ ! -f \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}\" ] || [ \"${DREVOPS_DB_DOWNLOAD_REFRESH}\" == \"1\" ] ; then \
     [ -n \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP}\" ] && rm -f \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}\"\/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP} && echo \"Removed previously created DB dumps.\"; \
     echo \"      > Creating a database dump ${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}.\"; \
     /app/vendor/bin/drush --root=./${DREVOPS_WEBROOT} sql:dump --structure-tables-key=common --structure-tables-list=ban,event_log_track,flood,login_security_track,purge_queue,queue,webform_submission,webform_submission_data,webform_submission_log,watchdog,cache* --extra-dump=--no-tablespaces > \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}\"; \
   else \
     echo \"      > Using existing dump ${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}.\"; \
   fi"

note "Downloading a database dump."
ssh_opts_string="${ssh_opts[@]}"
rsync_opts=(-e "ssh ${ssh_opts_string}")
rsync "${rsync_opts[@]}" "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER}@${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST}":"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}"/"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}" "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"

pass "Finished database dump download from Lagoon."
