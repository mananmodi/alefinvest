# Developing on CKEditor Non-breaking space Plugin

* Issues should be filed at https://www.drupal.org/project/issues/nbsp

## 📦 Repositories

Drupal repo
  ```
  git remote add drupal git@git.drupal.org:project/nbsp.git
  ```

Github repo
  ```
  git remote add github git@github.com:antistatique/drupal-ckeditor-nbsp.git
  ```

## 🔧 Prerequisites

First of all, you will need to have the following tools installed
globally on your environment:

  * drush
  * Latest dev release of Drupal
  * docker
  * docker compose

### Project bootstrap

Once run, you will be able to access to your fresh installed Drupal on `localhost::8888`.

    docker compose build --pull --build-arg BASE_IMAGE_TAG=9.5 drupal
    (get a coffee, this will take some time...)
    docker compose up -d drupal chrome
    docker compose exec -u www-data drupal drush site-install standard --db-url="mysql://drupal:drupal@db/drupal" -y

    # You may be interesed by resetting the admin password of your Docker and install the module using those cmd.
    docker compose exec drupal drush user:password admin admin
    docker compose exec drupal drush en nbsp

## 🚔 Build Javascript

You need to run `yarn` before using Webpack. Then run the commmand:

  ```
  // Build Plugin CKEditor 5 assets.
  yarn build
  ```

## 🏆 Tests

We use the [Docker for Drupal Contrib images](https://hub.docker.com/r/wengerk/drupal-for-contrib) to run testing on our project.

Run testing by stopping at first failure using the following command:

    docker compose exec -u www-data drupal phpunit --group=nbsp --no-coverage --stop-on-failure --configuration=/var/www/html/phpunit.xml

## 🚔 Check Javascript best practices

You need to run `yarn` before using ESLint. Then run the commmand:

  ```
  // Run Prettier
  yarn run prettier

  // Run ESLint - Passing ruleset with more problematic rules turned off.
  yarn run lint:js-passing
  ```

## 🚔 Check Styles best practices

You need to run `yarn` before using Stylelint. Then run the commmand:

  ```
  // Run Stylelint
  yarn lint:css
  ```

## 🚔 Check Drupal coding standards & Drupal best practices

During Docker build, the following Static Analyzers will be installed on the Docker `drupal` via Composer:

- `drupal/coder^8.3.1`  (including `squizlabs/php_codesniffer` & `phpstan/phpstan`),

The following Analyzer will be downloaded & installed as PHAR:

- `phpmd/phpmd`
- `sebastian/phpcpd`
- `wapmorgan/PhpDeprecationDetector`

### Command Line Usage

    ./scripts/hooks/post-commit
    # or run command on the container itself
    docker compose exec drupal bash

#### Running Code Sniffer Drupal & DrupalPractice

https://github.com/squizlabs/PHP_CodeSniffer

PHP_CodeSniffer is a set of two PHP scripts; the main `phpcs` script that tokenizes PHP, JavaScript and CSS files to
detect violations of a defined coding standard, and a second `phpcbf` script to automatically correct coding standard
violations.
PHP_CodeSniffer is an essential development tool that ensures your code remains clean and consistent.

  ```
  $ docker compose exec drupal ./vendor/bin/phpcs ./web/modules/contrib/nbsp/
  ```

Automatically fix coding standards

  ```
  $ docker compose exec drupal ./vendor/bin/phpcbf ./web/modules/contrib/nbsp/
  ```

#### Running PHP Mess Detector

https://github.com/phpmd/phpmd

Detect overcomplicated expressions & Unused parameters, methods, properties.

  ```
  $ docker compose exec drupal phpmd ./web/modules/contrib/nbsp/ text ./phpmd.xml \
  --suffixes php,module,inc,install,test,profile,theme,css,info,txt --exclude *Test.php,*vendor/*,*node_modules/*
  ```

#### Running PHP Copy/Paste Detector

https://github.com/sebastianbergmann/phpcpd

`phpcpd` is a Copy/Paste Detector (CPD) for PHP code.

  ```
  $ docker compose exec drupal phpcpd ./web/modules/contrib/nbsp/src --suffix .php --suffix .module --suffix .inc --suffix .install --suffix .test --suffix .profile --suffix .theme --suffix .css --suffix .info --suffix .txt --exclude *.md --exclude *.info.yml --exclude tests --exclude vendor/ --exclude node_modules/
  ```

#### Running PhpDeprecationDetector

https://github.com/wapmorgan/PhpDeprecationDetector

A scanner that checks compatibility of your code with PHP interpreter versions.

  ```
  $ docker compose exec drupal phpdd ./web/modules/contrib/nbsp/ \
    --file-extensions php,module,inc,install,test,profile,theme,info --exclude vendor --exclude node_modules
  ```

### Enforce code standards with git hooks

Maintaining code quality by adding the custom post-commit hook to yours.

  ```bash
  cat ./scripts/hooks/post-commit >> ./.git/hooks/post-commit
  ```
