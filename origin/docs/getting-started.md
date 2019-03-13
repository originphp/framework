# Getting Started

These instructions are for using the docker setup listed below. However if you don't want to use docker, then copy the files to your web server and skip the *Setting up the development server using Docker* section.

Whilst in alpha, I have included the Bookmark demo app in the src folder.

## Setting up the development server using Docker

Install [docker desktop](https://www.docker.com/products/docker-desktop), if you don't already have this.

1. Download the sourcecode from github.com into a folder called demo.

 `git clone https://github.com/originphp/originphp.git demo`

2. From within your project folder type `docker-compose build` - it will take a couple of minutes, and only needs to be done once unless you change the Docker configuration files.
3. Start the web server, type `docker-compose up` and go to [http://localhost:8000](http://localhost:8000)

## Configuring OriginPHP

1. Create the database using the statements in `config/database.sql`.

    * Using a Database Management App*
    The docker container MySQL server can be accessed using `localhost` and port `3306`, with any database management application. The username is `root` and the password is `root`. Mac users can use [Sequel Pro](https://www.sequelpro.com/) and Windows users can use [HeidiSql](https://www.heidisql.com/) both of those software are free.

    * Using MySQL client *
    To access the docker MySQL server using the MySQL client from within the docker container.
    - From the project folder type `docker-compose run app bash`, this will give you access to the docker container.
    - Then type `mysql -h db -uroot -p`
    - Paste the contents of the database.sql file and exit.

2. Create the database configuration file in the `config` folder , make a copy of the `database.php.default` file without the `default` extension, remember to set the username and password. 

    If you are using the docker setup then change the host to `db` (to access from within docker), username `origin` and set the password to `secret`.  This user was created in the database.sql.

    ````php 
        ConnectionManager::config('default', [
            'host' => 'db',
            'database' => 'origin',
            'username' => 'origin',
            'password' => 'secret'
        ]);
    ````
    When you go to [http://localhost:8000](http://localhost:8000) you should see a status page showing you that is connected to the database.

3. Create the tables and sample data using the statements in `config/schema/schema.sql`.  If you are not already in the container, from the project folder type in the following command to access the docker container `docker-compose run app bash`

    Then type in the following to import the `config/schema/schema.sql` file:

    `bin/console schema import`

## Bookmarks (Demo App)
To access the demo app, go to the login page, [http://localhost:8000/users/login](http://localhost:8000/users/login).
The login the username is `demo@example.com` and password is `origin`.

Once you have logged in you can try the bookmarks application.

There is also a Bookmarks shell app which demonstrates the console functionality, to run the Bookmarks shell. Run the following command to start it, and it will show you the available commands.

`bin/console bookmarks` 

One of the shell commands for bookmarks is uninstall, this will remove all demo files, this can be run by using the following command.

`bin/console bookmarks uninstall` 

## Super Quick Tutorial

What we are going to do is remove the sample files, and then use the Make plugin to generate the models,views and controllers to give you an idea how quick you can build apps.

Lets uninstall the bookmarks files (later on you can re-download these if you want)

`bin/console bookmarks uninstall` 

Now we are going to generate the code.

`bin/console make all` 

Thats it, you have now built your own bookmarks app using just the database. Ofcourse, our demo was slightly customised so would look different and have some different features. But that is the process you will normally start with when starting a new project, setting up the database and then generating the code.

If you go to [http://localhost:8000/bookmarks](http://localhost:8000/bookmarks) you will see some bookmarks, if you did not delete them.

Open one of the bookmarks, by clicking on one of the id numbers, this will take you to the view page.

You will see that there is a related list for tags, but nothing is showing. Open up the BookmarksController file in the src/Controller folder.

Edit the view action so you add the contain key to the options for get, and set the model name there. Now when you reload the page, it will load all associated Tags with this (These are defined in the Model itself). By default associated data is not fetched unless you tell it to do so.

````php
    public function view($id = null)
    {
        $bookmark = $this->Bookmark->get($id, [
            'with'=>['Tag']
            ]);
   
        $this->set('bookmark', $bookmark);
    }
````
Okay lets shut the container down, type in the following

`docker-compose down`

# Docker
My preferred method over the years has been using VirtualBox, with this the server setup/installs are done through a checklist, and this eliminates problems when running code on different servers such as development, staging and production. That said, I feel the way forward for a development setup is through docker and its disposable containers. I plan to include a server installation script for the staging and production servers on DigitalOcean or AWS, which has all the same extensions (except X-debug) as the docker development container.

The app is docker ready, just install [docker desktop](https://www.docker.com/products/docker-desktop) then go into the project directory and type `docker-compose build` this will build the docker container, you only need to do this once (and any time you make changes to the docker configuration files).

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

to access MySQL client from within bash. You must put db as host, as in the docker setup MySQL is a separate container.
`mysql -h db -uroot -p`

The MySQL password is set in the `docker-compose.yml` file.