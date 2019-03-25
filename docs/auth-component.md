# Auth Component

The Auth Component makes it easy to secure your application by requiring login.

You enable login via forms and/or http requests. By default it is only forms, you but can easily enable both.

To load and enable the `AuthComponent`, in your app controller initialize method add the following

```php

    public function initialize()
    {
        parent::initialize(); // !Important whenever you use a callback or initialize method
        $this->loadComponent('Auth',$options);
        ...
    }

```

The default config for the `AuthComponent`.

- *authenticate*: Supports `Form` and `Http`
- *loginAction*: This is the login action for the Form
- *loginRedirect*: This is where users are taken too when they login
- *logoutRedirect*: This is where users are taken too when they logout
- *model*: This the model used, default User
- *fields*: This is to configure the `username` field default is `email` and the password field in the model which called by the same name.
- *unauthorizedRedirect*: By default the user is redirected if the credentials are incorrect, however if you set to false then an exception is thrown.
- *authError*: The message to be displayed to the user

```php
     $config  = [
            'authenticate' => ['Form','Http'], // Form and Http supported
            'loginAction' => [
                'controller' => 'Users',
                'action' => 'login',
                'plugin' => null,
            ],
            'loginRedirect' => [
                'controller' => 'Users',
                'action' => 'index',
                'plugin' => null,
            ],
            'logoutRedirect' => [
                'controller' => 'Users',
                'action' => 'login',
                'plugin' => null,
            ],
            'model' => 'User',
            'fields' => ['username' => 'email', 'password' => 'password'],
            'scope' => [], // Extra conditions for db . e.g User.active=1;
            'unauthorizedRedirect' => true, // If false no redirect just exception e.g cli stuff
            'authError' => 'You are not authorized to access that location.',
        ]

```

In the controller add a method for the login, first we need to identify the user, if the user is authenticated then it will return a User Entity. Then if the user is returned you can modify any data then 
use the `login` method, which converts the User into an array and stores in the Session which essentially logs the the User in.

```php
 public function login()
    {
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->login($user);

                return $this->redirect($this->Auth->redirectUrl());
            }
            $this->Flash->error(__('Incorrect username or password.'));
        }
    }
```

When you need to access the logged in user info, you call the `user` method, if you do not pass a name
of a field, then it will return an array of the User information.

```php
    $user = $this->Auth->user();
```

Alternatively, you get an individual value from the user array by passing a key.

```php
    $email = $this->Auth->user('email');
```

The passwords are encrypted with php password_hash function, which is very secure.
So when a user signs up or changes their password you will need to hash the password, this will normally 
be done in your user model, this will help keep your controller slim.

```php

        class User extends AppModel
        {
            public function beforeSave(Entity $entity, array $options = [])
            {
                if(!parent::beforeSave($entity,$options)){
                    return false;
                }

                if(!empty($entity->password)){
                    $entity->password = password_hash($entity->password, PASSWORD_DEFAULT);
                }

                return true;
            }
        }
        
```

If you are going to hash the password from within the controller then you can use the auth component hashPassword method.


```php
    $user->password = $this->Auth->hashPasword($user->password);
```

In some controllers you might to want to allow certain actions to not require authentication, in this case you can pass and array of allowed actions

```php
    $this->Auth->allow(['reset_password','verify_email']);
```

Sometimes you want to know if the User is logged in, to do this use the `isLoggedIn` method.

```php
    if($this->Auth->isLoggedIn()){
        // do something
    }
```