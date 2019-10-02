# Changelog
## [Unreleased]

## [2.0.0] - 2019-10-04

Releasing version two, see the [upgrade guide](https://www.originphp.com/docs/upgrade) for information on how to upgrade from previous versions.

Version 2 removes deprecated features and provides a more organized folder structure and makes it easier to work with given the number of folders that are now used. I been working full time on the framework to get this where it is now, changes going forward from here should be slow, with a focus on improving code base, developing and testing with future PHP versions, bug and security fixes.

### Added

- Custom Entity classes, with mutators and accessors
- Security::random
- Security::uuid version 1 generation
- Added post install command

### Changed

- Added strict types
- Added return types
- Security::uid now returns a 15 character base 62 random string.
- Model callbacks arguments have changed. See [callbacks](https://www.originphp.com/docs/model/callbacks/) for more details.
- Model::afterFind now passes a collection for single or multiple results
- Model and Controller callbacks, are only called if methods are defined.
- Controller callbacks are now beforeAction and afterAction
- Folder structure (http,console and exception)
- Mailer templates folder and filename structure
- Error triggered in Jobs are now logged to help with debugging
- Model::$datasource changed Model::$connection
- Unit testing now uses PHPUnit 8.x
- Migrations now expect version to be BIGINT format
- Cookie writing, 3rd paramater is array and options array takes `expires` key

### Removed

- Text::random
- Helper functions, uid,left,right,contains and replace
- Mailer::$folder removed, this is now autodetected

### Fixes

These fixes have also been fixed in the version 1.x branch

- Fixed XML serializer
- Fixed custom class namespace issues with belongsTo/hasAndBelongsToMany
- Fixed Number parse, now returns double or integer

### Security
- Changed ElasticSearchException from extending HttpException
