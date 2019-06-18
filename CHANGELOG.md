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

### Added
- Html2Text utility

### Changed

- Default email format BOTH
- Email utility now converts HTML to texts for both

### Deprecated

- [ ] Shell

## [1.15.0] - 2019-06-17

### Added

- Http Utility
- Auth API authentication
- Auth Component is authorized and controller authorization
- Router: on method
- HttpException, and made this parent class of some http errors
- Added Serializer class
- Added controller serialize
- Helper functions, contains,left,right,length,begins,lower
- File/Folder perms alias

### Changed

- Error handler now uses request for content type
- Router setRequest to request
- File/folder options recursive etc

## [1.14.0] - 2019-06-13

### Changed

- Integration testing for controllers now includes more assertion methods
- Response cookie getter now returns cookie array

## [1.12.1] - 2019-06-11

### Changed

- Locales:generator command renamed to locale:generator
- Console script is now PHP based
- worked on tests and improved coverage

### Fixed

- Windows friendlier paths
- Throwing migration exception

## [1.12.0] - 2019-06-11

### Fixed

- LocaleGenerator issue with progressbar when running one locale
- Default validation error messages
-

### Changed

- Time fields are no longer converted to timezone unless date is present
- Number format now detects if number is float or integer
- I18n now looks for fallback language
- Behavior scaffold includes default methods
- Validator now uses locale settings for default date,time,datetime formats
- FormHelper formats date,datetime,time and number fields

### Added

- Float and integer validation
- Delocalize behavior

## [1.11.0] - 2019-06-10

### Fixed

- Intl Helper get default language

### Changed

- Gone back to original design for model validation, notBlank and required.

## [1.10.3] - 2019-06-10

### Changed

- Restored original behavior of model validation to only validate modified fields.

## [1.10.2] - 2019-06-10

### Changed

- renamed locales:generator to locales:generate

## [1.10.1] - 2019-06-10

### Fixed

- Plugin installer append application.php

## [1.10.0] - 2019-06-10

### Added

- CSV Utility
- File Utility
- Folder Utility

### Changed

- Form Helper create accepts model name

### Fixed

- Console Application issue with subcommands and options were been parsed by wrong parser

### Removed

- Duplicate files due to previous refactoring

## [1.9.1] - 2019-06-08

### Fixed

- Fixed issues with multiple validation rules and fails
- FormHelper required field indicator

### Changed

- Validation notBlank rule and required behavior

## [1.9.0] - 2019-06-08

### Changed

- Validation notBlank rule and required behavior

### Fixed

- Missing add button from related list template

## [1.8.1] - 2019-06-07

### Fixed

- Added missing FormHelper function submit

## [1.8.0] - 2019-06-07

### Fixed

- Typo in deprecation log

### Changed

- Request headers/header cookies/cookie
- Response headers/cookies
- FormHelper button default button is no longer submit. Use FormHelper:submit or set type option

## [1.7.4] - 2019-06-07

### Fixed

- Cookie check functions were not renamed

### Changed

- CookieHelper,CookieComponent write accepts strtotime string by default
- response cookie accepts strtotime string by default

### Depreciated

- Cookie check, use Cookie exists

## [1.7.3] - 2019-06-06

### Removed

- Removed duplicated tests due to restructing

### Fixed

- Fixed issue when using non existant class name in ConsoleApp

## [1.7.2] - 2019-06-06

### Fixed

- Fixed added missing clear method to SessionHelper and SessionComponent

## [1.7.1] - 2019-06-06

### Fixed

- Fixed issue with composer plugins

## [1.7.0] - 2019-06-06

### Changed

- bootstrap now autoloads, then loads application.php
- Changed how plugins load composer installed plugins

## [1.6.4] - 2019-06-06

### Fixed

- Fixed Plugin scaffolding template

## [1.6.3] - 2019-06-06

### Fixed

- Fixed some session check

### Added

- DotEnv now parses with export

### Depreciated

- Session check
- Cache check

### Removed

- Removed tests which were duplicated due to structure change

## [1.6.2] - 2019-06-06

### Fixed

- Fixed path issues due to renaming of framework

## [1.6.1] - 2019-06-05

### Removed

- removed old disk storage

## [1.6.0] - 2019-06-05

### Added

- Storage class with Local,FTP,and SFTP engines

### Changed

- Renaming Framework Repo
- Datasource Engines moved to Engine\Datasource
- Cache Engines moved to Engine\Cache
- Cache Engine moved to Origin\Utility namespace
- Refactored CacheEngine to abstract class

### Fixed

- Collection take
- Collection return type hinting
- Typos in Locales Generator

## [1.5.1] - 2019-05-31

### Changed

- The automatic loading of environment files has been changed back due to increased complexity in deployment situations. Original method of Server.php continues to be the best.

## [1.5.0] - 2019-05-31

### Added

- added DotEnv class for .env file parsing
- env function and loading config/environments

### Changed

- Email settings. Client is now domain and added SSL option
- Cookie no longer json encode non array values

### Fixed

- Connection issues now throw ConnectionException
- Command runner error when description is an array

## [1.4.0] - 2019-05-27

### Added

- Translation method and added plural support
- I18n locales generator command

### Changed

- I18n Date/Number setter/getters setCurrency,setLocale etc to defaultCurrency, locale
- Translation function \_\_ now uses {placeholder} instead of sprintf
- Renamed number helper and utility decimal method to precision.
- I18n class

### Fixed

- Fixed Yaml parsing issue with null values
- consoleio progress bar jitter fix
- scaffolding bug fix
- bug with debug data output with when % is involed

### Removed

- Dummy help data

## [1.3.1] - 2019-05-25

### Fixed

- Missing generator templates

## [1.3.0] - 2019-05-25

### Added

- Console Commands
- Generate, Db Commands
- PostgreSQL support

### Changed

- Added loadFixture by injecting Fixture manager into the test, framework callbacks, initialize, startup,shutdown.
- Changed BufferedConsoleOutput to TestSuite\Stub
- plugins.json filename
- ORM queries now use lowercase model alias as table alias

### Fixed

- console output style parser tag within tag bug

### Removed

- Status task
- Schema shell

## [1.2.3] - 2019-05-13

### Fixed

- Merged development into master :(

## [1.2.0] - 2019-05-13

### Added

- Added Migrations

### Changed

- Database schema files have been moved to ROOT/db
- In debug mode log file is development.log

### Fixed

- Fixed issue when trying to save data to medium/long text fields
- Fixed test issues caused by upgrading to MySQL 8
- Fixed test issues caused by change in console ui

## [1.1.2] - 2019-05-10

### Added

- Added support for installing plugins via composer

## [1.1.1] - 2019-05-09

### Fixed

- Fixed console colors
- Fixed console input to accept input without options
- Fixed console shell options now show on sub commands
- Fixed console showing help when using multiple required arguments

### Changed

- Removed console clear screen and adjusted
- changed schema folder to db
- Errors display
- Added custom meta mapping and changed model results to use this instead.

### Added

- Added shell:notice shell:info shell:debug
- ConfigTrait delete if null passed
- DB console shell app
- Plugin install shell command

## [1.1.0] - 2019-05-08

### Changed

- Restructured code to work with composer create-project

## [1.0.0] - 2019-05-08

### Added

- Added Console command specific help, arguments and options.
- YAML utility
- GeneratePlugin now generates middleware and shells
- Shell status method (StatusTask being depreciated)
- Request referer method
- Added Dom Utility which extends Dom to add javascript style selectors.

### Removed

- Removed docs, this has been migrated to own [repository](https://github.com/originphp/website).
- Removed IntegrationTesting fails which were duplicated since refactoring
- Removed logger helper function

### Changed

- Move Router,Request,Response,Session,Cookie,BaseApplication,Middleware, ErrorHandler from Core to Http folder
- Changed Generate Plugin to work with commands and options parsing.
- ConsoleException now extends StopExecutionException to work with console testing
- Autoloader/Plugin getInstance to instance
- Redirect now returns the response object to return the object
- Response::Status changed to statusCode
- Added option to disable cookie encryption
- ErrorHandlers log errors in console and debug mode
- Queue utility now accepts dot notation names
- Connection manager error display
- If assocation is defined and data is not array then marshaller will remove data. This is to prevent issues
  elsewhere later.
- When marshalling, assocation names are no longer merged with field list.
- Worked on unit testing. Coverage 80% (excluding schema and generate plugin)
- Improved documentation

### Fixed

- Fixed test failures caused by recent changes to cookie encryption
- Fixed issue with code still be run in integration tests after redirect
- Reading cookies from request now decrypts whole array
- Fixed issue with InegrationTesting and importing records. On subsequent tests imported records were not avilable.
- Fixed PHPUnit\Framework\Exception: Argument #2 (No Value) of PHPUnit\Framework\Assert::assertContains()
- issue with gitignore
- Entities from find had all fields marked as modified. added markClean
- Issue wtih file download
- Issue with merge controller vars
- Email client detection on server undefined offset
- Queue utility restoring number of tries
- Model delete dependent on multiple assocations had id switched
- Form helper radio bug fixes, options overwriting type and control radio was not wrapping in div correctly.
- Html helper script tag bug fix
- Issue with exceptions called by helpers within elements which caused nested output buffering
- Fix bug with marshaller and extracting fields on associated records

## [1.0.0-beta] - 2019-03-26

### Removed

- Reset changelog since there were too many changes going from alpha to beta to prevent confusion.
