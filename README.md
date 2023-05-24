[![build status](https://openlab.ncl.ac.uk/gitlab/make-place/web/badges/master/build.svg)](https://openlab.ncl.ac.uk/gitlab/make-place/web/commits/master)

# Make (your) Place

A geographical mapping platform designed to be reconfigurable and redeployable. Based on [Silverstripe](https://www.silverstripe.org/) and deployed through [Docker](https://www.docker.com/).

## Features

- Geographical surveys, place series of questions on a map
- Rich survey api to interact with your surveys outside of the site
- Commenting & Voting on survey responses
- Full CMS to configure and design your deployment's website
- Dynamic filtering interface which is completely configurable
- Customisable theme by docker variables

## Project Structure

- Features are implemented as Silverstripe modules; root level folders each with their own MVC structure inside
- JavaScript & SCSS transpiling via [Webpack](https://webpack.js.org/) (placed in `/public`), see `scripts/build-assets` and `scripts/dev-runtime`
- Server is split into 2 docker images, one for all packages (js & php) the other add project code, see `base.Dockerfile` and `Dockerfile`
- Server is based on [PHP Composer Image](https://openlab.ncl.ac.uk/gitlab/rob/composer-image) to provide `php5-fpm` & `nginx` stack with `php-composer` to install modules

## Prerequisites

- [Docker](https://www.docker.com/) and [docker-compose](https://docs.docker.com/compose/)
- [Node Js](https://nodejs.org) for local development
- A [Sendgrid](https://sendgrid.com/) api key for sending emails through their smtp

## Setup

1. Start up your containers

```bash
cd into/your/project
docker-compose up -d --build
docker-compose ps

# You'll need to setup the geo database and run migrations
# use the geo in docker-compose for whats required
# see: https://openlab.ncl.ac.uk/gitlab/make-place/geo#dev-notes
```

2. Start webpack watch to compile assets

```bash
npm install    # Only on your first run
npm run dev
open http://localhost:8080
```

3. All code should be mapped into your container so saving and reloading will always be the latest version

## Deploying Image (to GitLab)

We use a CI to continually run tests and build the `latest` version whenever you commit to master. To publish a specific version run this script:

```bash
# (optional) If you have changed composer or npm packages run this
#            and update your Dockerfile to reference the new base
VERSION=x.y.z
git tag base-$VERSION
docker build -f base.Dockerfile -t mkpl/php-platform:base-$VERSION .
docker push mkpl/php-platform:base-$VERSION

# Build and push image to openlab.ncl.ac.uk/gitlab
npm version # minor | major | patch | ...
VERSION=x.y.z
docker build -t mkpl/php-platform:$VERSION .
docker push mkpl/php-platform:$VERSION

```

## Detailed Project Structure

| Folder         | Contents                                                                                 |
| -------------- | ---------------------------------------------------------------------------------------- |
| `_config`      | Various configurations for the project including webpack setup and scss shared variables |
| `assets`       | Volumed mapped from container, where Silverstripe puts uplaoded assets                   |
| `auth`         | **Auth Module**, logic related to logging in and registering                             |
| `docs`         | Generated documentation for the API, generate with `scripts/apidoc`                      |
| `interaction`  | **Interaction Module**, logic related to voting and commenting on things                 |
| `maps`         | **Maps Module**, logic related to setting up and viewing configurable maps               |
| `mysite`       | **Mysite Module**, shared logic between modules and the basis for others to use          |
| `node_modules` | Imported javascript modules, install with `npm install`                                  |
| `public`       | Static files to be included in html, Webpack also transpiles into here                   |
| `scripts`      | Various scripts to ease development, from building the docker image or running Webpack   |
| `surveys`      | **Survey Module**, logic realted to creating and rendering surveys for people to answer  |
| `themes`       | Generic templates & styles for rendering pages server-side                               |

## Environment variables

Here are the environment variables the project uses and what they are used for. Required variables will fail the docker build if they are missing, non-required can be ignored but their relevant features will **not** work.

| Variable           | Required | Meaning                                                                                                                                                                   |
| ------------------ | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| DB_HOST            | **yes**  | The host providing the database, in Docker this can be the name of a linked database container                                                                            |
| DB_USER            | **yes**  | The user Silverstripe will use to access the database                                                                                                                     |
| DB_PASS            | **yes**  | The password of the above user                                                                                                                                            |
| DB_NAME            | **yes**  | The database to store site data in, the user **must** have access to it and be able to edit the schema                                                                    |
| DEPLOYMENT         | no       | The domain name of the deployment, default is `make.place`                                                                                                                |
| DB_TYPE            | no       | The type of database to connect to, default is `MySQLDatabase`                                                                                                            |
| DB_PATH            | no       | The directory a file-based database will be store, e.g. `/app/testdb/`                                                                                                    |
| SITE_ENV           | no       | The mode of the site, `live`, `testing`, `dev`; defaults to `live`                                                                                                        |
| LOCALE             | no       | The locale of the site, used for date formatting & translations, defaults to `en_GB`                                                                                      |
| LOG_FILE           | no       | Where to store the log file, relative to this `mysite/_config.php`                                                                                                        |
| DEFAULT_USER       | no       | The username of the default admin to create, must also have `DEFAULT_PASS` set                                                                                            |
| DEFAULT_PASS       | no       | The password of the default admin to create, must also have `DEFAULT_USER` set                                                                                            |
| LOG_EMAIL          | no       | An email to send server errors to                                                                                                                                         |
| ADMIN_EMAIL        | no       | The email address emails will come from                                                                                                                                   |
| CONTACT_EMAIL      | no       | The email address for people to contact the system owner                                                                                                                  |
| SITE_BASE          | no       | If the server is being run on a subdirectory e.g. `openlab.ncl.ac.uk/dokku/my-site`, setting this will fix Silverstripe's URLs. For this example set to `/dokku/my-site/` |
| G_RECAPTCHA_PUBLIC | no       | Your [Google Recaptcha](https://www.google.com/recaptcha) public key                                                                                                      |
| G_RECAPTCHA_SECRET | no       | Your [Google Recaptcha](https://www.google.com/recaptcha) secret key                                                                                                      |
| SENDGRID_API_KEY   | no       | Your [Sendgrid](https://sendgrid.com) Api key                                                                                                                             |

> NOTE: Docker Compose file has been modified in this fork to be able to deploy make.place with the use of Traefik [reverse-proxy](https://github.com/aarepuu/reverse-proxy).
