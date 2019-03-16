# Controllers

## What is a Controller
Controller is the C in MVC (Model View Controller). When a request is made, it is passed to the router then the router determines which controller to use. Most applications will receive the request, then get, create and or save data to the database through a model and then use a view to create the output to display to the user. It is considered a good practice to keep the controllers skinny, and should not contain business logic, the models should contain that.

## Controller Conventions
The name of the controller. should be in plural camel case and it needs to end with Controller. For example, `UsersController`,`UserProfilesController` and `BookmarksController`. It is important that you follow the conventions so that you can use the default routing, you can always customise the routing rules later.

## Controller Methods and Actions
When you create a controller it will extend the `AppController` and save this to the `src/Controller` folder. Your controller class will contain methods just like any other class, but only public methods will be treated as routeable actions.  When your application receives a request, the router will determine which controller and action to use, and then create an instance of the controller and run the action.

```php
namespace App\Controller;

class ContactsController extends AppController {
  public function view($id){

  }
}
```

For example, if a user wants to create a new contact and in your application they would go to  `/contacts/create`, this will load the `ContactsController` and run the `create` method, this will then automatically render the `/src/View/Contacts/create.ctp` unless you tell it otherwise. In the method we will create a Contact entity, which is a object which represents a single row of data, and then we will send it to the view using `set`. We do this by calling the `newEntity` method on the Contact model.

```php
class ContactsController extends AppController {
  public function new(){
      $contact = $this->Contact->newEntity();

      $contact->first_name = 'James';
      $contact->email = 'james@example.com';

      $this->set('contact',$contact);
  }
}
```

Any methods which are not supposed to be actions, should be set to `private` or `protected`.

## Request

When a request is made, a request is object is injected into the controller. GET, POST and FILES parameters are parsed and it also provides some functions to check the type of request or only allow a certain type of request.

### Params
Lets look at this request:

```
GET /bookmarks/view/1024
```

You will need to get the params array from the request object, and this will contain information about the controller, action, the passed arguments (pass), named parameters, which route was matched and the plugin name.

```php
print_r($this->request->params);
/*
Array
(
    [controller] => Bookmarks
    [action] => view
    [pass] => Array
        (
            [0] => 1024
        )

    [named] => Array
        (
        )

    [route] => /:controller/:action/*
    [plugin] => 
*/
)
```

This is an example of what named parameters looks like in a request.
```
GET /books/index/sort:desc/page:100
```

```php
print_r($this->request->params['named']);
/*
Array
(
 [sort] => desc
 [page] => 100
)
*/
```

### Query

Query parameters are also accessed through the request object.

```
GET /books/index?sort=asc&page=101
```

```php
print_r($this->request->query);
/*
Array
(
 [sort] => asc
 [page] => 1001
)
*/
```

## Post Data

Post data is data which has been posted, we have taken this from the $_POST variable.
```html
<input type="text" name="first_name" value="James" />
<input type="text" name="email" value="james@example.com" />
```
```php
print_r($this->request->data);
/*
Array
(
 [first_name] => 'James'
 [email] => 'james@example.com'
)
*/
```

## Custom Parameters

You can configure routes to return the some of the parameters as keys in the `params` array. You can do
this by modifying the `config/routes.php` to include something like this:

```php
Router::add('/:controller/:action/:id');
/*
Array
(
 [controller] => 'YourController'
 [action] => 'some_action',
 [id] => 1234
)
*/
```

## Session

When you need to persist small amounts of data between requests, using Sessions makes it really easy. The session is object
is available in controllers (and components) and views (and helpers).

To get the session object you need to get it from the request object.

Session data is stored using key value pairs, you can also use dot notation to deeper levels of an array. For example, `userProfile.id` would first look for the key would look for the key `userProfile` and if its value is an array and has the key `id` it will return the value. If it there is no key set then it will return a `null` value.

```php
class ContactsController extends AppController {
  public function getUserId(){
     $session = $this->request->session();
     return $sesson->read('user_id');
  }
}
```

To store data in the session:

```php
class ContactsController extends AppController {
  public function setUserId($id){
     $session = $this->request->session();
     $sesson->write('user_id',$id);
  }
}
```

To delete an item from the session

```php
class ContactsController extends AppController {
  public function deleteUserId(){
     $session = $this->request->session();
     $sesson->delete('user_id');
  }
}
```

You can also reset the whole session using the `reset` method.

## Flash Component
The Flash component enables you to display messages to the user either in the current request or on the next if redirecting.

```php
class ContactsController extends AppController
{
    public function edit($id){
        if($result){
            $this->Flash->success('Result is true');
        }
        else{
            $this->Flash->error('Result is false');
        }
    }
}
```
Each type of message will rendered in a div with its own class. The Flash component has the following methods:

- `info`
- `success`
- `warning`
- `error`

## Cookies
You can work with cookies from controllers and views, The cookie object allows you easily work with cookies. All cookie values are stored as a json string and by default they are automatically encrypted.

Here are some examples how to use the Cookie component

```php
class ContactsController extends AppController
{
    public function createCookies(){
      $this->Cookie->write('forever',rand());
      $this->Cookie->write('for-one-day-only',rand(),strtotime('+1 day'));
    }
    public function readCookie(){
        return $this->Cookie->read('monster');
    }
    public function deleteCookie(){
        $this->Cookie->delete('monster');
    }
}
```

You can also delete all cookies using the `destroy` method.

Another way to work with cookies is to use the request and response objects. You can get cookie values from the request object and set them on the response object.

```php
$value = $this->request->cookie('monster');

$this->response->cookie('key','value');
$this->response->cookie('key','value',strtotime('+7 days'));
$this->response->cookie('keyToDelete','',strtotime('-60 minutes')); // to delete
```

NOTE: When you use the response for writing cookies the values wont be available for reading until the next request, since they are only sent after everything has been rendered to the screen.

## Rendering Views
By default, all views in your controller are rendered as html. Rendering takes place automatically for the controller and action. So if you if the user requests `/contacts/show/1` it will load the `View/Contacts/show.ctp` file.

### JSON Views
You can quickly and easily render JSON data using results returned from find or get operations, arrays of data and strings. The controller will automatically call the `toJson` on the objects.

```php
class ContactsController extends AppController
{
    public function view($id)
    {
        $user = $this->User->get($id);
        $this->render(['json'=>$user]);
    }
}
```

You can also set the status code, this is handy when dealing with errors.

```php
class ContactsController extends AppController
{
    public function view($id = null)
    {
        $json = [
            'errors'=>[
                'message' =>'Not Found'
            ]
        ];
        $this->render(['json'=>$json,'status'=>404]);
    }
}
```

 Remember there are quite a lot of status codes, including `418 I am a teapot`, so you might find it easier to work with a smaller group of them which can easily cover everything you need to do.

| Status Code     | Definition                                                                                                |
| ----------------|---------------------------------------------------------------------------------------------------------- |
| 200             | OK (Success)                                                                                              |
| 400             | Bad Request (Failure - client side problem)                                                               |
| 500             | Internal Error (Failure - server side problem)                                                            |
| 401             | Unauthorized                                                                                              |
| 404             | Not Found                                                                                                 |
| 403             | Forbidden (For application level permissions)                                                             |

### XML Views
To render a xml view, just pass a result from the database, an array or a xml string. Data is converted using the XML utility. If you need to wrap some data in cdata tags, then make sure to include `use Origin\Utility\Xml` at the top of your file so you can call it directly.

```php
use Origin\Utility\Xml;
class PostsController extends AppController
{
    public function lastest()
    {
        $data = [
           'post' => [
               '@category' => 'how tos', // to set attribute use @
               'id' => 12345,
               'title' => 'How to create an XML block',
               'body' =>  Xml::cdata('A quick brown fox jumps of a lazy dog.'),
                'author' => [
                    'name' => 'James'
                  ]
              ]
         ];
        $this->render(['xml'=>$data]);
    }
}
```

Here is another example using data returned from the find operation.

```php
class ContactsController extends AppController
{
    public function all()
    {
        $results = $this->Contacts->find('all');
        $this->render(['xml'=>$results,'status'=>200]);
    }
}
```

## Filters
The controller has filters which are run before and after actions, and even in-between such as before rendering or before redirecting. If you want the filters to be run in every controller, then add them to the `AppController` and all child controllers will run this. Just remember to call the parent one as well.

### Before Filter
This is called before the action on the controller (but after initialize), here you can access or modify request data, check user permissions or session data. If you need too you can even stop the action from continuing by throwing an exception or redirecting to somewhere else.

```php
class PostsController extends AppController
{
    public function beforeFilter(){
        if($this->Auth->isLoggedIn()){
            $session = $this->request->session();
            echo $session->read('user_name');
        }
    }
}
```

### After Filter
This is called after the controller action has been run and the view has been rendered, but before the response has been sent to the client.

```php
class PostsController extends AppController
{
    public function afterFilter(){
        $this->doSomething();
    }
}
```

### Other Filters
There are two other filters in the controllers that you can use, and these are `beforeRender` and `beforeRedirect`.

## Request Object
In every controller you will find a `request` and `response` object. The request object contains information on the request made and the response object represents what will be sent back to the client.

### Request Methods

#### Getting the URL
To retrieve the full url including query string use `url` method, if you don't want the query string then pass false as an argument

```php
$fullUrl = $request->url(); // url: /contacts/view/100?page=1
$withoutQueryString = $request->url(false);// url: /contacts/view/100
```

### Determining the request method
The request `method` will return a string such as POST, PUT etc. The is function will check against a string or an array of methods to see if it matches up.

```php
$method = $request->method();

if($this->request->is('post')){
    // do something
}
```

### Allowing certain method
You can also allow only certain HTTP request methods, this can be a string or an array of methods.
```php
public function delete($id = null)
{
    $this->request->allowMethod(['post', 'delete']);
    ...
}
```

### Reading values from cookies in the request
In addition to getting the cookie object, from the request object you can read a value for a cookie.
```php
public function doSomething()
{
    $value = $this->request->cookie('key');
}
```

## Response Object

| Property                      | Definition                                                                            |
| ------------------------------|-------------------------------------------------------------------------------------- |
| status                        | This is the HTTP status code, e.g 200 for success or 404 for not found                |
| body                          | This is the string that is being sent to the view                                     |
| headers                       | These are the headers that will be sent                                               |
| contentType                   | The content type this could be html, json, csv etc                                    |

### Setting Custom Headers

You can set and get headers through the response object,

```php
$this->response->header('Accept-Language', 'en-us,en;q=0.5');
```

### Setting the Content Type

```php
$type = $this->response->type();
$this->response->type('application/vnd.ms-powerpoint');
```


### File Downloads

Sometimes you will need to send a file which is different from rendering a page. You can also force it to download the file
by setting `download` to true. The available options are `name`,`type` for content type and `download`. 

```php
$this->response->file('/tmp/transactions.pdf');
$this->response->file('/tmp/originphp.tmp',['name'=>'transactions.pdf']);
$this->response->file('/tmp/transactions.pdf',['download'=>true]);
```

## Logging

Logs are stored in `logs` and make it easy to debug and keep track of what is going on.
 OriginPHP uses a minimalistic file logger based upon the PSR 3 standard.

Each line in the log includes the date, channel, type of message and the message itself.

To get logger from the controller.

```php 
$logger = $this->logger();
$logger->error('something has gone wrong');
```
That will produce a line like this in the log:
```
[2019-03-10 13:37:49] Controller ERROR: something has gone wrong.
```

If you need to change the channel, you do that when calling the logger object.

```php 
public function index()
{
    $logger = $this->logger('EmailsController');
    $logger->warning('{key} was null',['key'=>'foo']);
}
```

This will produce a line like this in the log:
```
[2019-03-10 14:25:50] EmailsController WARNING: foo was null.
```

You can call the following logging methods on the Logger object:

| Method            | Use case                                                                                          |
| ------------------|-------------------------------------------------------------------------------------------------- |
| debug             | Detailed debug information                                                                        |
| info              | Interesting events.                                                                               |
| notice            | Normal but significant events.                                                                    |
| warning           | Exceptional occurrences that are not errors                                                       |
| error             | Runtime errors that do not require immediate action but should typically be logged and monitored. |
| critical          | Critical conditions or events.                                                                    |
| alert             | Actions that must be taken immediately.                                                           |
| emergency         | The system is unusable.                                                                           |