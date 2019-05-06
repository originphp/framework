# Middleware

If you need to use middleware for your app, doing so is straightforward, just create the file in `Middleware` folder and make sure both the name and class ends with `Middleware`, for example, the foo middleware would be `FooMiddleware`.

Your middleware class will need the process method and it must return the response object.

```php
namespace App\Middleware;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware;

class RequestModifierMiddleware extends Middleware
{
    /**
     * Processes the request, this must be implemented
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Response
     */
    public function process(Request $request, Response $response) : Response
    {

        // do something
        return $response;
    }
}
```

To load the middleware,  you need to call `loadMiddleware` in the initialize method of `src/Application.php` file. When web requests are run, the middleware process method will be called for each loaded middleware. In middleware you can modify the request data or set response.

```php
public function initialize()
{
    $this->loadMiddleware('RequestModifier');
}
```

You can also load middlewares from plugin folders.

```php
public function initialize()
{
    $this->loadMiddleware('MyPlugin.RequestModifier');
}
```

Thats its.