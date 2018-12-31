# OriginPHP Framework

The goals of this framework are

1) Be able to build applications rapidly and easily using MVC pattern.
2) Not have to learn a new language. 
    - Should be simple, easy and follow a logical pattern
    - Use a similar structure CakePHP, request, response, controller,models,views, helpers,components, behaviors, callbacks, testing etc.
3) High performance/low memory usage

## Why create another framework

I have used CakePHP for many years and could build apps fast - loved it. A few years ago I decided to write my own framework, so i built a private framework called nocarbs which was based upon the CakePHP 2.x manual and used the same structure. I immediately saw a performance increase in my apps, memory usage went down and I could build and scale quickly. 

Recently I looked at both Larvel and CakePHP 3.x. Larvel was like a new language which needed to be learned and I was immediately put off. CakePHP 3.x was incredibly complex and many changes from 2.x are significant , confusing and even unnecessary if building apps easily were to be a piece of cake. So I decided to build an opensource PHP framework which can fill the gap which CakePHP previously solved, build applications easily and fast using a MVC pattern, but without all the bloat and unnecessary overhead.

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

To access the demo app, go to the login page, [http://localhost:8000/login](http://localhost:8000/login).
The login the username is `demo@example.com` and password is `origin`.

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