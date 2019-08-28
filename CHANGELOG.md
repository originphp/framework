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
## [1.29.0] - xxx
### Added
- Mailer

### Changed
- Email now returns a Mailer\Message object instead of string

### Deprecated
- Utility\Email is now Mailer\Email

### Fixed
- Missing email account error not showing name
- View loading layout from plugin folder

## [1.28.2] - 2019-08-27
### Fixed
- Fixed type error causing type error with onError callback.

## [1.28.1] - 2019-08-27

### Fixed
- Moved declare ticks to top of page due to occasional issues with catching ctrl-c in a docker container.
- Made error messages for missing configurations more explanatory

### Changed
- The Job constructor arguments were moved in a last minute change from the release a few hours ago.

## [1.28.0] - 2019-08-26

### Added
- Jobs
- Service Objects
- ConsoleIntegrationTest extra assertions. E.g. assertOutputNotContains,assertErrorNotContains,assertOutputRegExp,assertErrorRegExp

### Changed
- Limited in memory SQL statement logging to 200 

### Fixed
- ConsoleOutput on Travis was switch TestStub output to plain causing inconsistent results with non framework tests.
- Generate for model test was loading calling parent::setUp twice
- Command help not showing default values for 0

## [1.27.2] - 2019-08-20

### Fixed
- Paginator component was blocking string page numbers

## [1.27.1] - 2019-08-20

### Changed
- Log file engine, file parameter changed to filename as this better fits since the path option is also avilable.

### Fixed
- Fixed issue with postgresql lastval is not yet defined in this session
- Fixed issue caused by constraint failure during tests

## [1.27.0] - 2019-08-19
Cleaned up a code a bit, there are almost 10k lines of code written over a year, fixed comments and added return types for internal functions. Split inflector to Utility namespace with method name changes (see changes)

### Changes
- new Inflector class  - major difference is camelCase now returns lower case first letter, and studly caps terminology is used for upper first case

### Deprecated
- Origin\Core\Inflector is now Origin\Utility\Inflector, function names have been changed
- Model:delete arguments change to be more inline with framework style. `delete($entity,$options)` used to be `delete($entity,$cascade,$callbacks)`;

### Added
- Generate command tests for Behaviors,Helpers, Components and Middleware
- Elasticsearch (Utility/Behavior and Command) 
- Command output with context support for info/debug/out/warning/error/success etc

### Fixed
- Html:sanitize options array was using tags options key which was suppose to be refactored during design stage.
- Markdown::toHtml code blocks had spaced removed due to santization
- Describe was not picking up fulltext indexes
- Legacy handler for db commands switched to SQL

## [1.26.0] - 2019-08-12
Refactored database engine to use the new schema design.

### Changed
- Console db commands now work with default php files instead of sql files
- Datasource:schema/describe now uses caching

### Added
- Db:rollback command
- Seed class
- Migrations add foreign key now accepts on update and delete options
- db:test:prepare command
- TableSchema object or creating tables with full support of constraints, indexes etc
- Cache file engine serialize option. Now serialization can be disabled.
- Cache duration now accepts string, e.g. +2 hours etc

### Fixed
- Migration createJoinTable reverse statement outside of test not working
- Fixed console output overwrite function when overwriting with shorter text
- File Cache exists checks expiration date
- Changelog mentioned of Schema/Migration::foreignKeys, this was not actually committed.

## [1.25.0] - 2019-07-26
### Migration Guide to 1.25
Small breaking change, on a undocumented method.

- keys and values from array returned by `Schema::foreignKeys()` have been changed to `table`,`column`,`name`, `referencedTable`,`referencedColumn`

### Fixed
- Db:schema:load was not disabling foreign key checks when executing statements, this caused issues with SQL files with foreign keys.

### Changed
- All models loaded through model registry will have the datasource set to test (previously only those loaded through fixtures)
- Migration::foreignKeys changed column names more inline with framework.

### Added
- Ability to use fixtures using existing tables in the test database
- BaseSchema::createTable accepts primaryKey option e.g. 'id' or ['contacts_id','tags_id']
- Better support for indexes on multiple columns when using Schema

## [1.24.1] - 2019-07-23
### Fixed
- Session cookies not being sent via non HTTPS

## [1.24.0] - 2019-07-23
### Added
- Added Schema::addIndex Type option to allow for creating fulltext indexes
- Entity modified(fieldname) to check if field was modified

### Fixed
Taking the code coverage from 90%-97*% has been difficult but worthwhile.

- Fixed starts/ends and Text:beginsWith, Text::endsWith returned true when empty string was used
- Fixed SFTP engine loading private key from file
- Fixed prepare results adding blank records when foreignKey is invalid
- Fixed Migration checkColumn checking if nullable
- Migration with pgsql did not drop null and default values
- Fixed bug with PgsqlSchema:change column not configuring type
- Fixed added deprecation warning, for previous deprecated feature which used fallback instead.
- Fixed session engine had a legacy function reset, which has been deprecated instead of removed.
- Fixed unpacking arrays from cookies
- Fixed migration FetchRow/FetchAll feature
- Fixed router adding route with args
- Fixed Markdown::toHtml code block issues

### Security
- Added escaping to Html:fromText
- Tightened Session security
- Markdown::toHtml runs result through Html:sanitize for protection

## [1.23.0] - 2019-07-12
### Added 
- HtmlHelper div function
- Request added new methods ip, path, host, ssl and ajax

### Changed
- Request works with copy of server vars

### Fixed
- Fixed invalid tag issue with Markdown::toHtml and code block
- Fixed issue with router and routes with single character e.g. t/slug/id was getting mapped to txxx/something/sdfsd

### Security
- Added integer check for get params in PaginatorComponent

## [1.22.1] - 2019-07-11

### Changed
- Redis cache engine changed to work with latest version which had deprecated delete, getKeys, setTimeout aliases.


## [1.22.0] - 2019-07-10
### Added
- Validator::extension now works with uploaded files
- CounterCache behavior
- Security::uid and Security::uuid
- CSV::process for processing large CSV files
- CSV utility allowed to pass options such as separator and enclosure
- Text Utility
- Html Utility
- Markdown Utility
- File upload and mime type validation

### Changed
- Email Utility now uses Html Utility

### Deprecated
- The internal class Html2Text is being deprecated, this was used by Email

### Fixed
- length function work with null value
- Fixed scaffold template css name
- Form helper file control was not rendering with correct css class
- Model query not adding table alias to query

## [1.21.1] - 2019-07-03

### Fixed
- ErrorHandler rendering fatal errors

### Security
- Fixed notices, warning showing when in non debug mode

## [1.21.0] - 2019-07-03

### Added

- Added Model::increment Model::decrement
- Added DateHelper::timeAgoInWords

### Changed

- Scaffolding now generates all fields and links via view link instead of primaryKey
- Html::link now escapes title
- IntegrationTestTrait, added server backup/restore
- Schema::buildColumn now can create mediumtext,longtext columns
- notices, warning etc will no longer throw exception
- Created Json/Xml view objects

### Fixed

- Fixed FormHelper missing timestamp mapping
- Fixed error when sorting related model on index generated by scaffolding
- Fixed Scaffolding id issue
- Fixed old reference to length in fixtures and tests
- Fixed Validator automatically failing rules if allowBlank = false. This should be dependent upon rule.
- Fixed NotBlank skipping rules on failure, this was legacy behavior before allowBlank
- Fixed TypeError Argument 1 passed in dispatcher
- Fixed FormHelper not showing max length
- Fixed notice in PaginatorComponent when passing a string for order
- Fixed transaction already started errors
- Fixed Schema adding null when creating table
- Fixed entity:toXml of using incorrect name property


## [1.20.0] - 2019-06-30
Some internal reorganization occurred, moving Cache,Storage and Queue, and rewriting Logger. (See migration guide, to upgrade)
The focus now will resume on increasing code coverage closer to 100%, bug fixes, if any, testing for security vulnerabilities, maybe adding SQLite engine.

### Migration Guide to 1.20

Some internal changes have made that will trigger a deprecation warning in debug mode. In particular Cache,Storage which use engines and Queue will use engines in future, and these have been put into their own folders.

- Rename references `Origin\Utility\Cache` to `Origin\Cache\Cache`. In `config/application.php` there is reference to this.
- Rename references `Origin\Utility\Storage` to `Origin\Storage\Storage`
- Rename references `Origin\Utility\Queue` to `Origin\Storage\Queue`
- If you have used logger `Core\Logger`, then use the new `Log\Log` library.

- If you have used `Cache::use` them then adjust to use `Cache::store`
- If you have used `Storage::use` then adjust to use `Storage::volume`

- In your console integration tests change methods `$this->errorOutput(` to `$this->error(`

### Deprecated

- `Core\Logger` been deprecated use `Log\Log` instead
- `Utility\Cache` been deprecated use `Cache\Cache` instead
- `Utility\Storage` been deprecated use `Storage\Storage` instead
- `Utility\Queue` been deprecated use `Queue\Queue` instead
- `Cache::use` deprecated use `Cache::store` instead
- `Storage:use` deprecated use `Storage::volume` instead
- `ConsoleIntegrationTestTrait::errorOutput` deprecated use `ConsoleIntegrationTestTrait::error` instead

### Changed

- Cleaned up and refactored Database engines
- Cache can now pass array of options
- Storage can now pass array of options

### Added

- Cache::store for getting configured cache store
- Storage::volume for getting configured storage volume
- Added all styles for POSIX levels in ConsoleOutput

### Fixed
- Fixed Dropping tables using Pgsql when foreignKeys are involved
- Storage FTP engine changed default mode to passive (default used nowdays)
- CommandTest changed output to RAW
- Fixed Fixture manager default behavior was not dropping tables between tests
- Fixed issue with console ProgressBar not being cleared
- Fixed Locale Generator prompting overwrite on files not just directory
- Fixed issue #55 - ConsoleApp Stop Execution Not Caught

## [1.19.0] - 2019-06-26
A small breakage for any code generated by the generator before this release.  Public setUp and public tearDown  were used instead of protected. Simply adjust those to protected setUp():void and protected tearDown():void
### Added
- travis ci caching of composer files and autodeploy on tagged releases

### Fixed
- Changed PHPUnit setUp / tearDown functions to protected as per the manual.

## [1.18.5] - 2019-06-26
### Added
- Database engines now have databases and tables

### Changed
- Updated travis CI settings
- Refactored DB command unit tests to work test on both mysql and pgsql
- Memcached/Redis unit tests now skip if incomplete settings are provided

## [1.18.4] - 2019-06-26
### Added
- Added Travis.yml

### Fixed
- Fixed issue with with view that plugin name was not being underscored
- Fixed DbSchemaDumpCommandTest to work with older versions of MYSQL
- Fixed ModelTest fail on older version of MySQL due to order
- Fixed Folder/file testing on different systems
- Fixed bug in testGenerateScaffold where it was using default datasource instead of test
- Fixed issue with Http test failing when running on different ip address

### Changed
- Memcached/Redis tests now use ENV vars for configuration
- Migration renameColumn now compatible with versions less than 8.0
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