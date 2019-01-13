# Code Generation

If you follow the conventions with database tables and column naming, you can quickly generate code use the `make` plugin, once you have done this you and delete the plugin from your source tree.

You can also customise the templates, and then save them for future projects.

The first thing to do is setup your database and config.

Run the console command (this is a shell script in the bin folder)

`bin/console make` and this will show the options that are available to you.

So lets you are creating a contact app, and you have a `contacts` table setup

We will need to create the Model, View and Controller (MVC);

`bin/console make controller Contacts`

`bin/console make model Contact`

`bin/console make view Contacts`

If all went ok then you should be able to acesss your contacts app by `http://localhost:8000/contacts`

It will create the following views
- `/contacts/add` 
- `/contacts/edit`
- `/contacts/index`
- `/contacts/view`