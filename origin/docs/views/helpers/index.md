# Helpers

You can create helpers, which are like components, and can contain logic for creating the views. For example we provide a Form helper, which makes working with forms very easy.


## Included Helpers

- [HtmlHelper](html-helper.md)
- [FormHelper](form-helper.md)
- [NumberHelper](number-helper.md)
- [DateHelper](date-helper.md)

## Create your owner helper

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

    public function bar($data)
    {
        return $this->doSomething($data);
    }

}
````

Remember, we use composer for auto-loading classes, anytime you create a new class ,you will need to run `composer dump-autoload`.

Once you have created the helper, the next thing to do is to load this in the controller, you can optionally pass an array of options.

```php
    class AppController extends Controller
    {
        public function initialize(array $config){
            parent::initialize($config);
            $fooOptions = ['key'=>$value];
            $this->loadHelper('Foo',$fooOptions);
        }
    }
```

Then from within the view


```php
    <h1>Hello world!</h1>
    <?= $this->Foo->doSomething($someData); ?>
```