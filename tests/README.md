# Readme 

> This is the old file for running tests from within the Docker image

This framework uses PHPUnit 8.0x for unit testing, when you run the PHPUnit command it should be run in the same folder where the `phpunit.xml` file is, which for the framework it is in `origin/tests`. The SRC folder for testing the framework is contained within TestApp, and therefore database configuration can be found there.

The frameworks' database config is in the TestApp folder as it is isolated.

To create the test database, origin_test

```php
$ bin/console db:create --connection=test
```

When using composer create project, it does not download the framework tests, you must fetch these manually and then copy the tests folder.

```linux
$ git clone https://github.com/originphp/framework tmp
$ cp -r tmp/tests <folder>
```

Then install dependencies

```php
$ composer install
```

In the tests/TestApp/config, the database settings will need to be configured there. There is also .env-template that needs to be filled for testing external services directly such SFTP. If you need the Docker configuration for these services send me an email, i will send you my setup.

You need to rename the `phpunit.xml.dist` to just `phpunit.xml`

To run all tests.

```linux
$ vendor/bin/phpunit
```

To run all tests in a folder. 

```linux
$ vendor/bin/phpunit
```

> If you have not used linux before you will find the tab button makes things easier, for example, type in `phpunit T` and it will autocomplete the folder, rinse and repeat.


To run an individual test:

```linux
$ vendor/bin/phpunit tests/TestCase/Core/AutoloaderTest.php
```

PHPUnit code coverage generation requires xdebug to be installed, however xdebug causes serious performance issues, so in the Docker container whilst it is installed, it is not enabled by default.

To generate the code coverage you need to first enable xdebug in the container by editing the PHP.ini file. In Docker these changes will not be kept once you restart the container.

```linux
$ echo 'zend_extension="/usr/lib/php/20170718/xdebug.so"' >> /etc/php/7.2/cli/php.ini
$ echo 'xdebug.default_enable=0' >> /etc/php/7.2/cli/php.ini
```

From the container and in the `framework` folder run the following commands

```linux
$ vendor/bin/phpunit --coverage-html /var/www/public/coverage
```

You can then access this by visiting [http://localhost:8000/coverage/index.html](http://localhost:8000/coverage/index.html).