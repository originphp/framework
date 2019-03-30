# Code Generation

If you follow the conventions with database tables and column naming, you can quickly generate code use the `make` plugin, once you have done this you and delete the plugin from your source tree.

You can also customise the templates, and then save them for future projects.

The first thing to do is setup your database and config.

Run the console command (this is a shell script in the bin folder) and this will show the options that are available to you.

```linux 
$ bin/console generate
```

So lets you are creating a contact app, and you have a `contacts` table setup

We will need to create the Model, View and Controller (MVC);

```linux
$ bin/console generate controller Contacts
```

```linux
$ bin/console generate model Contact
```

```linux
$ bin/console generate view Contacts
```

This will generate the following views
- `/contacts/add` 
- `/contacts/edit`
- `/contacts/index`
- `/contacts/view`

You can also generate the Model,View,Controller (MVC) in one go

```linux
$ bin/console generate all Contact
```

If all went ok then you should be able to access your contacts app by `http://localhost:8000/contacts`

Whats is really great, is if you look in the `plugin/make` folder you will find easy to edit templates, simply
modify your html there, wrap up stuff in divs, add classes and then make the code. It requires no learning at all.

There are number of vars which are used, and in most cases you wont even need to use these as you will modifying the html structure in the templates.

- `%model%` e.g. BookmarksTag
- `%controller%` e.g. BookmarksTags
- `%singularName%` e.g. bookmarksTag
- `%pluralName%` e.g. bookmarksTags
- `%singularHuman%` e.g. Bookmarks Tag
- `%pluralHuman%` e.g. Bookmarks Tags
- `%singularHumanLower%` e.g. bookmarks tag
- `%pluralHumanLower%` e.g. bookmarks tags
- `%controllerUnderscored%` e.g. bookmarks_tags
- `%primaryKey%` e.g. id