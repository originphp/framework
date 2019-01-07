# Controllers

## App Controller

Your application controllers will extend the `AppController`.

## Creating your own controller

When you create a controller, the name should be in plural camel case and it needs to end with Controller. Controller files are placed in the `Controller` directory of your app.

````php

namespace App\Controller;

class BookmarksController extends AppController {

  public function view($recordId = null){

  }
}

````

You can access the model for the controller, by using `$this->Bookmark`. If you want to use a different models in your controller, then you will need to load each model that you want to use the `loadModel` method.

## Request

Request data can be accessed via the Request object. This is available in the controller.


```php
  class BookmarksController extends AppController {

    public function index(){
      $requestData = $this->request->data;
    }
  }

```

What you can access through 

- `params` This is an array of params for the request
  - `controller` this tells you the controller name, e.g. Bookmarks
  - `action` this is the action (method) which will be called. e.g. index
  - `pass` these are the passed arguments. so a request of */bookmarks/view/10* would result in an array of *[10]*
  - `named` this is an array of named params if they are passed, with the array index by key. e.g. */bookmarks/index/sort:any* would result in an array of *['sort'=>'any']*

  - `route` this is is the matched route for the current request, this can be useful in debugging situations. e.g. */:controller/:action/*.

  - `plugin` this is the plugin name, default is `null`. 
- `query` This is array of query parameters parsed from the query string, */bookmarks/index?sort=title* and will result in an array of  *['sort'=>'title']*
- `data` this is the post data

### Request Methods

#### Request::is(string|array $type)

You can check if a request of is a certain type `get`,`post`,`put` or`delete`

```php
  class BookmarksController extends AppController {

    public function index(){
      if($this->request->is(['post','put'])){
        ...
      }
    }
  }

```
#### Request::allowMethod(string|array $type)

You can also restrict methods to being run by using `allowMethod`. If the request type does not
match then an `MethodNotAllowedException` will be thrown.



```php
  class BookmarksController extends AppController {

    public function delete(){

      $this->request->allowMethod(['post', 'delete']);
      ...
    }
  }

```



Query paramaters 


## Callbacks 

There are three callbacks which Controllers use, `initialize`,`startup` and `shutdown`;

````php
    /**
     * This is called when controller is constructed
     */
    public function initialize(array $config){}

    /**
     * This called after initialize but before the controller action
     */
    public function startup(){}

    /**
     * This is called after the controller action.
     */
    public function shutdown(){}
````

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
