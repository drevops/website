TEST
test2
<div align="center">

# DrevOps Website
Drupal 11 implementation of DrevOps Website for DrevOps

[![Database, Build, Test and Deploy](https://github.com/drevops/website/actions/workflows/build-test-deploy.yml/badge.svg)](https://github.com/drevops/website/actions/workflows/build-test-deploy.yml)
![Drupal 11](https://img.shields.io/badge/Drupal-11-blue.svg)
[![codecov](https://codecov.io/gh/drevops/website/graph/badge.svg)](https://codecov.io/gh/drevops/website)
![Automated updates](https://img.shields.io/badge/Automated%20updates-RenovateBot-brightgreen.svg)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY VORTEX TO TRACK INTEGRATION)

[![Vortex](https://img.shields.io/badge/Vortex-25.7.0-65ACBC.svg)](https://github.com/drevops/vortex/tree/25.7.0)

</div>

## Environments

- DEV: https://dev.drevops.com
- PROD: https://www.drevops.com

## Local environment setup

- Make sure that you have latest versions of all required software installed: [Docker](https://www.docker.com/), [Pygmy](https://github.com/pygmystack/pygmy), [Ahoy](https://github.com/ahoy-cli/ahoy)
- Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
- Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/desktop/settings-and-maintenance/settings/#virtual-file-shares)).

- Authenticate with Lagoon
  1. Create an SSH key and add it to your account in the [Lagoon Dashboard](https://ui-lagoon-master.ch.amazee.io/).
  2. Copy `.env.local.example` to `.env.local`.
  3. Update `$VORTEX_DB_DOWNLOAD_SSH_FILE` environment variable in `.env.local` file
     with the path to the SSH key.

- `ahoy download-db`

- `pygmy up`
- `ahoy build`

## Project documentation

- [FAQs](docs/faqs.md)
- [Testing](docs/testing.md)

- [CI](docs/ci.md)

- [Deployment](docs/deployment.md)

---
_This repository was created using the [Vortex](https://github.com/drevops/vortex) Drupal project template_
