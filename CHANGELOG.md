# Changelog

## [3.3.0] - 2020-08-15

### Added

- Added `Entity::isDirty` check if dirty
- Added `Entity::isClean` check if clean
- Added `Entity::wasChanged` to see if previous values were changed
- Added `Entity::changed` for getting changed values
- Added `Entity::dirty` for getting modified values
- Added `inline` option for `HtmlHelper::css`
- Added `inline` option for `HtmlHelper::js`
- Added `Service\Result::error`
- Added `config_path` helper function
- Added `Record` for working with data that is not persisted to the database and works with `FormHelper`

### Fixed

- Fixed entities marked as clean during the patch process
- Fixed `AuthComponent` now destroys all session data not just Auth related data
- Fixed `FormHelper` leaving empty label attribute when disabling label in control
- Fixed `FormHelper` leaving empty default attribute

### Deprecated

- Deprecated `Service` objects returning `null`, must always a return a `Result` object
- Deprecated `Entity::propertyExists`
- Deprecated `Entity::$_virtual` use `Entity::$virtual` instead
- Deprecated `Entity::$_hidden` use `Entity::$hidden` instead
- Deprecated `Entity::invalidate` use `Entity::error` instead
- Deprecated `present` options key, use the `present` validation rule instead

## [3.2.0] - 2020-07-26

### Added

- added `storage_path` function
- added `Mailer::delivered()`
- added `assertStringContains` to `OriginTestCase`
- added `assertStringNotContains` to `OriginTestCase`
- added `assertCookieNotSet` to `IntegrationTestTrait`
- added `assertFlashMessage` to `IntegrationTestTrait`
- added `assertSession` to `IntegrationTestTrait`
- added `assertSessionHasKey` to `IntegrationTestTrait`
- Added `JobTestTrait`

### Fixed

- Redis connection invalid password throwing error on travis due to recent changes in extension. ERR AUTH <password> called without any password configured for the default user. Are you sure your configuration is correct

### Changed

- Changed `MailerJob` execute params (this has backwards compatibility to prevent queued jobs from breaking)

## [3.1.1] - 2020-07-20

### Fixed

- Fixed redirect location to maintenance mode HTML page

## [3.1.0] - 2020-07-19

### Added

- Added `MaintenanceModeMiddleware`
- Added `tmp_path` helper function
- Added parsing command options with values enclosed in double quotes
- Added type `array` for Console options, allow to set multiple values
- Added `formGroup` template to `FormHelper`
- Added `formCheck` template to `FormHelper`

## [3.0.2] - 2020-07-17

### Added

- Added `Preloader`

### Fixed

- Fixed issue with nested quiet display in console output

## [3.0.1] - 2020-07-09

### Changed

- Changed PHPUnit minimum version to 9.2

## [3.0.0] - 2020-07-08

## Added

- Added improved console option parsing, you can now do `bin/console command --option value`

- Added `success` method to `Service\Result`.
- Added `data` method to `Service\Result`.

- Jobs
    - Added `onSuccess` callback registration (BC)
    - Added `onError` callback registration (BC)

- Mailbox
    - Added `onSuccess` callback registration
    - Added `onError` callback registration (BC)

- Model Added `onError` callback registration (BC)

## Changed

- Changed location of Framework `bootstrap.php` this has been moved to the `Core` directory (BC)
- Changed location of `BaseObject` this has been moved to the `Core` directory (BC)
- Changed default index and foreign key names when creating migrations to use `fk_` or `idx_` prefix
- Changed `View::element` to `View::renderShared`, this now renders a partial view from the shared folder, and will throw a `MissingSharedViewException`.
- Changed `Mail::attachments` property to private, use `Mail::attachments()` instead.
- Changed Mailbox `afterProcess` callback is called even if the mail bounces, use `onSuccess` instead if you only want to check non bounced messages

## Removed

- Removed autoloading from bootstrap (since 2.5)
- Removed `Model\Exception\NotFoundException`
- Removed `View\Exception\MissingElementException`
- Removed `View::view`

- Removed backwards compatibility , you need to adjust config file, and if you have used them elsewhere, then you will need
to adjust accordingly.
    - `config/app.php`: `debug` this should be `App.debug`
    - `config/app.php`: `Security.key` this should be`App.securityKey`
    - `config/app.php`: `Session.timeout` this should be `App.sessionTimeout`
    - `config/app.php`: `Mailbox.KeepEmails` this should be `App.mailboxKeepEmails`

- Removed backwards compatibility for model validation setting for individual rules
    - `allowBlank` - this was changed to `allowEmpty`
    - `required`: use `required` rule

- Removed `Model\ModelTrait`, this has been replaced with a `Core\ModelTrait`
- Removed `ORIGIN` constant

## [2.8.1] - 2020-07-03

### Fixed
- Fixed Config::write to use App.debug

### Deprecated

Added deprecation warnings for previously deprecated features which had backwards compatibility.

- Deprecated request::header() for getting items from header
- Deprecated model validation setting required
- Deprecated model validation setting allowEmpty
- Deprecated config settings: debug, Security.key, Session.timeout and Mailbox.KeepEmails

## [2.8.0] - 2020-06-26

## Changed

- `View::render` method has been changed for rendering partial views, previously it was used internally by the controller to render a view with a layout. If you have used this internal function then you will need change from `render` to `renderView`.

## Added

- Added `Number::readableSize`
- Added `Number::parseSize`

## [2.7.5] - 2020-06-19

### Fixed

- Fixed default fields on associations which were overwritten by associated fields

### Changed

- Changed associated fields options to not add prefixes to fields with spaces or an existing prefix.

## [2.7.4] - 2020-06-17

### Fixed

- Fixed General error: 1 no such table: sqlite_sequence when running tests and table does not exist

## [2.7.3] - 2020-06-14

### Added
- Added connection::transaction and refactored code to use this

## [2.7.2] - 2020-06-13

### Fixed
- Fixed various issues with Sqlite, testing and travis
- Fixed some committed debug code

## [2.7.1] - 2020-06-12

### Added
- Add console quiet mode

### Fixed
- Fixed issue with returning rollbacks by commands due to recent refactor

## [2.7.0] - 2020-06-12

### Added
- Added Sqlite engine
- Added reading database constraint actions 

### Changed
- Changed Fixture Manager to not try to load fixtures if not database settings are present
- Migration::addForeignKey, now accepts string as third argument for column name e.g. `$migration->addForeignKey('products', 'users', 'owner_id');`

### Fixed
- Fixed Form select control type detect with empty value

### Added

- Added HtmlHelper::tag for cleaner code with views

## [2.6.2] - 2020-05-30

### Fixed

- Fixed issue with `isUnique` caused by recent change to validation

### Added

- Added View::view method to be able to split large views into smaller ones, such as when using nav pills with javascript.
- Added negative number formatting to Number `Utility\Number`

### Changed

- Changed dotenv parsing to only cache when debug is disabled.

## [2.6.1] - 2020-05-20

### Fixed

- Fixed bug with new validation rules `required` and `optional`

## [2.6.0] - 2020-05-20

### Added

- Added `Config::consume`
- Added `stopOnFail` validation setting
- Added `notEmpty` validation rule (checks value and file uploads)
- Added special Validation rule `required`
- Added special Validation rule `optional`
- Added special Validation rule `present`
- FormHelper now accepts a `default` value when creating form control.

### Changed

- Changed .env.php is now the cached version of the parsed .env
- Changed validation settings key with full backwards compatibility
  - renamed validation setting key `required` to `present`
  - validation setting key `allowBlank` to `allowEmpty`
- Changed behavior of `required`, previously this was checking modified fields, to work with a certain form 
behavior, however this was incorrect, now it checks if key is present full stop.

### Notice

`Config::load` was developed but never implemented, however this has now been implemented in a different way and adjusted accordingly. The first argument no longer filename. If you have used this internal feature then it is a breaking change.

## [2.5.1] - 2020-05-12

### Fixed
- Fixed Number::parser returning 0 when non numeric strings were passed
- Fixed Model::new not using entity locator for custom entity classes
- Fixed FormHelper radio custom class

## [2.5.0] - 2020-05-02

### Added
- Added BatchInsertQuery for doing batch inserts

### Fixed 
- Fixed bug in MysqlSchema when mapping from generic float definition
- Fixed changelog date to show correct year :(

## [2.4.3] - 2020-02-21

cleaned up code, changed visibility on methods and properties, and fixed docblocks.

### Fixed

- Fixed Console command names validation to allow numbers e.g. oauth2 or oauth2:foo
- Fixed added Mail::attachments method (public property remains)

## [2.4.2] - 2020-01-19

### Changed

- Updated Copyright License Dates

## [2.4.1] - 2019-12-28

### Fixed

- Fixed Model after delete callback triggered when result is false when setting callbacks to only `after`
- Fixed Timestampable concern not adding created timestamp to associated record after failing validation.

## [2.4.0] - 2019-12-22

### Added

- Added Core/Exception/FileNotFoundExcepton
- Added Validation confirm rule

### Changed

- Changed validation to use `originphp/validation` package, this gives more validations but does not affect how its used, apart from deprecations listed below.

### Fixed

- Fixed error logging during early bootstrap stage, example parse error in application.php

### Deprecated

- Deprecated Validation rule `notEmpty` use `notBlank` instead
- Deprecated Validation rule `inList` use `in` instead.
- Deprecated Validation rule `custom` use `regex` instead.

## [2.3.2] - 2019-12-10

### Fixed

- Fixed validation equals to changed operator to compare value only (not datatype)
- Fixed form helper adding error class on associated objects with validation errors
- Fixed backtrace path
- Fixed marshaller patching detecting posted integer/null fields were treated as modified due to different
types, but were not.

## [2.3.1] - 2019-12-03

### Changed

- Changed Marshaller now uses primary key for patching of associated models, previously data was just overwritten.
- Changed Repositories load dependent model on creation instead of lazyloading.

### Fixed

- Fixed Entity naming issues on associated objects
- Fixed Connection logging unpreparing SQL statements showing null values and unpreparing 10+ values
- Fixed MySQL schema setting/getting null default to false for boolean
- Fixed FormHelper not parsing deep collection objects

## [2.3.0] - 2019-11-22

### Added

- Added Mailbox process incoming emails from pipe/imap or pop3 using controller like interface. This requires the php-mailparse. If you are using docker, you will need to add this to the Dockerfile.
- Added methods to Model: findBy, findAllBy, first, all, count, average, maximum, minimum.
- Added fluid query interface when using Model::select or Model::where
- Added Model find parameter `lock` for SELECT FOR UPDATE statements
- Added callbacks to Job beforeQueue,afterQueue, beforeDispatch, afterDispatch

### Fixed
- Fixed Controller callbacks disabling
- Fixed Console error render calls exit with exitcode 1

### Changed

- Changed Model now throws RecordNotFoundException future major release NotFoundException for Model will be deprecated

## [2.2.1] - 2019-11-11

### Changed
- improved IDS Middleware SQL injection attack rules.

### Fixed
- Fixed IDS Middleware issue with log filename on unix
- Fixed IDS Middleware performance rule 

## [2.2.0] - 2019-11-06

### Fixed
- Fixed issue with group reflection of tests in PHP 7.3 only

### Changed
- exceptions that extends HttpException will now show error message even if status code is 500 or above.

### Added

- PhpFile - class for reading/and writing arrays to files
- AccessLogMiddleware
- FirewallMiddleware
- IdsMiddleware
- ProfilerMiddleware
- ThrottleMiddleware

### Changed
- Controller::redirect adjusted and now no longer stops script execution, it lets the dispatcher handle this.

## [2.1.0] - 2019-11-01
### Added
- Added Cacheable concern for Models

## [2.0.1] - 2019-10-30
### Fixed
- OriginTestCase changed to Abstract class which was causing unexpected issues with PHPUnit
- Fixed CSRF token now rewrites cookie with each GET request
- Fixed loadMiddleware accepts options
- Paginator behavior with pages that are out of bounds throws page not found instead of ignoring

## [2.0.0] - 2019-10-25

Releasing version 2.0, see the [upgrade guide](https://www.originphp.com/docs/upgrade) for information on how to upgrade from previous versions.

There is an [upgrade tool](https://github.com/originphp/upgrade) which can handle moving and renaming and alerting to items that might need changing.

### Version 2

Summary of what has changed:

1. Removed deprecated features and no longer needed or duplicate features

2. Changed application folder a structure to a more organized way which makes it easier to work with given the number of folders that are now used. This affects Controllers, Middleware, Views and Mailers. see the [upgrade guide](http://localhost:8080/docs/upgrade) for more information on the folder structure.

3. Changed how some features work
    - changed how callbacks work in Controllers and Models, callbacks are now registered.
    - changed Middleware callbacks and design
    - Migrations store version in db as biginteger

4. Added strict types,return types any public properties have been changed to protected

5. Added some new features like custom Entity classes with mutators and accessors

6. Deocupled the framework into separate packages. The rest of the framework, Console/Http/Model/Mailer/Job etc stays as one but maybe in future will have split readonly repos using /splitsh.

7. Changed the bootstrap process, this means less magic under the hood and more flexability. For example, plugins can now be standlone applications.

I have been working full time on the framework to get this where it is now, changes going forward from here should be slow, with a focus on improving code base, developing and testing with future PHP versions, bug and security fixes.

### Added

- Custom Entity classes, with mutators and accessors
- Security::random and other random string generation based upon hex,base62,base36,base58,base64
- Security::uuid version 1 generation
- Concerns
- Folder::list now works recursively
- QueryObject

### Changed

- Change callbacks to require registering so can implement concerns properly.
- Added strict types
- Added return types
- Changed public properties to protected
- Security::uid now returns a 16 character base 62 random string.
- Model callbacks have changed, now they need registering and arguments that will be passed have also been changed.
 See [callbacks](https://www.originphp.com/docs/model/callbacks/) for more details.
    Important: Model::afterFind now passes a collection for single or multiple results
- Controller callbacks are now `startup` and `shutdown` inline with framework. beforeRedirect and beforeRender are used to register callbacks.
- Folder structure has change for both App and Framework
    - `App\Command` changed to `App\Console\Command`
    - `App\Controller` change to `App\Http\View`
    - `App\View` changed to `App\Http\View`
    - `App\Middleware` changed to `App\Http\Middleware`
    - `Origin\Command` change to `Origin\Console\Command`
    - `Origin\Controller` change to `Origin\Http\View`
    - `Origin\View` change to `Origin\Http\View`
    - `Origin\Http\Middleware` class changed to `Origin\Http\Middleware\Middleware`
    
- Mailer templates folder and filename structure
- Error triggered in Jobs are now logged to help with debugging
- Model::$datasource changed Model::$connection
- Unit testing now uses PHPUnit 8.x
- Migrations now expect version field to be BIGINT format
- Cookie writing, 3rd paramater is array and options array takes `expires` key
- Security::decrypt returns string or null
- Middleware aliases startup and shutdown are now callbacks. Use invoke and process instead.
- SimpleObject class renamed to BaseObject
- Log engine, log method changed to return void
- Concerns - these were silently added in the last major releases but were not documented, these have been rewritten to use `traits` instead.
- Datasource class renamed to Connection
- Locale files are now PHP changed from YAML
- File::info renamed filename key to name


** Utilities **

- Collection is now a composer package and under a different namespace `Origin\Collection`
- CSV is now a composer package and under a different namespace `Origin\Csv`
- DOM is now a composer package and under a different namespace `Origin\Dom`
- Email is now a composer package and under a different namespace `Origin\Email`. The email utility has completely changed, it is now purely for constructing and sending messages by SMTP. The default message type is now text, and template features have been removed. Note. This does not affect mailers, which use this as the backend.
- File and Folder are now in a composer package (originphp/filesystem) and under a different namespace `Origin\Filesystem`
- Yaml is now a composer package and under a different namespace `Origin\Yaml`
- Html is now a composer package and under a different namespace `Origin\Html`
- Text is now a composer package and under a different namespace `Origin\Text`
- Markdown is now a composer package and under a different namespace `Origin\Markdown`
- Inflector is now a composer package and under a different namespace `Origin\Inflector`
- Security is now a composer package and under a different namespace `Origin\Security`
- Http is now a composer package and under a different namespace `Origin\HttpClient` (originphp/http-client). Note Response object for this namespace has changed to the same namespace, this was previously in a sub namespace Http.

** Libraries **

- Log is now a composer package
- Storage is now a composer package
- Cache is now a composer package
- Elasticsearch is now a composer package under a different namespace `Elasticsearch`

- Changed Bootstrap process.  ConsoleApplications need to load config/bootstrap, previously this
loaded the framework bootstrap file.
- Console Commands return exit codes, added assertion for this
- Origin\Exception\Exception and Origin\Exception\CoreException are now in Origin\Core\Exception

- Entity now implements ArrayAccess and JsonSerializable
- Collection now implement JsonSerializable

### Removed

- Removed Text::random method (you can use Security::base62 for similar)
- Removed Helper functions: uuid,left,right,contains and replace
- Mailer::$folder removed, this is now autodetected
- Removed Behaviors
- Removed Inflector::add method (use Inflector::rules which now accepts strings)

### Fixes

These fixes have also been fixed in the version 1.x branch

- Fixed bug with paths for plugins installed using composer
- Fixed bug in Markdown::toHtml when parsing multiple ` tags in the same line
- Fixed XML serializer
- Fixed custom class namespace issues with belongsTo/hasAndBelongsToMany
- Fixed Number parse, now returns double or integer

### Security

- Changed ElasticSearchException from extending HttpException
