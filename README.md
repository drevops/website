# DrevOps Website
Drupal 10 implementation of DrevOps Website for DrevOps

[![CircleCI](https://dl.circleci.com/status-badge/img/gh/drevops/website/tree/main.svg?style=shield&circle-token=CCIPRJ_PoXMozZNjmvtyESrUQdKZa_0b9f2ba0b72fd042187733abcecdc6b7ae5cdaf1)](https://dl.circleci.com/status-badge/redirect/gh/drevops/website/tree/main)
![Drupal 10](https://img.shields.io/badge/Drupal-10-blue.svg)
[![codecov](https://codecov.io/gh/drevops/website/graph/badge.svg)](https://codecov.io/gh/drevops/website)


[![RenovateBot](https://img.shields.io/badge/RenovateBot-enabled-brightgreen.svg?logo=renovatebot)](https://renovatebot.com)


[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY DREVOPS TO TRACK INTEGRATION)

[![DrevOps](https://img.shields.io/badge/DrevOps-1.22.0-blue.svg)](https://github.com/drevops/scaffold/tree/1.22.0)

## Local environment setup

- Make sure that you have latest versions of all required software installed:
  - [Docker](https://www.docker.com/)
  - [Pygmy](https://github.com/pygmystack/pygmy)
  - [Ahoy](https://github.com/ahoy-cli/ahoy)
- Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
- Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/docker-for-mac/osxfs/#access-control)).



- Authenticate with Lagoon
  1. Create an SSH key and add it to your account in the [Lagoon Dashboard](https://ui-lagoon-master.ch.amazee.io/).
  2. Copy `.env.local.default` to `.env.local`.
  3. Update `$DREVOPS_DB_DOWNLOAD_SSH_FILE` environment variable in `.env.local` file
     with the path to the SSH key.




- `ahoy download-db`

- `pygmy up`
- `ahoy build`

### Apple M1 adjustments

Copy `docker-compose.override.default.yml` to `docker-compose.override.yml`.

## Project documentation

- [FAQs](docs/faqs.md)
- [Testing](docs/testing.md)
- [CI](docs/ci.md)
- [Releasing](docs/releasing.md)
- [Deployment](docs/deployment.md)
