# Readme 

This framework uses PHPUnit 7.x for unit testing, when you run the PHPUnit command it should be in the same folder where the `phpunit.xml` file is, which for the framework it is in `origin/tests`. The SRC folder for testing the framework is contained within TestApp, and therefore database configuration can be found there.

> Currently the tests for the framework need to be run in the Docker container as in some of the tests, paths were hardcoded such /var/www/somefile.txt

PHPUnit is installed in the Docker container, however if you want to benefit from code hinting then you should use the composer package.

```linux
$ composer require phpunit/phpunit ^7
```

> Running PHPUnit 7.5 with PHP 7.3 will cause segmentation fault 11 on MAC OS.

The frameworks' db config is in the TestApp folder as it is isolated.

To create the test database

```linux
$ bin/console db create origin_test -ds=test
```

When using composer create project, it does not download the framework tests, you must fetch these manually and then copy the tests folder.

```linux
$ git clone https://github.com/originphp/framework tmp
$ cp -r tmp/tests <folder>
```

In the tests/TestApp/config, the database settings will need to be configured there. There is also .env-template that needs to be filled for testing external services directly such SFTP. If you need the Docker configuration for these services send me an email, i will send you my setup.

To run all tests

```linux
$ phpunit
```

To run all tests in a folder. If you have not used linux before you will find the tab button makes things easier, for example, type in `phpunit T` and it will autocomplete the folder, rinse and repeat.

```linux
$ phpunit TestCase/Core
```

To run an individual test:

```linux
$ phpunit TestCase/Core/AutoloaderTest.php
```

PHPUnit code coverage generation requires xdebug to be installed, however xdebug causes
serious performance issues, so in the docker container whilst it is installed, it is not enabled by default.

To generate the code coverage you need to first enable xdebug in the container by editing the PHP.ini file. In docker these changes will not be kept once you restart.

```linux
$ echo 'zend_extension="/usr/lib/php/20170718/xdebug.so"' >> /etc/php/7.2/cli/php.ini
$ echo 'xdebug.default_enable=0' >> /etc/php/7.2/cli/php.ini
```

From the container and in the `origin/tests` folder run the following commands

```linux
$ phpunit --coverage-html /var/www/public/coverage
```

You can then access this by visiting [http://localhost:8000/coverage/index.html](http://localhost:8000/coverage/index.html).