# Getting Started with OriginPHP

## What is OriginPHP?

OriginPHP is a web application framework written in PHP that uses a number of well known software design patterns, including convention over configuration, MVC (Model View Controller), association data mapping, and front controller.

## Installation

Download the source code from [Git Hub](https://github.com/originphp/originphp) and extract this into a new folder, call it bookmarks.

If you have GIT installed, then you can run the following command from your source code folder to download the source into a folder called blog.

```bash
$ git clone https://github.com/originphp/originphp.git blog
```

Download and install [Docker](https://www.docker.com/products/docker-desktop) and let this rock your world. Your app will be built within a docker container, which you can start and shutdown as needed. The Docker container is only intended for development, and it will act very similar to a real server.

The first time use the docker container, you will need to build this, which might take a few minutes.

```bash
$ cd bookmarks
$ docker-compose build
```

Once it has finished building start the development server by typing in the following:

```bash
$ docker-compose up
```

Then open your web browser and go to [http://localhost:8000](http://localhost:8000)  which will show you a status page that all is working okay.

Lets create the database on the server, from the command line type in the following to access the container and MySQL client.

```bash
$ docker-compose run app bash
$ mysql -uroot -p
```

When it asks you for the password type in **root**, then copy and paste the following sql to create the database called bookmarks and a user called origin with the password **secret**.

```sql
CREATE DATABASE bookmarks CHARACTER SET utf8mb4;
GRANT ALL ON bookmarks.* TO 'origin' IDENTIFIED BY 'secret';
FLUSH PRIVILEGES;
```

NOTE: You can also acces the MySql server using any database management application using `localhost` port `3306`. Windows users can use [Sequel Pro](https://www.sequelpro.com/) or Mac users can use [Heidi SQL](https://www.heidisql.com/).

Open the `database.php.default` in your IDE, I recommend [Visual Studio Code](https://code.visualstudio.com/). Set the host, database, username and password as follows and then save a copy as `database.php`.

```php
ConnectionManager::config('default', [
    'host' => 'db', // Docker MySQL container
    'database' => 'bookmarks',
    'username' => 'origin',
    'password' => 'secret'
]);
```
NOTE: To access the MySQL server from within the Docker container, we need to use its name which is `db` and not `localhost`.

If all went well when you go to [http://localhost:8000](http://localhost:8000)  it should now say that it is connected to the database.

Finally, we need to import the tables, this information is in a file called `schema.sql` located in the `config` folder. From within the Docker container type in the following.

```bash
$ bin/console schema import
```

Now that this has been done  goto [http://localhost:8000/users/login](http://localhost:8000/users/login) use the username `demo@example.com` and password `origin` to login.

The bookmarks app also has its own console application, which shows you some features of the CLI.

Run the following command to show the available options, one of those is uninstall which you can use later to remove all the Bookmarks files. First you should look around the source and get a feel for everything.

```bash
$ bin/console bookmarks
```

To uninstall the bookmarks files

```bash
$ bin/console bookmarks uninstall
```

Once you have uninstalled, you can give the code generation a go. This will generate code using the database. You can easily customise the templates, the templates can be found in `plugins/generate/src/Template`. 

To see the command line options:
```bash
$ bin/console generate
```
To generate code using the database

```bash
$ bin/console generate all
```

Now go to [http://localhost:8000/bookmarks](http://localhost:8000/bookmarks) to see the code in action.

See the other guides for more information.