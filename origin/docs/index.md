# OriginPHP Framework

This is the manual for the OriginPHP framework, anybody can edit or improve this documentation by forking the code from [github](https://github.com/originphp/framework).

## Conventions

### Controller

Controller classes are plural and camel cased and end with controller, for example the controller for user profiles would be called `UserProfilesController`. 

````php

namespace App\Controller;

class UserProfilesController extends AppController
    ...
}

````

Sometimes you might want a controller to be named the singular version for users to see, if so then create a custom route in `routes.php`.

### Model

Models are singular camel cased, for example the model for a user profile is `UserProfile` this you can access from the controller.

````php

namespace App\Controller;

class UserProfilesController extends AppController

    public function index(){
        $records = $this->UserProfile->find('all');
    }
}

````
Table names should be plural and underscored. For example `user_profiles`. 
Each table in your database should have a primary key,and it should be named `id`. Foreign keys should be the singular underscored name, for example `user_profile_id`.

Dates,datetime,and time use the MySQL field types of the same time. So the date format is `YYYY-MM-DD`.

When accessing related models from a result (Entity), it is camel cased with the first letter in lower case. If it is `hasOne` or `belongsTo` then it is singular else if it is a `hasMany` or `hasAndBelongsToMany` then it is plural.


````php

foreach($users as $user){
    $tags = $user->tags;
    $userProfile = $user->userProfile; // hasOne or belongsTo
    $emails = $user->sentEmails; // hasMany or hasAndBelongsToMany 

}

````

### View

The view templates are in the `View` folder, and the folder name is plural camel cased, so for user profiles it would be `UserProfiles`, the templates
end with `.ctp` extension.

When `/users_profiles/index` is requested, the following will happen: 

- load the controller `UserProfilesController` from the `Controller` folder
- load the model `UserProfile` from the `Model` folder
- load the view template `index.ctp` from the `View/UserProfiles` folder