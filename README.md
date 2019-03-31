# OriginPHP (Beta)

OriginPHP is a MVC web application framework for PHP developers designed to be fast, easy to use (and learn) and scalable.

### Features

- High Performance and low memory usage: The bookmarks demo with multiple database associations and callbacks uses about 800k without debug. The framework itself is 1.4mb without git data and frameworks unit tests.
- MVC (Model View Controller) design pattern.
- Built development server (using docker) - Highly Recommended!!! See the [getting started guide](docs/getting-started.md).
- Console Apps: build console apps
- Autoloading with Composer full integrated.
- Easy to use Routing
- ORM: Works with MySQL and has easy to use finders.
- Cache:  supports file, APC, Memcached, Reddis out of the box.
- Queue: Speed up processing time by running background jobs without having to install addtional servers such as beanstalk.
- Helpers: View helpers include forms, date,number with an easy to use localization. If you want to use the Intl extention, there is a helper for that too.
- Integration Testing - Test your controllers and console apps with minimal lines of code.
- Middleware: An easy to understand and use middleware system for those who must have it.
- Code Generation: Generate your apps using existing database schema, effortlessly adjust the templates before generating them and bang! Its how you want it.
- Plugins: build resuable apps within apps, called plugins.
- Render JSON and XML views with no extra effort or files!
- Includes annotations to help your IDE with code completion (and guide you to what things do). If you are not using [Visual Studio Code](https://code.visualstudio.com/) then i recommend you give it a go.
- and much much more

See the [getting started guide](docs/getting-started.md) or the framework [documentation](docs/) to find out more. If you want to help contribute make this even better then I would love to hear from you.

- [Model Guide](models.md)
- [View Guide](views.md)
- [Controller Guide](controllers.md)

## Acknowledgements

This library is heavily inspired by CakePHP 2.x and CakePHP 3.x. (You might even call it CakePHP 5.0)

Jamiel Sharief