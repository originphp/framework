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
List:         git tag
Create:       git tag -a <tag_name> -m '<tag_message>'
Upload:       git push origin --tags
Delete tag:   git tag -d <tag_name>

## [Unreleased]
### Fixed
- Plugin folder loading
- Plugin view path bug
- Validation required bug fix
- FileEngine cache
- NullCache engine name
- Issue with error folder in lower case (not picked up in docker)
- Auth scope fixed
- Bookmarks edit tags not listed
- Bookmarks not validating URL
- Fixed Make plugin related list issue and back button
- Bookmarks creating null tags
- Plugin config folder path moved so its consistent with rest of framework

### Added
- Option to disable I18N date/number parsing
- Cache system (Redis,Memcached,File,Apcu,Array and Null)
- Logger Object
- Queue System
- Basic console options parsing
- Queue System for background jobs
- Marshalling now has option to disable parsing of date/datetime/numbers etc
- Debug function
- Make plugin now creates PHPUnit xml configuration file
- Added Collection documentation
- setError,getError functions in Entity and refactored use of errors
- Bookmarks demo tags
- Templater now works with dot notation

### Changed
- Renamed controller startup/shutdown to before/after filter
- Changed make plugin to generate
- Changed webroot to public
- Changed configure has to check
- Changed Utils folder to Utility
- Removed irrelvant keys from schema generate
- Changed location of schema files from config, to config/schema
- backtrace() now works properly in CLI
- Improved documentation
- adjusted PR function to only show when debug is set
- TestApp is now completely independent, tests modified as well.
- Changed some public methods to protected or private
- Moved Collections to Utils
- Make controller delete action template changed to use object->id

### Removed
- Log Class
- Deleted composer.lock
- unused code in Entity and View

## [1.0.0-beta] - 2019-xx-xx