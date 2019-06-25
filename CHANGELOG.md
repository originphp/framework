# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

**Types Of Changes:**

- Added for new features.
- Changed for changes in existing functionality.
- Deprecated for soon-to-be removed features.
- Removed for now removed features.
- Fixed for any bug fixes.
- Security in case of vulnerabilities.

**Git Commands:**
List: git tag
Create: git tag -a <tag_name> -m '<tag_message>'
Upload: git push origin --tags
Delete tag: git tag -d <tag_name>

## [Unreleased]
###
- Added Travis.yml

### Fixed
- Fixed bug in testGenerateScaffold where it was using default datasource instead of test

### Changed
- Database Connection Exception message now displayed in error, it is needed.
- Move phpunit.xml to root in preparation for travis
- Changed the composer.json to suggest memcached

## [1.18.3] - 2019-06-25
### Changed
- Refactored phpunit settings to work with env vars
- Console progress bar now works with and without ansi support
- Renamed phpunit.xml to phpunit.xml.dist
- Prepared cookie for Cookie.key

### Fixed
- Fixed .gitignore to reflect properly since splitting into composer packages
- Added Locale reset to Intl Number/Date tests
- Fixed File::group/Folder::group returning null on non linux systems
- NumberTest,DebugerTest, LocalesGeneratorTest, FolderTest fixed fails on different os
- Database exception errors when connecting were not being logged
- Issues with test hashes caused by last refactor Security.salt renamed to Security.pepper

## [1.18.2] - 2019-06-24
### Fixed
- Cookie tests
- Accidentally committed debug code

### Deprecated
- Security.salt and Security::hash option salt. This is now renamed to `pepper` as per the correct terminology

#### Security
- Cookie key was larger than openssl function would use and was truncated

## [1.18.1] - 2019-06-24
### Added
- Security hashPassword, verifyPassword
- Security compare for comparing hashed strings to protect against timing attacks

### Fixed
- String functions contains,begins,ends, length etc changed to multibyte
- Removed not used argument description

### Changed
- Composer.json - cleaned this up and improved
- CSRF Protection Middleware token changed to use the Security::hash function
- Security::hash now uses array of options
- Refactored Auth component to work with Security::verifyPassword
- Changed Cookie classes to work with refactored security utility
- Security encrypt returns base64 encoded string, a decrypt expects that.
- Security hash now throws exception if Algo is not known
- Html2Text now supports more headings, lists, definitions, blockquotes
- Removed unnecessary array access from collections

### Security
- Switched to random_bytes from openssl_random_pseudo_bytes
- Refactored Security::encrypt/decrypt added protection against timing attacks
- CSRF Protection Middleware, added protection against timing attacks
- File Utility switched to internal uid function for unique id generation
- Form Helper changed to escape values for protection against Cross-Site Scripting (XSS) attacks
- Adjusted h function work better for security
- Improved Email Utility for protection against Email Header Injection Attacks