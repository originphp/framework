# Testing Your Apps

## Setting up the database for testing

OriginPHP uses PHPUnit for unit testing.

The first thing to do is to create a test database, and setup the test database configuration.

To create the database and user you can use the following MySql.

````sql
CREATE DATABASE app_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON app_test.* TO 'somebody' IDENTIFIED BY 'secret';
FLUSH PRIVILEGES;
````

In your `config/database.php` add database config.

````php
    ConnectionManager::config('test', array(
    'host' => 'db', // Docker is db, or localhost or Ip address
    'database' => 'app_test',
    'username' => 'somebody',
    'password' => 'secret',
    ));
````

### Conventions

When you create tests these will go in the `tests/TestCase` folder, and then depending upon the type it will be another sub folder and the file will end with the `Test.php`

`tests/TestCase/Controller/BookmarksControllerTest.php`
`tests/TestCase/Model/BookmarkTest.php`
`tests/TestCase/Lib/MathLibraryTest.php`

When you create the test files, the filename should end with `Test.php` and they will extends either
- `\PHPUnit\Framework\TestCase` - To use PHPUnit directly without extra features of Origin such as fixtures
- `Origin\TestSuite\TestCase` - For testing everything else

### Testing Models

You will create a test case class like this, defining the fixtures that you will use in testing (including models that are used by other models etc).

````php
namespace App\Test\TestCase\Model;

use Origin\TestSuite\OriginTestCase;
use Origin\Model\ModelRegistry;
use Origin\Model\Entity;

class BookmarkTest extends OriginTestCase
{
    public $fixtures = ['Bookmark'];

    public function setUp()
    {
        $this->Bookmark = ModelRegistry::get('Bookmark');
        parent::setUp();
    }

    public function tearDown(){
        parent::tearDown(); # Important
    }

}

````

### Mocking Models 

To mock models extend your Test case by either `TestCase` or `ControllerTestCase` and then call the `getMockForModel` method. When the Model is mocked, we also add this to model registry. Remember if use the `tearDown` method in your test case, then call `parent::tearDown()`;

`getMockForModel(string $alias, array $methods=[],array $options=[])`

- *alias* name of model 
- *methods* array of methods to mock
- *options* options to be passed to constructor, you can also pass the class name of the model using
`className`

````php

class BookmarkTest extends OriginTestCase
{
    public function testSomething(){

        $model = $this->getMockForModel('Bookmark', ['something']);

        $model->expects($this->once())
            ->method('something')
            ->will($this->returnValue(true));
  
        $model->doSomething();
    }

}

````

## Testing Private or Protected Methods or Properties

There will be times you will want to test that protected or private methods or property, we have included a `TestTrait` to make this very easy.  There is a big debate on whether this should be done or just test the public methods and properties. I think it should be down to the specific case, for example if you look at our Email test, i wanted more control and each method to have its own test, I find this easier to write ,manage and maintain. 

````php
    public function testFrom()
    {
        $Email = new MockEmail();
        $Email = $Email->from('james@originphp.com');
        $this->assertInstanceOf('Origin\Utils\Email', $Email); // check returning this

        $property = $Email->getProperty('from'); # TestTrait
        $this->assertEquals(['james@originphp.com',null], $property);

        $Email = $Email->from('james@originphp.com', 'James');

        $property = $Email->getProperty('from'); # TestTrait
        $this->assertEquals(['james@originphp.com','James'], $property);
    }
````

An example of how you might use this:

````php

use Origin\TestSuite\TestTrait;

class BookmarkTest extends OriginTestCase
{
    use TestTrait;
    public function testPrivateProperty(){

        $privateProperty = $this->getProperty('hidden');
        ..
    }
}

````

There are 3 functions in the `TestTrait`

### callMethod(string $method,$args...)

This will call a any method, private or protected .

For example:

`$result = $this->callMethod('doSomething',$user, $password,...)`

### getProperty(string $name)

This will get any property of the object

For example:

`$id = $this->getProperty('id')`

### setProperty(string $name)

This will set any property of the object

For example:

`$this->setProperty('id',1234)`

# Testing Controllers

In the past testing controllers required quite a bit of code, however we have opted to use custom assertations and a request method which requires minimal input or config, to reduce the ifs and issets etc.

In your controller test case add the `IntegrationTestTrait`

````php

use Origin\TestSuite\IntegrationTestTrait;

class BookmarksControllerTest extends OriginTestCase
{
    use IntegrationTestTrait;

    public function testIndex(){
        $this->get('/bookmarks/index');
        $this->assertResponseOk();
        $this->assertResponseContains('<h2>Bookmarks</h2>');
    }
}

````

## Testing requests
You can test various requests

### get(string $url)

This will GET the url (get request)

`$this->get('/bookmarks/index');`

### post(string $url,array $data)

This will post DATA to the url (post request)

`$this->post('/bookmarks/index',['title'=>'bookmark name']);`

### delete(string $url)

This will send a DELETE request

`$this->delete('/bookmarks/delete/1234');`

You can also send PUT and PATCH requests.

### put(string $url,array $data)
### patch(string $url,array $data)

## Custom Assertations

````php

// Checks that response is 2xx
$this->assertResponseOk();

// Checks that response is 4xx
$this->assertResponseError();

// Checks that response is 2xx/3xx
$this->assertResponseSuccess();

// Checks that response is 5xx
$this->assertResponseFailure();

// Checks for a specific response code
$this->assertResponseCode(401);

// Check Response Content

$this->assertResponseEquals('{ "name":"James", "email":"james@originphp.com"}');

$this->assertResponseNotEquals('{ "error":"something went wrong"}');

$this->assertResponseContains('<h1>Some Title</h1>');

$this->assertResponseNotContains('please login');

$this->assertResponseEmpty();

$this->assertResponseNotEmpty();

// Check there was no redirect
$this->assertNoRedirect(); 

$this->assertRedirect(['controller'=>'users','action'=>'login']);

$this->assertRedirectContains('/users/view');

$this->assertRedirectNotContains('/users/login');

// Check Headers 
$this->assertHeaderContains('Content-Type', 'application/pdf');
$this->assertHeaderNotContains('Cache-Control', 'no-cache, must-revalidate');

````


## Other methods

### session(array $data)

Write data to session for the next request, one example is to test applications that require to be logged in.

````php
    $this->session(['Auth.User.id' =>1000]);
````

### header(string $header,string $value)

Set headers for the next request

````php
    $this->header('PHP_AUTH_USER','james@originphp.com');
    $this->header('PHP_AUTH_PW','secret');
````

### env(string $key , string $value)

Sets server enviroment $_SERVER

````php
    $this->env('HTTP_ACCEPT_LANGUAGE','en');
````

### controller()
Returns the controller from the last request

### request()
Returns the request object from the last request

### response()
Returns the response object from the last request