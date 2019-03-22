# Helpers

You can create helpers, which are like components, and can contain logic for creating the views. For example we provide a Form helper, which makes working with forms very easy.

## Included Helpers

- Html Helper - some nifty functions for working html
- Flash Helper - For displaying messages sent using the Flash Component
- Paginator Helper - This works with the Pagination Component
- Form Helper - Form magic at its best
- Number Helper - Number formatting functions
- Date Helper - Date formatting functions
- Cookie Helper - Working with that cookie jar
- Session Helper - Access the sesion data from views

## Creating Helpers

Helpers are stored in the `View/Helper` folder of your app. Here is an example of a simple helper:

````php
namespace App\View\Helper;

use Origin\View\Helper\Helper;

class FooHelper extends Helper
{
    /**
     * This is called when the Helper is created. You can put any logic here
     * instead of overiding the construct.
     */
    public function initialize(array $config){
        ...
    }

    public function doSomething($data)
    {
        return 'bar';
    }

}
````

## Loading Helpers

Once you have created the Helper, the next thing to do is to load this in the controller, you can optionally pass an array of options.

```php
    class AppController extends Controller
    {
        public function initialize(){
            $this->loadHelper('Foo',['setting'=>'on']);
        }
    }
```

## Using Helpers

Then you can access the Helper from the view

```php
    <h1>Hello world!</h1>
    <?= $this->Foo->doSomething($someData); ?> // outputs bar
```

You can also load Helpers within helpers, in your helper `initialize` method, call the `loadHelper` method. Then you access another helper from within your helper.

```php
public function initialize(array $config){
    $this->loadHelper('Session');
}
public function magic(){
    $secret = $this->Session->read('Magic.tricks.rabbit');
}
```

## Initialize

When a Helper is created, the `initialize` method will be called, this is where you can put any code
that you need to be executed when a Helper is created. This is a hook so you don't need to override the `___construct()`.

## Callbacks

There are two callbacks which Helpers use `startup` and `shutdown`. To use the callbacks, just create a method in your Helper with the callback name.

## Accessing the request object

If you need to access the request object from within the Helper.

```php
$request = $this->request();
```

## Accessing the response object

If you need to access the response object from within the Helper.

```php
$response = $this->response();
```

## Accessing the View

When working with Helpers, you may need to access the view object, this can be easily done by calling the view method.

```php
$view = $this->view();
```


## Config

Helpers work with the `ConfigTrait`, so this means you that your helper can have its own standardized configuration.

To get a value from the config:

```php
 $value = $this->config('foo'); // bar
```

To all of the values from the config

```php
 $array = $this->config();
```

To set a value in the config:

```php
 $this->config('foo','bar');
 $this->config(['foo'=>'bar']);
```

To set multiple values (not replace the array with the one passed)

```php
 $this->config(['foo'=>'bar']);
```

If you need your helper to have a default configuration, then you can set the `$defaultConfig` array property, this will be merged with any config passed when loading a helper.

```php
class FooHelper extends Helper
{
    protected $defaultConfig = [
        'foo' => 'bar'
    ];
}
```