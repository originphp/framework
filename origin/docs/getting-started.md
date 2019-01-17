# Getting Started

These instructions are for using the docker setup listed below. However if you don't want to use docker, then copy the files to your web server and skip the *Setting up the development server using Docker* section.

Whilst in alpha, I have included the Bookmark demo app in the src folder.

## Setting up the development server using Docker

Install [docker desktop](https://www.docker.com/products/docker-desktop), if you don't already have this.

1. Unzip the OriginPHP archive into a new project folder, e.g `origin-demo`
2. From within your project folder type `docker-compose build` - it will take a couple of minutes.
3. Start the web server, type `docker-compose up` and go to [http://localhost:8000](http://localhost:8000)

## Configuring OriginPHP

1. Run the sql statements `config/database.sql` to create the database and `config/schema.sql` to create the tables and sample data.

    The docker container MySql server can be accessed using `localhost` and port `3306`, with any database management application. The username is `root` and the password is `root`. Mac users can use [Sequel Pro](https://www.sequelpro.com/) and Windows users can use [HeidiSql](https://www.heidisql.com/).

    To access the docker MySql server using the MySql client from within the docker container.
    - From the project folder type `docker-compose run app bash`, this will give you access to the docker container.
    - Then type `mysql -h mysql -uroot -p`

2. Create the database configuration file, make a copy of the `database.php.default` file without the `default` extension. And set the username and password. 

    If you are using the docker setup then change the host to `db` (to access from within docker), username `origin` and set the password to `secret`.  This user was created in the database.sql.

    ````php 
        ConnectionManager::config('default', [
            'host' => 'db',
            'database' => 'origin',
            'username' => 'origin',
            'password' => 'secret'
        ]);
    ````

When you go to [http://localhost:8000](http://localhost:8000) you should see a status page showing you that everything is working.

## Bookmarks (Demo App)
To access the demo app, go to the login page, [http://localhost:8000/login](http://localhost:8000/login).
The login the username is `demo@example.com` and password is `origin`.

There is also a Bookmarks shell app which demonstrates the console functionality, to run the Bookmarks shell. Run the following command to start it, and it will show you the available commands.

`bin/console bookmarks` 

One of the shell commands for bookmarks is delete, this will remove all demo files, this can be run by using the following command.

`bin/console bookmarks delete` 

# Docker
My preferred method over the years has been using VirtualBox, with this the server setup/installs are done through a checklist, and this eliminates problems when running code on different servers such as development, staging and production. That said, I feel the way forward for a development setup is through docker and its disposable containers. I plan to include a server installation script for the staging and production servers on DigitalOcean or AWS, which has the same setup as the docker development container.

The app is docker ready, just install [docker desktop](https://www.docker.com/products/docker-desktop) then go into the project directory and
type `docker-compose build` this will build the docker container, you only need to do this once (and any time you make changes to the docker configuration files).

Then each time you want to work on your project you start it by running the 
`docker-compose up` from within the project directory. You will then be able to access
your project at *http://localhost:8000*, you can change the port by editing the `docker-compose.yml` and then rebuild the container.

To shutdown the container (important)
`docker-compose down`

To run commands within the container
`docker-compose run app *command*`

to access the bash terminal
`docker-compose run app bash`

Remember, if you wish to make changes to docker container that persist, you will need to adjust the docker files. An example of this would be adding a php extension which is not included. A full list can be found by examining the Dockerfile.

to access MySql client from within bash. You must put db as host, as in the docker setup MySql is a separate container.
`mysql -h db -uroot -p`

The MySql password is set in the `docker-compose.yml` file.