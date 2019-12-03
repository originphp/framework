# Changelog
## [2.3.1] - 2019-12-03

### Changed
- Marhsaller now uses primary key for patching of associated models, previously data was just overwritten.
- Repositories load dependent model on creation instead of lazyloading.

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
- Controller callbacks disabling
- Console error render calls exit with exitcode 1

### Changed

- Model now throws RecordNotFoundException future major release NotFoundException for Model will be deprecated

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
