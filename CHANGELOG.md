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