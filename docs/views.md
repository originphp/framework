# Views

The V in the Model View Controller (MVC). The controller has dealt with the request and probably used a model to generate
data which can be sent to the view, then the view will now display this to user.

## Rendering and Layouts

This framework favors  'convention over configuration' and views are a good example of this, if a user requests `/articles/latest`, the articles controller will be loaded and the view called latest will be rendered. You pass data to the view from the controller using the `set` method.

```php
class ArticlesController extends AppController
{
    public function latest(){
        $articles = $this->Article->find('all',['order'=>'created DESC','limit' =>5]);
        $this->set('articles',$articles);
    }
}
```

The file for the view is `src/View/Articles/latest.ctp` and might look something like this:

```php
<h1>Latest</h1>
<ul>
<?php
    foreach($articles as $article){
        ?>
            <li><?= $article->title ?></li>
        <?php
    }
?>
</ul>
```

That view will be rendered inside a `layout`, the framework comes with two starter layouts `default` and `basic` which can be found in the `src/View/Layout` folder.

```html
<!doctype html>
<html lang="en">
  <head>
    <title><?= $this->title(); ?></title>
    <link rel="stylesheet" href="/css/default.css">
  </head>
  <body>

    <div class="container">
      <?= $this->Flash->messages() ?>
      <?= $this->content() ?>
    </div>
  </body>
</html>
```

To render a different layout change the name of the `layout` property in the controller, or if you do not want to use a layout set the property to `false`.

```php
class ArticlesController extends AppController
{
   public $layout = 'default';
}
```

You can also access the `request` and `response` objects from within your views.

Views are rendered automatically unless you set the `autoRender` property in the controller to false.

## Rendering a different view

If you want to render a different view call the render function with the name of the view that you want to render.

```php
class ArticlesController extends AppController
{
    public function something_else(){
        $articles = $this->Article->find('all',['order'=>'created DESC','limit' =>5]);
        $this->set('articles',$articles);
        $this->render('latest');
    }
}
```

You can also render views in a different directory, you just have to start the name with a forward slash.

```php
$this->render('/Bookmarks/latest');
```

For example, if you called render from the articles controller, `latest` would be the same as `/Articles/latest`.

To render views from `Plugins`, you can use dot notation , followed by the view folder (controller name) and then the action.

```php
$this->render('MyPlugin.Contacts/index');
```

## Rendering Options

The normal views are typically used for HTML but you can use how you see fit.

### Rendering XML

You can render XML setting the xml option and either pass

- string: An existing xml string that you have loaded or generated from elsewhere.
- array: An will be converted to xml using the Xml utility.
- result: you can return a result or set of results and these be be converted to xml.

```php
class ArticlesController extends AppController
{
    public function latest(){
        $articles = $this->Article->find('all',['order'=>'created DESC','limit' =>5]);
        $this->render(['xml'=>$articles]);
    }
}
```

### Rendering JSON

You can render JSON by passing an array with the key json, the value can be either:

- result: you can return a result or set of results and these be be converted to xml.
- anything else: any value that you pass will go through the `json_encode` function.

```php
class ArticlesController extends AppController
{
    public function latest(){
        $articles = $this->Article->find('all',['order'=>'created DESC','limit' =>5]);
        $this->render(['json'=>$articles]);
    }
}
```

### Rendering Text

Sometimes you need to just render a text response, maybe for an ajax or web service request.

```php
$this->render(['text'=>'OK']);
```

### Rendering an external file

If you need to load an external file you can pass the file option, this is different from a view, since it just loads the contents using `file_get_contents`.

```php
$this->render(['file'=>'/var/www/tmp/prerenderedfile.html']);
```

### Setting Status Codes

You can change the status code sent to browser, for example 404. By setting this on the `response` object or by passing a `status` option.

Both ways are demonstrated here.

```php
class ArticlesController extends AppController
{
    public function view($id = null)
    {
        $json = ['errors'=>['message' =>'Not Found']];
        $this->render(['json'=>$json,'status'=>404]);
    }
    public function anotherWay(){
         $this->render(['status'=>404]);
    }
     public function latest(){
        $this->response->status(404);
    }
}
```

There are quite a lot of status codes, but APIs only use a small subset, here are probably the main ones.

| Code      | Definition                                              |
| ----------|---------------------------------------------------------|
| 200       | OK (Success)                                            |
| 400       | Bad Request (Failure - client side problem)             |
| 500       | Internal Error (Failure - server side problem)          |
| 401       | Unauthorized                                            |
| 404       | Not Found                                               |
| 403       | Forbidden (For application level permissions)           |

To status code is just sent to the browser, if you are rending HTML it will be make no difference, if its json and you use ajax queries it will.

If you want to set the code and generate an error page for the status, then you should throw an exception instead.

```php
use Origin\Exception\BadRequestException;
class ArticlesController extends AppController
{
    function backdoor(){
        throw new BadRequestException('Bad Request');
    }
}
```

Exceptions for each of the above status codes are available as well as few other ones can be found in the `origin/Exception` folder. If you have `debug` set to true in your bootstrap file then you will see the error on screen, if not then either 500 or 404 exception will be thrown and the details will be sent to the application.log file in the `logs` folder.

## Helpers

Helpers allow you share code between views, similar to the controller components. To load helpers call the `loadHelper` method in the `initialize` method of your controller.

```php
public function initialize(){
    $this->loadHelper('TableMagic');
}
```

For more information on this see the [helpers guide](helpers.md).

## Html Helper

The Html helper provides some useful tools for working with html.

### Links

To create a generate a link for  `/articles/view/1234` you can pass an array. You don't need to pass a controller name
if the link is to the same controller.

```php
echo $this->Html->link(['controller'=>'Articles','action'=>'view',1234]);
```

When using array you can also pass the following additional keys.

- ?: this should be array of key value pairs to generate a query string after the url
- #: for html fragements, this a link on the page which will cause it to scroll

All keys that are an integer will be passed as an argument. So to get `/articles/view/1234/abc`, use the following array.

```php
echo $this->Html->link(['controller'=>'Articles','action'=>'view',1234,'abc']);
```

You can also just pass a string

```php
echo $this->Html->link('/articles/action/1234');
```

### Scripts Tags

To load a javascript file from the `public/js` folder, this example we want to load `form.js`.

```php
echo $this->Html->js('form');
```

If you want to load from a different folder, make sure you start the name with a forward slash.

```php
echo $this->Html->js('/assets/js/form.js');
```

You can also load external files

```php
echo $this->Html->js('https://code.jquery.com/jquery-3.3.1.min.js');
```

You can also load a script from a plugin public folder, this will automatically load the contents inline of the view. This should only be used for development purposes for performance reasons, once in production move the file or create a symlink to it.

```php
echo $this->Html->js('Myplugin.custom.js');
```

### Stylesheets

Similar to loading scripts, you can use the css method.

```php
echo $this->Html->css('bootstrap');
```

To load from a different folder.

```php
echo $this->Html->css('/assets/css/bootstrap.css');
```

To load a stylesheet located on the web.

```php
echo $this->Html->css('https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css');
```

And to load a stylesheet from a plugin folder, you must also provide the controller name.

```php
echo $this->Html->css('MyPlugin.Controller/bootstrap.css');
```

### Images

You can easily add images to your view, you must provide an extension and if the name does not start with a forward slash it will look for the image in the `public/img` folder.

```php
echo $this->Html->img('spinner.png');
echo $this->Html->img('/somewherelse/images/spinner.gif'); // from public folder
```

## Elements

Sometimes you might use the same block of code inside multiple views, in this case you would want to use elements which are stored in `View/Element` and end with a `.ctp` extension.

Create a file  `View/Element/widget.ctp`

```php
<h2>Widget</h2>
<p>What is 1 + 1 ? <?= $answer ?></p>
```

Now anytime you want to use that element, you can also pass an array options where the data will be converted into variables with the names taken from the key value.

```php
 echo $this->element('widget',['answer'=>2]);
```

## Form Helper

The Form helper does the heavy lifting when working with forms in your app.

### Creating a Form

To create a form you use the create method and once you have finished adding the elements you call the end method which simply closes the form the tag. You should the pass an entity (it can be blank) for the model that you are working with, if you don't the form helper will work with the data from the request.

```php
    echo $this->Form->create($article);
    ...
    echo $this->Form->end();

```

If you want to create a form without this you can, however the database wont be introspected and fields types cant be detected, when creating a form this way, the request data is used. Validation errors are stored in entities, so using a form without this method cannot be used to display validation errors automatically.

```php
$this->Form->create();
$this->Form->create(null,$options);
```

The form options are as follows:

- type: default is `post`, you can also set this to `get` or `file` if you are going to upload a file.
- url: default is the current url, however you can use any url you want.

If you pass any other keys in the options, they will be used as attributes for the form tag, for e.g class.

### Form Controls

The form control is the main feature on the form helper, it will create a form element based depending upon how your database is setup. Form controls create a label and wrap the input in a div as well as display validation errors etc.

E.g.

```php
echo $this->Form->control('first_name');
```

Will output:

```html
<div class="form-group text">
    <label for="name">First Name</label>
    <input type="text" name="name" class="form-control" id="name">
</div>
```

### Form Control options

The options for control allow you to change the default settings.

- type: use this to override the type, text,textarea,number,date,time,datetime,select,checkbox,radio,password and file
- div: change the name of the div that input element is wrapped in e.g. form-group
- class: change the name of the class of the input element e.g. form-input
- label: (default:true), boolean, a string which will be the label text, or an array with the text key and any other options
 e.g. class

 All other options will be passed to the form element, if it does not recognise the option it will treat it as an attribute.

```php
echo $this->Form->control('name',['placeholder'=>'enter your name']);
```

The standard options which be used in most form inputs which are used by the control method

- id: (default:true) bool or set a string with the name that you want
- name: change the name of the options
- value: set the default value to be used by the input

Any other keys passed to the form inputs will be treated as attributes for html tag e.g. class, pattern,placeholder etc.

### Input Types

#### Text

This will be display a text box.

```php
echo $this->Form->text('first_name');
```

```html
<input type="text" name="first_name">
```

#### TextArea

This displays a textarea element

```php
echo $this->Form->textarea('some_name');
```

```html
<textarea name="some_name"></textarea>
```

#### Select

The select element works slightly different, since the second argument is for generating the options in the select, and the
third argument is where the options/attributes are passed.

```php
echo $this->Form->select('categories', [1=>'First',2=>'Second']);
```

```html
<select name="categories">
    <option value="1">First</option><option value="2">Second</option>
</select>
```

When working with selects you might want to allow an empty option.

```php
$selectOptions = [1=>'First',2=>'Second'];
echo $this->Form->select('categories',$selectOptions , ['empty'=>'select one']);
```

Which will output this:

```html
<select name="categories">
    <option value="">select one</option>
    <option value="1">First</option>
    <option value="2">Second</option>
</select>
```

#### Checkbox

To generate a checkbox

```php
echo $this->Form->checkbox('subscribe');
```

This will output this

```html
<input type="hidden" name="subscribe" value="0"><input type="checkbox" name="subscribe" value="1">
```

If you want it checked by default.

```php
echo $this->Form->checkbox('subscribe',['checked'=>true]);
```

#### Radio

To generate a radio input

```php
echo $this->Form->radio('plan', [1000=>'Basic',1001=>'Premium']);
```

```html
<label for="plan-1000"><input type="radio" name="plan" value="1000" id="plan-1000">Basic</label>
<label for="plan-1001"><input type="radio" name="plan" value="1001" id="plan-1001">Premium</label>
```

To check a value by default, set the value in options.

```php
echo $this->Form->radio('plan', [1000=>'Basic',1001=>'Premium'], ['value'=>1001]);
```

#### File

To create a file upload you need to set the type option when creating the form and then call file method.

```php
echo $this->Form->create(null, ['type'=>'file']);
echo $this->Form->file('contacts');
echo $this->Form->button('Import Contacts');
echo $this->Form->end();
```

This would create the following html:

```html
<form enctype="multipart/form-data" method="post" action="/contacts/import">
<input type="file" name="contacts">
<button type="submit">Import Contacts</button>
</form>
```

Then you can access the file data from the `request` object when the form has been submitted.

```php

print_r($this->request->data('contacts'));

// The array would look like this
Array
(
    [name] => bitcoin.pdf
    [type] => application/pdf
    [tmp_name] => /tmp/phpgCQmO0
    [error] => 0
    [size] => 184292
)
```

#### Password

This generates a password field.

```php
echo $this->Form->password('secret');
```

```html
<input type="password" name="secret">
```

### Date, Datetime Time and Number

These are just text elements and are place to give flexibility, the control method will generate html through these based the field type in the database. You can hook these as you see fit, or change the default templates for them.

## PostLinks

To create a link which when clicked on sends a post request. This is used in the framework during code generation for delete links, this allows you to ask the user to confirm and make sure people don't call the url manually.

```php
echo $this->Form->postLink('delete','/contacts/delete/1234',['confirm'=>'Are you sure you want to delete this?']);
```

This will output this:

```html
<form name="link_1000" style="display:none" method="post" action="/contacts/delete/1234">
<input type="hidden" name="_method" value="POST"></form>
<a href="#" method="post" onclick="if (confirm(&quot;are you sure?&quot;)) { document.link_1000.submit(); } event.returnValue = false; return false;">delete</a>
```

### Buttons

Buttons created via the button method in the form helper are automatically treated as submit buttons, if you don't want this then pass the `type` option as `button`.

```php
echo $this->Form->button('save');
echo $this->Form->button('cancel',['type'=>'button','onclick'=>'back();']);
```

## Controls for Associated Data

To create a form for associated data you use `model.fieldName` for `hasOne` and `belongsTo`, or `models.0.fieldName` for `hasMany` and `hasAndBelongsToMany`. Remember model names should be in lowercase.

```php
// Create for the Article Model
echo $this->Form->create($article);

// $article->title
echo $this->Form->control('title');

// $article->author->name - BelongsTo and HasOne
echo $this->Form->control('author.name');

// HasMany and HasAndBelongsToMany
echo $this->Form->control('tags.0.name');
echo $this->Form->control('comments.0.text');
echo $this->Form->control('comments.1.text');
```

When the form is posted the request data will look like this:

```php
Array
(
    [title] => How to save associated data
    [author] => Array
        (
            [name] => Jon Snow
        )
    [tags] => Array
        (
            [0] => Array
                (
                    [name] => New
                )
        )
    [comments] => Array
        (
            [0] => Array
                (
                    [text] => This is my first comment
                )
            [1] => Array
                (
                    [text] => This is my second comment
                )
        )
)
```

## Templates and defaults

### Control Defaults

OriginPHP uses bootstrap for its front end, and the defaults for each control are configured accordingly.
If you need to change these you can do by calling `controlDefaults` from your within view.

```php
$this->Form->controlDefaults([
    'text' => ['div' => 'form-group', 'class' => 'form-control']
    ]);
```

Or if you want to change them across the whole app or a particular controller, then set the `controlDefaults` option when loading the helper.

```php
  $this->loadHelper('Form', [
      'controlDefaults'=>[
        'text' => ['div' => 'input-field']
      ]
      ]);
```

### Templates

Depending upon the front end framework you are using you might need to adjust the default templates, for example wrapping a control in another div or changing how the template for an error message.

You can create a file in your `config`  which should return an array.

For example create add `config/myform-templates.php`  the following code:

```php
return [
    'control' => '<div class="row"><div class="{class} {type}{required}">{before}{content}{after}</div></div>',
    'controlError' => '<div class="{class} {type}{required} error">{before}{content}{after}{error}</div>',
    'error' => ' <span class="helper-text" data-error="wrong" data-success="right">{content}</span>'
];
```

Then when loading the Form Helper set the templates option, this will replace the default ones with the onces that you have defined.

```php
$this->loadHelper('Form',[
    'templates'=>'myform-templates'
    ]);
```

You can also change individual templates at runtime

```php
$this->Form->templates(['error'=>'<div class="omg">{content}</div>']);
```

## Date Helper

The date helper makes it easy to format dates, in your `AppController` setup the default timezone, and date formats (using PHP date function style formats), and the date helper will automatically format dates and times unless you tell it to use a different format. The date helper uses the Date utility.

```php
use Origin\Utility\Date;
public function initialize(){
     Date::locale([
         'timezone' => 'UTC',
         'date' => 'm/d/Y',
         'datetime' => 'm/d/Y H:i',
         'time' => 'H:i'
         ]);
}
```

From within your view you would use like this, it will automatically format the date/time/datetime depending upon the field type. The date helper assumes that the date in the database is stored in MySQL date/time formats. If you set the timezone to anything other than UTC, then the date utility will automatically convert times etc. Setting the timezone does not change the PHP script timezone, it is only used by the date utility.

```php
echo $this->Date->format($article->created); // From 2019-01-01 13:45:00 to 01/01/2019 13:45
```

If you want to format it a different way, you can do so. Time values will still be converted to local time if you set the timezone to anything other than UTC.

```php
echo $this->Date->format($article->created,'F jS Y'); // January 1st 2019
```

### Parsing

The date formatter assumes that the dates that you are formatting are in MySQL format, e.g. Y-m-d H:i:s. You can use the Date utility to delocalize user submitted data to convert to this format and timezone.

This can be done in middleware, the controller or model. The simplest way is to use the model callbacks such as `beforeValidate` or `beforeSave`.

```php
use Origin\Utility\Date;
$entity->created_date = Date::parseDatetime($entity->created_date); //31/01/2019 10:00 AM -> 2019-01-31 09:00:00
```

## Number Helper

The number helper provides a number of useful functions, you can configure the defaults in your `AppController` like this:

```php
use Origin\Utility\Number;
public function initialize(){
    Number::locale([
        'currency'=>'USD', // default currency
        'thousands'=>',',
        'decimals'=>'.',
        'places'=>2
        ]);
}
```

Once you have this configured whenever you use the number helper it will format based upon those defaults
unless you tell it otherwise.

## Formating numbers

```php
$this->Number->format('123456789.123456789'); // 123,456,789.12
$this->Number->format('123456789.123456789',['places'=>4]); //123,456,789.1235
```

Options for the number formatting are these, currency, decimal and percentage use the number formatter so you can also pass these options to those methods as well.

- before: Adds text before the string
- after: adds text after the string
- thousands: the thousands separator
- decimals: the decimals separator
- places: how many decimal points to show

### Formating Currencies

To format a currency you can do it like this, if you don't supply a currency as the second argument then
it will use your default currency.

```php
echo $this->Number->currency('1000000'); // $1,000,000.00
echo $this->Number->currency('1000000','GBP'); //£1,000,000.00
echo $this->Number->currency('1000','USD',['places'=>'per group']);
```

By default the number helper can work with USD, GBP, EUR, CAD, AUD,CHF AND JPY out of the box. But you can also add your own currencies.

```php
public function initialize(){
    Number::addCurrency('CNY',['before'=>'¥','name'=>'Chinese Yuan']);
}
```

Then from your view just supply the currency code as the second argument.

```php
echo $this->Number->currency('1000000', 'CNY'); // ¥1,000,000.00
```

### Formating Decimals

```php
echo $this->Number->decimal('100'); // 100.00
echo $this->Number->decimal('100.1',3); // 100.100
```

## Formating Percentages

On top the number options, when formating percentages there is an additional option which is `multiply`, this will multiply the result, this is handy when you have the result in decimal format.

```php
echo $this->Number->percent(50); // 50.00%
echo $this->Number->percent(0.3333333333, 2, ['multiply'=>true]);// 33.33%
```

### Parsing

The number formatter assumes that the numbers are do not have a thousands separator and the decimal point is
`.`. You can delocalize the user inputted data in the middleware, the controller or model. The simplest way is to use the model callbacks such as `beforeValidate` or `beforeSave`.

```php
use Origin\Utility\Number;
$user->balance = Number::parse($user->balance); //1.000,23 -> 1000.23
```