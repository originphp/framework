# Getting started on a new project

## Download the Source Code

From the directory where you store your source code run the following command. This will download the source code into the `project` folder, you can change this to the name of your project.

`git clone https://github.com/originphp/originphp.git project`

Then delete the .git information folder in the project folder.

## Build the docker container

The first time you download you will need to build the docker contianer, it might take a few minutes. You will need to have [docker desktop](https://www.docker.com/products/docker-desktop) installed.

````
cd project
docker-compose build
````

To start the docker container type the following:

`docker-compose up`

Then go to [http://localhost:8000](http://localhost:8000).

## Database

### Create the database

To create the database we will access the docker container, from the project folder type:

`docker-compose run app bash`

This will run bash in the docker container, which is called app.

We are going to access MySQL as root (this is configured in the docker-compose.yml file)

`mysql -h db -uroot -p`

And then type in the password `root`.

Paste the following code to create two databases, one for the application and one for testing.

````sql
CREATE DATABASE project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE project_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL ON project.* TO 'project' IDENTIFIED BY 'secret';
GRANT ALL ON project_test.* TO 'project' IDENTIFIED BY 'secret';

FLUSH PRIVILEGES;
````

Then `exit` MySQL. You should keep a copy of this in the `config/database.sql`.

### Configure the database

Copy the `config/database.php.default` and save as `config/database.php`

````php

    ConnectionManager::config('default',array(
    'host' => 'db',
    'database' => 'project',
    'username' => 'project',
    'password' => 'secret'
    ));

    ConnectionManager::config('test',array(
    'host' => 'db',
    'database' => 'project_test',
    'username' => 'project',
    'password' => 'secret'
    ));

````

Once you have done this go back to [http://localhost:8000](http://localhost:8000) and check the connected to database
status is now green.

### Import the schema

Before working on your app you will want to have the database schema setup. Paste your schema in `config/schema.sql`

From within your project directory run the following command

`bin/console schema import`

This has now imported your SQL file.

## Code Generation

Now that your database is configured and setup, the next step is to generate the code. 

This is done with a one liner.

`bin/console make all`

You can edit the templates that used to generate the code which are stored in the `plugins\make\src\Template`. These are single files which are simple to understand, see [code generation](code-generation.md) for more information. In most cases you will want to rearrange the templates and classes or divs etc.

````html
    <div class="page-header">
        <div class="float-right">
            <a href="/%controllerUnderscored%" class="btn btn-secondary" role="button"><?php echo __('Back');?></a>
        </div>
        <h2><?php echo __('Add %singularHuman%'); ?></h2>
    </div>
    <div class="%pluralName% form">
        <?= $this->Form->create($%singularName%); ?>
            <?php
                <RECORDBLOCK>
                echo $this->Form->control('%field%');
                </RECORDBLOCK>
            ?>
        <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary']); ?>
        <?= $this->Form->end(); ?>
    </div>
````