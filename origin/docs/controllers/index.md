# Controllers

## App Controller

Your application controllers will extend the `AppController`.

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

### Controller::loadComponent(string $name, array $config = [])

You load components in your controller initialize method.

`$this->loadComponent('Auth')`

or you

`$this->loadComponent('Auth',['className'=>'CustomAuthComponent'])`


### Controller::loadHelper(string $name, array $config = [])

You load helpers in your controller initialize method.

`$this->loadHelper('Form')`

or you

`$this->loadComponent('Form',['className'=>'CustomFormHelper'])`
