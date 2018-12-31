# Views

Views are templates used to generate output for the requests. The view templates are in the `View` folder, and each controller has its own sub folder. The folder name is plural camel cased, so for user profiles it would be `UserProfiles`, the templates end with `.ctp` extension. For example `View/UserProfiles`.

Here is an example view for a the login action of the users controller

````php
<h1>Login</h1>
<?php 
   echo $this->Form->create();
   echo $this->Form->control('email');
   echo $this->Form->control('password');
   echo $this->Form->button('Login');
   echo $this->Form->end();
?>
````

The views are rendered inside layouts, which can be found in the `View/Layout` folder, a layout has the main template for the whole page, which includes the html head and body tags etc.

You can access the current request data from the view by using `$this->request->params` and the query data can be obtained from `$this->request->query`. From your controller you can send data to the view by using `$this->set('key',$value);` and this can be accessed as a variable, e.g `echo $key`. 

## Elements

Sometimes you might use the same block of code inside multiple views, in this case you would want to use elements which are stored in `View/Element` and end with a `.ctp` extension.

Create a file  `View/Element/widget.ctp`

````php
<h2>Widget</h2>
<p>What is 1 + 1 ? <?= $answer ?></p>
````

Now anytime you want to use that element, you can also pass an array options where the data will be converted into variables with the names taken from the key value.

`element($name,array $options=[])`

````php

 echo $this->element('widget',['answer'=>2]);

````