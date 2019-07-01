# OriginPHP Framework

![license](https://img.shields.io/badge/license-MIT-brightGreen.svg)
![build](https://img.shields.io/badge/build-passing-brightGreen.svg)
![coverage](https://img.shields.io/badge/coverage-91%25-green.svg)
![memory](https://img.shields.io/badge/memory-850KB-brightGeen.svg)
![diskspace](https://img.shields.io/badge/disk_space-1.4MB-brightGreen.svg)
![rating](https://img.shields.io/badge/rating-awesome-blue.svg)

OriginPHP is a MVC web application framework for PHP developers designed to be fast, easy to use (and learn) and highly scalable. It is modeled upon CakePHP and Ruby On Rails (Which CakePHP was modeled upon). It comes with a Dockerized development environment.

## Features

- ORM database with support for MySQL and PostgreSQL.
- Caching that supports APCu, Redis, Memcache and File based cache
- Web Applications using MVC pattern
- Console Applications
- Middleware
- Migrations - update your database using migrations
- Code Generation and scaffolding
- Integration Testing for Web and Console Applications
- Queue System for background jobs
- Form helper
- Date,time,and number formating,validation and delocalization support
- Internationalization (I18n)
- Events system based upon the observer pattern
- Http utility for making get,post,patch,put and delete requests
- Yaml reading and writing
- CSV reading and writing
- XML reading and writing
- Storage system which supports local disk, FTP and SFTP
- Email using SMTP
- and much more

See the [documentation](https://www.originphp.com/docs/getting-started/) to find out more. If you want to help contribute make this even better then I would love to hear from you.

## Testing

Download the source code

```linux
$ git clone https://github.com/originphp/framework.git originphp
```

Run composer install

```linux
$ cd originphp
$ composer install
```

Rename the `phpunit.xml.dist` and edit the settings for the database connection and other services.

Create two databases `origin_test` and `origin` which are used by testing.

Then run PHPUnit

```linux
$ phpunit
```

You can send an email to <js@originphp.com>.

Jamiel Sharief