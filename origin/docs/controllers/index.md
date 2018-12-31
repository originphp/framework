# Controllers

## App Controller

Your application controllers will extend the `AppController`.


## Creating your own controller

Remember, we use composer for auto-loading classes, anytime you create a new class ,you will need to run `composer dump-autoload`.

When you create a controller, the name should be in plural camel case and it needs to end with Controller. Controller files are placed
in the `Controller` directory of your app.

````php

namespace App\Controller;

class BookmarksController extends AppController {

  public function view($recordId = null){

  }
}

````

You can access the model for the controller, by using `$this->Bookmark`. If you want to use a different models in your controller, then you will
need to load each model that you want to use with the `$this->loadModel($model)` method.

## Methods

### Controller::redirect(mixed $url)

To redirect to a different url use the redirect method. You can pass either a string or an array.

`$this->redirect('/thanks');`

`$this->redirect('https://www.wikipedia.org');`

To use an array simply

````php

$this->redirect([
  'controller' => 'users',
  'action' => 'view',
  1024
]);

````
To use a query string, pass a `?` key with an array.

`'?' => ['sort'=>'ASC','page'=>1]`

You can also use `#` to scroll to a part of the page.

e.g `'#'=>'bottom'`

You can also set named parameters.


````php

$this->redirect([
  'controller' => 'orders',
  'action' => 'confirmation',
  'product' => 'ebook',
  'quantity' => 5
]);

````
which will generate a URL like

`/orders/confirmation/product:ebook/quantity:5`

### Controller::set(string $var, mixed $value)

You can send data to the views by using the `set`.

`$this->set('user',$this->User->find('first'))`

### Controller::loadModel(string $name, array $config = [])

You can load any model from within the controller by using the load model method.

`$this->loadModel('User')`

The model will then be available as `$this->User` from within the controller, it also returns the model, if you want to access it without
the this.

You can also pass an array of options.

`$this->loadModel('Member',['className'=>'User'])`

### Controller::loadComponent(string $name, array $config = [])

You load components in your controller initialize method.

`$this->loadComponent('Auth')`

or you can pass an options array

`$this->loadComponent('Auth',['className'=>'CustomAuthComponent'])`


### Controller::loadHelper(string $name, array $config = [])

You load helpers in your controller initialize method.

`$this->loadHelper('Form')`

or you can pass an options array

`$this->loadComponent('Form',['className'=>'CustomFormHelper'])`
