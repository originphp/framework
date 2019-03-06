# Readme 

This framework uses PHPUnit for unit testing, when you run the PHPUnit command it should be in the same folder where the `phpunit.xml` file is, which for the framework it is in `origin/tests`. The SRC folder for testing the framework is contained within TestApp, and therefore database configuration can be found there.

To run all tests

`phpunit`

To run all tests in a folder. If you have not used linux before you will find the tab button makes things easier, for example, type in `phpunit T` and it will autocomplete the folder, rinse and repeat.

`phpunit TestCase/Core`

To run an individual test:

`phpunit TestCase/Core/AutoloaderTest.php`


PHPUnit code coverage generation requires xdebug to be installed, however xdebug causes
serious performance issues, so in the docker container whilst it is installed, it is not enabled by default.

To generate the code coverage you need to first enable xdebug in the container by editing the PHP.ini file. In docker these changes will not be kept once you restart.

````
echo 'zend_extension="/usr/lib/php/20170718/xdebug.so"' >> /etc/php/7.2/cli/php.ini
echo 'xdebug.default_enable=0' >> /etc/php/7.2/cli/php.ini
````

From the container and in the `origin/tests` folder run the following commands

`phpunit --coverage-html /var/www/webroot/coverage`

You can then access this by visiting [http://localhost:8000/coverage](http://localhost:8000/coverage).