# Changelog
## [Unreleased]

## [2.0.0] - 2019-10-04

Releasing version two, see the [upgrade guide](https://www.originphp.com/docs/upgrade) for information on how to upgrade from previous versions.

Version 2
1. removes deprecated features and provides a more organized folder structure and makes it easier to work with given the number of folders that are now used.

2. Changed how callbacks work in Controllers and Models, so that can take advantage of PHP features.

I been working full time on the framework to get this where it is now, changes going forward from here should be slow, with a focus on improving code base, developing and testing with future PHP versions, bug and security fixes.

### Added

- Custom Entity classes, with mutators and accessors
- Security::random
- Security::uuid version 1 generation
- Added post install command
- Concerns

### Changed

- Change callbacks to require registering so can implement concerns properly.
- Update docs on tests (concerns have changed, remove behavirs),
- Remove behavior
- Added strict types
- Added return types
- Security::uid now returns a 15 character base 62 random string.
- Model callbacks have changed, now they need registering and arguments that will be passed have also been changed.
 See [callbacks](https://www.originphp.com/docs/model/callbacks/) for more details.
    Important: Model::afterFind now passes a collection for single or multiple results
- Controller callbacks are now `startup` and `shutdown` inline with framework. beforeRedirect and beforeRender are used to registerx callbacks.
- Folder structure (http,console and exception)
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

### Removed

- Text::random
- Helper functions, uid,left,right,contains and replace
- Mailer::$folder removed, this is now autodetected
- Behaviors

### Fixes

These fixes have also been fixed in the version 1.x branch

- Fixed XML serializer
- Fixed custom class namespace issues with belongsTo/hasAndBelongsToMany
- Fixed Number parse, now returns double or integer

### Security
- Changed ElasticSearchException from extending HttpException
