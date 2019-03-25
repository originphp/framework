# Plugins 

You can also create Plugins which are a combinations of controllers,models,views etc and then you can use between apps by copying the plugin folder.

The first step to creating a plugin is to create the folders.

Lets say you want to create a contact manager plugin, you create the folder structure. It uses the same structure as the src folder, you can add what you need.

```
.
|-- plugins
|   |-- contact_manager
|   |   -- config
|   |   -- src
|   |       |-- Controller
|   |       |-- Model
|   |       |-- View
|   |   -- tests
```

You can use the generate plugin to create the folder structure, routes and app controller and model.

`bin/console generate plugin ContactManager` 

Then in your `config/bootstrap.php` add:

`Plugin::load('ContactManager');` 

The plugin name in load should be `CamelCase`, but the folder should be `underscored`.

If you did not use the generate plugin then follow these steps.

## Setup Routing

Then you will need to setup the routing (if you are going to use). In your plugin folder create `config/routes.php` and add:

```php
<?php 
use Origin\Core\Router;
Router::add('/contact_manager/:controller/:action/*', ['plugin'=>'ContactManager']);
```

## Create AppController

Create `ContactManagerAppController.php` in the `plugins/contact_manager/src/Controller` folder.

```php
<?php 
namespace ContactManager\Controller;

use App\Controller\AppController;

class ContactManagerAppController extends AppController
{
}
```

## Create AppModel

Create `ContactManagerAppModel.php` in the `plugins/contact_manager/src/Model` folder.

```php
<?php 
namespace ContactManager\Model;

use App\Model\AppModel;

class ContactManagerAppModel extends AppModel
{
}

```

## Loading models

From within the controller you use the loadModel method with plugin syntax. The loadModel both returns the model
and sets it up as property.

```php

    $this->loadModel('ContactManger.Contact');

    $results = $this->Contact->find('all');

```
