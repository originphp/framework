# PostgreSQL setup

PostgreSQL is work in progress since and brings a number of challenges. 

1. The getMetadata does not return the alias for table
2. User is a reserved word and force escaping of everything

```php
ConnectionManager::config('default', array(
  'host' => 'pg',
  'database' => 'origin',
  'username' => 'root',
  'password' => 'root',
  'engine' => 'pgsql'
));
```

Add the following to docker-compose.yml

```yml
 pg:
    image: postgres
    restart: always
    environment:
      POSTGRES_USER: root
      POSTGRES_PASSWORD: root
    ports:
        - "5432:5432"
```

To dockerfile setup to install the following

```
 postgresql-client
 php-pgsql
```