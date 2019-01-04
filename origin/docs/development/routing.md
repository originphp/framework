# Routing

When requests come they are routed to the correct controller. You configure routes in the `config\routes.php` file.

To add a route you use the Router add method.

`Router::add($route,$options)`

Lets look at some real examples.

You prefer a user does not see the controller and action in the url , this example will take all requests to `/login` and send it to the users controller and call the action `login`.

`Router::add('/login', ['controller' => 'Users', 'action' => 'login']);`

You want all requests to / to display a page using the pages controller.

`Router::add('/', ['controller' => 'Pages', 'action' => 'display', 'home']);`

You want to parse the url in a certain way using variables. `:controller` for controller and `:action` for action. This is the default routing that is used in the framework, you can remove this and then only setup routes for what you want.

`Router::add('/:controller/:action/*');`

For example if you only wanted to route for the posts controller.

`Router::add('/posts/:action/*',['controller'=>'Posts']);`

You can use the same to show a different controller in the url.

`Router::add('/posts/:action/*',['controller'=>'BlogPosts']);`