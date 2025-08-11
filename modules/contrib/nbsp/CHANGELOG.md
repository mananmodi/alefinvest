# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- add official support of drupal 10.0 (with CKEditor 4)
- add coverage of Drupal 10.1.x
- add coverage of Drupal 10.2.x
- add Drupal GitlabCI
- add Drupal core .stylelintrc.json
- add cpsell project words for Gitlab-CI

### Changed
- fix tests on Drupal 10.2+ using HTML5 filter
- fix issue #3432756: Splitting the links in two

### Removed
- drop support of drupal below 9.5.x
- removed translation-files - Issue #3365383

## [2.3.0] - 2023-06-02
### Fixed
- fix issue #3336616 by Harlor, wengerk: CKEditor5 replaces nbsp with whitespaces

### Removed
- fix Issue #3355217 by wengerk, vincent signoret: ckeditor dependency on 8.x-2.2 drupal 9.4 ckeditor5

## [2.2.0] - 2022-12-05
### Added
- add support for CKEditor 5 - Issue #3277174 by mmiramont, VladimirAus, MacSim: CKEditor 5 compatibility
- add official support of drupal 9.5

### Changed
- change generated markup from <span class="nbsp"> to own markup <nbsp> - Issue #3066349 by John Pitcairn, wengerk, PhilY, Artusamak: Using span is problematic due to Drupal's permissive html filters

### Removed
- drop support of drupal below 9.3.x

### Fixed
- fix deprecation of theme classy for tests

## [2.1.0] - 2022-10-21
### Added
- add dependabot for GitHub Action dependency
- add upgrade-status check
- add coverage for Drupal 9.3, 9.4 & 9.5

### Changed
- disable deprecation notice PHPUnit
- update changelog form to follow keep-a-changelog format
- drop support of drupal 8.8 & 8.9

### Removed
- remove satackey/action-docker-layer-caching on Github Actions
- remove trigger github actions on every pull-request, keep only push

### Fixed
- fixed docker test Javascript on CI
- fixed docker test Unit Database not ready

## [2.0.0-alpha1] - 2020-07-01
### Fixed
- Issue #2941631 - Doc is a bit misleading
- Issue #2996835 - Coding standard
- add travis integration
- add wengerk/docker-drupal-for-contrib
- ensure Drupal 9 readiness

## [1.2.0] - 2017-03-10
### Added
- added CCKEditor Filter to remove span

## [1.1.0] - 2017-03-08
### Added
- add ESLint for Javascript best practices

## [1.0.0] - 2017-03-08
### Added
- First draft.

[Unreleased]: https://github.com/antistatique/drupal-ckeditor-nbsp/compare/8.x-2.3...HEAD
[2.3.0]: https://github.com/antistatique/drupal-ckeditor-nbsp/compare/8.x-2.2...8.x-2.3
[2.2.0]: https://github.com/antistatique/drupal-ckeditor-nbsp/compare/8.x-2.1...8.x-2.2
[2.1.0]: https://github.com/antistatique/drupal-ckeditor-nbsp/compare/8.x-2.0-alpha1...8.x-2.1
[2.0.0-alpha1]: https://github.com/antistatique/drupal-ckeditor-nbsp/compare/8.x-1.2...8.x-2.0-alpha1
[1.2.0]: https://github.com/antistatique/drupal-ckeditor-nbsp/compare/8.x-1.1...8.x-1.2
[1.1.0]: https://github.com/antistatique/drupal-ckeditor-nbsp/compare/8.x-1.0...8.x-1.1
[1.0.0]: https://github.com/antistatique/drupal-ckeditor-nbsp/releases/tag/8.x-1.0
